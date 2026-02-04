<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';


date_default_timezone_set('UTC');

$usuarioId = $_SESSION['id'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;
$maquinaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$linkVolver = "form-hojaProceso.php"; 
if (isset($_GET['from']) && $_GET['from'] === 'cambios') {
    $linkVolver = "registros-cambios.php"; 
}

if ($maquinaId > 0) {
    $stmt = $conn->prepare("SELECT ma_marca, ma_modelo FROM maquinas WHERE ma_id = :id AND ma_empresa = :empresa");
    $stmt->execute([':id' => $maquinaId, ':empresa' => $empresaId]);
    $maquinaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$maquinaInfo) { die("Máquina no encontrada."); }
} else { die("ID no válido."); }

$sqlHoja = "SELECT h.*, uc.us_nombre as creador, um.us_nombre as modificador 
            FROM hojas_proceso h
            LEFT JOIN usuarios uc ON h.hp_usuario_id = uc.us_id
            LEFT JOIN usuarios um ON h.hp_ultimo_usuario_id = um.us_id
            WHERE h.hp_maquina_id = :mid LIMIT 1";
$stmtH = $conn->prepare($sqlHoja);
$stmtH->execute([':mid' => $maquinaId]);
$datosHoja = $stmtH->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $campos = [
        'iny_vel_10','iny_vel_9','iny_vel_8','iny_vel_7','iny_vel_6','iny_vel_5','iny_vel_4','iny_vel_3','iny_vel_2','iny_vel_1',
        'iny_lim_10','iny_lim_9','iny_lim_8','iny_lim_7','iny_lim_6','iny_lim_5','iny_lim_4','iny_lim_3','iny_lim_2','iny_lim_1',
        'iny_pos_10','iny_pos_9','iny_pos_8','iny_pos_7','iny_pos_6','iny_pos_5','iny_pos_4','iny_pos_3','iny_pos_2','iny_pos_1',
        'conmutacion',
        'sos_pres_1','sos_pres_2','sos_pres_3','sos_pres_4','sos_pres_5','sos_pres_6','sos_pres_7','sos_pres_8','sos_pres_9','sos_pres_10',
        'sos_time_1','sos_time_2','sos_time_3','sos_time_4','sos_time_5','sos_time_6','sos_time_7','sos_time_8','sos_time_9','sos_time_10',
        'car_rpm_1','car_rpm_2','car_rpm_3','car_rpm_4','car_rpm_5',
        'car_back_1','car_back_2','car_back_3','car_back_4','car_back_5',
        'car_pos_1','car_pos_2','car_pos_3','car_pos_4','car_pos_5',
        'refrigeracion','carga_mm','descompresion','vel_descompr',
        'temp_c_b','temp_c_8','temp_c_7','temp_c_6','temp_c_5','temp_c_4','temp_c_3','temp_c_2','temp_c_1',
        'tr_fijo','tr_movil','tr_c1','tr_c2','tr_c3','tr_c4',
        'cc_1','cc_2','cc_3','cc_4','cc_5','cc_6','cc_7','cc_8','cc_9','cc_10','cc_11','cc_12','cc_13','cc_14','cc_15','cc_16',
        'res1_nom','res1_temp','res1_time','res2_nom','res2_temp','res2_time','res3_nom','res3_temp','res3_time'
    ];

    try {
        $fechaActual = date('Y-m-d H:i:s');
        $params = [':maquina' => $maquinaId, ':usuario' => $usuarioId, ':fecha' => $fechaActual];
        
        $sets = [];
        foreach ($campos as $c) {
            $sets[] = "hp_$c = :$c";
            $params[":$c"] = isset($input[$c]) ? $input[$c] : '';
        }

        if ($datosHoja) {
            $sql = "UPDATE hojas_proceso SET hp_ultimo_usuario_id = :usuario, hp_fecha_modificacion = :fecha, " . implode(', ', $sets) . " WHERE hp_maquina_id = :maquina";
        } else {
            $cols = implode(', ', array_map(function($c){ return "hp_$c"; }, $campos));
            $vals = implode(', ', array_map(function($c){ return ":$c"; }, $campos));
            $sql = "INSERT INTO hojas_proceso (hp_maquina_id, hp_usuario_id, hp_empresa_id, hp_fecha_registro, $cols) VALUES (:maquina, :usuario, :empresa, :fecha, $vals)";
            $params[':empresa'] = $empresaId;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Proceso - Inyección</title>
    <link rel="icon" type="image/png" href="imagenes/loguito.png">
    <link rel="stylesheet" href="css/acg.estilos.css">
    <style>
        .form-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .header { justify-content: space-between; }
        .audit-box { background-color: #e3f2fd; border: 1px solid #2196f3; color: #0d47a1; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9em; text-align: center; }
        
        h3 { margin-top: 30px; margin-bottom: 15px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 5px; font-size: 1.1em; }

        .grid-11 { 
            display: grid; 
            grid-template-columns: 1.5fr repeat(10, 1fr); 
            gap: 5px; align-items: center; width: 100%; 
        }
        .grid-6 { 
            display: grid; 
            grid-template-columns: 1.5fr repeat(5, 1fr); 
            gap: 5px; align-items: center; width: 100%; 
        }
        .grid-9 { 
            display: grid; 
            grid-template-columns: 0.5fr repeat(8, 1fr); 
            gap: 5px; align-items: center; width: 100%; 
        }
        .grid-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        
        .params-header { 
            font-weight: bold; text-align: center; background-color: #f4f4f4; 
            padding: 8px 5px; border-radius: 5px; margin-bottom: 5px; 
            font-size: 0.85em; border: 1px solid #ddd;
        }
        
        .params-row { margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #f0f0f0; }
        
        .params-header div:first-child, .params-row label { text-align: left; padding-left: 5px; }
        .params-row label { font-size: 0.85em; color: #333; font-weight: 600; white-space: nowrap; }
        .params-row input { width: 100%; padding: 5px; font-size: 0.9em; border: 1px solid #ccc; border-radius: 4px; text-align: center; box-sizing: border-box; }
        
        .input-disabled { background-color: #eaeaea; border: none; }

        .form-actions { margin-top: 30px; display: flex; justify-content: center; gap: 15px; }
        .btn { padding: 10px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: 500; color: white; }
        .btn-success { background-color: #28a745; }
        .btn-primary { background-color: #007bff; }

        #hoja-impresion { display: none; }
        @media print {
            body * { visibility: hidden; }
            .header, .form-container, footer { display: none !important; }
            #hoja-impresion, #hoja-impresion * { visibility: visible; }
            #hoja-impresion { display: block !important; position: absolute; left: 0; top: 0; width: 100%; background: white; font-family: Arial, sans-serif; font-size: 10px; }
            .print-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .print-table th, .print-table td { border: 1px solid #000; padding: 3px; text-align: center; }
            .print-header { background-color: #ddd !important; font-weight: bold; -webkit-print-color-adjust: exact; }
            .print-left { text-align: left !important; padding-left: 5px; }
            .print-box { height: 40px; vertical-align: bottom; }
        }
    </style>
</head>

<body>
<header class="header">
    <div class="header-title-group">
        <img src="imagenes/logo.png" alt="Logo" class="header-logo">
        <h1>Hoja de Proceso</h1>
    </div>
    <div style="display:flex; gap:20px; align-items:center;">
        <span style="font-weight:bold; color:#555;">Máquina: <?= htmlspecialchars($maquinaInfo['ma_marca'] . ' ' . $maquinaInfo['ma_modelo']) ?></span>
        <a href="<?= $linkVolver ?>" class="back-button">⬅️ Volver</a>
    </div>
</header>

<main class="form-container">
    
    <?php if ($datosHoja): ?>
        <div class="audit-box">
            <span>ℹ️</span>
            <?php if ($datosHoja['hp_fecha_modificacion']): ?>
                <strong>Última edición:</strong> Por <u><?= htmlspecialchars($datosHoja['modificador']) ?></u> el <span class="fecha-utc"><?= $datosHoja['hp_fecha_modificacion'] ?></span>
            <?php else: ?>
                <strong>Creado:</strong> Por <u><?= htmlspecialchars($datosHoja['creador']) ?></u> el <span class="fecha-utc"><?= $datosHoja['hp_fecha_registro'] ?></span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="audit-box" style="background-color: #fff3cd; border-color: #ffeeba; color: #856404;">
            <span>⚠️</span> <strong>Nuevo Proceso:</strong> Registre los datos iniciales.
        </div>
    <?php endif; ?>

    <form id="formProceso">

        <h3>Inyección (Llenado de molde)</h3>
        <div class="params-header grid-11">
            <div>PARÁMETRO</div>
            <div>10</div><div>9</div><div>8</div><div>7</div><div>6</div>
            <div>5</div><div>4</div><div>3</div><div>2</div><div>1</div>
        </div>
        <div class="params-row grid-11">
            <label>VELOCIDAD</label>
            <input id="iny_vel_10"><input id="iny_vel_9"><input id="iny_vel_8"><input id="iny_vel_7"><input id="iny_vel_6">
            <input id="iny_vel_5"><input id="iny_vel_4"><input id="iny_vel_3"><input id="iny_vel_2"><input id="iny_vel_1">
        </div>
        <div class="params-row grid-11">
            <label>LÍMITE PRESIÓN</label>
            <input id="iny_lim_10"><input id="iny_lim_9"><input id="iny_lim_8"><input id="iny_lim_7"><input id="iny_lim_6">
            <input id="iny_lim_5"><input id="iny_lim_4"><input id="iny_lim_3"><input id="iny_lim_2"><input id="iny_lim_1">
        </div>
        <div class="params-row grid-11">
            <label>POSICIONES</label>
            <input id="iny_pos_10"><input id="iny_pos_9"><input id="iny_pos_8"><input id="iny_pos_7"><input id="iny_pos_6">
            <input id="iny_pos_5"><input id="iny_pos_4"><input id="iny_pos_3"><input id="iny_pos_2"><input id="iny_pos_1">
        </div>
        <div class="grid-row" style="margin-top:10px; width: 300px;">
            <label style="font-weight:bold; font-size:0.9em;">Punto de Conmutación (mm):</label>
            <input id="conmutacion" style="width:100px; padding:5px; border:1px solid #ccc; text-align:center;">
        </div>

        <h3>Sostenimiento (Postpresión)</h3>
        <div class="params-header grid-11">
            <div>ZONA</div>
            <div>1</div><div>2</div><div>3</div><div>4</div><div>5</div>
            <div>6</div><div>7</div><div>8</div><div>9</div><div>10</div>
        </div>
        <div class="params-row grid-11">
            <label>PRESIÓN SOST.</label>
            <input id="sos_pres_1"><input id="sos_pres_2"><input id="sos_pres_3"><input id="sos_pres_4"><input id="sos_pres_5">
            <input id="sos_pres_6"><input id="sos_pres_7"><input id="sos_pres_8"><input id="sos_pres_9"><input id="sos_pres_10">
        </div>
        <div class="params-row grid-11">
            <label>TIEMPO</label>
            <input id="sos_time_1"><input id="sos_time_2"><input id="sos_time_3"><input id="sos_time_4"><input id="sos_time_5">
            <input id="sos_time_6"><input id="sos_time_7"><input id="sos_time_8"><input id="sos_time_9"><input id="sos_time_10">
        </div>

        <h3>Carga</h3>
        <div class="params-header grid-6">
            <div>PARÁMETRO</div><div>1</div><div>2</div><div>3</div><div>4</div><div>5</div>
        </div>
        <div class="params-row grid-6">
            <label>REVOLUCIONES</label>
            <input id="car_rpm_1"><input id="car_rpm_2"><input id="car_rpm_3"><input id="car_rpm_4"><input id="car_rpm_5">
        </div>
        <div class="params-row grid-6">
            <label>CONTRAPRESIÓN</label>
            <input id="car_back_1"><input id="car_back_2"><input id="car_back_3"><input id="car_back_4"><input id="car_back_5">
        </div>
        <div class="params-row grid-6">
            <label>POSICIONES</label>
            <input id="car_pos_1"><input id="car_pos_2"><input id="car_pos_3"><input id="car_pos_4"><input id="car_pos_5">
        </div>

        <div class="grid-row" style="gap:20px; margin-top:15px; background:#f9f9f9; padding:10px; border:1px solid #eee;">
            <div><label style="font-weight:bold; font-size:0.85em;">Refrigeración:</label> <input id="refrigeracion" style="width:80px; text-align:center;"></div>
            <div><label style="font-weight:bold; font-size:0.85em;">Carga (mm):</label> <input id="carga_mm" style="width:80px; text-align:center;"></div>
            <div><label style="font-weight:bold; font-size:0.85em;">Descompresión:</label> <input id="descompresion" style="width:80px; text-align:center;"></div>
            <div><label style="font-weight:bold; font-size:0.85em;">Vel. Descompr.:</label> <input id="vel_descompr" style="width:80px; text-align:center;"></div>
        </div>

        <h3>Temperaturas de Cañón</h3>
        <div class="params-header grid-11">
            <div>ZONA</div>
            <div>B</div><div>8</div><div>7</div><div>6</div><div>5</div>
            <div>4</div><div>3</div><div>2</div><div>1</div><div></div>
        </div>
        <div class="params-row grid-11">
            <label>REAL</label>
            <input id="temp_c_b"><input id="temp_c_8"><input id="temp_c_7"><input id="temp_c_6"><input id="temp_c_5">
            <input id="temp_c_4"><input id="temp_c_3"><input id="temp_c_2"><input id="temp_c_1">
            <input class="input-disabled" disabled>
        </div>

        <h3>Termo Reguladores</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">LADO FIJO</label><input id="tr_fijo">
            </div>
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">CARRO 1</label><input id="tr_c1">
            </div>
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">CARRO 3</label><input id="tr_c3">
            </div>
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">LADO MÓVIL</label><input id="tr_movil">
            </div>
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">CARRO 2</label><input id="tr_c2">
            </div>
            <div class="params-row" style="display:flex; align-items:center; gap:10px;">
                <label style="width:80px;">CARRO 4</label><input id="tr_c4">
            </div>
        </div>

        <h3>Canal Caliente</h3>
        <div class="params-header grid-9">
            <div>FILA</div><div>1</div><div>2</div><div>3</div><div>4</div><div>5</div><div>6</div><div>7</div><div>8</div>
        </div>
        <div class="params-row grid-9">
            <label>1 - 8</label>
            <input id="cc_1"><input id="cc_2"><input id="cc_3"><input id="cc_4">
            <input id="cc_5"><input id="cc_6"><input id="cc_7"><input id="cc_8">
        </div>
        <div class="params-header grid-9" style="margin-top:5px;">
            <div>FILA</div><div>9</div><div>10</div><div>11</div><div>12</div><div>13</div><div>14</div><div>15</div><div>16</div>
        </div>
        <div class="params-row grid-9">
            <label>9 - 16</label>
            <input id="cc_9"><input id="cc_10"><input id="cc_11"><input id="cc_12">
            <input id="cc_13"><input id="cc_14"><input id="cc_15"><input id="cc_16">
        </div>

        <h3>Secado de Resina</h3>
        <div class="params-header" style="display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 5px;">
            <div>RESINA</div><div>TEMP. SECADO (°C)</div><div>TIEMPO SECADO</div>
        </div>
        <div class="params-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 5px;">
            <input id="res1_nom" placeholder="Material 1" style="text-align:left;">
            <input id="res1_temp">
            <input id="res1_time">
        </div>
        <div class="params-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 5px;">
            <input id="res2_nom" placeholder="Material 2" style="text-align:left;">
            <input id="res2_temp">
            <input id="res2_time">
        </div>
        <div class="params-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 5px;">
            <input id="res3_nom" placeholder="Material 3" style="text-align:left;">
            <input id="res3_temp">
            <input id="res3_time">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <?= $datosHoja ? '💾 Actualizar Cambios' : '💾 Guardar Proceso' ?>
            </button>
            <button type="button" class="btn btn-primary" onclick="imprimirHoja()">🖨️ Imprimir</button>
        </div>

    </form>
</main>

<footer><p>Método ACG</p></footer>

<div id="hoja-impresion">
    <table class="print-table" style="margin-bottom:10px;">
        <tr>
            <td style="width:20%; border:none;"><img src="imagenes/logo.png" style="height:40px;"></td>
            <td style="width:60%; border:none; font-size:18px; font-weight:bold;">HOJA DE PROCESO</td>
            <td style="width:20%; border:none;">ID: <?= $maquinaId ?></td>
        </tr>
    </table>

    <div class="print-header print-left">INYECCIÓN</div>
    <table class="print-table">
        <tr class="print-header"><td>PARAM</td><td>10</td><td>9</td><td>8</td><td>7</td><td>6</td><td>5</td><td>4</td><td>3</td><td>2</td><td>1</td></tr>
        <tr><td class="print-left">Velocidad</td><td id="pr_iny_vel_10"></td><td id="pr_iny_vel_9"></td><td id="pr_iny_vel_8"></td><td id="pr_iny_vel_7"></td><td id="pr_iny_vel_6"></td><td id="pr_iny_vel_5"></td><td id="pr_iny_vel_4"></td><td id="pr_iny_vel_3"></td><td id="pr_iny_vel_2"></td><td id="pr_iny_vel_1"></td></tr>
        <tr><td class="print-left">Presión</td><td id="pr_iny_lim_10"></td><td id="pr_iny_lim_9"></td><td id="pr_iny_lim_8"></td><td id="pr_iny_lim_7"></td><td id="pr_iny_lim_6"></td><td id="pr_iny_lim_5"></td><td id="pr_iny_lim_4"></td><td id="pr_iny_lim_3"></td><td id="pr_iny_lim_2"></td><td id="pr_iny_lim_1"></td></tr>
        <tr><td class="print-left">Posición</td><td id="pr_iny_pos_10"></td><td id="pr_iny_pos_9"></td><td id="pr_iny_pos_8"></td><td id="pr_iny_pos_7"></td><td id="pr_iny_pos_6"></td><td id="pr_iny_pos_5"></td><td id="pr_iny_pos_4"></td><td id="pr_iny_pos_3"></td><td id="pr_iny_pos_2"></td><td id="pr_iny_pos_1"></td></tr>
    </table>
    <div style="font-size:11px; margin-bottom:10px;">Conmutación: <span id="pr_conmutacion"></span> mm</div>

    <div class="print-header print-left">SOSTENIMIENTO</div>
    <table class="print-table">
        <tr class="print-header"><td>ZONA</td><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td><td>9</td><td>10</td></tr>
        <tr><td class="print-left">Presión</td><td id="pr_sos_pres_1"></td><td id="pr_sos_pres_2"></td><td id="pr_sos_pres_3"></td><td id="pr_sos_pres_4"></td><td id="pr_sos_pres_5"></td><td id="pr_sos_pres_6"></td><td id="pr_sos_pres_7"></td><td id="pr_sos_pres_8"></td><td id="pr_sos_pres_9"></td><td id="pr_sos_pres_10"></td></tr>
        <tr><td class="print-left">Tiempo</td><td id="pr_sos_time_1"></td><td id="pr_sos_time_2"></td><td id="pr_sos_time_3"></td><td id="pr_sos_time_4"></td><td id="pr_sos_time_5"></td><td id="pr_sos_time_6"></td><td id="pr_sos_time_7"></td><td id="pr_sos_time_8"></td><td id="pr_sos_time_9"></td><td id="pr_sos_time_10"></td></tr>
    </table>

    <div class="print-header print-left">CARGA</div>
    <table class="print-table">
        <tr class="print-header"><td>PARAM</td><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td colspan="2">EXTRAS</td></tr>
        <tr><td class="print-left">RPM</td><td id="pr_car_rpm_1"></td><td id="pr_car_rpm_2"></td><td id="pr_car_rpm_3"></td><td id="pr_car_rpm_4"></td><td id="pr_car_rpm_5"></td><td class="print-left">Refrig:</td><td id="pr_refrigeracion"></td></tr>
        <tr><td class="print-left">Back Pres</td><td id="pr_car_back_1"></td><td id="pr_car_back_2"></td><td id="pr_car_back_3"></td><td id="pr_car_back_4"></td><td id="pr_car_back_5"></td><td class="print-left">Carga mm:</td><td id="pr_carga_mm"></td></tr>
        <tr><td class="print-left">Posición</td><td id="pr_car_pos_1"></td><td id="pr_car_pos_2"></td><td id="pr_car_pos_3"></td><td id="pr_car_pos_4"></td><td id="pr_car_pos_5"></td><td class="print-left">Descompr:</td><td id="pr_descompresion"></td></tr>
    </table>

    <div class="print-header print-left">TEMPERATURAS CAÑÓN</div>
    <table class="print-table">
        <tr class="print-header"><td>B</td><td>8</td><td>7</td><td>6</td><td>5</td><td>4</td><td>3</td><td>2</td><td>1</td></tr>
        <tr><td id="pr_temp_c_b"></td><td id="pr_temp_c_8"></td><td id="pr_temp_c_7"></td><td id="pr_temp_c_6"></td><td id="pr_temp_c_5"></td><td id="pr_temp_c_4"></td><td id="pr_temp_c_3"></td><td id="pr_temp_c_2"></td><td id="pr_temp_c_1"></td></tr>
    </table>

    <table class="print-table" style="margin-top:10px;">
        <tr>
            <td class="print-header">TERMO REGULADORES</td>
            <td class="print-header">CANAL CALIENTE</td>
            <td class="print-header">RESINAS</td>
        </tr>
        <tr>
            <td style="vertical-align:top; text-align:left;">
                Fijo: <span id="pr_tr_fijo"></span><br>
                Móvil: <span id="pr_tr_movil"></span><br>
                C1: <span id="pr_tr_c1"></span> | C2: <span id="pr_tr_c2"></span><br>
                C3: <span id="pr_tr_c3"></span> | C4: <span id="pr_tr_c4"></span>
            </td>
            <td style="vertical-align:top;">
                <span id="pr_cc_1"></span> <span id="pr_cc_2"></span> <span id="pr_cc_3"></span> <span id="pr_cc_4"></span> <span id="pr_cc_5"></span> <span id="pr_cc_6"></span> <span id="pr_cc_7"></span> <span id="pr_cc_8"></span><br>
                <span id="pr_cc_9"></span> <span id="pr_cc_10"></span> <span id="pr_cc_11"></span> <span id="pr_cc_12"></span> <span id="pr_cc_13"></span> <span id="pr_cc_14"></span> <span id="pr_cc_15"></span> <span id="pr_cc_16"></span>
            </td>
            <td style="vertical-align:top; text-align:left;">
                1: <span id="pr_res1_nom"></span><br>
                2: <span id="pr_res2_nom"></span><br>
                3: <span id="pr_res3_nom"></span>
            </td>
        </tr>
    </table>
    
    <table class="print-table" style="margin-top:20px;">
    <tr><td class="print-left" style="border-bottom:none;">FECHA: <?= date("d/m/Y") ?></td><td colspan="2" style="border-bottom:none;">CONTROL DE CAMBIOS</td></tr>
    <tr><td class="print-box">EMISOR</td><td class="print-box">REVISOR</td><td class="print-box">APROBÓ</td></tr>
    </table>
</div>

<script>
    (function(){
        document.querySelectorAll('.fecha-utc').forEach(function(el) {
            let txt = el.innerText; if(!txt) return;
            let date = new Date(txt.replace(' ', 'T') + 'Z');
            if(!isNaN(date)) el.innerText = date.toLocaleString();
        });
    })();

    const datosDB = <?php echo $datosHoja ? json_encode($datosHoja) : 'null'; ?>;
    if(datosDB) {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(inp => {
            const colName = 'hp_' + inp.id;
            if(datosDB[colName] !== undefined) inp.value = datosDB[colName];
        });
    }

    document.getElementById('formProceso').addEventListener('submit', function(e){
        e.preventDefault();
        if(!confirm("¿Guardar cambios?")) return;
        const inputs = document.querySelectorAll('input');
        let data = {};
        inputs.forEach(inp => data[inp.id] = inp.value);

        fetch('hoja.pro.php?id=<?= $maquinaId ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) { alert("✅ Guardado"); location.reload(); }
            else { alert("❌ Error: " + d.message); }
        })
        .catch(() => alert("Error de conexión"));
    });

    function imprimirHoja() {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(inp => {
            const dest = document.getElementById('pr_' + inp.id);
            if(dest) dest.innerText = inp.value;
        });
        window.print();
    }
</script>
</body>
</html>