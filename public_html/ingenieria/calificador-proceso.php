<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$procesoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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

// ── Sin proceso: selector ───────────────────────────────────
if (!$procesoId) {
    $stLst = $conn->prepare(
        "SELECT pr.pr_id, pi.pi_cod_prod, pi.pi_descripcion, pi.pi_color,
                ma.ma_no, ma.ma_marca, ma.ma_modelo
         FROM procesos pr
         JOIN piezas   pi ON pr.pr_pieza_id  = pi.pi_id
         JOIN maquinas ma ON pr.pr_maquina_id = ma.ma_id
         WHERE pr.pr_activo = 1 AND pr.pr_empresa_id = :emp
         ORDER BY pi.pi_cod_prod, ma.ma_marca");
    $stLst->execute([':emp' => $empresaId]);
    $lista = $stLst->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <!DOCTYPE html><html lang="es">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Calificador — Seleccionar proceso</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css"></head>
    <body>
    <header class="header">
      <div class="header-title-group"><img src="/imagenes/logo.png" alt="" class="header-logo">
        <h1>Calificador — Seleccionar proceso</h1></div>
      <div class="header-right"><a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?></div>
    </header>
    <main class="main-container"><div class="form-section">
      <p style="margin-bottom:14px;color:#555;font-size:.9em;">Selecciona el proceso a calificar:</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;">
        <?php foreach ($lista as $pr): ?>
        <a href="/ingenieria/calificador-proceso.php?id=<?= $pr['pr_id'] ?>"
           style="background:#fff;border:2px solid #e5e7eb;border-radius:8px;padding:14px 16px;
                  text-decoration:none;color:inherit;display:block;"
           onmouseover="this.style.borderColor='#0056b3'" onmouseout="this.style.borderColor='#e5e7eb'">
          <div style="font-family:monospace;font-weight:700;color:#1e3a8a;">
            <?= htmlspecialchars($pr['pi_cod_prod'].'/'.(($pr['ma_no']??'')?:$pr['ma_marca'])) ?>
          </div>
          <div style="font-size:.82em;color:#555;margin-top:3px;">
            <?= htmlspecialchars($pr['pi_descripcion']??'') ?>
            <?= $pr['pi_color'] ? '· '.htmlspecialchars($pr['pi_color']) : '' ?>
          </div>
          <div style="font-size:.78em;color:#888;"><?= htmlspecialchars($pr['ma_marca'].' '.$pr['ma_modelo']) ?></div>
        </a>
        <?php endforeach; ?>
        <?php if (empty($lista)): ?>
        <div style="color:#888;padding:20px;">No hay procesos activos. <a href="/ingenieria/proceso-nuevo.php">Crear uno</a></div>
        <?php endif; ?>
      </div>
    </div></main>
    <footer><p>Método ACG</p></footer><?php includeSidebar(); ?>
    </body></html>
    <?php exit;
}

// ── Cargar proceso ──────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT pr.*,
           pi.pi_cod_prod, pi.pi_descripcion, pi.pi_color, pi.pi_espesor,
           ma.ma_no, ma.ma_marca, ma.ma_modelo, ma.ma_max_vel_inyec, ma.ma_max_pres_inyec,
           ma.ma_diam_husillo,
           mo.mo_numero, re.re_tipo_resina, re.re_grado
    FROM procesos pr
    JOIN piezas   pi ON pr.pr_pieza_id   = pi.pi_id
    JOIN maquinas ma ON pr.pr_maquina_id  = ma.ma_id
    LEFT JOIN moldes  mo ON pr.pr_molde_id   = mo.mo_id
    LEFT JOIN resinas re ON pr.pr_resina_id  = re.re_id
    WHERE pr.pr_id = ? AND pr.pr_empresa_id = ?");
$stmt->execute([$procesoId, $empresaId]);
$proc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$proc) { header('Location: /ingenieria/calificador-proceso.php'); exit; }

// ── Cargar calificador existente ────────────────────────────
$cal = $conn->prepare("SELECT * FROM procesos_calificador WHERE cal_proceso_id = ?");
$cal->execute([$procesoId]);
$cal = $cal->fetch(PDO::FETCH_ASSOC) ?: [];

// ── Cargar E y C para parámetros sugeridos ──────────────────
$eyc = $conn->prepare("SELECT * FROM procesos_eyc WHERE eyc_proceso_id = ?");
$eyc->execute([$procesoId]);
$eyc = $eyc->fetch(PDO::FETCH_ASSOC) ?: [];

$codigo = $proc['pi_cod_prod'].'/'.($proc['ma_no']?:$proc['ma_marca']);
$n = fn($k,$d='') => $cal['cal_'.$k] ?? $d;

// Vista para imprimir sin calificación
$vistaImpresion = isset($_GET['vista']) && $_GET['vista'] === 'piso';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Calificador — <?= htmlspecialchars($codigo) ?></title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png">
  <link rel="stylesheet" href="/css/acg.estilos.css">
  <style>
    .cal-section{background:#fff;border-radius:8px;padding:14px 18px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:12px;}
    .cal-section h3{margin:0 0 10px;font-size:.82em;color:#fff;background:#1e3a8a;padding:5px 12px;border-radius:4px;text-transform:uppercase;letter-spacing:.5px;}
    /* Tabla hoja proceso */
    .pt{width:100%;border-collapse:collapse;font-size:.78em;}
    .pt th{background:#1e3a8a;color:#fff;padding:4px 5px;text-align:center;white-space:nowrap;font-size:.75em;}
    .pt td{padding:2px 3px;border:1px solid #e5e7eb;text-align:center;}
    .pt td.lbl{text-align:left;font-weight:600;font-size:.75em;color:#333;background:#f8faff;padding:3px 7px;white-space:nowrap;}
    .pt td.unit{color:#888;font-size:.72em;background:#f8faff;}
    .pt input[type=number],.pt input[type=text]{width:55px;padding:2px 3px;border:1.5px solid #f59e0b;border-radius:2px;text-align:center;font-size:.82em;background:#fffbeb;font-family:monospace;}
    .pt input:focus{border-color:#d97706;outline:none;background:#fff;}
    .pt input.ing{border-color:#7c3aed!important;background:#faf5ff!important;}
    /* Sugeridos */
    .sug-badge{display:inline-block;background:#dbeafe;color:#1e40af;border-radius:3px;font-size:.7em;padding:1px 5px;margin-left:4px;cursor:help;}
    /* Calificadores */
    .califs{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:8px;}
    .ccard{border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;}
    .ccard .cnum{font-size:.68em;font-weight:700;color:#888;}
    .ccard .ctit{font-size:.85em;font-weight:700;color:#1e3a8a;margin:2px 0 6px;}
    .ccard .cdet{font-size:.75em;color:#666;margin-bottom:6px;}
    .cbar{height:12px;border-radius:6px;background:#e5e7eb;overflow:hidden;margin-bottom:3px;}
    .cbar-f{height:100%;border-radius:6px;transition:width .3s;}
    .cval{font-size:1.2em;font-weight:700;font-family:monospace;}
    .csub{font-size:.7em;color:#888;margin-top:2px;}
    .c-ok{color:#065f46;}.c-warn{color:#92400e;}.c-bad{color:#991b1b;}
    .b-ok{background:#10b981;}.b-warn{background:#f59e0b;}.b-bad{background:#ef4444;}
    /* Resumen strip */
    .resumen-strip{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;}
    .rs-item{background:#f8faff;border:1px solid #e5e7eb;border-radius:4px;padding:4px 10px;font-size:.78em;}
    .rs-item .rs-lbl{color:#888;display:block;font-size:.85em;}
    .rs-item .rs-val{font-weight:700;}
    /* Print */
    @media print{
      header,footer,.acciones,.back-button,.burger-btn,.no-print{display:none!important;}
      .cal-section{box-shadow:none;border:1px solid #ccc;break-inside:avoid;}
      .cal-section h3{background:#555!important;-webkit-print-color-adjust:exact;}
      body{font-size:9px;}
    }
  </style>
</head>
<body>
<header class="header">
  <div class="header-title-group">
    <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
    <h1>Calificador — <span style="font-family:monospace"><?= htmlspecialchars($codigo) ?></span></h1>
  </div>
  <div class="header-right">
    <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    <?= burgerBtn() ?>
  </div>
</header>

<main class="main-container">

  <!-- Info rápida -->
  <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:7px 14px;
              font-size:.8em;color:#1e3a8a;margin-bottom:12px;display:flex;gap:18px;flex-wrap:wrap;">
    <span><b>Pieza:</b> <?= htmlspecialchars($proc['pi_cod_prod']) ?><?= $proc['pi_descripcion'] ? ' — '.htmlspecialchars($proc['pi_descripcion']) : '' ?></span>
    <span><b>Molde:</b> <?= htmlspecialchars($proc['mo_numero']??'—') ?></span>
    <span><b>Resina:</b> <?= htmlspecialchars(($proc['re_tipo_resina']??'').' '.($proc['re_grado']??'')) ?></span>
    <span><b>Máquina:</b> <?= htmlspecialchars(($proc['ma_no']?$proc['ma_no'].' ':'').$proc['ma_marca'].' '.$proc['ma_modelo']) ?></span>
    <span class="no-print">
      <a href="?id=<?= $procesoId ?>&vista=piso" style="color:#0056b3;font-size:.85em;">🖨️ Vista sin calificación (piso)</a>
    </span>
  </div>

  <?php if ($vistaImpresion): ?>
  <div style="background:#fef3c7;border:1px solid #fcd34d;padding:6px 12px;border-radius:4px;
              font-size:.8em;color:#92400e;margin-bottom:10px;display:flex;justify-content:space-between;" class="no-print">
    <span>📋 Vista de piso — sin calificación | <a href="?id=<?= $procesoId ?>">← Ver con calificación</a></span>
    <button onclick="window.print()" style="border:none;background:none;cursor:pointer;color:#0056b3;">🖨️ Imprimir</button>
  </div>
  <?php endif; ?>

  <!-- ══ PARÁMETROS SUGERIDOS (calculados de E y C) ══ -->
  <?php if (!$vistaImpresion && $eyc): ?>
  <?php
    $vol_disp   = (float)($eyc['eyc_vol_disparo'] ?? 0);
    $diam_hus   = (float)($proc['ma_diam_husillo'] ?? 0);
    $vel_max    = (float)($proc['ma_max_vel_inyec'] ?? 0);
    $espesor    = (float)($proc['pi_espesor'] ?? 0);
    $area_hus   = $diam_hus > 0 ? $diam_hus * $diam_hus * 0.007854 : 0;
    $recorrido  = $area_hus > 0 ? $vol_disp * 10 / $area_hus : 0;
    $carga_sug  = $recorrido > 0 ? (int)(($recorrido + 24) / 10) * 10 : 0;
    $conm_sug   = $carga_sug - 0.95 * $recorrido;
    $cojin_sug  = $carga_sug - $recorrido;
    $vel_sug    = $vel_max > 0 ? (int)(0.77 * $vel_max) : 0;
    $tpo_sug    = $espesor > 0 ? round($espesor * 3.3, 2) : 0;
  ?>
  <div class="cal-section no-print" style="background:#f0fdf4;border:1px solid #86efac;">
    <h3 style="background:#059669;">💡 Parámetros sugeridos (hoja 5)</h3>
    <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:.82em;">
      <?php
      $sugs = [
        ['Vel. inyección', $vel_sug, 'mm/s'],
        ['Recorrido',     round($recorrido,1), 'mm'],
        ['Carga (pos.)',  $carga_sug, 'mm'],
        ['Conmutación',  round($conm_sug,1), 'mm'],
        ['Cojín',        round($cojin_sug,1), 'mm'],
        ['Tiempo iny.',  $tpo_sug, 's'],
      ];
      foreach ($sugs as [$lbl, $val, $unit]): ?>
      <div style="background:#fff;border:1px solid #86efac;border-radius:5px;padding:6px 12px;text-align:center;">
        <div style="font-size:.78em;color:#555;"><?= $lbl ?></div>
        <div style="font-weight:700;color:#065f46;font-size:1.05em;font-family:monospace;"><?= $val ?></div>
        <div style="font-size:.7em;color:#888;"><?= $unit ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ HOJA DE PROCESO ══ -->
  <div class="cal-section">
    <h3>🖨️ Hoja de Proceso<?= $vistaImpresion ? ' — PISO / CLIENTE' : '' ?></h3>

    <!-- VALIDACIÓN + RESUMEN -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:12px;font-size:.82em;">
      <div><label style="font-size:.75em;color:#666;display:block;">Ingeniero de proceso</label>
           <input type="text" id="ing_nombre" value="<?= htmlspecialchars($n('ing_nombre')) ?>" oninput="calcular()"
                  style="width:100%;padding:4px 7px;border:1.5px solid #7c3aed;border-radius:4px;background:#faf5ff;"></div>
      <div><label style="font-size:.75em;color:#666;display:block;">Nivel</label>
           <input type="text" id="ing_nivel" value="<?= htmlspecialchars($n('ing_nivel')) ?>" oninput="calcular()"
                  style="width:100%;padding:4px 7px;border:1.5px solid #7c3aed;border-radius:4px;background:#faf5ff;"></div>
      <div><label style="font-size:.75em;color:#666;display:block;">Fecha</label>
           <input type="date" id="ing_fecha" value="<?= htmlspecialchars($n('ing_fecha',date('Y-m-d'))) ?>" oninput="calcular()"
                  style="width:100%;padding:4px 7px;border:1.5px solid #7c3aed;border-radius:4px;background:#faf5ff;"></div>
    </div>

    <!-- INYECCIÓN — 10 perfiles -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:0 0 4px;">INYECCIÓN — Perfiles &lt;10 → &lt;1</p>
    <div style="overflow-x:auto;">
    <table class="pt">
      <thead><tr>
        <th>PARAM.</th>
        <?php for($p=10;$p>=1;$p--): ?><th>&lt; <?=$p?></th><?php endfor; ?>
        <th>UNID.</th>
      </tr></thead>
      <tbody>
        <tr><td class="lbl">Velocidad</td>
          <?php for($p=10;$p>=1;$p--): ?>
          <td><input type="number" step="0.1" id="iny_vel_<?=$p?>" value="<?=$n("iny_vel_$p")?>" oninput="calcular()"></td>
          <?php endfor; ?>
          <td class="unit">mm/s</td></tr>
        <tr><td class="lbl">Presión límite</td>
          <?php for($p=10;$p>=1;$p--): ?>
          <td><input type="number" step="1" id="iny_pres_<?=$p?>" value="<?=$n("iny_pres_$p")?>" oninput="calcular()"></td>
          <?php endfor; ?>
          <td class="unit">bar</td></tr>
        <tr><td class="lbl">Posición</td>
          <?php for($p=10;$p>=1;$p--): ?>
          <td><input type="number" step="0.1" id="iny_pos_<?=$p?>" value="<?=$n("iny_pos_$p")?>" oninput="calcular()"></td>
          <?php endfor; ?>
          <td class="unit">mm</td></tr>
      </tbody>
    </table>
    </div>

    <!-- SOSTENIMIENTO — 3 perfiles -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:10px 0 4px;">SOSTENIMIENTO — Perfiles &lt;3 → &lt;1</p>
    <div style="overflow-x:auto;">
    <table class="pt">
      <thead><tr><th>PARAM.</th><th>&lt; 3</th><th>&lt; 2</th><th>&lt; 1</th><th>UNID.</th></tr></thead>
      <tbody>
        <tr><td class="lbl">Velocidad</td>
          <?php foreach([3,2,1] as $p): ?><td><input type="number" step="0.1" id="sos_vel_<?=$p?>" value="<?=$n("sos_vel_$p")?>" oninput="calcular()"></td><?php endforeach; ?>
          <td class="unit">mm/s</td></tr>
        <tr><td class="lbl">Presión</td>
          <?php foreach([3,2,1] as $p): ?><td><input type="number" step="1" id="sos_pres_<?=$p?>" value="<?=$n("sos_pres_$p")?>" oninput="calcular()"></td><?php endforeach; ?>
          <td class="unit">bar</td></tr>
        <tr><td class="lbl">Tiempo</td>
          <?php foreach([3,2,1] as $p): ?><td><input type="number" step="0.01" id="sos_tiempo_<?=$p?>" value="<?=$n("sos_tiempo_$p")?>" oninput="calcular()"></td><?php endforeach; ?>
          <td class="unit">s</td></tr>
      </tbody>
    </table>
    </div>

    <!-- CARGA + DESCOMPRESIÓN -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:10px 0 4px;">CARGA / DESCOMPRESIÓN / ENFRIAMIENTO</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:7px;font-size:.8em;">
      <?php
      $flds=[
        ['car_vel_rpm','Vel. carga (RPM)','0.1'],
        ['car_contrapres','Contrapresión (bar)','0.1'],
        ['car_posicion','Posición carga (mm)','0.1'],
        ['car_enf_tiempo','Tiempo enfriamiento (s)','0.1'],
        ['car_decomp_mm','Descompresión (mm)','0.1'],
        ['car_decomp_vel','Vel. descompr. (mm/s)','0.1'],
      ];
      foreach($flds as [$id,$lbl,$step]): ?>
      <div><label style="font-size:.75em;color:#555;display:block;"><?=$lbl?></label>
           <input type="number" step="<?=$step?>" id="<?=$id?>" value="<?=$n($id)?>"
                  oninput="calcular()" style="width:100%;padding:3px 5px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <?php endforeach; ?>
    </div>

    <!-- TEMPERATURA — boquilla + 12 zonas + garganta + aceite -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:10px 0 4px;">TEMPERATURA DEL CAÑÓN</p>
    <div style="overflow-x:auto;">
    <table class="pt">
      <thead><tr>
        <th>Boq.</th><?php for($z=12;$z>=1;$z--): ?><th>Z<?=$z?></th><?php endfor; ?><th>Garg.</th><th>Aceite</th>
      </tr></thead>
      <tbody><tr>
        <td><input type="number" step="1" id="temp_boquilla" value="<?=$n('temp_boquilla')?>" oninput="calcular()"></td>
        <?php for($z=12;$z>=1;$z--): ?>
        <td><input type="number" step="1" id="temp_z<?=$z?>" value="<?=$n("temp_z$z")?>" oninput="calcular()"></td>
        <?php endfor; ?>
        <td><input type="number" step="1" id="temp_garganta" value="<?=$n('temp_garganta')?>" oninput="calcular()"></td>
        <td><input type="number" step="1" id="temp_aceite" value="<?=$n('temp_aceite')?>" oninput="calcular()"></td>
      </tr></tbody>
    </table>
    </div>

    <!-- CANAL CALIENTE — 15 zonas -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:10px 0 4px;">CANAL CALIENTE (si aplica)</p>
    <div style="overflow-x:auto;">
    <table class="pt">
      <thead><tr><?php for($z=1;$z<=15;$z++): ?><th>CC<?=$z?></th><?php endfor; ?></tr></thead>
      <tbody><tr>
        <?php for($z=1;$z<=15;$z++): ?>
        <td><input type="number" step="1" id="cc_z<?=$z?>" value="<?=$n("cc_z$z")?>" oninput="calcular()"></td>
        <?php endfor; ?>
      </tr></tbody>
    </table>
    </div>

    <!-- REFRIGERACIÓN — lado móvil (6) + flotante (1) + lado fijo (6) -->
    <p style="font-size:.76em;font-weight:700;color:#1e3a8a;margin:10px 0 4px;">REFRIGERACIÓN (°C)</p>
    <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:.8em;align-items:flex-end;">
      <div>
        <div style="font-size:.72em;font-weight:700;color:#0891b2;margin-bottom:3px;">LADO MÓVIL (1-6)</div>
        <div style="display:flex;gap:4px;">
          <?php for($r=1;$r<=6;$r++): ?>
          <div style="text-align:center;">
            <div style="font-size:.68em;color:#888;">M<?=$r?></div>
            <input type="number" step="0.1" id="refrig_movil_<?=$r?>" value="<?=$n("refrig_movil_$r")?>"
                   oninput="calcular()" style="width:48px;padding:2px 3px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:center;font-size:.82em;">
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <div>
        <div style="font-size:.72em;font-weight:700;color:#9333ea;margin-bottom:3px;">FLOTANTE</div>
        <input type="number" step="0.1" id="refrig_flotante" value="<?=$n('refrig_flotante')?>"
               oninput="calcular()" style="width:55px;padding:3px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:center;font-size:.82em;">
      </div>
      <div>
        <div style="font-size:.72em;font-weight:700;color:#0056b3;margin-bottom:3px;">LADO FIJO (1-6)</div>
        <div style="display:flex;gap:4px;">
          <?php for($r=1;$r<=6;$r++): ?>
          <div style="text-align:center;">
            <div style="font-size:.68em;color:#888;">F<?=$r?></div>
            <input type="number" step="0.1" id="refrig_fijo_<?=$r?>" value="<?=$n("refrig_fijo_$r")?>"
                   oninput="calcular()" style="width:48px;padding:2px 3px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:center;font-size:.82em;">
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div><!-- /hoja proceso -->

  <!-- ══ RESULTADOS DE LA MÁQUINA ══ -->
  <div class="cal-section">
    <h3>📊 Resultados que da la máquina</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:7px;">
      <?php
      $res_campos=[
        ['res_t_ciclo','T. ciclo','s','0.01'],
        ['res_t_inyeccion','T. inyección','s','0.001'],
        ['res_t_plastif','T. plastificación','s','0.01'],
        ['res_pos_cojin','Posición cojín','mm','0.1'],
        ['res_pres_conmut','Pres. conmutación','bar','1'],
        ['res_pres_max','Presión máxima','bar','1'],
      ];
      foreach($res_campos as [$id,$lbl,$unit,$step]): ?>
      <div><label style="font-size:.73em;color:#555;display:block;"><?=$lbl?> (<?=$unit?>)</label>
        <input type="number" step="<?=$step?>" id="<?=$id?>" value="<?=$n($id)?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #bfdbfe;border-radius:3px;background:#eff6ff;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <?php endforeach; ?>
      <div><label style="font-size:.73em;color:#7c3aed;font-weight:700;display:block;">Temp. caída pieza (°C)</label>
        <input type="number" step="0.5" id="temp_caida_pieza" value="<?=$n('temp_caida_pieza')?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #7c3aed;border-radius:3px;background:#faf5ff;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <div><label style="font-size:.73em;color:#555;display:block;">Espesor pieza (mm)</label>
        <input type="number" step="0.01" id="espesor_pieza" value="<?=$n('espesor_pieza',$proc['pi_espesor']??'')?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <div><label style="font-size:.73em;color:#555;display:block;">Vel. máx. ref. (mm/s)</label>
        <input type="number" step="1" id="vel_max_ref" value="<?=$n('vel_max_ref',$proc['ma_max_vel_inyec']??'')?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <div><label style="font-size:.73em;color:#555;display:block;">Vol. disparo (cm³)</label>
        <input type="number" step="0.01" id="vol_disparo_ref" value="<?=$n('vol_disparo_ref',$eyc['eyc_vol_disparo']??'')?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:right;font-family:monospace;font-size:.88em;"></div>
      <div><label style="font-size:.73em;color:#555;display:block;">Diam. husillo (mm)</label>
        <input type="number" step="0.1" id="diam_husillo_ref" value="<?=$n('diam_husillo_ref',$proc['ma_diam_husillo']??'')?>"
               oninput="calcular()"
               style="width:100%;padding:3px 5px;border:1.5px solid #f59e0b;border-radius:3px;background:#fffbeb;text-align:right;font-family:monospace;font-size:.88em;"></div>
    </div>
  </div>

  <!-- ══ CALIFICADOR ══ -->
  <?php if (!$vistaImpresion): ?>
  <div class="cal-section">
    <h3>🏆 Calificador del Proceso</h3>
    <div id="resumen-strip" class="resumen-strip"></div>
    <div class="califs" id="califs-container"></div>
  </div>
  <?php endif; ?>

  <div class="acciones no-print">
    <button class="btn btn-guardar" onclick="guardar()">💾 Guardar</button>
    <button class="btn btn-pdf" onclick="window.print()">📥 Imprimir</button>
    <?php if (!$vistaImpresion): ?>
    <a href="?id=<?= $procesoId ?>&vista=piso" class="btn btn-limpiar">👁️ Vista piso</a>
    <?php else: ?>
    <a href="?id=<?= $procesoId ?>" class="btn btn-limpiar">🏆 Ver con calificación</a>
    <?php endif; ?>
  </div>

</main>
<footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>

<script>
const PROCESO_ID = <?= $procesoId ?>;
const $ = id => document.getElementById(id);
const num = id => { const e=$(id); return e?(parseFloat(e.value)||0):0; };
const arr = (ids) => ids.map(id => num(id));

function calcular() {
    // ── Leer inputs ──
    // Inyección
    const iny_vel  = [5,4,3,2,1].map(p => num(`iny_vel_${p}`));   // índice 0=p5, 4=p1
    const iny_pres = [5,4,3,2,1].map(p => num(`iny_pres_${p}`));
    const iny_pos  = [5,4,3,2,1].map(p => num(`iny_pos_${p}`));
    const pos1 = iny_pos[0]; // posición perfil 1 (la primera, más cercana a fin)
    const pos5 = iny_pos[4]; // posición perfil 5 (la más lejana)

    // Sostenimiento
    const sos_t1 = num('sos_tiempo_1');
    const sos_t2 = num('sos_tiempo_2');

    // Carga
    const carga_pos  = num('car_posicion');   // G17
    const enf_tiempo = num('car_enf_tiempo'); // B19
    const decomp_mm  = num('car_decomp_mm');  // F19
    const vel_max_r  = num('vel_max_ref') || 110;

    // Temperatura refrigeración
    const temp_fijo  = num('refrig_temp_fijo');
    const temp_movil = num('refrig_temp_movil');

    // Resultados máquina
    const t_iny    = num('res_t_inyeccion');  // B29
    const t_plastif= num('res_t_plastif');    // C29
    const pos_sal  = num('res_pos_salida');   // D29
    const conm_real= num('res_conmut_real');  // E29
    const cojin    = num('res_cojin');        // F29
    const pres_conm= num('res_pres_conmut'); // G29
    const pres_maq = num('res_pres_max');     // H29

    // Ingeniero / configuración
    const temp_caida = num('temp_caida_pieza');
    const espesor    = num('espesor_pieza');

    const califs = [];

    // ── 1. LLENADO DE PIEZA ──────────────────────────────────
    // % llenado = (Carga - Conmutación_pos1) / (Carga - Cojín)
    const pct_llenado = (carga_pos && cojin)
        ? (carga_pos - pos1) / (carga_pos - cojin) : null;
    const cal1 = pct_llenado !== null
        ? (pct_llenado < 0.965 ? pct_llenado / 0.965 : 0.965 / pct_llenado) : null;
    califs.push({
        num: 1, titulo: 'Llenado de pieza',
        detalle: `Meta: 96.5% | Actual: ${pct_llenado !== null ? (pct_llenado*100).toFixed(1)+'%' : '—'}`,
        subdetalle: `(Carga ${carga_pos} - Pos.1 ${pos1}) / (Carga ${carga_pos} - Cojín ${cojin})`,
        valor: cal1, tipo: 'ratio'
    });

    // ── 2. CALIDAD DE LA CARGA ───────────────────────────────
    // (Tiempo plastificación + 0.4) / Tiempo enfriamiento
    const cal2 = enf_tiempo > 0 ? (t_plastif + 0.4) / enf_tiempo : null;
    califs.push({
        num: 2, titulo: 'Calidad de la carga',
        detalle: `La carga debe terminar antes del enfriamiento`,
        subdetalle: `(Plastif. ${t_plastif} + 0.4) / Enf. ${enf_tiempo}`,
        valor: cal2, tipo: 'inverso', meta: 'Menor es mejor (ideal < 1)'
    });

    // ── 3. TEMPERATURA DE PROCESO (presión) ──────────────────
    // Presión conm / Presión máx = idealmente entre 40-60%
    const ratio_pres = pres_maq > 0 ? pres_conm / 1900 : null;
    const cal3 = ratio_pres !== null
        ? (ratio_pres < 0.5 ? ratio_pres / 0.5 : 0.5 / ratio_pres) : null;
    califs.push({
        num: 3, titulo: 'Temperatura de proceso',
        detalle: `Meta: 40–60% de la presión máx. de inyección`,
        subdetalle: `${pres_conm} bar / 1900 bar = ${ratio_pres !== null ? (ratio_pres*100).toFixed(1)+'%' : '—'}`,
        valor: cal3, tipo: 'ratio'
    });

    // ── 4. CAUDAL DE LLENADO ─────────────────────────────────
    califs.push({
        num: 4, titulo: 'Caudal de llenado',
        detalle: 'Pendiente de definición', valor: null, tipo: 'pendiente'
    });

    // ── 5. REFRIGERACIÓN POR ESPESOR ─────────────────────────
    // Refrig real = suma tiempos sost + enfriamiento
    const refrig_real = sos_t1 + sos_t2 + enf_tiempo;
    const refrig_teorica = 2.4 * espesor * espesor;
    const espesor_teo = refrig_real > 0 ? Math.sqrt(refrig_real / 2.4) : null;
    const cal5 = (espesor > 0 && espesor_teo > 0) ? espesor / espesor_teo : null;
    califs.push({
        num: 5, titulo: 'Refrigeración por espesor',
        detalle: `Espesor real ${espesor}mm vs espesor teórico ${espesor_teo ? espesor_teo.toFixed(3)+'mm' : '—'}`,
        subdetalle: `Refrig. real: ${refrig_real.toFixed(1)}s | Teórica: ${refrig_teorica.toFixed(2)}s`,
        valor: cal5, tipo: 'ratio'
    });

    // ── 6. REFRIGERACIÓN POR TEMPERATURA DE CAÍDA ────────────
    const temp_agua_prom = (temp_fijo + temp_movil) / 2;
    const temp_ideal_caida = temp_agua_prom + 80;
    const cal6 = temp_ideal_caida > 0 ? temp_caida / temp_ideal_caida : null;
    califs.push({
        num: 6, titulo: 'Refrigeración por temperatura de caída',
        detalle: `Temp. ideal caída: ${temp_agua_prom.toFixed(1)} + 80 = ${temp_ideal_caida.toFixed(1)}°C`,
        subdetalle: `Caída real: ${temp_caida}°C`,
        valor: cal6, tipo: 'ratio'
    });

    // ── 7. APROVECHAMIENTO DE MÁQUINA (caudal inyección) ─────
    // Recorrido = (Carga + Descomp) - Posición perfil 1
    const recorrido = (carga_pos + decomp_mm) - pos1;
    const vel_prom = t_iny > 0 ? recorrido / t_iny : null;
    const cal7 = (vel_prom !== null && vel_max_r > 0) ? vel_prom / vel_max_r : null;
    califs.push({
        num: 7, titulo: 'Aprovechamiento de máquina',
        detalle: `Vel. promedio inyección vs vel. máxima`,
        subdetalle: `Recorrido ${recorrido.toFixed(1)}mm / ${t_iny}s = ${vel_prom ? vel_prom.toFixed(1)+' mm/s' : '—'} | Máx: ${vel_max_r}`,
        valor: cal7, tipo: 'ratio'
    });

    // ── 9. PERFILADO (OPCIONAL) ───────────────────────────────
    const vels = iny_vel.filter(v => v > 0);
    let cal9 = null;
    let perfil_detail = '';
    if (vels.length >= 2) {
        // ratios V2/V1, V3/V2, V4/V3, V5/V4 (de perfil 1 al 5)
        // en el Excel: C5=V5, D5=V4, E5=V3, F5=V2, G5=V1
        // ratios son F5/G5, E5/F5, D5/E5, C5/D5
        const v = [iny_vel[4], iny_vel[3], iny_vel[2], iny_vel[1], iny_vel[0]]; // [V1..V5]
        const ratios = [];
        for (let i = 1; i < v.length; i++) {
            if (v[i-1] > 0) ratios.push(v[i] / v[i-1]);
        }
        if (ratios.length) {
            const mn = Math.min(...ratios), mx = Math.max(...ratios);
            cal9 = mx > 0 ? mn / mx : null;
            perfil_detail = `Ratios: ${ratios.map(r=>r.toFixed(3)).join(' / ')}`;
        }
    }
    califs.push({
        num: 9, titulo: 'Perfilado',
        detalle: 'Homogeneidad del perfil de velocidad (opcional)',
        subdetalle: perfil_detail || 'Ingresa velocidades de inyección',
        valor: cal9, tipo: 'ratio', opcional: true
    });

    // ── 10. ACELERACIÓN ───────────────────────────────────────
    // Recorridos por zona
    const pos = iny_pos.slice().reverse(); // [pos1..pos5]
    const vel = iny_vel.slice().reverse(); // [vel1..vel5]
    const r1 = (carga_pos + decomp_mm) - (pos[4] || 0);
    const r2 = (pos[4]||0) - (pos[3]||0);
    const r3 = (pos[3]||0) - (pos[2]||0);
    const r4 = (pos[2]||0) - (pos[1]||0);
    const r5 = (pos[1]||0) - (pos[0]||0);
    const tiempos_teo = [
        vel[4] > 0 ? r1/vel[4] : 0,
        vel[3] > 0 ? r2/vel[3] : 0,
        vel[2] > 0 ? r3/vel[2] : 0,
        vel[1] > 0 ? r4/vel[1] : 0,
        vel[0] > 0 ? r5/vel[0] : 0,
    ];
    const t_teo_total = tiempos_teo.reduce((a,b)=>a+b, 0);
    const dif_acel = t_iny > 0 ? t_iny - t_teo_total : null;
    califs.push({
        num: 10, titulo: 'Aceleración',
        detalle: `Tiempo calculado: ${t_teo_total.toFixed(3)}s | Real: ${t_iny}s`,
        subdetalle: `Diferencia: ${dif_acel !== null ? dif_acel.toFixed(3)+'s' : '—'}`,
        valor: dif_acel !== null ? (1 - Math.min(Math.abs(dif_acel)/t_iny, 1)) : null,
        tipo: 'ratio'
    });

    renderCalifs(califs);
}

function colorCls(v,tipo){
    if(v===null) return '';
    if(tipo==='valvula') return v===0?'c-bad':v===1?'c-ok':'';
    if(tipo==='inverso') return v<=1?'c-ok':v<=1.2?'c-warn':'c-bad';
    return v>=0.95?'c-ok':v>=0.80?'c-warn':'c-bad';
}
function barraCls(v,tipo){
    if(tipo==='pendiente') return 'b-ok';
    if(tipo==='valvula') return v===0?'b-bad':'b-ok';
    if(tipo==='inverso') return v<=1?'b-ok':v<=1.2?'b-warn':'b-bad';
    return v!==null?(v>=0.95?'b-ok':v>=0.80?'b-warn':'b-bad'):'b-ok';
}

function renderCalifs(lista){
    const cont=$('califs-container');
    if(!cont) return;
    cont.innerHTML=lista.map(c=>{
        const pct=c.valor!==null?Math.min(Math.abs(c.valor)*100,100):0;
        const cls=colorCls(c.valor,c.tipo), bcls=barraCls(c.valor,c.tipo);
        const vs=c.valor!==null?(c.valor*100).toFixed(1)+'%':'—';
        const pend=c.tipo==='pendiente';
        const opt=c.opcional?'<span style="font-size:.66em;color:#aaa;"> (opcional)</span>':'';
        return `<div class="ccard">
            <div class="cnum">${c.num}.</div>
            <div class="ctit">${c.titulo}${opt}</div>
            <div class="cdet">${c.detalle}</div>
            ${!pend?`<div class="cbar"><div class="cbar-f ${bcls}" style="width:${pct}%"></div></div>
            <span class="cval ${cls}">${vs}</span>
            <div class="csub">${c.subdetalle||''}</div>`
            :`<div style="color:#94a3b8;font-size:.75em;font-style:italic;">Pendiente de implementación</div>`}
        </div>`;
    }).join('');
}

function renderResumen(lista){
    const s=$('resumen-strip');
    if(!s) return;
    const activos=lista.filter(c=>c.tipo!=='pendiente'&&c.valor!==null);
    const promedio=activos.length?activos.reduce((a,c)=>a+Math.abs(c.valor),0)/activos.length:null;
    s.innerHTML=activos.map(c=>`
        <div class="rs-item">
          <span class="rs-lbl">${c.num}. ${c.titulo.substring(0,20)}${c.titulo.length>20?'…':''}</span>
          <span class="rs-val ${colorCls(c.valor,c.tipo)}">${(c.valor*100).toFixed(0)}%</span>
        </div>`).join('')
        +(promedio!==null?`<div class="rs-item" style="background:#1e3a8a;color:#fff;border-color:#1e3a8a;">
          <span class="rs-lbl" style="color:#93c5fd;">PROMEDIO</span>
          <span class="rs-val" style="color:#fff;">${(promedio*100).toFixed(1)}%</span>
        </div>`:'');
}

function guardar(){
    const p={proceso_id:PROCESO_ID};
    // Inyección 10 perfiles
    for(let i=1;i<=10;i++){
        p[`iny_vel_${i}`] =num(`iny_vel_${i}`)||null;
        p[`iny_pres_${i}`]=num(`iny_pres_${i}`)||null;
        p[`iny_pos_${i}`] =num(`iny_pos_${i}`)||null;
    }
    // Sostenimiento 3 perfiles
    for(let i=1;i<=3;i++){
        p[`sos_vel_${i}`]   =num(`sos_vel_${i}`)||null;
        p[`sos_pres_${i}`]  =num(`sos_pres_${i}`)||null;
        p[`sos_tiempo_${i}`]=num(`sos_tiempo_${i}`)||null;
    }
    // Carga
    ['vel_rpm','contrapres','posicion','enf_tiempo','decomp_mm','decomp_vel'].forEach(k=>{ p[`car_${k}`]=num(`car_${k}`)||null; });
    // Temperaturas cañón
    ['boquilla','z12','z11','z10','z9','z8','z7','z6','z5','z4','z3','z2','z1','garganta','aceite'].forEach(z=>{p[`temp_${z}`]=num(`temp_${z}`)||null;});
    // Canal caliente
    for(let i=1;i<=15;i++) p[`cc_z${i}`]=num(`cc_z${i}`)||null;
    // Refrigeración
    for(let i=1;i<=6;i++){p[`refrig_movil_${i}`]=num(`refrig_movil_${i}`)||null; p[`refrig_fijo_${i}`]=num(`refrig_fijo_${i}`)||null;}
    p.refrig_flotante=num('refrig_flotante')||null;
    // Resultados
    ['res_t_ciclo','res_t_inyeccion','res_t_plastif','res_pos_cojin','res_pres_conmut','res_pres_max'].forEach(k=>{p[k]=num(k)||null;});
    p.temp_caida_pieza=num('temp_caida_pieza')||null;
    p.espesor_pieza=num('espesor_pieza')||null;
    p.vel_max_ref=num('vel_max_ref')||null;
    p.vol_disparo_ref=num('vol_disparo_ref')||null;
    p.diam_husillo_ref=num('diam_husillo_ref')||null;
    // Validación
    p.ing_nombre=$('ing_nombre')?.value||null;
    p.ing_nivel=$('ing_nivel')?.value||null;
    p.ing_fecha=$('ing_fecha')?.value||null;

    fetch('/ingenieria/guardar_calificador.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)})
    .then(r=>r.json()).then(res=>alert(res.ok?'✅ Guardado correctamente':'❌ Error: '+(res.mensaje||'')));
}

window.addEventListener('DOMContentLoaded', calcular);
</script>
</body>
</html>
