<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - PUBLIC HEADER
 * =========================================================
 *
 * Responsibilities:
 * - Load the application bootstrap if necessary
 * - Set page title
 * - Get the current user
 * - Load fonts, Bootstrap and global public styles
 * - Load the public navbar
 * - Open the <main> element
 *
 * Application/business logic should NOT be processed here.
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| Application Bootstrap
|--------------------------------------------------------------------------
|
| Normally, individual pages should load bootstrap.php BEFORE
| processing POST requests or redirects.
|
| This fallback keeps the header safe during the migration of
| older Green Harvest pages.
|
*/

require_once __DIR__ . '/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Page Information
|--------------------------------------------------------------------------
*/

$pageTitle = $pageTitle ?? APP_NAME;

$user = currentUser($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <meta
        name="description"
        content="Fresh organic foods from trusted local farmers in Ghana. Shop vegetables, fruits, grains, herbs and other fresh produce from Green Harvest."
    >


    <!-- =====================================================
         Fonts
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         Bootstrap
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         Green Harvest Global Styles
    ====================================================== -->

    <link
        rel="stylesheet"
        href="<?= url('assets/css/style.css') ?>"
    >

    <style>

        :root {

            --gh-green-50: #f0fdf4;
            --gh-green-100: #dcfce7;

            --gh-green-500: #22c55e;
            --gh-green-600: #16a34a;
            --gh-green-700: #15803d;
            --gh-green-800: #166534;
            --gh-green-900: #14532d;

            --gh-dark: #10271a;

            --gh-text: #1f2937;
            --gh-muted: #6b7280;

            --gh-border: #e5e7eb;

            --gh-background: #f8faf8;
            --gh-white: #ffffff;

            --gh-danger: #dc2626;
            --gh-warning: #d97706;

            --gh-radius-sm: 10px;
            --gh-radius: 14px;
            --gh-radius-lg: 20px;

            --gh-font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --gh-font-display: 'Manrope', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

            --gh-shadow:
                0 10px 30px rgba(15, 55, 30, 0.07);

        }


        /*
        |--------------------------------------------------------------------------
        | Reset / Base
        |--------------------------------------------------------------------------
        */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            font-family: var(--gh-font-sans);

            background: var(--gh-background);

            color: var(--gh-text);

            line-height: 1.6;

            -webkit-font-smoothing: antialiased;

        }


        body,
        input,
        textarea,
        select,
        button {
            font-family: var(--gh-font-sans);
        }


        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {

            font-family: var(--gh-font-display);

            color: var(--gh-dark);

            font-weight: 800;

            letter-spacing: -0.04em;

        }


        a {
            text-decoration: none;
        }


        img {
            max-width: 100%;
        }


        /*
        |--------------------------------------------------------------------------
        | Main Content
        |--------------------------------------------------------------------------
        */

        .site-main {
            min-height: calc(100vh - 160px);
        }


        /*
        |--------------------------------------------------------------------------
        | Public Navbar
        |--------------------------------------------------------------------------
        */

        .gh-navbar {

            background:
                rgba(255, 255, 255, 0.94) !important;

            backdrop-filter: blur(15px);

            -webkit-backdrop-filter: blur(15px);

            border-bottom:
                1px solid rgba(15, 81, 50, 0.08);

            min-height: 74px;

            z-index: 1030;

        }


        .gh-navbar .navbar-brand {

            color: var(--gh-dark);

            font-family: var(--gh-font-display);

            font-weight: 800;

            font-size: 1.25rem;

            letter-spacing: -0.03em;

        }


        .gh-navbar .navbar-brand:hover {
            color: var(--gh-green-700);
        }


        .brand-mark {

            width: 38px;

            height: 38px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    var(--gh-green-600),
                    var(--gh-green-800)
                );

            box-shadow:
                0 8px 20px
                rgba(22, 163, 74, 0.18);

        }


        .gh-navbar .nav-link {

            color: #526057;

            font-size: 0.9rem;

            font-weight: 600;

            padding-left: 0.8rem !important;

            padding-right: 0.8rem !important;

            transition:
                color 180ms ease;

        }


        .gh-navbar .nav-link:hover,
        .gh-navbar .nav-link:focus {
            color: var(--gh-green-700);
        }


        /*
        |--------------------------------------------------------------------------
        | Navbar Search
        |--------------------------------------------------------------------------
        */

        .nav-search {

            position: relative;

            max-width: 220px;

        }


        .nav-search .form-control {

            min-height: 40px;

            padding-right: 42px;

            border-radius: 12px;

            border:
                1px solid var(--gh-border);

            background: #f8faf9;

            font-size: 0.85rem;

        }


        .nav-search .btn {

            position: absolute;

            right: 3px;

            top: 50%;

            transform: translateY(-50%);

            width: 36px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 0;

            color: var(--gh-green-700);

            background: transparent;

        }


        /*
        |--------------------------------------------------------------------------
        | Page Hero
        |--------------------------------------------------------------------------
        */

        .page-hero {

            position: relative;

            overflow: hidden;

            padding: 68px 20px 58px;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #0b301b 0%,
                    #14532d 55%,
                    #166534 100%
                );

        }


        .page-hero::after {

            content: '';

            position: absolute;

            width: 320px;

            height: 320px;

            right: -100px;

            top: -160px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, 0.05);

        }


        .page-hero .container {
            position: relative;
            z-index: 1;
        }


        .page-hero h1 {

            color: #ffffff;

            font-size:
                clamp(2.2rem, 6vw, 4rem);

            margin-bottom: 0;

        }


        .section-eyebrow {

            margin-bottom: 8px;

            color: #86efac;

            font-size: 0.75rem;

            font-weight: 800;

            letter-spacing: 0.14em;

            text-transform: uppercase;

        }


        /*
        |--------------------------------------------------------------------------
        | Page Sections
        |--------------------------------------------------------------------------
        */

        .section-pad {
            padding: 64px 20px;
        }


        .section-title {

            font-size:
                clamp(1.8rem, 5vw, 2.7rem);

            margin-bottom: 1rem;

        }


        .soft-section {
            background: var(--gh-green-50);
        }


        /*
        |--------------------------------------------------------------------------
        | Cards
        |--------------------------------------------------------------------------
        */

        .auth-card,
        .summary-card {

            background: #ffffff;

            border:
                1px solid
                rgba(20, 83, 45, 0.08);

            border-radius: var(--gh-radius-lg);

            box-shadow: var(--gh-shadow);

        }


        .product-card {

            height: 100%;

            display: flex;

            flex-direction: column;

            overflow: hidden;

            background: #ffffff;

            border:
                1px solid var(--gh-border);

            border-radius: var(--gh-radius-lg);

            transition:
                transform 220ms ease,
                box-shadow 220ms ease,
                border-color 220ms ease;

        }


        .product-card:hover {

            transform:
                translateY(-5px);

            border-color:
                rgba(22, 163, 74, 0.25);

            box-shadow:
                0 20px 45px
                rgba(20, 83, 45, 0.11);

        }


        .product-image {

            width: 100%;

            aspect-ratio: 1 / 1;

            object-fit: cover;

            background: #edf5ef;

        }


        .product-info {

            flex: 1;

            display: flex;

            flex-direction: column;

            padding: 18px;

        }


        .product-category {

            margin-bottom: 6px;

            color: var(--gh-green-700);

            font-size: 0.72rem;

            font-weight: 800;

            letter-spacing: 0.1em;

            text-transform: uppercase;

        }


        .product-name {

            margin-bottom: 5px;

            font-size: 1.15rem;

        }


        .product-detail {

            margin-bottom: auto;

            color: var(--gh-muted);

            font-size: 0.88rem;

        }


        .product-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 16px;

            padding-top: 16px;

            border-top:
                1px solid var(--gh-border);

        }


        .price {

            color: var(--gh-green-800);

            font-family: 'Manrope', 'Inter', sans-serif;

            font-size: 1.25rem;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | Forms
        |--------------------------------------------------------------------------
        */

        .form-label {

            margin-bottom: 7px;

            color: #34483b;

            font-size: 0.88rem;

            font-weight: 700;

        }


        .form-control,
        .form-select {

            min-height: 48px;

            border:
                1px solid #dce5df;

            border-radius: 12px;

            background: #fbfdfb;

            font-size: 0.92rem;

            transition:
                border-color 180ms ease,
                box-shadow 180ms ease,
                background 180ms ease;

        }


        textarea.form-control {
            min-height: auto;
        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                var(--gh-green-600);

            background: #ffffff;

            box-shadow:
                0 0 0 4px
                rgba(22, 163, 74, 0.09);

        }


        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .btn {

            border-radius: 11px;

            font-weight: 700;

            transition:
                transform 180ms ease,
                background 180ms ease,
                color 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease;

        }


        .btn:hover {
            transform: translateY(-1px);
        }


        .btn-green {

            color: #ffffff;

            background:
                var(--gh-green-700);

            border:
                1px solid var(--gh-green-700);

        }


        .btn-green:hover,
        .btn-green:focus {

            color: #ffffff;

            background:
                var(--gh-green-800);

            border-color:
                var(--gh-green-800);

            box-shadow:
                0 10px 24px
                rgba(21, 128, 61, 0.18);

        }


        .btn-outline-green {

            color:
                var(--gh-green-800);

            background:
                transparent;

            border:
                1px solid
                rgba(21, 128, 61, 0.35);

        }


        .btn-outline-green:hover,
        .btn-outline-green:focus {

            color: #ffffff;

            background:
                var(--gh-green-700);

            border-color:
                var(--gh-green-700);

        }


        .btn-lg {
            padding: 12px 22px;
        }


        /*
        |--------------------------------------------------------------------------
        | Product Actions
        |--------------------------------------------------------------------------
        */

        .product-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 82px;

            min-height: 40px;

            padding: 8px 16px;

            border: 0;

            border-radius: 11px;

            background:
                var(--gh-green-700);

            color: #ffffff;

            font-size: 0.85rem;

            font-weight: 700;

            cursor: pointer;

        }


        .product-action:hover:not(:disabled) {
            background:
                var(--gh-green-800);
        }


        .product-action:disabled {

            cursor: not-allowed;

            background: #cbd5e1;

        }


        /*
        |--------------------------------------------------------------------------
        | Cart Badge
        |--------------------------------------------------------------------------
        */

        .cart-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 8px 13px;

            border:
                1px solid
                rgba(22, 163, 74, 0.16);

            border-radius: 999px;

            background:
                var(--gh-green-50);

            color:
                var(--gh-green-800);

            font-size: 0.85rem;

            font-weight: 700;

            transition:
                background 180ms ease,
                color 180ms ease;

        }


        .cart-badge:hover {

            background:
                var(--gh-green-100);

            color:
                var(--gh-green-900);

        }


        /*
        |--------------------------------------------------------------------------
        | Utility Classes
        |--------------------------------------------------------------------------
        */

        .text-muted {
            color:
                var(--gh-muted) !important;
        }


        .lead {
            line-height: 1.75;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 991.98px) {

            .nav-search {
                max-width: 100%;
            }

            .section-pad {
                padding:
                    48px 18px;
            }

        }


        @media (max-width: 575.98px) {

            .page-hero {

                padding:
                    48px 18px 42px;

            }

            .page-hero h1 {
                font-size: 2.25rem;
            }

            .auth-card,
            .summary-card {
                border-radius: 16px;
            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| Public Navigation
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/navbar.php';

?>


<main class="site-main">