<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - PUBLIC NAVBAR
 * =========================================================
 *
 * Responsibilities:
 * - Display Green Harvest branding
 * - Display main navigation links
 * - Provide live product search
 * - Provide compact account icon
 * - Provide compact basket icon + live quantity
 * - Load the global cart drawer
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| Current Page
|--------------------------------------------------------------------------
*/

$currentPage =
    basename(
        (string) (
            $_SERVER['PHP_SELF']
            ?? 'index.php'
        )
    );


/*
|--------------------------------------------------------------------------
| Search Query
|--------------------------------------------------------------------------
*/

$searchQuery =
    trim(
        (string) (
            $_GET['q']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$user =
    (
        isset($user)
        &&
        is_array($user)
    )
        ? $user
        : null;


/*
|--------------------------------------------------------------------------
| Navbar Defaults
|--------------------------------------------------------------------------
*/

$cartTotalItems = 0;

$displayName =
    'Account';

$accountEmail =
    '';

$logoUrl =
    url(
        'assets/images/placeholder.svg'
    );

$searchUrl =
    url(
        'search.php'
    );

$suggestionUrl =
    url(
        'search-suggestions.php'
    );


/*
|--------------------------------------------------------------------------
| Basket Count
|--------------------------------------------------------------------------
*/

if (
    isLoggedIn()
    &&
    isset($pdo)
    &&
    $pdo instanceof PDO
) {

    try {

        $cartTotalItems =
            cartCount(
                $pdo
            );


    } catch (
        PDOException $e
    ) {

        error_log(
            'Green Harvest navbar cart count error: ' .
            $e->getMessage()
        );

    }

}


/*
|--------------------------------------------------------------------------
| Customer Display Information
|--------------------------------------------------------------------------
*/

if ($user !== null) {

    $fullName =
        trim(
            (string) (
                $user['full_name']
                ?? ''
            )
        );


    $accountEmail =
        trim(
            (string) (
                $user['email']
                ?? ''
            )
        );


    if ($fullName !== '') {

        $displayName =
            $fullName;

    }

}


/*
|--------------------------------------------------------------------------
| Active Navigation Helper
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'navActive'
    )
) {

    function navActive(
        string $page,
        string $currentPage
    ): string {

        if (
            $page ===
            $currentPage
        ) {

            return 'active';

        }


        /*
         * Keep Shop highlighted
         * when viewing a single product.
         */
        if (
            $page === 'shop.php'
            &&
            $currentPage === 'product.php'
        ) {

            return 'active';

        }


        return '';

    }

}


/*
|--------------------------------------------------------------------------
| Navigation Links
|--------------------------------------------------------------------------
*/

$navLinks = [

    [
        'index.php',
        'Home',
    ],

    [
        'shop.php',
        'Shop',
    ],

    [
        'category.php',
        'Categories',
    ],

    [
        'about.php',
        'About',
    ],

    [
        'contact.php',
        'Contact',
    ],

];

?>


<style>

/* =========================================================
   GREEN HARVEST NAVBAR
========================================================= */

.gh-navbar {

    padding:
        0;

    background:
        #ffffff;

    box-shadow:
        0 2px 8px
        rgba(
            0,
            0,
            0,
            .08
        );

}


.gh-navbar >
.gh-navbar-shell {

    position:
        relative;

    width:
        100%;

    min-height:
        88px;

    display:
        flex;

    flex-wrap:
        nowrap;

    align-items:
        center;

    justify-content:
        flex-start;

    gap:
        26px;

    padding:
        0
        32px;

}


/* =========================================================
   BRAND
========================================================= */

.gh-brand {

    position:
        relative;

    z-index:
        5;

    flex:
        0 0 auto;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        12px;

    margin:
        0;

    text-decoration:
        none;

    transition:
        opacity .2s ease;

}


.gh-brand:hover {

    opacity:
        .85;

}


.gh-logo-wrapper {

    width:
        48px;

    height:
        48px;

    flex:
        0 0 48px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        12px;

    background:
        rgba(
            22,
            163,
            74,
            .08
        );

}


.gh-site-logo {

    width:
        48px;

    height:
        48px;

    display:
        block;

    object-fit:
        contain;

}


.gh-logo-fallback {

    width:
        44px;

    height:
        44px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        10px;

    background:
        linear-gradient(
            135deg,
            var(--gh-green-600),
            var(--gh-green-700)
        );

    color:
        #ffffff;

    font-size:
        1.35rem;

}


.gh-brand-name {

    color:
        var(--gh-dark);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.4rem;

    font-weight:
        800;

    line-height:
        1.1;

    letter-spacing:
        -.04em;

    white-space:
        nowrap;

}


/* =========================================================
   NAVBAR COLLAPSE
========================================================= */

.gh-navbar-collapse {

    flex:
        1 1 auto;

    min-width:
        0;

}


/* =========================================================
   PRIMARY NAVIGATION
========================================================= */

.gh-primary-nav {

    display:
        flex;

    align-items:
        center;

    gap:
        4px;

    margin:
        0
        20px
        0
        clamp(
            18px,
            3vw,
            48px
        );

}


.gh-primary-nav
.nav-link {

    position:
        relative;

    min-height:
        88px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 11px
        !important;

    color:
        #526057;

    font-size:
        .86rem;

    font-weight:
        600;

    line-height:
        1;

    white-space:
        nowrap;

    transition:
        color .2s ease,
        background-color .2s ease;

}


.gh-primary-nav
.nav-link:hover,
.gh-primary-nav
.nav-link:focus-visible {

    color:
        var(--gh-green-700);

}


.gh-primary-nav
.nav-link.active {

    color:
        var(--gh-green-700)
        !important;

    font-weight:
        700;

}


.gh-primary-nav
.nav-link.active::after {

    content:
        '';

    position:
        absolute;

    right:
        11px;

    bottom:
        19px;

    left:
        11px;

    height:
        2.5px;

    border-radius:
        999px;

    background:
        var(--gh-green-600);

}


/* =========================================================
   NAV TOOLS / SEARCH
========================================================= */

.gh-nav-tools {

    min-width:
        0;

    display:
        flex;

    align-items:
        center;

    margin-left:
        auto;

}


.gh-search-wrapper {

    position:
        relative;

    flex:
        0 1 300px;

    width:
        300px;

}


.gh-nav-search {

    position:
        relative;

    width:
        100%;

}


.gh-nav-search
.form-control {

    width:
        100%;

    height:
        46px;

    padding:
        10px
        48px
        10px
        17px;

    border:
        1px solid
        var(--gh-border);

    border-radius:
        999px;

    background:
        #f7faf8;

    color:
        var(--gh-dark);

    font-size:
        .82rem;

    transition:
        background .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-nav-search
.form-control::placeholder {

    color:
        #9ca69f;

}


.gh-nav-search
.form-control:focus {

    border-color:
        var(--gh-green-600);

    background:
        #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(
            22,
            163,
            74,
            .10
        );

}


.gh-search-button {

    position:
        absolute;

    top:
        50%;

    right:
        5px;

    width:
        36px;

    height:
        36px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0;

    border:
        0;

    border-radius:
        50%;

    background:
        transparent;

    color:
        var(--gh-green-700);

    cursor:
        pointer;

    transform:
        translateY(-50%);

    transition:
        background .2s ease;

}


.gh-search-button:hover {

    background:
        var(--gh-green-50);

}


.gh-search-button:focus-visible {

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(
            22,
            163,
            74,
            .12
        );

}


/* =========================================================
   SEARCH SUGGESTIONS
========================================================= */

.gh-search-suggestions {

    position:
        absolute;

    z-index:
        1100;

    top:
        calc(
            100% + 10px
        );

    right:
        0;

    display:
        none;

    width:
        420px;

    max-height:
        520px;

    overflow-y:
        auto;

    padding:
        10px;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .12
        );

    border-radius:
        18px;

    background:
        #ffffff;

    box-shadow:
        0 24px 64px
        rgba(
            15,
            55,
            30,
            .18
        );

}


.gh-search-suggestions.show {

    display:
        block;

}


.search-suggestion-heading {

    padding:
        10px
        12px
        8px;

    color:
        var(--gh-muted);

    font-size:
        .68rem;

    font-weight:
        800;

    letter-spacing:
        .12em;

    text-transform:
        uppercase;

}


.search-product-item,
.search-category-item {

    width:
        100%;

    display:
        flex;

    align-items:
        center;

    color:
        var(--gh-text);

    text-decoration:
        none;

    transition:
        background .18s ease;

}


.search-product-item {

    gap:
        12px;

    padding:
        11px;

    border-radius:
        13px;

}


.search-category-item {

    gap:
        11px;

    padding:
        11px
        12px;

    border-radius:
        12px;

}


.search-product-item:hover,
.search-product-item.keyboard-active,
.search-category-item:hover,
.search-category-item.keyboard-active {

    background:
        var(--gh-green-50);

    color:
        var(--gh-text);

}


.search-product-image {

    width:
        56px;

    height:
        56px;

    flex:
        0 0 56px;

    object-fit:
        cover;

    border-radius:
        12px;

    background:
        #edf5ef;

}


.search-product-content {

    min-width:
        0;

    flex:
        1;

}


.search-product-name {

    display:
        block;

    overflow:
        hidden;

    margin-bottom:
        3px;

    color:
        var(--gh-dark);

    font-size:
        .86rem;

    font-weight:
        700;

    white-space:
        nowrap;

    text-overflow:
        ellipsis;

}


.search-product-meta {

    display:
        block;

    overflow:
        hidden;

    color:
        var(--gh-muted);

    font-size:
        .73rem;

    white-space:
        nowrap;

    text-overflow:
        ellipsis;

}


.search-product-price {

    flex-shrink:
        0;

    color:
        var(--gh-green-800);

    font-size:
        .79rem;

    font-weight:
        800;

}


.search-category-icon {

    width:
        36px;

    height:
        36px;

    flex:
        0 0 36px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        10px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

}


.search-category-text strong {

    display:
        block;

    color:
        var(--gh-dark);

    font-size:
        .84rem;

    font-weight:
        700;

}


.search-category-text small {

    margin-top:
        2px;

    color:
        var(--gh-muted);

    font-size:
        .72rem;

}


.search-state,
.search-loading {

    padding:
        20px
        18px;

    color:
        var(--gh-muted);

    font-size:
        .83rem;

    text-align:
        center;

}


.search-state i {

    display:
        block;

    margin-bottom:
        10px;

    color:
        var(--gh-green-600);

    font-size:
        1.5rem;

}


.search-view-all {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin-top:
        8px;

    padding:
        12px
        13px;

    border-top:
        1px solid
        var(--gh-border);

    color:
        var(--gh-green-800);

    font-size:
        .79rem;

    font-weight:
        800;

    text-decoration:
        none;

    transition:
        background .2s ease;

}


.search-view-all:hover {

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-900);

}


/* =========================================================
   RIGHT NAVBAR ACTIONS
========================================================= */

.gh-nav-actions {

    position:
        relative;

    z-index:
        1050;

    flex:
        0 0 auto;

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    margin-left:
        2px;

}


/* =========================================================
   SIMPLE ICON BUTTON
========================================================= */

.gh-nav-icon-button {

    position:
        relative;

    width:
        42px;

    height:
        42px;

    flex:
        0 0 42px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0;

    border:
        1px solid
        transparent;

    border-radius:
        50%;

    background:
        transparent;

    color:
        #253a2b;

    font-size:
        1.18rem;

    line-height:
        1;

    text-decoration:
        none;

    cursor:
        pointer;

    transition:
        background-color .18s ease,
        border-color .18s ease,
        color .18s ease,
        transform .18s ease;

}


.gh-nav-icon-button:hover,
.gh-nav-icon-button:focus-visible {

    border-color:
        rgba(
            21,
            128,
            61,
            .12
        );

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

}


.gh-nav-icon-button:hover {

    transform:
        translateY(-1px);

}


.gh-nav-icon-button:focus-visible {

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(
            22,
            163,
            74,
            .12
        );

}


/* =========================================================
   ACCOUNT DROPDOWN
========================================================= */

.gh-account-dropdown {

    position:
        relative;

}


.gh-account-menu {

    width:
        235px;

    margin-top:
        10px
        !important;

    overflow:
        hidden;

    padding:
        8px;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .11
        )
        !important;

    border-radius:
        15px;

    background:
        #ffffff;

    box-shadow:
        0 20px 50px
        rgba(
            9,
            37,
            22,
            .14
        );

}


/* =========================================================
   ACCOUNT DROPDOWN HEADER
========================================================= */

.gh-account-menu-header {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    padding:
        9px
        8px
        11px;

}


.gh-account-menu-avatar {

    width:
        38px;

    height:
        38px;

    flex:
        0 0 38px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        12px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

    font-size:
        .98rem;

}


.gh-account-menu-user {

    min-width:
        0;

    flex:
        1;

}


.gh-account-menu-user small {

    display:
        block;

    margin-bottom:
        1px;

    color:
        #839087;

    font-size:
        .54rem;

    font-weight:
        650;

}


.gh-account-menu-user strong {

    display:
        block;

    overflow:
        hidden;

    color:
        #092516;

    font-size:
        .7rem;

    font-weight:
        800;

    white-space:
        nowrap;

    text-overflow:
        ellipsis;

}


.gh-account-menu-email {

    display:
        block;

    overflow:
        hidden;

    margin-top:
        2px;

    color:
        #7c8b81;

    font-size:
        .55rem;

    white-space:
        nowrap;

    text-overflow:
        ellipsis;

}


/* =========================================================
   ACCOUNT MENU DIVIDER
========================================================= */

.gh-account-menu-divider {

    height:
        1px;

    margin:
        5px
        7px;

    background:
        rgba(
            20,
            83,
            45,
            .08
        );

}


/* =========================================================
   ACCOUNT MENU ITEMS
========================================================= */

.gh-account-menu-item {

    min-height:
        38px;

    display:
        flex
        !important;

    align-items:
        center;

    gap:
        9px;

    padding:
        8px
        10px
        !important;

    border-radius:
        9px;

    color:
        #45564a
        !important;

    font-size:
        .67rem;

    font-weight:
        700;

    text-decoration:
        none;

    transition:
        background-color .18s ease,
        color .18s ease;

}


.gh-account-menu-item i {

    width:
        17px;

    flex:
        0 0 17px;

    color:
        var(--gh-green-700);

    font-size:
        .82rem;

}


.gh-account-menu-item:hover,
.gh-account-menu-item:focus {

    background:
        var(--gh-green-50)
        !important;

    color:
        var(--gh-green-800)
        !important;

}


/* =========================================================
   BASKET ROW INSIDE ACCOUNT MENU
========================================================= */

.gh-account-menu-basket-count {

    min-width:
        19px;

    height:
        19px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-left:
        auto;

    padding:
        0
        5px;

    border-radius:
        999px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

    font-size:
        .52rem;

    font-weight:
        850;

}


/* =========================================================
   LOGOUT
========================================================= */

.gh-account-logout {

    color:
        #b91c1c
        !important;

}


.gh-account-logout i {

    color:
        #b91c1c;

}


.gh-account-logout:hover,
.gh-account-logout:focus {

    background:
        #fef2f2
        !important;

    color:
        #991b1b
        !important;

}


/* =========================================================
   CART ICON
========================================================= */

.gh-nav-cart-button {

    overflow:
        visible;

}


/* =========================================================
   CART COUNT BADGE
========================================================= */

.gh-cart-count {

    position:
        absolute;

    top:
        -3px;

    right:
        -4px;

    min-width:
        18px;

    height:
        18px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0
        5px;

    border:
        2px solid
        #ffffff;

    border-radius:
        999px;

    background:
        #22c55e;

    color:
        #052e16;

    font-size:
        .53rem;

    font-weight:
        900;

    line-height:
        1;

    box-shadow:
        0 2px 5px
        rgba(
            9,
            37,
            22,
            .12
        );

}


/*
 * Your cart.js can remove/add d-none
 * as the count changes.
 */
.gh-cart-count.d-none {

    display:
        none
        !important;

}


/* =========================================================
   MOBILE MENU TOGGLER
========================================================= */

.gh-navbar-toggler {

    position:
        relative;

    z-index:
        5;

    flex:
        0 0 auto;

    padding:
        4px
        6px;

    border:
        0;

    box-shadow:
        none
        !important;

}


/* =========================================================
   DESKTOP
========================================================= */

@media (
    min-width: 1200px
) {

    .navbar-expand-xl
    .gh-navbar-collapse {

        display:
            flex
            !important;

        flex-grow:
            1;

        flex-basis:
            auto
            !important;

        align-items:
            center;

        min-width:
            0;

    }


    .gh-primary-nav {

        position:
            static;

        flex:
            0 0 auto;

        transform:
            none;

    }


    .gh-nav-tools {

        flex:
            0 1 auto;

        min-width:
            0;

        margin-left:
            auto;

    }


    /*
     * Actions are outside the collapse
     * so there is only one account/cart
     * icon set on all screen sizes.
     */
    .gh-nav-actions {

        order:
            4;

    }

}


/* =========================================================
   MEDIUM DESKTOP
========================================================= */

@media (
    min-width: 1200px
)
and
(
    max-width: 1399.98px
) {

    .gh-navbar >
    .gh-navbar-shell {

        gap:
            15px;

        padding-right:
            20px;

        padding-left:
            20px;

    }


    .gh-brand {

        gap:
            9px;

    }


    .gh-brand-name {

        font-size:
            1.22rem;

    }


    .gh-logo-wrapper,
    .gh-site-logo {

        width:
            44px;

        height:
            44px;

    }


    .gh-logo-wrapper {

        flex-basis:
            44px;

    }


    .gh-primary-nav {

        gap:
            1px;

        margin-right:
            10px;

        margin-left:
            8px;

    }


    .gh-primary-nav
    .nav-link {

        padding:
            0 8px
            !important;

        font-size:
            .8rem;

    }


    .gh-primary-nav
    .nav-link.active::after {

        right:
            8px;

        left:
            8px;

    }


    .gh-search-wrapper {

        width:
            clamp(
                190px,
                17vw,
                245px
            );

        flex-basis:
            clamp(
                190px,
                17vw,
                245px
            );

    }


    .gh-nav-icon-button {

        width:
            39px;

        height:
            39px;

        flex-basis:
            39px;

        font-size:
            1.08rem;

    }

}


/* =========================================================
   LARGE DESKTOP
========================================================= */

@media (
    min-width: 1600px
) {

    .gh-navbar >
    .gh-navbar-shell {

        padding-right:
            48px;

        padding-left:
            48px;

    }


    .gh-search-wrapper {

        width:
            320px;

        flex-basis:
            320px;

    }

}


/* =========================================================
   TABLET / MOBILE
========================================================= */

@media (
    max-width: 1199.98px
) {

    .gh-navbar {

        padding:
            10px
            0;

    }


    .gh-navbar >
    .gh-navbar-shell {

        min-height:
            60px;

        flex-wrap:
            wrap;

        gap:
            9px;

        padding-right:
            20px;

        padding-left:
            20px;

    }


    /*
     * Mobile header row:
     *
     * Brand | account | basket | menu
     */

    .gh-brand {

        order:
            1;

        margin-right:
            auto;

    }


    .gh-nav-actions {

        order:
            2;

        margin-left:
            0;

    }


    .gh-navbar-toggler {

        order:
            3;

    }


    .gh-navbar-collapse {

        order:
            4;

        flex-basis:
            100%;

        width:
            100%;

        margin-top:
            10px;

        padding-top:
            13px;

        border-top:
            1px solid
            var(--gh-border);

    }


    .gh-primary-nav {

        flex-direction:
            column;

        align-items:
            stretch;

        gap:
            4px;

        margin:
            0
            0
            13px;

    }


    .gh-primary-nav
    .nav-link {

        min-height:
            0;

        justify-content:
            flex-start;

        padding:
            10px
            10px
            !important;

        border-radius:
            8px;

        font-size:
            .82rem;

    }


    .gh-primary-nav
    .nav-link.active::after {

        display:
            none;

    }


    .gh-primary-nav
    .nav-link.active {

        background:
            var(--gh-green-50);

    }


    .gh-nav-tools {

        width:
            100%;

        margin-left:
            0;

    }


    .gh-search-wrapper {

        width:
            100%;

        flex:
            1 1 100%;

    }


    .gh-search-suggestions {

        right:
            auto;

        left:
            0;

        width:
            100%;

        max-width:
            100%;

    }


    .gh-account-menu {

        position:
            absolute;

    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (
    max-width: 575.98px
) {

    .gh-navbar >
    .gh-navbar-shell {

        min-height:
            56px;

        gap:
            5px;

        padding-right:
            14px;

        padding-left:
            14px;

    }


    .gh-logo-wrapper,
    .gh-site-logo {

        width:
            40px;

        height:
            40px;

    }


    .gh-logo-wrapper {

        flex-basis:
            40px;

    }


    .gh-brand {

        gap:
            7px;

    }


    .gh-brand-name {

        font-size:
            1.05rem;

    }


    .gh-nav-actions {

        gap:
            1px;

    }


    .gh-nav-icon-button {

        width:
            36px;

        height:
            36px;

        flex-basis:
            36px;

        font-size:
            1.02rem;

    }


    .gh-cart-count {

        top:
            -3px;

        right:
            -3px;

        min-width:
            17px;

        height:
            17px;

        border-width:
            2px;

        font-size:
            .49rem;

    }


    .gh-navbar-toggler {

        padding:
            3px;

    }


    .gh-navbar-toggler
    .navbar-toggler-icon {

        width:
            1.3em;

        height:
            1.3em;

    }


    .gh-search-suggestions {

        left:
            -6px;

        width:
            calc(
                100% + 12px
            );

        max-height:
            480px;

    }


    .search-product-price {

        display:
            none;

    }


    .search-product-image {

        width:
            48px;

        height:
            48px;

        flex-basis:
            48px;

    }


    .gh-account-menu {

        width:
            min(
                235px,
                calc(
                    100vw - 28px
                )
            );

    }

}


/* =========================================================
   VERY SMALL PHONE
========================================================= */

@media (
    max-width: 390px
) {

    .gh-brand-name {

        font-size:
            .96rem;

    }


    .gh-logo-wrapper,
    .gh-site-logo {

        width:
            37px;

        height:
            37px;

    }


    .gh-logo-wrapper {

        flex-basis:
            37px;

    }


    .gh-nav-icon-button {

        width:
            34px;

        height:
            34px;

        flex-basis:
            34px;

    }

}


/* =========================================================
   EXTRA SMALL PHONE
========================================================= */

@media (
    max-width: 350px
) {

    .gh-brand-name {

        display:
            none;

    }

}


/* =========================================================
   ACCESSIBILITY
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-brand,
    .gh-nav-icon-button,
    .gh-nav-search
    .form-control {

        transition:
            none;

    }

}

</style>



<!-- =========================================================
     GREEN HARVEST NAVBAR
========================================================= -->

<nav
    class="
        navbar
        navbar-expand-xl
        navbar-light
        sticky-top
        gh-navbar
    "
    aria-label="Green Harvest main navigation"
>


    <div
        class="
            container-fluid
            gh-navbar-shell
        "
    >


        <!-- =================================================
             BRAND
        ================================================== -->

        <a
            class="
                navbar-brand
                gh-brand
            "
            href="<?= e(
                url(
                    'index.php'
                )
            ) ?>"
            aria-label="Green Harvest home"
        >


            <span class="gh-logo-wrapper">


                <img
                    src="<?= e(
                        $logoUrl
                    ) ?>"
                    alt="Green Harvest"
                    class="gh-site-logo"
                    width="48"
                    height="48"
                    onerror="
                        this.style.display='none';
                        this.nextElementSibling.style.display='inline-flex';
                    "
                >


                <span
                    class="gh-logo-fallback"
                    style="display:none;"
                    aria-hidden="true"
                >

                    <i class="bi bi-leaf-fill"></i>

                </span>


            </span>


            <span class="gh-brand-name">

                Green Harvest

            </span>


        </a>



        <!-- =================================================
             MOBILE / DESKTOP MENU TOGGLE
             Hidden automatically on XL desktop.
        ================================================== -->

        <button
            class="
                navbar-toggler
                gh-navbar-toggler
            "
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>



        <!-- =================================================
             COLLAPSIBLE NAVIGATION + SEARCH
        ================================================== -->

        <div
            class="
                collapse
                navbar-collapse
                gh-navbar-collapse
            "
            id="mainNavbar"
        >


            <!-- =============================================
                 LINKS
            ============================================== -->

            <ul
                class="
                    navbar-nav
                    gh-primary-nav
                    align-items-xl-center
                "
            >


                <?php foreach (
                    $navLinks as
                    [
                        $href,
                        $label
                    ]
                ): ?>


                    <li class="nav-item">


                        <a
                            class="
                                nav-link
                                <?= e(
                                    navActive(
                                        $href,
                                        $currentPage
                                    )
                                ) ?>
                            "
                            href="<?= e(
                                url(
                                    $href
                                )
                            ) ?>"
                        >

                            <?= e(
                                $label
                            ) ?>

                        </a>


                    </li>


                <?php endforeach; ?>


            </ul>



            <!-- =============================================
                 SEARCH
            ============================================== -->

            <div class="gh-nav-tools">


                <div class="gh-search-wrapper">


                    <form
                        action="<?= e(
                            $searchUrl
                        ) ?>"
                        method="get"
                        class="gh-nav-search"
                        id="greenHarvestSearchForm"
                        role="search"
                        autocomplete="off"
                    >


                        <input
                            class="form-control"
                            type="search"
                            name="q"
                            id="greenHarvestSearchInput"
                            value="<?= e(
                                $searchQuery
                            ) ?>"
                            minlength="2"
                            maxlength="100"
                            placeholder="Search foods..."
                            aria-label="Search Green Harvest products"
                            aria-autocomplete="list"
                            aria-controls="greenHarvestSuggestions"
                            aria-expanded="false"
                        >


                        <button
                            type="submit"
                            class="gh-search-button"
                            aria-label="Search"
                        >

                            <i class="bi bi-search"></i>

                        </button>


                    </form>



                    <!-- =====================================
                         LIVE SEARCH RESULTS
                    ====================================== -->

                    <div
                        id="greenHarvestSuggestions"
                        class="gh-search-suggestions"
                        role="listbox"
                        aria-label="Search suggestions"
                        data-suggestion-url="<?= e(
                            $suggestionUrl
                        ) ?>"
                        data-search-url="<?= e(
                            $searchUrl
                        ) ?>"
                    ></div>


                </div>


            </div>


        </div>



        <!-- =================================================
             ACCOUNT + BASKET ICONS

             IMPORTANT:
             This is outside the collapse.

             Desktop:
             appears at far-right.

             Mobile:
             remains visible beside menu toggle.
        ================================================== -->

        <div class="gh-nav-actions">


            <!-- =================================================
                 LOGGED-IN ACCOUNT
            ================================================== -->

            <?php if (
                isLoggedIn()
                &&
                $user !== null
            ): ?>


                <div
                    class="
                        dropdown
                        gh-account-dropdown
                    "
                >


                    <!-- =========================================
                         ACCOUNT ICON ONLY
                    ========================================== -->

                    <button
                        type="button"
                        class="gh-nav-icon-button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Open account menu"
                        title="My Account"
                    >

                        <i
                            class="bi bi-person-circle"
                            aria-hidden="true"
                        ></i>

                    </button>



                    <!-- =========================================
                         ACCOUNT DROPDOWN
                    ========================================== -->

                    <div
                        class="
                            dropdown-menu
                            dropdown-menu-end
                            gh-account-menu
                        "
                    >


                        <!-- =====================================
                             USER INFO
                        ====================================== -->

                        <div class="gh-account-menu-header">


                            <span
                                class="gh-account-menu-avatar"
                                aria-hidden="true"
                            >

                                <i class="bi bi-person-fill"></i>

                            </span>


                            <div class="gh-account-menu-user">


                                <small>

                                    Signed in as

                                </small>


                                <strong>

                                    <?= e(
                                        $displayName
                                    ) ?>

                                </strong>


                                <?php if (
                                    $accountEmail !== ''
                                ): ?>


                                    <span class="gh-account-menu-email">

                                        <?= e(
                                            $accountEmail
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </div>


                        </div>



                        <div class="gh-account-menu-divider"></div>



                        <!-- =====================================
                             ADMIN ACCOUNT
                        ====================================== -->

                        <?php if (
                            isAdmin()
                        ): ?>


                            <a
                                class="
                                    dropdown-item
                                    gh-account-menu-item
                                "
                                href="<?= e(
                                    url(
                                        'admin/dashboard.php'
                                    )
                                ) ?>"
                            >

                                <i class="bi bi-speedometer2"></i>

                                <span>

                                    Admin Dashboard

                                </span>

                            </a>



                        <?php else: ?>


                            <!-- =================================
                                 CUSTOMER ACCOUNT
                            ================================== -->

                            <a
                                class="
                                    dropdown-item
                                    gh-account-menu-item
                                "
                                href="<?= e(
                                    url(
                                        'account.php'
                                    )
                                ) ?>"
                            >

                                <i class="bi bi-person"></i>

                                <span>

                                    My Account

                                </span>

                            </a>



                            <!-- =================================
                                 ORDERS
                            ================================== -->

                            <a
                                class="
                                    dropdown-item
                                    gh-account-menu-item
                                "
                                href="<?= e(
                                    url(
                                        'orders.php'
                                    )
                                ) ?>"
                            >

                                <i class="bi bi-bag-check"></i>

                                <span>

                                    My Orders

                                </span>

                            </a>



                            <!-- =================================
                                 BASKET LINK IN ACCOUNT MENU
                            ================================== -->

                            <a
                                class="
                                    dropdown-item
                                    gh-account-menu-item
                                "
                                href="<?= e(
                                    url(
                                        'cart.php'
                                    )
                                ) ?>"
                            >

                                <i class="bi bi-basket2"></i>

                                <span>

                                    My Basket

                                </span>


                                <span
                                    class="gh-account-menu-basket-count<?= $cartTotalItems > 0 ? '' : ' d-none' ?>"
                                    data-cart-count
                                    aria-live="polite"
                                >

                                    <?= (int)
                                        $cartTotalItems ?>

                                </span>


                            </a>


                        <?php endif; ?>



                        <div class="gh-account-menu-divider"></div>



                        <!-- =====================================
                             LOGOUT
                        ====================================== -->

                        <a
                            class="
                                dropdown-item
                                gh-account-menu-item
                                gh-account-logout
                            "
                            href="<?= e(
                                url(
                                    'logout.php'
                                )
                            ) ?>"
                        >

                            <i class="bi bi-box-arrow-right"></i>

                            <span>

                                Sign Out

                            </span>

                        </a>


                    </div>


                </div>



            <?php else: ?>


                <!-- =================================================
                     GUEST ACCOUNT ICON
                ================================================== -->

                <a
                    href="<?= e(
                        url(
                            'login.php'
                        )
                    ) ?>"
                    class="gh-nav-icon-button"
                    aria-label="Sign in"
                    title="Sign In"
                >

                    <i
                        class="bi bi-person-circle"
                        aria-hidden="true"
                    ></i>

                </a>


            <?php endif; ?>



            <!-- =================================================
                 SIMPLE BASKET ICON

                 DO NOT REMOVE:
                 data-cart-open
                 data-cart-button
                 data-cart-count

                 They are used by assets/js/cart.js.
            ================================================== -->

            <a
                href="<?= e(
                    url(
                        'cart.php'
                    )
                ) ?>"
                class="
                    gh-nav-icon-button
                    gh-nav-cart-button
                "
                data-cart-open
                data-cart-button
                aria-label="Shopping basket with <?= (int) $cartTotalItems ?> item(s)"
                title="Basket"
            >


                <i
                    class="bi bi-basket2"
                    aria-hidden="true"
                ></i>



                <span
                    class="gh-cart-count<?= $cartTotalItems > 0 ? '' : ' d-none' ?>"
                    data-cart-count
                    aria-live="polite"
                >

                    <?= (int)
                        $cartTotalItems ?>

                </span>


            </a>


        </div>


    </div>


</nav>



<!-- =========================================================
     LIVE SEARCH JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const searchInput =
            document.getElementById(
                'greenHarvestSearchInput'
            );


        const searchForm =
            document.getElementById(
                'greenHarvestSearchForm'
            );


        const suggestionBox =
            document.getElementById(
                'greenHarvestSuggestions'
            );


        /*
         * Navbar can still function even if
         * search elements are unavailable.
         */
        if (
            !searchInput
            ||
            !searchForm
            ||
            !suggestionBox
        ) {

            return;

        }


        const suggestionUrl =
            suggestionBox.dataset
                .suggestionUrl
            || '';


        const searchResultsUrl =
            suggestionBox.dataset
                .searchUrl
            || '';


        let searchTimer =
            null;


        let requestController =
            null;


        let activeSuggestionIndex =
            -1;



        /* =====================================================
           CLEAR SUGGESTIONS
        ====================================================== */

        function clearSuggestions() {


            suggestionBox.innerHTML =
                '';


            suggestionBox.classList.remove(
                'show'
            );


            searchInput.setAttribute(
                'aria-expanded',
                'false'
            );


            activeSuggestionIndex =
                -1;

        }



        /* =====================================================
           OPEN SUGGESTIONS
        ====================================================== */

        function openSuggestions() {


            suggestionBox.classList.add(
                'show'
            );


            searchInput.setAttribute(
                'aria-expanded',
                'true'
            );

        }



        /* =====================================================
           CREATE SAFE DOM ELEMENT
        ====================================================== */

        function createElement(
            tag,
            className,
            text
        ) {


            const node =
                document.createElement(
                    tag
                );


            if (className) {

                node.className =
                    className;

            }


            if (text) {

                node.textContent =
                    text;

            }


            return node;

        }



        /* =====================================================
           LOADING
        ====================================================== */

        function showLoading() {


            suggestionBox.innerHTML =
                '';


            suggestionBox.appendChild(
                createElement(
                    'div',
                    'search-loading',
                    'Searching Green Harvest...'
                )
            );


            openSuggestions();

        }



        /* =====================================================
           VIEW ALL
        ====================================================== */

        function addViewAllLink(
            query
        ) {


            const link =
                createElement(
                    'a',
                    'search-view-all search-keyboard-item'
                );


            link.href =
                searchResultsUrl +
                '?q=' +
                encodeURIComponent(
                    query
                );


            link.appendChild(
                createElement(
                    'span',
                    '',
                    'View all results for "' +
                    query +
                    '"'
                )
            );


            link.appendChild(
                createElement(
                    'i',
                    'bi bi-arrow-right'
                )
            );


            suggestionBox.appendChild(
                link
            );

        }



        /* =====================================================
           NO RESULTS
        ====================================================== */

        function showNoResults(
            query
        ) {


            suggestionBox.innerHTML =
                '';


            const state =
                createElement(
                    'div',
                    'search-state'
                );


            state.appendChild(
                createElement(
                    'i',
                    'bi bi-search'
                )
            );


            state.appendChild(
                createElement(
                    'div',
                    '',
                    'No suggestions found for "' +
                    query +
                    '".'
                )
            );


            suggestionBox.appendChild(
                state
            );


            addViewAllLink(
                query
            );


            openSuggestions();

        }



        /* =====================================================
           PRODUCT RESULT
        ====================================================== */

        function createProductItem(
            product
        ) {


            const link =
                createElement(
                    'a',
                    'search-product-item search-keyboard-item'
                );


            link.href =
                product.url
                || '#';


            link.setAttribute(
                'role',
                'option'
            );


            const image =
                createElement(
                    'img',
                    'search-product-image'
                );


            image.src =
                product.image
                || '';


            image.alt =
                product.name
                || '';


            image.loading =
                'lazy';



            const content =
                createElement(
                    'span',
                    'search-product-content'
                );


            content.appendChild(
                createElement(
                    'span',
                    'search-product-name',
                    product.name
                    || ''
                )
            );


            content.appendChild(
                createElement(
                    'span',
                    'search-product-meta',
                    (
                        product.category
                        ||
                        'Fresh Produce'
                    )
                    +
                    ' • per '
                    +
                    (
                        product.unit
                        ||
                        'item'
                    )
                )
            );



            const price =
                createElement(
                    'span',
                    'search-product-price',
                    product.price
                    || ''
                );


            link.appendChild(
                image
            );


            link.appendChild(
                content
            );


            link.appendChild(
                price
            );


            return link;

        }



        /* =====================================================
           CATEGORY RESULT
        ====================================================== */

        function createCategoryItem(
            category
        ) {


            const link =
                createElement(
                    'a',
                    'search-category-item search-keyboard-item'
                );


            link.href =
                category.url
                || '#';


            link.setAttribute(
                'role',
                'option'
            );



            const icon =
                createElement(
                    'span',
                    'search-category-icon'
                );


            icon.appendChild(
                createElement(
                    'i',
                    'bi bi-grid'
                )
            );



            const content =
                createElement(
                    'span',
                    'search-category-text'
                );


            content.appendChild(
                createElement(
                    'strong',
                    '',
                    category.name
                    || ''
                )
            );


            content.appendChild(
                createElement(
                    'small',
                    '',
                    'Browse this category'
                )
            );


            link.appendChild(
                icon
            );


            link.appendChild(
                content
            );


            return link;

        }



        /* =====================================================
           RENDER RESULTS
        ====================================================== */

        function renderSuggestions(
            data,
            query
        ) {


            suggestionBox.innerHTML =
                '';


            activeSuggestionIndex =
                -1;


            const products =
                data
                &&
                Array.isArray(
                    data.products
                )
                    ? data.products
                    : [];


            const categories =
                data
                &&
                Array.isArray(
                    data.categories
                )
                    ? data.categories
                    : [];


            if (
                !data
                ||
                data.success !== true
                ||
                (
                    products.length === 0
                    &&
                    categories.length === 0
                )
            ) {

                showNoResults(
                    query
                );

                return;

            }



            if (
                products.length > 0
            ) {


                suggestionBox.appendChild(
                    createElement(
                        'div',
                        'search-suggestion-heading',
                        'Products'
                    )
                );


                products.forEach(
                    function (
                        product
                    ) {

                        suggestionBox.appendChild(
                            createProductItem(
                                product
                            )
                        );

                    }
                );

            }



            if (
                categories.length > 0
            ) {


                suggestionBox.appendChild(
                    createElement(
                        'div',
                        'search-suggestion-heading',
                        'Categories'
                    )
                );


                categories.forEach(
                    function (
                        category
                    ) {

                        suggestionBox.appendChild(
                            createCategoryItem(
                                category
                            )
                        );

                    }
                );

            }


            addViewAllLink(
                query
            );


            openSuggestions();

        }



        /* =====================================================
           LOAD SEARCH SUGGESTIONS
        ====================================================== */

        async function loadSuggestions(
            query
        ) {


            if (
                requestController
            ) {

                requestController.abort();

            }


            requestController =
                new AbortController();


            showLoading();


            try {


                const response =
                    await fetch(
                        suggestionUrl +
                        '?q=' +
                        encodeURIComponent(
                            query
                        ),
                        {

                            method:
                                'GET',

                            headers: {

                                Accept:
                                    'application/json'

                            },

                            signal:
                                requestController.signal

                        }
                    );


                if (
                    !response.ok
                ) {

                    throw new Error(
                        'Search request failed.'
                    );

                }


                const data =
                    await response.json();


                /*
                 * Ignore an old AJAX response
                 * after the customer has typed
                 * something different.
                 */
                if (
                    searchInput.value.trim()
                    !==
                    query
                ) {

                    return;

                }


                renderSuggestions(
                    data,
                    query
                );


            } catch (
                error
            ) {


                if (
                    error.name
                    ===
                    'AbortError'
                ) {

                    return;

                }


                suggestionBox.innerHTML =
                    '';


                suggestionBox.appendChild(
                    createElement(
                        'div',
                        'search-state',
                        'Search suggestions are unavailable right now.'
                    )
                );


                openSuggestions();

            }

        }



        /* =====================================================
           KEYBOARD HIGHLIGHT
        ====================================================== */

        function updateKeyboardSelection(
            items
        ) {


            items.forEach(
                function (
                    item,
                    index
                ) {

                    item.classList.toggle(
                        'keyboard-active',
                        index ===
                        activeSuggestionIndex
                    );

                }
            );


            if (
                items[
                    activeSuggestionIndex
                ]
            ) {

                items[
                    activeSuggestionIndex
                ]
                .scrollIntoView({

                    block:
                        'nearest'

                });

            }

        }



        /* =====================================================
           SEARCH INPUT
        ====================================================== */

        searchInput.addEventListener(
            'input',
            function () {


                const query =
                    searchInput.value.trim();


                window.clearTimeout(
                    searchTimer
                );


                if (
                    query.length < 2
                ) {


                    if (
                        requestController
                    ) {

                        requestController.abort();

                    }


                    clearSuggestions();


                    return;

                }


                searchTimer =
                    window.setTimeout(
                        function () {

                            loadSuggestions(
                                query
                            );

                        },
                        250
                    );

            }
        );



        /* =====================================================
           SEARCH FOCUS
        ====================================================== */

        searchInput.addEventListener(
            'focus',
            function () {


                if (
                    searchInput.value.trim().length
                    >=
                    2
                    &&
                    suggestionBox.children.length
                    >
                    0
                ) {

                    openSuggestions();

                }

            }
        );



        /* =====================================================
           KEYBOARD NAVIGATION
        ====================================================== */

        searchInput.addEventListener(
            'keydown',
            function (
                event
            ) {


                const items =
                    suggestionBox.querySelectorAll(
                        '.search-keyboard-item'
                    );


                if (
                    event.key
                    ===
                    'Escape'
                ) {

                    clearSuggestions();

                    return;

                }


                if (
                    items.length === 0
                ) {

                    return;

                }


                if (
                    event.key
                    ===
                    'ArrowDown'
                ) {

                    event.preventDefault();


                    activeSuggestionIndex =
                        (
                            activeSuggestionIndex
                            +
                            1
                        )
                        %
                        items.length;


                    updateKeyboardSelection(
                        items
                    );

                }


                if (
                    event.key
                    ===
                    'ArrowUp'
                ) {

                    event.preventDefault();


                    activeSuggestionIndex =
                        activeSuggestionIndex
                        <=
                        0
                            ? items.length - 1
                            : activeSuggestionIndex - 1;


                    updateKeyboardSelection(
                        items
                    );

                }


                if (
                    event.key
                    ===
                    'Enter'
                    &&
                    activeSuggestionIndex
                    >=
                    0
                    &&
                    items[
                        activeSuggestionIndex
                    ]
                ) {

                    event.preventDefault();


                    items[
                        activeSuggestionIndex
                    ].click();

                }

            }
        );



        /* =====================================================
           CLOSE WHEN CLICKING OUTSIDE SEARCH
        ====================================================== */

        document.addEventListener(
            'click',
            function (
                event
            ) {


                const wrapper =
                    searchInput.closest(
                        '.gh-search-wrapper'
                    );


                if (
                    wrapper
                    &&
                    !wrapper.contains(
                        event.target
                    )
                ) {

                    clearSuggestions();

                }

            }
        );



        /* =====================================================
           PREVENT EMPTY SEARCH
        ====================================================== */

        searchForm.addEventListener(
            'submit',
            function (
                event
            ) {


                if (
                    searchInput.value.trim().length
                    <
                    2
                ) {

                    event.preventDefault();

                    searchInput.focus();

                }

            }
        );


    }
);

</script>



<?php

/*
|--------------------------------------------------------------------------
| Global Cart Drawer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/cart-drawer.php';

?>


<script
    src="<?= e(
        url(
            'assets/js/cart.js'
        )
    ) ?>"
    defer
></script>