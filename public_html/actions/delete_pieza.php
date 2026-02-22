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

$stmt = $conn->prepare("UPDATE piezas SET pi_activo = 0, pi_actualizado_en = NOW(), pi_actualizado_por = ? WHERE pi_id = ?");
try {
    $stmt->execute([$usuarioSesion, $piId]);
    jsonSuccess("Registro eliminado correctamente");
} catch (PDOException $e) {
    jsonError("Error al eliminar el registro", 500);
}
