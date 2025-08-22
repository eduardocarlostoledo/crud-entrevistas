<?php
require __DIR__.'/db.php';

// CSRF muy básico (mejor usar libs en producción)
session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

function redirect($path = ''){ header('Location: '.base_url().($path ? '?'.$path : '')); exit; }

// Helpers de validación mínima
function bool($name){ return isset($_POST[$name]) && $_POST[$name] === 'on'; }
function str($name){ return trim($_POST[$name] ?? ''); }
function intv($name){ return (int)($_POST[$name] ?? 0); }

// CREATE
if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['_csrf'] ?? '')) die('CSRF inválido');

  $sql = 'INSERT INTO postulantes (nombres, apellidos, dni, email, telefono, edad, tiene_titulo, titulo_detalle,
           experiencia_php, experiencia_js, experiencia_sql, experiencia_postgres, experiencia_siu_toba, observaciones)
          VALUES (:nombres,:apellidos,:dni,:email,:telefono,:edad,:tiene_titulo,:titulo_detalle,
           :php,:js,:sql,:pg,:toba,:obs)';
  $stmt = db()->prepare($sql);
  try {
    $stmt->execute([
      ':nombres' => str('nombres'),
      ':apellidos'=> str('apellidos'),
      ':dni' => str('dni'),
      ':email' => str('email'),
      ':telefono'=> str('telefono'),
      ':edad' => intv('edad'),
      ':tiene_titulo' => isset($_POST['tiene_titulo']) ? 't' : 'f',
      ':titulo_detalle' => str('titulo_detalle') ?: null,
      ':php' => bool('experiencia_php') ? 't' : 'f',
      ':js'  => bool('experiencia_js') ? 't' : 'f',
      ':sql' => bool('experiencia_sql') ? 't' : 'f',
      ':pg'  => bool('experiencia_postgres') ? 't' : 'f',
      ':toba'=> bool('experiencia_siu_toba') ? 't' : 'f',
      ':obs' => str('observaciones') ?: null,
    ]);
  } catch (PDOException $e) {
    http_response_code(400);
    die('Error al guardar: '.$e->getMessage());
  }
  redirect();
}

// UPDATE
if ($action === 'update' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['_csrf'] ?? '')) die('CSRF inválido');
  $sql = 'UPDATE postulantes SET
            nombres=:nombres, apellidos=:apellidos, dni=:dni, email=:email, telefono=:telefono,
            edad=:edad, tiene_titulo=:tiene_titulo, titulo_detalle=:titulo_detalle,
            experiencia_php=:php, experiencia_js=:js, experiencia_sql=:sql,
            experiencia_postgres=:pg, experiencia_siu_toba=:toba, observaciones=:obs
          WHERE id=:id';
  $stmt = db()->prepare($sql);
  $stmt->execute([
    ':id' => $id,
    ':nombres' => str('nombres'),
    ':apellidos'=> str('apellidos'),
    ':dni' => str('dni'),
    ':email' => str('email'),
    ':telefono'=> str('telefono'),
    ':edad' => intv('edad'),
    ':tiene_titulo' => isset($_POST['tiene_titulo']) ? 't' : 'f',
    ':titulo_detalle' => str('titulo_detalle') ?: null,
    ':php' => bool('experiencia_php') ? 't' : 'f',
    ':js'  => bool('experiencia_js') ? 't' : 'f',
    ':sql' => bool('experiencia_sql') ? 't' : 'f',
    ':pg'  => bool('experiencia_postgres') ? 't' : 'f',
    ':toba'=> bool('experiencia_siu_toba') ? 't' : 'f',
    ':obs' => str('observaciones') ?: null,
  ]);
  redirect();
}

// DELETE
if ($action === 'destroy' && $id) {
  $stmt = db()->prepare('DELETE FROM postulantes WHERE id=:id');
  $stmt->execute([':id'=>$id]);
  redirect();
}

// READ (show)
if ($action === 'show' && $id) {
  $stmt = db()->prepare('SELECT * FROM postulantes WHERE id=:id');
  $stmt->execute([':id'=>$id]);
  $p = $stmt->fetch();
  if (!$p) { http_response_code(404); die('No encontrado'); }
  echo headerHtml('Detalle postulante');
  echo botonVolver();
  echo renderForm($p, 'update&id='.$p['id'], 'Actualizar');
  echo footerHtml();
  exit;
}

// INDEX (listado + alta rápida)
$busq = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM postulantes';
$params = [];
if ($busq !== '') {
  $sql .= ' WHERE nombres ILIKE :q OR apellidos ILIKE :q OR dni ILIKE :q OR email ILIKE :q';
  $params[':q'] = "%$busq%";
}
$sql .= ' ORDER BY creado_en DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Vistas
function headerHtml($title='Postulantes'){ ob_start(); ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial,sans-serif;margin:24px;background:#f7f7fb;color:#222}
  .container{max-width:1050px;margin:auto}
  .card{background:white;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px}
  .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
  .col-6{grid-column:span 6}
  .col-12{grid-column:span 12}
  label{display:block;font-weight:600;margin-bottom:6px}
  input[type=text], input[type=number], input[type=email], textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px}
  input[type=checkbox]{transform:scale(1.1);margin-right:6px}
  .muted{color:#6b7280;font-size:13px}
  .row{display:flex;gap:8px;align-items:center}
  .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #374151;background:#111827;color:#fff;text-decoration:none}
  .btn.secondary{background:#fff;border-color:#9ca3af;color:#111827}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{border-bottom:1px solid #eee;padding:8px;text-align:left}
  .badge{background:#eef2ff;color:#3730a3;padding:2px 8px;border-radius:999px;font-size:12px}
</style>
</head>
<body><div class="container">
<h2><?= htmlspecialchars($title) ?></h2>
<?php return ob_get_clean(); }

function footerHtml(){ return '</div></body></html>'; }
function botonVolver(){ return '<p><a class="btn secondary" href="'.base_url().'">← Volver</a></p>'; }

function renderForm($p = null, $actionPath='store', $cta='Guardar'){
  $csrf = $_SESSION['csrf'];
  $v = fn($k,$d='')=>htmlspecialchars($p[$k]??$d);
  $ck = fn($k)=>!empty($p[$k]) ? 'checked' : '';
  ob_start(); ?>
  <form class="card" method="post" action="<?= base_url().'?action='.$actionPath ?>">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="grid">
      <div class="col-6"><label>Nombres</label><input type="text" name="nombres" required value="<?= $v('nombres') ?>"></div>
      <div class="col-6"><label>Apellidos</label><input type="text" name="apellidos" required value="<?= $v('apellidos') ?>"></div>
      <div class="col-6"><label>DNI</label><input type="text" name="dni" required value="<?= $v('dni') ?>"></div>
      <div class="col-6"><label>Email</label><input type="email" name="email" required value="<?= $v('email') ?>"></div>
      <div class="col-6"><label>Teléfono</label><input type="text" name="telefono" value="<?= $v('telefono') ?>"></div>
      <div class="col-6"><label>Edad (18–45)</label><input type="number" name="edad" min="18" max="45" required value="<?= $v('edad') ?>"></div>

      <div class="col-12 row">
        <label><input type="checkbox" name="tiene_titulo" <?= $ck('tiene_titulo') ?>> Tiene título (Lic./Analista)</label>
        <input type="text" name="titulo_detalle" placeholder="Ej: Analista de Sistemas" value="<?= $v('titulo_detalle') ?>" style="flex:1">
      </div>

      <div class="col-12"><span class="muted">Cualidades/Conocimientos</span></div>
      <div class="col-12 row">
        <label><input type="checkbox" name="experiencia_php" <?= $ck('experiencia_php') ?>> PHP</label>
        <label><input type="checkbox" name="experiencia_js" <?= $ck('experiencia_js') ?>> JS</label>
        <label><input type="checkbox" name="experiencia_sql" <?= $ck('experiencia_sql') ?>> SQL/PLpgSQL</label>
        <label><input type="checkbox" name="experiencia_postgres" <?= $ck('experiencia_postgres') ?>> PostgreSQL</label>
        <label><input type="checkbox" name="experiencia_siu_toba" <?= $ck('experiencia_siu_toba') ?>> SIU-TOBA</label>
      </div>

      <div class="col-12"><label>Observaciones</label><textarea name="observaciones" rows="4"><?= $v('observaciones') ?></textarea></div>
    </div>
    <div class="row" style="margin-top:12px">
      <button class="btn" type="submit"><?= htmlspecialchars($cta) ?></button>
      <a class="btn secondary" href="<?= base_url() ?>">Cancelar</a>
    </div>
  </form>
  <?php return ob_get_clean(); }

// Vista principal
echo headerHtml('Postulantes – Convocatoria Técnico en Sistemas');
?>

<div class="card">
  <form method="get" action="<?= base_url() ?>" class="row">
    <input type="hidden" name="action" value="index" />
    <input type="text" name="q" placeholder="Buscar por nombre, DNI o email" value="<?= htmlspecialchars($busq) ?>" style="flex:1">
    <button class="btn" type="submit">Buscar</button>
    <a class="btn secondary" href="<?= base_url() ?>">Limpiar</a>
  </form>
</div>

<?= renderForm(null, 'store', 'Agregar postulante') ?>

<div class="card">
  <h3>Listado (máx 200 últimos)</h3>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th><th>Nombre</th><th>DNI</th><th>Email</th><th>Edad</th><th>Conocimientos</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><span class="badge">#<?= (int)$r['id'] ?></span></td>
        <td><?= htmlspecialchars($r['apellidos'].', '.$r['nombres']) ?></td>
        <td><?= htmlspecialchars($r['dni']) ?></td>
        <td><?= htmlspecialchars($r['email']) ?></td>
        <td><?= (int)$r['edad'] ?></td>
        <td>
          <?= $r['experiencia_php'] ? 'PHP ' : '' ?>
          <?= $r['experiencia_js'] ? 'JS ' : '' ?>
          <?= $r['experiencia_sql'] ? 'SQL ' : '' ?>
          <?= $r['experiencia_postgres'] ? 'PG ' : '' ?>
          <?= $r['experiencia_siu_toba'] ? 'SIU-TOBA' : '' ?>
        </td>
        <td>
          <a class="btn secondary" href="<?= base_url().'?action=show&id='.(int)$r['id'] ?>">Editar</a>
          <a class="btn secondary" href="<?= base_url().'?action=destroy&id='.(int)$r['id'] ?>" onclick="return confirm('¿Eliminar postulante?')">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php echo footerHtml();