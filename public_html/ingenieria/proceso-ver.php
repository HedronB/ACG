<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$usuarioId = (int)$_SESSION['id'];
$procesoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$procesoId) { header('Location: /ingenieria/procesos.php'); exit; }

// Cargar proceso completo
$stmt = $conn->prepare("
    SELECT pr.*,
           pi.pi_cod_prod, pi.pi_descripcion, pi.pi_color, pi.pi_molde, pi.pi_resina,
           pi.pi_espesor, pi.pi_area_proy, pi.pi_porc_molido,
           ma.ma_no, ma.ma_marca, ma.ma_modelo, ma.ma_diam_husillo,
           ma.ma_carga_max, ma.ma_max_vel_inyec, ma.ma_max_pres_inyec,
           ma.ma_termoreguladores, ma.ma_canal_caliente, ma.ma_tonelaje,
           ma.ma_dist_barras, ma.ma_apert_max,
           mo.mo_numero, mo.mo_no_cavidades, mo.mo_peso_pieza, mo.mo_puert_cavidad,
           mo.mo_no_coladas, mo.mo_peso_colada, mo.mo_ancho, mo.mo_abierto,
           re.re_cod_int, re.re_tipo_resina, re.re_grado,
           re.re_densidad, re.re_factor_correccion,
           re.re_sec_temp, re.re_sec_tiempo,
           re.re_temp_masa_max, re.re_temp_masa_min,
           u.us_nombre AS creado_por
    FROM procesos pr
    JOIN piezas   pi ON pr.pr_pieza_id   = pi.pi_id
    JOIN maquinas ma ON pr.pr_maquina_id  = ma.ma_id
    LEFT JOIN moldes   mo ON pr.pr_molde_id   = mo.mo_id
    LEFT JOIN resinas  re ON pr.pr_resina_id  = re.re_id
    LEFT JOIN usuarios u  ON pr.pr_usuario_id = u.us_id
    WHERE pr.pr_id = ? AND pr.pr_empresa_id = ?");
$stmt->execute([$procesoId, $empresaId]);
$proc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$proc) { header('Location: /ingenieria/procesos.php'); exit; }

// Cargar datos E y C
$eyc = $conn->prepare("SELECT * FROM procesos_eyc WHERE eyc_proceso_id = ?");
$eyc->execute([$procesoId]);
$eyc = $eyc->fetch(PDO::FETCH_ASSOC) ?: [];

// Cargar reometría
$reoStmt = $conn->prepare("SELECT * FROM procesos_reometria WHERE reo_proceso_id = ? ORDER BY reo_orden");
$reoStmt->execute([$procesoId]);
$reometria = $reoStmt->fetchAll(PDO::FETCH_ASSOC);

$menu_retorno = '/ingenieria/procesos.php';
$menu_principal = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};
$codigo = $proc['pi_cod_prod'] . '/' . ($proc['ma_no'] ?: $proc['ma_marca']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proceso <?= htmlspecialchars($codigo) ?></title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png">
  <link rel="stylesheet" href="/css/acg.estilos.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <style>
    /* ── Sección de datos ───────────────────── */
    .proc-section { background:#fff; border-radius:8px; padding:18px 20px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:16px; }
    .proc-section h3 { margin:0 0 14px; font-size:.88em; color:#fff; background:#1e3a8a;
                       padding:7px 14px; border-radius:5px; text-transform:uppercase; letter-spacing:.5px; }
    .info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; }
    .info-item label { display:block; font-size:.72em; color:#666; font-weight:600; }
    .info-item span  { font-size:.9em; }

    /* ── Inputs amarillos ───────────────────── */
    .inp-amarillo { border:2px solid #f59e0b !important; background:#fffbeb !important; }
    .inp-azul     { border:2px solid #0891b2 !important; background:#e0f2fe !important; }

    /* ── Resultados ─────────────────────────── */
    .res-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:8px; }
    .res-item label { font-size:.72em; color:#555; font-weight:600; display:block; }
    .res-val { padding:5px 8px; border:1px solid #e5e7eb; border-radius:4px; font-family:monospace; font-size:.9em; background:#f9fafb; }
    .res-val.ok   { background:#d1fae5; border-color:#6ee7b7; color:#065f46; font-weight:700; }
    .res-val.warn { background:#fef3c7; border-color:#fcd34d; color:#92400e; }
    .res-val.bad  { background:#fee2e2; border-color:#fca5a5; color:#991b1b; }
    .res-val.hl   { background:#dbeafe; border-color:#93c5fd; font-weight:700; }

    /* ── Reometría ──────────────────────────── */
    .reo-table { width:100%; border-collapse:collapse; font-size:.85em; }
    .reo-table th { background:#1e3a8a; color:#fff; padding:6px 10px; text-align:center; font-size:.8em; }
    .reo-table td { padding:4px 6px; border:1px solid #e5e7eb; text-align:center; }
    .reo-table tr:nth-child(even) td { background:#f8faff; }
    .reo-table input[type=number] { width:80px; padding:4px 6px; border:1px solid #d1d5db;
        border-radius:3px; text-align:center; font-size:.88em; background:#fffbeb; }
    .reo-table td.calc-td { background:#f0f4ff; font-family:monospace; }
    .chart-wrap { height:280px; margin-top:16px; }

    /* ── Ocultos ────────────────────────────── */
    .ocultos { display:none; }

    /* ── Print ──────────────────────────────── */
    @media print {
      header, footer, .acciones, .back-button, .burger-btn { display:none !important; }
      .proc-section { box-shadow:none; border:1px solid #ccc; page-break-inside:avoid; }
      .proc-section h3 { background:#555 !important; -webkit-print-color-adjust:exact; }
      body { font-size:10px; }
      .chart-wrap { height:200px; }
    }
  </style>
</head>
<body>
<header class="header">
  <div class="header-title-group">
    <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
    <h1>Proceso: <span style="font-family:monospace;"><?= htmlspecialchars($codigo) ?></span></h1>
  </div>
  <div class="header-right">
    <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    <?= burgerBtn() ?>
  </div>
</header>

<main class="main-container">

  <!-- ── Encabezado del proceso ─────────────────────────────── -->
  <div class="proc-section">
    <h3>📋 Datos generales del proceso</h3>
    <div class="info-grid">
      <div class="info-item"><label>Código proceso</label><span style="font-family:monospace;font-weight:700;"><?= htmlspecialchars($codigo) ?></span></div>
      <div class="info-item"><label>Pieza</label><span><?= htmlspecialchars($proc['pi_cod_prod']) ?></span></div>
      <div class="info-item"><label>Descripción</label><span><?= htmlspecialchars($proc['pi_descripcion'] ?? '—') ?></span></div>
      <div class="info-item"><label>Color</label><span><?= htmlspecialchars($proc['pi_color'] ?? '—') ?></span></div>
      <div class="info-item"><label>Molde</label><span><?= htmlspecialchars($proc['mo_numero'] ?? $proc['pi_molde'] ?? '—') ?></span></div>
      <div class="info-item"><label>Resina</label><span><?= htmlspecialchars(($proc['re_tipo_resina'] ?? '') . ' ' . ($proc['re_grado'] ?? '') ?: ($proc['pi_resina'] ?? '—')) ?></span></div>
      <div class="info-item"><label>Máquina</label><span><?= htmlspecialchars(($proc['ma_no'] ? $proc['ma_no'].' — ' : '') . $proc['ma_marca'] . ' ' . $proc['ma_modelo']) ?></span></div>
      <div class="info-item"><label>Creado por</label><span><?= htmlspecialchars($proc['creado_por'] ?? '—') ?></span></div>
      <div class="info-item"><label>Fecha</label><span><?= date('d/m/Y', strtotime($proc['pr_fecha_creacion'])) ?></span></div>
    </div>
  </div>

  <!-- ── Datos de Entrada y Cálculo (Paso 2) ───────────────── -->
  <div class="proc-section">
    <h3>⚙️ Datos de Entrada y Cálculo — Paso 2</h3>

    <!-- DATOS DE LA PARTE (cargados del catálogo, solo lectura) -->
    <p style="font-size:.8em;color:#555;margin-bottom:10px;">
      <strong>Datos del catálogo</strong> — cargados automáticamente de pieza, molde y resina.
    </p>
    <div class="res-grid" style="margin-bottom:18px;">
      <div class="res-item"><label>Cavidades activas</label><div class="res-val" id="d_cavidades"><?= $proc['mo_no_cavidades'] ?? '—' ?></div></div>
      <div class="res-item"><label>Peso por cavidad (gr)</label><div class="res-val" id="d_peso_cav"><?= $proc['mo_peso_pieza'] ?? '—' ?></div></div>
      <div class="res-item"><label>Gates por cavidad</label><div class="res-val" id="d_gates"><?= $proc['mo_puert_cavidad'] ?? '—' ?></div></div>
      <div class="res-item"><label>Coladas</label><div class="res-val" id="d_coladas"><?= $proc['mo_no_coladas'] ?? '—' ?></div></div>
      <div class="res-item"><label>Peso por colada (gr)</label><div class="res-val" id="d_peso_col"><?= $proc['mo_peso_colada'] ?? '—' ?></div></div>
      <div class="res-item"><label>Espesor pieza (mm)</label><div class="res-val" id="d_espesor"><?= $proc['pi_espesor'] ?? '—' ?></div></div>
      <div class="res-item"><label>Densidad fría (g/cm³)</label><div class="res-val" id="d_densidad_fria"><?= $proc['re_densidad'] ?? '—' ?></div></div>
      <div class="res-item"><label>Factor a caliente</label><div class="res-val" id="d_factor"><?= $proc['re_factor_correccion'] ?? '—' ?></div></div>
      <div class="res-item"><label>Tipo resina</label><div class="res-val"><?= htmlspecialchars(($proc['re_tipo_resina'] ?? '') . ' ' . ($proc['re_grado'] ?? '')) ?></div></div>
      <div class="res-item"><label>Temp. masa max (°C)</label><div class="res-val" id="d_temp_max"><?= $proc['re_temp_masa_max'] ?? '—' ?></div></div>
      <div class="res-item"><label>Temp. masa min (°C)</label><div class="res-val" id="d_temp_min"><?= $proc['re_temp_masa_min'] ?? '—' ?></div></div>
      <div class="res-item"><label>Secado temp. (°C)</label><div class="res-val"><?= $proc['re_sec_temp'] ?? '—' ?></div></div>
      <div class="res-item"><label>Secado tiempo (h)</label><div class="res-val"><?= $proc['re_sec_tiempo'] ?? '—' ?></div></div>
      <div class="res-item"><label>Husillo máquina (mm)</label><div class="res-val" id="d_husillo_maq"><?= $proc['ma_diam_husillo'] ?? '—' ?></div></div>
      <div class="res-item"><label>Vel. máx. iny. (mm/s)</label><div class="res-val" id="d_vel_max"><?= $proc['ma_max_vel_inyec'] ?? '—' ?></div></div>
      <div class="res-item"><label>Pres. máx. iny. (bar)</label><div class="res-val" id="d_pres_max"><?= $proc['ma_max_pres_inyec'] ?? '—' ?></div></div>
    </div>

    <!-- INPUTS MANUALES (celdas amarillas) -->
    <p style="font-size:.8em;color:#555;margin-bottom:10px;">
      <strong style="color:#92400e;">Entradas manuales</strong> — ajusta según el proceso real.
    </p>
    <div class="res-grid" style="margin-bottom:18px;" id="form-eyc">
      <div class="res-item">
        <label>Descripción del proceso</label>
        <input type="text" id="eyc_descripcion" class="inp-amarillo" value="<?= htmlspecialchars($eyc['eyc_descripcion'] ?? 'Base') ?>" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>Cojín / Conmutación (mm)</label>
        <input type="number" id="eyc_cojin" class="inp-amarillo" value="<?= $eyc['eyc_cojin'] ?? '' ?>" step="0.1" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>Vel. inyección (mm/s)</label>
        <input type="number" id="eyc_vel_iny" class="inp-amarillo" value="<?= $eyc['eyc_vel_inyeccion'] ?? '' ?>" step="1" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>T. sostenimiento (s) <span style="color:#0891b2;">●</span></label>
        <input type="number" id="eyc_tpo_sos" class="inp-azul" value="<?= $eyc['eyc_tpo_sostenimiento'] ?? 2 ?>" step="0.1" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>T. enfriamiento (s)</label>
        <input type="number" id="eyc_tpo_enf" class="inp-amarillo" value="<?= $eyc['eyc_tpo_enfriamiento'] ?? 6 ?>" step="0.1" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>Diám. bebedero (mm)</label>
        <input type="number" id="eyc_diam_beb" class="inp-amarillo" value="<?= $eyc['eyc_diam_bebedero'] ?? 6 ?>" step="0.1" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>% de molido</label>
        <input type="number" id="eyc_molido" class="inp-amarillo" value="<?= $eyc['eyc_porc_molido'] ?? ($proc['pi_porc_molido'] ?? 0) ?>" step="0.1" min="0" max="100" oninput="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;">
      </div>
      <div class="res-item">
        <label>Posición del puerto</label>
        <select id="eyc_pos_puerto" class="inp-amarillo" onchange="calcular()" style="width:100%;padding:5px 8px;border-radius:4px;border:2px solid #f59e0b;background:#fffbeb;">
          <option value="2">Al centro</option>
          <option value="1">En la orilla</option>
        </select>
      </div>
    </div>

    <!-- RESULTADOS CALCULADOS VISIBLES -->
    <p style="font-size:.8em;color:#555;margin-bottom:10px;"><strong>Resultados calculados</strong></p>
    <div class="res-grid">
      <div class="res-item"><label>Densidad en caliente (g/cm³)</label><div class="res-val" id="r_dens_cal">—</div></div>
      <div class="res-item"><label>Peso del disparo (gr)</label><div class="res-val hl" id="r_peso_disp">—</div></div>
      <div class="res-item"><label>Volumen del disparo (cm³)</label><div class="res-val hl" id="r_vol_disp">—</div></div>
      <div class="res-item"><label>Volumen por cavidad (cm³)</label><div class="res-val" id="r_vol_cav">—</div></div>
      <div class="res-item"><label>Volumen por colada (cm³)</label><div class="res-val" id="r_vol_col">—</div></div>
      <div class="res-item"><label>Ø husillo mínimo (mm)</label><div class="res-val" id="r_hus_min">—</div></div>
      <div class="res-item"><label>Ø husillo sugerido (mm)</label><div class="res-val hl" id="r_hus_sug">—</div></div>
      <div class="res-item"><label>Ø husillo máximo (mm)</label><div class="res-val" id="r_hus_max">—</div></div>
      <div class="res-item"><label>Tonelaje sugerido</label><div class="res-val" id="r_tonelaje">—</div></div>
      <div class="res-item"><label>Estado husillo máquina</label><div class="res-val" id="r_husillo_estado">—</div></div>
      <div class="res-item"><label>Presión inicial sugerida (bar)</label><div class="res-val hl" id="r_pres_inicial">—</div></div>
      <div class="res-item"><label>Step de presión (bar)</label><div class="res-val" id="r_step_pres">—</div></div>
    </div>

    <div class="acciones" style="margin-top:16px;">
      <button class="btn btn-guardar" onclick="guardarEyC()">💾 Guardar datos E y C</button>
      <a href="/ingenieria/calificador-proceso.php?id=<?= $procesoId ?>" class="btn btn-excel">🏆 Abrir calificador</a>
    </div>
  </div>

  <!-- ── Reometría Presión vs Tiempo (Paso 3) ──────────────── -->
  <div class="proc-section">
    <h3>📈 Reometría Presión vs Tiempo — Paso 3</h3>
    <p style="font-size:.82em;color:#555;margin-bottom:12px;">
      Ingresa las presiones medidas en cada prueba. El tiempo se calcula automáticamente.
      Las filas aparecen de una en una conforme ingresas datos.
    </p>

    <div class="tabla-container-scroll">
      <table class="reo-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Presión (bar)</th>
            <th>Tiempo 1 (s)</th>
            <th>Tiempo 2 (s)</th>
            <th>Prom. (s)</th>
            <th>Diferencia</th>
          </tr>
        </thead>
        <tbody id="reo-body"></tbody>
      </table>
    </div>

    <div class="chart-wrap">
      <canvas id="reoChart"></canvas>
    </div>

    <div class="acciones" style="margin-top:12px;">
      <button class="btn btn-guardar" onclick="guardarReometria()">💾 Guardar reometría</button>
      <button class="btn btn-pdf" onclick="window.print()">📥 Imprimir</button>
    </div>
  </div>

</main>

<footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>

<script>
// ── Datos del servidor ───────────────────────────────────────
const PROC = {
    id:            <?= $procesoId ?>,
    cavidades:     <?= (float)($proc['mo_no_cavidades'] ?? 0) ?>,
    peso_cav:      <?= (float)($proc['mo_peso_pieza']   ?? 0) ?>,
    gates:         <?= (float)($proc['mo_puert_cavidad']?? 0) ?>,
    coladas:       <?= (float)($proc['mo_no_coladas']   ?? 0) ?>,
    peso_col:      <?= (float)($proc['mo_peso_colada']  ?? 0) ?>,
    espesor:       <?= (float)($proc['pi_espesor']      ?? 0) ?>,
    area_proy:     <?= (float)($proc['pi_area_proy']    ?? 0) ?>,
    dens_fria:     <?= (float)($proc['re_densidad']     ?? 0) ?>,
    factor_cal:    <?= (float)($proc['re_factor_correccion'] ?? 0.811) ?>,
    vel_max:       <?= (float)($proc['ma_max_vel_inyec']?? 0) ?>,
    pres_max:      <?= (float)($proc['ma_max_pres_inyec']?? 0) ?>,
    husillo_maq:   <?= (float)($proc['ma_diam_husillo'] ?? 0) ?>,
};
const REO_INIT = <?= json_encode($reometria) ?>;

// ── Helpers ──────────────────────────────────────────────────
const $id = id => document.getElementById(id);
const num  = id => parseFloat($id(id)?.value) || 0;
const fmt  = (v, d=3) => (v === null || isNaN(v) || !isFinite(v)) ? '—' : (+v).toFixed(d);
function setRes(id, v, d, cls) {
    const el = $id(id); if (!el) return;
    el.textContent = fmt(v, d);
    el.className = 'res-val' + (cls ? ' ' + cls : '');
}

// ── Motor de cálculo v2 (fórmulas hoja '3' Ingeniería_de_proceso__2_) ──
function calcular() {
    // Datos del catálogo
    const cav     = PROC.cavidades;
    const p_cav   = PROC.peso_cav;
    const gates   = PROC.gates;
    const col     = PROC.coladas;
    const p_col   = PROC.peso_col;
    const dens_f  = PROC.dens_fria;
    const factor  = PROC.factor_cal || 0.811;
    const area_proy = PROC.area_proy; // cm² — necesario para tonelaje v2

    // Input nuevo: posición del puerto (1=orilla, 2=centro)
    const pos_puerto = parseFloat(document.getElementById('eyc_pos_puerto')?.value || 2);

    // ─ Cálculos VISIBLES ─────────────────────────────────────
    // D60: Densidad en caliente = densidad_fria * factor
    const dens_cal = dens_f * factor;

    // D61: Peso del disparo = cavidades*peso_cav + coladas*peso_col
    const peso_disp = cav * p_cav + col * p_col;

    // D62: Volumen del disparo = peso_disparo / densidad_caliente
    const vol_disp = dens_cal > 0 ? peso_disp / dens_cal : null;

    // D63: Volumen por cavidad = peso_cav / densidad_caliente
    const vol_cav = dens_cal > 0 ? p_cav / dens_cal : null;

    // D64: Volumen por gate = vol_cav / gates
    const vol_gate = vol_cav && gates > 0 ? vol_cav / gates : null;

    // D65: Volumen por colada = peso_col / densidad_caliente
    const vol_col = dens_cal > 0 && col > 0 ? p_col / dens_cal : null;

    // D66-D68: Diámetros husillo
    const hus_min = vol_disp ? 10 * Math.pow(vol_disp / 0.7854 / 3.5, 0.33333) : null;
    const hus_sug = vol_disp ? 10 * Math.pow(vol_disp / 0.7854 / 2.2, 0.33333) : null;
    const hus_max = vol_disp ? 10 * Math.pow(vol_disp / 0.7854 / 1.1, 0.33333) : null;

    // ─ Cálculos OCULTOS (no se muestran al usuario) ──────────
    // H59: espesor en cm = espesor_mm / 10  (ahora calculado, antes hardcoded 0.1)
    const espesor_cm = (PROC.espesor || 0) / 10;

    // H60: Disco equivalente por cavidad = vol_cav / espesor_cm
    const disco_cav = vol_cav && espesor_cm > 0 ? vol_cav / espesor_cm : null;

    // H61: Disco equivalente por puerto = disco_cav / gates
    const disco_prt = disco_cav && gates > 0 ? disco_cav / gates : null;

    // H62: Diámetro disco equiv/cavidad = sqrt(disco_cav / 0.7854)
    const diam_disco_cav = disco_cav ? Math.sqrt(disco_cav / 0.7854) : null;

    // H63: Diámetro disco equiv/puerto = sqrt(disco_prt / 0.7854)
    const diam_disco_prt = disco_prt ? Math.sqrt(disco_prt / 0.7854) : null;

    // H64: posición puerto (1=orilla, 2=centro) — viene del input
    // H65: tipo_puerto — solo informativo

    // H66: Recorrido = diam_disco_prt / espesor_cm / pos_puerto  ← divide por posición
    const recorrido = diam_disco_prt && espesor_cm > 0
        ? diam_disco_prt / espesor_cm / pos_puerto : null;

    // H67: Efecto del recorrido = (8.8 + recorrido * 0.069) / 10  ← fórmula nueva
    const efecto_rec = recorrido !== null ? (8.8 + recorrido * 0.069) / 10 : null;

    // H68: Número de discos = cavidades * gates
    const num_discos = cav * gates;

    // H69: Fuerza media/cm² = 0.3  (constante)
    const fuerza_media_cm2 = 0.3;

    // H70: Fuerza compensada recorrido = efecto_rec * fuerza_media_cm2
    const fuerza_comp = efecto_rec !== null ? efecto_rec * fuerza_media_cm2 : null;

    // H71: Fuerza por disco = area_proyectada_pieza * fuerza_comp  ← usa área real
    const fuerza_disco = (area_proy && fuerza_comp !== null) ? area_proy * fuerza_comp : null;

    // H72: Fuerza total = fuerza_disco * cavidades
    const fuerza_total = fuerza_disco !== null ? fuerza_disco * cav : null;

    // D72: Presión máxima = 1904 bar (constante del proceso)
    const pres_max = 1904;

    // H73: Presión promedio = 0.369 (constante)
    const pres_promedio = 0.369;

    // D73: Presión inicial = INT(pres_promedio * pres_max * efecto_rec)
    const pres_inicial = efecto_rec !== null
        ? Math.trunc(pres_promedio * pres_max * efecto_rec) : null;

    // H74: pres_max - pres_inicial
    const pres_rango = pres_inicial !== null ? pres_max - pres_inicial : null;

    // H75: iteraciones = 29
    // H76: step = INT(pres_rango / 29)
    const step_pres = pres_rango !== null ? Math.trunc(pres_rango / 29) : null;

    // Tonelaje = fuerza_total (ya incluye cavidades)
    const tonelaje = fuerza_total;

    // ─ Mostrar resultados visibles ───────────────────────────
    setRes('r_dens_cal',   dens_cal,   4);
    setRes('r_peso_disp',  peso_disp,  2, 'hl');
    setRes('r_vol_disp',   vol_disp,   4, 'hl');
    setRes('r_vol_cav',    vol_cav,    4);
    setRes('r_vol_col',    vol_col !== null ? vol_col : 0, 4);
    setRes('r_hus_min',    hus_min,    2);
    setRes('r_hus_sug',    hus_sug,    2, 'hl');
    setRes('r_hus_max',    hus_max,    2);
    setRes('r_tonelaje',   tonelaje,   1);
    setRes('r_pres_inicial', pres_inicial, 0, 'hl');
    setRes('r_step_pres',  step_pres,  0);

    // Estado husillo máquina
    const hm = PROC.husillo_maq;
    if (hm && hus_min && hus_max) {
        const el = $id('r_husillo_estado');
        if (hm >= hus_min && hm <= hus_max) {
            el.textContent = `✅ OK (${hm} mm)`;
            el.className = 'res-val ok';
        } else if (hm < hus_min) {
            el.textContent = `⚠️ Muy chico (${hm} mm)`;
            el.className = 'res-val warn';
        } else {
            el.textContent = `❌ Muy grande (${hm} mm)`;
            el.className = 'res-val bad';
        }
    }

    // Actualizar eje de presiones de la reometría
    actualizarPresionesReo(pres_inicial, step_pres);

    return { dens_cal, peso_disp, vol_disp, vol_cav, vol_col,
             hus_min, hus_sug, hus_max, tonelaje, pres_inicial, step_pres };
}

// ── Reometría ────────────────────────────────────────────────
let reoData = Array.from({length:30}, (_,i) => ({
    orden: i+1, p1: null, p2: null, prom: null, tiempo: null, dif: null
}));
let reoChart = null;
let presInicial = 0;
let intervalo = 0;

function actualizarPresionesReo(presIni, step) {
    // Nueva lógica v2: la PRESIÓN es el eje calculado (30 iteraciones)
    // El TIEMPO es lo que el usuario captura
    // presión_fila_i = pres_inicial + i * step
    if (presIni === null || step === null) return;
    reoData.forEach((r, i) => {
        r.presion_calc = presIni + (i * step); // presión calculada para esa fila
        // Promedio de tiempos capturados (t1 y t2)
        r.prom = (r.p1 !== null || r.p2 !== null)
                 ? ((r.p1 || 0) + (r.p2 || 0)) / (r.p1 !== null && r.p2 !== null ? 2 : 1)
                 : null;
        r.dif  = i > 0 && r.prom !== null && reoData[i-1].prom !== null
                 ? reoData[i-1].prom - r.prom : null;
    });
    renderReoTabla();
    actualizarGrafica();
}

function renderReoTabla() {
    const tbody = $id('reo-body');
    tbody.innerHTML = '';

    reoData.forEach((r, i) => {
        const ultima_con_dato = reoData.reduce((acc, rd, ri) =>
            rd.p1 !== null || rd.p2 !== null ? ri : acc, -1);
        if (i > ultima_con_dato + 1 || i >= 30) return;

        const pres = r.presion_calc !== undefined ? r.presion_calc.toFixed(0) : '—';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${r.orden}</td>
            <td class="calc-td" style="font-weight:700;color:#1e3a8a;">${pres}</td>
            <td><input type="number" step="0.001" value="${r.p1 ?? ''}"
                onchange="setReo(${i},'p1',this.value)"
                style="width:80px;padding:3px 5px;border:1px solid #d1d5db;border-radius:3px;text-align:center;background:#fffbeb;font-size:.88em;"></td>
            <td><input type="number" step="0.001" value="${r.p2 ?? ''}"
                onchange="setReo(${i},'p2',this.value)"
                style="width:80px;padding:3px 5px;border:1px solid #d1d5db;border-radius:3px;text-align:center;background:#fffbeb;font-size:.88em;"></td>
            <td class="calc-td">${r.prom !== null ? r.prom.toFixed(3) : '—'}</td>
            <td class="calc-td">${r.dif  !== null ? r.dif.toFixed(3)  : '—'}</td>`;
        tbody.appendChild(tr);
    });
}

function setReo(idx, campo, val) {
    reoData[idx][campo] = val !== '' ? parseFloat(val) : null;
    recalcularTiempos();
}

function actualizarGrafica() {
    // v2: X = tiempo capturado (promedio), Y = presión calculada
    const datos = reoData
        .filter(r => r.prom !== null && r.presion_calc !== undefined)
        .map(r => ({ x: r.prom, y: r.presion_calc }));

    if (!reoChart) {
        const ctx = $id('reoChart').getContext('2d');
        reoChart = new Chart(ctx, {
            type: 'scatter',
            data: { datasets: [{ label: 'Presión vs Tiempo', data: datos,
                borderColor: '#1e3a8a', backgroundColor: 'rgba(30,58,138,.15)',
                pointRadius: 5, showLine: true, tension: 0.3 }] },
            options: { responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Tiempo (s)' } },
                    y: { title: { display: true, text: 'Presión (bar)' } }
                },
                plugins: { legend: { display: false },
                           title: { display: true, text: 'RELACIÓN PRESIÓN vs. TIEMPO' } }
            }
        });
    } else {
        reoChart.data.datasets[0].data = datos;
        reoChart.update();
    }
}

// ── Guardar E y C ────────────────────────────────────────────
function guardarEyC() {
    const calc = calcular();
    const payload = {
        proceso_id:      PROC.id,
        descripcion:     $id('eyc_descripcion').value,
        cojin:           num('eyc_cojin')    || null,
        vel_inyeccion:   num('eyc_vel_iny')  || null,
        tpo_sostenimiento: num('eyc_tpo_sos') || null,
        tpo_enfriamiento:  num('eyc_tpo_enf') || null,
        diam_bebedero:   num('eyc_diam_beb') || null,
        porc_molido:     num('eyc_molido')   || null,
        pos_puerto:      parseFloat($id('eyc_pos_puerto')?.value || 2),
        // resultados
        densidad_caliente: calc.dens_cal,
        peso_disparo:      calc.peso_disp,
        vol_disparo:       calc.vol_disp,
        diam_husillo_min:  calc.hus_min,
        diam_husillo_sug:  calc.hus_sug,
        diam_husillo_max:  calc.hus_max,
        tonelaje_sug:      calc.tonelaje,
    };
    fetch('/ingenieria/guardar_eyc.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    }).then(r=>r.json()).then(res => {
        alert(res.ok ? '✅ Datos E y C guardados correctamente' : '❌ Error: ' + (res.mensaje||''));
    });
}

// ── Guardar Reometría ────────────────────────────────────────
function guardarReometria() {
    const filas = reoData.filter(r => r.p1 !== null || r.p2 !== null);
    if (!filas.length) { alert('No hay datos de reometría para guardar.'); return; }
    fetch('/ingenieria/guardar_reometria.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ proceso_id: PROC.id, filas })
    }).then(r=>r.json()).then(res => {
        alert(res.ok ? '✅ Reometría guardada correctamente' : '❌ Error: ' + (res.mensaje||''));
    });
}

// ── Inicialización ───────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    // Restaurar posición de puerto si estaba guardada
    // (almacenado como campo en eyc, por ahora se inicializa en 2=centro)

    // Cargar reometría existente
    REO_INIT.forEach(r => {
        const idx = r.reo_orden - 1;
        if (idx >= 0 && idx < 30) {
            reoData[idx].p1 = r.reo_presion_1 !== null ? parseFloat(r.reo_presion_1) : null;
            reoData[idx].p2 = r.reo_presion_2 !== null ? parseFloat(r.reo_presion_2) : null;
        }
    });
    calcular();
});
</script>
</body>
</html>
