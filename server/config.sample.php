<?php
/* =========================================================
   Biofoods — Configuración (PLANTILLA)
   1) Copiá este archivo como  config.php
   2) Completá los valores
   3) NUNCA subas config.php con datos reales a un repo público
   ========================================================= */

return [

  // ---------- Base de datos MySQL (Hostinger → Bases de datos) ----------
  'db' => [
    'host' => 'localhost',
    'name' => 'REEMPLAZAR_nombre_db',
    'user' => 'REEMPLAZAR_usuario_db',
    'pass' => 'REEMPLAZAR_password_db',
  ],

  // ---------- Correos ----------
  'business_email' => 'info@biofoodspy.com',      // recibe los pedidos + comprobante
  'report_recipients' => ['info@biofoodspy.com', 'ivanmaldonado05@gmail.com'], // reporte semanal
  'mail_from'  => 'pedidos@biofoodspy.com',       // remitente (casilla del dominio)
  'mail_from_name' => 'Biofoods Paraguay',

  // ---------- SMTP (recomendado; casilla del dominio en Hostinger) ----------
  // Si se deja vacío 'host', se usa la función mail() de PHP como respaldo.
  'smtp' => [
    'host' => '',                  // ej. smtp.hostinger.com
    'port' => 465,                 // 465 (SSL) o 587 (TLS)
    'secure' => 'ssl',             // 'ssl' o 'tls'
    'user' => '',                  // ej. pedidos@biofoodspy.com
    'pass' => '',
  ],

  // ---------- Panel de administración ----------
  // Generá el hash con:  php -r "echo password_hash('TU_CLAVE', PASSWORD_DEFAULT);"
  'admin_user' => 'biofoods',
  'admin_pass_hash' => 'REEMPLAZAR_hash_generado',

  // ---------- Bancard vPOS ----------
  'bancard' => [
    'env' => 'staging',            // 'staging' (pruebas) o 'production'
    'public_key'  => 'REEMPLAZAR_public_key',
    'private_key' => 'REEMPLAZAR_private_key',
    // URLs oficiales de Bancard (confirmar en su documentación al integrar):
    'base_staging'    => 'https://vpos.infonet.com.py:8888',
    'base_production' => 'https://vpos.infonet.com.py',
    // A dónde vuelve el cliente después de pagar / URL que Bancard llama para confirmar:
    'return_url'  => 'https://biofoodspy.com/pago-resultado.html',
    'confirm_url' => 'https://biofoodspy.com/server/bancard/confirm.php',
  ],

  // ---------- General ----------
  'site_name' => 'Biofoods Paraguay',
  'upload_dir' => __DIR__ . '/uploads',           // carpeta privada para comprobantes
  'allowed_origin' => '',                          // dejar vacío si el front está en el mismo dominio
  'bank' => [
    'banco' => 'Banco Sudameris', 'cuenta' => 'Cta. Cte. 5632542',
    'titular' => 'CENIN EAS', 'ci' => 'C.I. 80142067-9', 'alias' => '801420679',
  ],
];
