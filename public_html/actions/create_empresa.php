<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
if ((int)$_SESSION['rol'] !== 1) jsonError("Sin permiso", 403);

$input = json_decode(file_get_contents('php://input'), true);
$nombre = trim($input['em_nombre'] ?? '');
if (!$nombre) jsonError("El nombre es obligatorio", 400);

$stmt = $conn->prepare("INSERT INTO empresas (em_nombre) VALUES (?)");
try {
    $stmt->execute([$nombre]);
    jsonSuccess("Empresa creada correctamente", ['em_id' => $conn->lastInsertId()]);
} catch (PDOException $e) {
    jsonError("Error al crear la empresa", 500);
}
