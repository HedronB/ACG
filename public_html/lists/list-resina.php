<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

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
        INNER JOIN empresas e ON r.re_empresa = e.em_id";

$where  = "";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where = " WHERE r.re_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where = " WHERE r.re_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY r.re_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$puedeEditarEliminar = ($rol == 1 || $rol == 2);
$menu_retorno = "/";

switch ($_SESSION['rol']) {
    case 1:
        $menu_retorno = "/admin/menu_admin.php";
        break;

    case 2:
        $menu_retorno = "/user/menu_user.php";
        break;

    case 3:
        $menu_retorno = "/user/menu_user.php";
        break;

    default:
        $menu_retorno = "/index.php";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de resinas</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <style>
        .header {
            justify-content: space-between;
        }
        .tabla-registros td,
        .tabla-registros th {
            vertical-align: middle;
            font-size: 0.85em;
            white-space: nowrap;
        }
        .tabla-registros {
            width: 100%;
        }
        .tabla-container-scroll {
            overflow-x: auto;
        }
        .filtros-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .filtros-container input[type="text"],
        .filtros-container select {
            padding: 6px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 0.9em;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pagination-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .pagination-buttons button {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background-color: #f3f4f6;
            cursor: pointer;
            font-size: 0.85em;
        }
        .pagination-buttons button[disabled] {
            opacity: 0.5;
            cursor: default;
        }
        .page-size-select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 0.85em;
        }
        .pagination-info {
            font-size: 0.85em;
        }
        .export-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-export {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background-color: #e5e7eb;
            cursor: pointer;
            font-size: 0.85em;
        }

        @media print {
            header, footer, .filtros-container, .pagination-container {
                display: none !important;
            }
            .tabla-container-scroll {
                overflow: visible;
            }
            body {
                margin: 10px;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-title-group">
            <a href="/registros.php">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="/registros.php">
                <h1>Listado de resinas</h1>
            </a>
        </div>

        <div>
            <!-- <a href="form-maquina.php" class="back-button">➕ Nueva máquina</a> -->
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
                        <option value="0">Fecha registro</option>
                        <option value="1">Código interno</option>
                        <option value="2">Tipo resina</option>
                        <option value="3">Grado</option>
                        <option value="4">% reciclado</option>
                        <option value="5">Temp. masa máx.</option>
                        <option value="6">Temp. masa mín.</option>
                        <option value="7">Temp. ref. máx.</option>
                        <option value="8">Temp. ref. mín.</option>
                        <option value="9">Secado temp.</option>
                        <option value="10">Secado tiempo</option>
                        <option value="11">Densidad</option>
                        <option value="12">Factor corrección</option>
                        <option value="13">Carga</option>
                        <option value="14">Usuario</option>
                        <option value="15">Empresa</option>
                    </select>
                </label>

                <label>
                    Registros por página:
                    <select id="pageSize" class="page-size-select">
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
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
                                    <th>Fecha registro</th>
                                    <th>Código interno</th>
                                    <th>Tipo resina</th>
                                    <th>Grado</th>
                                    <th>% reciclado</th>
                                    <th>Temp. masa máx.</th>
                                    <th>Temp. masa mín.</th>
                                    <th>Temp. ref. máx.</th>
                                    <th>Temp. ref. mín.</th>
                                    <th>Secado temp.</th>
                                    <th>Secado tiempo</th>
                                    <th>Densidad</th>
                                    <th>Factor corrección</th>
                                    <th>Carga</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resinas as $r): ?>
                                    <tr data-id="<?= (int)$r['re_id'] ?>">
                                        <td><?= htmlspecialchars($r['re_fecha']) ?></td>
                                        <td><?= htmlspecialchars($r['re_cod_int']) ?></td>
                                        <td><?= htmlspecialchars($r['re_tipo_resina']) ?></td>
                                        <td><?= htmlspecialchars($r['re_grado']) ?></td>
                                        <td><?= htmlspecialchars($r['re_porc_reciclado']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_masa_max']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_masa_min']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_ref_max']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_ref_min']) ?></td>
                                        <td><?= htmlspecialchars($r['re_sec_temp']) ?></td>
                                        <td><?= htmlspecialchars($r['re_sec_tiempo']) ?></td>
                                        <td><?= htmlspecialchars($r['re_densidad']) ?></td>
                                        <td><?= htmlspecialchars($r['re_factor_correccion']) ?></td>
                                        <td><?= htmlspecialchars($r['re_carga']) ?></td>
                                        <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($r['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <a href="editar_resina.php?id=<?= (int)$r['re_id'] ?>" class="btn btn-primary" style="font-size:0.8em;">Editar</a>
                                                <a href="eliminar_resina.php?id=<?= (int)$r['re_id'] ?>"
                                                class="btn btn-danger"
                                                style="font-size:0.8em;"
                                                onclick="return confirm('¿Seguro que desea eliminar esta resina?');">
                                                    Eliminar
                                                </a>
                                            </td>
                                        <?php endif; ?>
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

    <footer>
        <p>Método ACG</p>
    </footer>

    <script>
        (function () {
            const table = document.getElementById('tablaResinas');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            const filtroGlobal   = document.getElementById('filtroGlobal');
            const campoFiltro    = document.getElementById('campoFiltro');
            const pageSizeSelect = document.getElementById('pageSize');
            const prevBtn        = document.getElementById('prevPage');
            const nextBtn        = document.getElementById('nextPage');
            const info           = document.getElementById('paginationInfo');
            const btnExportCSV   = document.getElementById('btnExportCSV');
            const btnExportPDF   = document.getElementById('btnExportPDF');

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
                            const texto = celdas.map(td => td.innerText.toLowerCase()).join(' ');
                            return texto.includes(term);
                        } else {
                            const idx = parseInt(campo, 10);
                            if (idx >= 0 && idx < celdas.length) {
                                const texto = celdas[idx].innerText.toLowerCase();
                                return texto.includes(term);
                            }
                            return false;
                        }
                    });
                }
                currentPage = 1;
                renderPage();
            }

            function renderPage() {
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }

                const total = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                const pageRows = filteredRows.slice(start, end);

                pageRows.forEach(r => tbody.appendChild(r));

                const from = total === 0 ? 0 : start + 1;
                const to = Math.min(end, total);
                info.textContent = `Mostrando ${from}–${to} de ${total} registros (pág. ${currentPage} de ${totalPages})`;

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

            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderPage();
                }
            });

            nextBtn.addEventListener('click', () => {
                const total = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage();
                }
            });

            function exportTableToCSV(filename) {
                const visibleRows = filteredRows;
                const csvRows = [];
                const ths = table.querySelectorAll('thead th');
                const header = Array.from(ths).map(th => `"${th.innerText.replace(/"/g, '""')}"`);
                csvRows.push(header.join(';'));

                visibleRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const rowData = Array.from(cells).map(td => {
                        const text = td.innerText.replace(/\s+/g, ' ').trim();
                        return `"${text.replace(/"/g, '""')}"`;
                    });
                    csvRows.push(rowData.join(';'));
                });

                const csvString = csvRows.join('\r\n');
                const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            btnExportCSV.addEventListener('click', () => {
                exportTableToCSV('resinas.csv');
            });

            btnExportPDF.addEventListener('click', () => {
                window.print();
            });

            renderPage();
        })();
    </script>
</body>
</html>
