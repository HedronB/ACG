<?php

function v_string($value, bool $required = false): ?string
{
    if ($required && empty($value)) {
        jsonError("Campo requerido faltante", 422);
    }

    if ($value === null || $value === '') {
        return null;
    }

    return trim((string)$value);
}

function v_int($value, bool $required = false): ?int
{
    if ($required && ($value === null || $value === '')) {
        jsonError("Campo requerido faltante", 422);
    }

    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        jsonError("Valor numérico inválido", 422);
    }

    return (int)$value;
}

function v_float($value, bool $required = false): ?float
{
    if ($required && ($value === null || $value === '')) {
        jsonError("Campo requerido faltante", 422);
    }

    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        jsonError("Valor numérico inválido", 422);
    }

    return (float)$value;
}
