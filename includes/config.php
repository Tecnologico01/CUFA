<?php
// Valores predeterminados para XAMPP. También admite variables de entorno.
define('BASE_URL', rtrim(getenv('CUFA_BASE_URL') ?: '/sistema_academico/', '/') . '/');
define('DB_HOST', getenv('CUFA_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('CUFA_DB_PORT') ?: '3306');
define('DB_NAME', getenv('CUFA_DB_NAME') ?: 'residencia');
define('DB_USER', getenv('CUFA_DB_USER') ?: 'root');
define('DB_PASS', getenv('CUFA_DB_PASS') !== false ? getenv('CUFA_DB_PASS') : '');
