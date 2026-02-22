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

    $sql = "INSERT INTO piezas (
                pi_usuario, pi_empresa, pi_planta, pi_fecha,
                pi_cod_prod, pi_molde, pi_descripcion, pi_resina,
                pi_espesor, pi_area_proy, pi_color, pi_tipo_empaque,
                pi_piezas, pi_caja_no_pzs, pi_caja_tamano,
                pi_bolsa1, pi_bolsa2, pi_tarima_no_cajas
            ) VALUES (
                :pi_usuario, :pi_empresa, :pi_planta, :pi_fecha,
                :pi_cod_prod, :pi_molde, :pi_descripcion, :pi_resina,
                :pi_espesor, :pi_area_proy, :pi_color, :pi_tipo_empaque,
                :pi_piezas, :pi_caja_no_pzs, :pi_caja_tamano,
                :pi_bolsa1, :pi_bolsa2, :pi_tarima_no_cajas
            )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $codigoProducto = $r['codigoProducto'] ?? null;
        $numeroMolde    = $r['numeroMolde'] ?? null;
        if (empty($codigoProducto) || empty($numeroMolde)) continue;

        $fecha = parseFechaCliente($r['fechaGuardado'] ?? null);

        if ($stmt->execute([
            ':pi_usuario'         => $usuario,
            ':pi_empresa'         => $empresa,
            ':pi_planta'          => $planta,
            ':pi_fecha'           => $fecha,
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
