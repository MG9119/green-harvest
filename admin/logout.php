<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN LOGOUT
 * =========================================================
 *
 * Responsibilities:
 * - Load the application
 * - End the current authenticated session
 * - Redirect to the administrator login page
 * =========================================================
 */

require_once __DIR__ . '/../includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Logout Current User
|--------------------------------------------------------------------------
|
| logoutUser() handles:
| - Clearing session data
| - Removing the old session cookie
| - Destroying the old session
| - Starting a clean session
| - Regenerating the session ID
| - Creating the logout flash message
|
*/

if (isLoggedIn()) {

    logoutUser();
}


/*
|--------------------------------------------------------------------------
| Redirect to Admin Login
|--------------------------------------------------------------------------
*/

redirectTo('admin/login.php');