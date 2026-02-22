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

    $sql = "INSERT INTO moldes (
                mo_usuario, mo_empresa, mo_planta, mo_fecha,
                mo_no_pieza, mo_numero,
                mo_ancho, mo_alto, mo_largo, mo_placas_voladas,
                mo_anillo_centrador, mo_no_circ_agua, mo_peso,
                mo_apert_min, mo_abierto, mo_tipo_colada,
                mo_no_zonas, mo_no_cavidades, mo_peso_pieza,
                mo_puert_cavidad, mo_no_coladas, mo_peso_colada,
                mo_peso_disparo, mo_noyos, mo_entr_aire,
                mo_thermoreguladores, mo_valve_gates,
                mo_tiempo_ciclo, mo_cavidades_activas
            ) VALUES (
                :mo_usuario, :mo_empresa, :mo_planta, :mo_fecha,
                :mo_no_pieza, :mo_numero,
                :mo_ancho, :mo_alto, :mo_largo, :mo_placas_voladas,
                :mo_anillo_centrador, :mo_no_circ_agua, :mo_peso,
                :mo_apert_min, :mo_abierto, :mo_tipo_colada,
                :mo_no_zonas, :mo_no_cavidades, :mo_peso_pieza,
                :mo_puert_cavidad, :mo_no_coladas, :mo_peso_colada,
                :mo_peso_disparo, :mo_noyos, :mo_entr_aire,
                :mo_thermoreguladores, :mo_valve_gates,
                :mo_tiempo_ciclo, :mo_cavidades_activas
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $numeroPieza = $r['numeroPieza'] ?? null;
        $numeroMolde = $r['numeroMolde'] ?? null;
        if (empty($numeroPieza) || empty($numeroMolde)) continue;

        $fecha = parseFechaCliente($r['fechaGuardado'] ?? null);

        if ($stmt->execute([
            ':mo_usuario'           => $usuario,
            ':mo_empresa'           => $empresa,
            ':mo_planta'            => $planta,
            ':mo_fecha'             => $fecha,
            ':mo_no_pieza'          => $numeroPieza,
            ':mo_numero'            => $numeroMolde,
            ':mo_ancho'             => $r['ancho'] ?? null,
            ':mo_alto'              => $r['alto'] ?? null,
            ':mo_largo'             => $r['largo'] ?? null,
            ':mo_placas_voladas'    => $r['placasVoladas'] ?? null,
            ':mo_anillo_centrador'  => $r['anilloCentrador'] ?? null,
            ':mo_no_circ_agua'      => $r['circuitosAgua'] ?? null,
            ':mo_peso'              => $r['peso'] ?? null,
            ':mo_apert_min'         => $r['aperturaMinima'] ?? null,
            ':mo_abierto'           => $r['moldeAbierto'] ?? null,
            ':mo_tipo_colada'       => $r['tipoColada'] ?? null,
            ':mo_no_zonas'          => $r['numeroZonas'] ?? null,
            ':mo_no_cavidades'      => $r['numeroCavidades'] ?? null,
            ':mo_peso_pieza'        => $r['pesoPieza'] ?? null,
            ':mo_puert_cavidad'     => $r['puertosCavidad'] ?? null,
            ':mo_no_coladas'        => $r['numeroColadas'] ?? null,
            ':mo_peso_colada'       => $r['pesoColada'] ?? null,
            ':mo_peso_disparo'      => $r['pesoDisparo'] ?? null,
            ':mo_noyos'             => $r['noyos'] ?? null,
            ':mo_entr_aire'         => $r['entradasAire'] ?? null,
            ':mo_thermoreguladores' => $r['thermoreguladores'] ?? null,
            ':mo_valve_gates'       => $r['valveGates'] ?? null,
            ':mo_tiempo_ciclo'      => $r['tiempoCiclo'] ?? null,
            ':mo_cavidades_activas' => $r['cavidadesActivas'] ?? null,
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
