<?php
function requirePostJson(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError("Método no permitido", 405);
    }

    $input = file_get_contents('php://input');
    $data  = json_decode($input, true);

    if (!$data || !isset($data['registros']) || !is_array($data['registros'])) {
        jsonError("Datos inválidos", 400);
    }

    if (count($data['registros']) === 0) {
        jsonError("No se recibieron registros", 400);
    }

    return $data['registros'];
}
