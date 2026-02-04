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
            $sql = "UPDATE maquinas SET 
                    ma_marca = :marca, ma_modelo = :modelo, ma_fecha_fabr = :fecha,
                    ma_ubicacion = :ubicacion, ma_tipo = :tipo, ma_tonelaje = :tonelaje,
                    ma_ancho = :ancho, ma_largo = :largo, ma_alto = :alto, ma_peso = :peso,
                    
                    ma_vol_tanq_aceite = :tanque, ma_dist_barras = :dist_barras, ma_tam_platina = :platina,
                    ma_anillo_centr = :anillo, ma_alt_max_molde = :alt_max, ma_apert_max = :apert_max,
                    ma_alt_min_molde = :alt_min, ma_tipo_sujecion = :sujecion, ma_molde_chico = :molde_chico,
                    ma_botado_patron = :bot_patron, ma_botado_fuerza = :bot_fuerza, ma_botado_carrera = :bot_carrera,
                    
                    ma_tam_unid_inyec = :tam_unid, ma_vol_inyec = :vol_inyec, ma_diam_husillo = :diam_husillo,
                    ma_carga_max = :carga_max, ma_ld = :ld, ma_tipo_husillo = :tipo_husillo,
                    ma_max_pres_inyec = :pres_inyec, ma_max_contrapres = :contrapres, ma_max_revol = :revol,
                    ma_max_vel_inyec = :vel_inyec, ma_valv_shut_off = :shut_off, ma_carga_vuelo = :carga_vuelo,
                    
                    ma_fuerza_apoyo = :fuerza_apoyo, ma_noyos = :noyos, ma_no_valv_aire = :no_valv_aire,
                    ma_tipo_valv_aire = :tipo_valv, ma_secador = :secador, ma_termoreguladores = :termo,
                    ma_cargador = :cargador, ma_canal_caliente = :canal, ma_robot = :robot,
                    ma_acumul_hidr = :acumul, ma_voltaje = :voltaje, ma_calentamiento = :calent,
                    ma_tam_motor_1 = :motor1, ma_tam_motor_2 = :motor2

                    WHERE ma_id = :id";
            
            if ($rol == 2) { $sql .= " AND ma_empresa = :empresa"; }

            $stmt = $conn->prepare($sql);
            
            $params = [
                ':marca' => $input['ma_marca'], ':modelo' => $input['ma_modelo'], ':fecha' => $input['ma_fecha_fabr'],
                ':ubicacion' => $input['ma_ubicacion'], ':tipo' => $input['ma_tipo'], ':tonelaje' => $input['ma_tonelaje'],
                ':ancho' => $input['ma_ancho'], ':largo' => $input['ma_largo'], ':alto' => $input['ma_alto'], ':peso' => $input['ma_peso'],
                
                ':tanque' => $input['ma_vol_tanq_aceite'], ':dist_barras' => $input['ma_dist_barras'], ':platina' => $input['ma_tam_platina'],
                ':anillo' => $input['ma_anillo_centr'], ':alt_max' => $input['ma_alt_max_molde'], ':apert_max' => $input['ma_apert_max'],
                ':alt_min' => $input['ma_alt_min_molde'], ':sujecion' => $input['ma_tipo_sujecion'], ':molde_chico' => $input['ma_molde_chico'],
                ':bot_patron' => $input['ma_botado_patron'], ':bot_fuerza' => $input['ma_botado_fuerza'], ':bot_carrera' => $input['ma_botado_carrera'],
                
                ':tam_unid' => $input['ma_tam_unid_inyec'], ':vol_inyec' => $input['ma_vol_inyec'], ':diam_husillo' => $input['ma_diam_husillo'],
                ':carga_max' => $input['ma_carga_max'], ':ld' => $input['ma_ld'], ':tipo_husillo' => $input['ma_tipo_husillo'],
                ':pres_inyec' => $input['ma_max_pres_inyec'], ':contrapres' => $input['ma_max_contrapres'], ':revol' => $input['ma_max_revol'],
                ':vel_inyec' => $input['ma_max_vel_inyec'], ':shut_off' => $input['ma_valv_shut_off'], ':carga_vuelo' => $input['ma_carga_vuelo'],
                
                ':fuerza_apoyo' => $input['ma_fuerza_apoyo'], ':noyos' => $input['ma_noyos'], ':no_valv_aire' => $input['ma_no_valv_aire'],
                ':tipo_valv' => $input['ma_tipo_valv_aire'], ':secador' => $input['ma_secador'], ':termo' => $input['ma_termoreguladores'],
                ':cargador' => $input['ma_cargador'], ':canal' => $input['ma_canal_caliente'], ':robot' => $input['ma_robot'],
                ':acumul' => $input['ma_acumul_hidr'], ':voltaje' => $input['ma_voltaje'], ':calent' => $input['ma_calentamiento'],
                ':motor1' => $input['ma_tam_motor_1'], ':motor2' => $input['ma_tam_motor_2'],

                ':id' => $input['ma_id']
            ];

            if ($rol == 2) { $params[':empresa'] = $empresaId; }

            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }

    if ($input['action'] === 'delete') {
        try {
            $sql = "DELETE FROM maquinas WHERE ma_id = :id";
            if ($rol == 2) { $sql .= " AND ma_empresa = :empresa"; }
            $stmt = $conn->prepare($sql);
            $params = [':id' => $input['id']];
            if ($rol == 2) { $params[':empresa'] = $empresaId; }
            echo json_encode(['success' => $stmt->execute($params)]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
        exit;
    }
}

$sql = "SELECT m.*, u.us_nombre as nombre_usuario, e.em_nombre as nombre_empresa 
        FROM maquinas m 
        LEFT JOIN usuarios u ON m.ma_usuario = u.us_id
        LEFT JOIN empresas e ON m.ma_empresa = e.em_id
        WHERE 1=1";

if ($rol == 2 || $rol == 3) { $sql .= " AND m.ma_empresa = :empresa"; }
$sql .= " ORDER BY m.ma_fecha DESC";

$stmt = $conn->prepare($sql);
if ($rol == 2 || $rol == 3) { $stmt->bindParam(':empresa', $empresaId, PDO::PARAM_INT); }
$stmt->execute();
$maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de máquinas</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
            min-width: 4500px;
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
            border: 1px solid #194bb1;
            background-color: #194bb1;
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

        .edit-input { display: none; width: 100%; padding: 4px; border: 1px solid #3b82f6; border-radius: 4px; box-sizing: border-box; }
        .view-data { display: block; }
        
        .btn { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75em; font-weight: bold; color:white; margin-right: 3px; }
        .btn-primary { background-color: #007bff; }
        .btn-success { background-color: #28a745; display: none; }
        .btn-danger { background-color: #dc3545; }

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
                    <option value="1">Marca</option>
                    <option value="2">Modelo</option>
                    <option value="4">Ubicación</option>
                    <option value="11">Tonelaje</option>
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
                            <?php if ($puedeEditarEliminar): ?> <th>Acciones</th> <?php endif; ?>
                            <th>Fecha</th><th>Marca</th><th>Modelo</th><th>Fecha fabricación</th><th>Ubicación</th><th>Tipo</th>
                            <th>Ancho</th><th>Largo</th><th>Alto</th><th>Peso</th>
                            <th>Tanque aceite</th><th>Tonelaje</th><th>Dist. barras</th><th>Tamaño platina</th><th>Anillo centrador</th>
                            <th>Alt. máx. molde</th><th>Apertura máx.</th><th>Alt. mín. molde</th><th>Tipo sujeción</th><th>Molde chico</th>
                            <th>Botado patrón</th><th>Botado fuerza</th><th>Botado carrera</th><th>Tam. unid. inyec.</th><th>Vol. inyección</th>
                            <th>Diám. husillo</th><th>Carga máx.</th><th>L/D</th><th>Tipo husillo</th><th>Max pres. inyec.</th>
                            <th>Max contrapres.</th><th>Max revol.</th><th>Max vel. inyec.</th><th>Válv. shut-off</th><th>Carga vuelo</th>
                            <th>Fuerza apoyo</th><th>Noyos</th><th>No. válv. aire</th><th>Tipo válv. aire</th><th>Secador</th>
                            <th>Termoreguladores</th><th>Cargador</th><th>Canal caliente</th><th>Robot</th><th>Acumulador hidr.</th>
                            <th>Voltaje</th><th>Calentamiento</th><th>Tam. motor 1</th><th>Tam. motor 2</th>
                            <th>Usuario</th><th>Empresa</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($maquinas as $m): ?>
                            <tr id="row-<?= $m['ma_id'] ?>">
                                
                                <?php if ($puedeEditarEliminar): ?>
                                    <td>
                                        <button class="btn btn-primary btn-edit" onclick="toggleEdit(<?= $m['ma_id'] ?>)">Editar</button>
                                        <button class="btn btn-success btn-save" onclick="guardarFila(<?= $m['ma_id'] ?>)">Guardar</button>
                                        <button class="btn btn-danger btn-delete" onclick="eliminarFila(<?= $m['ma_id'] ?>)">Eliminar</button>
                                    </td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($m['ma_fecha']) ?></td>
                                
                                <td><span class="view-data"><?= $m['ma_marca'] ?></span><input class="edit-input" id="marca_<?= $m['ma_id'] ?>" value="<?= $m['ma_marca'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_modelo'] ?></span><input class="edit-input" id="modelo_<?= $m['ma_id'] ?>" value="<?= $m['ma_modelo'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_fecha_fabr'] ?></span><input type="date" class="edit-input" id="fecha_<?= $m['ma_id'] ?>" value="<?= date('Y-m-d', strtotime($m['ma_fecha_fabr'])) ?>"></td>
                                <td><span class="view-data"><?= $m['ma_ubicacion'] ?></span><input class="edit-input" id="ubicacion_<?= $m['ma_id'] ?>" value="<?= $m['ma_ubicacion'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_tipo'] ?></span>
                                    <select class="edit-input" id="tipo_<?= $m['ma_id'] ?>">
                                        <option value="Hidráulica" <?= $m['ma_tipo']=='Hidráulica'?'selected':''?>>Hidráulica</option>
                                        <option value="Electrica" <?= $m['ma_tipo']=='Electrica'?'selected':''?>>Electrica</option>
                                    </select>
                                </td>

                                <td><span class="view-data"><?= $m['ma_ancho'] ?></span><input type="number" step="0.01" class="edit-input" id="ancho_<?= $m['ma_id'] ?>" value="<?= $m['ma_ancho'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_largo'] ?></span><input type="number" step="0.01" class="edit-input" id="largo_<?= $m['ma_id'] ?>" value="<?= $m['ma_largo'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_alto'] ?></span><input type="number" step="0.01" class="edit-input" id="alto_<?= $m['ma_id'] ?>" value="<?= $m['ma_alto'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_peso'] ?></span><input type="number" step="0.01" class="edit-input" id="peso_<?= $m['ma_id'] ?>" value="<?= $m['ma_peso'] ?>"></td>
                                
                                <td><span class="view-data"><?= $m['ma_vol_tanq_aceite'] ?></span><input class="edit-input" id="tanque_<?= $m['ma_id'] ?>" value="<?= $m['ma_vol_tanq_aceite'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tonelaje'] ?></span><input type="number" step="0.01" class="edit-input" id="tonelaje_<?= $m['ma_id'] ?>" value="<?= $m['ma_tonelaje'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_dist_barras'] ?></span><input class="edit-input" id="dist_barras_<?= $m['ma_id'] ?>" value="<?= $m['ma_dist_barras'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tam_platina'] ?></span><input class="edit-input" id="platina_<?= $m['ma_id'] ?>" value="<?= $m['ma_tam_platina'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_anillo_centr'] ?></span><input class="edit-input" id="anillo_<?= $m['ma_id'] ?>" value="<?= $m['ma_anillo_centr'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_alt_max_molde'] ?></span><input class="edit-input" id="alt_max_<?= $m['ma_id'] ?>" value="<?= $m['ma_alt_max_molde'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_apert_max'] ?></span><input class="edit-input" id="apert_max_<?= $m['ma_id'] ?>" value="<?= $m['ma_apert_max'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_alt_min_molde'] ?></span><input class="edit-input" id="alt_min_<?= $m['ma_id'] ?>" value="<?= $m['ma_alt_min_molde'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_tipo_sujecion'] ?></span>
                                    <select class="edit-input" id="sujecion_<?= $m['ma_id'] ?>">
                                        <option value="Tornillo" <?= $m['ma_tipo_sujecion']=='Tornillo'?'selected':''?>>Tornillo</option>
                                        <option value="Ranura" <?= $m['ma_tipo_sujecion']=='Ranura'?'selected':''?>>Ranura</option>
                                    </select>
                                </td>

                                <td><span class="view-data"><?= $m['ma_molde_chico'] ?></span><input class="edit-input" id="molde_chico_<?= $m['ma_id'] ?>" value="<?= $m['ma_molde_chico'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_botado_patron'] ?></span><input class="edit-input" id="bot_patron_<?= $m['ma_id'] ?>" value="<?= $m['ma_botado_patron'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_botado_fuerza'] ?></span><input class="edit-input" id="bot_fuerza_<?= $m['ma_id'] ?>" value="<?= $m['ma_botado_fuerza'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_botado_carrera'] ?></span><input class="edit-input" id="bot_carrera_<?= $m['ma_id'] ?>" value="<?= $m['ma_botado_carrera'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tam_unid_inyec'] ?></span><input class="edit-input" id="tam_unid_<?= $m['ma_id'] ?>" value="<?= $m['ma_tam_unid_inyec'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_vol_inyec'] ?></span>
                                    <input type="number" step="0.01" class="edit-input" id="vol_inyec_<?= $m['ma_id'] ?>" value="<?= $m['ma_vol_inyec'] ?>" readonly style="background-color: #f0f0f0;">
                                </td>

                                <td>
                                    <span class="view-data"><?= $m['ma_diam_husillo'] ?></span>
                                    <input type="number" step="0.01" class="edit-input" id="diam_husillo_<?= $m['ma_id'] ?>" value="<?= $m['ma_diam_husillo'] ?>" oninput="calcularVolumen(<?= $m['ma_id'] ?>)">
                                </td>
                                <td>
                                    <span class="view-data"><?= $m['ma_carga_max'] ?></span>
                                    <input type="number" step="0.01" class="edit-input" id="carga_max_<?= $m['ma_id'] ?>" value="<?= $m['ma_carga_max'] ?>" oninput="calcularVolumen(<?= $m['ma_id'] ?>)">
                                </td>

                                <td><span class="view-data"><?= $m['ma_ld'] ?></span><input class="edit-input" id="ld_<?= $m['ma_id'] ?>" value="<?= $m['ma_ld'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_tipo_husillo'] ?></span>
                                    <select class="edit-input" id="tipo_husillo_<?= $m['ma_id'] ?>">
                                        <option value="Estandar" <?= $m['ma_tipo_husillo']=='Estandar'?'selected':''?>>Estandar</option>
                                        <option value="Tratado" <?= $m['ma_tipo_husillo']=='Tratado'?'selected':''?>>Tratado</option>
                                        <option value="Bimetalico" <?= $m['ma_tipo_husillo']=='Bimetalico'?'selected':''?>>Bimetalico</option>
                                    </select>
                                </td>

                                <td><span class="view-data"><?= $m['ma_max_pres_inyec'] ?></span><input class="edit-input" id="pres_inyec_<?= $m['ma_id'] ?>" value="<?= $m['ma_max_pres_inyec'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_max_contrapres'] ?></span><input class="edit-input" id="contrapres_<?= $m['ma_id'] ?>" value="<?= $m['ma_max_contrapres'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_max_revol'] ?></span><input class="edit-input" id="revol_<?= $m['ma_id'] ?>" value="<?= $m['ma_max_revol'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_max_vel_inyec'] ?></span><input class="edit-input" id="vel_inyec_<?= $m['ma_id'] ?>" value="<?= $m['ma_max_vel_inyec'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_valv_shut_off'] ?></span>
                                    <select class="edit-input" id="shut_off_<?= $m['ma_id'] ?>"><option value="Si" <?= $m['ma_valv_shut_off']=='Si'?'selected':''?>>Si</option><option value="No" <?= $m['ma_valv_shut_off']=='No'?'selected':''?>>No</option></select>
                                </td>
                                <td>
                                    <span class="view-data"><?= $m['ma_carga_vuelo'] ?></span>
                                    <select class="edit-input" id="carga_vuelo_<?= $m['ma_id'] ?>"><option value="Si" <?= $m['ma_carga_vuelo']=='Si'?'selected':''?>>Si</option><option value="No" <?= $m['ma_carga_vuelo']=='No'?'selected':''?>>No</option></select>
                                </td>
                                
                                <td><span class="view-data"><?= $m['ma_fuerza_apoyo'] ?></span><input class="edit-input" id="fuerza_apoyo_<?= $m['ma_id'] ?>" value="<?= $m['ma_fuerza_apoyo'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_noyos'] ?></span><input class="edit-input" id="noyos_<?= $m['ma_id'] ?>" value="<?= $m['ma_noyos'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_no_valv_aire'] ?></span><input class="edit-input" id="no_valv_aire_<?= $m['ma_id'] ?>" value="<?= $m['ma_no_valv_aire'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tipo_valv_aire'] ?></span><input class="edit-input" id="tipo_valv_<?= $m['ma_id'] ?>" value="<?= $m['ma_tipo_valv_aire'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_secador'] ?></span>
                                    <select class="edit-input" id="secador_<?= $m['ma_id'] ?>">
                                        <option value="No" <?= $m['ma_secador']=='No'?'selected':''?>>No</option>
                                        <option value="Secador" <?= $m['ma_secador']=='Secador'?'selected':''?>>Secador</option>
                                        <option value="Dehumificador" <?= $m['ma_secador']=='Dehumificador'?'selected':''?>>Dehumificador</option>
                                    </select>
                                </td>
                                
                                <td><span class="view-data"><?= $m['ma_termoreguladores'] ?></span><input type="number" class="edit-input" id="termo_<?= $m['ma_id'] ?>" value="<?= $m['ma_termoreguladores'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_cargador'] ?></span>
                                    <select class="edit-input" id="cargador_<?= $m['ma_id'] ?>"><option value="Si" <?= $m['ma_cargador']=='Si'?'selected':''?>>Si</option><option value="No" <?= $m['ma_cargador']=='No'?'selected':''?>>No</option></select>
                                </td>
                                
                                <td><span class="view-data"><?= $m['ma_canal_caliente'] ?></span><input class="edit-input" id="canal_<?= $m['ma_id'] ?>" value="<?= $m['ma_canal_caliente'] ?>"></td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_robot'] ?></span>
                                    <select class="edit-input" id="robot_<?= $m['ma_id'] ?>">
                                        <option value="No" <?= $m['ma_robot']=='No'?'selected':''?>>No</option>
                                        <option value="Cartesiano" <?= $m['ma_robot']=='Cartesiano'?'selected':''?>>Cartesiano</option>
                                        <option value="Brazo Libre" <?= $m['ma_robot']=='Brazo Libre'?'selected':''?>>Brazo Libre</option>
                                    </select>
                                </td>
                                
                                <td>
                                    <span class="view-data"><?= $m['ma_acumul_hidr'] ?></span>
                                    <select class="edit-input" id="acumul_<?= $m['ma_id'] ?>"><option value="Si" <?= $m['ma_acumul_hidr']=='Si'?'selected':''?>>Si</option><option value="No" <?= $m['ma_acumul_hidr']=='No'?'selected':''?>>No</option></select>
                                </td>
                                
                                <td><span class="view-data"><?= $m['ma_voltaje'] ?></span><input class="edit-input" id="voltaje_<?= $m['ma_id'] ?>" value="<?= $m['ma_voltaje'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_calentamiento'] ?></span><input class="edit-input" id="calent_<?= $m['ma_id'] ?>" value="<?= $m['ma_calentamiento'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tam_motor_1'] ?></span><input class="edit-input" id="motor1_<?= $m['ma_id'] ?>" value="<?= $m['ma_tam_motor_1'] ?>"></td>
                                <td><span class="view-data"><?= $m['ma_tam_motor_2'] ?></span><input class="edit-input" id="motor2_<?= $m['ma_id'] ?>" value="<?= $m['ma_tam_motor_2'] ?>"></td>
                                
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
                    if (campo === 'all') {
                        const texto = celdas.map(td => td.innerText.toLowerCase()).join(' ');
                        return texto.includes(term);
                    } else {
                        const texto = celdas.map(td => td.innerText.toLowerCase()).join(' ');
                        return texto.includes(term);
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
            info.textContent = `Mostrando ${from}–${to} de ${total} registros (pág. ${currentPage} de ${totalPages})`;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages || total === 0;
        }

        filtroGlobal.addEventListener('input', aplicaFiltro);
        campoFiltro.addEventListener('change', aplicaFiltro);
        pageSizeSelect.addEventListener('change', () => { pageSize = parseInt(pageSizeSelect.value, 10); currentPage = 1; renderPage(); });
        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
        nextBtn.addEventListener('click', () => { const total = filteredRows.length; const totalPages = Math.max(1, Math.ceil(total / pageSize)); if (currentPage < totalPages) { currentPage++; renderPage(); } });

        btnExportCSV.addEventListener('click', () => {
            const t = table.cloneNode(true);
            if(t.rows[0].cells[0].innerText.includes('Acciones')) Array.from(t.rows).forEach(r => r.deleteCell(0));
            XLSX.utils.table_to_book(t); 
            XLSX.writeFile(XLSX.utils.table_to_book(t), "Maquinas.xlsx");
        });

        btnExportPDF.addEventListener('click', () => {
            const doc = new window.jspdf.jsPDF('l', 'mm', 'a0');
            doc.text("Listado de Máquinas", 14, 15);
            doc.autoTable({ html: '#tablaMaquinas', startY: 20, styles: { fontSize: 6 }, didParseCell: d => { if(d.column.index === 0 && d.section === 'body') d.cell.text = ''; } });
            doc.save('Maquinas.pdf');
        });

        renderPage();
    })();

    function calcularVolumen(id) {
        const diam = parseFloat(document.getElementById('diam_husillo_' + id).value) || 0;
        const carga = parseFloat(document.getElementById('carga_max_' + id).value) || 0;
        if (diam > 0 && carga > 0) {
            const volumen = (diam * diam * 0.7854 * carga) / 1000;
            document.getElementById('vol_inyec_' + id).value = volumen.toFixed(2);
        } else {
            document.getElementById('vol_inyec_' + id).value = "";
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
        row.style.backgroundColor = editing ? '#eef2f7' : '';
    }

    function guardarFila(id) {
        if (!confirm("¿Desea guardar los cambios?")) return;

        let datos = { action: 'update', ma_id: id };
        
        const fields = [
            'marca','modelo','fecha','ubicacion','tipo','ancho','largo','alto','peso',
            'tanque','tonelaje','dist_barras','platina','anillo','alt_max','apert_max','alt_min','sujecion','molde_chico',
            'bot_patron','bot_fuerza','bot_carrera','tam_unid','vol_inyec','diam_husillo','carga_max','ld','tipo_husillo',
            'pres_inyec','contrapres','revol','vel_inyec','shut_off','carga_vuelo','fuerza_apoyo','noyos','no_valv_aire',
            'tipo_valv','secador','termo','cargador','canal','robot','acumul','voltaje','calent','motor1','motor2'
        ];
        
        datos.ma_marca = document.getElementById('marca_' + id).value;
        datos.ma_modelo = document.getElementById('modelo_' + id).value;
        datos.ma_fecha_fabr = document.getElementById('fecha_' + id).value;
        datos.ma_ubicacion = document.getElementById('ubicacion_' + id).value;
        datos.ma_tipo = document.getElementById('tipo_' + id).value;
        datos.ma_ancho = document.getElementById('ancho_' + id).value;
        datos.ma_largo = document.getElementById('largo_' + id).value;
        datos.ma_alto = document.getElementById('alto_' + id).value;
        datos.ma_peso = document.getElementById('peso_' + id).value;
        
        datos.ma_vol_tanq_aceite = document.getElementById('tanque_' + id).value;
        datos.ma_tonelaje = document.getElementById('tonelaje_' + id).value;
        datos.ma_dist_barras = document.getElementById('dist_barras_' + id).value;
        datos.ma_tam_platina = document.getElementById('platina_' + id).value;
        datos.ma_anillo_centr = document.getElementById('anillo_' + id).value;
        datos.ma_alt_max_molde = document.getElementById('alt_max_' + id).value;
        datos.ma_apert_max = document.getElementById('apert_max_' + id).value;
        datos.ma_alt_min_molde = document.getElementById('alt_min_' + id).value;
        datos.ma_tipo_sujecion = document.getElementById('sujecion_' + id).value;
        datos.ma_molde_chico = document.getElementById('molde_chico_' + id).value;
        
        datos.ma_botado_patron = document.getElementById('bot_patron_' + id).value;
        datos.ma_botado_fuerza = document.getElementById('bot_fuerza_' + id).value;
        datos.ma_botado_carrera = document.getElementById('bot_carrera_' + id).value;
        datos.ma_tam_unid_inyec = document.getElementById('tam_unid_' + id).value;
        datos.ma_vol_inyec = document.getElementById('vol_inyec_' + id).value;
        datos.ma_diam_husillo = document.getElementById('diam_husillo_' + id).value;
        datos.ma_carga_max = document.getElementById('carga_max_' + id).value;
        datos.ma_ld = document.getElementById('ld_' + id).value;
        datos.ma_tipo_husillo = document.getElementById('tipo_husillo_' + id).value;
        
        datos.ma_max_pres_inyec = document.getElementById('pres_inyec_' + id).value;
        datos.ma_max_contrapres = document.getElementById('contrapres_' + id).value;
        datos.ma_max_revol = document.getElementById('revol_' + id).value;
        datos.ma_max_vel_inyec = document.getElementById('vel_inyec_' + id).value;
        datos.ma_valv_shut_off = document.getElementById('shut_off_' + id).value;
        datos.ma_carga_vuelo = document.getElementById('carga_vuelo_' + id).value;
        
        datos.ma_fuerza_apoyo = document.getElementById('fuerza_apoyo_' + id).value;
        datos.ma_noyos = document.getElementById('noyos_' + id).value;
        datos.ma_no_valv_aire = document.getElementById('no_valv_aire_' + id).value;
        datos.ma_tipo_valv_aire = document.getElementById('tipo_valv_' + id).value;
        datos.ma_secador = document.getElementById('secador_' + id).value;
        datos.ma_termoreguladores = document.getElementById('termo_' + id).value;
        datos.ma_cargador = document.getElementById('cargador_' + id).value;
        datos.ma_canal_caliente = document.getElementById('canal_' + id).value;
        datos.ma_robot = document.getElementById('robot_' + id).value;
        datos.ma_acumul_hidr = document.getElementById('acumul_' + id).value;
        datos.ma_voltaje = document.getElementById('voltaje_' + id).value;
        datos.ma_calentamiento = document.getElementById('calent_' + id).value;
        datos.ma_tam_motor_1 = document.getElementById('motor1_' + id).value;
        datos.ma_tam_motor_2 = document.getElementById('motor2_' + id).value;

        fetch('list-maquina-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Actualizado");
                location.reload();
            } else {
                alert("❌ Error: " + data.message);
            }
        });
    }

    function eliminarFila(id) {
        if (!confirm("⚠️ ¿Eliminar registro?")) return;
        fetch('list-maquina-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("🗑️ Eliminado");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        });
    }
</script>
</body>
</html>