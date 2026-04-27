<?php
define('BASE_PATH', dirname(__DIR__));

// Zona horaria del sistema — ajustar si el servidor cambia de región
date_default_timezone_set('America/Mexico_City');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>