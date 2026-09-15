<?php
/* Devuelve el estado de un pedido (para la página de resultado del pago).
   No expone datos sensibles: solo estado, código y total. */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../db.php';

$id = (int)($_GET['order'] ?? 0);
if (!$id) json_out(['ok' => false], 400);

$o = db()->prepare("SELECT id, status, total FROM orders WHERE id=?");
$o->execute([$id]); $row = $o->fetch();
if (!$row) json_out(['ok' => false], 404);

json_out(['ok' => true, 'code' => order_code($row['id']), 'status' => $row['status'], 'total' => (int)$row['total']]);
