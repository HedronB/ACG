<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;

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

// Filtro por empresa para roles no-admin
$filtroEmpresa1 = ($rol == 2 || $rol == 3) ? "AND h.hr_empresa_id = :empresa1" : "";
$filtroEmpresa2 = ($rol == 2 || $rol == 3) ? "AND h.hp_empresa_id = :empresa2" : "";
$filtroEmpresa3 = ($rol == 2 || $rol == 3) ? "AND m.ma_empresa = :empresa3" : "";
$filtroEmpresa4 = ($rol == 2 || $rol == 3) ? "AND m.mo_empresa = :empresa4" : "";
$filtroEmpresa5 = ($rol == 2 || $rol == 3) ? "AND p.pi_empresa = :empresa5" : "";
$filtroEmpresa6 = ($rol == 2 || $rol == 3) ? "AND r.re_empresa = :empresa6" : "";

$sql = "
(SELECT
    'resultado' as tipo_hoja, 'hoja' as categoria,
    h.hr_id as id, h.hr_maquina_id as referencia_id,
    h.hr_fecha_registro as fecha_registro, h.hr_fecha_modificacion as fecha_modificacion,
    CONCAT(m.ma_marca, ' ', m.ma_modelo) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM hojas_resultado h
INNER JOIN maquinas m ON h.hr_maquina_id = m.ma_id
INNER JOIN usuarios uc ON h.hr_usuario_id = uc.us_id
LEFT JOIN usuarios um ON h.hr_ultimo_usuario_id = um.us_id
LEFT JOIN empresas e ON h.hr_empresa_id = e.em_id
WHERE 1=1 $filtroEmpresa1)

UNION ALL

(SELECT
    'proceso' as tipo_hoja, 'hoja' as categoria,
    h.hp_id as id, h.hp_maquina_id as referencia_id,
    h.hp_fecha_registro as fecha_registro, h.hp_fecha_modificacion as fecha_modificacion,
    CONCAT(m.ma_marca, ' ', m.ma_modelo) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM hojas_proceso h
INNER JOIN maquinas m ON h.hp_maquina_id = m.ma_id
INNER JOIN usuarios uc ON h.hp_usuario_id = uc.us_id
LEFT JOIN usuarios um ON h.hp_ultimo_usuario_id = um.us_id
LEFT JOIN empresas e ON h.hp_empresa_id = e.em_id
WHERE 1=1 $filtroEmpresa2)

UNION ALL

(SELECT
    'maquina' as tipo_hoja, 'catalogo' as categoria,
    m.ma_id as id, m.ma_id as referencia_id,
    m.ma_fecha as fecha_registro, m.ma_actualizado_en as fecha_modificacion,
    CONCAT(m.ma_marca, ' ', m.ma_modelo) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM maquinas m
INNER JOIN usuarios uc ON m.ma_usuario = uc.us_id
LEFT JOIN usuarios um ON m.ma_actualizado_por = um.us_id
LEFT JOIN empresas e ON m.ma_empresa = e.em_id
WHERE m.ma_activo = 1 $filtroEmpresa3)

UNION ALL

(SELECT
    'molde' as tipo_hoja, 'catalogo' as categoria,
    m.mo_id as id, m.mo_id as referencia_id,
    m.mo_fecha as fecha_registro, m.mo_actualizado_en as fecha_modificacion,
    CONCAT('Molde ', m.mo_numero, ' / Pieza ', m.mo_no_pieza) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM moldes m
INNER JOIN usuarios uc ON m.mo_usuario = uc.us_id
LEFT JOIN usuarios um ON m.mo_actualizado_por = um.us_id
LEFT JOIN empresas e ON m.mo_empresa = e.em_id
WHERE m.mo_activo = 1 $filtroEmpresa4)

UNION ALL

(SELECT
    'pieza' as tipo_hoja, 'catalogo' as categoria,
    p.pi_id as id, p.pi_id as referencia_id,
    p.pi_fecha as fecha_registro, p.pi_actualizado_en as fecha_modificacion,
    CONCAT('Pieza ', p.pi_cod_prod, ' - ', p.pi_descripcion) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM piezas p
INNER JOIN usuarios uc ON p.pi_usuario = uc.us_id
LEFT JOIN usuarios um ON p.pi_actualizado_por = um.us_id
LEFT JOIN empresas e ON p.pi_empresa = e.em_id
WHERE p.pi_activo = 1 $filtroEmpresa5)

UNION ALL

(SELECT
    'resina' as tipo_hoja, 'catalogo' as categoria,
    r.re_id as id, r.re_id as referencia_id,
    r.re_fecha as fecha_registro, r.re_actualizado_en as fecha_modificacion,
    CONCAT(r.re_tipo_resina, ' ', r.re_grado) as contexto,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM resinas r
INNER JOIN usuarios uc ON r.re_usuario = uc.us_id
LEFT JOIN usuarios um ON r.re_actualizado_por = um.us_id
LEFT JOIN empresas e ON r.re_empresa = e.em_id
WHERE r.re_activo = 1 $filtroEmpresa6)

ORDER BY COALESCE(fecha_modificacion, fecha_registro) DESC
";

$stmt = $conn->prepare($sql);

if ($rol == 2 || $rol == 3) {
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindParam(":empresa{$i}", $empresaId, PDO::PARAM_INT);
    }
}

$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cambios - Sistema</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body>
<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_principal ?>"><img src="/imagenes/logo.png" alt="Logo" class="header-logo"></a>
        <h1>Registro de Cambios</h1>
    </div>
    <div>
        <div class="header-right">
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
    </div>
</header>

<main class="main-container">
    <div class="form-section">
        <div class="filtros-container">
            <label>🔍 Buscar: <input type="text" id="filtroGlobal" placeholder="Usuario, máquina..."></label>
            <label>Campo: <select id="campoFiltro"><option value="all">Todos los campos</option><option value="0">Máquina</option><option value="2">Usuario Responsable</option></select></label>
            <label>Registros: <select id="pageSize" class="page-size-select"><option value="25">25</option><option value="50" selected>50</option><option value="100">100</option></select></label>
            <div class="export-buttons">
                <button type="button" class="btn btn-excel btn-export" id="btnExportCSV">📥 Exportar Excel</button>
                <button type="button" class="btn btn-pdf btn-export" id="btnExportPDF">📥 Exportar PDF</button>
            </div>
        </div>

        <div class="registros-section">
            <?php if (empty($registros)): ?>
                <p style="text-align:center; padding:20px;">No se han registrado cambios o actividades recientes.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaCambios">
                        <thead>
                        <tr>
                            <th>Máquina (Contexto)</th>
                            <th>Tipo de Actividad</th>
                            <th>Usuario Responsable</th>
                            <th>Fecha y Hora</th>
                            <th>Detalle Original</th>
                            <th>Acción</th>
                            <?php if ($rol == 1): ?><th>Empresa</th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($registros as $r): ?>
                            
                            <?php
                                // Configuración por tipo: [color, texto, link]
                                $tipoConfig = [
                                    'resultado' => ['#d35400', 'HOJA RESULTADO', '/reportes/hoja-resultado.php?id='.$r['referencia_id'].'&from=cambios'],
                                    'proceso'   => ['#2e86c1', 'HOJA PROCESO',   '/reportes/hoja-proceso.php?id='.$r['referencia_id'].'&from=cambios'],
                                    'maquina'   => ['#1a5276', 'MÁQUINA',         '/lists/list-maquina.php'],
                                    'molde'     => ['#117a65', 'MOLDE',           '/lists/list-molde.php'],
                                    'pieza'     => ['#6c3483', 'PIEZA',           '/lists/list-pieza.php'],
                                    'resina'    => ['#784212', 'RESINA',          '/lists/list-resina.php'],
                                ];
                                $cfg  = $tipoConfig[$r['tipo_hoja']] ?? ['#555', strtoupper($r['tipo_hoja']), '#'];
                                $link = $cfg[2];
                                $label = '<span style="color:'.$cfg[0].'; font-weight:bold; font-size:0.85em;">'.$cfg[1].'</span>';
                                
                                // Descripción alusiva según tipo y acción
                                $descripciones = [
                                    'resultado' => ['creacion'=>'Nueva Hoja de Resultado', 'edicion'=>'Actualización de Hoja de Resultado'],
                                    'proceso'   => ['creacion'=>'Nueva Hoja de Proceso',   'edicion'=>'Actualización de Hoja de Proceso'],
                                    'maquina'   => ['creacion'=>'Alta de Máquina',          'edicion'=>'Edición de Máquina'],
                                    'molde'     => ['creacion'=>'Alta de Molde',            'edicion'=>'Edición de Molde'],
                                    'pieza'     => ['creacion'=>'Alta de Pieza',            'edicion'=>'Edición de Pieza'],
                                    'resina'    => ['creacion'=>'Alta de Resina',           'edicion'=>'Edición de Resina'],
                                ];
                                $desc = $descripciones[$r['tipo_hoja']] ?? ['creacion'=>'Creación','edicion'=>'Edición'];
                                // Texto que va en columna contexto (reemplaza ma_marca/ma_modelo)
                                $contexto = htmlspecialchars($r['contexto'] ?? '');
                            ?>

                            <?php if ($r['fecha_modificacion']): ?>
                            <tr>
                                <td><?= $label ?><br><span style="color:#333;"><?= $contexto ?></span></td>
                                <td><span class="badge bg-modified">✏️ <?= $desc['edicion'] ?></span></td>
                                <td><?= htmlspecialchars($r['modificador'] ?? '—') ?></td>
                                <td><?= $r['fecha_modificacion'] ?></td>
                                <td><a href="<?= $link ?>" style="color:#194bb1; text-decoration:underline;">Ver detalle</a></td>
                                <?php if ($rol == 1): ?><td><?= htmlspecialchars($r['nombre_empresa'] ?? '') ?></td><?php endif; ?>
                            </tr>
                            <?php endif; ?>

                            <tr>
                                <td><?= $label ?><br><span style="color:#333;"><?= $contexto ?></span></td>
                                <td><span class="badge bg-created">✨ <?= $desc['creacion'] ?></span></td>
                                <td><?= htmlspecialchars($r['creador'] ?? '—') ?></td>
                                <td><?= $r['fecha_registro'] ?></td>
                                <td><a href="<?= $link ?>" style="color:#194bb1; text-decoration:underline;">Ver detalle</a></td>
                                <td>
                                <?php if (in_array($r['tipo_hoja'], ['resultado','proceso']) && ($rol <= 2 || $r['creador'] === $r['creador'])): ?>
                                    <button class="btn-eliminar-hoja" style="border:none;background:none;cursor:pointer;color:#dc2626;font-size:.9em;padding:2px 6px;"
                                        data-tipo="<?= $r['tipo_hoja'] ?>" data-id="<?= $r['id'] ?>"
                                        title="Eliminar hoja">✖️</button>
                                <?php endif; ?>
                                </td>
                                <?php if ($rol == 1): ?><td><?= htmlspecialchars($r['nombre_empresa'] ?? '') ?></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-buttons">
                        <button type="button" id="prevPage">&laquo; Anterior</button>
                        <button type="button" id="nextPage">Siguiente &raquo;</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer><p>Método ACG</p></footer>

<script>
    (function(){
        document.querySelectorAll('.fecha-utc').forEach(function(el) {
            let fechaTexto = el.innerText;
            if(!fechaTexto) return;
            let fechaUTC = new Date(fechaTexto.replace(' ', 'T')); // hora local, sin Z
            if (!isNaN(fechaUTC)) {
                el.innerText = fechaUTC.toLocaleString();
            }
        });
    })();

    (function () {
        const table = document.getElementById('tablaCambios');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const filtroGlobal = document.getElementById('filtroGlobal');
        const campoFiltro = document.getElementById('campoFiltro');
        const pageSizeSelect = document.getElementById('pageSize');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const info = document.getElementById('paginationInfo');
        const btnExportCSV = document.getElementById('btnExportCSV');
        const btnExportPDF = document.getElementById('btnExportPDF');

        let filteredRows = rows.slice();
        let currentPage = 1;
        let pageSize = parseInt(pageSizeSelect.value, 10);

        function aplicaFiltro() {
            const term = filtroGlobal.value.toLowerCase().trim();
            const campo = campoFiltro.value;
            if (!term) { filteredRows = rows.slice(); } else {
                filteredRows = rows.filter(row => {
                    const celdas = Array.from(row.cells);
                    if (campo === 'all') { return celdas.some(td => td.innerText.toLowerCase().includes(term)); }
                    else { const idx = parseInt(campo); return celdas[idx] ? celdas[idx].innerText.toLowerCase().includes(term) : false; }
                });
            }
            currentPage = 1; renderPage();
        }

        function renderPage() {
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
            const total = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageRows = filteredRows.slice(start, end);
            pageRows.forEach(r => tbody.appendChild(r));
            const from = total === 0 ? 0 : start + 1;
            const to = Math.min(end, total);
            info.textContent = `Mostrando ${from}–${to} de ${total} registros`;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages || total === 0;
        }

        filtroGlobal.addEventListener('input', aplicaFiltro);
        campoFiltro.addEventListener('change', aplicaFiltro);
        pageSizeSelect.addEventListener('change', () => { pageSize = parseInt(pageSizeSelect.value, 10); currentPage = 1; renderPage(); });
        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
        nextBtn.addEventListener('click', () => { const total = filteredRows.length; const totalPages = Math.max(1, Math.ceil(total / pageSize)); if (currentPage < totalPages) { currentPage++; renderPage(); } });

        if(btnExportCSV) btnExportCSV.addEventListener('click', () => { const t = table.cloneNode(true); XLSX.writeFile(XLSX.utils.table_to_book(t), "Registro_Cambios.xlsx"); });
        if(btnExportPDF) btnExportPDF.addEventListener('click', () => { const doc = new window.jspdf.jsPDF('l', 'mm', 'a4'); doc.text("Registro de Cambios del Sistema", 14, 15); doc.autoTable({ html: '#tablaCambios', startY: 20, styles: { fontSize: 8 }, columnStyles: { 4: { cellWidth: 30 } } }); doc.save('Registro_Cambios.pdf'); });

        renderPage();
    })();

    // Eliminar hojas
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar-hoja');
        if (!btn) return;
        const tipo = btn.dataset.tipo;
        const id   = btn.dataset.id;
        if (!confirm('¿Eliminar esta hoja de ' + tipo + '? Esta acción no se puede deshacer.')) return;
        fetch('/actions/delete_hoja.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({tipo, id})
        }).then(r=>r.json()).then(res => {
            if (res.ok) { btn.closest('tr').remove(); }
            else { alert('Error: ' + (res.mensaje || 'No se pudo eliminar')); }
        });
    });
</script>
<?php includeSidebar(); ?>
</body>
</html>