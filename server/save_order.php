<?php
/* Recibe el pedido del checkout (FormData), lo guarda, guarda el comprobante,
   y envía: (1) aviso al negocio con el comprobante adjunto, (2) confirmación al cliente. */
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/mailer.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { json_out(['ok' => true]); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { json_out(['ok' => false, 'error' => 'Método no permitido'], 405); }

$c = cfg();

// ---- datos ----
$name    = clean($_POST['name'] ?? '', 160);
$email   = clean($_POST['email'] ?? '', 160);
$phone   = clean($_POST['phone'] ?? '', 60);
$deliv   = ($_POST['deliv'] ?? 'envio') === 'retiro' ? 'retiro' : 'envio';
$city    = clean($_POST['city'] ?? '', 120);
$address = clean($_POST['address'] ?? '', 255);
$lat     = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng     = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
$note    = clean($_POST['note'] ?? '', 500);
$source  = clean($_POST['source'] ?? 'Directo', 120);
$payment = ($_POST['payment'] ?? 'transferencia') === 'tarjeta' ? 'tarjeta' : 'transferencia';
$subtotal= (int)($_POST['subtotal'] ?? 0);
$shipping= (int)($_POST['shipping'] ?? 0);
$total   = (int)($_POST['total'] ?? ($subtotal + $shipping));
$items   = json_decode($_POST['items'] ?? '[]', true) ?: [];

// ---- validación ----
if ($name === '')                                   json_out(['ok'=>false,'error'=>'Falta el nombre.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     json_out(['ok'=>false,'error'=>'Email inválido.'], 422);
if ($phone === '')                                  json_out(['ok'=>false,'error'=>'Falta el teléfono.'], 422);
if (!$items)                                        json_out(['ok'=>false,'error'=>'El carrito está vacío.'], 422);
if ($deliv === 'envio' && ($city === '' || $address === '')) json_out(['ok'=>false,'error'=>'Faltan datos de envío.'], 422);

// ---- comprobante (obligatorio en transferencia) ----
$proofName = null;
if ($payment === 'transferencia') {
  if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    json_out(['ok'=>false,'error'=>'Adjuntá tu comprobante de transferencia.'], 422);
  }
  $f = $_FILES['proof'];
  if ($f['size'] > 10 * 1024 * 1024) json_out(['ok'=>false,'error'=>'El comprobante supera los 10 MB.'], 422);
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $f['tmp_name']); finfo_close($finfo);
  $okTypes = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
  if (!isset($okTypes[$mime])) json_out(['ok'=>false,'error'=>'El comprobante debe ser imagen o PDF.'], 422);

  if (!is_dir($c['upload_dir'])) @mkdir($c['upload_dir'], 0775, true);
  $proofName = 'comprobante_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $okTypes[$mime];
  if (!move_uploaded_file($f['tmp_name'], $c['upload_dir'] . '/' . $proofName)) {
    json_out(['ok'=>false,'error'=>'No se pudo guardar el comprobante.'], 500);
  }
}

// ---- guardar en la base ----
$pdo = db();
$stmt = $pdo->prepare("INSERT INTO orders
  (name,email,phone,delivery,city,address,location_lat,location_lng,subtotal,shipping,total,payment,status,proof_file,source,note,items)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([
  $name,$email,$phone,$deliv,($deliv==='envio'?$city:null),($deliv==='envio'?$address:null),
  $lat,$lng,$subtotal,$shipping,$total,$payment,
  'pending', // transferencia: la verifica el negocio · tarjeta: la confirma Bancard (bancard/confirm.php)
  $proofName,$source,$note,json_encode($items, JSON_UNESCAPED_UNICODE)
]);
$id = (int)$pdo->lastInsertId();
$code = order_code($id);

// ---- armar cuerpos de email ----
$itemsHtml = '';
foreach ($items as $it) {
  $t = e($it['title'] ?? ''); $s = e($it['size'] ?? ''); $q = (int)($it['qty'] ?? 1); $p = (int)($it['price'] ?? 0);
  $itemsHtml .= "<tr><td style='padding:4px 0'>{$q}× {$t}" . ($s?" <span style='color:#6d7263'>({$s})</span>":"") . "</td>"
             . "<td align='right' style='padding:4px 0'>" . money($p*$q) . "</td></tr>";
}
$delivLabel = $deliv === 'retiro' ? 'Retiro en local' : ('Envío a domicilio' . ($city ? " — {$city}" : ''));
$locLine = ($deliv==='envio' && $lat && $lng) ? "<p>📍 Ubicación: <a href='https://maps.google.com/?q={$lat},{$lng}'>ver en Google Maps</a></p>" : '';
$bank = $c['bank'];
$bankLine = "{$bank['banco']} · {$bank['cuenta']} · {$bank['titular']} · Alias {$bank['alias']}";

$summary = "<table style='width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px'>{$itemsHtml}"
  . "<tr><td style='padding-top:8px'>Envío</td><td align='right' style='padding-top:8px'>" . ($shipping?money($shipping):'Gratis') . "</td></tr>"
  . "<tr><td style='font-weight:bold;padding-top:6px'>Total</td><td align='right' style='font-weight:bold;padding-top:6px'>" . money($total) . "</td></tr></table>";

// (1) al negocio
$bizHtml = "<div style='font-family:Arial,sans-serif;color:#232619'>"
  . "<h2 style='color:#1e3a2b'>Nuevo pedido {$code}</h2>"
  . "<p><b>Cliente:</b> " . e($name) . "<br><b>Email:</b> " . e($email) . "<br><b>Teléfono:</b> " . e($phone)
  . "<br><b>Origen:</b> " . e($source) . "<br><b>Entrega:</b> " . e($delivLabel)
  . ($address ? "<br><b>Dirección:</b> " . e($address) : '') . "</p>{$locLine}"
  . ($note ? "<p><b>Nota:</b> " . e($note) . "</p>" : '')
  . "<p><b>Pago:</b> " . ($payment==='tarjeta'?'Tarjeta (Bancard)':'Transferencia — comprobante adjunto') . "</p>"
  . $summary . "</div>";
$proofPath = $proofName ? ($c['upload_dir'] . '/' . $proofName) : null;
@send_mail($c['business_email'], "Nuevo pedido {$code} — Biofoods", $bizHtml, $proofPath, $proofName);

// (2) al cliente (un solo email)
$custHtml = "<div style='font-family:Arial,sans-serif;color:#232619'>"
  . "<h2 style='color:#1e3a2b'>¡Gracias por tu pedido, " . e($name) . "!</h2>"
  . "<p>Recibimos tu pedido <b>{$code}</b> y tu comprobante. Verificamos la transferencia y te avisamos apenas esté confirmada.</p>"
  . "<p><b>Entrega:</b> " . e($delivLabel) . "</p>"
  . $summary
  . ($payment==='transferencia' ? "<p style='margin-top:12px;color:#6d7263'>Datos de la transferencia: {$bankLine}</p>" : '')
  . "<p style='margin-top:16px'>Biofoods Paraguay 🌿</p></div>";
$emailed = @send_mail($email, "Recibimos tu pedido {$code} — Biofoods", $custHtml);

json_out(['ok' => true, 'order_id' => $id, 'code' => $code, 'emailed' => (bool)$emailed]);
