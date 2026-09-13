<?php
/* Reporte semanal de ventas — se ejecuta por cron (ej. lunes 08:00).
   Hostinger → Avanzado → Cron Jobs:  php /home/USUARIO/.../server/cron/weekly_report.php
   Envía: cantidad de pedidos, ventas y desglose por origen, al negocio y al desarrollador. */
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../db.php';

/* Protección: el cron (CLI) siempre corre; por navegador solo con ?key=... */
$CRON_KEY = 'bf-report-2026';
if (php_sapi_name() !== 'cli') {
  if (($_GET['key'] ?? '') !== $CRON_KEY) { http_response_code(403); exit('No autorizado'); }
}

$pdo = db();

$tot = $pdo->query("SELECT COUNT(*) n,
    COALESCE(SUM(CASE WHEN status IN ('paid','done') THEN total END),0) rev,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pend
  FROM orders WHERE created_at >= (NOW() - INTERVAL 7 DAY)")->fetch();

$bySrc = $pdo->query("SELECT COALESCE(source,'Directo') src, COUNT(*) n,
    COALESCE(SUM(CASE WHEN status IN ('paid','done') THEN total END),0) rev
  FROM orders WHERE created_at >= (NOW() - INTERVAL 7 DAY)
  GROUP BY src ORDER BY n DESC")->fetchAll();

$rows = '';
foreach ($bySrc as $s) {
  $rows .= "<tr><td style='padding:4px 0'>" . e($s['src']) . "</td><td align='right'>" . (int)$s['n'] . "</td><td align='right'>" . money($s['rev']) . "</td></tr>";
}
if (!$rows) $rows = "<tr><td colspan='3' style='color:#6d7263'>Sin pedidos esta semana.</td></tr>";

$desde = date('d/m', strtotime('-7 day')); $hasta = date('d/m');
$html = "<div style='font-family:Arial,sans-serif;color:#232619'>"
  . "<h2 style='color:#1e3a2b'>Reporte semanal de ventas</h2>"
  . "<p style='color:#6d7263'>Semana {$desde} – {$hasta}</p>"
  . "<p><b>Pedidos:</b> " . (int)$tot['n'] . " &nbsp;·&nbsp; <b>Ventas:</b> " . money($tot['rev']) . " &nbsp;·&nbsp; <b>Pendientes:</b> " . (int)$tot['pend'] . "</p>"
  . "<h3 style='color:#1e3a2b;margin-top:16px'>Por origen</h3>"
  . "<table style='width:100%;border-collapse:collapse;font-size:14px'>"
  . "<tr style='color:#6d7263'><td>Origen</td><td align='right'>Pedidos</td><td align='right'>Ventas</td></tr>"
  . $rows . "</table>"
  . "<p style='margin-top:16px;color:#6d7263;font-size:12px'>Biofoods · reporte automático</p></div>";

$ok = send_mail(cfg()['report_recipients'], "Reporte semanal — Biofoods ({$desde}–{$hasta})", $html);
echo $ok ? "Reporte enviado\n" : "Fallo el envío\n";
