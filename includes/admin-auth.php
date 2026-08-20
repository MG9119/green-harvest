<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN AUTHENTICATION GUARD
 * =========================================================
 *
 * Include this file on every protected admin page.
 *
 * Responsibilities:
 * - Load the application bootstrap
 * - Verify that the current user is an administrator
 * - Redirect unauthorized users to the admin login page
 * =========================================================
 */

require_once __DIR__ . '/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Require Administrator Access
|--------------------------------------------------------------------------
*/

requireAdmin();