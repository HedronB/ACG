<?php
session_start();
require_once "protect.php";
require_once "config/db.php";

if (!isset($_SESSION['id'], $_SESSION['rol'], $_SESSION['empresa'])) {
    header("Location: log.php?error=Sesión no válida");
    exit();
}

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

$sql = "SELECT 
            m.mo_id,
            m.mo_fecha,
            m.mo_no_pieza,
            m.mo_numero,
            m.mo_ancho,
            m.mo_alto,
            m.mo_largo,
            m.mo_placas_voladas,
            m.mo_anillo_centrador,
            m.mo_no_circ_agua,
            m.mo_peso,
            m.mo_apert_min,
            m.mo_abierto,
            m.mo_tipo_colada,
            m.mo_no_zonas,
            m.mo_no_cavidades,
            m.mo_peso_pieza,
            m.mo_puert_cavidad,
            m.mo_no_coladas,
            m.mo_peso_colada,
            m.mo_peso_disparo,
            m.mo_noyos,
            m.mo_entr_aire,
            m.mo_thermoreguladores,
            m.mo_valve_gates,
            m.mo_tiempo_ciclo,
            m.mo_cavidades_activas,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM moldes m
        INNER JOIN usuarios u ON m.mo_usuario = u.us_id
        INNER JOIN empresas e ON m.mo_empresa = e.em_id";

$where  = "";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where = " WHERE m.mo_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where = " WHERE m.mo_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY m.mo_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$moldes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$puedeEditarEliminar = ($rol == 1 || $rol == 2);
$menu_retorno = "";

switch ($_SESSION['rol']) {
    case 1:
        $menu_retorno = "admin/menu_admin.php";
        break;

    case 2:
        $menu_retorno = "user/menu_user.php";
        break;

    case 3:
        $menu_retorno = "user/menu_user.php";
        break;

    default:
        $menu_retorno = "index.php";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de moldes</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
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
            <a href="registros.php">
                <img src="imagenes/logo.png" alt="Logo de la Empresa" class="header-logo">
            </a>
            <a href="registros.php">
                <h1>Listado de moldes</h1>
            </a>
        </div>

        <div>
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
                        <option value="1">No. pieza</option>
                        <option value="2">No. molde</option>
                        <option value="3">Ancho</option>
                        <option value="4">Alto</option>
                        <option value="5">Largo</option>
                        <option value="6">Placas voladas</option>
                        <option value="7">Anillo centrador</option>
                        <option value="8">No. circ. agua</option>
                        <option value="9">Peso</option>
                        <option value="10">Apertura mín.</option>
                        <option value="11">Abierto</option>
                        <option value="12">Tipo colada</option>
                        <option value="13">No. zonas</option>
                        <option value="14">No. cavidades</option>
                        <option value="15">Peso pieza</option>
                        <option value="16">Puer. por cavidad</option>
                        <option value="17">No. coladas</option>
                        <option value="18">Peso colada</option>
                        <option value="19">Peso disparo</option>
                        <option value="20">Noyos</option>
                        <option value="21">Entrada aire</option>
                        <option value="22">Thermoreguladores</option>
                        <option value="23">Valve gates</option>
                        <option value="24">Tiempo ciclo</option>
                        <option value="25">Cavidades activas</option>
                        <option value="26">Usuario</option>
                        <option value="27">Empresa</option>
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
                <?php if (empty($moldes)): ?>
                    <p>No hay moldes registrados para los criterios de búsqueda.</p>
                <?php else: ?>
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros" id="tablaMoldes">
                            <thead>
                                <tr>
                                    <th>Fecha registro</th>
                                    <th>No. pieza</th>
                                    <th>No. molde</th>
                                    <th>Ancho</th>
                                    <th>Alto</th>
                                    <th>Largo</th>
                                    <th>Placas voladas</th>
                                    <th>Anillo centrador</th>
                                    <th>No. circ. agua</th>
                                    <th>Peso</th>
                                    <th>Apertura mín.</th>
                                    <th>Abierto</th>
                                    <th>Tipo colada</th>
                                    <th>No. zonas</th>
                                    <th>No. cavidades</th>
                                    <th>Peso pieza</th>
                                    <th>Puer. por cavidad</th>
                                    <th>No. coladas</th>
                                    <th>Peso colada</th>
                                    <th>Peso disparo</th>
                                    <th>Noyos</th>
                                    <th>Entrada aire</th>
                                    <th>Thermoreguladores</th>
                                    <th>Valve gates</th>
                                    <th>Tiempo ciclo</th>
                                    <th>Cavidades activas</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moldes as $m): ?>
                                    <tr data-id="<?= (int)$m['mo_id'] ?>">
                                        <td><?= htmlspecialchars($m['mo_fecha']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_pieza']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_numero']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_ancho']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_alto']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_largo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_placas_voladas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_anillo_centrador']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_circ_agua']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_apert_min']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_abierto']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_tipo_colada']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_zonas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_cavidades']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_pieza']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_puert_cavidad']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_coladas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_colada']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_disparo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_noyos']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_entr_aire']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_thermoreguladores']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_valve_gates']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_tiempo_ciclo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_cavidades_activas']) ?></td>
                                        <td><?= htmlspecialchars($m['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($m['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <a href="editar_molde.php?id=<?= (int)$m['mo_id'] ?>" class="btn btn-primary" style="font-size:0.8em;">Editar</a>
                                                <a href="eliminar_molde.php?id=<?= (int)$m['mo_id'] ?>"
                                                class="btn btn-danger"
                                                style="font-size:0.8em;"
                                                onclick="return confirm('¿Seguro que desea eliminar este molde?');">
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
            const table = document.getElementById('tablaMoldes');
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
                exportTableToCSV('moldes.csv');
            });

            btnExportPDF.addEventListener('click', () => {
                window.print();
            });

            renderPage();
        })();
    </script>
</body>
</html>
