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
            $sql = "UPDATE moldes SET 
                    mo_no_pieza = :no_pieza, mo_numero = :numero, mo_ancho = :ancho,
                    mo_alto = :alto, mo_largo = :largo, mo_placas_voladas = :placas,
                    mo_anillo_centrador = :anillo, mo_no_circ_agua = :circ_agua, mo_peso = :peso,
                    mo_apert_min = :apert_min, mo_abierto = :abierto, mo_tipo_colada = :colada,
                    mo_no_zonas = :zonas, mo_no_cavidades = :cavidades, mo_peso_pieza = :peso_pieza,
                    mo_puert_cavidad = :puert_cavidad, mo_no_coladas = :no_coladas, mo_peso_colada = :peso_colada,
                    mo_peso_disparo = :peso_disparo, mo_noyos = :noyos, mo_entr_aire = :entr_aire,
                    mo_thermoreguladores = :thermo, mo_valve_gates = :valve, mo_tiempo_ciclo = :ciclo,
                    mo_cavidades_activas = :cav_activas
                    WHERE mo_id = :id";
            
            if ($rol == 2) { $sql .= " AND mo_empresa = :empresa"; }

            $stmt = $conn->prepare($sql);
            
            $params = [
                ':no_pieza' => $input['mo_no_pieza'], ':numero' => $input['mo_numero'], ':ancho' => $input['mo_ancho'],
                ':alto' => $input['mo_alto'], ':largo' => $input['mo_largo'], ':placas' => $input['mo_placas_voladas'],
                ':anillo' => $input['mo_anillo_centrador'], ':circ_agua' => $input['mo_no_circ_agua'], ':peso' => $input['mo_peso'],
                ':apert_min' => $input['mo_apert_min'], ':abierto' => $input['mo_abierto'], ':colada' => $input['mo_tipo_colada'],
                ':zonas' => $input['mo_no_zonas'], ':cavidades' => $input['mo_no_cavidades'], ':peso_pieza' => $input['mo_peso_pieza'],
                ':puert_cavidad' => $input['mo_puert_cavidad'], ':no_coladas' => $input['mo_no_coladas'], ':peso_colada' => $input['mo_peso_colada'],
                ':peso_disparo' => $input['mo_peso_disparo'], ':noyos' => $input['mo_noyos'], ':entr_aire' => $input['mo_entr_aire'],
                ':thermo' => $input['mo_thermoreguladores'], ':valve' => $input['mo_valve_gates'], ':ciclo' => $input['mo_tiempo_ciclo'],
                ':cav_activas' => $input['mo_cavidades_activas'],
                ':id' => $input['mo_id']
            ];

            if ($rol == 2) { $params[':empresa'] = $empresaId; }

            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }

    if ($input['action'] === 'delete') {
        try {
            $sql = "DELETE FROM moldes WHERE mo_id = :id";
            if ($rol == 2) { $sql .= " AND mo_empresa = :empresa"; }
            $stmt = $conn->prepare($sql);
            $params = [':id' => $input['id']];
            if ($rol == 2) { $params[':empresa'] = $empresaId; }
            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }
}

$sql = "SELECT 
            m.mo_id, m.mo_fecha, m.mo_no_pieza, m.mo_numero, m.mo_ancho, m.mo_alto, m.mo_largo,
            m.mo_placas_voladas, m.mo_anillo_centrador, m.mo_no_circ_agua, m.mo_peso,
            m.mo_apert_min, m.mo_abierto, m.mo_tipo_colada, m.mo_no_zonas, m.mo_no_cavidades,
            m.mo_peso_pieza, m.mo_puert_cavidad, m.mo_no_coladas, m.mo_peso_colada,
            m.mo_peso_disparo, m.mo_noyos, m.mo_entr_aire, m.mo_thermoreguladores,
            m.mo_valve_gates, m.mo_tiempo_ciclo, m.mo_cavidades_activas,
            u.us_nombre AS nombre_usuario, e.em_nombre AS nombre_empresa
        FROM moldes m
        LEFT JOIN usuarios u ON m.mo_usuario = u.us_id
        LEFT JOIN empresas e ON m.mo_empresa = e.em_id
        WHERE 1=1";

if ($rol == 2 || $rol == 3) { $sql .= " AND m.mo_empresa = :empresa"; }
$sql .= " ORDER BY m.mo_fecha DESC";

$stmt = $conn->prepare($sql);
if ($rol == 2 || $rol == 3) { $stmt->bindParam(':empresa', $empresaId, PDO::PARAM_INT); }
$stmt->execute();
$moldes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de moldes</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .header { justify-content: space-between; }
        .tabla-registros td, .tabla-registros th { vertical-align: middle; font-size: 0.85em; white-space: nowrap; padding: 6px 8px; border-bottom: 1px solid #eee; }
        .tabla-registros { width: 100%; border-collapse: collapse; min-width: 3000px; }
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
        <a href="registros.php"><h1>Listado de moldes</h1></a>
    </div>
    <div>
        <a href="form-molde.php" class="back-button">➕ Nuevo Molde</a>
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
                    <option value="1">No. pieza</option>
                    <option value="2">No. molde</option>
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
            <?php if (empty($moldes)): ?>
                <p>No hay moldes registrados.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaMoldes">
                        <thead>
                        <tr>
                            <?php if ($puedeEditarEliminar): ?> <th style="min-width:160px;">Acciones</th> <?php endif; ?>
                            <th>Fecha</th><th>No. pieza</th><th>No. molde</th>
                            <th>Ancho</th><th>Alto</th><th>Largo</th><th>Placas voladas</th>
                            <th>Anillo centrador</th><th>No. circ. agua</th>
                            <th>Peso (Calc)</th><th>Apertura mín.</th><th>Abierto (Calc)</th>
                            <th>Tipo colada</th><th>No. zonas</th><th>No. cavidades</th><th>Peso pieza</th>
                            <th>Puer. por cavidad</th><th>No. coladas</th><th>Peso colada</th>
                            <th>Peso disparo (Calc)</th><th>Noyos</th><th>Entrada aire</th>
                            <th>Thermoreguladores</th><th>Valve gates</th><th>Tiempo ciclo</th><th>Cavidades activas</th>
                            <th>Usuario</th><th>Empresa</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($moldes as $m): ?>
                            <tr id="row-<?= $m['mo_id'] ?>">
                                <?php if ($puedeEditarEliminar): ?>
                                    <td>
                                        <button class="btn btn-primary btn-edit" onclick="toggleEdit(<?= $m['mo_id'] ?>)">Editar</button>
                                        <button class="btn btn-success btn-save" onclick="guardarFila(<?= $m['mo_id'] ?>)">Guardar</button>
                                        <button class="btn btn-danger btn-delete" onclick="eliminarFila(<?= $m['mo_id'] ?>)">Eliminar</button>
                                    </td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($m['mo_fecha']) ?></td>
                                
                                <td><span class="view-data"><?= $m['mo_no_pieza'] ?></span><input class="edit-input" id="no_pieza_<?= $m['mo_id'] ?>" value="<?= $m['mo_no_pieza'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_numero'] ?></span><input class="edit-input" id="numero_<?= $m['mo_id'] ?>" value="<?= $m['mo_numero'] ?>"></td>
                                
                                <td><span class="view-data"><?= $m['mo_ancho'] ?></span><input type="number" step="0.01" class="edit-input" id="ancho_<?= $m['mo_id'] ?>" value="<?= $m['mo_ancho'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                <td><span class="view-data"><?= $m['mo_alto'] ?></span><input type="number" step="0.01" class="edit-input" id="alto_<?= $m['mo_id'] ?>" value="<?= $m['mo_alto'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                <td><span class="view-data"><?= $m['mo_largo'] ?></span><input type="number" step="0.01" class="edit-input" id="largo_<?= $m['mo_id'] ?>" value="<?= $m['mo_largo'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                
                                <td><span class="view-data"><?= $m['mo_placas_voladas'] ?></span><input class="edit-input" id="placas_<?= $m['mo_id'] ?>" value="<?= $m['mo_placas_voladas'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_anillo_centrador'] ?></span><input class="edit-input" id="anillo_<?= $m['mo_id'] ?>" value="<?= $m['mo_anillo_centrador'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_no_circ_agua'] ?></span><input class="edit-input" id="circ_agua_<?= $m['mo_id'] ?>" value="<?= $m['mo_no_circ_agua'] ?>"></td>
                                
                                <td><span class="view-data"><?= $m['mo_peso'] ?></span><input type="number" step="0.01" class="edit-input" id="peso_<?= $m['mo_id'] ?>" value="<?= $m['mo_peso'] ?>" readonly style="background-color:#f0f0f0;"></td>
                                
                                <td><span class="view-data"><?= $m['mo_apert_min'] ?></span><input type="number" step="0.01" class="edit-input" id="apert_min_<?= $m['mo_id'] ?>" value="<?= $m['mo_apert_min'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                
                                <td><span class="view-data"><?= $m['mo_abierto'] ?></span><input type="number" step="0.01" class="edit-input" id="abierto_<?= $m['mo_id'] ?>" value="<?= $m['mo_abierto'] ?>" readonly style="background-color:#f0f0f0;"></td>
                                
                                <td><span class="view-data"><?= $m['mo_tipo_colada'] ?></span><input class="edit-input" id="colada_<?= $m['mo_id'] ?>" value="<?= $m['mo_tipo_colada'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_no_zonas'] ?></span><input class="edit-input" id="zonas_<?= $m['mo_id'] ?>" value="<?= $m['mo_no_zonas'] ?>"></td>
                                
                                <td><span class="view-data"><?= $m['mo_no_cavidades'] ?></span><input type="number" class="edit-input" id="cavidades_<?= $m['mo_id'] ?>" value="<?= $m['mo_no_cavidades'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                <td><span class="view-data"><?= $m['mo_peso_pieza'] ?></span><input type="number" step="0.01" class="edit-input" id="peso_pieza_<?= $m['mo_id'] ?>" value="<?= $m['mo_peso_pieza'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                
                                <td><span class="view-data"><?= $m['mo_puert_cavidad'] ?></span><input class="edit-input" id="puert_cavidad_<?= $m['mo_id'] ?>" value="<?= $m['mo_puert_cavidad'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_no_coladas'] ?></span><input class="edit-input" id="no_coladas_<?= $m['mo_id'] ?>" value="<?= $m['mo_no_coladas'] ?>"></td>
                                
                                <td><span class="view-data"><?= $m['mo_peso_colada'] ?></span><input type="number" step="0.01" class="edit-input" id="peso_colada_<?= $m['mo_id'] ?>" value="<?= $m['mo_peso_colada'] ?>" oninput="calcularCampos(<?= $m['mo_id'] ?>)"></td>
                                
                                <td><span class="view-data"><?= $m['mo_peso_disparo'] ?></span><input type="number" step="0.01" class="edit-input" id="peso_disparo_<?= $m['mo_id'] ?>" value="<?= $m['mo_peso_disparo'] ?>" readonly style="background-color:#f0f0f0;"></td>
                                
                                <td><span class="view-data"><?= $m['mo_noyos'] ?></span><input class="edit-input" id="noyos_<?= $m['mo_id'] ?>" value="<?= $m['mo_noyos'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_entr_aire'] ?></span><input class="edit-input" id="entr_aire_<?= $m['mo_id'] ?>" value="<?= $m['mo_entr_aire'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_thermoreguladores'] ?></span><input class="edit-input" id="thermo_<?= $m['mo_id'] ?>" value="<?= $m['mo_thermoreguladores'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_valve_gates'] ?></span><input class="edit-input" id="valve_<?= $m['mo_id'] ?>" value="<?= $m['mo_valve_gates'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_tiempo_ciclo'] ?></span><input class="edit-input" id="ciclo_<?= $m['mo_id'] ?>" value="<?= $m['mo_tiempo_ciclo'] ?>"></td>
                                <td><span class="view-data"><?= $m['mo_cavidades_activas'] ?></span><input class="edit-input" id="cav_activas_<?= $m['mo_id'] ?>" value="<?= $m['mo_cavidades_activas'] ?>"></td>
                                
                                <td><?= htmlspecialchars($m['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($m['nombre_empresa']) ?></td>
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
        const table = document.getElementById('tablaMoldes');
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
            XLSX.writeFile(XLSX.utils.table_to_book(t), "Moldes.xlsx");
        });

        if(btnExportPDF) btnExportPDF.addEventListener('click', () => {
            const doc = new window.jspdf.jsPDF('l', 'mm', 'a0');
            doc.text("Listado de Moldes", 14, 15);
            doc.autoTable({ html: '#tablaMoldes', startY: 20, styles: { fontSize: 6 }, didParseCell: d => { if(d.column.index === 0 && d.section === 'body') d.cell.text = ''; } });
            doc.save('Moldes.pdf');
        });

        renderPage();
    })();

    function calcularCampos(id) {
        const pesoPieza = parseFloat(document.getElementById("peso_pieza_" + id).value) || 0;
        const numCavidades = parseFloat(document.getElementById("cavidades_" + id).value) || 0;
        const pesoColada = parseFloat(document.getElementById("peso_colada_" + id).value) || 0;
        
        const pesoDisparo = (pesoPieza * numCavidades) + pesoColada;
        document.getElementById("peso_disparo_" + id).value = pesoDisparo > 0 ? pesoDisparo.toFixed(2) : "";

        const alto = parseFloat(document.getElementById("alto_" + id).value) || 0;
        const apertMin = parseFloat(document.getElementById("apert_min_" + id).value) || 0;
        
        const moldeAbierto = alto + apertMin;
        document.getElementById("abierto_" + id).value = moldeAbierto > 0 ? moldeAbierto.toFixed(2) : "";

        const ancho = parseFloat(document.getElementById("ancho_" + id).value) || 0;
        const largo = parseFloat(document.getElementById("largo_" + id).value) || 0;
        
        if (ancho > 0 && alto > 0 && largo > 0) {
            const peso = (ancho * alto * largo) / 1000;
            document.getElementById("peso_" + id).value = peso.toFixed(2);
        } else {
            document.getElementById("peso_" + id).value = "";
        }
    }

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

        let datos = { action: 'update', mo_id: id };
        
        datos.mo_no_pieza = document.getElementById('no_pieza_' + id).value;
        datos.mo_numero = document.getElementById('numero_' + id).value;
        datos.mo_ancho = document.getElementById('ancho_' + id).value;
        datos.mo_alto = document.getElementById('alto_' + id).value;
        datos.mo_largo = document.getElementById('largo_' + id).value;
        datos.mo_placas_voladas = document.getElementById('placas_' + id).value;
        datos.mo_anillo_centrador = document.getElementById('anillo_' + id).value;
        datos.mo_no_circ_agua = document.getElementById('circ_agua_' + id).value;
        datos.mo_peso = document.getElementById('peso_' + id).value; // Calculado
        datos.mo_apert_min = document.getElementById('apert_min_' + id).value;
        datos.mo_abierto = document.getElementById('abierto_' + id).value; // Calculado
        datos.mo_tipo_colada = document.getElementById('colada_' + id).value;
        datos.mo_no_zonas = document.getElementById('zonas_' + id).value;
        datos.mo_no_cavidades = document.getElementById('cavidades_' + id).value;
        datos.mo_peso_pieza = document.getElementById('peso_pieza_' + id).value;
        datos.mo_puert_cavidad = document.getElementById('puert_cavidad_' + id).value;
        datos.mo_no_coladas = document.getElementById('no_coladas_' + id).value;
        datos.mo_peso_colada = document.getElementById('peso_colada_' + id).value;
        datos.mo_peso_disparo = document.getElementById('peso_disparo_' + id).value; // Calculado
        datos.mo_noyos = document.getElementById('noyos_' + id).value;
        datos.mo_entr_aire = document.getElementById('entr_aire_' + id).value;
        datos.mo_thermoreguladores = document.getElementById('thermo_' + id).value;
        datos.mo_valve_gates = document.getElementById('valve_' + id).value;
        datos.mo_tiempo_ciclo = document.getElementById('ciclo_' + id).value;
        datos.mo_cavidades_activas = document.getElementById('cav_activas_' + id).value;

        fetch('list-molde-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Molde actualizado");
                location.reload();
            } else {
                alert("❌ Error: " + data.message);
            }
        });
    }

    function eliminarFila(id) {
        if (!confirm("⚠️ ¿Eliminar molde?")) return;
        fetch('list-molde-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("🗑️ Molde eliminado");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        });
    }
</script>
</body>
</html>