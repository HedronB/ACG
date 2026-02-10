<?php
require_once __DIR__ . '/../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

ini_set('default_charset', 'UTF-8');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "mensaje" => "Método no permitido"]);
    exit();
}

if (!isset($_SESSION['id']) || !isset($_SESSION['empresa'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "mensaje" => "Sesión no válida"]);
    exit();
}

$re_usuario = $_SESSION['id'];
$re_empresa = $_SESSION['empresa'];
$re_fecha   = date('Y-m-d H:i:s');

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data || !isset($data['registros']) || !is_array($data['registros'])) {
    echo json_encode(["ok" => false, "mensaje" => "Datos inválidos"]);
    exit();
}

$registros = $data['registros'];

if (count($registros) === 0) {
    echo json_encode(["ok" => false, "mensaje" => "No se recibieron registros"]);
    exit();
}

try {
    $conn->beginTransaction();

    $sql = "INSERT INTO resinas (
                re_usuario,
                re_empresa,
                re_fecha,
                re_cod_int,
                re_tipo_resina,
                re_grado,
                re_porc_reciclado,
                re_temp_masa_max,
                re_temp_masa_min,
                re_temp_ref_max,
                re_temp_ref_min,
                re_sec_temp,
                re_sec_tiempo,
                re_densidad,
                re_factor_correccion,
                re_carga
            ) VALUES (
                :re_usuario,
                :re_empresa,
                :re_fecha,
                :re_cod_int,
                :re_tipo_resina,
                :re_grado,
                :re_porc_reciclado,
                :re_temp_masa_max,
                :re_temp_masa_min,
                :re_temp_ref_max,
                :re_temp_ref_min,
                :re_sec_temp,
                :re_sec_tiempo,
                :re_densidad,
                :re_factor_correccion,
                :re_carga
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $codigoInterno = $r['codigoInterno'] ?? null;
        $tipoResina    = $r['tipoResina'] ?? null;

        if (empty($codigoInterno) || empty($tipoResina)) {
            continue;
        }

        if ($stmt->execute([
            ':re_usuario'          => $re_usuario,
            ':re_empresa'          => $re_empresa,
            ':re_fecha'            => $re_fecha,
            ':re_cod_int'          => $codigoInterno,
            ':re_tipo_resina'      => $tipoResina,
            ':re_grado'            => $r['grado'] ?? null,
            ':re_porc_reciclado'   => $r['porcentajeReciclado'] ?? null,
            ':re_temp_masa_max'    => $r['tempMasaMax'] ?? null,
            ':re_temp_masa_min'    => $r['tempMasaMin'] ?? null,
            ':re_temp_ref_max'     => $r['tempRefrigeracionMax'] ?? null,
            ':re_temp_ref_min'     => $r['tempRefrigeracionMin'] ?? null,
            ':re_sec_temp'         => $r['tempSecado'] ?? null,
            ':re_sec_tiempo'       => $r['tiempoSecado'] ?? null,
            ':re_densidad'         => $r['densidad'] ?? null,
            ':re_factor_correccion' => $r['factorCorreccion'] ?? null,
            ':re_carga'            => $r['carga'] ?? null,
        ])) {
            $insertados++;
        }
    }

    $conn->commit();

    echo json_encode([
        "ok"         => true,
        "mensaje"    => "Registros de resinas guardados correctamente",
        "insertados" => $insertados
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    } 
    http_response_code(500);
    echo json_encode([
        "ok"      => false,
        "mensaje" => "Error interno al guardar registros"
    ]);
}
