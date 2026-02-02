<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once "protect.php";
require_once "config/db.php";

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

$mo_usuario = $_SESSION['id'];
$mo_empresa = $_SESSION['empresa'];
$mo_fecha   = date('Y-m-d H:i:s');

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

    $sql = "INSERT INTO moldes (
                mo_usuario,
                mo_empresa,
                mo_fecha,
                mo_no_pieza,
                mo_numero,
                mo_ancho,
                mo_alto,
                mo_largo,
                mo_placas_voladas,
                mo_anillo_centrador,
                mo_no_circ_agua,
                mo_peso,
                mo_apert_min,
                mo_abierto,
                mo_tipo_colada,
                mo_no_zonas,
                mo_no_cavidades,
                mo_peso_pieza,
                mo_puert_cavidad,
                mo_no_coladas,
                mo_peso_colada,
                mo_peso_disparo,
                mo_noyos,
                mo_entr_aire,
                mo_thermoreguladores,
                mo_valve_gates,
                mo_tiempo_ciclo,
                mo_cavidades_activas
            ) VALUES (
                :mo_usuario,
                :mo_empresa,
                :mo_fecha,
                :mo_no_pieza,
                :mo_numero,
                :mo_ancho,
                :mo_alto,
                :mo_largo,
                :mo_placas_voladas,
                :mo_anillo_centrador,
                :mo_no_circ_agua,
                :mo_peso,
                :mo_apert_min,
                :mo_abierto,
                :mo_tipo_colada,
                :mo_no_zonas,
                :mo_no_cavidades,
                :mo_peso_pieza,
                :mo_puert_cavidad,
                :mo_no_coladas,
                :mo_peso_colada,
                :mo_peso_disparo,
                :mo_noyos,
                :mo_entr_aire,
                :mo_thermoreguladores,
                :mo_valve_gates,
                :mo_tiempo_ciclo,
                :mo_cavidades_activas
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $numeroPieza = $r['numeroPieza'] ?? null;
        $numeroMolde = $r['numeroMolde'] ?? null;

        if (empty($numeroPieza) || empty($numeroMolde)) {
            continue;
        }

        $stmt->execute([
            ':mo_usuario'           => $mo_usuario,
            ':mo_empresa'           => $mo_empresa,
            ':mo_fecha'             => $mo_fecha,
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
        ]);

        $insertados++;
    }

    $conn->commit();

    echo json_encode([
        "ok"         => true,
        "mensaje"    => "Registros de molde guardados correctamente",
        "insertados" => $insertados
    ]);
} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode([
        "ok"      => false,
        "mensaje" => "Error al guardar: " . $e->getMessage()
    ]);
}
