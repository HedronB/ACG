<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

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
            $sql = "UPDATE piezas SET 
                    pi_fecha = :fecha,
                    pi_cod_prod = :cod_prod,
                    pi_molde = :molde,
                    pi_descripcion = :descripcion,
                    pi_resina = :resina,
                    pi_espesor = :espesor,
                    pi_area_proy = :area,
                    pi_color = :color,
                    pi_tipo_empaque = :empaque,
                    pi_piezas = :piezas,
                    pi_caja_no_pzs = :caja_pzs,
                    pi_caja_tamano = :caja_tam,
                    pi_bolsa1 = :bolsa1,
                    pi_bolsa2 = :bolsa2,
                    pi_tarima_no_cajas = :tarima
                    WHERE pi_id = :id";
            
            if ($rol == 2) { $sql .= " AND pi_empresa = :empresa"; }

            $stmt = $conn->prepare($sql);
            
            $params = [
                ':fecha' => $input['pi_fecha'],
                ':cod_prod' => $input['pi_cod_prod'],
                ':molde' => $input['pi_molde'],
                ':descripcion' => $input['pi_descripcion'],
                ':resina' => $input['pi_resina'],
                ':espesor' => $input['pi_espesor'],
                ':area' => $input['pi_area_proy'],
                ':color' => $input['pi_color'],
                ':empaque' => $input['pi_tipo_empaque'],
                ':piezas' => $input['pi_piezas'],
                ':caja_pzs' => $input['pi_caja_no_pzs'],
                ':caja_tam' => $input['pi_caja_tamano'],
                ':bolsa1' => $input['pi_bolsa1'],
                ':bolsa2' => $input['pi_bolsa2'],
                ':tarima' => $input['pi_tarima_no_cajas'],
                ':id' => $input['pi_id']
            ];

            if ($rol == 2) { $params[':empresa'] = $empresaId; }

            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }

    if ($input['action'] === 'delete') {
        try {
            $sql = "DELETE FROM piezas WHERE pi_id = :id";
            if ($rol == 2) { $sql .= " AND pi_empresa = :empresa"; }
            $stmt = $conn->prepare($sql);
            $params = [':id' => $input['id']];
            if ($rol == 2) { $params[':empresa'] = $empresaId; }
            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }
}

$sql = "SELECT 
            p.pi_id,
            p.pi_fecha,
            p.pi_cod_prod,
            p.pi_molde,
            p.pi_descripcion,
            p.pi_resina,
            p.pi_espesor,
            p.pi_area_proy,
            p.pi_color,
            p.pi_tipo_empaque,
            p.pi_piezas,
            p.pi_caja_no_pzs,
            p.pi_caja_tamano,
            p.pi_bolsa1,
            p.pi_bolsa2,
            p.pi_tarima_no_cajas,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM piezas p
        INNER JOIN usuarios u ON p.pi_usuario = u.us_id
        INNER JOIN empresas e ON p.pi_empresa = e.em_id
        WHERE 1=1";

if ($rol == 2) { 
    $sql .= " AND p.pi_empresa = :empresa"; 
} elseif ($rol == 3) {
    $sql .= " AND p.pi_empresa = :empresa";
}

$sql .= " ORDER BY p.pi_fecha DESC";

$stmt = $conn->prepare($sql);
if ($rol == 2 || $rol == 3) { 
    $stmt->bindParam(':empresa', $empresaId, PDO::PARAM_INT); 
}
$stmt->execute();
$piezas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de piezas</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .header { justify-content: space-between; }
        .tabla-registros td, .tabla-registros th { vertical-align: middle; font-size: 0.85em; white-space: nowrap; padding: 6px 8px; border-bottom: 1px solid #eee; }
        .tabla-registros { width: 100%; border-collapse: collapse; min-width: 2500px; }
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
        <a href="registros.php"><h1>Listado de piezas</h1></a>
    </div>
    <div>
        <a href="form-pieza.php" class="back-button">➕ Nueva Pieza</a>
        <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
    </div>
</header>

<main class="main-container">
    <div class="form-section">

        <div class="filtros-container">
            <label>🔍 Buscar: <input type="text" id="filtroGlobal" placeholder="Escribe para filtrar..."></label>
            <label>Campo: 
                <select id="campoFiltro">
                    <option value="all">Todos los campos</option>
                    <option value="1">Código producto</option>
                    <option value="3">Descripción</option>
                    <option value="4">Resina</option>
                </select>
            </label>
            <label>Registros: 
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
            <?php if (empty($piezas)): ?>
                <p>No hay piezas registradas para los criterios de búsqueda.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaPiezas">
                        <thead>
                        <tr>
                            <?php if ($puedeEditarEliminar): ?> <th style="min-width:160px;">Acciones</th> <?php endif; ?>
                            <th>Fecha registro</th>
                            <th>Código producto</th><th>Molde</th><th>Descripción</th><th>Resina</th>
                            <th>Espesor</th><th>Área proyectada</th><th>Color</th><th>Tipo empaque</th>
                            <th>Piezas</th><th>Caja no. pzs</th><th>Tamaño caja</th>
                            <th>Bolsa 1</th><th>Bolsa 2</th><th>Tarima no. cajas</th>
                            <th>Usuario</th><th>Empresa</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($piezas as $p): ?>
                            <tr id="row-<?= $p['pi_id'] ?>">
                                <?php if ($puedeEditarEliminar): ?>
                                    <td>
                                        <button class="btn btn-primary btn-edit" onclick="toggleEdit(<?= $p['pi_id'] ?>)">Editar</button>
                                        <button class="btn btn-success btn-save" onclick="guardarFila(<?= $p['pi_id'] ?>)">Guardar</button>
                                        <button class="btn btn-danger btn-delete" onclick="eliminarFila(<?= $p['pi_id'] ?>)">Eliminar</button>
                                    </td>
                                <?php endif; ?>

                                <td><span class="view-data"><?= htmlspecialchars($p['pi_fecha']) ?></span><input type="date" class="edit-input" id="fecha_<?= $p['pi_id'] ?>" value="<?= date('Y-m-d', strtotime($p['pi_fecha'])) ?>"></td>
                                
                                <td><span class="view-data"><?= $p['pi_cod_prod'] ?></span><input class="edit-input" id="cod_prod_<?= $p['pi_id'] ?>" value="<?= $p['pi_cod_prod'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_molde'] ?></span><input class="edit-input" id="molde_<?= $p['pi_id'] ?>" value="<?= $p['pi_molde'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_descripcion'] ?></span><input class="edit-input" id="descripcion_<?= $p['pi_id'] ?>" value="<?= $p['pi_descripcion'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_resina'] ?></span><input class="edit-input" id="resina_<?= $p['pi_id'] ?>" value="<?= $p['pi_resina'] ?>"></td>
                                
                                <td><span class="view-data"><?= $p['pi_espesor'] ?></span><input type="number" step="0.01" class="edit-input" id="espesor_<?= $p['pi_id'] ?>" value="<?= $p['pi_espesor'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_area_proy'] ?></span><input type="number" step="0.01" class="edit-input" id="area_<?= $p['pi_id'] ?>" value="<?= $p['pi_area_proy'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_color'] ?></span><input class="edit-input" id="color_<?= $p['pi_id'] ?>" value="<?= $p['pi_color'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_tipo_empaque'] ?></span><input class="edit-input" id="empaque_<?= $p['pi_id'] ?>" value="<?= $p['pi_tipo_empaque'] ?>"></td>
                                
                                <td><span class="view-data"><?= $p['pi_piezas'] ?></span><input type="number" class="edit-input" id="piezas_<?= $p['pi_id'] ?>" value="<?= $p['pi_piezas'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_caja_no_pzs'] ?></span><input type="number" class="edit-input" id="caja_pzs_<?= $p['pi_id'] ?>" value="<?= $p['pi_caja_no_pzs'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_caja_tamano'] ?></span><input class="edit-input" id="caja_tam_<?= $p['pi_id'] ?>" value="<?= $p['pi_caja_tamano'] ?>"></td>
                                
                                <td><span class="view-data"><?= $p['pi_bolsa1'] ?></span><input class="edit-input" id="bolsa1_<?= $p['pi_id'] ?>" value="<?= $p['pi_bolsa1'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_bolsa2'] ?></span><input class="edit-input" id="bolsa2_<?= $p['pi_id'] ?>" value="<?= $p['pi_bolsa2'] ?>"></td>
                                <td><span class="view-data"><?= $p['pi_tarima_no_cajas'] ?></span><input type="number" class="edit-input" id="tarima_<?= $p['pi_id'] ?>" value="<?= $p['pi_tarima_no_cajas'] ?>"></td>
                                
                                <td><?= htmlspecialchars($p['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($p['nombre_empresa']) ?></td>
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
        const table = document.getElementById('tablaPiezas');
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
            XLSX.writeFile(XLSX.utils.table_to_book(t), "Piezas.xlsx");
        });

        if(btnExportPDF) btnExportPDF.addEventListener('click', () => {
            const doc = new window.jspdf.jsPDF('l', 'mm', 'a3');
            doc.text("Listado de Piezas", 14, 15);
            doc.autoTable({ html: '#tablaPiezas', startY: 20, styles: { fontSize: 8 }, didParseCell: d => { if(d.column.index === 0 && d.section === 'body') d.cell.text = ''; } });
            doc.save('Piezas.pdf');
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

        let datos = { action: 'update', pi_id: id };
        
        datos.pi_fecha = document.getElementById('fecha_' + id).value;
        datos.pi_cod_prod = document.getElementById('cod_prod_' + id).value;
        datos.pi_molde = document.getElementById('molde_' + id).value;
        datos.pi_descripcion = document.getElementById('descripcion_' + id).value;
        datos.pi_resina = document.getElementById('resina_' + id).value;
        datos.pi_espesor = document.getElementById('espesor_' + id).value;
        datos.pi_area_proy = document.getElementById('area_' + id).value;
        datos.pi_color = document.getElementById('color_' + id).value;
        datos.pi_tipo_empaque = document.getElementById('empaque_' + id).value;
        datos.pi_piezas = document.getElementById('piezas_' + id).value;
        datos.pi_caja_no_pzs = document.getElementById('caja_pzs_' + id).value;
        datos.pi_caja_tamano = document.getElementById('caja_tam_' + id).value;
        datos.pi_bolsa1 = document.getElementById('bolsa1_' + id).value;
        datos.pi_bolsa2 = document.getElementById('bolsa2_' + id).value;
        datos.pi_tarima_no_cajas = document.getElementById('tarima_' + id).value;

        fetch('list-pieza-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Pieza actualizada");
                location.reload();
            } else {
                alert("❌ Error: " + data.message);
            }
        });
    }

    function eliminarFila(id) {
        if (!confirm("⚠️ ¿Eliminar pieza?")) return;
        fetch('list-pieza-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("🗑️ Pieza eliminada");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        });
    }
</script>
</body>
</html>