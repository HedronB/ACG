<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['pi_id'])) jsonError("Datos inválidos", 400);

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];
$piId          = (int)$input['pi_id'];

$check = $conn->prepare("SELECT pi_empresa, pi_usuario FROM piezas WHERE pi_id = ?");
$check->execute([$piId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);
if ($rol !== 1) {
    if ($rol === 2 && (int)$reg['pi_empresa'] !== $empresaSesion) jsonError("Sin permiso", 403);
    if ($rol === 3 && (int)$reg['pi_usuario'] !== $usuarioSesion) jsonError("Sin permiso", 403);
}

$campos = [
    'pi_cod_prod','pi_molde','pi_descripcion','pi_resina','pi_espesor',
    'pi_area_proy','pi_color','pi_tipo_empaque','pi_piezas','pi_caja_no_pzs',
    'pi_caja_tamaño','pi_bolsa1','pi_bolsa2','pi_tarima_no_cajas'
];

$sets = implode(', ', array_map(fn($c) => "$c = :$c", $campos));
$sets .= ', pi_actualizado_en = NOW(), pi_actualizado_por = :pi_actualizado_por';

$stmt = $conn->prepare("UPDATE piezas SET $sets WHERE pi_id = :pi_id");
$params = [':pi_id' => $piId, ':pi_actualizado_por' => $usuarioSesion];
foreach ($campos as $c) {
    $params[":$c"] = isset($input[$c]) && $input[$c] !== '' ? $input[$c] : null;
}

try {
    $stmt->execute($params);
    jsonSuccess("Registro actualizado correctamente");
} catch (PDOException $e) {
    jsonError("Error al actualizar el registro", 500);
}
