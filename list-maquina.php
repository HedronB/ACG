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
            m.ma_id,
            m.ma_usuario,
            m.ma_empresa,
            m.ma_fecha,
            m.ma_marca,
            m.ma_modelo,
            m.ma_fecha_fabr,
            m.ma_ubicacion,
            m.ma_tipo,
            m.ma_ancho,
            m.ma_largo,
            m.ma_alto,
            m.ma_peso,
            m.ma_vol_tanq_aceite,
            m.ma_tonelaje,
            m.ma_dist_barras,
            m.ma_tam_platina,
            m.ma_anillo_centr,
            m.ma_alt_max_molde,
            m.ma_apert_max,
            m.ma_alt_min_molde,
            m.ma_tipo_sujecion,
            m.ma_molde_chico,
            m.ma_botado_patron,
            m.ma_botado_fuerza,
            m.ma_botado_carrera,
            m.ma_tam_unid_inyec,
            m.ma_vol_inyec,
            m.ma_diam_husillo,
            m.ma_carga_max,
            m.ma_ld,
            m.ma_tipo_husillo,
            m.ma_max_pres_inyec,
            m.ma_max_contrapres,
            m.ma_max_revol,
            m.ma_max_vel_inyec,
            m.ma_valv_shut_off,
            m.ma_carga_vuelo,
            m.ma_fuerza_apoyo,
            m.ma_noyos,
            m.ma_no_valv_aire,
            m.ma_tipo_valv_aire,
            m.ma_secador,
            m.ma_termoreguladores,
            m.ma_cargador,
            m.ma_canal_caliente,
            m.ma_robot,
            m.ma_acumul_hidr,
            m.ma_voltaje,
            m.ma_calentamiento,
            m.ma_tam_motor_1,
            m.ma_tam_motor_2,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM maquinas m
        INNER JOIN usuarios u ON m.ma_usuario = u.us_id
        INNER JOIN empresas e ON m.ma_empresa = e.em_id";

$where  = "";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where = " WHERE m.ma_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where = " WHERE m.ma_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY m.ma_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Listado de máquinas</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    <style>
        .header {
            justify-content: space-between;
        }
        .tabla-registros td,
        .tabla-registros th {
            vertical-align: middle;
            font-size: 0.8em;
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

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }
        .modal-backdrop.active {
            display: flex;
        }
        .modal {
            background: #ffffff;
            border-radius: 8px;
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.1em;
        }
        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
        }
        .modal-body {
            margin-bottom: 15px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .modal .input-group {
            margin-bottom: 10px;
        }
        .modal label {
            display: block;
            font-size: 0.8em;
            margin-bottom: 2px;
        }
        .modal input,
        .modal select {
            width: 100%;
            padding: 6px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 0.8em;
        }

        @media print {
            header, footer, .filtros-container, .pagination-container, .modal-backdrop {
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
            <h1>Listado de máquinas</h1>
        </a>
    </div>

    <div>
        <a href="form-maquina.php" class="back-button">➕ Nueva máquina</a>
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
                    <option value="0">Fecha</option>
                    <option value="1">Marca</option>
                    <option value="2">Modelo</option>
                    <option value="3">Fecha fabricación</option>
                    <option value="4">Ubicación</option>
                    <option value="5">Tipo</option>
                    <option value="6">Ancho</option>
                    <option value="7">Largo</option>
                    <option value="8">Alto</option>
                    <option value="9">Peso</option>
                    <option value="10">Tanque aceite</option>
                    <option value="11">Tonelaje</option>
                    <option value="12">Dist. barras</option>
                    <option value="13">Tamaño platina</option>
                    <option value="14">Anillo centrador</option>
                    <option value="15">Alt. máx. molde</option>
                    <option value="16">Apertura máx.</option>
                    <option value="17">Alt. mín. molde</option>
                    <option value="18">Tipo sujeción</option>
                    <option value="19">Molde chico</option>
                    <option value="20">Botado patrón</option>
                    <option value="21">Botado fuerza</option>
                    <option value="22">Botado carrera</option>
                    <option value="23">Tamaño unid. inyec.</option>
                    <option value="24">Vol. inyección</option>
                    <option value="25">Diám. husillo</option>
                    <option value="26">Carga máx.</option>
                    <option value="27">L/D</option>
                    <option value="28">Tipo husillo</option>
                    <option value="29">Pres. inyec.</option>
                    <option value="30">Contrapresión</option>
                    <option value="31">Revoluciones</option>
                    <option value="32">Vel. inyec.</option>
                    <option value="33">Válv. shut-off</option>
                    <option value="34">Carga vuelo</option>
                    <option value="35">Fuerza apoyo</option>
                    <option value="36">Noyos</option>
                    <option value="37">No. válv. aire</option>
                    <option value="38">Tipo válv. aire</option>
                    <option value="39">Secador</option>
                    <option value="40">Termoreguladores</option>
                    <option value="41">Cargador</option>
                    <option value="42">Canal caliente</option>
                    <option value="43">Robot</option>
                    <option value="44">Acumulador hidr.</option>
                    <option value="45">Voltaje</option>
                    <option value="46">Calentamiento</option>
                    <option value="47">Tamaño motor 1</option>
                    <option value="48">Tamaño motor 2</option>
                    <option value="49">Usuario</option>
                    <option value="50">Empresa</option>
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
            <?php if (empty($maquinas)): ?>
                <p>No hay máquinas registradas para los criterios de búsqueda.</p>
            <?php else: ?>
                <div class="tabla-container-scroll">
                    <table class="tabla-registros" id="tablaMaquinas">
                        <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Fecha fabricación</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Ancho</th>
                            <th>Largo</th>
                            <th>Alto</th>
                            <th>Peso</th>
                            <th>Tanque aceite</th>
                            <th>Tonelaje</th>
                            <th>Dist. barras</th>
                            <th>Tamaño platina</th>
                            <th>Anillo centrador</th>
                            <th>Alt. máx. molde</th>
                            <th>Apertura máx.</th>
                            <th>Alt. mín. molde</th>
                            <th>Tipo sujeción</th>
                            <th>Molde chico</th>
                            <th>Botado patrón</th>
                            <th>Botado fuerza</th>
                            <th>Botado carrera</th>
                            <th>Tam. unid. inyec.</th>
                            <th>Vol. inyección</th>
                            <th>Diám. husillo</th>
                            <th>Carga máx.</th>
                            <th>L/D</th>
                            <th>Tipo husillo</th>
                            <th>Max pres. inyec.</th>
                            <th>Max contrapres.</th>
                            <th>Max revol.</th>
                            <th>Max vel. inyec.</th>
                            <th>Válv. shut-off</th>
                            <th>Carga vuelo</th>
                            <th>Fuerza apoyo</th>
                            <th>Noyos</th>
                            <th>No. válv. aire</th>
                            <th>Tipo válv. aire</th>
                            <th>Secador</th>
                            <th>Termoreguladores</th>
                            <th>Cargador</th>
                            <th>Canal caliente</th>
                            <th>Robot</th>
                            <th>Acumulador hidr.</th>
                            <th>Voltaje</th>
                            <th>Calentamiento</th>
                            <th>Tam. motor 1</th>
                            <th>Tam. motor 2</th>
                            <th>Usuario</th>
                            <th>Empresa</th>
                            <?php if ($puedeEditarEliminar): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($maquinas as $m): ?>
                            <tr
                                data-id="<?= (int)$m['ma_id'] ?>"
                                data-maquina='<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'
                            >
                                <td><?= htmlspecialchars($m['ma_fecha']) ?></td>
                                <td><?= htmlspecialchars($m['ma_marca']) ?></td>
                                <td><?= htmlspecialchars($m['ma_modelo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_fecha_fabr']) ?></td>
                                <td><?= htmlspecialchars($m['ma_ubicacion']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tipo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_ancho']) ?></td>
                                <td><?= htmlspecialchars($m['ma_largo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_alto']) ?></td>
                                <td><?= htmlspecialchars($m['ma_peso']) ?></td>
                                <td><?= htmlspecialchars($m['ma_vol_tanq_aceite']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tonelaje']) ?></td>
                                <td><?= htmlspecialchars($m['ma_dist_barras']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tam_platina']) ?></td>
                                <td><?= htmlspecialchars($m['ma_anillo_centr']) ?></td>
                                <td><?= htmlspecialchars($m['ma_alt_max_molde']) ?></td>
                                <td><?= htmlspecialchars($m['ma_apert_max']) ?></td>
                                <td><?= htmlspecialchars($m['ma_alt_min_molde']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tipo_sujecion']) ?></td>
                                <td><?= htmlspecialchars($m['ma_molde_chico']) ?></td>
                                <td><?= htmlspecialchars($m['ma_botado_patron']) ?></td>
                                <td><?= htmlspecialchars($m['ma_botado_fuerza']) ?></td>
                                <td><?= htmlspecialchars($m['ma_botado_carrera']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tam_unid_inyec']) ?></td>
                                <td><?= htmlspecialchars($m['ma_vol_inyec']) ?></td>
                                <td><?= htmlspecialchars($m['ma_diam_husillo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_carga_max']) ?></td>
                                <td><?= htmlspecialchars($m['ma_ld']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tipo_husillo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_max_pres_inyec']) ?></td>
                                <td><?= htmlspecialchars($m['ma_max_contrapres']) ?></td>
                                <td><?= htmlspecialchars($m['ma_max_revol']) ?></td>
                                <td><?= htmlspecialchars($m['ma_max_vel_inyec']) ?></td>
                                <td><?= htmlspecialchars($m['ma_valv_shut_off']) ?></td>
                                <td><?= htmlspecialchars($m['ma_carga_vuelo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_fuerza_apoyo']) ?></td>
                                <td><?= htmlspecialchars($m['ma_noyos']) ?></td>
                                <td><?= htmlspecialchars($m['ma_no_valv_aire']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tipo_valv_aire']) ?></td>
                                <td><?= htmlspecialchars($m['ma_secador']) ?></td>
                                <td><?= htmlspecialchars($m['ma_termoreguladores']) ?></td>
                                <td><?= htmlspecialchars($m['ma_cargador']) ?></td>
                                <td><?= htmlspecialchars($m['ma_canal_caliente']) ?></td>
                                <td><?= htmlspecialchars($m['ma_robot']) ?></td>
                                <td><?= htmlspecialchars($m['ma_acumul_hidr']) ?></td>
                                <td><?= htmlspecialchars($m['ma_voltaje']) ?></td>
                                <td><?= htmlspecialchars($m['ma_calentamiento']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tam_motor_1']) ?></td>
                                <td><?= htmlspecialchars($m['ma_tam_motor_2']) ?></td>
                                <td><?= htmlspecialchars($m['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($m['nombre_empresa']) ?></td>
                                <?php if ($puedeEditarEliminar): ?>
                                    <td>
                                        <button type="button"
                                                class="btn btn-primary btn-edit"
                                                style="font-size:0.75em;"
                                                data-id="<?= (int)$m['ma_id'] ?>">
                                            Editar
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-delete"
                                                style="font-size:0.75em;"
                                                data-id="<?= (int)$m['ma_id'] ?>">
                                            Eliminar
                                        </button>
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

<?php if ($puedeEditarEliminar): ?>
    <div class="modal-backdrop" id="modalEditar">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar máquina</h2>
                <button type="button" class="modal-close" data-close="modalEditar">&times;</button>
            </div>
            <form id="formEditarMaquina" method="POST" action="update_maquina.php">
                <div class="modal-body">
                    <input type="hidden" name="ma_id" id="edit_ma_id">

                    <div class="input-group">
                        <label for="edit_ma_fecha">Fecha</label>
                        <input type="datetime-local" name="ma_fecha" id="edit_ma_fecha">
                    </div>

                    <div class="input-group">
                        <label for="edit_ma_marca">Marca</label>
                        <input type="text" name="ma_marca" id="edit_ma_marca">
                    </div>

                    <div class="input-group">
                        <label for="edit_ma_modelo">Modelo</label>
                        <input type="text" name="ma_modelo" id="edit_ma_modelo">
                    </div>

                    <div class="input-group">
                        <label for="edit_ma_fecha_fabr">Fecha fabricación</label>
                        <input type="date" name="ma_fecha_fabr" id="edit_ma_fecha_fabr">
                    </div>

                    <div class="input-group">
                        <label for="edit_ma_ubicacion">Ubicación</label>
                        <input type="text" name="ma_ubicacion" id="edit_ma_ubicacion">
                    </div>

                    <div class="input-group">
                        <label for="edit_ma_tipo">Tipo</label>
                        <input type="number" step="0.01" name="ma_tipo" id="edit_ma_tipo">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="back-button" data-close="modalEditar">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="modalEliminar">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <h2>Eliminar máquina</h2>
                <button type="button" class="modal-close" data-close="modalEliminar">&times;</button>
            </div>
            <form id="formEliminarMaquina" method="POST" action="delete_maquina.php">
                <div class="modal-body">
                    <input type="hidden" name="ma_id" id="delete_ma_id">
                    <p>¿Seguro que deseas eliminar esta máquina?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="back-button" data-close="modalEliminar">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
    (function () {
        const table = document.getElementById('tablaMaquinas');
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
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        btnExportCSV.addEventListener('click', () => {
            exportTableToCSV('maquinas.csv');
        });

        btnExportPDF.addEventListener('click', () => {
            window.print();
        });

        renderPage();

        <?php if ($puedeEditarEliminar): ?>
        const body = document.body;

        function openModal(id) {
            const backdrop = document.getElementById(id);
            if (!backdrop) return;
            backdrop.classList.add('active');
            body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const backdrop = document.getElementById(id);
            if (!backdrop) return;
            backdrop.classList.remove('active');
            body.style.overflow = '';
        }

        document.querySelectorAll('.modal-close, [data-close]').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-close');
                if (id) closeModal(id);
            });
        });

        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        });

        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const row = table.querySelector('tr[data-id="' + id + '"]');
                if (!row) return;

                const dataJson = row.getAttribute('data-maquina');
                if (!dataJson) return;

                let data;
                try {
                    data = JSON.parse(dataJson);
                } catch (e) {
                    console.error('Error parseando datos de máquina', e);
                    return;
                }

                document.getElementById('edit_ma_id').value = data.ma_id || '';

                if (data.ma_fecha) {
                    const dt = data.ma_fecha.replace(' ', 'T').slice(0, 16);
                    document.getElementById('edit_ma_fecha').value = dt;
                } else {
                    document.getElementById('edit_ma_fecha').value = '';
                }

                document.getElementById('edit_ma_marca').value       = data.ma_marca || '';
                document.getElementById('edit_ma_modelo').value      = data.ma_modelo || '';

                if (data.ma_fecha_fabr) {
                    document.getElementById('edit_ma_fecha_fabr').value = data.ma_fecha_fabr.slice(0, 10);
                } else {
                    document.getElementById('edit_ma_fecha_fabr').value = '';
                }

                document.getElementById('edit_ma_ubicacion').value   = data.ma_ubicacion || '';
                document.getElementById('edit_ma_tipo').value        = data.ma_tipo || '';

                openModal('modalEditar');
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                document.getElementById('delete_ma_id').value = id;
                openModal('modalEliminar');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.active').forEach(m => {
                    m.classList.remove('active');
                });
                body.style.overflow = '';
            }
        });
        <?php endif; ?>
    })();
</script>
</body>
</html>
