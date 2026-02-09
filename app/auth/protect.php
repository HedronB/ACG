<?php
require_once BASE_PATH . '/app/bootstrap.php';

if (!isset($_SESSION['id'])) {
    header("Location: /log.php?error=Debe iniciar sesión");
    exit();
}

$file = basename($_SERVER['PHP_SELF']);

$roles_permitidos = [

    'index.php' => [0,1,2,3],
    'log.php' => [0,1,2,3],
    'sign.php' => [0,1,2,3],

    'menu_admin.php' => [1],
    'menu_info_admin.php' => [1],
    'manage_users.php' => [1],

    'menu_user.php' => [2,3],
    'menu_info_user.php' => [2,3],

    'perfil.php' => [1,2,3],
    'registros.php' => [1,2,3],

    'form-maquina.php' => [1,2,3],
    'form-molde.php' => [1,2,3],
    'form-pieza.php' => [1,2,3],
    'form-resina.php' => [1,2,3],

    'list-maquina.php' => [1,2,3],
    'list-molde.php' => [1,2,3],
    'list-pieza.php' => [1,2,3],
    'list-resina.php' => [1,2,3],

    'guardar_maquina.php' => [1,2,3],
    'guardar_molde.php' => [1,2,3],
    'guardar_pieza.php' => [1,2,3],
    'guardar_resina.php' => [1,2,3],
];


if (!isset($roles_permitidos[$file])) {
    header("Location: /index.php?error=Acceso no permitido");
    exit();
}

if (!in_array($_SESSION['rol'], $roles_permitidos[$file])) {
    header("Location: /index.php?error=No tiene permisos");
    exit();
}
