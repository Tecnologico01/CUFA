<?php

function env_first(array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

$basePath = env_first(['APP_BASE_PATH'], '/sistema_academico');
$basePath = '/' . trim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}

define('BASE_PATH', $basePath);
define('BASE_URL', $basePath === '' ? '/' : $basePath . '/');

define('DB_HOST', env_first(['MYSQLHOST', 'DB_HOST'], 'localhost'));
define('DB_PORT', env_first(['MYSQLPORT', 'DB_PORT'], '3306'));
define('DB_NAME', env_first(['MYSQLDATABASE', 'DB_NAME'], 'residencia'));
define('DB_USER', env_first(['MYSQLUSER', 'DB_USER'], 'root'));
define('DB_PASS', env_first(['MYSQLPASSWORD', 'DB_PASS'], ''));
define('APP_DEBUG', filter_var(env_first(['APP_DEBUG'], 'false'), FILTER_VALIDATE_BOOLEAN));

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    if ($path === '') {
        return BASE_URL;
    }

    return rtrim(BASE_URL, '/') . '/' . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

?>
