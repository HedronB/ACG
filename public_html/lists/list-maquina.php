<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

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

$where  = " WHERE m.ma_activo = 1";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where .= " AND m.ma_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where .= " AND m.ma_usuario = :usuario";
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
    <title>Listado de máquinas</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
<header class="header">
    <div class="header-title-group">
        <a href="/registros.php">
            <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
        </a>
        <a href="/registros.php">
            <h1>Listado de máquinas</h1>
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
<!-- ── Modal Editar Máquina ── -->
<div class="modal-backdrop" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar máquina</h2>
            <button type="button" class="modal-close" data-close="modalEditar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_ma_id">
            <div class="form-grid">
                <div class="input-group"><label>Marca</label><input type="text" id="edit_ma_marca"></div>
                <div class="input-group"><label>Modelo</label><input type="text" id="edit_ma_modelo"></div>
                <div class="input-group"><label>Fecha fabricación</label><input type="date" id="edit_ma_fecha_fabr"></div>
                <div class="input-group"><label>Ubicación</label><input type="text" id="edit_ma_ubicacion"></div>
                <div class="input-group"><label>Tipo de máquina</label>
                    <select id="edit_ma_tipo">
                        <option value="">-- Seleccionar --</option>
                        <option value="Hidráulica">Hidráulica</option>
                        <option value="Eléctrica">Eléctrica</option>
                    </select>
                </div>
                <div class="input-group"><label>Ancho (mm)</label><input type="number" step="0.01" id="edit_ma_ancho"></div>
                <div class="input-group"><label>Largo (mm)</label><input type="number" step="0.01" id="edit_ma_largo"></div>
                <div class="input-group"><label>Alto (mm)</label><input type="number" step="0.01" id="edit_ma_alto"></div>
                <div class="input-group"><label>Peso (kg)</label><input type="number" step="0.01" id="edit_ma_peso"></div>
                <div class="input-group"><label>Vol. tanque aceite</label><input type="number" step="0.01" id="edit_ma_vol_tanq_aceite"></div>
                <div class="input-group"><label>Tonelaje</label><input type="number" step="0.01" id="edit_ma_tonelaje"></div>
                <div class="input-group"><label>Dist. barras</label><input type="number" step="0.01" id="edit_ma_dist_barras"></div>
                <div class="input-group"><label>Tamaño platina</label><input type="number" step="0.01" id="edit_ma_tam_platina"></div>
                <div class="input-group"><label>Anillo centrador</label><input type="number" step="0.01" id="edit_ma_anillo_centr"></div>
                <div class="input-group"><label>Alt. máx. molde</label><input type="number" step="0.01" id="edit_ma_alt_max_molde"></div>
                <div class="input-group"><label>Apertura máx.</label><input type="number" step="0.01" id="edit_ma_apert_max"></div>
                <div class="input-group"><label>Alt. mín. molde</label><input type="number" step="0.01" id="edit_ma_alt_min_molde"></div>
                <div class="input-group"><label>Tipo sujeción</label><input type="text" id="edit_ma_tipo_sujecion"></div>
                <div class="input-group"><label>Molde chico</label><input type="number" step="0.01" id="edit_ma_molde_chico"></div>
                <div class="input-group"><label>Botado patrón</label><input type="text" id="edit_ma_botado_patron"></div>
                <div class="input-group"><label>Botado fuerza</label><input type="number" step="0.01" id="edit_ma_botado_fuerza"></div>
                <div class="input-group"><label>Botado carrera</label><input type="number" step="0.01" id="edit_ma_botado_carrera"></div>
                <div class="input-group"><label>Tam. unid. inyección</label><input type="number" step="0.01" id="edit_ma_tam_unid_inyec"></div>
                <div class="input-group"><label>Vol. inyección</label><input type="number" step="0.01" id="edit_ma_vol_inyec"></div>
                <div class="input-group"><label>Diám. husillo</label><input type="number" step="0.01" id="edit_ma_diam_husillo"></div>
                <div class="input-group"><label>Carga máx.</label><input type="number" step="0.01" id="edit_ma_carga_max"></div>
                <div class="input-group"><label>L/D</label><input type="text" id="edit_ma_ld"></div>
                <div class="input-group"><label>Tipo husillo</label><input type="text" id="edit_ma_tipo_husillo"></div>
                <div class="input-group"><label>Máx. pres. inyección</label><input type="number" step="0.01" id="edit_ma_max_pres_inyec"></div>
                <div class="input-group"><label>Máx. contrapresión</label><input type="number" step="0.01" id="edit_ma_max_contrapres"></div>
                <div class="input-group"><label>Máx. revoluciones</label><input type="number" step="0.01" id="edit_ma_max_revol"></div>
                <div class="input-group"><label>Máx. vel. inyección</label><input type="number" step="0.01" id="edit_ma_max_vel_inyec"></div>
                <div class="input-group"><label>Válv. shut-off</label><input type="text" id="edit_ma_valv_shut_off"></div>
                <div class="input-group"><label>Carga vuelo</label><input type="text" id="edit_ma_carga_vuelo"></div>
                <div class="input-group"><label>Fuerza apoyo</label><input type="number" step="0.01" id="edit_ma_fuerza_apoyo"></div>
                <div class="input-group"><label>Noyos</label><input type="number" id="edit_ma_noyos"></div>
                <div class="input-group"><label>No. válvulas aire</label><input type="number" id="edit_ma_no_valv_aire"></div>
                <div class="input-group"><label>Tipo válvulas aire</label><input type="text" id="edit_ma_tipo_valv_aire"></div>
                <div class="input-group"><label>Secador</label><input type="text" id="edit_ma_secador"></div>
                <div class="input-group"><label>Termoreguladores</label><input type="number" id="edit_ma_termoreguladores"></div>
                <div class="input-group"><label>Cargador</label><input type="text" id="edit_ma_cargador"></div>
                <div class="input-group"><label>Canal caliente</label><input type="number" id="edit_ma_canal_caliente"></div>
                <div class="input-group"><label>Robot</label><input type="text" id="edit_ma_robot"></div>
                <div class="input-group"><label>Acumulador hidr.</label><input type="text" id="edit_ma_acumul_hidr"></div>
                <div class="input-group"><label>Voltaje</label><input type="number" step="0.01" id="edit_ma_voltaje"></div>
                <div class="input-group"><label>Calentamiento</label><input type="number" step="0.01" id="edit_ma_calentamiento"></div>
                <div class="input-group"><label>Tamaño motor 1</label><input type="number" step="0.01" id="edit_ma_tam_motor_1"></div>
                <div class="input-group"><label>Tamaño motor 2</label><input type="number" step="0.01" id="edit_ma_tam_motor_2"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEditar">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarEdicion">Guardar cambios</button>
        </div>
    </div>
</div>

<!-- ── Modal Eliminar ── -->
<div class="modal-backdrop" id="modalEliminar">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>Eliminar máquina</h2>
            <button type="button" class="modal-close" data-close="modalEliminar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_ma_id">
            <p>¿Seguro que deseas eliminar esta máquina? La acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEliminar">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const table = document.getElementById('tablaMaquinas');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));

    const filtroGlobal   = document.getElementById('filtroGlobal');
    const campoFiltro    = document.getElementById('campoFiltro');
    const pageSizeSelect = document.getElementById('pageSize');
    const prevBtn        = document.getElementById('prevPage');
    const nextBtn        = document.getElementById('nextPage');
    const info           = document.getElementById('paginationInfo');
    const btnExportCSV   = document.getElementById('btnExportCSV');
    const btnExportPDF   = document.getElementById('btnExportPDF');

    let filteredRows = rows.slice();
    let currentPage  = 1;
    let pageSize     = parseInt(pageSizeSelect.value, 10);

    function aplicaFiltro() {
        const term  = filtroGlobal.value.toLowerCase().trim();
        const campo = campoFiltro.value;
        filteredRows = !term ? rows.slice() : rows.filter(row => {
            const celdas = Array.from(row.cells);
            if (campo === 'all') return celdas.map(td => td.innerText.toLowerCase()).join(' ').includes(term);
            const idx = parseInt(campo, 10);
            return (idx >= 0 && idx < celdas.length) ? celdas[idx].innerText.toLowerCase().includes(term) : false;
        });
        currentPage = 1;
        renderPage();
    }

    function renderPage() {
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
        const total      = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const end   = start + pageSize;
        filteredRows.slice(start, end).forEach(r => tbody.appendChild(r));
        const from = total === 0 ? 0 : start + 1;
        info.textContent = `Mostrando ${from}–${Math.min(end, total)} de ${total} registros (pág. ${currentPage} de ${totalPages})`;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages || total === 0;
    }

    filtroGlobal.addEventListener('input', aplicaFiltro);
    campoFiltro.addEventListener('change', aplicaFiltro);
    pageSizeSelect.addEventListener('change', () => { pageSize = parseInt(pageSizeSelect.value, 10); currentPage = 1; renderPage(); });
    prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
    nextBtn.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        if (currentPage < totalPages) { currentPage++; renderPage(); }
    });

    function getTableData() {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
        const dataRows = filteredRows.map(row =>
            Array.from(row.querySelectorAll('td')).map(td => td.innerText.replace(/\s+/g, ' ').trim())
        );
        return { headers, dataRows };
    }

    btnExportCSV.addEventListener('click', function () {
        const { headers, dataRows } = getTableData();
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...dataRows]);
        // Auto ancho de columnas
        const colWidths = headers.map((h, i) => ({
            wch: Math.min(40, Math.max(h.length, ...dataRows.map(r => (r[i] || '').length)))
        }));
        ws['!cols'] = colWidths;
        XLSX.utils.book_append_sheet(wb, ws, 'Datos');
        XLSX.writeFile(wb, 'maquinas.xlsx');
    });

    btnExportPDF.addEventListener('click', function () {
        const { headers, dataRows } = getTableData();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' });

        const pageW   = doc.internal.pageSize.getWidth();
        const margin  = 10;
        const usableW = pageW - margin * 2;

        // Calcular ancho de cada columna basado en contenido
        const colWidths = headers.map((h, i) => {
            const maxLen = Math.max(
                h.length,
                ...dataRows.map(r => (r[i] || '').length).slice(0, 50)
            );
            return Math.min(40, Math.max(8, maxLen * 1.8));
        });

        const totalW = colWidths.reduce((a, b) => a + b, 0);

        // Si todo cabe en una sola página de ancho, usar tableWidth normal
        // Si no, dividir en grupos de columnas que quepan
        const groups = [];
        let group = [], groupW = 0;
        for (let i = 0; i < headers.length; i++) {
            if (groupW + colWidths[i] > usableW && group.length > 0) {
                groups.push(group);
                group = [i];
                groupW = colWidths[i];
            } else {
                group.push(i);
                groupW += colWidths[i];
            }
        }
        if (group.length > 0) groups.push(group);

        let firstGroup = true;
        groups.forEach(colIdxs => {
            if (!firstGroup) doc.addPage();
            firstGroup = false;

            const gHeaders = colIdxs.map(i => headers[i]);
            const gData    = dataRows.map(row => colIdxs.map(i => row[i] || ''));
            const gWidths  = colIdxs.map(i => colWidths[i]);
            const scale    = usableW / gWidths.reduce((a, b) => a + b, 0);
            const finalW   = gWidths.map(w => w * scale);

            doc.setFontSize(10);
            doc.text('Listado de Máquinas', margin, margin - 2);

            doc.autoTable({
                head: [gHeaders],
                body: gData,
                startY: margin + 2,
                margin: { left: margin, right: margin },
                tableWidth: usableW,
                columnStyles: Object.fromEntries(finalW.map((w, i) => [i, { cellWidth: w }])),
                styles: {
                    fontSize: 7,
                    cellPadding: 1.5,
                    overflow: 'linebreak',
                    valign: 'middle',
                },
                headStyles: {
                    fillColor: [0, 0, 0],
                    textColor: 255,
                    fontStyle: 'bold',
                    fontSize: 7,
                },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                didDrawPage: function (data) {
                    const pageCount = doc.internal.getNumberOfPages();
                    doc.setFontSize(7);
                    doc.text(
                        `Pág. ${doc.internal.getCurrentPageInfo().pageNumber} de ${pageCount}`,
                        pageW - margin - 20,
                        doc.internal.pageSize.getHeight() - 5
                    );
                },
            });
        });

        doc.save('maquinas.pdf');
    });

    renderPage();

    <?php if ($puedeEditarEliminar): ?>
    // ── Modales ────────────────────────────────────────────
    const body = document.body;

    function openModal(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.add('active'); body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.remove('active'); body.style.overflow = ''; }
    }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', function () { closeModal(this.getAttribute('data-close')); });
    });
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) { this.classList.remove('active'); body.style.overflow = ''; }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
            body.style.overflow = '';
        }
    });

    // ── Mapeo de campos del modal ───────────────────────────
    const CAMPOS = [
        'ma_marca','ma_modelo','ma_fecha_fabr','ma_ubicacion','ma_tipo',
        'ma_ancho','ma_largo','ma_alto','ma_peso','ma_vol_tanq_aceite',
        'ma_tonelaje','ma_dist_barras','ma_tam_platina','ma_anillo_centr',
        'ma_alt_max_molde','ma_apert_max','ma_alt_min_molde','ma_tipo_sujecion',
        'ma_molde_chico','ma_botado_patron','ma_botado_fuerza','ma_botado_carrera',
        'ma_tam_unid_inyec','ma_vol_inyec','ma_diam_husillo','ma_carga_max',
        'ma_ld','ma_tipo_husillo','ma_max_pres_inyec','ma_max_contrapres',
        'ma_max_revol','ma_max_vel_inyec','ma_valv_shut_off','ma_carga_vuelo',
        'ma_fuerza_apoyo','ma_noyos','ma_no_valv_aire','ma_tipo_valv_aire',
        'ma_secador','ma_termoreguladores','ma_cargador','ma_canal_caliente',
        'ma_robot','ma_acumul_hidr','ma_voltaje','ma_calentamiento',
        'ma_tam_motor_1','ma_tam_motor_2'
    ];

    // Editar
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = table.querySelector(`tr[data-id="${this.dataset.id}"]`);
            if (!row) return;
            let data;
            try { data = JSON.parse(row.getAttribute('data-maquina')); } catch(e) { return; }

            document.getElementById('edit_ma_id').value = data.ma_id || '';
            CAMPOS.forEach(campo => {
                const el = document.getElementById('edit_' + campo);
                if (!el) return;
                let val = data[campo] ?? '';
                if (el.tagName === 'SELECT') {
                    el.value = val;
                } else if (el.type === 'date' && val) {
                    el.value = val.slice(0, 10);
                } else {
                    el.value = val;
                }
            });
            openModal('modalEditar');
        });
    });

    document.getElementById('btnGuardarEdicion').addEventListener('click', function () {
        const id = document.getElementById('edit_ma_id').value;
        if (!id) return;

        const payload = { ma_id: id };
        CAMPOS.forEach(campo => {
            const el = document.getElementById('edit_' + campo);
            if (el) payload[campo] = el.value;
        });

        fetch('/actions/update_maquina.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) { closeModal('modalEditar'); location.reload(); }
            else { alert(res.mensaje || 'Error al guardar'); }
        })
        .catch(() => alert('Error de comunicación con el servidor'));
    });

    // Eliminar
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('delete_ma_id').value = this.dataset.id;
            openModal('modalEliminar');
        });
    });

    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        const id = document.getElementById('delete_ma_id').value;
        if (!id) return;

        fetch('/actions/delete_maquina.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ma_id: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) { closeModal('modalEliminar'); location.reload(); }
            else { alert(res.mensaje || 'Error al eliminar'); }
        })
        .catch(() => alert('Error de comunicación con el servidor'));
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
