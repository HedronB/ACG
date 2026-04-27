<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;

// Definir menú de retorno
$menu_retorno = "/reportes/menu-reportes.php";

// 2. CONSULTA DE MÁQUINAS
// Obtenemos solo los datos necesarios para la selección
$sql = "SELECT m.ma_id, m.ma_fecha, m.ma_marca, m.ma_modelo, e.em_nombre as nombre_empresa
        FROM maquinas m
        LEFT JOIN empresas e ON m.ma_empresa = e.em_id
        WHERE m.ma_activo = 1";

// Filtro por empresa (Admin ve todo, Gerente/Usuario solo su empresa)
if ($rol == 2 || $rol == 3) {
    $sql .= " AND m.ma_empresa = :empresa";
}

$sql .= " ORDER BY m.ma_fecha DESC";

$stmt = $conn->prepare($sql);
if ($rol == 2 || $rol == 3) {
    $stmt->bindParam(':empresa', $empresaId, PDO::PARAM_INT);
}
$stmt->execute();
$maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Máquina - Hoja de Proceso</title>
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
        <h1>Seleccionar Máquina</h1>
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
            <label>
                🔍 Buscar:
                <input type="text" id="filtroGlobal" placeholder="Escribe para filtrar...">
            </label>

            <label>
                Campo:
                <select id="campoFiltro">
                    <option value="all">Todos los campos</option>
                    <option value="1">Fecha</option>
                    <option value="2">Marca</option>
                    <option value="3">Modelo</option>
                </select>
            </label>

            <label>
                Registros por página:
                <select id="pageSize" class="page-size-select">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                </select>
            </label>
            
            <div class="export-buttons">
                <button type="button" class="btn btn-excel btn-export" id="btnExportCSV">📥 Exportar Excel</button>
                <button type="button" class="btn btn-pdf btn-export" id="btnExportPDF">📥 Exportar PDF</button>
            </div>
        </div>

        <div class="registros-section">
            <?php if (empty($maquinas)): ?>
                <p style="text-align:center; padding:20px;">No hay máquinas registradas.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaMaquinas">
                        <thead>
                        <tr>
                            <th style="width: 180px; text-align:center;">Acciones</th>
                            <th>Fecha</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <?php if ($rol == 1): ?><th>Empresa</th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                        <?php foreach ($maquinas as $m): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <a href="/ingenieria/seleccionar-maquina.php?modo=nuevo&ma_id=<?= $m['ma_id'] ?>" class="btn-hoja btn-action-hoja">
                                        📝 Hoja de proceso
                                    </a>
                                </td>
                                <td><?= date('d/m/Y', strtotime($m['ma_fecha'])) ?></td>
                                <td><?= htmlspecialchars($m['ma_marca']) ?></td>
                                <td><?= htmlspecialchars($m['ma_modelo']) ?></td>
                                <?php if ($rol == 1): ?><td><?= htmlspecialchars($m['nombre_empresa']) ?></td><?php endif; ?>
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
    // --- LÓGICA DE FILTRADO Y PAGINACIÓN ---
    (function () {
        const table = document.getElementById('tablaMaquinas');
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
                    // Col 0 es Acciones. Col 1 es Fecha, 2 Marca, 3 Modelo.
                    if (campo === 'all') {
                        // Buscamos a partir de la columna 1 para ignorar el texto del botón
                        return celdas.slice(1).some(td => td.innerText.toLowerCase().includes(term));
                    } else {
                        const idx = parseInt(campo);
                        if (celda = celdas[idx]) {
                            return celda.innerText.toLowerCase().includes(term);
                        }
                        return false;
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
        pageSizeSelect.addEventListener('change', () => { 
            pageSize = parseInt(pageSizeSelect.value, 10); 
            currentPage = 1; 
            renderPage(); 
        });
        
        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
        nextBtn.addEventListener('click', () => { 
            const total = filteredRows.length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
            if (currentPage < totalPages) { currentPage++; renderPage(); } 
        });

        // Funciones de Exportación
        if(btnExportCSV) btnExportCSV.addEventListener('click', () => {
            const t = table.cloneNode(true);
            if(t.rows[0].cells[0].innerText.includes('Acciones')) {
                Array.from(t.rows).forEach(r => r.deleteCell(0));
            }
            XLSX.writeFile(XLSX.utils.table_to_book(t), "Maquinas_Proceso.xlsx");
        });

        if(btnExportPDF) btnExportPDF.addEventListener('click', () => {
            const doc = new window.jspdf.jsPDF('l', 'mm', 'a4');
            doc.text("Listado de Máquinas - Proceso", 14, 15);
            doc.autoTable({ 
                html: '#tablaMaquinas', 
                startY: 20, 
                didParseCell: d => { if(d.column.index === 0 && d.section === 'body') d.cell.text = ''; } 
            });
            doc.save('Maquinas_Proceso.pdf');
        });

        renderPage();
    })();
</script>
<?php includeSidebar(); ?>
</body>
</html>