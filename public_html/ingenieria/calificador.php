<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

$menu_retorno = '/ingenieria/procesos.php';

// ── Cargar catálogos para los selectores ─────────────────────────────
// Máquinas
$sqlM = "SELECT ma_id, ma_no, ma_marca, ma_modelo,
                ma_diam_husillo, ma_carga_max, ma_vol_inyec,
                ma_tam_unid_inyec, ma_max_vel_inyec, ma_max_pres_inyec,
                ma_termoreguladores, ma_canal_caliente, ma_tonelaje
         FROM maquinas WHERE ma_activo = 1";
$paramsM = [];
if ($rol !== 1) {
    $sqlM .= " AND ma_empresa = :empresa";
    $paramsM[':empresa'] = $empresaId;
    if ($plantaId) { $sqlM .= " AND ma_planta = :planta"; $paramsM[':planta'] = $plantaId; }
}
$sqlM .= " ORDER BY ma_marca, ma_modelo";
$stmtM = $conn->prepare($sqlM); $stmtM->execute($paramsM);
$maquinas = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// Moldes
$sqlMo = "SELECT mo_id, mo_numero, mo_no_pieza, mo_no_cavidades,
                 mo_peso_pieza, mo_no_coladas, mo_peso_colada, mo_peso_disparo,
                 mo_puert_cavidad, mo_tipo_colada, mo_thermoreguladores,
                 mo_anillo_centrador, mo_apert_min, mo_abierto
          FROM moldes WHERE mo_activo = 1";
$paramsMo = [];
if ($rol !== 1) { $sqlMo .= " AND mo_empresa = :empresa"; $paramsMo[':empresa'] = $empresaId; }
$sqlMo .= " ORDER BY mo_numero";
$stmtMo = $conn->prepare($sqlMo); $stmtMo->execute($paramsMo);
$moldes = $stmtMo->fetchAll(PDO::FETCH_ASSOC);

// Resinas
$sqlR = "SELECT re_id, re_cod_int, re_tipo_resina, re_grado,
                re_densidad, re_factor_correccion, re_carga,
                re_sec_temp, re_sec_tiempo,
                re_temp_masa_max, re_temp_masa_min,
                re_temp_ref_max, re_temp_ref_min, re_porc_reciclado
         FROM resinas WHERE re_activo = 1";
$paramsR = [];
if ($rol !== 1) { $sqlR .= " AND re_empresa = :empresa"; $paramsR[':empresa'] = $empresaId; }
$sqlR .= " ORDER BY re_tipo_resina, re_grado";
$stmtR = $conn->prepare($sqlR); $stmtR->execute($paramsR);
$resinas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

// Piezas
$sqlP = "SELECT pi_id, pi_cod_prod, pi_descripcion, pi_espesor, pi_area_proy, pi_color
         FROM piezas WHERE pi_activo = 1";
$paramsP = [];
if ($rol !== 1) { $sqlP .= " AND pi_empresa = :empresa"; $paramsP[':empresa'] = $empresaId; }
$sqlP .= " ORDER BY pi_cod_prod";
$stmtP = $conn->prepare($sqlP); $stmtP->execute($paramsP);
$piezas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// JSON para JS
$maquinasJson = json_encode(array_column($maquinas, null, 'ma_id'), JSON_UNESCAPED_UNICODE);
$moldesJson   = json_encode(array_column($moldes,   null, 'mo_id'), JSON_UNESCAPED_UNICODE);
$resinasJson  = json_encode(array_column($resinas,  null, 're_id'), JSON_UNESCAPED_UNICODE);
$piezasJson   = json_encode(array_column($piezas,   null, 'pi_id'), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificador de Proceso</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <style>
        /* ── Layout de selección ───────────────── */
        .sel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }
        .sel-card { background:#fff; border-radius:8px; padding:14px 16px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        .sel-card h4 { margin:0 0 8px; color:#0056b3; font-size:.9em; }
        .sel-card select { width:100%; padding:7px; border:1px solid #d1d5db; border-radius:4px; font-size:.88em; }
        .sel-card .add-link { font-size:.78em; color:#0056b3; text-decoration:none; margin-top:5px; display:inline-block; }
        .sel-resumen {
            background:#f0f7ff; border:1px solid #bfdbfe; border-radius:6px;
            padding:10px 16px; font-size:.85em; color:#1e3a8a; margin-bottom:16px;
            display:none;
        }
        .sel-resumen span { margin-right:18px; }

        /* ── Sección de inputs manuales ─────────── */
        .inputs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .inp-group { display:flex; flex-direction:column; }
        .inp-group label { font-size:.8em; font-weight:600; color:#555; margin-bottom:3px; }
        .inp-group input {
            padding:6px 8px; border:2px solid #f59e0b;
            border-radius:4px; font-size:.92em; background:#fffbeb;
        }
        .inp-group input:focus { outline:none; border-color:#d97706; background:#fff; }
        .inp-group input.readonly-calc {
            background:#2c3e50 !important; color:#ecf0f1 !important;
            border-color:#1a252f; cursor:not-allowed; font-weight:600;
        }
        .inp-group .inp-unit { font-size:.75em; color:#888; margin-top:2px; }

        /* ── Secciones del calificador ──────────── */
        .calc-section {
            background:#fff; border-radius:8px; padding:18px 20px;
            box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:16px;
        }
        .calc-section h3 {
            margin:0 0 14px; font-size:1em; color:#fff;
            background:#0056b3; padding:8px 14px; border-radius:5px;
            letter-spacing:.5px; text-transform:uppercase; font-size:.88em;
        }
        .calc-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap:8px;
        }
        .calc-item { display:flex; flex-direction:column; }
        .calc-item label { font-size:.75em; color:#555; font-weight:600; margin-bottom:2px; }
        .calc-item .val {
            padding:5px 8px; border:1px solid #e5e7eb; border-radius:4px;
            font-size:.92em; background:#f9fafb; font-family:monospace;
            color:#111;
        }
        .calc-item .val.highlight {
            background:#dbeafe; border-color:#93c5fd; font-weight:700; color:#1e3a8a;
        }
        .calc-item .val.warn {
            background:#fef3c7; border-color:#fcd34d; color:#92400e;
        }
        .calc-item .val.ok {
            background:#d1fae5; border-color:#6ee7b7; color:#065f46;
        }
        .calc-item .val.bad {
            background:#fee2e2; border-color:#fca5a5; color:#991b1b;
        }
        .calc-item .unit { font-size:.72em; color:#888; margin-top:1px; }

        /* ── Tabla de programación ──────────────── */
        .prog-table { width:100%; border-collapse:collapse; font-size:.83em; margin-top:8px; }
        .prog-table th {
            background:#1e3a8a; color:#fff; padding:6px 10px;
            text-align:center; font-weight:600; font-size:.8em;
        }
        .prog-table td {
            padding:5px 10px; border:1px solid #e5e7eb;
            text-align:center; font-family:monospace;
        }
        .prog-table tr:nth-child(even) td { background:#f8faff; }
        .prog-table td.lbl { text-align:left; font-weight:600; font-size:.82em; color:#333; font-family:sans-serif; }

        /* ── Botones de acción ──────────────────── */
        .acciones { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }

        /* ── Sección no activa ──────────────────── */
        .inactive { opacity:.45; pointer-events:none; }

        /* ── Print ──────────────────────────────── */
        @media print {
            header, footer, .sel-grid, .sel-resumen, .acciones,
            .back-button, .burger-btn { display:none !important; }
            .calc-section { box-shadow:none; border:1px solid #ccc; page-break-inside:avoid; }
            .calc-section h3 { background:#ccc !important; color:#000 !important; -webkit-print-color-adjust:exact; }
            body { font-size:11px; }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_retorno ?>">
            <img src="/imagenes/logo.png" alt="Logo" class="header-logo">
        </a>
        <h1>Calificador de Proceso</h1>
    </div>
    <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">

    <!-- ── SELECTORES ───────────────────────────────────── -->
    <div class="calc-section">
        <h3>📋 Selección de componentes</h3>
        <div class="sel-grid">
            <div class="sel-card">
                <h4>🏭 Máquina *</h4>
                <select id="sel_maquina" onchange="cargarMaquina()">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($maquinas as $m): ?>
                    <option value="<?= $m['ma_id'] ?>">
                        <?= htmlspecialchars(($m['ma_no'] ? $m['ma_no'].' — ' : '').$m['ma_marca'].' '.$m['ma_modelo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-maquina.php" class="add-link">+ Registrar nueva</a>
            </div>
            <div class="sel-card">
                <h4>📦 Molde *</h4>
                <select id="sel_molde" onchange="cargarMolde()">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($moldes as $mo): ?>
                    <option value="<?= $mo['mo_id'] ?>">
                        <?= htmlspecialchars(($mo['mo_numero'] ?? 'S/N').($mo['mo_no_pieza'] ? ' — '.$mo['mo_no_pieza'] : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-molde.php" class="add-link">+ Registrar nuevo</a>
            </div>
            <div class="sel-card">
                <h4>💧 Resina *</h4>
                <select id="sel_resina" onchange="cargarResina()">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($resinas as $r): ?>
                    <option value="<?= $r['re_id'] ?>">
                        <?= htmlspecialchars(($r['re_tipo_resina'] ?? '').($r['re_grado'] ? ' '.$r['re_grado'] : '').($r['re_cod_int'] ? ' ['.$r['re_cod_int'].']' : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-resina.php" class="add-link">+ Registrar nueva</a>
            </div>
            <div class="sel-card">
                <h4>🧩 Pieza</h4>
                <select id="sel_pieza" onchange="cargarPieza()">
                    <option value="">— Sin seleccionar —</option>
                    <?php foreach ($piezas as $p): ?>
                    <option value="<?= $p['pi_id'] ?>">
                        <?= htmlspecialchars(($p['pi_cod_prod'] ?? '').($p['pi_descripcion'] ? ' — '.$p['pi_descripcion'] : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-pieza.php" class="add-link">+ Registrar nueva</a>
            </div>
        </div>

        <!-- Resumen de selección -->
        <div class="sel-resumen" id="resumen"></div>
    </div>

    <!-- ── INPUTS MANUALES (celdas amarillas del Excel) ──── -->
    <div class="calc-section" id="sec-inputs">
        <h3>✏️ Datos de entrada (ajuste manual)</h3>
        <div class="inputs-grid">
            <div class="inp-group">
                <label>Descripción del proceso</label>
                <input type="text" id="inp_descripcion" value="Base" oninput="calcular()">
            </div>
            <div class="inp-group">
                <label>Cojín / Conmutación (mm)</label>
                <input type="number" id="inp_cojin" value="21" step="0.1" oninput="calcular()">
                <span class="inp-unit">Resultado de conmutación medido</span>
            </div>
            <div class="inp-group">
                <label>Cavidades activas</label>
                <input type="number" id="inp_cavidades_activas" value="" step="1" oninput="calcular()">
                <span class="inp-unit">Del molde seleccionado</span>
            </div>
            <div class="inp-group">
                <label>Peso por cavidad (grs)</label>
                <input type="number" id="inp_peso_cavidad" value="" step="0.01" oninput="calcular()">
                <span class="inp-unit">Del molde seleccionado</span>
            </div>
            <div class="inp-group">
                <label>Gates por cavidad</label>
                <input type="number" id="inp_gates_cavidad" value="" step="1" oninput="calcular()">
                <span class="inp-unit">Del molde seleccionado</span>
            </div>
            <div class="inp-group">
                <label>Cantidad de coladas</label>
                <input type="number" id="inp_cant_coladas" value="" step="1" oninput="calcular()">
                <span class="inp-unit">Del molde seleccionado</span>
            </div>
            <div class="inp-group">
                <label>Peso por colada (grs)</label>
                <input type="number" id="inp_peso_colada" value="" step="0.01" oninput="calcular()">
                <span class="inp-unit">Del molde seleccionado</span>
            </div>
            <div class="inp-group">
                <label>Diámetro del bebedero (mm)</label>
                <input type="number" id="inp_diam_bebedero" value="6" step="0.1" oninput="calcular()">
            </div>
            <div class="inp-group">
                <label>Espesor promedio pieza (mm)</label>
                <input type="number" id="inp_espesor" value="" step="0.01" oninput="calcular()">
                <span class="inp-unit">De la pieza seleccionada</span>
            </div>
            <div class="inp-group">
                <label>Tipo de resina</label>
                <input type="text" id="inp_tipo_resina" value="" oninput="calcular()">
                <span class="inp-unit">De la resina seleccionada</span>
            </div>
            <div class="inp-group">
                <label>Densidad en caliente (g/cm³)</label>
                <input type="number" id="inp_densidad" value="" step="0.01" oninput="calcular()">
                <span class="inp-unit">De la resina seleccionada</span>
            </div>
            <div class="inp-group">
                <label>Tiempo de sostenimiento (s)</label>
                <input type="number" id="inp_tpo_sostenimiento" value="2" step="0.1" oninput="calcular()" style="border-color:#0891b2; background:#e0f2fe;">
                <span class="inp-unit">⬅ Ajustar según proceso</span>
            </div>
            <div class="inp-group">
                <label>Tiempo de enfriamiento (s)</label>
                <input type="number" id="inp_tpo_enfriamiento" value="6" step="0.1" oninput="calcular()">
                <span class="inp-unit">Calculado o ajustado</span>
            </div>
            <div class="inp-group">
                <label>Velocidad de inyección (mm/s)</label>
                <input type="number" id="inp_vel_iny" value="300" step="1" oninput="calcular()">
                <span class="inp-unit">Valor a programar en máquina</span>
            </div>
            <div class="inp-group">
                <label>Carga máx. máquina (cm³)</label>
                <input type="number" id="inp_carga_max" value="" step="0.1" oninput="calcular()">
                <span class="inp-unit">De la máquina seleccionada</span>
            </div>
            <div class="inp-group">
                <label>Diámetro del husillo (mm)</label>
                <input type="number" id="inp_diam_husillo" value="" step="0.1" oninput="calcular()">
                <span class="inp-unit">De la máquina seleccionada</span>
            </div>
        </div>
    </div>

    <!-- ── RESULTADOS: DISPARO Y HUSILLO ────────────────── -->
    <div class="calc-section" id="sec-disparo">
        <h3>⚙️ Disparo y husillo</h3>
        <div class="calc-grid">
            <div class="calc-item">
                <label>Peso total disparo</label>
                <div class="val" id="res_peso_disparo">—</div>
                <div class="unit">grs.</div>
            </div>
            <div class="calc-item">
                <label>Volumen disparo</label>
                <div class="val" id="res_vol_disparo">—</div>
                <div class="unit">cm³</div>
            </div>
            <div class="calc-item">
                <label>Volumen inyección (95%)</label>
                <div class="val" id="res_vol_iny_95">—</div>
                <div class="unit">cm³</div>
            </div>
            <div class="calc-item">
                <label>Recorrido husillo</label>
                <div class="val highlight" id="res_recorrido">—</div>
                <div class="unit">mm</div>
            </div>
            <div class="calc-item">
                <label>Husillo ideal (diám.)</label>
                <div class="val" id="res_husillo_ideal">—</div>
                <div class="unit">mm</div>
            </div>
            <div class="calc-item">
                <label>Precisión del husillo</label>
                <div class="val" id="res_precision">—</div>
                <div class="unit">ratio</div>
            </div>
            <div class="calc-item">
                <label>% en piezas</label>
                <div class="val" id="res_pct_piezas">—</div>
                <div class="unit">%</div>
            </div>
            <div class="calc-item">
                <label>% en coladas</label>
                <div class="val" id="res_pct_coladas">—</div>
                <div class="unit">%</div>
            </div>
            <div class="calc-item">
                <label>Tipo de canal</label>
                <div class="val highlight" id="res_tipo_canal">—</div>
            </div>
        </div>
    </div>

    <!-- ── ÁREA Y GEOMETRÍA ─────────────────────────────── -->
    <div class="calc-section" id="sec-geometria">
        <h3>📐 Área y geometría del gate</h3>
        <div class="calc-grid">
            <div class="calc-item">
                <label>Total de gates</label>
                <div class="val" id="res_total_gates">—</div>
            </div>
            <div class="calc-item">
                <label>Volumen por gate</label>
                <div class="val" id="res_vol_gate">—</div>
                <div class="unit">cm³</div>
            </div>
            <div class="calc-item">
                <label>Área del disco</label>
                <div class="val" id="res_area_disco">—</div>
                <div class="unit">cm²</div>
            </div>
            <div class="calc-item">
                <label>Diámetro del disco</label>
                <div class="val highlight" id="res_diam_disco">—</div>
                <div class="unit">cm</div>
            </div>
            <div class="calc-item">
                <label>Relación de espesor</label>
                <div class="val" id="res_rel_espesor">—</div>
                <div class="unit">veces</div>
            </div>
            <div class="calc-item">
                <label>Área proyectada</label>
                <div class="val" id="res_area_proy">—</div>
                <div class="unit">cm²</div>
            </div>
        </div>
    </div>

    <!-- ── TIEMPOS Y CICLO ──────────────────────────────── -->
    <div class="calc-section" id="sec-tiempos">
        <h3>⏱️ Tiempos y ciclo</h3>
        <div class="calc-grid">
            <div class="calc-item">
                <label>Tiempo de llenado (iny)</label>
                <div class="val highlight" id="res_tpo_llenado">—</div>
                <div class="unit">s</div>
            </div>
            <div class="calc-item">
                <label>T. llenado coladas</label>
                <div class="val" id="res_tpo_llenado_col">—</div>
                <div class="unit">s</div>
            </div>
            <div class="calc-item">
                <label>T. llenado piezas</label>
                <div class="val" id="res_tpo_llenado_pzs">—</div>
                <div class="unit">s</div>
            </div>
            <div class="calc-item">
                <label>Refrigeración total</label>
                <div class="val" id="res_refrig_total">—</div>
                <div class="unit">s</div>
            </div>
            <div class="calc-item">
                <label>Piezas por hora</label>
                <div class="val highlight" id="res_pzas_hora">—</div>
            </div>
            <div class="calc-item">
                <label>Vel. promedio inyección</label>
                <div class="val" id="res_vel_prom">—</div>
                <div class="unit">mm/s</div>
            </div>
            <div class="calc-item">
                <label>Tiempo más corto posible</label>
                <div class="val" id="res_tpo_corto">—</div>
                <div class="unit">s</div>
            </div>
        </div>
    </div>

    <!-- ── CAUDALES ─────────────────────────────────────── -->
    <div class="calc-section" id="sec-caudal">
        <h3>🌊 Caudales de llenado</h3>
        <div class="calc-grid">
            <div class="calc-item">
                <label>Caudal total</label>
                <div class="val" id="res_caudal_total">—</div>
                <div class="unit">cm³/s</div>
            </div>
            <div class="calc-item">
                <label>Caudal en coladas</label>
                <div class="val" id="res_caudal_coladas">—</div>
                <div class="unit">cm³/s</div>
            </div>
            <div class="calc-item">
                <label>Caudal en piezas</label>
                <div class="val" id="res_caudal_pzas">—</div>
                <div class="unit">cm³/s</div>
            </div>
            <div class="calc-item">
                <label>Caudal por gate</label>
                <div class="val highlight" id="res_caudal_gate">—</div>
                <div class="unit">cm³/s</div>
            </div>
        </div>
    </div>

    <!-- ── DATOS A PROGRAMAR ────────────────────────────── -->
    <div class="calc-section" id="sec-programa">
        <h3>🖥️ PROGRAMAR EN LA MÁQUINA</h3>
        <table class="prog-table">
            <thead>
                <tr>
                    <th>PARÁMETRO</th>
                    <th>INYECCIÓN</th>
                    <th>SOSTENIMIENTO</th>
                    <th>CARGA</th>
                    <th>CONMUTACIÓN</th>
                    <th>UNIDAD</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="lbl">Velocidad</td>
                    <td id="prg_vel_iny">—</td>
                    <td id="prg_vel_sos">0.00001</td>
                    <td id="prg_vel_car">—</td>
                    <td>—</td>
                    <td>mm/sg</td>
                </tr>
                <tr>
                    <td class="lbl">Presión / Límite</td>
                    <td id="prg_pres_iny">—</td>
                    <td id="prg_pres_sos">0.000001</td>
                    <td id="prg_pres_car">—</td>
                    <td>—</td>
                    <td>bares</td>
                </tr>
                <tr>
                    <td class="lbl">Tiempo</td>
                    <td id="prg_tpo_iny">—</td>
                    <td id="prg_tpo_sos">—</td>
                    <td>—</td>
                    <td>—</td>
                    <td>s</td>
                </tr>
                <tr>
                    <td class="lbl">Posición (recorrido)</td>
                    <td id="prg_pos_iny">—</td>
                    <td>—</td>
                    <td>—</td>
                    <td id="prg_pos_con">—</td>
                    <td>mm</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── ACCIONES ─────────────────────────────────────── -->
    <div class="acciones">
        <button class="btn btn-pdf" onclick="window.print()">📥 Imprimir / PDF</button>
        <button class="btn btn-excel" onclick="exportarExcel()">📥 Exportar Excel</button>
        <button class="btn btn-guardar" onclick="guardarCalificador()">💾 Guardar resultado</button>
    </div>

</main>

<footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>

<script>
// ── Catálogos desde PHP ──────────────────────────────────────────────
const MAQUINAS = <?= $maquinasJson ?>;
const MOLDES   = <?= $moldesJson ?>;
const RESINAS  = <?= $resinasJson ?>;
const PIEZAS   = <?= $piezasJson ?>;

// ── Helpers ──────────────────────────────────────────────────────────
const $ = id => document.getElementById(id);
const num = id => parseFloat($(id)?.value) || 0;
const fmt = (v, dec=3) => (v === null || isNaN(v) || !isFinite(v)) ? '—' : v.toFixed(dec);

function setVal(id, v, dec=3) {
    const el = $(id);
    if (!el) return;
    el.textContent = fmt(v, dec);
    el.className = 'val';
}
function setValColor(id, v, dec, css) {
    setVal(id, v, dec);
    if ($(id)) $(id).classList.add(css);
}
function setInput(id, v) {
    const el = $(id);
    if (el && (el.value === '' || el.dataset.auto === '1')) {
        el.value = v !== null && v !== undefined ? v : '';
        el.dataset.auto = '1';
    }
}

// ── Carga desde selectores ────────────────────────────────────────────
function cargarMaquina() {
    const id = $('sel_maquina').value;
    if (!id) return actualizarResumen();
    const m = MAQUINAS[id];
    if (!m) return;
    setInput('inp_carga_max',   m.ma_carga_max);
    setInput('inp_diam_husillo',m.ma_diam_husillo);
    actualizarResumen();
    calcular();
}

function cargarMolde() {
    const id = $('sel_molde').value;
    if (!id) return actualizarResumen();
    const mo = MOLDES[id];
    if (!mo) return;
    setInput('inp_cavidades_activas', mo.mo_no_cavidades);
    setInput('inp_peso_cavidad',      mo.mo_peso_pieza);
    setInput('inp_gates_cavidad',     mo.mo_puert_cavidad);
    setInput('inp_cant_coladas',      mo.mo_no_coladas);
    setInput('inp_peso_colada',       mo.mo_peso_colada);
    actualizarResumen();
    calcular();
}

function cargarResina() {
    const id = $('sel_resina').value;
    if (!id) return actualizarResumen();
    const r = RESINAS[id];
    if (!r) return;
    // factor_correccion = densidad en caliente aproximada
    setInput('inp_densidad',    r.re_factor_correccion || r.re_densidad);
    setInput('inp_tipo_resina', (r.re_tipo_resina || '') + (r.re_grado ? ' ' + r.re_grado : ''));
    actualizarResumen();
    calcular();
}

function cargarPieza() {
    const id = $('sel_pieza').value;
    if (!id) return actualizarResumen();
    const p = PIEZAS[id];
    if (!p) return;
    setInput('inp_espesor', p.pi_espesor);
    actualizarResumen();
    calcular();
}

function actualizarResumen() {
    const partes = [];
    const maqId = $('sel_maquina').value;
    const moId  = $('sel_molde').value;
    const reId  = $('sel_resina').value;
    const piId  = $('sel_pieza').value;

    if (maqId && MAQUINAS[maqId]) {
        const m = MAQUINAS[maqId];
        partes.push(`🏭 <span><b>Máquina:</b> ${(m.ma_no ? m.ma_no+' — ' : '')}${m.ma_marca} ${m.ma_modelo}</span>`);
    }
    if (moId && MOLDES[moId]) {
        const mo = MOLDES[moId];
        partes.push(`📦 <span><b>Molde:</b> ${mo.mo_numero || 'S/N'}${mo.mo_no_pieza ? ' — '+mo.mo_no_pieza : ''}</span>`);
    }
    if (reId && RESINAS[reId]) {
        const r = RESINAS[reId];
        partes.push(`💧 <span><b>Resina:</b> ${r.re_tipo_resina || ''} ${r.re_grado || ''}</span>`);
    }
    if (piId && PIEZAS[piId]) {
        const p = PIEZAS[piId];
        partes.push(`🧩 <span><b>Pieza:</b> ${p.pi_cod_prod || ''}${p.pi_descripcion ? ' — '+p.pi_descripcion : ''}</span>`);
    }

    const el = $('resumen');
    if (partes.length) {
        el.innerHTML = partes.join('  ');
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

// ── Motor de cálculo (fórmulas del Excel "2) E y C") ─────────────────
function calcular() {
    // ── Inputs manuales (celdas amarillas) ──
    const cojin              = num('inp_cojin');              // D7
    const cavidades_activas  = num('inp_cavidades_activas');  // D9
    const peso_cavidad       = num('inp_peso_cavidad');       // D10
    const gates_cavidad      = num('inp_gates_cavidad');      // D11
    const cant_coladas       = num('inp_cant_coladas');       // D13
    const peso_colada        = num('inp_peso_colada');        // D14
    const diam_bebedero      = num('inp_diam_bebedero');      // D17
    const espesor            = num('inp_espesor');            // D18
    const densidad_cal       = num('inp_densidad');           // D42 (densidad en caliente)
    const vel_iny            = num('inp_vel_iny');            // D52
    const tpo_sostenimiento  = num('inp_tpo_sostenimiento'); // C43 (azul)
    const tpo_enfriamiento   = num('inp_tpo_enfriamiento'); // C44 (b2)
    const carga_max          = num('inp_carga_max');          // C23 (máquina)
    const diam_husillo       = num('inp_diam_husillo');       // E23 (máquina)

    // ── Cálculos intermedios ────────────────────────────────────────
    // I21: Peso total disparo = cavidades_activas * peso_cavidad + cant_coladas * peso_colada
    const peso_disparo = cavidades_activas * peso_cavidad + cant_coladas * peso_colada; // =D9*D10+D13*D14

    // D46: densidad * refrigeración_total (se usa en vol disparo)
    const refrig_total = tpo_sostenimiento + tpo_enfriamiento; // C45 = C43+C44
    const D46 = densidad_cal * refrig_total;                   // =D42*B45

    // I7: Volumen disparo = peso_disparo / D46
    const vol_disparo = D46 !== 0 ? peso_disparo / D46 : null;   // =I21/D46/1

    // I8: Volumen inyección (95%) = 0.95 * peso_disparo / D46
    const vol_iny_95 = D46 !== 0 ? 0.95 * peso_disparo / D46 : null; // =0.95*I21/D46

    // Área del husillo: E24 = diam_husillo^2 * 0.7854 / 100
    const area_husillo = diam_husillo * diam_husillo * 0.7854 / 100; // =E23*E23*0.7854/100

    // I59: area del gate = D55^2 * 0.007854 (D55 = diam_bebedero en este contexto)
    const D55 = diam_bebedero;
    const area_gate = D55 * D55 * 0.007854; // =D55*D55*0.007854

    // I9: Recorrido disparo = vol_disparo * 10 / area_gate
    const recorrido_disp = (vol_disparo !== null && area_gate !== 0)
        ? vol_disparo * 10 / area_gate : null; // =I7*10/I59

    // I10: Recorrido inyección = vol_iny_95 * 10 / area_gate
    const recorrido_iny = (vol_iny_95 !== null && area_gate !== 0)
        ? vol_iny_95 * 10 / area_gate : null; // =I8*10/I59

    // D12: Total gates = gates_cavidad * cavidades_activas
    const total_gates = gates_cavidad * cavidades_activas; // =D11*D9

    // I11: Volumen por gate = peso_cavidad / gates_cavidad / D46 * 0.95
    const vol_gate = (gates_cavidad !== 0 && D46 !== 0)
        ? peso_cavidad / gates_cavidad / D46 * 0.95 : null; // =D10/D11/D46*0.95

    // I12: Área del disco = vol_gate / (espesor / 10)
    const area_disco = (vol_gate !== null && espesor !== 0)
        ? vol_gate / (espesor / 10) : null; // =I11/(D18/10)

    // I13: Diámetro = sqrt(area_disco / 0.7854)
    const diam_disco = area_disco !== null
        ? Math.sqrt(area_disco / 0.7854) : null; // =SQRT(I12/0.7854)

    // I14: Relación de espesor = diam_disco * 5 / espesor
    const rel_espesor = (diam_disco !== null && espesor !== 0)
        ? diam_disco * 5 / espesor : null; // =I13*5/D18

    // E15: tipo_canal_flag = cant_coladas * peso_colada
    const E15 = cant_coladas * peso_colada; // =D13*D14
    const tipo_canal = E15 === 0 ? 'C. Caliente' : (E15 > 0 ? 'Col. FRÍA' : '¡ ERROR !');

    // D16: Área proyectada = 7*7*cavidades_activas  (fórmula del Excel con 7*7 como placeholder)
    const area_proy = 7 * 7 * cavidades_activas; // =7*7*D9 (simplificado — pieza aporta dato real)

    // C28: Volumen X cavidad = peso_cavidad / densidad (C24 en Indicadores = densidad ~0.73)
    // En EyC no hay densidad directa — usamos densidad_cal como aproximación
    const dens_base = densidad_cal || 1;
    const vol_cavidad  = dens_base !== 0 ? peso_cavidad / dens_base : null;
    const vol_coladas  = dens_base !== 0 ? (cant_coladas * peso_colada) / dens_base : null;

    // C29: peso todas piezas = peso_cavidad * cavidades_activas
    const peso_piezas  = peso_cavidad * cavidades_activas; // =C25*C26
    // E28: peso todas coladas = peso_colada * cant_coladas
    const peso_coladas = peso_colada * cant_coladas; // =E25*E26
    // C32: peso total disparo = peso_piezas + peso_coladas
    const peso_total   = peso_piezas + peso_coladas; // =C29+E28

    // C30: % piezas / C30: % coladas
    const pct_piezas  = peso_total !== 0 ? peso_piezas  / peso_total : null; // =C29/C32
    const pct_coladas = peso_total !== 0 ? peso_coladas / peso_total : null; // =E28/C32

    // C33: Recorrido husillo = 10 * vol_total / area_husillo
    const vol_total_molde = dens_base !== 0 ? peso_total / dens_base : null; // E32=C32/C24
    const recorrido_husillo = (vol_total_molde !== null && area_husillo !== 0)
        ? 10 * vol_total_molde / area_husillo : null; // =10*E32/E24

    // E33: Husillo ideal
    const husillo_ideal = vol_total_molde !== null
        ? 10 * Math.pow(vol_total_molde / 2 / 0.7854, 0.33333) : null; // =10*(E32/2/0.7854)^0.33333

    // E34: Precisión = recorrido_husillo / diam_husillo
    const precision = (recorrido_husillo !== null && diam_husillo !== 0)
        ? recorrido_husillo / diam_husillo : null; // =C33/E23

    // ── Tiempos ─────────────────────────────────────────────────────
    // C42 (densidad como tiempo de llenado en Indicadores C42=1.29 → tiempo)
    // En EyC, E37=C42 (Indicadores C42 = tiempo de llenado del ciclo real)
    // Para este módulo usamos: tiempo_llenado = recorrido_husillo / vel_iny
    const tpo_llenado = (recorrido_husillo !== null && vel_iny !== 0)
        ? recorrido_husillo / vel_iny : null;

    // C38: T. llenado coladas = tpo_llenado * pct_coladas
    const tpo_llenado_col = (tpo_llenado !== null && pct_coladas !== null)
        ? tpo_llenado * pct_coladas : null; // =E37*E30
    // E38: T. llenado piezas = tpo_llenado * pct_piezas
    const tpo_llenado_pzs = (tpo_llenado !== null && pct_piezas !== null)
        ? tpo_llenado * pct_piezas : null; // =E37*C30

    // C41 (ciclo total en Indicadores) — no disponible aquí directamente
    // Usamos: ciclo = llenado + sostenimiento + enfriamiento + movimientos_prensa
    // E36: tiempo más corto = (cojin - vel_iny_max_refrig) / vel_max ...
    // Simplificamos como el Excel indica en C36 (vel max inyección de máquina)
    const vel_max_maquina = num('inp_vel_iny'); // aproximación
    const tpo_corto = vel_max_maquina !== 0
        ? recorrido_husillo !== null ? recorrido_husillo / vel_max_maquina : null
        : null; // =(B6-C6)/C36 → simplificado

    // C47: piezas por hora (Indicadores usa ciclo C41; aproximamos)
    // ciclo_aprox = llenado + sostenimiento + enfriamiento + 3s prensa
    const ciclo_aprox = (tpo_llenado || 0) + tpo_sostenimiento + tpo_enfriamiento + 3;
    const pzas_hora = ciclo_aprox > 0
        ? 3600 * cavidades_activas / ciclo_aprox : null; // =3600*C26/C41

    // Vel promedio
    const vel_prom = (recorrido_husillo !== null && tpo_llenado !== null && tpo_llenado !== 0)
        ? recorrido_husillo / tpo_llenado : null; // =C33/E37

    // ── Caudales ────────────────────────────────────────────────────
    // E48: Caudal total = vol_total / tpo_llenado
    const caudal_total = (vol_total_molde !== null && tpo_llenado !== null && tpo_llenado !== 0)
        ? vol_total_molde / tpo_llenado : null; // =E32/E37

    // E49: Caudal coladas = vol_coladas / tpo_llenado_col
    const caudal_coladas = (vol_coladas !== null && tpo_llenado_col !== null && tpo_llenado_col !== 0)
        ? vol_coladas / tpo_llenado_col : null; // =E29/C38

    // E50: Caudal piezas
    const vol_piezas = dens_base !== 0 ? peso_piezas / dens_base : null;
    const caudal_pzas = (vol_piezas !== null && tpo_llenado_pzs !== null && tpo_llenado_pzs !== 0)
        ? vol_piezas / tpo_llenado_pzs : null; // =C31/E38

    // E51: Caudal por gate
    const caudal_gate = (caudal_pzas !== null && gates_cavidad !== 0)
        ? caudal_pzas / gates_cavidad : null; // =E50/C27

    // ── Velocidad de inyección calculada ────────────────────────────
    const vel_iny_80 = vel_iny * 0.8; // I22 = D52*0.8

    // ── Mostrar resultados ──────────────────────────────────────────
    setVal('res_peso_disparo',   peso_disparo, 2);
    setVal('res_vol_disparo',    vol_disparo,  3);
    setVal('res_vol_iny_95',     vol_iny_95,   3);
    setVal('res_recorrido',      recorrido_husillo, 2);
    setVal('res_husillo_ideal',  husillo_ideal, 2);
    setVal('res_precision',      precision,    4);
    setVal('res_pct_piezas',     pct_piezas !== null ? pct_piezas * 100 : null, 1);
    setVal('res_pct_coladas',    pct_coladas !== null ? pct_coladas * 100 : null, 1);

    const tc = $('res_tipo_canal');
    if (tc) { tc.textContent = tipo_canal; tc.className = 'val highlight'; }

    setVal('res_total_gates',    total_gates,  0);
    setVal('res_vol_gate',       vol_gate,     4);
    setVal('res_area_disco',     area_disco,   3);
    setVal('res_diam_disco',     diam_disco,   3);
    setVal('res_rel_espesor',    rel_espesor,  2);
    setVal('res_area_proy',      area_proy,    1);

    setVal('res_tpo_llenado',    tpo_llenado,  3);
    setVal('res_tpo_llenado_col',tpo_llenado_col, 4);
    setVal('res_tpo_llenado_pzs',tpo_llenado_pzs, 4);
    setVal('res_refrig_total',   refrig_total, 1);
    setVal('res_pzas_hora',      pzas_hora,    1);
    setVal('res_vel_prom',       vel_prom,     2);
    setVal('res_tpo_corto',      tpo_corto,    3);

    setVal('res_caudal_total',   caudal_total,   3);
    setVal('res_caudal_coladas', caudal_coladas, 3);
    setVal('res_caudal_pzas',    caudal_pzas,    3);
    setVal('res_caudal_gate',    caudal_gate,    4);

    // ── Tabla de programación ───────────────────────────────────────
    $('prg_vel_iny').textContent  = vel_iny_80 ? vel_iny_80.toFixed(1) : '—';
    $('prg_tpo_iny').textContent  = tpo_llenado ? tpo_llenado.toFixed(2) : '—';
    $('prg_pos_iny').textContent  = recorrido_husillo ? recorrido_husillo.toFixed(2) : '—';
    $('prg_tpo_sos').textContent  = tpo_sostenimiento.toFixed(1);
    $('prg_pos_con').textContent  = cojin ? cojin.toFixed(1) : '—';

    // Colorear estado del husillo
    if (precision !== null) {
        const pEl = $('res_precision');
        pEl.classList.remove('ok','warn','bad');
        if (precision >= 0.8 && precision <= 1.2) pEl.classList.add('ok');
        else if (precision < 0.8 || precision > 1.5) pEl.classList.add('bad');
        else pEl.classList.add('warn');
    }
}

function guardarCalificador() {
    alert('Funcionalidad de guardado en base de datos — disponible en próxima versión.');
}

function exportarExcel() {
    alert('Exportación a Excel — disponible en próxima versión.');
}

// Calcular al cargar si hay datos en campos
window.addEventListener('DOMContentLoaded', calcular);
</script>
</body>
</html>
