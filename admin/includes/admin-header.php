<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN HEADER
 * =========================================================
 *
 * Responsibilities:
 * - Protect the admin area
 * - Load shared admin navigation
 * - Display page title
 * - Display administrator information
 * - Open the admin page layout
 *
 * IMPORTANT:
 * admin-footer.php will close the elements opened here.
 * =========================================================
 */

require_once __DIR__ . '/../../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Page Information
|--------------------------------------------------------------------------
*/

$pageTitle =
    $pageTitle ?? 'Dashboard';


$currentPage =
    basename(
        $_SERVER['PHP_SELF']
        ?? 'dashboard.php'
    );


/*
|--------------------------------------------------------------------------
| Current Administrator
|--------------------------------------------------------------------------
*/

$adminUser = currentUser($pdo);


if (!$adminUser) {

    setFlash(
        'error',
        'Your administrator session has expired. Please sign in again.'
    );

    redirectTo('admin/login.php');
}


$adminName =
    trim(
        (string) (
            $adminUser['full_name']
            ?? 'Administrator'
        )
    );


$adminFirstName =
    explode(
        ' ',
        $adminName
    )[0];


/*
|--------------------------------------------------------------------------
| Navigation State
|--------------------------------------------------------------------------
*/

$productPages = [
    'products.php',
    'add-product.php',
    'edit-product.php',
];


$orderPages = [
    'orders.php',
    'order-details.php',
];

?>
<!DOCTYPE html>

<html
    lang="en"
    class="h-full bg-slate-100"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <title>
        <?= e($pageTitle) ?>
        | Green Harvest Admin
    </title>


    <!-- =====================================================
         Typography
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
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         Tailwind CSS
    ====================================================== -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- =====================================================
         Lucide Icons
    ====================================================== -->

    <script src="https://unpkg.com/lucide@latest"></script>


    <!-- =====================================================
         Tailwind Configuration
    ====================================================== -->

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {

                        sans: [
                            '"Plus Jakarta Sans"',
                            'sans-serif'
                        ]

                    },

                    colors: {

                        brand: {

                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d'

                        }

                    }

                }

            }

        };

    </script>


    <!-- =====================================================
         Admin Global Styles
    ====================================================== -->

    <style>

        html {
            scroll-behavior: smooth;
        }


        body {
            min-width: 320px;
        }


        /*
        ---------------------------------------------------------
        Forms
        ---------------------------------------------------------
        */

        input,
        select,
        textarea {

            outline: none;

        }


        input:focus,
        select:focus,
        textarea:focus {

            border-color: #16a34a !important;

            box-shadow:
                0 0 0 3px
                rgba(22, 163, 74, .12);

        }


        /*
        ---------------------------------------------------------
        Scrollbar
        ---------------------------------------------------------
        */

        ::-webkit-scrollbar {

            width: 8px;
            height: 8px;

        }


        ::-webkit-scrollbar-track {

            background: #f1f5f9;

        }


        ::-webkit-scrollbar-thumb {

            background: #cbd5e1;

            border-radius: 999px;

        }


        ::-webkit-scrollbar-thumb:hover {

            background: #94a3b8;

        }

    </style>

</head>


<body
    class="
        min-h-full
        font-sans
        text-slate-800
        antialiased
        bg-slate-100
    "
>


<!-- =========================================================
     ADMIN LAYOUT
========================================================= -->

<div
    class="
        min-h-screen
        flex
        flex-col
        md:flex-row
        w-full
    "
>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
        class="
            w-full
            md:w-64
            md:min-h-screen
            bg-slate-900
            text-slate-300
            flex-shrink-0
            flex
            flex-col
            justify-between
            border-r
            border-slate-800
        "
    >


        <div class="p-5 lg:p-6">


            <!-- =============================================
                 Logo
            ============================================== -->

            <a
                href="<?= url('admin/dashboard.php') ?>"
                class="
                    flex
                    items-center
                    gap-3
                    text-white
                    font-bold
                    text-xl
                    tracking-tight
                    mb-8
                    hover:text-white
                "
            >

                <span
                    class="
                        w-11
                        h-11
                        rounded-2xl
                        bg-white/5
                        border
                        border-white/10
                        flex
                        items-center
                        justify-center
                        overflow-hidden
                        shadow-lg
                        shadow-brand-900/20
                    "
                >

                    <img
                        src="<?= url('assets/images/placeholder.svg') ?>"
                        alt="Green Harvest logo"
                        class="
                            w-full
                            h-full
                            object-cover
                            block
                        "
                    >

                </span>


                <span class="leading-none">
                    Green Harvest
                </span>
            </a>



            <!-- =============================================
                 Navigation
            ============================================== -->

            <nav
                class="
                    space-y-1.5
                    font-medium
                    text-sm
                "
            >


                <!-- Dashboard -->

                <a
                    href="<?= url('admin/dashboard.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= $currentPage === 'dashboard.php'
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="layout-dashboard"
                        class="w-5 h-5"
                    ></i>

                    Dashboard

                </a>



                <!-- Products -->

                <a
                    href="<?= url('admin/products.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= in_array(
                            $currentPage,
                            $productPages,
                            true
                        )
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="package"
                        class="w-5 h-5"
                    ></i>

                    Products

                </a>



                <!-- Categories -->

                <a
                    href="<?= url('admin/categories.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= $currentPage === 'categories.php'
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="folder-tree"
                        class="w-5 h-5"
                    ></i>

                    Categories

                </a>



                <!-- Orders -->

                <a
                    href="<?= url('admin/orders.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= in_array(
                            $currentPage,
                            $orderPages,
                            true
                        )
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="shopping-bag"
                        class="w-5 h-5"
                    ></i>

                    Orders

                </a>



                <!-- Customers -->

                <a
                    href="<?= url('admin/customers.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= $currentPage === 'customers.php'
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="users"
                        class="w-5 h-5"
                    ></i>

                    Customers

                </a>

                <!-- Inbox / Feedback -->
                <a
                    href="<?= url('admin/admin-feedback.php') ?>"
                    class="
                        flex
                        items-center
                        gap-3
                        px-4
                        py-3
                        rounded-xl
                        transition-colors

                        <?= $currentPage === 'admin-feedback.php'
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20 font-semibold'
                            : 'hover:bg-slate-800 hover:text-white'
                        ?>
                    "
                >

                    <i
                        data-lucide="inbox"
                        class="w-5 h-5"
                    ></i>

                    Inbox

                </a>

            </nav>


        </div>



        <!-- =================================================
             Sidebar Footer
        ================================================== -->

        <div
            class="
                p-4
                mx-4
                mb-4
                bg-slate-800/60
                rounded-2xl
                border
                border-slate-700/50
            "
        >


            <!-- Admin -->

            <div
                class="
                    px-3
                    py-3
                    mb-2
                    border-b
                    border-slate-700
                "
            >

                <span
                    class="
                        block
                        text-xs
                        text-slate-500
                        mb-1
                    "
                >
                    Signed in as
                </span>


                <strong
                    class="
                        block
                        text-sm
                        text-white
                        truncate
                    "
                >

                    <?= e($adminName) ?>

                </strong>

            </div>



            <!-- Public Store -->

            <a
                href="<?= url('index.php') ?>"
                target="_blank"
                rel="noopener"
                class="
                    flex
                    items-center
                    justify-between
                    text-xs
                    font-semibold
                    text-slate-300
                    hover:text-white
                    px-3
                    py-2.5
                    rounded-lg
                    hover:bg-slate-700/50
                    transition-colors
                "
            >

                <span>
                    View Public Store
                </span>


                <i
                    data-lucide="external-link"
                    class="w-4 h-4"
                ></i>

            </a>



            <!-- Logout -->

            <a
                href="<?= url('admin/logout.php') ?>"
                class="
                    flex
                    items-center
                    justify-between
                    text-xs
                    font-semibold
                    text-rose-400
                    hover:text-rose-300
                    px-3
                    py-2.5
                    rounded-lg
                    hover:bg-rose-500/10
                    transition-colors
                "
            >

                <span>
                    Logout
                </span>


                <i
                    data-lucide="log-out"
                    class="w-4 h-4"
                ></i>

            </a>


        </div>


    </aside>



    <!-- =====================================================
         MAIN ADMIN AREA
    ====================================================== -->

    <div
        class="
            flex-1
            flex
            flex-col
            min-w-0
        "
    >


        <!-- =================================================
             Top Header
        ================================================== -->

        <header
            class="
                bg-white
                border-b
                border-slate-200
                px-5
                lg:px-8
                py-5
                flex
                flex-wrap
                items-center
                justify-between
                gap-4
            "
        >


            <div>


                <span
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-brand-600
                        block
                        mb-0.5
                    "
                >

                    Admin Area

                </span>


                <h1
                    class="
                        text-2xl
                        font-bold
                        text-slate-900
                    "
                >

                    <?= e($pageTitle) ?>

                </h1>


            </div>



            <div
                class="
                    flex
                    items-center
                    gap-3
                "
            >


               <!-- Administrator Badge -->

               <div
                   class="
                       hidden
                       sm:flex
                       items-center
                       gap-2
                       px-3
                       py-2
                       rounded-xl
                       bg-brand-50
                       border
                       border-brand-100
                   "
               >

                    <span
                        class="
                            w-8
                            h-8
                            rounded-full
                            bg-brand-600
                            text-white
                            flex
                            items-center
                            justify-center
                            font-bold
                            text-sm
                        "
                    >

                        <?= e(
                            strtoupper(
                                substr(
                                    $adminFirstName,
                                    0,
                                    1
                                )
                            )
                        ) ?>

                    </span>


                    <div>


                        <span
                            class="
                                block
                                text-xs
                                font-bold
                                text-slate-800
                            "
                        >

                            <?= e($adminFirstName) ?>

                        </span>


                        <span
                            class="
                                block
                                text-[10px]
                                font-semibold
                                text-brand-700
                            "
                        >

                            Administrator

                        </span>


                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             Main Page Content
        ================================================== -->

        <main
            class="
                flex-1
                p-5
                lg:p-8
                max-w-7xl
                w-full
                mx-auto
            "
        >


            <?php displayFlash(); ?>