<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - AUTHENTICATION
 * =========================================================
 *
 * Responsibilities:
 * - Check login state
 * - Get current user
 * - Customer/admin roles
 * - Login
 * - Registration
 * - Logout
 * - Protect authenticated pages
 *
 * This file assumes database.php and functions.php have
 * already been loaded by includes/bootstrap.php.
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| Authentication State
|--------------------------------------------------------------------------
*/

/**
 * Check whether a user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && (int) $_SESSION['user_id'] > 0;
}


/**
 * Get the currently authenticated user ID.
 */
function getUserId(): ?int
{
    if (!isLoggedIn()) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}


/**
 * Check whether the current user is an administrator.
 */
function isAdmin(): bool
{
    return isLoggedIn()
        && isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}


/**
 * Check whether the current user is a customer.
 */
function isCustomer(): bool
{
    return isLoggedIn()
        && isset($_SESSION['role'])
        && $_SESSION['role'] === 'customer';
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

/**
 * Get the currently authenticated user's database record.
 */
function currentUser(PDO $pdo): ?array
{
    $userId = getUserId();

    if ($userId === null) {
        return null;
    }

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            full_name,
            email,
            phone,
            address,
            role,
            created_at
        FROM users
        WHERE id = ?
        LIMIT 1
        '
    );

    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * If the session refers to a user that no longer exists,
     * clear the authentication information.
     */
    if (!$user) {
        unset(
            $_SESSION['user_id'],
            $_SESSION['role'],
            $_SESSION['full_name']
        );

        return null;
    }

    return $user;
}


/*
|--------------------------------------------------------------------------
| Login Protection
|--------------------------------------------------------------------------
*/

/**
 * Require the visitor to be logged in.
 *
 * If not logged in, send the visitor to login.php and preserve
 * the current page as a redirect target.
 */
function requireLogin(): void
{
    if (isLoggedIn()) {
        return;
    }

    setFlash(
        'warning',
        'Please log in to continue.'
    );

    /*
     * Determine the current page relative to Green Harvest.
     *
     * Example:
     *
     * /Green_harvest/checkout.php
     *
     * becomes:
     *
     * checkout.php
     */
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    $appPath = parse_url(
        APP_URL,
        PHP_URL_PATH
    ) ?: '';

    $requestPath = parse_url(
        $requestUri,
        PHP_URL_PATH
    ) ?: '';

    $queryString = parse_url(
        $requestUri,
        PHP_URL_QUERY
    );

    if (
        $appPath !== '' &&
        str_starts_with($requestPath, $appPath)
    ) {
        $requestPath = substr(
            $requestPath,
            strlen($appPath)
        );
    }

    $redirect = ltrim(
        $requestPath,
        '/'
    );

    if (
        is_string($queryString) &&
        $queryString !== ''
    ) {
        $redirect .= '?' . $queryString;
    }

    $redirect = safeRedirectPath(
        $redirect,
        'index.php'
    );

    redirectTo(
        'login.php?redirect=' .
        urlencode($redirect)
    );
}


/**
 * Require administrator access.
 */
function requireAdmin(): void
{
    if (isAdmin()) {
        return;
    }

    setFlash(
        'error',
        'Administrator access is required.'
    );

    redirectTo('admin/login.php');
}


/*
|--------------------------------------------------------------------------
| User Login
|--------------------------------------------------------------------------
*/

/**
 * Authenticate a user using email and password.
 */
function loginUser(
    PDO $pdo,
    string $email,
    string $password
): bool {
    $email = strtolower(
        trim($email)
    );

    if ($email === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            full_name,
            email,
            password,
            role,
            auth_provider
        FROM users
        WHERE email = ?
        LIMIT 1
        '
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    if ((string) ($user['auth_provider'] ?? 'local') !== 'local') {
        return false;
    }

    if (
        !password_verify(
            $password,
            $user['password']
        )
    ) {
        return false;
    }

    /*
     * Prevent session fixation.
     */
    session_regenerate_id(true);

    $_SESSION['user_id'] =
        (int) $user['id'];

    $_SESSION['full_name'] =
        (string) $user['full_name'];

    $_SESSION['role'] =
        (string) ($user['role'] ?? 'customer');

    return true;
}

/**
 * Log in a user from Google OAuth and create the account if needed.
 */
function loginGoogleUser(
    PDO $pdo,
    string $email,
    string $fullName,
    ?string $phone = null
): bool {
    $email = strtolower(trim($email));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            full_name,
            role,
            auth_provider
        FROM users
        WHERE email = ?
        LIMIT 1
        '
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = (int) $user['id'];
        $role = (string) ($user['role'] ?? 'customer');
        $fullName = trim((string) ($user['full_name'] ?: $fullName));

        if ((string) ($user['auth_provider'] ?? 'local') !== 'google') {
            $update = $pdo->prepare(
                'UPDATE users SET auth_provider = ? WHERE id = ? LIMIT 1'
            );
            $update->execute(['google', $userId]);
        }
    } else {
        $phone = normalizePhoneNumber((string) ($phone ?: '+15550000000'));

        if ($phone === '' || !preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
            $phone = '+15550000000';
        }

        $passwordHash = password_hash(
            bin2hex(random_bytes(24)),
            PASSWORD_DEFAULT
        );

        $insert = $pdo->prepare(
            '
            INSERT INTO users (
                full_name,
                email,
                phone,
                password,
                role,
                auth_provider
            )
            VALUES (?, ?, ?, ?, ?, ?)
            '
        );

        $insert->execute([
            trim($fullName) !== '' ? trim($fullName) : 'Google User',
            $email,
            $phone,
            $passwordHash,
            'customer',
            'google',
        ]);

        $userId = (int) $pdo->lastInsertId();
        $role = 'customer';
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role'] = $role;

    return true;
}


function normalizePhoneNumber(string $phone): string
{
    $phone = trim($phone);
    $phone = str_replace([' ', '-', '(', ')', '+'], '', $phone);

    if ($phone === '') {
        return '';
    }

    return '+' . preg_replace('/[^0-9]/', '', $phone);
}

/*
|--------------------------------------------------------------------------
| Customer Registration
|--------------------------------------------------------------------------
*/

/**
 * Register a new customer.
 *
 * Public registration always creates a CUSTOMER account.
 * A visitor can never create an admin account through
 * register.php.
 */
function registerUser(
    PDO $pdo,
    string $name,
    string $email,
    string $phone,
    string $password
): array {
    $name = trim($name);

    $email = strtolower(
        trim($email)
    );

    $phone = normalizePhoneNumber($phone);

    /*
     * Basic validation at the authentication layer.
     * register.php will also provide user-friendly validation.
     */
    if ($name === '') {
        return [
            'success' => false,
            'error'   => 'Full name is required.',
        ];
    }

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        return [
            'success' => false,
            'error'   => 'Please enter a valid email address.',
        ];
    }

    if (
        $phone === '' ||
        !preg_match('/^\+[1-9]\d{6,14}$/', $phone)
    ) {
        return [
            'success' => false,
            'error'   => 'Please enter a valid phone number.',
        ];
    }

    if (strlen($password) < 8) {
        return [
            'success' => false,
            'error'   =>
                'Password must be at least 8 characters.',
        ];
    }

    /*
     * Check whether the email address already exists.
     */
    $stmt = $pdo->prepare(
        '
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
        '
    );

    $stmt->execute([$email]);

    if ($stmt->fetchColumn()) {
        return [
            'success' => false,
            'error'   =>
                'An account with this email already exists.',
        ];
    }

    /*
     * Securely hash the password.
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    try {

        $stmt = $pdo->prepare(
            '
            INSERT INTO users (
                full_name,
                email,
                phone,
                password,
                role,
                auth_provider
            )
            VALUES (?, ?, ?, ?, ?, ?)
            '
        );

        $stmt->execute([
            $name,
            $email,
            $phone,
            $passwordHash,
            'customer',
            'local',
        ]);

        $userId =
            (int) $pdo->lastInsertId();

        $requireEmailVerification = getenv('REQUIRE_EMAIL_VERIFICATION');
        $requireEmailVerification = $requireEmailVerification === false
           ? false
           : filter_var($requireEmailVerification, FILTER_VALIDATE_BOOLEAN);

        if ($requireEmailVerification) {
           return [
               'success' => true,
               'user_id' => $userId,
               'requires_verification' => true,
           ];
        }

        /*
         * Log the new customer in immediately.
         */
        session_regenerate_id(true);

        $_SESSION['user_id'] =
           $userId;

        $_SESSION['role'] =
           'customer';

        $_SESSION['full_name'] =
           $name;

        return [
           'success' => true,
           'user_id' => $userId,
        ];

    } catch (PDOException $e) {

        error_log(
            'Green Harvest registration error: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'error'   =>
                'Registration could not be completed. Please try again.',
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

/**
 * Completely sign the current user out.
 */
function logoutUser(): void
{
    /*
     * Remove authentication/session data.
     */
    $_SESSION = [];

    /*
     * Delete the browser's existing PHP session cookie.
     */
    if (ini_get('session.use_cookies')) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    /*
     * Destroy the existing server-side session.
     */
    if (
        session_status() === PHP_SESSION_ACTIVE
    ) {
        session_destroy();
    }

    /*
     * Start a completely new session so we can safely
     * store the logout confirmation message.
     */
    session_start();

    session_regenerate_id(true);

    setFlash(
        'success',
        'You have been logged out successfully.'
    );
}