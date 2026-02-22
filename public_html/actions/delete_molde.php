<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['mo_id'])) jsonError("Datos inválidos", 400);

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];
$moId          = (int)$input['mo_id'];

$check = $conn->prepare("SELECT mo_empresa, mo_usuario FROM moldes WHERE mo_id = ?");
$check->execute([$moId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);
if ($rol !== 1) {
    if ($rol === 2 && (int)$reg['mo_empresa'] !== $empresaSesion) jsonError("Sin permiso", 403);
    if ($rol === 3 && (int)$reg['mo_usuario'] !== $usuarioSesion) jsonError("Sin permiso", 403);
}

$stmt = $conn->prepare("UPDATE moldes SET mo_activo = 0, mo_actualizado_en = NOW(), mo_actualizado_por = ? WHERE mo_id = ?");
try {
    $stmt->execute([$usuarioSesion, $moId]);
    jsonSuccess("Registro eliminado correctamente");
} catch (PDOException $e) {
    jsonError("Error al eliminar el registro", 500);
}
