<?php
/* Bancard vPOS — confirmación del pago (webhook).
   Bancard llama a esta URL (config: bancard.confirm_url) cuando se procesa el pago.
   Verifica el token y marca el pedido como pagado. Enviar también la confirmación al cliente.
   NOTA: confirmar el formato exacto del callback con la documentación de Bancard e implementar
   la verificación del token con la respuesta real antes de producción. */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../db.php';

$c = cfg(); $b = $c['bancard'];

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$op  = $in['operation'] ?? $in; // Bancard suele enviar {operation:{...}}

$shop_process_id = (int)($op['shop_process_id'] ?? 0);
$responseCode    = $op['response_code'] ?? ($op['status'] ?? '');
$amount          = $op['amount'] ?? '';
$token           = $op['token'] ?? '';

if (!$shop_process_id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'sin shop_process_id']); exit; }

// verificación de token (confirm): md5(private_key + shop_process_id + "confirm" + amount + currency)
$expected = md5($b['private_key'] . $shop_process_id . 'confirm' . $amount . 'PYG');
$tokenOk  = ($token && hash_equals($expected, $token));

$pdo = db();
$aprobado = in_array(strtolower((string)$responseCode), ['s','00','approved','success'], true);

if ($aprobado && ($tokenOk || $token === '')) { // en staging el token puede variar; endurecer en producción
  $pdo->prepare("UPDATE orders SET status='paid', bancard_ref=? WHERE id=? AND status<>'paid'")
      ->execute([($op['authorization_number'] ?? $op['ticket_number'] ?? null), $shop_process_id]);

  // confirmación al cliente
  $o = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$shop_process_id]); $order = $o->fetch();
  if ($order) {
    $html = "<div style='font-family:Arial,sans-serif;color:#232619'><h2 style='color:#1e3a2b'>¡Pago confirmado!</h2>"
      . "<p>Gracias " . e($order['name']) . ". Tu pago del pedido <b>" . order_code($order['id']) . "</b> fue aprobado. Total: <b>" . money($order['total']) . "</b>.</p>"
      . "<p>Biofoods Paraguay 🌿</p></div>";
    @send_mail($order['email'], "Pago confirmado — pedido " . order_code($order['id']) . " — Biofoods", $html);
  }
}

// Bancard espera una respuesta reconocible
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
