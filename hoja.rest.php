<?php
session_start();
date_default_timezone_set('UTC');

if (!isset($_SESSION['id'])) { header("Location: log.php"); exit(); }

require_once "config/db.php";

$usuarioId = $_SESSION['id'];
$empresaId = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 0;

$maquinaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$linkVolver = "form-hojaResultado.php"; 
if (isset($_GET['from']) && $_GET['from'] === 'cambios') {
    $linkVolver = "registros-cambios.php"; 
}

if ($maquinaId > 0) {
    $stmt = $conn->prepare("SELECT ma_marca, ma_modelo FROM maquinas WHERE ma_id = :id AND ma_empresa = :empresa");
    $stmt->execute([':id' => $maquinaId, ':empresa' => $empresaId]);
    $maquinaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$maquinaInfo) { die("Máquina no encontrada o acceso denegado."); }
} else {
    die("ID de máquina no válido.");
}

$sqlHoja = "SELECT 
                h.*, 
                uc.us_nombre as creador, 
                um.us_nombre as modificador 
            FROM hojas_resultado h
            LEFT JOIN usuarios uc ON h.hr_usuario_id = uc.us_id
            LEFT JOIN usuarios um ON h.hr_ultimo_usuario_id = um.us_id
            WHERE h.hr_maquina_id = :mid LIMIT 1";
$stmtH = $conn->prepare($sqlHoja);
$stmtH->execute([':mid' => $maquinaId]);
$datosHoja = $stmtH->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) { echo json_encode(['success'=>false, 'message'=>'Datos vacíos']); exit; }

    try {
        $fechaActual = date('Y-m-d H:i:s');

        if ($datosHoja) {
            $sql = "UPDATE hojas_resultado SET 
                hr_ultimo_usuario_id = :usuario, 
                hr_fecha_modificacion = :fecha,
                hr_p1_real=:p1r, hr_p1_min=:p1min, hr_p1_max=:p1max,
                hr_p2_real=:p2r, hr_p2_min=:p2min, hr_p2_max=:p2max,
                hr_p3_real=:p3r, hr_p3_min=:p3min, hr_p3_max=:p3max,
                hr_p4_real=:p4r, hr_p4_min=:p4min, hr_p4_max=:p4max,
                hr_p5_real=:p5r, hr_p5_min=:p5min, hr_p5_max=:p5max,
                hr_p6_real=:p6r, hr_p6_min=:p6min, hr_p6_max=:p6max,
                hr_p7_real=:p7r, hr_p7_min=:p7min, hr_p7_max=:p7max,
                hr_p8_real=:p8r, hr_p8_min=:p8min, hr_p8_max=:p8max,
                hr_p9_real=:p9r, hr_p9_min=:p9min, hr_p9_max=:p9max,
                hr_carga=:carga, hr_conm=:conm, hr_cojin_calc=:cojin, hr_porcentaje=:porc,
                hr_material=:mat, hr_grado=:grad, hr_peso_disparo=:peso, hr_volumen_disparo=:vol,
                hr_diametro_husillo=:hus, hr_diametro_boquilla=:boq, hr_imagen=:img
                WHERE hr_maquina_id = :maquina";
        } else {
            $sql = "INSERT INTO hojas_resultado (
                hr_maquina_id, hr_usuario_id, hr_empresa_id, hr_fecha_registro,
                hr_p1_real, hr_p1_min, hr_p1_max, hr_p2_real, hr_p2_min, hr_p2_max,
                hr_p3_real, hr_p3_min, hr_p3_max, hr_p4_real, hr_p4_min, hr_p4_max,
                hr_p5_real, hr_p5_min, hr_p5_max, hr_p6_real, hr_p6_min, hr_p6_max,
                hr_p7_real, hr_p7_min, hr_p7_max, hr_p8_real, hr_p8_min, hr_p8_max,
                hr_p9_real, hr_p9_min, hr_p9_max,
                hr_carga, hr_conm, hr_cojin_calc, hr_porcentaje,
                hr_material, hr_grado, hr_peso_disparo, hr_volumen_disparo, 
                hr_diametro_husillo, hr_diametro_boquilla, hr_imagen
            ) VALUES (
                :maquina, :usuario, :empresa, :fecha,
                :p1r, :p1min, :p1max, :p2r, :p2min, :p2max,
                :p3r, :p3min, :p3max, :p4r, :p4min, :p4max,
                :p5r, :p5min, :p5max, :p6r, :p6min, :p6max,
                :p7r, :p7min, :p7max, :p8r, :p8min, :p8max,
                :p9r, :p9min, :p9max,
                :carga, :conm, :cojin, :porc,
                :mat, :grad, :peso, :vol, :hus, :boq, :img
            )";
        }

        $stmt = $conn->prepare($sql);
        $params = [
            ':maquina' => $maquinaId, 
            ':usuario' => $usuarioId,
            ':fecha'   => $fechaActual,
            ':p1r'=>$input['p1_real'], ':p1min'=>$input['p1_min'], ':p1max'=>$input['p1_max'],
            ':p2r'=>$input['p2_real'], ':p2min'=>$input['p2_min'], ':p2max'=>$input['p2_max'],
            ':p3r'=>$input['p3_real'], ':p3min'=>$input['p3_min'], ':p3max'=>$input['p3_max'],
            ':p4r'=>$input['p4_real'], ':p4min'=>$input['p4_min'], ':p4max'=>$input['p4_max'],
            ':p5r'=>$input['p5_real'], ':p5min'=>$input['p5_min'], ':p5max'=>$input['p5_max'],
            ':p6r'=>$input['p6_real'], ':p6min'=>$input['p6_min'], ':p6max'=>$input['p6_max'],
            ':p7r'=>$input['p7_real'], ':p7min'=>$input['p7_min'], ':p7max'=>$input['p7_max'],
            ':p8r'=>$input['p8_real'], ':p8min'=>$input['p8_min'], ':p8max'=>$input['p8_max'],
            ':p9r'=>$input['p9_real'], ':p9min'=>$input['p9_min'], ':p9max'=>$input['p9_max'],
            ':carga'=>$input['carga'], ':conm'=>$input['conm'], ':cojin'=>$input['cojin_calc'], ':porc'=>$input['porcentaje'],
            ':mat'=>$input['material'], ':grad'=>$input['grado'], ':peso'=>$input['pesoDisparo'], ':vol'=>$input['volumenDisparo'],
            ':hus'=>$input['diametroHusillo'], ':boq'=>$input['diametroBoquilla'], ':img'=>$input['imagen']
        ];
        
        if (!$datosHoja) { $params[':empresa'] = $empresaId; }

        $stmt->execute($params);
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hoja de Resultados - Inyección</title>
  <link rel="icon" type="image/png" href="imagenes/loguito.png" />
  <link rel="stylesheet" href="css/acg.estilos.css" />
  <style>
    .form-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .header { justify-content: space-between; }

    .audit-box {
        background-color: #e3f2fd; border: 1px solid #2196f3; color: #0d47a1;
        padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9em;
        text-align: center;
    }

    .params-header, .params-row { display: grid; grid-template-columns: 3fr 1fr 1fr 1fr 1.5fr; gap: 10px; align-items: center; width: 100%; }
    .params-header { font-weight: bold; text-align: center; background-color: #f4f4f4; padding: 10px 5px; border-radius: 5px; margin-bottom: 10px; font-size: 0.85em; border: 1px solid #ddd; }
    .params-row { margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #f0f0f0; }
    .params-row label { font-size: 0.9em; color: #333; font-weight: 600; }
    .params-row input { width: 100%; padding: 5px; font-size: 0.9em; border: 1px solid #ccc; border-radius: 4px; text-align: center; }
    .input-tolerancia { background-color: #eaeaea; color: #555; border: 1px solid #dcdcdc; font-weight: bold; cursor: default; }

    h3 { margin-top: 25px; margin-bottom: 15px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 5px; font-size: 1.1em; }

    .btn { padding: 10px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: 500; }
    .btn-primary { background-color: #007bff; color: white;}
    .btn-success { background-color: #28a745; color: white; }
    
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-size: 0.9em; margin-bottom: 5px; font-weight: bold; }
    .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .form-actions { margin-top: 20px; display: flex; justify-content: center; gap: 15px; }
    .resultado-valor { font-size: 1.5em; font-weight: bold; color: #28a745; margin-top: 5px; }

    #hoja-impresion { display: none; } 
    @media print {
      body * { visibility: hidden; }
      .header, .form-container, footer { display: none !important; }
      #hoja-impresion, #hoja-impresion * { visibility: visible; }
      #hoja-impresion { display: block !important; position: absolute; left: 0; top: 0; width: 100%; background: white; font-family: Arial, sans-serif; font-size: 11px; }
      .excel-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
      .excel-table th, .excel-table td { border: 1px solid #000; padding: 3px; text-align: center; height: 16px; }
      .bg-green { background-color: #00FF00 !important; font-weight: bold; font-size: 18px; -webkit-print-color-adjust: exact; }
      .bg-lime { background-color: #90EE90 !important; -webkit-print-color-adjust: exact; }
      .bg-yellow { background-color: #FFFF00 !important; -webkit-print-color-adjust: exact; }
      .bg-grey { background-color: #E0E0E0 !important; -webkit-print-color-adjust: exact; }
      .bg-light-grey { background-color: #F2F2F2 !important; -webkit-print-color-adjust: exact; }
      .text-left { text-align: left !important; padding-left: 5px !important; }
      .grafica-container { border: 2px solid #000; height: 380px; width: 100%; display: flex; align-items: center; justify-content: center; margin-top: 2px; overflow: hidden; }
      .grafica-container img { width: 100%; height: 100%; object-fit: fill; }
      .footer-table { margin-top: 10px; border: 2px solid #000; }
      .footer-box { height: 40px; vertical-align: bottom; font-size: 9px; }
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="header-title-group">
      <img src="imagenes/logo.png" alt="Logo" class="header-logo" />
      <h1>Hoja de Resultados - Inyección</h1>
    </div>
    <div style="display:flex; gap:20px; align-items:center;">
        <span style="font-weight:bold; color:#555;">Máquina: <?= htmlspecialchars($maquinaInfo['ma_marca'] . ' ' . $maquinaInfo['ma_modelo']) ?></span>
        <a href="<?= $linkVolver ?>" class="back-button">⬅️ Volver</a>
    </div>
  </header>

  <div class="form-container">
    
    <?php if ($datosHoja): ?>
        <div class="audit-box">
            <span>ℹ️</span>
            <?php if ($datosHoja['hr_fecha_modificacion']): ?>
                <strong>Última edición:</strong> Por <u><?= htmlspecialchars($datosHoja['modificador']) ?></u> 
                el <span class="fecha-utc"><?= $datosHoja['hr_fecha_modificacion'] ?></span>
            <?php else: ?>
                <strong>Creado:</strong> Por <u><?= htmlspecialchars($datosHoja['creador']) ?></u> 
                el <span class="fecha-utc"><?= $datosHoja['hr_fecha_registro'] ?></span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="audit-box" style="background-color: #fff3cd; border-color: #ffeeba; color: #856404;">
            <span>⚠️</span> <strong>Nueva Hoja:</strong> Esta máquina aún no tiene hoja de resultados. Llene los datos para crearla.
        </div>
    <?php endif; ?>

    <form id="formPieza" enctype="multipart/form-data">
      <h3>Parámetros de Proceso</h3>
      <div class="params-header"><div>PARÁMETRO</div> <div>REAL</div> <div>MÍNIMO</div> <div>MÁXIMO</div> <div>TOLERANCIA</div></div>

      <div class="params-row"><label>Tiempo de Ciclo</label><input type="number" step="0.01" id="p1_real" required /><input type="number" step="0.01" id="p1_min" /><input type="number" step="0.01" id="p1_max" /><input type="text" class="input-tolerancia" value="+/- 0.20 Segs." readonly /></div>
      <div class="params-row"><label>Tiempo de Inyección</label><input type="number" step="0.01" id="p2_real" /><input type="number" step="0.01" id="p2_min" /><input type="number" step="0.01" id="p2_max" /><input type="text" class="input-tolerancia" value="+/- 0.02 Segs." readonly /></div>
      <div class="params-row"><label>Presión de Conmutación</label><input type="number" step="0.01" id="p3_real" /><input type="number" step="0.01" id="p3_min" /><input type="number" step="0.01" id="p3_max" /><input type="text" class="input-tolerancia" value="+/- 5% bares" readonly /></div>
      <div class="params-row"><label>Presión de Iny. MAX</label><input type="number" step="0.01" id="p4_real" /><input type="number" step="0.01" id="p4_min" /><input type="number" step="0.01" id="p4_max" /><input type="text" class="input-tolerancia" value="+/- 7% bares" readonly /></div>
      <div class="params-row"><label>Carrera de Conmutación</label><input type="number" step="0.01" id="p5_real" /><input type="number" step="0.01" id="p5_min" /><input type="number" step="0.01" id="p5_max" /><input type="text" class="input-tolerancia" value="+/- 0.1 mm" readonly /></div>
      <div class="params-row"><label>Cojín de Masa</label><input type="number" step="0.01" id="p6_real" /><input type="number" step="0.01" id="p6_min" /><input type="number" step="0.01" id="p6_max" /><input type="text" class="input-tolerancia" value="+/- 0.2 mm" readonly /></div>
      <div class="params-row"><label>Tiempo Dosificación</label><input type="number" step="0.01" id="p7_real" /><input type="number" step="0.01" id="p7_min" /><input type="number" step="0.01" id="p7_max" /><input type="text" class="input-tolerancia" value="+/- .5 Segs." readonly /></div>
      <div class="params-row"><label>Temp. Caída de Pieza</label><input type="number" step="0.1" id="p8_real" /><input type="number" step="0.1" id="p8_min" /><input type="number" step="0.1" id="p8_max" /><input type="text" class="input-tolerancia" value="°C" readonly /></div>
      <div class="params-row"><label>Caudal de Agua</label><input type="number" step="0.1" id="p9_real" /><input type="number" step="0.1" id="p9_min" /><input type="number" step="0.1" id="p9_max" /><input type="text" class="input-tolerancia" value="l/min" readonly /></div>

      <h3>Relación de Inyección</h3>
      <div class="form-grid">
        <div class="form-group"><label>CARGA</label><input type="number" step="0.1" id="carga" oninput="calcularPromedio()" /></div>
        <div class="form-group"><label>CONM</label><input type="number" step="0.1" id="conm" oninput="calcularPromedio()" /></div>
        <div class="form-group"><label>COJÍN</label><input type="number" step="0.1" id="cojin_calc" oninput="calcularPromedio()" /></div>
        <div class="resultado-box"><label>Resultado</label><div class="resultado-valor"><span id="resultado_promedio">0.00</span>%</div></div>
      </div>

      <h3>Datos Técnicos</h3>
      <div class="form-grid">
        <div class="form-group"><label>Material / Resina</label><input type="text" id="material" /></div>
        <div class="form-group"><label>Grado</label><input type="text" id="grado" /></div>
        <div class="form-group"><label>Peso Disparo (grs)</label><input type="number" step="0.01" id="pesoDisparo" /></div>
        <div class="form-group"><label>Volumen Disparo (cm3)</label><input type="number" step="0.01" id="volumenDisparo" /></div>
        <div class="form-group"><label>Diámetro Husillo (mm)</label><input type="number" step="0.01" id="diametroHusillo" /></div>
        <div class="form-group"><label>Diámetro Boquilla (mm)</label><input type="number" step="0.01" id="diametroBoquilla" /></div>
      </div>

      <h3>Evidencia Gráfica</h3>
      <div class="form-group">
        <label>Subir Imagen de Gráfica</label>
        <input type="file" id="imagenGrafica" accept="image/*" onchange="previsualizarImagen(event)" />
        <div id="previewContainer" style="margin-top:10px;"></div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-success">
            <?= $datosHoja ? '💾 Actualizar Cambios' : '💾 Guardar Nueva Hoja' ?>
        </button>
        <button type="button" class="btn btn-primary" onclick="imprimirReporteActual()">🖨️ Imprimir Hoja</button>
      </div>
    </form>
  </div>

  <footer><p>Método ACG</p></footer>

  <div id="hoja-impresion">
    <table class="excel-table" style="border:none;">
      <tr>
        <td rowspan="2" style="border:none; width: 20%; text-align:left;"><img src="imagenes/logo.png" style="height:45px;"></td>
        <td rowspan="2" class="bg-green" style="border: 2px solid black; width: 50%;">HOJA RESULTADOS</td>
        <td style="border: 1px solid black; background: #90EE90; width: 30%;"></td>
      </tr>
      <tr><td style="border: 1px solid black; background: #90EE90;">Base</td></tr>
      <tr><td style="border:none;"></td><td style="border:none;"></td><td style="border: 1px solid black; background: #90EE90;"><span id="print_porcentaje_top">0.00</span>% Molido</td></tr>
    </table>
    
    <table class="excel-table" style="border: 2px solid black;">
      <thead>
        <tr class="text-bold">
          <th style="background:white; width: 32%;"></th>
          <th style="background:white; width: 17%; border-bottom: 2px double black;">REAL</th>
          <th style="background:white; width: 17%; border-bottom: 2px double black;">min</th>
          <th style="background:white; width: 17%; border-bottom: 2px double black;">MAX</th>
          <th style="background:white; width: 17%; border-bottom: 2px double black;">TOLERANCIA</th>
        </tr>
      </thead>
      <tbody>
        <tr><td class="text-left">Tiempo de Ciclo</td><td class="bg-yellow" id="pr_p1_real"></td><td id="pr_p1_min"></td><td id="pr_p1_max"></td><td>+/- 0.20 Segs.</td></tr>
        <tr><td class="text-left">Tiempo de inyección</td><td class="bg-light-grey" id="pr_p2_real"></td><td id="pr_p2_min"></td><td id="pr_p2_max"></td><td>+/- 0.02 Segs.</td></tr>
        <tr><td class="text-left">Presión de Conmutación</td><td class="bg-yellow" id="pr_p3_real"></td><td id="pr_p3_min"></td><td id="pr_p3_max"></td><td>+/- 5% bares</td></tr>
        <tr><td class="text-left">Presión de Inyec. MAX</td><td class="bg-yellow" id="pr_p4_real"></td><td id="pr_p4_min"></td><td id="pr_p4_max"></td><td>+/- 7% bares</td></tr>
        <tr><td class="text-left">Carrera de Conmutación</td><td class="bg-light-grey" id="pr_p5_real"></td><td id="pr_p5_min"></td><td id="pr_p5_max"></td><td>+/- 0.1 mm</td></tr>
        <tr><td class="text-left">Cojín de masa</td><td class="bg-yellow" id="pr_p6_real"></td><td id="pr_p6_min"></td><td id="pr_p6_max"></td><td>+/- 0.2 mm</td></tr>
        <tr><td class="text-left">Tiempo dosificación</td><td class="bg-yellow" id="pr_p7_real"></td><td id="pr_p7_min"></td><td id="pr_p7_max"></td><td>+/- .5 Segs.</td></tr>
        <tr><td class="text-left">Temp. de Caida de pieza</td><td id="pr_p8_real"></td><td id="pr_p8_min"></td><td id="pr_p8_max"></td><td></td></tr>
        <tr><td class="text-left">Caudal de Agua</td><td id="pr_p9_real"></td><td id="pr_p9_min"></td><td id="pr_p9_max"></td><td></td></tr>
      </tbody>
    </table>

    <table class="excel-table" style="margin-top:2px;">
      <tr>
        <td style="width: 35%; vertical-align: top; padding:0; border:none;">
          <table class="excel-table">
            <tr><td colspan="3" style="border: 2px solid black;">RELACIÓN DE INYECCIÓN</td></tr>
            <tr><td class="text-left bg-light-grey">CARGA</td> <td id="pr_carga"></td><td rowspan="3" class="bg-lime" style="font-size: 16px; border: 2px solid black; font-weight:bold;"><span id="pr_porcentaje"></span>%</td></tr>
            <tr><td class="text-left bg-light-grey">CONM</td> <td id="pr_conm"></td></tr>
            <tr><td class="text-left bg-light-grey">COJIN</td> <td class="bg-yellow" id="pr_cojin_calc"></td></tr>
          </table>
        </td>
        <td style="width: 25%; vertical-align: top; padding:0; border:none;">
             <table class="excel-table"><tr><td class="text-left bg-grey" style="width:40%">Material</td><td id="pr_material"></td></tr><tr><td class="text-left bg-grey">Grado</td><td id="pr_grado"></td></tr></table>
        </td>
        <td style="width: 40%; vertical-align: top; padding:0; border:none;">
          <table class="excel-table">
            <tr><td class="text-left">Peso disparo</td><td id="pr_peso"></td><td>grs</td></tr>
            <tr><td class="text-left">Volumen disparo</td><td id="pr_volumen"></td><td>cm3</td></tr>
            <tr><td class="text-left">Diametro husillo</td><td id="pr_husillo"></td><td>mm</td></tr>
            <tr><td class="text-left">Diametro boquilla</td><td id="pr_boquilla"></td><td>mm</td></tr>
          </table>
        </td>
      </tr>
    </table>
    <div style="border: 2px solid black; border-bottom:none; text-align: center; font-weight: bold; background: white; margin-top:2px; letter-spacing: 3px;">G R A F I C A</div>
    <div class="grafica-container"><img id="print_img_placeholder" src="" alt="" style="display:none;"></div>
    <table class="excel-table footer-table">
      <tr><td class="text-left" style="border-bottom:none;">FECHA</td><td colspan="2" style="border-bottom:none;">CONTROL DE CAMBIOS</td><td style="border-bottom:none;">COPIA DEPARTAMENTO</td></tr>
      <tr class="text-bold"><td>EMISOR</td><td>REVISOR</td><td>APROBO</td><td>CALIDAD</td></tr>
      <tr><td class="footer-box">Res 1 <br> <?= date("d-M-y"); ?></td><td class="footer-box">Res 2 <br> <?= date("d-M-y"); ?></td><td class="footer-box">Res 3 <br> <?= date("d-M-y"); ?></td><td class="footer-box">Res 4 <br> <?= date("d-M-y"); ?></td></tr>
    </table>
  </div>

  <script>
    (function(){
        document.querySelectorAll('.fecha-utc').forEach(function(el) {
            let fechaTexto = el.innerText;
            if(!fechaTexto) return;

            let fechaUTC = new Date(fechaTexto.replace(' ', 'T') + 'Z');

            if (!isNaN(fechaUTC)) {
                el.innerText = fechaUTC.toLocaleString();
            }
        });
    })();

    let imgDataUrl = null; 
    const datosBD = <?php echo $datosHoja ? json_encode($datosHoja) : 'null'; ?>;

    window.onload = function() {
        if (datosBD) {
            cargarDatosEnInputs(datosBD);
        }
    };

    function cargarDatosEnInputs(datos) {
        const campos = [
            'p1_real', 'p1_min', 'p1_max', 'p2_real', 'p2_min', 'p2_max',
            'p3_real', 'p3_min', 'p3_max', 'p4_real', 'p4_min', 'p4_max',
            'p5_real', 'p5_min', 'p5_max', 'p6_real', 'p6_min', 'p6_max',
            'p7_real', 'p7_min', 'p7_max', 'p8_real', 'p8_min', 'p8_max',
            'p9_real', 'p9_min', 'p9_max',
            'carga', 'conm', 'cojin_calc', 'material', 'grado', 
            'pesoDisparo', 'volumenDisparo', 'diametroHusillo', 'diametroBoquilla'
        ];

        campos.forEach(id => {
            let colName = 'hr_' + id;
            if(id === 'pesoDisparo') colName = 'hr_peso_disparo';
            if(id === 'volumenDisparo') colName = 'hr_volumen_disparo';
            if(id === 'diametroHusillo') colName = 'hr_diametro_husillo';
            if(id === 'diametroBoquilla') colName = 'hr_diametro_boquilla';
            if(id === 'cojin_calc') colName = 'hr_cojin_calc';

            if (datos[colName] !== undefined && document.getElementById(id)) {
                document.getElementById(id).value = datos[colName];
            }
        });

        if(datos.hr_porcentaje) document.getElementById('resultado_promedio').textContent = datos.hr_porcentaje;
        
        if(datos.hr_imagen) {
            imgDataUrl = datos.hr_imagen;
            const div = document.getElementById('previewContainer');
            div.innerHTML = '<img src="'+imgDataUrl+'" style="max-width:200px; border:1px solid #ccc; padding:5px;">';
        }
    }

    function calcularPromedio() {
      const carga = parseFloat(document.getElementById('carga').value) || 0;
      const conm = parseFloat(document.getElementById('conm').value) || 0;
      const cojin = parseFloat(document.getElementById('cojin_calc').value) || 0;
      let res = "0.00";
      if ((carga - cojin) !== 0) {
        res = (((carga - conm) / (carga - cojin)) * 100).toFixed(2);
      }
      document.getElementById('resultado_promedio').textContent = res;
      return res;
    }

    function previsualizarImagen(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) { 
            imgDataUrl = e.target.result; 
            document.getElementById('previewContainer').innerHTML = '<img src="'+imgDataUrl+'" style="max-width:200px;">';
        }
        reader.readAsDataURL(file);
      }
    }

    document.getElementById("formPieza").addEventListener("submit", function(e) {
      e.preventDefault();
      
      if(!confirm("¿Guardar cambios en la hoja de esta máquina?")) return;

      const datos = {
        p1_real: document.getElementById("p1_real").value, p1_min: document.getElementById("p1_min").value, p1_max: document.getElementById("p1_max").value,
        p2_real: document.getElementById("p2_real").value, p2_min: document.getElementById("p2_min").value, p2_max: document.getElementById("p2_max").value,
        p3_real: document.getElementById("p3_real").value, p3_min: document.getElementById("p3_min").value, p3_max: document.getElementById("p3_max").value,
        p4_real: document.getElementById("p4_real").value, p4_min: document.getElementById("p4_min").value, p4_max: document.getElementById("p4_max").value,
        p5_real: document.getElementById("p5_real").value, p5_min: document.getElementById("p5_min").value, p5_max: document.getElementById("p5_max").value,
        p6_real: document.getElementById("p6_real").value, p6_min: document.getElementById("p6_min").value, p6_max: document.getElementById("p6_max").value,
        p7_real: document.getElementById("p7_real").value, p7_min: document.getElementById("p7_min").value, p7_max: document.getElementById("p7_max").value,
        p8_real: document.getElementById("p8_real").value, p8_min: document.getElementById("p8_min").value, p8_max: document.getElementById("p8_max").value,
        p9_real: document.getElementById("p9_real").value, p9_min: document.getElementById("p9_min").value, p9_max: document.getElementById("p9_max").value,
        
        carga: document.getElementById("carga").value,
        conm: document.getElementById("conm").value,
        cojin_calc: document.getElementById("cojin_calc").value,
        porcentaje: document.getElementById("resultado_promedio").textContent,

        material: document.getElementById("material").value,
        grado: document.getElementById("grado").value,
        pesoDisparo: document.getElementById("pesoDisparo").value,
        volumenDisparo: document.getElementById("volumenDisparo").value,
        diametroHusillo: document.getElementById("diametroHusillo").value,
        diametroBoquilla: document.getElementById("diametroBoquilla").value,
        
        imagen: imgDataUrl
      };

      fetch('hoja.rest.php?id=<?= $maquinaId ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(datos)
      })
      .then(res => res.json())
      .then(data => {
          if (data.success) {
              alert("✅ Guardado correctamente");
              location.reload(); 
          } else {
              alert("❌ Error: " + data.message);
          }
      })
      .catch(err => alert("Error de conexión"));
    });

    function imprimirReporteActual() {
        const map = [
            { src: 'p1_real', dest: 'pr_p1_real' }, { src: 'p1_min', dest: 'pr_p1_min' }, { src: 'p1_max', dest: 'pr_p1_max' },
            { src: 'p2_real', dest: 'pr_p2_real' }, { src: 'p2_min', dest: 'pr_p2_min' }, { src: 'p2_max', dest: 'pr_p2_max' },
            { src: 'p3_real', dest: 'pr_p3_real' }, { src: 'p3_min', dest: 'pr_p3_min' }, { src: 'p3_max', dest: 'pr_p3_max' },
            { src: 'p4_real', dest: 'pr_p4_real' }, { src: 'p4_min', dest: 'pr_p4_min' }, { src: 'p4_max', dest: 'pr_p4_max' },
            { src: 'p5_real', dest: 'pr_p5_real' }, { src: 'p5_min', dest: 'pr_p5_min' }, { src: 'p5_max', dest: 'pr_p5_max' },
            { src: 'p6_real', dest: 'pr_p6_real' }, { src: 'p6_min', dest: 'pr_p6_min' }, { src: 'p6_max', dest: 'pr_p6_max' },
            { src: 'p7_real', dest: 'pr_p7_real' }, { src: 'p7_min', dest: 'pr_p7_min' }, { src: 'p7_max', dest: 'pr_p7_max' },
            { src: 'p8_real', dest: 'pr_p8_real' }, { src: 'p8_min', dest: 'pr_p8_min' }, { src: 'p8_max', dest: 'pr_p8_max' },
            { src: 'p9_real', dest: 'pr_p9_real' }, { src: 'p9_min', dest: 'pr_p9_min' }, { src: 'p9_max', dest: 'pr_p9_max' },
            { src: 'carga', dest: 'pr_carga' }, { src: 'conm', dest: 'pr_conm' }, { src: 'cojin_calc', dest: 'pr_cojin_calc' },
            { src: 'material', dest: 'pr_material' }, { src: 'grado', dest: 'pr_grado' },
            { src: 'pesoDisparo', dest: 'pr_peso' }, { src: 'volumenDisparo', dest: 'pr_volumen' },
            { src: 'diametroHusillo', dest: 'pr_husillo' }, { src: 'diametroBoquilla', dest: 'pr_boquilla' }
        ];

        map.forEach(item => {
            const elSrc = document.getElementById(item.src);
            const elDest = document.getElementById(item.dest);
            if(elSrc && elDest) elDest.textContent = elSrc.value;
        });

        document.getElementById('pr_porcentaje').textContent = document.getElementById('resultado_promedio').textContent;
        document.getElementById('print_porcentaje_top').textContent = document.getElementById('resultado_promedio').textContent;
        
        const imgPrint = document.getElementById('print_img_placeholder');
        if (imgDataUrl) {
            imgPrint.src = imgDataUrl;
            imgPrint.style.display = 'block';
        } else {
            imgPrint.style.display = 'none';
        }

        window.print();
    }
  </script>
</body>
</html>