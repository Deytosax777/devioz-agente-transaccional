<?php

declare(strict_types=1);

/**
 * Crea o actualiza un usuario del panel de administracion.
 *
 * Uso:
 *   php scripts/create-admin.php correo@empresa.pe "Contraseña" "Nombre Apellido"
 *   composer create-admin -- correo@empresa.pe "Contraseña" "Nombre"
 */

use Devioz\Models\AdminUser;

require dirname(__DIR__) . '/src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la línea de comandos.\n");
    exit(1);
}

$email    = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name     = $argv[3] ?? 'Administrador Devioz';

if ($email === null || $password === null) {
    echo "Uso: php scripts/create-admin.php <email> <password> [nombre]\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email inválido: {$email}\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

$admin = AdminUser::updateOrCreate(
    ['email' => $email],
    [
        'name'          => $name,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    ]
);

echo "✔ Usuario admin " . ($admin->wasRecentlyCreated ? 'creado' : 'actualizado') . ": {$admin->email}\n";
echo "  Accede al panel en: /admin/\n";
