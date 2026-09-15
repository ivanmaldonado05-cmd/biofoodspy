<?php
/* Bancard vPOS — crear operación de pago (single_buy).
   Devuelve el process_id que el frontend usa para mostrar el formulario de tarjeta (iframe de Bancard).
   NOTA: verificar nombres de campos/URLs contra la documentación vigente de Bancard al integrar,
   y probar primero en el entorno de STAGING con tarjetas de prueba.
   Requiere claves reales en config.php (bancard.public_key / private_key). */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../db.php';

$c = cfg(); $b = $c['bancard'];
if ($b['public_key'] === 'REEMPLAZAR_public_key') json_out(['ok'=>false,'error'=>'Bancard no configurado.'], 500);

$orderId = (int)($_POST['order_id'] ?? 0);
if (!$orderId) json_out(['ok'=>false,'error'=>'Falta order_id'], 422);

$pdo = db();
$o = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$orderId]); $order = $o->fetch();
if (!$order) json_out(['ok'=>false,'error'=>'Pedido no encontrado'], 404);

$shop_process_id = (int)$order['id'];                 // id único de la operación
$amount   = number_format((float)$order['total'], 2, '.', ''); // "150000.00"
$currency = 'PYG';

// token de seguridad exigido por Bancard
$token = md5($b['private_key'] . $shop_process_id . $amount . $currency);

$payload = [
  'public_key' => $b['public_key'],
  'operation'  => [
    'token'           => $token,
    'shop_process_id' => $shop_process_id,
    'amount'          => $amount,
    'currency'        => $currency,
    'description'     => 'Pedido ' . order_code($order['id']) . ' - Biofoods',
    'return_url'      => $b['return_url'] . '?order=' . $order['id'],
    'cancel_url'      => $b['return_url'] . '?order=' . $order['id'] . '&cancel=1',
  ],
];

$base = $b['env'] === 'production' ? $b['base_production'] : $b['base_staging'];
$ch = curl_init($base . '/vpos/api/0.3/single_buy');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 20,
]);
$res = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
if ($res === false) json_out(['ok'=>false,'error'=>'Bancard no respondió: '.$err], 502);

$data = json_decode($res, true);
$processId = $data['process_id'] ?? null;
if (!$processId) json_out(['ok'=>false,'error'=>'Sin process_id','bancard'=>$data], 502);

$pdo->prepare("UPDATE orders SET bancard_process_id=? WHERE id=?")->execute([$processId, $order['id']]);
// URL del JS del checkout de Bancard (verificar ruta/versión exacta con la documentación de Bancard).
$jsUrl = $base . '/checkout/javascript/dist/bancard-checkout-4.0.0.js';
json_out(['ok'=>true, 'process_id'=>$processId, 'js_url'=>$jsUrl]);
