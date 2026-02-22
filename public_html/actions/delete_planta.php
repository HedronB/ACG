<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
if ((int)$_SESSION['rol'] !== 1) jsonError("Sin permiso", 403);

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int)($input['pl_id'] ?? 0);
if (!$id) jsonError("Datos inválidos", 400);

$stmt = $conn->prepare("UPDATE plantas SET pl_activo = 0 WHERE pl_id = ?");
try {
    $stmt->execute([$id]);
    jsonSuccess("Planta eliminada correctamente");
} catch (PDOException $e) {
    jsonError("Error al eliminar la planta", 500);
}
