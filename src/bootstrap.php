<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

// Variables de entorno (.env en la raiz del proyecto)
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

date_default_timezone_set('America/Lima');

// Eloquent ORM (Capsule) - conexion MySQL
$capsule = new Capsule();
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env('DB_HOST', '127.0.0.1'),
    'port'      => (int) env('DB_PORT', 3306),
    'database'  => env('DB_DATABASE', 'devioz_db'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'options'   => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;
