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

try {
    $conn->beginTransaction();

    // 1. Desactivar todos los usuarios de la empresa (baja lógica)
    $conn->prepare("UPDATE usuarios SET us_rol = 0 WHERE us_empresa = ?")->execute([$id]);

    // 2. Baja lógica de todos los catálogos de la empresa
    // (nombres de columnas corregidos: mo_activo, pi_activo, re_activo, ma_activo)
    $conn->prepare("UPDATE maquinas SET ma_activo = 0 WHERE ma_empresa = ?")->execute([$id]);
    $conn->prepare("UPDATE moldes   SET mo_activo = 0 WHERE mo_empresa = ?")->execute([$id]);
    $conn->prepare("UPDATE piezas   SET pi_activo = 0 WHERE pi_empresa = ?")->execute([$id]);
    $conn->prepare("UPDATE resinas  SET re_activo = 0 WHERE re_empresa = ?")->execute([$id]);

    // 3. Desactivar plantas de la empresa
    $conn->prepare("UPDATE plantas SET pl_activo = 0 WHERE pl_empresa = ?")->execute([$id]);

    // 4. Desactivar procesos de la empresa (baja lógica)
    $conn->prepare("UPDATE procesos SET pr_activo = 0 WHERE pr_empresa_id = ?")->execute([$id]);

    // 5. NO eliminamos físicamente — baja lógica de la empresa
    // Si en el futuro se requiere eliminar físicamente, se necesita limpiar
    // todas las FKs en el orden correcto. Por ahora usamos una columna de estado.
    // Como empresas no tiene columna activo, la eliminamos solo si no hay FKs activas.
    
    // Verificar si hay registros que impiden el DELETE físico
    $checks = [
        "SELECT COUNT(*) FROM usuarios WHERE us_empresa = ? AND us_rol > 0",
        "SELECT COUNT(*) FROM hojas_resultado WHERE hr_empresa_id = ?",
        "SELECT COUNT(*) FROM hojas_proceso WHERE hp_empresa_id = ?",
    ];
    $bloqueos = [];
    foreach ($checks as $sql) {
        $s = $conn->prepare($sql); $s->execute([$id]);
        if ($s->fetchColumn() > 0) { $bloqueos[] = true; }
    }

    if (empty($bloqueos)) {
        // Sin dependencias activas — se puede eliminar físicamente
        // Primero eliminar plantas (FK pl_empresa -> empresas)
        $conn->prepare("DELETE FROM plantas WHERE pl_empresa = ?")->execute([$id]);
        $conn->prepare("DELETE FROM empresas WHERE em_id = ?")->execute([$id]);
        $conn->commit();
        jsonSuccess("Empresa y todos sus datos eliminados correctamente");
    } else {
        // Hay hojas/usuarios históricos — hacemos baja lógica de la empresa
        // Renombramos para indicar que está dada de baja
        $conn->prepare("UPDATE empresas SET em_nombre = CONCAT('[BAJA] ', em_nombre) WHERE em_id = ? AND em_nombre NOT LIKE '[BAJA]%'")->execute([$id]);
        $conn->commit();
        jsonSuccess("Empresa y todos sus catálogos dados de baja. Los registros históricos se conservan.");
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    jsonError("Error al dar de baja la empresa: " . $e->getMessage(), 500);
}
