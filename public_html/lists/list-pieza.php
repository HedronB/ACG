<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

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
        INNER JOIN empresas e ON p.pi_empresa = e.em_id";

$where  = "";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where = " WHERE p.pi_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where = " WHERE p.pi_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY p.pi_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$piezas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Listado de piezas</title>
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
                <h1>Listado de piezas</h1>
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
                        <option value="1">Código producto</option>
                        <option value="2">Molde</option>
                        <option value="3">Descripción</option>
                        <option value="4">Resina</option>
                        <option value="5">Espesor</option>
                        <option value="6">Área proyectada</option>
                        <option value="7">Color</option>
                        <option value="8">Tipo empaque</option>
                        <option value="9">Piezas</option>
                        <option value="10">Caja no. pzs</option>
                        <option value="11">Tamaño caja</option>
                        <option value="12">Bolsa 1</option>
                        <option value="13">Bolsa 2</option>
                        <option value="14">Tarima no. cajas</option>
                        <option value="15">Usuario</option>
                        <option value="16">Empresa</option>
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
                <?php if (empty($piezas)): ?>
                    <p>No hay piezas registradas para los criterios de búsqueda.</p>
                <?php else: ?>
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros" id="tablaPiezas">
                            <thead>
                                <tr>
                                    <th>Fecha registro</th>
                                    <th>Código producto</th>
                                    <th>Molde</th>
                                    <th>Descripción</th>
                                    <th>Resina</th>
                                    <th>Espesor</th>
                                    <th>Área proyectada</th>
                                    <th>Color</th>
                                    <th>Tipo empaque</th>
                                    <th>Piezas</th>
                                    <th>Caja no. pzs</th>
                                    <th>Tamaño caja</th>
                                    <th>Bolsa 1</th>
                                    <th>Bolsa 2</th>
                                    <th>Tarima no. cajas</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($piezas as $p): ?>
                                    <tr data-id="<?= (int)$p['pi_id'] ?>">
                                        <td><?= htmlspecialchars($p['pi_fecha']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_cod_prod']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_molde']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_descripcion']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_resina']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_espesor']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_area_proy']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_color']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_tipo_empaque']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_piezas']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_caja_no_pzs']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_caja_tamano']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_bolsa1']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_bolsa2']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_tarima_no_cajas']) ?></td>
                                        <td><?= htmlspecialchars($p['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($p['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <a href="editar_pieza.php?id=<?= (int)$p['pi_id'] ?>" class="btn btn-primary" style="font-size:0.8em;">Editar</a>
                                                <a href="eliminar_pieza.php?id=<?= (int)$p['pi_id'] ?>"
                                                class="btn btn-danger"
                                                style="font-size:0.8em;"
                                                onclick="return confirm('¿Seguro que desea eliminar esta pieza?');">
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
            const table = document.getElementById('tablaPiezas');
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
                exportTableToCSV('piezas.csv');
            });

            btnExportPDF.addEventListener('click', () => {
                window.print();
            });

            renderPage();
        })();
    </script>
</body>
</html>
