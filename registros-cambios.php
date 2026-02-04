<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

date_default_timezone_set('UTC'); 

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;

$menu_retorno = "index.php";
if ($rol == 1) $menu_retorno = "admin/menu_admin.php";
if ($rol == 2 || $rol == 3) $menu_retorno = "user/menu_user.php";

$sql = "
(SELECT 
    'resultado' as tipo_hoja,
    h.hr_id as id, h.hr_maquina_id as maquina_id, 
    h.hr_fecha_registro as fecha_registro, h.hr_fecha_modificacion as fecha_modificacion,
    m.ma_marca, m.ma_modelo,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM hojas_resultado h
INNER JOIN maquinas m ON h.hr_maquina_id = m.ma_id
INNER JOIN usuarios uc ON h.hr_usuario_id = uc.us_id
LEFT JOIN usuarios um ON h.hr_ultimo_usuario_id = um.us_id
LEFT JOIN empresas e ON h.hr_empresa_id = e.em_id
WHERE 1=1 " . (($rol == 2 || $rol == 3) ? "AND h.hr_empresa_id = :empresa1" : "") . ")

UNION ALL

(SELECT 
    'proceso' as tipo_hoja,
    h.hp_id as id, h.hp_maquina_id as maquina_id, 
    h.hp_fecha_registro as fecha_registro, h.hp_fecha_modificacion as fecha_modificacion,
    m.ma_marca, m.ma_modelo,
    uc.us_nombre AS creador, um.us_nombre AS modificador,
    e.em_nombre AS nombre_empresa
FROM hojas_proceso h
INNER JOIN maquinas m ON h.hp_maquina_id = m.ma_id
INNER JOIN usuarios uc ON h.hp_usuario_id = uc.us_id
LEFT JOIN usuarios um ON h.hp_ultimo_usuario_id = um.us_id
LEFT JOIN empresas e ON h.hp_empresa_id = e.em_id
WHERE 1=1 " . (($rol == 2 || $rol == 3) ? "AND h.hp_empresa_id = :empresa2" : "") . ")

ORDER BY COALESCE(fecha_modificacion, fecha_registro) DESC
";

$stmt = $conn->prepare($sql);

if ($rol == 2 || $rol == 3) {
    $stmt->bindParam(':empresa1', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':empresa2', $empresaId, PDO::PARAM_INT);
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
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .header { justify-content: space-between; }
        .tabla-registros td, .tabla-registros th { vertical-align: middle; font-size: 0.85em; white-space: nowrap; padding: 8px 10px; border-bottom: 1px solid #eee; }
        .tabla-registros th { background-color: #000; color: white; text-align: left; height: 45px; }
        .tabla-registros { width: 100%; border-collapse: collapse; min-width: 1000px; }
        .tabla-container-scroll { overflow-x: auto; padding-bottom: 15px; }
        .filtros-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; align-items: center; }
        .filtros-container input[type="text"], .filtros-container select { padding: 6px 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.9em; }
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; gap: 10px; flex-wrap: wrap; }
        .pagination-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .pagination-buttons button { padding: 5px 10px; border-radius: 4px; border: 1px solid #194bb1; background-color: #194bb1; cursor: pointer; font-size: 0.85em; color: white; }
        .pagination-buttons button[disabled] { opacity: 0.5; cursor: default; }
        .page-size-select { padding: 4px 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.85em; }
        .pagination-info { font-size: 0.85em; }
        .export-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-export { padding: 6px 10px; border-radius: 4px; border: 1px solid #d1d5db; background-color: #e5e7eb; cursor: pointer; font-size: 0.85em; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75em; font-weight: bold; color: white; display: inline-block; }
        .bg-created { background-color: #28a745; }
        .bg-modified { background-color: #f39c12; }
        
        .tipo-res { color: #d35400; font-weight: bold; font-size: 0.9em; }
        .tipo-pro { color: #2e86c1; font-weight: bold; font-size: 0.9em; }

        @media print { header, footer, .filtros-container, .pagination-container, .export-buttons { display: none !important; } body { margin: 10px; } }
    </style>
</head>

<body>
<header class="header">
    <div class="header-title-group">
        <a href="<?= $menu_retorno ?>"><img src="imagenes/logo.png" alt="Logo" class="header-logo"></a>
        <a href="<?= $menu_retorno ?>"><h1>Registro de Cambios</h1></a>
    </div>
    <div>
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    </div>
</header>

<main class="main-container">
    <div class="form-section">
        <div class="filtros-container">
            <label>🔍 Buscar: <input type="text" id="filtroGlobal" placeholder="Usuario, máquina..."></label>
            <label>Campo: <select id="campoFiltro"><option value="all">Todos los campos</option><option value="0">Máquina</option><option value="2">Usuario Responsable</option></select></label>
            <label>Registros: <select id="pageSize" class="page-size-select"><option value="25">25</option><option value="50" selected>50</option><option value="100">100</option></select></label>
            <div class="export-buttons">
                <button type="button" class="btn-export" id="btnExportCSV">⬇️ Exportar Excel (CSV)</button>
                <button type="button" class="btn-export" id="btnExportPDF">⬇️ Exportar PDF</button>
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
                            <?php if ($rol == 1): ?><th>Empresa</th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($registros as $r): ?>
                            
                            <?php 
                                if ($r['tipo_hoja'] === 'resultado') {
                                    $link = "hoja.rest.php?id=" . $r['maquina_id'] . "&from=cambios";
                                    $label = '<span class="tipo-res">HOJA RESULTADO</span>';
                                } else {
                                    $link = "hoja.pro.php?id=" . $r['maquina_id'] . "&from=cambios";
                                    $label = '<span class="tipo-pro">HOJA PROCESO</span>';
                                }
                            ?>

                            <?php if ($r['fecha_modificacion']): ?>
                            <tr>
                                <td>
                                    <?= $label ?><br>
                                    <strong><?= htmlspecialchars($r['ma_marca']) ?></strong> <span style="color:#555;"><?= htmlspecialchars($r['ma_modelo']) ?></span>
                                </td>
                                <td><span class="badge bg-modified">✏️ Edición / Actualización</span></td>
                                <td><?= htmlspecialchars($r['modificador']) ?></td>
                                <td><span class="fecha-utc"><?= $r['fecha_modificacion'] ?></span></td>
                                <td><a href="<?= $link ?>" style="color:#194bb1; text-decoration:underline;">Ver hoja actual</a></td>
                                <?php if ($rol == 1): ?><td><?= htmlspecialchars($r['nombre_empresa']) ?></td><?php endif; ?>
                            </tr>
                            <?php endif; ?>

                            <tr>
                                <td>
                                    <?= $label ?><br>
                                    <strong><?= htmlspecialchars($r['ma_marca']) ?></strong> <span style="color:#555;"><?= htmlspecialchars($r['ma_modelo']) ?></span>
                                </td>
                                <td><span class="badge bg-created">✨ Creación Inicial</span></td>
                                <td><?= htmlspecialchars($r['creador']) ?></td>
                                <td><span class="fecha-utc"><?= $r['fecha_registro'] ?></span></td>
                                <td><a href="<?= $link ?>" style="color:#194bb1; text-decoration:underline;">Ver hoja actual</a></td>
                                <?php if ($rol == 1): ?><td><?= htmlspecialchars($r['nombre_empresa']) ?></td><?php endif; ?>
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
            let fechaUTC = new Date(fechaTexto.replace(' ', 'T') + 'Z');
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
</script>
</body>
</html>