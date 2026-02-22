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

$check = $conn->prepare("SELECT ma_empresa, ma_usuario FROM maquinas WHERE ma_id = ?");
$check->execute([$maId]);
$reg = $check->fetch(PDO::FETCH_ASSOC);

if (!$reg) jsonError("Registro no encontrado", 404);

if ($rol !== 1) {
    if ($rol === 2 && (int)$reg['ma_empresa'] !== $empresaSesion) {
        jsonError("Sin permiso para eliminar este registro", 403);
    }
    if ($rol === 3 && (int)$reg['ma_usuario'] !== $usuarioSesion) {
        jsonError("Sin permiso para eliminar este registro", 403);
    }
}

// Baja lógica
$stmt = $conn->prepare("UPDATE maquinas SET ma_activo = 0, ma_actualizado_en = NOW(), ma_actualizado_por = ? WHERE ma_id = ?");
try {
    $stmt->execute([$usuarioSesion, $maId]);
    jsonSuccess("Registro eliminado correctamente");
} catch (PDOException $e) {
    jsonError("Error al eliminar el registro", 500);
}
