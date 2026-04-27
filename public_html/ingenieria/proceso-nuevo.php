<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;
$menu_retorno = '/ingenieria/procesos.php';
$menu_principal = match($rol) {
    1 => '/admin/menu_admin.php',
    2,3 => '/user/menu_user.php',
    default => '/index.php'
};

// Cargar piezas activas
$sqlPi = "SELECT pi_id, pi_cod_prod, pi_descripcion, pi_color,
                 pi_molde, pi_resina, pi_espesor, pi_area_proy, pi_porc_molido
          FROM piezas WHERE pi_activo = 1";
$pPi = [];
if ($rol !== 1) { $sqlPi .= " AND pi_empresa = :empresa"; $pPi[':empresa'] = $empresaId; }
$sqlPi .= " ORDER BY pi_cod_prod";
$stmtPi = $conn->prepare($sqlPi); $stmtPi->execute($pPi);
$piezas = $stmtPi->fetchAll(PDO::FETCH_ASSOC);

// Cargar moldes (para lookup por mo_numero)
$sqlMo = "SELECT mo_id, mo_numero, mo_no_pieza, mo_no_cavidades, mo_peso_pieza,
                 mo_puert_cavidad, mo_no_coladas, mo_peso_colada, mo_ancho,
                 mo_abierto, mo_placas_voladas
          FROM moldes WHERE mo_activo = 1";
$pMo = [];
if ($rol !== 1) { $sqlMo .= " AND mo_empresa = :empresa"; $pMo[':empresa'] = $empresaId; }
$stmtMo = $conn->prepare($sqlMo); $stmtMo->execute($pMo);
$moldes = $stmtMo->fetchAll(PDO::FETCH_ASSOC);
$moldesIdx = array_column($moldes, null, 'mo_numero'); // index por mo_numero

// Cargar resinas (para lookup por re_cod_int)
$sqlRe = "SELECT re_id, re_cod_int, re_tipo_resina, re_grado,
                 re_densidad, re_factor_correccion, re_sec_temp, re_sec_tiempo,
                 re_temp_masa_max, re_temp_masa_min
          FROM resinas WHERE re_activo = 1";
$pRe = [];
if ($rol !== 1) { $sqlRe .= " AND re_empresa = :empresa"; $pRe[':empresa'] = $empresaId; }
$stmtRe = $conn->prepare($sqlRe); $stmtRe->execute($pRe);
$resinas = $stmtRe->fetchAll(PDO::FETCH_ASSOC);
$resinasIdx = array_column($resinas, null, 're_cod_int');

// JSON para JS
$piezasJson  = json_encode(array_column($piezas,  null, 'pi_id'), JSON_UNESCAPED_UNICODE);
$moldesJson  = json_encode($moldesIdx, JSON_UNESCAPED_UNICODE);
$resinasJson = json_encode($resinasIdx, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo Proceso</title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png">
  <link rel="stylesheet" href="/css/acg.estilos.css">
  <style>
    .confirm-card { background:#fff; border-radius:8px; padding:18px 20px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:16px; }
    .confirm-card h4 { margin:0 0 12px; color:#0056b3; font-size:.95em; border-bottom:1px solid #e5e7eb; padding-bottom:6px; }
    .confirm-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:8px; }
    .confirm-item label { display:block; font-size:.75em; color:#666; font-weight:600; }
    .confirm-item span  { font-size:.92em; color:#111; }
    .confirm-item span.empty { color:#aaa; font-style:italic; }
    .maq-card { background:#f8faff; border:2px solid #e5e7eb; border-radius:8px; padding:12px 16px; cursor:pointer; transition:border .15s; }
    .maq-card:hover, .maq-card.selected { border-color:#0056b3; background:#eff6ff; }
    .maq-card .maq-nombre { font-weight:700; font-size:.95em; color:#1e3a8a; }
    .maq-card .maq-detalle { font-size:.8em; color:#555; margin-top:4px; }
    .maq-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:10px; margin-top:10px; }
    .maq-manual { margin-top:12px; padding-top:12px; border-top:1px solid #e5e7eb; }
    .paso { display:none; }
    .paso.activo { display:block; }
    .pasos-nav { display:flex; gap:6px; margin-bottom:20px; }
    .paso-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center;
                font-size:.78em; font-weight:700; background:#e5e7eb; color:#666; }
    .paso-dot.activo { background:#0056b3; color:#fff; }
    .paso-dot.done   { background:#059669; color:#fff; }
    .alerta { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:6px; font-size:.88em; margin-bottom:12px; }
  </style>
</head>
<body>
<header class="header">
  <div class="header-title-group">
    <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
    <h1>Nuevo Proceso</h1>
  </div>
  <div class="header-right">
    <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    <?= burgerBtn() ?>
  </div>
</header>

<main class="main-container">
  <div class="form-section">

    <!-- Indicador de pasos -->
    <div class="pasos-nav">
      <div class="paso-dot activo" id="dot1">1</div>
      <div style="align-self:center;flex:1;height:2px;background:#e5e7eb;"></div>
      <div class="paso-dot" id="dot2">2</div>
      <div style="align-self:center;flex:1;height:2px;background:#e5e7eb;"></div>
      <div class="paso-dot" id="dot3">3</div>
    </div>

    <!-- ── PASO 1: Seleccionar pieza ──────────────────────── -->
    <div class="paso activo" id="paso1">
      <div class="confirm-card">
        <h4>📦 Paso 1 — Seleccionar pieza</h4>
        <div class="form-group" style="max-width:400px;">
          <label>Buscar pieza por código o descripción</label>
          <input type="text" id="buscarPieza" placeholder="Escribe para filtrar..."
                 oninput="filtrarPiezas()" style="padding:8px;border:1px solid #d1d5db;border-radius:4px;width:100%;font-size:.92em;">
        </div>
        <div style="margin-top:10px;max-height:280px;overflow-y:auto;" id="listaPiezas">
          <?php foreach ($piezas as $pi): ?>
          <div class="maq-card" onclick="seleccionarPieza(<?= $pi['pi_id'] ?>)"
               id="pieza_<?= $pi['pi_id'] ?>"
               data-buscar="<?= strtolower(htmlspecialchars($pi['pi_cod_prod'].' '.$pi['pi_descripcion'].' '.$pi['pi_color'])) ?>">
            <div class="maq-nombre"><?= htmlspecialchars($pi['pi_cod_prod']) ?></div>
            <div class="maq-detalle">
              <?= htmlspecialchars($pi['pi_descripcion'] ?? '') ?>
              <?= $pi['pi_color'] ? '· '.htmlspecialchars($pi['pi_color']) : '' ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($piezas)): ?>
          <div class="alerta">No hay piezas registradas. <a href="/forms/form-pieza.php">Registrar pieza</a></div>
          <?php endif; ?>
        </div>
        <a href="/forms/form-pieza.php" class="btn btn-limpiar" style="margin-top:12px;display:inline-block;">
          ➕ Registrar nueva pieza
        </a>
      </div>
    </div>

    <!-- ── PASO 2: Confirmar datos de pieza ───────────────── -->
    <div class="paso" id="paso2">
      <div class="confirm-card" id="card-pieza">
        <h4>✅ Paso 2 — Confirmar datos de la pieza</h4>
        <div class="confirm-grid" id="resumen-pieza"></div>
        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
          <button class="btn btn-limpiar" onclick="irPaso(1)">⬅️ Cambiar pieza</button>
          <a id="link-corregir-pieza" href="#" class="btn btn-pdf" target="_blank">🔧 Corregir en catálogo</a>
          <button class="btn btn-guardar" onclick="confirmarPieza()">✅ Confirmar y continuar</button>
        </div>
      </div>
    </div>

    <!-- ── PASO 3: Seleccionar máquina ───────────────────── -->
    <div class="paso" id="paso3">

      <div class="confirm-card">
        <h4>🏭 Paso 3 — Seleccionar máquina</h4>
        <p style="font-size:.85em;color:#555;margin-bottom:8px;">
          Las máquinas se ordenan por compatibilidad con el molde. Selecciona la que usarás.
        </p>

        <div id="maquinas-sugeridas" class="maq-grid"></div>
        <div id="sin-sugerencias" class="alerta" style="display:none;">
          No se encontraron máquinas compatibles automáticamente. Selecciona una manualmente.
        </div>

        <div class="maq-manual">
          <label style="font-size:.85em;font-weight:600;color:#555;">O buscar máquina manualmente:</label>
          <select id="maq-manual-sel" style="padding:7px;border:1px solid #d1d5db;border-radius:4px;width:100%;margin-top:4px;font-size:.88em;" onchange="seleccionarMaquinaManual()">
            <option value="">— Seleccionar —</option>
          </select>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
          <button class="btn btn-limpiar" onclick="irPaso(2)">⬅️ Volver</button>
          <button class="btn btn-guardar" id="btn-crear-proceso" onclick="crearProceso()" disabled>
            💾 Crear proceso
          </button>
          <a href="/forms/form-maquina.php" class="btn btn-pdf">➕ Registrar nueva máquina</a>
        </div>
      </div>

    </div><!-- /paso3 -->

  </div>
</main>

<footer><p>Método ACG</p></footer>
<?php includeSidebar(); ?>
<script>
const PIEZAS  = <?= $piezasJson ?>;
const MOLDES  = <?= $moldesJson ?>;
const RESINAS = <?= $resinasJson ?>;

let piezaSelId = null;
let piezaSel   = null;
let moldeSel   = null;
let resinaSel  = null;
let maquinaSelId = null;

// ── Navegación de pasos ──────────────────────────────────────
function irPaso(n) {
    [1,2,3].forEach(i => {
        document.getElementById('paso'+i).classList.toggle('activo', i === n);
        const dot = document.getElementById('dot'+i);
        dot.className = 'paso-dot' + (i < n ? ' done' : i === n ? ' activo' : '');
    });
}

// ── Paso 1: filtrar piezas ────────────────────────────────────
function filtrarPiezas() {
    const q = document.getElementById('buscarPieza').value.toLowerCase();
    document.querySelectorAll('#listaPiezas .maq-card').forEach(el => {
        el.style.display = el.dataset.buscar.includes(q) ? '' : 'none';
    });
}

function seleccionarPieza(id) {
    piezaSelId = id;
    piezaSel   = PIEZAS[id];
    // Buscar molde y resina relacionados
    moldeSel  = piezaSel.pi_molde  ? MOLDES[piezaSel.pi_molde]   : null;
    resinaSel = piezaSel.pi_resina ? RESINAS[piezaSel.pi_resina] : null;
    mostrarResumenPieza();
    irPaso(2);
}

// ── Paso 2: resumen de pieza ──────────────────────────────────
function mostrarResumenPieza() {
    const p = piezaSel, mo = moldeSel, re = resinaSel;
    const items = [
        ['Código producto', p.pi_cod_prod],
        ['Descripción',     p.pi_descripcion],
        ['Color',           p.pi_color],
        ['% Molido',        p.pi_porc_molido != null ? p.pi_porc_molido + '%' : null],
        ['Espesor',         p.pi_espesor ? p.pi_espesor + ' mm' : null],
        ['Área proyectada', p.pi_area_proy ? p.pi_area_proy + ' cm²' : null],
        ['───── MOLDE ─────',''],
        ['Número molde',    p.pi_molde],
        ['Cavidades',       mo?.mo_no_cavidades],
        ['Peso pieza',      mo?.mo_peso_pieza ? mo.mo_peso_pieza + ' gr' : null],
        ['Gates/cavidad',   mo?.mo_puert_cavidad],
        ['Coladas',         mo?.mo_no_coladas],
        ['Peso colada',     mo?.mo_peso_colada ? mo.mo_peso_colada + ' gr' : null],
        ['Ancho molde',     mo?.mo_ancho ? mo.mo_ancho + ' mm' : null],
        ['Molde abierto',   mo?.mo_abierto ? mo.mo_abierto + ' mm' : null],
        ['───── RESINA ─────',''],
        ['Código resina',   p.pi_resina],
        ['Tipo / Grado',    re ? (re.re_tipo_resina||'') + ' ' + (re.re_grado||'') : null],
        ['Densidad fría',   re?.re_densidad ? re.re_densidad + ' g/cm³' : null],
        ['Temp. secado',    re?.re_sec_temp  ? re.re_sec_temp  + ' °C'  : null],
        ['Tiempo secado',   re?.re_sec_tiempo ? re.re_sec_tiempo + ' h' : null],
    ];

    document.getElementById('resumen-pieza').innerHTML = items.map(([lbl, val]) => {
        if (val === '') return `<div class="confirm-item" style="grid-column:1/-1;font-weight:700;color:#0056b3;font-size:.8em;margin-top:8px;">${lbl}</div>`;
        return `<div class="confirm-item">
            <label>${lbl}</label>
            <span class="${val ? '' : 'empty'}">${val ?? '—'}</span>
        </div>`;
    }).join('');

    document.getElementById('link-corregir-pieza').href = '/forms/form-pieza.php';

    // Avisos si faltan datos críticos
    let avisos = [];
    if (!p.pi_molde || !moldeSel) avisos.push('La pieza no tiene molde asignado o el molde no está en el catálogo.');
    if (!p.pi_resina || !resinaSel) avisos.push('La pieza no tiene resina asignada o la resina no está en el catálogo.');
    if (!moldeSel?.mo_no_cavidades) avisos.push('El molde no tiene número de cavidades registrado.');
    if (!moldeSel?.mo_peso_pieza)   avisos.push('El molde no tiene peso de pieza registrado.');

    const avisoEl = document.getElementById('aviso-pieza');
    if (avisoEl) avisoEl.remove();
    if (avisos.length) {
        const div = document.createElement('div');
        div.id = 'aviso-pieza';
        div.className = 'alerta';
        div.innerHTML = '⚠️ <strong>Datos incompletos:</strong><br>' + avisos.map(a=>`• ${a}`).join('<br>');
        document.getElementById('card-pieza').prepend(div);
    }
}

function confirmarPieza() {
    cargarMaquinas();
    irPaso(3);
}

// ── Paso 3: máquinas ─────────────────────────────────────────
function cargarMaquinas() {
    // v3: ancho_molde viene de mo_placas_voladas (ancho con placas voladas)
    const ancho  = moldeSel?.mo_placas_voladas ? parseFloat(moldeSel.mo_placas_voladas) :
                   (moldeSel?.mo_ancho ? parseFloat(moldeSel.mo_ancho) : 0);
    const abierto = moldeSel?.mo_abierto ? parseFloat(moldeSel.mo_abierto) : 0;

    fetch('/ingenieria/api-maquinas.php?empresa=<?= $empresaId ?>&planta=<?= $plantaId ?? '' ?>')
    .then(r => r.json())
    .then(maquinas => {
        // Calcular volumen de disparo para rango de husillo
        const dens_f  = resinaSel?.re_densidad         ? parseFloat(resinaSel.re_densidad)         : 0.9;
        const factor  = resinaSel?.re_factor_correccion ? parseFloat(resinaSel.re_factor_correccion): 0.811;
        const dens_cal = dens_f * factor;

        const mo = moldeSel;
        let volDisparo = 0, fuerza_total = 0;
        if (mo && dens_cal > 0) {
            const peso = (parseFloat(mo.mo_no_cavidades||0) * parseFloat(mo.mo_peso_pieza||0))
                       + (parseFloat(mo.mo_no_coladas||0)  * parseFloat(mo.mo_peso_colada||0));
            volDisparo = peso / dens_cal;

            // Calcular fuerza total (para filtro de tonelaje, v3 hoja 4)
            // Usa los mismos cálculos ocultos que proceso-ver
            const espesor_cm   = parseFloat(mo.mo_apert_min||0) / 10 || 0.2; // approx si no hay espesor pieza
            const vol_cav      = dens_cal > 0 ? parseFloat(mo.mo_peso_pieza||0) / dens_cal : 0;
            const gates        = parseFloat(mo.mo_puert_cavidad||1);
            const disco_cav    = espesor_cm > 0 ? vol_cav / espesor_cm : 0;
            const disco_prt    = gates > 0 ? disco_cav / gates : 0;
            const diam_prt     = Math.sqrt(disco_prt / 0.7854);
            const recorrido    = espesor_cm > 0 ? diam_prt / espesor_cm / 2 : 1; // default centro=2
            const efecto_rec   = (8.8 + recorrido * 0.069) / 10;
            const area_proy    = parseFloat(piezaSel?.pi_area_proy||0);
            const fuerza_comp  = efecto_rec * 0.3;
            const fuerza_disco = area_proy * fuerza_comp;
            fuerza_total = fuerza_disco * parseFloat(mo.mo_no_cavidades||1);
        }

        // Rangos v3 (hoja 4):
        const hMin     = volDisparo > 0 ? 10 * Math.pow(volDisparo / 0.7854 / 3.5, 0.33333) : 0;
        const hMax     = volDisparo > 0 ? 10 * Math.pow(volDisparo / 0.7854 / 1.1, 0.33333) : 999;
        const tonMin   = fuerza_total;           // mínimo = fuerza_total calculada
        const tonMax   = fuerza_total * 3;       // máximo = 3x fuerza_total
        const anchMin  = ancho + 15;             // dist_barras > ancho_molde + 15mm

        // Calcular score de husillo para cada máquina (v3: cuánto se acerca al 2.4x ideal)
        const scoreMaquina = (m) => {
            const diam = parseFloat(m.ma_diam_husillo || 0);
            if (!diam || !volDisparo) return 0;
            const area_husillo = diam * diam * 0.007854;
            const recorrido_maq = volDisparo / area_husillo;
            const rec_norm = recorrido_maq * 10 / diam;
            return rec_norm > 2.4 ? 2.4 / rec_norm : rec_norm / 2.4; // 1.0 = perfecto
        };

        // Filtrar y clasificar
        const compatibles = [], resto = [];
        maquinas.forEach(m => {
            const distBarras = parseFloat(m.ma_dist_barras || 0);
            const apertMax   = parseFloat(m.ma_apert_max   || 0);
            const diam       = parseFloat(m.ma_diam_husillo|| 0);
            const tonelaje   = parseFloat(m.ma_tonelaje    || 0);

            const ok_barras  = anchMin  === 15 || distBarras >= anchMin;
            const ok_abierto = abierto  === 0  || apertMax   >= abierto;
            const ok_husillo = diam     === 0  || (diam >= hMin * 0.95 && diam <= hMax * 1.05);
            const ok_ton     = tonelaje === 0  || fuerza_total === 0 || (tonelaje >= tonMin && tonelaje <= tonMax);

            const score = scoreMaquina(m);
            if (ok_barras && ok_abierto && ok_husillo && ok_ton) {
                compatibles.push({...m, score, calidad: 'compatible'});
            } else {
                resto.push({...m, score, calidad: 'manual'});
            }
        });

        // Ordenar compatibles por score descendente (mejor husillo primero)
        compatibles.sort((a, b) => b.score - a.score);

        renderMaquinas(compatibles, hMin, hMax, tonMin, tonMax);
        llenarSelectManual([...compatibles, ...resto]);
    });
}

function renderMaquinas(lista, hMin, hMax, tonMin, tonMax) {
    const cont = document.getElementById('maquinas-sugeridas');
    const sin  = document.getElementById('sin-sugerencias');
    if (!lista.length) { cont.innerHTML=''; sin.style.display='block'; return; }
    sin.style.display = 'none';
    cont.innerHTML = lista.map((m, i) => {
        const scorePct = m.score ? Math.round(m.score * 100) : '—';
        const rank = i === 0 ? '⭐ ' : '';
        return `
        <div class="maq-card" onclick="elegirMaquina(${m.ma_id})" id="maqcard_${m.ma_id}">
            <div class="maq-nombre">${rank}${m.ma_no ? m.ma_no+' — ' : ''}${m.ma_marca} ${m.ma_modelo}</div>
            <div class="maq-detalle">
                Husillo: ${m.ma_diam_husillo||'?'} mm
                · Barras: ${m.ma_dist_barras||'?'} mm
                · Ton: ${m.ma_tonelaje||'?'}
                · Score husillo: ${scorePct}%
            </div>
        </div>`;
    }).join('');
}

function llenarSelectManual(lista) {
    const sel = document.getElementById('maq-manual-sel');
    lista.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.ma_id;
        opt.textContent = (m.ma_no ? m.ma_no+' — ' : '') + m.ma_marca + ' ' + m.ma_modelo;
        sel.appendChild(opt);
    });
}

function elegirMaquina(id) {
    maquinaSelId = id;
    document.querySelectorAll('.maq-card[id^="maqcard_"]').forEach(el => el.classList.remove('selected'));
    const card = document.getElementById('maqcard_'+id);
    if (card) card.classList.add('selected');
    document.getElementById('maq-manual-sel').value = '';
    document.getElementById('btn-crear-proceso').disabled = false;
}

function seleccionarMaquinaManual() {
    const val = document.getElementById('maq-manual-sel').value;
    if (!val) return;
    maquinaSelId = val;
    document.querySelectorAll('.maq-card[id^="maqcard_"]').forEach(el => el.classList.remove('selected'));
    document.getElementById('btn-crear-proceso').disabled = false;
}

function crearProceso() {
    if (!piezaSelId || !maquinaSelId) return;
    document.getElementById('btn-crear-proceso').disabled = true;
    document.getElementById('btn-crear-proceso').textContent = '⏳ Creando...';

    fetch('/ingenieria/guardar_proceso.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            pieza_id:   piezaSelId,
            maquina_id: maquinaSelId,
            molde_num:  piezaSel?.pi_molde  || null,
            resina_cod: piezaSel?.pi_resina || null,
        })
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            window.location = '/ingenieria/proceso-ver.php?id=' + res.proceso_id;
        } else {
            alert(res.mensaje || 'Error al crear el proceso');
            document.getElementById('btn-crear-proceso').disabled = false;
            document.getElementById('btn-crear-proceso').textContent = '💾 Crear proceso';
        }
    });
}
</script>
</body>
</html>
