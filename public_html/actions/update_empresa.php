<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
if ((int)$_SESSION['rol'] !== 1) jsonError("Sin permiso", 403);

$input = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['em_id'] ?? 0);
$nombre = trim($input['em_nombre'] ?? '');
if (!$id || !$nombre) jsonError("Datos inválidos", 400);

$stmt = $conn->prepare("UPDATE empresas SET em_nombre = ? WHERE em_id = ?");
try {
    $stmt->execute([$nombre, $id]);
    jsonSuccess("Empresa actualizada correctamente");
} catch (PDOException $e) {
    jsonError("Error al actualizar la empresa", 500);
}
