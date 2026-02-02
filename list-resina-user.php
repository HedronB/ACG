<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: log.php?error=Debes_iniciar_sesion");
    exit();
}

require_once "config/db.php";

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;

$menu_retorno = "index.php";
if ($rol == 1) $menu_retorno = "admin/menu_admin.php";
if ($rol == 2 || $rol == 3) $menu_retorno = "user/menu_user.php";

$puedeEditarEliminar = ($rol == 1 || $rol == 2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['action'])) { echo json_encode(['success'=>false, 'message'=>'No action']); exit; }

    if ($input['action'] === 'update') {
        try {
            $sql = "UPDATE resinas SET 
                    re_cod_int = :cod_int,
                    re_tipo_resina = :tipo,
                    re_grado = :grado,
                    re_porc_reciclado = :reciclado,
                    re_temp_masa_max = :masa_max,
                    re_temp_masa_min = :masa_min,
                    re_temp_ref_max = :ref_max,
                    re_temp_ref_min = :ref_min,
                    re_sec_temp = :sec_temp,
                    re_sec_tiempo = :sec_tiempo,
                    re_densidad = :densidad,
                    re_factor_correccion = :factor,
                    re_carga = :carga
                    WHERE re_id = :id";
            
            if ($rol == 2) { $sql .= " AND re_empresa = :empresa"; }

            $stmt = $conn->prepare($sql);
            
            $params = [
                ':cod_int' => $input['re_cod_int'],
                ':tipo' => $input['re_tipo_resina'],
                ':grado' => $input['re_grado'],
                ':reciclado' => $input['re_porc_reciclado'],
                ':masa_max' => $input['re_temp_masa_max'],
                ':masa_min' => $input['re_temp_masa_min'],
                ':ref_max' => $input['re_temp_ref_max'],
                ':ref_min' => $input['re_temp_ref_min'],
                ':sec_temp' => $input['re_sec_temp'],
                ':sec_tiempo' => $input['re_sec_tiempo'],
                ':densidad' => $input['re_densidad'],
                ':factor' => $input['re_factor_correccion'],
                ':carga' => $input['re_carga'],
                ':id' => $input['re_id']
            ];

            if ($rol == 2) { $params[':empresa'] = $empresaId; }

            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }

    if ($input['action'] === 'delete') {
        try {
            $sql = "DELETE FROM resinas WHERE re_id = :id";
            if ($rol == 2) { $sql .= " AND re_empresa = :empresa"; }
            $stmt = $conn->prepare($sql);
            $params = [':id' => $input['id']];
            if ($rol == 2) { $params[':empresa'] = $empresaId; }
            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }
}

$sql = "SELECT 
            r.re_id,
            r.re_fecha,
            r.re_cod_int,
            r.re_tipo_resina,
            r.re_grado,
            r.re_porc_reciclado,
            r.re_temp_masa_max,
            r.re_temp_masa_min,
            r.re_temp_ref_max,
            r.re_temp_ref_min,
            r.re_sec_temp,
            r.re_sec_tiempo,
            r.re_densidad,
            r.re_factor_correccion,
            r.re_carga,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM resinas r
        INNER JOIN usuarios u ON r.re_usuario = u.us_id
        INNER JOIN empresas e ON r.re_empresa = e.em_id
        WHERE 1=1";

if ($rol == 2 || $rol == 3) { $sql .= " AND r.re_empresa = :empresa"; }
$sql .= " ORDER BY r.re_fecha DESC";

$stmt = $conn->prepare($sql);
if ($rol == 2 || $rol == 3) { $stmt->bindParam(':empresa', $empresaId, PDO::PARAM_INT); }
$stmt->execute();
$resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de resinas</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .header { justify-content: space-between; }
        .tabla-registros td, .tabla-registros th { vertical-align: middle; font-size: 0.85em; white-space: nowrap; padding: 6px 8px; border-bottom: 1px solid #eee; }
        .tabla-registros { width: 100%; border-collapse: collapse; min-width: 2000px; }
        .tabla-container-scroll { overflow-x: auto; padding-bottom: 15px; }
        
        .filtros-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; align-items: center; }
        .filtros-container input[type="text"], .filtros-container select { padding: 6px 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.9em; }
        
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; gap: 10px; flex-wrap: wrap; }
        .pagination-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        
        .pagination-buttons button {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #194bb1;
            background-color: #194bb1;
            cursor: pointer;
            font-size: 0.85em;
            color: white;
        }
        .pagination-buttons button[disabled] { opacity: 0.5; cursor: default; }
        
        .page-size-select { padding: 4px 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.85em; }
        .pagination-info { font-size: 0.85em; }
        
        .export-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-export { padding: 6px 10px; border-radius: 4px; border: 1px solid #d1d5db; background-color: #e5e7eb; cursor: pointer; font-size: 0.85em; }

        .edit-input { display: none; width: 100%; padding: 4px; border: 1px solid #3b82f6; border-radius: 4px; box-sizing: border-box; }
        .view-data { display: block; }
        
        .btn { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75em; font-weight: bold; color:white; margin-right: 3px; }
        .btn-primary { background-color: #007bff; }
        .btn-success { background-color: #28a745; display: none; }
        .btn-danger { background-color: #dc3545; }

        @media print {
            header, footer, .filtros-container, .pagination-container, .export-buttons { display: none !important; }
            .tabla-container-scroll { overflow: visible; }
            body { margin: 10px; }
        }
    </style>
</head>

<body>
<header class="header">
    <div class="header-title-group">
        <a href="registros.php"><img src="imagenes/logo.png" alt="Logo" class="header-logo"></a>
        <a href="registros.php"><h1>Listado de resinas</h1></a>
    </div>
    <div>
        <a href="form-resina.php" class="back-button">➕ Nueva Resina</a>
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    </div>
</header>

<main class="main-container">
    <div class="form-section">

        <div class="filtros-container">
            <label>
                🔍 Buscar:
                <input type="text" id="filtroGlobal" placeholder="Escribe para filtrar...">
            </label>

            <label>
                Campo:
                <select id="campoFiltro">
                    <option value="all">Todos los campos</option>
                    <option value="1">Código interno</option>
                    <option value="2">Tipo resina</option>
                    <option value="3">Grado</option>
                </select>
            </label>

            <label>
                Registros por página:
                <select id="pageSize" class="page-size-select">
                    <option value="25">25</option><option value="50" selected>50</option><option value="100">100</option>
                </select>
            </label>

            <div class="export-buttons">
                <button type="button" class="btn-export" id="btnExportCSV">⬇️ Exportar Excel (CSV)</button>
                <button type="button" class="btn-export" id="btnExportPDF">⬇️ Exportar PDF</button>
            </div>
        </div>

        <div class="registros-section">
            <?php if (empty($resinas)): ?>
                <p>No hay resinas registradas para los criterios de búsqueda.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaResinas">
                        <thead>
                        <tr>
                            <?php if ($puedeEditarEliminar): ?> <th style="min-width:160px;">Acciones</th> <?php endif; ?>
                            <th>Fecha registro</th>
                            <th>Código interno</th><th>Tipo resina</th><th>Grado</th><th>% reciclado</th>
                            <th>Temp. masa máx.</th><th>Temp. masa mín.</th>
                            <th>Temp. ref. máx.</th><th>Temp. ref. mín.</th>
                            <th>Secado temp.</th><th>Secado tiempo</th>
                            <th>Densidad</th><th>Factor corrección</th><th>Carga</th>
                            <th>Usuario</th><th>Empresa</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($resinas as $r): ?>
                            <tr id="row-<?= $r['re_id'] ?>">
                                <?php if ($puedeEditarEliminar): ?>
                                    <td>
                                        <button class="btn btn-primary btn-edit" onclick="toggleEdit(<?= $r['re_id'] ?>)">Editar</button>
                                        <button class="btn btn-success btn-save" onclick="guardarFila(<?= $r['re_id'] ?>)">Guardar</button>
                                        <button class="btn btn-danger btn-delete" onclick="eliminarFila(<?= $r['re_id'] ?>)">Eliminar</button>
                                    </td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($r['re_fecha']) ?></td>
                                
                                <td><span class="view-data"><?= $r['re_cod_int'] ?></span><input class="edit-input" id="cod_int_<?= $r['re_id'] ?>" value="<?= $r['re_cod_int'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_tipo_resina'] ?></span><input class="edit-input" id="tipo_<?= $r['re_id'] ?>" value="<?= $r['re_tipo_resina'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_grado'] ?></span><input class="edit-input" id="grado_<?= $r['re_id'] ?>" value="<?= $r['re_grado'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_porc_reciclado'] ?></span><input type="number" step="0.01" class="edit-input" id="reciclado_<?= $r['re_id'] ?>" value="<?= $r['re_porc_reciclado'] ?>"></td>
                                
                                <td><span class="view-data"><?= $r['re_temp_masa_max'] ?></span><input type="number" step="0.01" class="edit-input" id="masa_max_<?= $r['re_id'] ?>" value="<?= $r['re_temp_masa_max'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_temp_masa_min'] ?></span><input type="number" step="0.01" class="edit-input" id="masa_min_<?= $r['re_id'] ?>" value="<?= $r['re_temp_masa_min'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_temp_ref_max'] ?></span><input type="number" step="0.01" class="edit-input" id="ref_max_<?= $r['re_id'] ?>" value="<?= $r['re_temp_ref_max'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_temp_ref_min'] ?></span><input type="number" step="0.01" class="edit-input" id="ref_min_<?= $r['re_id'] ?>" value="<?= $r['re_temp_ref_min'] ?>"></td>
                                
                                <td><span class="view-data"><?= $r['re_sec_temp'] ?></span><input type="number" step="0.01" class="edit-input" id="sec_temp_<?= $r['re_id'] ?>" value="<?= $r['re_sec_temp'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_sec_tiempo'] ?></span><input type="number" step="0.01" class="edit-input" id="sec_tiempo_<?= $r['re_id'] ?>" value="<?= $r['re_sec_tiempo'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_densidad'] ?></span><input type="number" step="0.0001" class="edit-input" id="densidad_<?= $r['re_id'] ?>" value="<?= $r['re_densidad'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_factor_correccion'] ?></span><input type="number" step="0.01" class="edit-input" id="factor_<?= $r['re_id'] ?>" value="<?= $r['re_factor_correccion'] ?>"></td>
                                <td><span class="view-data"><?= $r['re_carga'] ?></span><input type="number" step="0.01" class="edit-input" id="carga_<?= $r['re_id'] ?>" value="<?= $r['re_carga'] ?>"></td>
                                
                                <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($r['nombre_empresa']) ?></td>
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
    (function () {
        const table = document.getElementById('tablaResinas');
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
            if (!term) {
                filteredRows = rows.slice();
            } else {
                filteredRows = rows.filter(row => {
                    const celdas = Array.from(row.cells);
                    if (campo === 'all') {
                        return celdas.some(td => td.innerText.toLowerCase().includes(term));
                    } else {
                        return celdas.some(td => td.innerText.toLowerCase().includes(term));
                    }
                });
            }
            currentPage = 1;
            renderPage();
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

        if(btnExportCSV) btnExportCSV.addEventListener('click', () => {
            const t = table.cloneNode(true);
            if(t.rows[0].cells[0].innerText.includes('Acciones')) Array.from(t.rows).forEach(r => r.deleteCell(0));
            XLSX.writeFile(XLSX.utils.table_to_book(t), "Resinas.xlsx");
        });

        if(btnExportPDF) btnExportPDF.addEventListener('click', () => {
            const doc = new window.jspdf.jsPDF('l', 'mm', 'a3');
            doc.text("Listado de Resinas", 14, 15);
            doc.autoTable({ html: '#tablaResinas', startY: 20, styles: { fontSize: 8 }, didParseCell: d => { if(d.column.index === 0 && d.section === 'body') d.cell.text = ''; } });
            doc.save('Resinas.pdf');
        });

        renderPage();
    })();

    function toggleEdit(id) {
        const row = document.getElementById('row-' + id);
        const editing = row.classList.toggle('modo-edicion');
        row.querySelectorAll('.view-data').forEach(e => e.style.display = editing ? 'none' : 'block');
        row.querySelectorAll('.edit-input').forEach(e => e.style.display = editing ? 'block' : 'none');
        
        row.querySelector('.btn-edit').style.display = editing ? 'none' : 'inline-block';
        row.querySelector('.btn-save').style.display = editing ? 'inline-block' : 'none';
        row.querySelector('.btn-delete').style.display = editing ? 'none' : 'inline-block';
        
        row.style.backgroundColor = editing ? '#eef7ff' : '';
    }

    function guardarFila(id) {
        if (!confirm("¿Desea guardar los cambios?")) return;

        let datos = { action: 'update', re_id: id };
        
        datos.re_cod_int = document.getElementById('cod_int_' + id).value;
        datos.re_tipo_resina = document.getElementById('tipo_' + id).value;
        datos.re_grado = document.getElementById('grado_' + id).value;
        datos.re_porc_reciclado = document.getElementById('reciclado_' + id).value;
        datos.re_temp_masa_max = document.getElementById('masa_max_' + id).value;
        datos.re_temp_masa_min = document.getElementById('masa_min_' + id).value;
        datos.re_temp_ref_max = document.getElementById('ref_max_' + id).value;
        datos.re_temp_ref_min = document.getElementById('ref_min_' + id).value;
        datos.re_sec_temp = document.getElementById('sec_temp_' + id).value;
        datos.re_sec_tiempo = document.getElementById('sec_tiempo_' + id).value;
        datos.re_densidad = document.getElementById('densidad_' + id).value;
        datos.re_factor_correccion = document.getElementById('factor_' + id).value;
        datos.re_carga = document.getElementById('carga_' + id).value;

        fetch('list-resina-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Resina actualizada");
                location.reload();
            } else {
                alert("❌ Error: " + data.message);
            }
        });
    }

    function eliminarFila(id) {
        if (!confirm("⚠️ ¿Eliminar resina?")) return;
        fetch('list-resina-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("🗑️ Resina eliminada");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        });
    }
</script>
</body>
</html>