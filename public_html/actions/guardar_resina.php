<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/RequestHelper.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

ini_set('default_charset', 'UTF-8');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || !isset($_SESSION['empresa'])) {
    jsonError("Sesión no válida", 401);
}

$usuario = (int)$_SESSION['id'];
$empresa = (int)$_SESSION['empresa'];
$planta  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

$registros = requirePostJson();

function parseFechaCliente(?string $iso): string {
    if ($iso) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', substr($iso, 0, 19));
        if ($dt) return $dt->format('Y-m-d H:i:s');
    }
    return date('Y-m-d H:i:s');
}

try {
    $conn->beginTransaction();

    $sql = "INSERT INTO resinas (
                re_usuario, re_empresa, re_planta, re_fecha,
                re_cod_int, re_tipo_resina, re_grado, re_porc_reciclado,
                re_temp_masa_max, re_temp_masa_min,
                re_temp_ref_max, re_temp_ref_min,
                re_sec_temp, re_sec_tiempo,
                re_densidad, re_factor_correccion, re_carga
            ) VALUES (
                :re_usuario, :re_empresa, :re_planta, :re_fecha,
                :re_cod_int, :re_tipo_resina, :re_grado, :re_porc_reciclado,
                :re_temp_masa_max, :re_temp_masa_min,
                :re_temp_ref_max, :re_temp_ref_min,
                :re_sec_temp, :re_sec_tiempo,
                :re_densidad, :re_factor_correccion, :re_carga
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $codigoInterno = $r['codigoInterno'] ?? null;
        $tipoResina    = $r['tipoResina'] ?? null;
        if (empty($codigoInterno) || empty($tipoResina)) continue;

        $fecha = parseFechaCliente($r['fechaGuardado'] ?? null);

        if ($stmt->execute([
            ':re_usuario'           => $usuario,
            ':re_empresa'           => $empresa,
            ':re_planta'            => $planta,
            ':re_fecha'             => $fecha,
            ':re_cod_int'           => $codigoInterno,
            ':re_tipo_resina'       => $tipoResina,
            ':re_grado'             => $r['grado'] ?? null,
            ':re_porc_reciclado'    => $r['porcentajeReciclado'] ?? null,
            ':re_temp_masa_max'     => $r['tempMasaMax'] ?? null,
            ':re_temp_masa_min'     => $r['tempMasaMin'] ?? null,
            ':re_temp_ref_max'      => $r['tempRefrigeracionMax'] ?? null,
            ':re_temp_ref_min'      => $r['tempRefrigeracionMin'] ?? null,
            ':re_sec_temp'          => $r['tempSecado'] ?? null,
            ':re_sec_tiempo'        => $r['tiempoSecado'] ?? null,
            ':re_densidad'          => $r['densidad'] ?? null,
            ':re_factor_correccion' => $r['factorCorreccion'] ?? null,
            ':re_carga'             => $r['carga'] ?? null,
        ])) {
            $insertados++;
        }
    }

    $conn->commit();
    jsonSuccess("Registros guardados correctamente", ["insertados" => $insertados]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    jsonError("Error interno al guardar registros", 500);
}
