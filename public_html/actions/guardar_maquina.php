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

// Fecha: preferir la enviada por el cliente (con zona horaria local), fallback al servidor
function parseFechaCliente(?string $iso): string {
    if ($iso) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', substr($iso, 0, 19));
        if ($dt) return $dt->format('Y-m-d H:i:s');
    }
    return date('Y-m-d H:i:s');
}

try {
    $conn->beginTransaction();

    $sql = "INSERT INTO maquinas (
        ma_usuario, ma_empresa, ma_planta, ma_fecha,
        ma_marca, ma_modelo, ma_fecha_fabr, ma_ubicacion, ma_tipo,
        ma_ancho, ma_largo, ma_alto, ma_peso, ma_vol_tanq_aceite,
        ma_tonelaje, ma_dist_barras, ma_tam_platina, ma_anillo_centr,
        ma_alt_max_molde, ma_apert_max, ma_alt_min_molde, ma_tipo_sujecion,
        ma_molde_chico, ma_botado_patron, ma_botado_fuerza, ma_botado_carrera,
        ma_tam_unid_inyec, ma_vol_inyec, ma_diam_husillo, ma_carga_max,
        ma_ld, ma_tipo_husillo, ma_max_pres_inyec, ma_max_contrapres,
        ma_max_revol, ma_max_vel_inyec, ma_valv_shut_off, ma_carga_vuelo,
        ma_fuerza_apoyo, ma_noyos, ma_no_valv_aire, ma_tipo_valv_aire,
        ma_secador, ma_termoreguladores, ma_cargador, ma_canal_caliente,
        ma_robot, ma_acumul_hidr, ma_voltaje, ma_calentamiento,
        ma_tam_motor_1, ma_tam_motor_2
    ) VALUES (
        :ma_usuario, :ma_empresa, :ma_planta, :ma_fecha,
        :ma_marca, :ma_modelo, :ma_fecha_fabr, :ma_ubicacion, :ma_tipo,
        :ma_ancho, :ma_largo, :ma_alto, :ma_peso, :ma_vol_tanq_aceite,
        :ma_tonelaje, :ma_dist_barras, :ma_tam_platina, :ma_anillo_centr,
        :ma_alt_max_molde, :ma_apert_max, :ma_alt_min_molde, :ma_tipo_sujecion,
        :ma_molde_chico, :ma_botado_patron, :ma_botado_fuerza, :ma_botado_carrera,
        :ma_tam_unid_inyec, :ma_vol_inyec, :ma_diam_husillo, :ma_carga_max,
        :ma_ld, :ma_tipo_husillo, :ma_max_pres_inyec, :ma_max_contrapres,
        :ma_max_revol, :ma_max_vel_inyec, :ma_valv_shut_off, :ma_carga_vuelo,
        :ma_fuerza_apoyo, :ma_noyos, :ma_no_valv_aire, :ma_tipo_valv_aire,
        :ma_secador, :ma_termoreguladores, :ma_cargador, :ma_canal_caliente,
        :ma_robot, :ma_acumul_hidr, :ma_voltaje, :ma_calentamiento,
        :ma_tam_motor_1, :ma_tam_motor_2
    )";

    $stmt = $conn->prepare($sql);
    $insertados = 0;

    foreach ($registros as $r) {
        $fecha = parseFechaCliente($r['fechaGuardado'] ?? null);

        if ($stmt->execute([
            ':ma_usuario' => $usuario,
            ':ma_empresa' => $empresa,
            ':ma_planta'  => $planta,
            ':ma_fecha'   => $fecha,
            ':ma_marca'             => $r['marca'] ?? null,
            ':ma_modelo'            => $r['modelo'] ?? null,
            ':ma_fecha_fabr'        => $r['fechaFabricacion'] ?? null,
            ':ma_ubicacion'         => $r['ubicacion'] ?? null,
            ':ma_tipo'              => $r['tipoMaquina'] ?? null,
            ':ma_ancho'             => $r['dimAncho'] ?? null,
            ':ma_largo'             => $r['dimLargo'] ?? null,
            ':ma_alto'              => $r['dimAlto'] ?? null,
            ':ma_peso'              => $r['peso'] ?? null,
            ':ma_vol_tanq_aceite'   => $r['tamanoTanqueAceite'] ?? null,
            ':ma_tonelaje'          => $r['tonelaje'] ?? null,
            ':ma_dist_barras'       => $r['distanciaBarras'] ?? null,
            ':ma_tam_platina'       => $r['tamanoPlatina'] ?? null,
            ':ma_anillo_centr'      => $r['anilloCentrador'] ?? null,
            ':ma_alt_max_molde'     => $r['alturaMaxMolde'] ?? null,
            ':ma_apert_max'         => $r['aperturaMax'] ?? null,
            ':ma_alt_min_molde'     => $r['alturaMinMolde'] ?? null,
            ':ma_tipo_sujecion'     => $r['tipoSujecion'] ?? null,
            ':ma_molde_chico'       => $r['moldeChico'] ?? null,
            ':ma_botado_patron'     => $r['patronBotado'] ?? null,
            ':ma_botado_fuerza'     => $r['fuerzaBotado'] ?? null,
            ':ma_botado_carrera'    => $r['carreraBotado'] ?? null,
            ':ma_tam_unid_inyec'    => $r['tamanoUnidadInyeccion'] ?? null,
            ':ma_vol_inyec'         => $r['volumenInyeccion'] ?? null,
            ':ma_diam_husillo'      => $r['diametroHusillo'] ?? null,
            ':ma_carga_max'         => $r['cargaMax'] ?? null,
            ':ma_ld'                => $r['ld'] ?? null,
            ':ma_tipo_husillo'      => $r['tipoHusillo'] ?? null,
            ':ma_max_pres_inyec'    => $r['maxPresionInyeccion'] ?? null,
            ':ma_max_contrapres'    => $r['maxContrapresion'] ?? null,
            ':ma_max_revol'         => $r['maxRevoluciones'] ?? null,
            ':ma_max_vel_inyec'     => $r['maxVelocidadInyeccion'] ?? null,
            ':ma_valv_shut_off'     => $r['valvulaShutOff'] ?? null,
            ':ma_carga_vuelo'       => $r['cargaVuelo'] ?? null,
            ':ma_fuerza_apoyo'      => $r['fuerzaApoyo'] ?? null,
            ':ma_noyos'             => $r['noyos'] ?? null,
            ':ma_no_valv_aire'      => $r['numValvulasAire'] ?? null,
            ':ma_tipo_valv_aire'    => $r['tipoValvulasAire'] ?? null,
            ':ma_secador'           => $r['secador'] ?? null,
            ':ma_termoreguladores'  => $r['termoreguladores'] ?? null,
            ':ma_cargador'          => $r['cargador'] ?? null,
            ':ma_canal_caliente'    => $r['canalCaliente'] ?? null,
            ':ma_robot'             => $r['robot'] ?? null,
            ':ma_acumul_hidr'       => $r['acumuladorHidraulico'] ?? null,
            ':ma_voltaje'           => $r['voltaje'] ?? null,
            ':ma_calentamiento'     => $r['calentamiento'] ?? null,
            ':ma_tam_motor_1'       => $r['tamanoMotor1'] ?? null,
            ':ma_tam_motor_2'       => $r['tamanoMotor2'] ?? null,
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
