<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - APPLICATION BOOTSTRAP
 * =========================================================
 *
 * This is the central backend loader.
 *
 * Application pages should load this file instead of
 * loading database.php, functions.php and auth.php
 * independently.
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/functions.php';

require_once __DIR__ . '/auth.php';
