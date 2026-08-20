<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - GLOBAL CART DRAWER
 * =========================================================
 *
 * Included once by navbar.php.
 *
 * Drawer data is loaded from:
 * cart.php?drawer=1
 *
 * Layout:
 * - Header remains visible
 * - Notice remains above products
 * - Product area scrolls
 * - Totals/footer remain visible
 * - Checkout button always visible
 * =========================================================
 */

?>

<style>

/* =========================================================
   CART OVERLAY
========================================================= */

.gh-cart-overlay {

    position:
        fixed;

    inset:
        0;

    z-index:
        1990;

    opacity:
        0;

    visibility:
        hidden;

    background:
        rgba(
            4,
            20,
            11,
            .34
        );

    backdrop-filter:
        blur(2px);

    transition:
        opacity .22s ease,
        visibility .22s ease;

}


.gh-cart-overlay.show {

    opacity:
        1;

    visibility:
        visible;

}


/* =========================================================
   CART DRAWER
========================================================= */

.gh-cart-drawer {

    position:
        fixed;

    z-index:
        2000;

    top:
        14px;

    right:
        14px;

    /*
     * Compact desktop width.
     */
    width:
        min(
            340px,
            calc(
                100vw - 28px
            )
        );

    /*
     * IMPORTANT:
     * Use the viewport height so footer
     * cannot fall outside of the screen.
     */
    height:
        calc(
            100vh - 28px
        );

    height:
        calc(
            100dvh - 28px
        );

    max-height:
        calc(
            100dvh - 28px
        );

    /*
     * Flex layout:
     *
     * Header  = natural height
     * Notice  = natural height
     * Body    = remaining space + scroll
     * Footer  = natural height
     */
    display:
        flex;

    flex-direction:
        column;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .11
        );

    border-radius:
        22px;

    background:
        #ffffff;

    box-shadow:
        0 28px 80px
        rgba(
            9,
            37,
            22,
            .22
        );

    transform:
        translateX(
            calc(
                100% + 30px
            )
        );

    transition:
        transform .3s
        cubic-bezier(
            .16,
            1,
            .3,
            1
        );

}


.gh-cart-drawer.open {

    transform:
        translateX(0);

}


/* =========================================================
   HEADER
========================================================= */

.gh-cart-drawer-header {

    position:
        relative;

    z-index:
        5;

    flex:
        0 0 auto;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        18px
        18px
        14px;

    border-bottom:
        1px solid
        rgba(
            20,
            83,
            45,
            .08
        );

    background:
        #ffffff;

}


.gh-cart-drawer-kicker {

    display:
        block;

    margin-bottom:
        2px;

    color:
        #15803d;

    font-size:
        .61rem;

    font-weight:
        850;

    letter-spacing:
        .13em;

    text-transform:
        uppercase;

}


.gh-cart-drawer-title {

    margin:
        0;

    color:
        #092516;

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.12rem;

    font-weight:
        800;

    line-height:
        1.2;

}


.gh-cart-drawer-close {

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

    padding:
        0;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .10
        );

    border-radius:
        10px;

    background:
        #ffffff;

    color:
        #526057;

    cursor:
        pointer;

    transition:
        background-color .2s ease,
        border-color .2s ease,
        color .2s ease;

}


.gh-cart-drawer-close:hover {

    border-color:
        rgba(
            21,
            128,
            61,
            .22
        );

    background:
        #f0fdf4;

    color:
        #166534;

}


/* =========================================================
   NOTICE
========================================================= */

.gh-cart-notice {

    position:
        relative;

    z-index:
        4;

    flex:
        0 0 auto;

    display:
        none;

    align-items:
        flex-start;

    gap:
        8px;

    margin:
        10px
        14px
        0;

    padding:
        9px
        10px;

    border-radius:
        10px;

    background:
        #f0fdf4;

    color:
        #166534;

    font-size:
        .72rem;

    font-weight:
        700;

    line-height:
        1.45;

}


.gh-cart-notice.show {

    display:
        flex;

}


.gh-cart-notice.error {

    background:
        #fef2f2;

    color:
        #b91c1c;

}


/* =========================================================
   SCROLLABLE BODY
========================================================= */

.gh-cart-drawer-body {

    /*
     * This is the only area allowed
     * to grow and scroll.
     */
    flex:
        1 1 auto;

    min-height:
        0;

    overflow-x:
        hidden;

    overflow-y:
        auto;

    padding:
        12px
        14px;

    overscroll-behavior:
        contain;

    scrollbar-width:
        thin;

    scrollbar-color:
        rgba(
            21,
            128,
            61,
            .25
        )
        transparent;

}


.gh-cart-drawer-body::-webkit-scrollbar {

    width:
        6px;

}


.gh-cart-drawer-body::-webkit-scrollbar-track {

    background:
        transparent;

}


.gh-cart-drawer-body::-webkit-scrollbar-thumb {

    border-radius:
        999px;

    background:
        rgba(
            21,
            128,
            61,
            .22
        );

}


/* =========================================================
   LOADING
========================================================= */

.gh-cart-loading {

    min-height:
        170px;

    display:
        grid;

    place-items:
        center;

    color:
        #647568;

    text-align:
        center;

    font-size:
        .78rem;

}


.gh-cart-loading
.spinner-border {

    width:
        1.35rem;

    height:
        1.35rem;

    margin-bottom:
        9px;

    color:
        #15803d;

}


/* =========================================================
   EMPTY CART
========================================================= */

.gh-mini-cart-empty {

    min-height:
        250px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    padding:
        28px
        18px;

    text-align:
        center;

}


.gh-mini-cart-empty-icon {

    width:
        52px;

    height:
        52px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        13px;

    border-radius:
        16px;

    background:
        #f0fdf4;

    color:
        #15803d;

    font-size:
        1.25rem;

}


.gh-mini-cart-empty h3 {

    margin:
        0
        0
        6px;

    color:
        #092516;

    font-size:
        .95rem;

    font-weight:
        800;

}


.gh-mini-cart-empty p {

    max-width:
        230px;

    margin:
        0
        0
        16px;

    color:
        #647568;

    font-size:
        .73rem;

    line-height:
        1.55;

}


/* =========================================================
   PRODUCT LIST
========================================================= */

.gh-mini-cart-products {

    display:
        grid;

    gap:
        8px;

}


/* =========================================================
   CART ITEM
========================================================= */

.gh-mini-cart-item {

    position:
        relative;

    display:
        grid;

    grid-template-columns:
        54px
        minmax(
            0,
            1fr
        )
        auto;

    gap:
        10px;

    align-items:
        start;

    padding:
        10px;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .08
        );

    border-radius:
        14px;

    background:
        #ffffff;

    transition:
        background-color .25s ease,
        border-color .25s ease,
        transform .2s ease;

}


.gh-mini-cart-item.just-added {

    border-color:
        rgba(
            21,
            128,
            61,
            .28
        );

    background:
        #f0fdf4;

}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.gh-mini-cart-image {

    width:
        54px;

    height:
        54px;

    overflow:
        hidden;

    border-radius:
        11px;

    background:
        #eaf6ec;

}


.gh-mini-cart-image img {

    width:
        100%;

    height:
        100%;

    display:
        block;

    object-fit:
        cover;

}


/* =========================================================
   PRODUCT INFO
========================================================= */

.gh-mini-cart-info {

    min-width:
        0;

}


.gh-mini-cart-name,
.gh-mini-cart-name:link,
.gh-mini-cart-name:visited {

    display:
        -webkit-box;

    overflow:
        hidden;

    margin-bottom:
        3px;

    color:
        #166534
        !important;

    font-size:
        .78rem;

    font-weight:
        800;

    line-height:
        1.35;

    text-decoration:
        none
        !important;

    -webkit-box-orient:
        vertical;

    -webkit-line-clamp:
        2;

}


.gh-mini-cart-name:hover {

    color:
        #15803d
        !important;

}


.gh-mini-cart-meta {

    color:
        #647568;

    font-size:
        .65rem;

    line-height:
        1.4;

}


.gh-mini-cart-line-total {

    color:
        #166534;

    font-size:
        .72rem;

    font-weight:
        800;

    white-space:
        nowrap;

}


/* =========================================================
   ITEM CONTROLS
========================================================= */

.gh-mini-cart-controls {

    grid-column:
        2 / 4;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        8px;

    margin-top:
        -2px;

}


/* =========================================================
   QUANTITY STEPPER
========================================================= */

.gh-mini-cart-stepper {

    display:
        inline-grid;

    grid-template-columns:
        28px
        34px
        28px;

    align-items:
        center;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .11
        );

    border-radius:
        9px;

    background:
        #ffffff;

}


.gh-mini-cart-stepper button {

    width:
        28px;

    height:
        28px;

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

    background:
        #f7faf7;

    color:
        #166534;

    font-size:
        .73rem;

    cursor:
        pointer;

}


.gh-mini-cart-stepper button:hover {

    background:
        #eaf6ec;

}


.gh-mini-cart-stepper input {

    width:
        34px;

    height:
        28px;

    padding:
        0;

    border:
        0;

    border-right:
        1px solid
        rgba(
            20,
            83,
            45,
            .08
        );

    border-left:
        1px solid
        rgba(
            20,
            83,
            45,
            .08
        );

    outline:
        none;

    background:
        #ffffff;

    color:
        #092516;

    font-size:
        .7rem;

    font-weight:
        800;

    text-align:
        center;

    -moz-appearance:
        textfield;

}


.gh-mini-cart-stepper
input::-webkit-outer-spin-button,
.gh-mini-cart-stepper
input::-webkit-inner-spin-button {

    margin:
        0;

    -webkit-appearance:
        none;

}


/* =========================================================
   REMOVE ITEM
========================================================= */

.gh-mini-cart-remove {

    width:
        28px;

    height:
        28px;

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
        #fee2e2;

    border-radius:
        8px;

    background:
        #ffffff;

    color:
        #b91c1c;

    cursor:
        pointer;

    transition:
        background-color .2s ease,
        border-color .2s ease;

}


.gh-mini-cart-remove:hover {

    border-color:
        #fecaca;

    background:
        #fff1f2;

}


/* =========================================================
   FOOTER
========================================================= */

.gh-cart-drawer-footer {

    position:
        relative;

    z-index:
        10;

    /*
     * Prevent footer from shrinking.
     */
    flex:
        0 0 auto;

    width:
        100%;

    padding:
        14px
        16px
        16px;

    border-top:
        1px solid
        rgba(
            20,
            83,
            45,
            .10
        );

    background:
        #ffffff;

    box-shadow:
        0 -10px 28px
        rgba(
            9,
            37,
            22,
            .055
        );

}


/* =========================================================
   TOTAL ROWS
========================================================= */

.gh-cart-drawer-row {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        14px;

    margin-bottom:
        7px;

    color:
        #647568;

    font-size:
        .70rem;

}


.gh-cart-drawer-row strong {

    color:
        #166534;

    font-weight:
        800;

}


.gh-cart-drawer-total {

    margin:
        10px
        0
        12px;

    padding-top:
        10px;

    border-top:
        1px solid
        rgba(
            20,
            83,
            45,
            .09
        );

    color:
        #092516;

    font-weight:
        800;

}


.gh-cart-drawer-total strong {

    color:
        #15803d;

    font-size:
        1rem;

}


/* =========================================================
   FOOTER ACTIONS
========================================================= */

.gh-cart-drawer-actions {

    display:
        grid;

    grid-template-columns:
        1fr
        1fr;

    gap:
        8px;

}


.gh-cart-drawer-actions
.btn {

    min-height:
        39px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        8px
        9px;

    border-radius:
        999px;

    font-size:
        .68rem;

    font-weight:
        800;

    text-decoration:
        none;

}


/* =========================================================
   CHECKOUT BUTTON
========================================================= */

.gh-cart-drawer-checkout {

    grid-column:
        1 / -1;

    width:
        100%;

    min-height:
        44px
        !important;

    display:
        inline-flex
        !important;

    align-items:
        center;

    justify-content:
        center;

    visibility:
        visible
        !important;

    opacity:
        1
        !important;

    border:
        1px solid
        #166534
        !important;

    border-radius:
        999px
        !important;

    background:
        linear-gradient(
            135deg,
            #15803d,
            #14532d
        )
        !important;

    color:
        #ffffff
        !important;

    font-size:
        .72rem
        !important;

    font-weight:
        800
        !important;

    text-decoration:
        none
        !important;

    box-shadow:
        0 9px 20px
        rgba(
            20,
            83,
            45,
            .16
        );

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        background .2s ease;

}


.gh-cart-drawer-checkout:hover {

    transform:
        translateY(-1px);

    background:
        linear-gradient(
            135deg,
            #16a34a,
            #166534
        )
        !important;

    color:
        #ffffff
        !important;

    box-shadow:
        0 12px 24px
        rgba(
            20,
            83,
            45,
            .20
        );

}


/* =========================================================
   SECONDARY FOOTER BUTTONS
========================================================= */

.gh-cart-drawer-actions
.btn-outline-green {

    border-color:
        rgba(
            20,
            83,
            45,
            .16
        );

    background:
        #ffffff;

    color:
        #166534;

}


.gh-cart-drawer-actions
.btn-outline-green:hover {

    border-color:
        rgba(
            21,
            128,
            61,
            .30
        );

    background:
        #f0fdf4;

    color:
        #15803d;

}


/* =========================================================
   MOBILE
========================================================= */

@media (
    max-width: 575.98px
) {

    .gh-cart-drawer {

        top:
            8px;

        right:
            8px;

        width:
            calc(
                100vw - 16px
            );

        height:
            calc(
                100vh - 16px
            );

        height:
            calc(
                100dvh - 16px
            );

        max-height:
            calc(
                100dvh - 16px
            );

        border-radius:
            18px;

    }


    .gh-cart-drawer-header {

        padding:
            15px
            15px
            12px;

    }


    .gh-cart-drawer-body {

        padding:
            10px
            12px;

    }


    .gh-cart-drawer-footer {

        padding:
            11px
            13px
            13px;

    }


    .gh-cart-drawer-checkout {

        min-height:
            45px
            !important;

    }

}


/* =========================================================
   SHORT SCREEN
========================================================= */

@media (
    max-height: 650px
) {

    .gh-cart-drawer-header {

        padding-top:
            12px;

        padding-bottom:
            10px;

    }


    .gh-cart-drawer-footer {

        padding-top:
            10px;

        padding-bottom:
            10px;

    }


    .gh-cart-drawer-row {

        margin-bottom:
            4px;

    }


    .gh-cart-drawer-total {

        margin-top:
            6px;

        margin-bottom:
            8px;

        padding-top:
            7px;

    }


    .gh-cart-drawer-actions
    .btn {

        min-height:
            35px;

    }


    .gh-cart-drawer-checkout {

        min-height:
            40px
            !important;

    }

}


/* =========================================================
   ACCESSIBILITY
========================================================= */

.gh-cart-drawer
button:focus-visible,
.gh-cart-drawer
a:focus-visible,
.gh-cart-drawer
input:focus-visible {

    outline:
        2px solid
        #22c55e;

    outline-offset:
        2px;

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-cart-drawer,
    .gh-cart-overlay,
    .gh-mini-cart-item,
    .gh-cart-drawer-checkout {

        transition:
            none;

    }

}

</style>



<!-- =========================================================
     OVERLAY
========================================================= -->

<div
    id="greenHarvestCartOverlay"
    class="gh-cart-overlay"
    data-cart-overlay
    aria-hidden="true"
></div>



<!-- =========================================================
     CART DRAWER
========================================================= -->

<aside
    id="greenHarvestCartDrawer"
    class="gh-cart-drawer"
    aria-hidden="true"
    aria-label="Shopping basket"
    data-cart-drawer
    data-cart-url="<?= e(
        url(
            'cart.php'
        )
    ) ?>"
    data-add-url="<?= e(
        url(
            'add-to-cart.php'
        )
    ) ?>"
    data-update-url="<?= e(
        url(
            'update-cart.php'
        )
    ) ?>"
    data-remove-url="<?= e(
        url(
            'remove-from-cart.php'
        )
    ) ?>"
    data-checkout-url="<?= e(
        url(
            'checkout.php'
        )
    ) ?>"
    data-shop-url="<?= e(
        url(
            'shop.php'
        )
    ) ?>"
    data-login-url="<?= e(
        url(
            'login.php?redirect=' .
            urlencode(
                'shop.php'
            )
        )
    ) ?>"
>


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="gh-cart-drawer-header">


        <div>


            <span class="gh-cart-drawer-kicker">

                Green Harvest

            </span>


            <h2 class="gh-cart-drawer-title">


                Your Basket


                <span
                    data-cart-drawer-count
                    aria-live="polite"
                >

                    (0)

                </span>


            </h2>


        </div>



        <button
            type="button"
            class="gh-cart-drawer-close"
            data-cart-close
            aria-label="Close basket"
        >

            <i class="bi bi-x-lg"></i>

        </button>


    </header>



    <!-- =====================================================
         STATUS NOTICE
    ====================================================== -->

    <div
        class="gh-cart-notice"
        data-cart-notice
        role="status"
        aria-live="polite"
    ></div>



    <!-- =====================================================
         SCROLLABLE PRODUCT AREA
    ====================================================== -->

    <div
        class="gh-cart-drawer-body"
        data-cart-drawer-body
    >


        <div class="gh-cart-loading">


            <div>


                <div
                    class="spinner-border"
                    role="status"
                    aria-hidden="true"
                ></div>


                <div>

                    Loading your basket...

                </div>


            </div>


        </div>


    </div>



    <!-- =====================================================
         FIXED FOOTER
    ====================================================== -->

    <footer
        class="gh-cart-drawer-footer"
        data-cart-drawer-footer
    >


        <!-- =============================================
             SUBTOTAL
        ============================================== -->

        <div class="gh-cart-drawer-row">


            <span>

                Subtotal

            </span>


            <strong data-cart-drawer-subtotal>

                GH₵ 0.00

            </strong>


        </div>



        <!-- =============================================
             DELIVERY
        ============================================== -->

        <div class="gh-cart-drawer-row">


            <span>

                Delivery

            </span>


            <strong data-cart-drawer-delivery>

                GH₵ 0.00

            </strong>


        </div>



        <!-- =============================================
             TOTAL
        ============================================== -->

        <div
            class="
                gh-cart-drawer-row
                gh-cart-drawer-total
            "
        >


            <span>

                Total

            </span>


            <strong data-cart-drawer-total>

                GH₵ 0.00

            </strong>


        </div>



        <!-- =============================================
             ACTIONS
        ============================================== -->

        <div class="gh-cart-drawer-actions">


            <!-- =========================================
                 CHECKOUT
            ========================================== -->

            <a
                href="<?= e(
                    url(
                        'checkout.php'
                    )
                ) ?>"
                class="
                    btn
                    btn-green
                    gh-cart-drawer-checkout
                "
                data-cart-checkout
            >

                Proceed to Checkout

                <i
                    class="
                        bi
                        bi-arrow-right
                        ms-1
                    "
                ></i>

            </a>



            <!-- =========================================
                 VIEW CART
            ========================================== -->

            <a
                href="<?= e(
                    url(
                        'cart.php'
                    )
                ) ?>"
                class="
                    btn
                    btn-outline-green
                "
            >

                <i
                    class="
                        bi
                        bi-basket
                        me-1
                    "
                ></i>

                View Basket

            </a>



            <!-- =========================================
                 KEEP SHOPPING
            ========================================== -->

            <button
                type="button"
                class="
                    btn
                    btn-outline-green
                "
                data-cart-close
            >

                Keep Shopping

            </button>


        </div>


    </footer>


</aside>