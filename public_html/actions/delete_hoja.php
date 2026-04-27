<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);

$input = json_decode(file_get_contents('php://input'), true);
$tipo  = $input['tipo'] ?? '';   // 'resultado' o 'proceso'
$id    = (int)($input['id'] ?? 0);
if (!$id || !in_array($tipo, ['resultado','proceso'])) jsonError("Datos inválidos", 400);

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$usuarioId = (int)$_SESSION['id'];

try {
    if ($tipo === 'resultado') {
        // Verificar permisos
        $check = $conn->prepare("SELECT hr_empresa_id, hr_usuario_id FROM hojas_resultado WHERE hr_id = ?");
        $check->execute([$id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonError("Registro no encontrado", 404);
        if ($rol !== 1 && (int)$row['hr_empresa_id'] !== $empresaId) jsonError("Sin permiso", 403);
        if ($rol === 3 && (int)$row['hr_usuario_id'] !== $usuarioId) jsonError("Sin permiso", 403);

        $conn->prepare("DELETE FROM hojas_resultado WHERE hr_id = ?")->execute([$id]);
        jsonSuccess("Hoja de resultado eliminada correctamente");

    } else {
        // proceso
        $check = $conn->prepare("SELECT hp_empresa_id, hp_usuario_id FROM hojas_proceso WHERE hp_id = ?");
        $check->execute([$id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonError("Registro no encontrado", 404);
        if ($rol !== 1 && (int)$row['hp_empresa_id'] !== $empresaId) jsonError("Sin permiso", 403);
        if ($rol === 3 && (int)$row['hp_usuario_id'] !== $usuarioId) jsonError("Sin permiso", 403);

        $conn->prepare("DELETE FROM hojas_proceso WHERE hp_id = ?")->execute([$id]);
        jsonSuccess("Hoja de proceso eliminada correctamente");
    }
} catch (PDOException $e) {
    jsonError("Error al eliminar: " . $e->getMessage(), 500);
}
