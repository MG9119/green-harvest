<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN ENTRY POINT
 * =========================================================
 *
 * Responsibilities:
 * - Load the Green Harvest application
 * - Send authenticated administrators to dashboard
 * - Send everyone else to admin login
 * =========================================================
 */

require_once __DIR__ . '/../includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Admin Routing
|--------------------------------------------------------------------------
*/

if (isAdmin()) {

    redirectTo('admin/dashboard.php');
}


redirectTo('admin/login.php');