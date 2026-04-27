<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;
$menu_retorno  = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
$menu_principal = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};

// Cargar listado de procesos
$sql = "SELECT pr.pr_id, pr.pr_activo, pr.pr_fecha_creacion, pr.pr_fecha_modificacion,
               pi.pi_cod_prod, pi.pi_descripcion, pi.pi_color,
               ma.ma_no, ma.ma_marca, ma.ma_modelo,
               mo.mo_numero,
               re.re_tipo_resina, re.re_grado, re.re_cod_int,
               u.us_nombre AS creado_por
        FROM procesos pr
        JOIN piezas   pi ON pr.pr_pieza_id   = pi.pi_id
        JOIN maquinas ma ON pr.pr_maquina_id  = ma.ma_id
        LEFT JOIN moldes  mo ON pr.pr_molde_id  = mo.mo_id
        LEFT JOIN resinas re ON pr.pr_resina_id = re.re_id
        LEFT JOIN usuarios u ON pr.pr_usuario_id = u.us_id
        WHERE pr.pr_empresa_id = :empresa";
$p = [':empresa' => $empresaId];
if ($rol !== 1 && $plantaId) {
    $sql .= " AND ma.ma_planta = :planta";
    $p[':planta'] = $plantaId;
}
$sql .= " ORDER BY pr.pr_activo DESC, pr.pr_fecha_creacion DESC";
$stmt = $conn->prepare($sql); $stmt->execute($p);
$procesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Procesos de Ingeniería</title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png">
  <link rel="stylesheet" href="/css/acg.estilos.css">
  <style>
    .badge-activo   { background:#d1fae5; color:#065f46; padding:2px 10px; border-radius:12px; font-size:.78em; font-weight:700; }
    .badge-inactivo { background:#fee2e2; color:#991b1b; padding:2px 10px; border-radius:12px; font-size:.78em; font-weight:700; }
    .search-bar { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
    .search-bar input { padding:7px 10px; border:1px solid #d1d5db; border-radius:4px; font-size:.9em; min-width:220px; }
  </style>
</head>
<body>
<header class="header">
  <div class="header-title-group">
    <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
    <h1>Procesos de Ingeniería</h1>
  </div>
  <div class="header-right">
    <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    <?= burgerBtn() ?>
  </div>
</header>

<main class="main-container">
  <div class="form-section wide">

    <div class="search-bar">
      <input type="text" id="filtro" placeholder="🔍 Buscar por pieza, máquina, resina..." oninput="filtrar()">
      <a href="/ingenieria/proceso-nuevo.php" class="btn btn-guardar">➕ Nuevo proceso</a>
    </div>

    <div class="tabla-container-scroll">
      <table class="tabla-registros" id="tablaProc">
        <thead>
          <tr>
            <th>Código proceso</th>
            <th>Pieza</th>
            <th>Descripción</th>
            <th>Color</th>
            <th>Molde</th>
            <th>Resina</th>
            <th>Máquina</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($procesos as $p): ?>
          <tr class="<?= $p['pr_activo'] ? '' : 'fila-inactiva' ?>" style="<?= $p['pr_activo'] ? '' : 'opacity:.55;' ?>">
            <td style="font-family:monospace;font-weight:700;">
              <?= htmlspecialchars($p['pi_cod_prod'] . '/' . ($p['ma_no'] ?: $p['ma_marca'])) ?>
            </td>
            <td><?= htmlspecialchars($p['pi_cod_prod']) ?></td>
            <td><?= htmlspecialchars($p['pi_descripcion'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['pi_color'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['mo_numero'] ?? '—') ?></td>
            <td style="font-size:.82em;">
              <?= htmlspecialchars(($p['re_tipo_resina'] ?? '') . ($p['re_grado'] ? ' '.$p['re_grado'] : '')) ?>
            </td>
            <td><?= htmlspecialchars(($p['ma_no'] ? $p['ma_no'].' ' : '') . $p['ma_marca'] . ' ' . $p['ma_modelo']) ?></td>
            <td style="font-size:.82em;"><?= date('d/m/Y', strtotime($p['pr_fecha_creacion'])) ?></td>
            <td>
              <span class="badge-<?= $p['pr_activo'] ? 'activo' : 'inactivo' ?>">
                <?= $p['pr_activo'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td>
              <a href="/ingenieria/proceso-ver.php?id=<?= $p['pr_id'] ?>" class="btn-editar" style="padding:4px 8px;border-radius:3px;text-decoration:none;font-size:.8em;">
                <?= $p['pr_activo'] ? '✏️ Abrir' : '👁️ Ver' ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($procesos)): ?>
          <tr><td colspan="10" style="text-align:center;color:#888;padding:30px;">
            No hay procesos registrados. <a href="/ingenieria/proceso-nuevo.php">Crear el primero</a>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>
<script>
function filtrar() {
  const q = document.getElementById('filtro').value.toLowerCase();
  document.querySelectorAll('#tablaProc tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
