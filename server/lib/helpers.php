<?php
/* Helpers compartidos */

function cfg() {
  static $c = null;
  if ($c === null) {
    $file = __DIR__ . '/../config.php';
    if (!file_exists($file)) { http_response_code(500); die('Falta config.php (copiá config.sample.php).'); }
    $c = require $file;
  }
  return $c;
}

function money($n) { return 'Gs. ' . number_format((int)$n, 0, ',', '.'); }

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function clean($s, $max = 255) {
  $s = trim((string)$s);
  $s = str_replace(["\r", "\n", "\0"], ' ', $s);
  return mb_substr($s, 0, $max);
}

function json_out($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  $c = cfg();
  if (!empty($c['allowed_origin'])) {
    header('Access-Control-Allow-Origin: ' . $c['allowed_origin']);
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
  }
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function order_code($id) { return '#' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }
