<?php

function jsonSuccess(string $mensaje, array $extra = []): void
{
    echo json_encode(array_merge([
        "ok" => true,
        "mensaje" => $mensaje
    ], $extra));
    exit;
}

function jsonError(string $mensaje, int $code = 400): void
{
    http_response_code($code);
    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);
    exit;
}
