<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Método no permitido", 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['ma_id'])) {
    jsonError("Datos inválidos", 400);
}

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];
$maId          = (int)$input['ma_id'];

// Verificar que el registro pertenece a la empresa (o es admin)
$check = $conn->prepare("SELECT ma_empresa, ma_usuario FROM maquinas WHERE ma_id = ?");
$check->execute([$maId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);

if ($rol !== 1) {
    // Gerente: solo su empresa
    if ($rol === 2 && (int)$reg['ma_empresa'] !== $empresaSesion) {
        jsonError("Sin permiso para editar este registro", 403);
    }
    // Empleado: solo sus propios registros
    if ($rol === 3 && (int)$reg['ma_usuario'] !== $usuarioSesion) {
        jsonError("Sin permiso para editar este registro", 403);
    }
}

$campos = [
    'ma_marca','ma_modelo','ma_fecha_fabr','ma_ubicacion','ma_tipo',
    'ma_ancho','ma_largo','ma_alto','ma_peso','ma_vol_tanq_aceite',
    'ma_tonelaje','ma_dist_barras','ma_tam_platina','ma_anillo_centr',
    'ma_alt_max_molde','ma_apert_max','ma_alt_min_molde','ma_tipo_sujecion',
    'ma_molde_chico','ma_botado_patron','ma_botado_fuerza','ma_botado_carrera',
    'ma_tam_unid_inyec','ma_vol_inyec','ma_diam_husillo','ma_carga_max',
    'ma_ld','ma_tipo_husillo','ma_max_pres_inyec','ma_max_contrapres',
    'ma_max_revol','ma_max_vel_inyec','ma_valv_shut_off','ma_carga_vuelo',
    'ma_fuerza_apoyo','ma_noyos','ma_no_valv_aire','ma_tipo_valv_aire',
    'ma_secador','ma_termoreguladores','ma_cargador','ma_canal_caliente',
    'ma_robot','ma_acumul_hidr','ma_voltaje','ma_calentamiento',
    'ma_tam_motor_1','ma_tam_motor_2'
];

$sets = implode(', ', array_map(fn($c) => "$c = :$c", $campos));
$sets .= ', ma_actualizado_en = NOW(), ma_actualizado_por = :ma_actualizado_por';

$sql = "UPDATE maquinas SET $sets WHERE ma_id = :ma_id";
$stmt = $conn->prepare($sql);

$params = [':ma_id' => $maId, ':ma_actualizado_por' => $usuarioSesion];
foreach ($campos as $c) {
    $params[":$c"] = isset($input[$c]) && $input[$c] !== '' ? $input[$c] : null;
}

try {
    $stmt->execute($params);
    jsonSuccess("Registro actualizado correctamente");
} catch (PDOException $e) {
    jsonError("Error al actualizar el registro", 500);
}
