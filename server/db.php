<?php
/* Conexión PDO + creación del esquema si no existe */
require_once __DIR__ . '/lib/helpers.php';

function db() {
  static $pdo = null;
  if ($pdo !== null) return $pdo;
  $c = cfg()['db'];
  try {
    $pdo = new PDO(
      "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
      $c['user'], $c['pass'],
      [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
    );
  } catch (Throwable $ex) {
    http_response_code(500);
    die('Error de conexión a la base de datos.');
  }
  ensure_schema($pdo);
  return $pdo;
}

function ensure_schema($pdo) {
  $sql = file_get_contents(__DIR__ . '/schema.sql');
  if ($sql) { try { $pdo->exec($sql); } catch (Throwable $e) { /* ya existe */ } }
}
