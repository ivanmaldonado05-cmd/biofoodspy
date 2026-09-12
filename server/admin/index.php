<?php
/* Panel de administración de pedidos (producción).
   Login con sesión · lista/filtros · detalle · cambio de estado · exportar CSV · comprobante. */
session_start();
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../db.php';
$c = cfg();

/* ---------- auth ---------- */
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

if (!empty($_POST['login'])) {
  $u = $_POST['user'] ?? ''; $p = $_POST['pass'] ?? '';
  if ($u === $c['admin_user'] && password_verify($p, $c['admin_pass_hash'])) {
    $_SESSION['bf_admin'] = true; header('Location: index.php'); exit;
  } else { $loginErr = 'Usuario o contraseña incorrectos.'; }
}
$authed = !empty($_SESSION['bf_admin']);

/* ---------- login view ---------- */
if (!$authed) { ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin — Biofoods</title>
<style>body{font-family:system-ui,Arial,sans-serif;background:#fbf7ee;display:grid;place-items:center;min-height:100vh;margin:0}
.card{background:#fff;border:1px solid #e7dcc6;border-radius:20px;padding:2.2rem;width:min(380px,92%);box-shadow:0 18px 44px rgba(20,41,27,.14);text-align:center}
h1{color:#1e3a2b;font-size:1.3rem}label{display:block;text-align:left;font-weight:700;font-size:.82rem;margin:.9rem 0 .3rem}
input{width:100%;padding:.75rem;border:1.6px solid #ded2b9;border-radius:10px;box-sizing:border-box}
button{margin-top:1.2rem;width:100%;padding:.8rem;background:#1e3a2b;color:#fbf7ee;border:0;border-radius:100px;font-weight:700;cursor:pointer}
.err{color:#bd6440;font-size:.85rem;margin-top:.6rem}</style></head>
<body><form class="card" method="post">
<h1>Panel de administración</h1>
<label>Usuario</label><input name="user" autocomplete="username">
<label>Contraseña</label><input name="pass" type="password" autocomplete="current-password">
<button name="login" value="1">Ingresar</button>
<?php if (!empty($loginErr)) echo '<p class="err">'.e($loginErr).'</p>'; ?>
</form></body></html>
<?php exit; }

/* ---------- comprobante download (autenticado) ---------- */
if (!empty($_GET['proof'])) {
  $name = basename($_GET['proof']);
  $path = $c['upload_dir'] . '/' . $name;
  if (preg_match('/^comprobante_[\w.-]+$/', $name) && file_exists($path)) {
    $fi = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($fi, $path); finfo_close($fi);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $name . '"');
    readfile($path); exit;
  }
  http_response_code(404); exit('No encontrado');
}

$pdo = db();

/* ---------- cambio de estado ---------- */
if (!empty($_POST['action']) && $_POST['action'] === 'status') {
  $st = in_array($_POST['status'] ?? '', ['pending','paid','done','cancel'], true) ? $_POST['status'] : null;
  $oid = (int)($_POST['id'] ?? 0);
  if ($st && $oid) { $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$st, $oid]); }
  header('Location: index.php' . (!empty($_POST['back']) ? '?id='.$oid : '')); exit;
}

/* ---------- filtros ---------- */
$q = trim($_GET['q'] ?? ''); $fs = $_GET['status'] ?? ''; $fo = $_GET['source'] ?? '';
$where = []; $args = [];
if ($fs !== '') { $where[] = 'status = ?'; $args[] = $fs; }
if ($fo !== '') { $where[] = 'source = ?'; $args[] = $fo; }
if ($q !== '')  { $where[] = '(name LIKE ? OR email LIKE ? OR id = ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = (int)ltrim($q,'#'); }
$wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- exportar CSV ---------- */
if (($_GET['export'] ?? '') === 'csv') {
  $rows = $pdo->prepare("SELECT * FROM orders $wsql ORDER BY id DESC"); $rows->execute($args);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="pedidos_biofoods.csv"');
  $out = fopen('php://output', 'w');
  fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para Excel
  fputcsv($out, ['N°','Fecha','Cliente','Email','Teléfono','Entrega','Ciudad','Total','Pago','Origen','Estado']);
  foreach ($rows as $r) {
    fputcsv($out, [order_code($r['id']), $r['created_at'], $r['name'], $r['email'], $r['phone'],
      $r['delivery'], $r['city'], $r['total'], $r['payment'], $r['source'], $r['status']]);
  }
  fclose($out); exit;
}

/* ---------- KPIs ---------- */
$kpi = $pdo->query("SELECT
  COALESCE(SUM(CASE WHEN status IN ('paid','done') THEN total END),0) AS revenue,
  COUNT(*) AS total_orders,
  SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending
  FROM orders WHERE created_at >= (NOW() - INTERVAL 7 DAY)")->fetch();
$topSrc = $pdo->query("SELECT source, COUNT(*) n FROM orders WHERE created_at >= (NOW() - INTERVAL 7 DAY) GROUP BY source ORDER BY n DESC LIMIT 1")->fetch();

/* ---------- detalle ---------- */
$detail = null;
if (!empty($_GET['id'])) {
  $d = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $d->execute([(int)$_GET['id']]); $detail = $d->fetch();
}

/* ---------- lista ---------- */
$list = $pdo->prepare("SELECT * FROM orders $wsql ORDER BY id DESC LIMIT 300"); $list->execute($args);
$orders = $list->fetchAll();

$PILL = ['pending'=>['#fbeccb','#8a6516','Pendiente'],'paid'=>['#d8ecdc','#1f6b3a','Pagado'],'done'=>['#dbe6f5','#2b4c7e','Entregado'],'cancel'=>['#f2dcd4','#9a4526','Cancelado']];
function pill($s,$P){ $p=$P[$s]??['#eee','#333',$s]; return "<span style='background:{$p[0]};color:{$p[1]};padding:3px 10px;border-radius:100px;font-size:12px;font-weight:800'>{$p[2]}</span>"; }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pedidos — Biofoods Admin</title>
<style>
:root{--forest:#1e3a2b;--cream:#fbf7ee;--cream2:#f4ecdd;--sand:#e7dcc6;--muted:#6d7263;--amber:#bd8121}
*{box-sizing:border-box}body{font-family:system-ui,Arial,sans-serif;margin:0;background:var(--cream2);color:#232619}
.top{background:var(--forest);color:var(--cream);padding:1rem 1.4rem;display:flex;justify-content:space-between;align-items:center}
.top h1{margin:0;font-size:1.25rem}.top a{color:#cbd6c6;text-decoration:none;font-size:.9rem}
.wrap{max-width:1100px;margin:1.4rem auto;padding:0 1rem}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.4rem}
.kpi{background:#fff;border:1px solid var(--sand);border-radius:16px;padding:1rem}
.kpi small{color:var(--muted);font-weight:700;font-size:.72rem;text-transform:uppercase}
.kpi b{display:block;font-size:1.5rem;color:var(--forest);margin-top:.3rem}
.panel{background:#fff;border:1px solid var(--sand);border-radius:16px;overflow:hidden}
.phead{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;padding:1rem;border-bottom:1px solid var(--cream2)}
input,select{padding:.5rem .7rem;border:1.5px solid var(--sand);border-radius:100px;font-size:.88rem}
.btn{background:var(--forest);color:var(--cream);border:0;border-radius:100px;padding:.5rem 1rem;font-weight:700;cursor:pointer;text-decoration:none;font-size:.85rem;display:inline-block}
.btn.ghost{background:transparent;color:var(--forest);border:1.5px solid var(--sand)}
table{width:100%;border-collapse:collapse}th,td{padding:.7rem 1rem;text-align:left;font-size:.88rem;border-top:1px solid var(--cream2)}
th{background:var(--cream);font-size:.72rem;text-transform:uppercase;color:var(--muted)}
tr:hover td{background:#faf6ec}a.row{color:inherit;text-decoration:none}
.detail{background:#fff;border:1px solid var(--sand);border-radius:16px;padding:1.3rem;margin-bottom:1.2rem}
.detail .r{display:flex;justify-content:space-between;padding:.25rem 0;border-bottom:1px solid var(--cream2)}
@media(max-width:700px){.kpis{grid-template-columns:1fr 1fr}table{font-size:.8rem}}
</style></head><body>
<div class="top"><h1>🌿 Biofoods — Pedidos</h1><a href="?logout=1">Cerrar sesión ✕</a></div>
<div class="wrap">

  <div class="kpis">
    <div class="kpi"><small>Ventas (semana)</small><b><?=money($kpi['revenue'])?></b></div>
    <div class="kpi"><small>Pedidos (semana)</small><b><?=(int)$kpi['total_orders']?></b></div>
    <div class="kpi"><small>Pendientes</small><b style="color:var(--amber)"><?=(int)$kpi['pending']?></b></div>
    <div class="kpi"><small>Top origen</small><b><?=e($topSrc['source'] ?? '—')?></b></div>
  </div>

  <?php if ($detail): $P=$PILL; $items=json_decode($detail['items'],true)?:[]; ?>
  <div class="detail">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
      <h2 style="margin:0;color:var(--forest)">Pedido <?=order_code($detail['id'])?> <?=pill($detail['status'],$P)?></h2>
      <a class="btn ghost" href="index.php">← Volver</a>
    </div>
    <div class="r"><span>Fecha</span><b><?=e($detail['created_at'])?></b></div>
    <div class="r"><span>Cliente</span><b><?=e($detail['name'])?> · <?=e($detail['email'])?> · <?=e($detail['phone'])?></b></div>
    <div class="r"><span>Entrega</span><b><?=$detail['delivery']==='retiro'?'Retiro':'Envío'?><?=$detail['city']?' — '.e($detail['city']):''?></b></div>
    <?php if($detail['address']):?><div class="r"><span>Dirección</span><b><?=e($detail['address'])?></b></div><?php endif;?>
    <?php if($detail['location_lat']):?><div class="r"><span>Ubicación</span><a target="_blank" href="https://maps.google.com/?q=<?=$detail['location_lat']?>,<?=$detail['location_lng']?>">Ver en Google Maps</a></div><?php endif;?>
    <div class="r"><span>Origen</span><b><?=e($detail['source'])?></b></div>
    <div style="margin:.8rem 0"><b>Productos</b><table><?php foreach($items as $it):?><tr><td><?=(int)$it['qty']?>× <?=e($it['title'])?> <?=$it['size']?'('.e($it['size']).')':''?></td><td align="right"><?=money($it['price']*$it['qty'])?></td></tr><?php endforeach;?>
      <tr><td>Envío</td><td align="right"><?=$detail['shipping']?money($detail['shipping']):'Gratis'?></td></tr>
      <tr><td><b>Total</b></td><td align="right"><b><?=money($detail['total'])?></b></td></tr></table></div>
    <div class="r"><span>Pago</span><b><?=$detail['payment']==='tarjeta'?'Tarjeta (Bancard)':'Transferencia'?></b>
      <?php if($detail['proof_file']):?><a class="btn ghost" target="_blank" href="?proof=<?=urlencode($detail['proof_file'])?>">📎 Ver comprobante</a><?php endif;?></div>
    <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
      <?php foreach(['paid'=>'Marcar pagado','done'=>'Marcar entregado','pending'=>'Marcar pendiente','cancel'=>'Cancelar'] as $st=>$lbl): if($st!==$detail['status']):?>
      <form method="post" style="display:inline"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=$detail['id']?>"><input type="hidden" name="status" value="<?=$st?>"><input type="hidden" name="back" value="1"><button class="btn <?=$st==='cancel'?'ghost':''?>"><?=$lbl?></button></form>
      <?php endif; endforeach;?>
    </div>
  </div>
  <?php endif; ?>

  <div class="panel">
    <form class="phead" method="get">
      <input name="q" placeholder="Buscar cliente o N°…" value="<?=e($q)?>">
      <select name="status"><option value="">Todos los estados</option><?php foreach(['pending'=>'Pendiente','paid'=>'Pagado','done'=>'Entregado','cancel'=>'Cancelado'] as $k=>$v):?><option value="<?=$k?>" <?=$fs===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select>
      <input name="source" placeholder="Origen" value="<?=e($fo)?>">
      <button class="btn">Filtrar</button>
      <a class="btn ghost" href="?export=csv&q=<?=urlencode($q)?>&status=<?=e($fs)?>&source=<?=urlencode($fo)?>">⬇ Exportar CSV</a>
    </form>
    <table>
      <thead><tr><th>N°</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Entrega</th><th>Pago</th><th>Origen</th><th>Estado</th></tr></thead>
      <tbody>
      <?php if(!$orders):?><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">No hay pedidos.</td></tr><?php endif;?>
      <?php foreach($orders as $o):?>
        <tr onclick="location.href='?id=<?=$o['id']?>'" style="cursor:pointer">
          <td><b><?=order_code($o['id'])?></b></td>
          <td><?=date('d/m H:i', strtotime($o['created_at']))?></td>
          <td><b><?=e($o['name'])?></b><br><small style="color:var(--muted)"><?=e($o['email'])?></small></td>
          <td><?=money($o['total'])?></td>
          <td><?=$o['delivery']==='retiro'?'Retiro':'Envío'?><?=$o['city']?' · '.e($o['city']):''?></td>
          <td><?=$o['payment']==='tarjeta'?'Tarjeta':'Transf.'?></td>
          <td><?=e($o['source'])?></td>
          <td><?=pill($o['status'],$PILL)?></td>
        </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div></body></html>
