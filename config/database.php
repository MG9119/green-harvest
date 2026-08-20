<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - DATABASE CONFIGURATION
 * =========================================================
 *
 * Responsibilities:
 * - Start and secure the PHP session
 * - Define application constants
 * - Read environment configuration
 * - Create the PDO database connection
 *
 * This file is safe to commit to GitHub because it contains
 * no database passwords, AWS keys, or other secrets.
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| Application Environment
|--------------------------------------------------------------------------
*/

$appEnvRaw = getenv('APP_ENV');

$appEnv = is_string($appEnvRaw) && trim($appEnvRaw) !== ''
    ? strtolower(trim($appEnvRaw))
    : 'local';

define('APP_ENV', $appEnv);
define('APP_NAME', 'Green Harvest');

$appUrl = getenv('APP_URL');

define(
    'APP_URL',
    is_string($appUrl) && trim($appUrl) !== ''
        ? rtrim(trim($appUrl), '/')
        : 'http://localhost/Green_harvest'
);


/*
|--------------------------------------------------------------------------
| Detect HTTPS
|--------------------------------------------------------------------------
|
| Supports direct HTTPS and deployments behind a reverse proxy/load balancer.
|--------------------------------------------------------------------------
*/

$isHttps = false;

if (
    isset($_SERVER['HTTPS'])
    &&
    $_SERVER['HTTPS'] !== ''
    &&
    strtolower((string) $_SERVER['HTTPS']) !== 'off'
) {
    $isHttps = true;
}

if (
    isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
    &&
    strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
) {
    $isHttps = true;
}


/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $sessionSecureEnv = getenv('SESSION_SECURE');

    if ($sessionSecureEnv !== false && $sessionSecureEnv !== '') {
        $sessionSecure = in_array(
            strtolower((string) $sessionSecureEnv),
            ['1', 'true', 'yes', 'on'],
            true
        );
    } else {
        $sessionSecure = $isHttps;
    }

    $sessionDomain = getenv('SESSION_DOMAIN');
    $sessionDomain = is_string($sessionDomain)
        ? trim($sessionDomain)
        : '';

    $sessionSameSite = getenv('SESSION_SAME_SITE');
    $sessionSameSite = is_string($sessionSameSite)
        ? ucfirst(strtolower(trim($sessionSameSite)))
        : 'Lax';

    if (!in_array($sessionSameSite, ['Lax', 'Strict', 'None'], true)) {
        $sessionSameSite = 'Lax';
    }

    /*
     * SameSite=None requires Secure cookies.
     */
    if ($sessionSameSite === 'None' && !$sessionSecure) {
        $sessionSameSite = 'Lax';
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $sessionDomain,
        'secure'   => $sessionSecure,
        'httponly' => true,
        'samesite' => $sessionSameSite,
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Application Paths
|--------------------------------------------------------------------------
*/

define('ROOT_PATH', dirname(__DIR__));

define('CONFIG_PATH', ROOT_PATH . '/config');

define('INCLUDES_PATH', ROOT_PATH . '/includes');

define('UPLOADS_PATH', ROOT_PATH . '/uploads');

define('PRODUCT_UPLOAD_PATH', UPLOADS_PATH . '/products');

define('CATEGORY_UPLOAD_PATH', UPLOADS_PATH . '/categories');


/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
|
| Local XAMPP defaults:
| DB_HOST = 127.0.0.1
| DB_PORT = 3306
| DB_NAME = green_harvest
| DB_USER = root
| DB_PASS = empty
|
| On AWS, configure these values as environment variables.
|--------------------------------------------------------------------------
*/

$dbHostEnv = getenv('DB_HOST');
$dbPortEnv = getenv('DB_PORT');
$dbNameEnv = getenv('DB_NAME');
$dbUserEnv = getenv('DB_USER');
$dbPassEnv = getenv('DB_PASS');

$dbHost = is_string($dbHostEnv) && trim($dbHostEnv) !== ''
    ? trim($dbHostEnv)
    : '127.0.0.1';

$dbPort = is_string($dbPortEnv) && trim($dbPortEnv) !== ''
    ? trim($dbPortEnv)
    : '3306';

$dbName = is_string($dbNameEnv) && trim($dbNameEnv) !== ''
    ? trim($dbNameEnv)
    : 'green_harvest';

$dbUser = is_string($dbUserEnv) && trim($dbUserEnv) !== ''
    ? trim($dbUserEnv)
    : 'root';

$dbPass = is_string($dbPassEnv)
    ? $dbPassEnv
    : '';

$isLocalEnvironment = in_array(
    APP_ENV,
    ['local', 'development', 'dev', 'testing', 'test'],
    true
);


/*
|--------------------------------------------------------------------------
| Production Configuration Validation
|--------------------------------------------------------------------------
|
| Do not allow AWS/production deployments to silently fall back to
| local database values.
|--------------------------------------------------------------------------
*/

if (!$isLocalEnvironment) {

    $requiredVariables = [
        'DB_HOST' => $dbHostEnv,
        'DB_NAME' => $dbNameEnv,
        'DB_USER' => $dbUserEnv,
        'DB_PASS' => $dbPassEnv,
    ];

    foreach ($requiredVariables as $variableName => $variableValue) {

        if (
            $variableValue === false
            ||
            !is_string($variableValue)
            ||
            trim($variableValue) === ''
        ) {
            throw new RuntimeException(
                $variableName . ' must be configured in non-local environments.'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Validate Database Port
|--------------------------------------------------------------------------
*/

if (
    !ctype_digit($dbPort)
    ||
    (int) $dbPort < 1
    ||
    (int) $dbPort > 65535
) {
    throw new RuntimeException('DB_PORT contains an invalid port number.');
}


/*
|--------------------------------------------------------------------------
| PDO Database Connection
|--------------------------------------------------------------------------
*/

try {

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbHost,
        $dbPort,
        $dbName
    );

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]
    );

} catch (PDOException $e) {

    /*
     * Log the technical error server-side.
     * Never display credentials or PDO details to visitors.
     */
    error_log(
        'Green Harvest database connection error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    exit(
        'Green Harvest could not connect to the database. ' .
        'Please try again later.'
    );
}