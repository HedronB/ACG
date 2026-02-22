<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['re_id'])) jsonError("Datos inválidos", 400);

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];
$reId          = (int)$input['re_id'];

$check = $conn->prepare("SELECT re_empresa, re_usuario FROM resinas WHERE re_id = ?");
$check->execute([$reId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);
if ($rol !== 1) {
    if ($rol === 2 && (int)$reg['re_empresa'] !== $empresaSesion) jsonError("Sin permiso", 403);
    if ($rol === 3 && (int)$reg['re_usuario'] !== $usuarioSesion) jsonError("Sin permiso", 403);
}

$campos = [
    're_cod_int','re_tipo_resina','re_grado','re_porc_reciclado',
    're_temp_masa_max','re_temp_masa_min','re_temp_ref_max','re_temp_ref_min',
    're_sec_temp','re_sec_tiempo','re_densidad','re_factor_correccion','re_carga'
];

$sets = implode(', ', array_map(fn($c) => "$c = :$c", $campos));
$sets .= ', re_actualizado_en = NOW(), re_actualizado_por = :re_actualizado_por';

$stmt = $conn->prepare("UPDATE resinas SET $sets WHERE re_id = :re_id");
$params = [':re_id' => $reId, ':re_actualizado_por' => $usuarioSesion];
foreach ($campos as $c) {
    $params[":$c"] = isset($input[$c]) && $input[$c] !== '' ? $input[$c] : null;
}

try {
    $stmt->execute($params);
    jsonSuccess("Registro actualizado correctamente");
} catch (PDOException $e) {
    jsonError("Error al actualizar el registro", 500);
}
