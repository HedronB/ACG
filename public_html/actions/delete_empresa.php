<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/ResponseHelper.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError("Método no permitido", 405);
if ((int)$_SESSION['rol'] !== 1) jsonError("Sin permiso", 403);

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int)($input['em_id'] ?? 0);
if (!$id) jsonError("Datos inválidos", 400);

// Verificar que no haya usuarios activos asignados
$check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE us_empresa = ? AND us_rol > 0");
$check->execute([$id]);
if ($check->fetchColumn() > 0) {
    jsonError("No se puede eliminar: hay usuarios activos asignados a esta empresa. Reasígnalos primero.", 409);
}

try {
    $conn->beginTransaction();
    // Dar de baja plantas de la empresa
    $conn->prepare("UPDATE plantas SET pl_activo = 0 WHERE pl_empresa = ?")->execute([$id]);
    // Eliminar la empresa (solo si no hay FK constraint con registros de datos)
    // Como maquinas/moldes/etc tienen FK, solo eliminamos si no hay registros activos
    $tablas = [
        ["SELECT COUNT(*) FROM maquinas WHERE ma_empresa = ? AND ma_activo = 1", "máquinas"],
        ["SELECT COUNT(*) FROM moldes WHERE mo_empresa = ? AND activo = 1", "moldes"],
        ["SELECT COUNT(*) FROM piezas WHERE pi_empresa = ? AND activo = 1", "piezas"],
        ["SELECT COUNT(*) FROM resinas WHERE re_empresa = ? AND activo = 1", "resinas"],
    ];
    foreach ($tablas as [$sql, $nombre]) {
        $s = $conn->prepare($sql); $s->execute([$id]);
        if ($s->fetchColumn() > 0) {
            $conn->rollBack();
            jsonError("No se puede eliminar: hay {$nombre} activos de esta empresa.", 409);
        }
    }
    $conn->prepare("DELETE FROM empresas WHERE em_id = ?")->execute([$id]);
    $conn->commit();
    jsonSuccess("Empresa eliminada correctamente");
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    jsonError("Error al eliminar la empresa", 500);
}
