<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

$modo = in_array($_GET['modo'] ?? '', ['nuevo','corregir','ver']) ? $_GET['modo'] : 'nuevo';
$modoLabels = ['nuevo'=>'Nuevo Proceso','corregir'=>'Corregir Proceso','ver'=>'Ver Proceso Anterior'];
$modoLabel  = $modoLabels[$modo];

// Máquinas
$sqlM = "SELECT m.ma_id, m.ma_no, m.ma_marca, m.ma_modelo, m.ma_ubicacion, e.em_nombre
         FROM maquinas m LEFT JOIN empresas e ON m.ma_empresa = e.em_id
         WHERE m.ma_activo = 1";
$paramsM = [];
if ($rol !== 1) {
    $sqlM .= " AND m.ma_empresa = :empresa";
    $paramsM[':empresa'] = $empresaId;
    if ($plantaId) { $sqlM .= " AND m.ma_planta = :planta"; $paramsM[':planta'] = $plantaId; }
}
$sqlM .= " ORDER BY m.ma_marca, m.ma_modelo";
$stmtM = $conn->prepare($sqlM); $stmtM->execute($paramsM);
$maquinas = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// Moldes
$sqlMo = "SELECT mo_id, mo_numero, mo_no_pieza, mo_no_cavidades FROM moldes WHERE mo_activo = 1";
$paramsMo = [];
if ($rol !== 1) { $sqlMo .= " AND mo_empresa = :empresa"; $paramsMo[':empresa'] = $empresaId; }
$sqlMo .= " ORDER BY mo_numero";
$stmtMo = $conn->prepare($sqlMo); $stmtMo->execute($paramsMo);
$moldes = $stmtMo->fetchAll(PDO::FETCH_ASSOC);

// Resinas
$sqlR = "SELECT re_id, re_cod_int, re_tipo_resina, re_grado, re_sec_temp, re_sec_tiempo,
                re_temp_masa_max, re_temp_masa_min, re_densidad, re_factor_correccion
         FROM resinas WHERE re_activo = 1";
$paramsR = [];
if ($rol !== 1) { $sqlR .= " AND re_empresa = :empresa"; $paramsR[':empresa'] = $empresaId; }
$sqlR .= " ORDER BY re_tipo_resina, re_grado";
$stmtR = $conn->prepare($sqlR); $stmtR->execute($paramsR);
$resinas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

// Piezas
$sqlP = "SELECT pi_id, pi_cod_prod, pi_descripcion, pi_molde, pi_resina, pi_espesor, pi_area_proy, pi_color
         FROM piezas WHERE pi_activo = 1";
$paramsP = [];
if ($rol !== 1) { $sqlP .= " AND pi_empresa = :empresa"; $paramsP[':empresa'] = $empresaId; }
$sqlP .= " ORDER BY pi_cod_prod";
$stmtP = $conn->prepare($sqlP); $stmtP->execute($paramsP);
$piezas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

$menu_retorno = '/ingenieria/procesos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modoLabel ?></title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .seleccion-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px; }
        @media(max-width:700px){ .seleccion-grid { grid-template-columns: 1fr; } }
        .sel-card { background:#fff; border-radius:8px; padding:18px; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
        .sel-card h4 { margin:0 0 10px 0; color:#0056b3; font-size:0.95em; display:flex; align-items:center; gap:8px; }
        .sel-card select { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:4px; font-size:0.9em; }
        .sel-card .add-link { font-size:0.8em; color:#0056b3; text-decoration:none; margin-top:6px; display:inline-block; }
        .sel-card .add-link:hover { text-decoration:underline; }
        .sel-obligatorio h4::after { content:" *"; color:#c0392b; }
        .info-seleccion { background:#f0f7ff; border:1px solid #bfdbfe; border-radius:6px; padding:12px 16px; margin-top:16px; font-size:0.85em; color:#1e3a8a; display:none; }
        .btn-continuar { margin-top:24px; width:100%; padding:14px; font-size:1.05em; }
        .modo-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.82em; font-weight:700;
            background:#dbeafe; color:#1d4ed8; margin-bottom:16px; }
        .modo-badge.corregir { background:#fef3c7; color:#92400e; }
        .modo-badge.ver { background:#ede9fe; color:#5b21b6; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_retorno ?>">
            <img src="/imagenes/logo.png" alt="Logo" class="header-logo">
        </a>
        <h1><?= $modoLabel ?></h1>
    </div>
    <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
</header>

<main class="main-container">

    <div class="form-section">
        <span class="modo-badge <?= $modo ?>"><?= $modoLabel ?></span>

        <div class="seleccion-grid">

            <!-- MÁQUINA (obligatorio) -->
            <div class="sel-card sel-obligatorio">
                <h4>🏭 Máquina</h4>
                <select id="sel_maquina" onchange="actualizarSeleccion()">
                    <option value="">-- Seleccionar máquina --</option>
                    <?php foreach ($maquinas as $m): ?>
                    <option value="<?= $m['ma_id'] ?>"
                        data-no="<?= htmlspecialchars($m['ma_no'] ?? '') ?>"
                        data-marca="<?= htmlspecialchars($m['ma_marca'] ?? '') ?>"
                        data-modelo="<?= htmlspecialchars($m['ma_modelo'] ?? '') ?>"
                        data-ubicacion="<?= htmlspecialchars($m['ma_ubicacion'] ?? '') ?>">
                        <?= htmlspecialchars(
                            ($m['ma_no'] ? $m['ma_no'].' — ' : '') .
                            $m['ma_marca'].' '.$m['ma_modelo'] .
                            ($m['ma_ubicacion'] ? ' ('.$m['ma_ubicacion'].')' : '')
                        ) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-maquina.php" class="add-link">+ Registrar nueva máquina</a>
            </div>

            <!-- MOLDE -->
            <div class="sel-card">
                <h4>📦 Molde</h4>
                <select id="sel_molde" onchange="actualizarSeleccion()">
                    <option value="">-- Sin seleccionar --</option>
                    <?php foreach ($moldes as $mo): ?>
                    <option value="<?= $mo['mo_id'] ?>"
                        data-numero="<?= htmlspecialchars($mo['mo_numero'] ?? '') ?>"
                        data-pieza="<?= htmlspecialchars($mo['mo_no_pieza'] ?? '') ?>"
                        data-cavidades="<?= htmlspecialchars($mo['mo_no_cavidades'] ?? '') ?>">
                        <?= htmlspecialchars(($mo['mo_numero'] ?? 'Sin número').($mo['mo_no_pieza'] ? ' — '.$mo['mo_no_pieza'] : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-molde.php" class="add-link">+ Registrar nuevo molde</a>
            </div>

            <!-- RESINA -->
            <div class="sel-card">
                <h4>💧 Resina</h4>
                <select id="sel_resina" onchange="actualizarSeleccion()">
                    <option value="">-- Sin seleccionar --</option>
                    <?php foreach ($resinas as $r): ?>
                    <option value="<?= $r['re_id'] ?>"
                        data-tipo="<?= htmlspecialchars($r['re_tipo_resina'] ?? '') ?>"
                        data-grado="<?= htmlspecialchars($r['re_grado'] ?? '') ?>"
                        data-sec_temp="<?= htmlspecialchars($r['re_sec_temp'] ?? '') ?>"
                        data-sec_tiempo="<?= htmlspecialchars($r['re_sec_tiempo'] ?? '') ?>"
                        data-temp_max="<?= htmlspecialchars($r['re_temp_masa_max'] ?? '') ?>"
                        data-temp_min="<?= htmlspecialchars($r['re_temp_masa_min'] ?? '') ?>"
                        data-densidad="<?= htmlspecialchars($r['re_densidad'] ?? '') ?>"
                        data-factor="<?= htmlspecialchars($r['re_factor_correccion'] ?? '') ?>">
                        <?= htmlspecialchars(($r['re_tipo_resina'] ?? '').($r['re_grado'] ? ' '.$r['re_grado'] : '').($r['re_cod_int'] ? ' ['.$r['re_cod_int'].']' : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-resina.php" class="add-link">+ Registrar nueva resina</a>
            </div>

            <!-- PIEZA -->
            <div class="sel-card">
                <h4>🧩 Pieza</h4>
                <select id="sel_pieza" onchange="actualizarSeleccion()">
                    <option value="">-- Sin seleccionar --</option>
                    <?php foreach ($piezas as $p): ?>
                    <option value="<?= $p['pi_id'] ?>"
                        data-cod="<?= htmlspecialchars($p['pi_cod_prod'] ?? '') ?>"
                        data-desc="<?= htmlspecialchars($p['pi_descripcion'] ?? '') ?>"
                        data-color="<?= htmlspecialchars($p['pi_color'] ?? '') ?>"
                        data-espesor="<?= htmlspecialchars($p['pi_espesor'] ?? '') ?>"
                        data-area="<?= htmlspecialchars($p['pi_area_proy'] ?? '') ?>">
                        <?= htmlspecialchars(($p['pi_cod_prod'] ?? '').($p['pi_descripcion'] ? ' — '.$p['pi_descripcion'] : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="/forms/form-pieza.php" class="add-link">+ Registrar nueva pieza</a>
            </div>

        </div>

        <!-- Resumen de selección -->
        <div class="info-seleccion" id="infoSeleccion"></div>

        <!-- Botón continuar -->
        <button class="btn btn-guardar btn-continuar" id="btnContinuar" disabled onclick="continuar()">
            ⚙️ Continuar a Hoja de Proceso
        </button>

    </div>
</main>

<footer><p>Método ACG</p></footer>

<?php includeSidebar(); ?>

<script>
const MODO = '<?= $modo ?>';

function actualizarSeleccion() {
    const maq   = document.getElementById('sel_maquina');
    const molde = document.getElementById('sel_molde');
    const resina= document.getElementById('sel_resina');
    const pieza = document.getElementById('sel_pieza');
    const info  = document.getElementById('infoSeleccion');
    const btn   = document.getElementById('btnContinuar');

    const maqId = maq.value;
    btn.disabled = !maqId;

    if (!maqId) { info.style.display = 'none'; return; }

    const maqOpt = maq.selectedOptions[0];
    let html = '<strong>Selección actual:</strong><br>';
    html += `🏭 <b>Máquina:</b> ${maqOpt.dataset.no ? maqOpt.dataset.no+' — ' : ''}${maqOpt.dataset.marca} ${maqOpt.dataset.modelo}`;

    if (molde.value) {
        const mo = molde.selectedOptions[0];
        html += `<br>📦 <b>Molde:</b> ${mo.dataset.numero}${mo.dataset.pieza ? ' ('+mo.dataset.pieza+')' : ''}${mo.dataset.cavidades ? ' — '+mo.dataset.cavidades+' cavidades' : ''}`;
    }
    if (resina.value) {
        const r = resina.selectedOptions[0];
        html += `<br>💧 <b>Resina:</b> ${r.dataset.tipo} ${r.dataset.grado}`;
        if (r.dataset.sec_temp) html += ` | Secado: ${r.dataset.sec_temp}°C / ${r.dataset.sec_tiempo}h`;
    }
    if (pieza.value) {
        const p = pieza.selectedOptions[0];
        html += `<br>🧩 <b>Pieza:</b> ${p.dataset.cod}${p.dataset.desc ? ' — '+p.dataset.desc : ''}`;
    }

    info.innerHTML = html;
    info.style.display = 'block';
}

function continuar() {
    const maqId   = document.getElementById('sel_maquina').value;
    const moldeId = document.getElementById('sel_molde').value;
    const resinaId= document.getElementById('sel_resina').value;
    const piezaId = document.getElementById('sel_pieza').value;

    // Pasar datos al catálogo como datos de sesión via POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/reportes/hoja-proceso.php';

    const campos = {
        ma_id: maqId,
        mo_id: moldeId,
        re_id: resinaId,
        pi_id: piezaId,
        modo: MODO,
        // Datos de resina para pre-rellenar
        re_sec_temp:  document.getElementById('sel_resina').selectedOptions[0]?.dataset.sec_temp || '',
        re_sec_tiempo:document.getElementById('sel_resina').selectedOptions[0]?.dataset.sec_tiempo || '',
        re_tipo:      document.getElementById('sel_resina').selectedOptions[0]?.dataset.tipo || '',
        re_grado:     document.getElementById('sel_resina').selectedOptions[0]?.dataset.grado || '',
        re_temp_max:  document.getElementById('sel_resina').selectedOptions[0]?.dataset.temp_max || '',
        re_temp_min:  document.getElementById('sel_resina').selectedOptions[0]?.dataset.temp_min || '',
        // Datos de molde
        mo_numero:    document.getElementById('sel_molde').selectedOptions[0]?.dataset.numero || '',
        mo_cavidades: document.getElementById('sel_molde').selectedOptions[0]?.dataset.cavidades || '',
        // Datos de pieza
        pi_cod:       document.getElementById('sel_pieza').selectedOptions[0]?.dataset.cod || '',
        pi_desc:      document.getElementById('sel_pieza').selectedOptions[0]?.dataset.desc || '',
    };

    Object.entries(campos).forEach(([k,v]) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = k; input.value = v;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>
