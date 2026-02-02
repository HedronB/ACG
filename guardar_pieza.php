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

$pi_usuario = $_SESSION['id'];
$pi_empresa = $_SESSION['empresa'];
$pi_fecha   = date('Y-m-d H:i:s');

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

    $sql = "INSERT INTO piezas (
                pi_usuario,
                pi_empresa,
                pi_fecha,
                pi_cod_prod,
                pi_molde,
                pi_descripcion,
                pi_resina,
                pi_espesor,
                pi_area_proy,
                pi_color,
                pi_tipo_empaque,
                pi_piezas,
                pi_caja_no_pzs,
                pi_caja_tamano,
                pi_bolsa1,
                pi_bolsa2,
                pi_tarima_no_cajas
            ) VALUES (
                :pi_usuario,
                :pi_empresa,
                :pi_fecha,
                :pi_cod_prod,
                :pi_molde,
                :pi_descripcion,
                :pi_resina,
                :pi_espesor,
                :pi_area_proy,
                :pi_color,
                :pi_tipo_empaque,
                :pi_piezas,
                :pi_caja_no_pzs,
                :pi_caja_tamano,
                :pi_bolsa1,
                :pi_bolsa2,
                :pi_tarima_no_cajas
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $codigoProducto = $r['codigoProducto'] ?? null;
        $numeroMolde    = $r['numeroMolde'] ?? null;

        if (empty($codigoProducto) || empty($numeroMolde)) {
            continue;
        }

        $stmt->execute([
            ':pi_usuario'         => $pi_usuario,
            ':pi_empresa'         => $pi_empresa,
            ':pi_fecha'           => $pi_fecha,
            ':pi_cod_prod'        => $codigoProducto,
            ':pi_molde'           => $numeroMolde,
            ':pi_descripcion'     => $r['descripcion'] ?? null,
            ':pi_resina'          => $r['resina'] ?? null,
            ':pi_espesor'         => $r['espesorPieza'] ?? null,
            ':pi_area_proy'       => $r['areaProyectada'] ?? null,
            ':pi_color'           => $r['color'] ?? null,
            ':pi_tipo_empaque'    => $r['tipoEmpaque'] ?? null,
            ':pi_piezas'          => $r['piezas'] ?? null,
            ':pi_caja_no_pzs'     => $r['piezasPorCaja'] ?? null,
            ':pi_caja_tamano'     => $r['tamanoCaja'] ?? null,
            ':pi_bolsa1'          => null,
            ':pi_bolsa2'          => null,
            ':pi_tarima_no_cajas' => $r['cajasPorTarima'] ?? null,
        ]);

        $insertados++;
    }

    $conn->commit();

    echo json_encode([
        "ok"         => true,
        "mensaje"    => "Registros de piezas guardados correctamente",
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
