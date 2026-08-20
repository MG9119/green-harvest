<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - SHOPPING CART
 * =========================================================
 *
 * Responsibilities:
 * - Display full shopping cart
 * - Supply AJAX mini-cart/cart-drawer data
 * - Calculate cart totals
 * - Show stock availability
 * - Update/remove products
 * - Provide drawer remove controls
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Detect Drawer Request
|--------------------------------------------------------------------------
*/

$isDrawerRequest =
    isset($_GET['drawer'])
    &&
    $_GET['drawer'] === '1';


/*
|--------------------------------------------------------------------------
| Cart Data
|--------------------------------------------------------------------------
*/

$items = [];

$totals = [

    'subtotal' => 0.00,

    'delivery' => 0.00,

    'total' => 0.00,

];

$cartLoadError = false;

$hasUnavailableItems = false;

$cartCountValue = 0;


/*
|--------------------------------------------------------------------------
| Load Cart
|--------------------------------------------------------------------------
*/

try {

    $items =
        getCartItems(
            $pdo
        );


    $totals =
        cartTotals(
            $items
        );


    foreach (
        $items as
        $item
    ) {

        $quantity =
            (int) (
                $item['quantity']
                ?? 0
            );


        $cartCountValue +=
            $quantity;


        $status =
            (string) (
                $item['status']
                ?? ''
            );


        $stock =
            (int) (
                $item['stock_quantity']
                ?? 0
            );


        if (
            $status !== 'active'
            ||
            $stock <= 0
            ||
            $quantity > $stock
        ) {

            $hasUnavailableItems =
                true;

        }

    }


} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest cart loading error: ' .
        $e->getMessage()
    );


    $cartLoadError =
        true;

}


/*
|--------------------------------------------------------------------------
| AJAX CART DRAWER RESPONSE
|--------------------------------------------------------------------------
*/

if ($isDrawerRequest) {


    header(
        'Content-Type: application/json; charset=utf-8'
    );


    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );


    /*
     * Failed to load basket.
     */
    if ($cartLoadError) {

        http_response_code(
            500
        );


        echo json_encode(
            [
                'success' =>
                    false,

                'message' =>
                    'We could not load your basket.',
            ],
            JSON_UNESCAPED_SLASHES
            |
            JSON_UNESCAPED_UNICODE
        );


        exit;

    }



    /*
    |--------------------------------------------------------------------------
    | Render Drawer HTML
    |--------------------------------------------------------------------------
    */

    ob_start();

    ?>


    <?php if (!$items): ?>


        <!-- =================================================
             EMPTY DRAWER
        ================================================== -->

        <div class="gh-mini-cart-empty">


            <span class="gh-mini-cart-empty-icon">

                <i class="bi bi-basket"></i>

            </span>


            <h3>

                Your basket is empty

            </h3>


            <p>

                Add something fresh and it
                will appear here.

            </p>


            <a
                href="<?= e(
                    url(
                        'shop.php'
                    )
                ) ?>"
                class="btn btn-green btn-sm"
            >

                Continue Shopping

            </a>


        </div>


    <?php else: ?>


        <!-- =================================================
             DRAWER PRODUCTS
        ================================================== -->

        <div class="gh-mini-cart-products">


            <?php foreach (
                $items as
                $item
            ): ?>


                <?php

                $productId =
                    (int)
                    $item['product_id'];


                $quantity =
                    (int)
                    $item['quantity'];


                $itemPrice =
                    (float)
                    $item['price'];


                $itemSubtotal =
                    isset(
                        $item['subtotal']
                    )
                        ? (float)
                            $item['subtotal']
                        : (
                            $itemPrice
                            *
                            $quantity
                        );


                $productName =
                    (string)
                    $item['name'];

                ?>


                <article
                    class="gh-mini-cart-item"
                    data-product-id="<?= $productId ?>"
                >


                    <!-- =====================================
                         IMAGE
                    ====================================== -->

                    <a
                        href="<?= e(
                            url(
                                'product.php?id=' .
                                $productId
                            )
                        ) ?>"
                        class="gh-mini-cart-image"
                    >


                        <img
                            src="<?= e(
                                productImageUrl(
                                    $item['image']
                                    ?? null
                                )
                            ) ?>"
                            alt="<?= e(
                                $productName
                            ) ?>"
                            loading="lazy"
                        >


                    </a>



                    <!-- =====================================
                         PRODUCT DETAILS
                    ====================================== -->

                    <div class="gh-mini-cart-info">


                        <a
                            href="<?= e(
                                url(
                                    'product.php?id=' .
                                    $productId
                                )
                            ) ?>"
                            class="gh-mini-cart-name"
                        >

                            <?= e(
                                $productName
                            ) ?>

                        </a>


                        <div class="gh-mini-cart-meta">


                            <span>

                                Qty <?= $quantity ?>

                            </span>


                            <span>

                                •

                            </span>


                            <span>

                                <?= money(
                                    $itemPrice
                                ) ?>

                            </span>


                        </div>


                    </div>



                    <!-- =====================================
                         LINE TOTAL
                    ====================================== -->

                    <strong class="gh-mini-cart-line-total">

                        <?= money(
                            $itemSubtotal
                        ) ?>

                    </strong>



                    <!-- =====================================
                         CONTROLS
                    ====================================== -->

                    <div class="gh-mini-cart-controls">


                        <span class="gh-mini-cart-quantity-display">

                            <i class="bi bi-basket2"></i>

                            <?= $quantity ?>

                            item<?= $quantity === 1
                                ? ''
                                : 's' ?>

                        </span>



                        <!-- =============================
                             REMOVE PRODUCT
                        ============================== -->

                        <form
                            method="post"
                            action="<?= e(
                                url(
                                    'remove-from-cart.php'
                                )
                            ) ?>"
                            class="
                                m-0
                                gh-mini-cart-remove-form
                            "
                            data-mini-cart-remove-form
                        >


                            <?= csrfField() ?>


                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $productId ?>"
                            >


                            <input
                                type="hidden"
                                name="redirect"
                                value="cart.php"
                            >


                            <button
                                type="submit"
                                class="gh-mini-cart-remove"
                                data-product-name="<?= e(
                                    $productName
                                ) ?>"
                                aria-label="Remove <?= e(
                                    $productName
                                ) ?> from basket"
                                title="Remove from basket"
                            >

                                <i class="bi bi-trash3"></i>

                            </button>


                        </form>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


    <?php

    $drawerHtml =
        ob_get_clean();


    /*
    |--------------------------------------------------------------------------
    | Return Drawer JSON
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [

            'success' =>
                true,

            'count' =>
                $cartCountValue,

            'html' =>
                $drawerHtml,

            'subtotal' =>
                (float)
                $totals['subtotal'],

            'subtotal_formatted' =>
                money(
                    $totals['subtotal']
                ),

            'delivery' =>
                (float)
                $totals['delivery'],

            'delivery_formatted' =>
                money(
                    $totals['delivery']
                ),

            'total' =>
                (float)
                $totals['total'],

            'total_formatted' =>
                money(
                    $totals['total']
                ),

            'cart_url' =>
                url(
                    'cart.php'
                ),

            'checkout_url' =>
                url(
                    'checkout.php'
                ),

            'has_unavailable_items' =>
                $hasUnavailableItems,

        ],
        JSON_UNESCAPED_SLASHES
        |
        JSON_UNESCAPED_UNICODE
    );


    exit;

}


/*
|--------------------------------------------------------------------------
| NORMAL CART PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Shopping Cart';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST CART
========================================================= */

.cart-layout {

    display:
        grid;

    grid-template-columns:
        minmax(0,1fr)
        310px;

    gap:
        24px;

    align-items:
        start;

}


/* =========================================================
   CART LIST
========================================================= */

.cart-list {

    overflow:
        hidden;

    border:
        1px solid
        var(
            --gh-border,
            rgba(23,79,42,.1)
        );

    border-radius:
        18px;

    background:
        #ffffff;

    box-shadow:
        0 12px 32px
        rgba(23,79,42,.055);

}


.cart-list-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        18px 20px;

    border-bottom:
        1px solid
        var(
            --gh-border,
            rgba(23,79,42,.1)
        );

}


.cart-list-header h2 {

    margin:
        0;

    color:
        var(
            --gh-green-800,
            #166534
        );

    font-size:
        1.2rem;

    font-weight:
        800;

}


.cart-item-count {

    color:
        var(
            --gh-green-700,
            #15803d
        );

    font-size:
        .76rem;

    font-weight:
        750;

}


/* =========================================================
   CART ITEM
========================================================= */

.cart-item {

    display:
        grid;

    grid-template-columns:
        minmax(235px,1fr)
        115px
        110px
        42px;

    gap:
        16px;

    align-items:
        center;

    padding:
        17px 20px;

    border-bottom:
        1px solid
        var(
            --gh-border,
            rgba(23,79,42,.09)
        );

}


.cart-item:last-child {

    border-bottom:
        0;

}


/* =========================================================
   PRODUCT
========================================================= */

.cart-product {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    min-width:
        0;

}


.cart-product-image {

    width:
        74px;

    height:
        74px;

    flex-shrink:
        0;

    overflow:
        hidden;

    border-radius:
        13px;

    background:
        #eaf6ec;

}


.cart-product-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.cart-product-info {

    min-width:
        0;

}


.cart-product-category {

    margin:
        0 0 3px;

    color:
        #15803d;

    font-size:
        .62rem;

    font-weight:
        800;

    letter-spacing:
        .07em;

    text-transform:
        uppercase;

}


.cart-product-name {

    margin:
        0 0 4px;

    font-size:
        .95rem;

    line-height:
        1.35;

}


.cart-product-name a {

    color:
        #166534
        !important;

    font-weight:
        800;

    text-decoration:
        none
        !important;

}


.cart-product-name a:hover {

    color:
        #15803d
        !important;

}


.cart-product-price {

    margin:
        0;

    color:
        #15803d;

    font-size:
        .75rem;

    font-weight:
        650;

}


/* =========================================================
   STOCK
========================================================= */

.stock-note {

    display:
        inline-block;

    margin-top:
        5px;

    font-size:
        .67rem;

    font-weight:
        700;

}


.stock-note.low {

    color:
        #b45309;

}


.stock-note.unavailable {

    color:
        #b91c1c;

}


/* =========================================================
   QUANTITY
========================================================= */

.quantity-label,
.cart-subtotal-label {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #15803d;

    font-size:
        .62rem;

    font-weight:
        750;

    text-transform:
        uppercase;

}


.quantity-form {

    display:
        flex;

    gap:
        5px;

}


.quantity-form
.form-control {

    width:
        66px;

    min-height:
        38px;

    padding:
        5px;

    color:
        #166534;

    font-size:
        .8rem;

    font-weight:
        700;

    text-align:
        center;

}


.quantity-update {

    width:
        38px;

    height:
        38px;

    min-width:
        38px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0;

}


/* =========================================================
   SUBTOTAL
========================================================= */

.cart-subtotal {

    color:
        #166534;

    font-size:
        .82rem;

    font-weight:
        800;

}


/* =========================================================
   REMOVE
========================================================= */

.remove-button {

    width:
        38px;

    height:
        38px;

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
        #fecaca;

    border-radius:
        10px;

    background:
        #ffffff;

    color:
        #b91c1c;

    cursor:
        pointer;

}


.remove-button:hover {

    background:
        #fff1f2;

    border-color:
        #fca5a5;

    color:
        #991b1b;

}


/* =========================================================
   CART ACTIONS
========================================================= */

.cart-actions {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        14px;

    padding:
        16px 20px;

    background:
        #fbfdfb;

}


.cart-actions-note {

    color:
        #647568;

    font-size:
        .7rem;

}


/* =========================================================
   ORDER SUMMARY
========================================================= */

.order-summary {

    position:
        sticky;

    top:
        90px;

    padding:
        21px;

    border-radius:
        17px;

}


.order-summary h2 {

    margin:
        0 0 18px;

    color:
        #166534;

    font-size:
        1.15rem;

    font-weight:
        800;

}


.summary-row {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    margin-bottom:
        11px;

    color:
        #647568;

    font-size:
        .79rem;

}


.summary-row strong {

    color:
        #166534;

}


.summary-divider {

    margin:
        15px 0;

    border-color:
        rgba(23,79,42,.1);

}


.summary-total {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    margin-bottom:
        18px;

}


.summary-total span {

    color:
        #166534;

    font-size:
        .82rem;

    font-weight:
        800;

}


.summary-total strong {

    color:
        #15803d;

    font-size:
        1.27rem;

    font-weight:
        800;

}


.delivery-note {

    display:
        flex;

    gap:
        8px;

    margin-top:
        14px;

    padding:
        10px;

    border-radius:
        10px;

    background:
        #f0fdf4;

    color:
        #166534;

    font-size:
        .67rem;

    line-height:
        1.45;

}


/* =========================================================
   WARNING
========================================================= */

.cart-warning {

    display:
        flex;

    gap:
        9px;

    margin-bottom:
        15px;

    padding:
        11px;

    border:
        1px solid
        #fde68a;

    border-radius:
        11px;

    background:
        #fffbeb;

    color:
        #92400e;

    font-size:
        .7rem;

}


/* =========================================================
   EMPTY CART
========================================================= */

.empty-cart {

    max-width:
        620px;

    margin:
        0 auto;

    padding:
        55px 25px;

    text-align:
        center;

}


.empty-cart-icon {

    width:
        66px;

    height:
        66px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        17px;

    border-radius:
        20px;

    background:
        #eaf6ec;

    color:
        #15803d;

    font-size:
        1.7rem;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 991.98px
) {

    .cart-layout {

        grid-template-columns:
            1fr;

    }


    .order-summary {

        position:
            static;

        max-width:
            500px;

    }

}


@media (
    max-width: 767.98px
) {

    .cart-item {

        grid-template-columns:
            1fr
            1fr;

    }


    .cart-product {

        grid-column:
            1 / -1;

    }


    .cart-item-remove {

        display:
            flex;

        justify-content:
            flex-end;

    }

}


@media (
    max-width: 575.98px
) {

    .cart-item {

        padding:
            16px;

    }


    .cart-product-image {

        width:
            66px;

        height:
            66px;

    }


    .cart-actions {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .cart-actions .btn {

        width:
            100%;

    }

}

</style>



<!-- =========================================================
     CART HERO
========================================================= -->

<section class="page-hero">


    <div class="container">


        <p class="section-eyebrow">

            Shopping Cart

        </p>


        <h1>

            Your fresh basket.

        </h1>


    </div>


</section>



<!-- =========================================================
     CART CONTENT
========================================================= -->

<section class="section-pad">


    <div class="container">


        <?php displayFlash(); ?>



        <?php if (
            $cartLoadError
        ): ?>


            <div class="alert alert-danger">

                We could not load your shopping cart.
                Please refresh the page and try again.

            </div>


        <?php endif; ?>



        <?php if (!$items): ?>


            <!-- =================================================
                 EMPTY CART
            ================================================== -->

            <div class="auth-card empty-cart">


                <span class="empty-cart-icon">

                    <i class="bi bi-basket"></i>

                </span>



                <?php if (
                    isLoggedIn()
                ): ?>


                    <h2>

                        Your cart is empty.

                    </h2>


                    <p>

                        Your basket is waiting for
                        something fresh. Browse Green
                        Harvest products and add your
                        favourites.

                    </p>


                    <a
                        href="<?= e(
                            url(
                                'shop.php'
                            )
                        ) ?>"
                        class="btn btn-green"
                    >

                        Start Shopping

                    </a>


                <?php else: ?>


                    <h2>

                        Sign in to view your cart.

                    </h2>


                    <p>

                        Green Harvest saves your
                        basket to your account.

                    </p>


                    <div
                        class="
                            d-flex
                            justify-content-center
                            gap-2
                        "
                    >


                        <a
                            href="<?= e(
                                url(
                                    'login.php?redirect=' .
                                    urlencode(
                                        'cart.php'
                                    )
                                )
                            ) ?>"
                            class="btn btn-green"
                        >

                            Sign In

                        </a>


                        <a
                            href="<?= e(
                                url(
                                    'register.php?redirect=' .
                                    urlencode(
                                        'cart.php'
                                    )
                                )
                            ) ?>"
                            class="btn btn-outline-green"
                        >

                            Create Account

                        </a>


                    </div>


                <?php endif; ?>


            </div>


        <?php else: ?>


            <div class="cart-layout">


                <!-- =================================================
                     PRODUCTS
                ================================================== -->

                <div class="cart-list">


                    <div class="cart-list-header">


                        <h2>

                            Your Products

                        </h2>


                        <span class="cart-item-count">

                            <?= $cartCountValue ?>

                            item<?= $cartCountValue === 1
                                ? ''
                                : 's' ?>

                        </span>


                    </div>



                    <?php foreach (
                        $items as
                        $item
                    ): ?>


                        <?php

                        $productId =
                            (int)
                            $item['product_id'];


                        $quantity =
                            (int)
                            $item['quantity'];


                        $stock =
                            (int)
                            $item['stock_quantity'];


                        $status =
                            (string)
                            $item['status'];


                        $available =
                            $status === 'active'
                            &&
                            $stock > 0;

                        ?>


                        <div class="cart-item">


                            <!-- =====================================
                                 PRODUCT
                            ====================================== -->

                            <div class="cart-product">


                                <a
                                    href="<?= e(
                                        url(
                                            'product.php?id=' .
                                            $productId
                                        )
                                    ) ?>"
                                    class="cart-product-image"
                                >


                                    <img
                                        src="<?= e(
                                            productImageUrl(
                                                $item['image']
                                                ?? null
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $item['name']
                                        ) ?>"
                                        loading="lazy"
                                    >


                                </a>



                                <div class="cart-product-info">


                                    <p class="cart-product-category">

                                        <?= e(
                                            $item['category_name']
                                            ??
                                            'Fresh Produce'
                                        ) ?>

                                    </p>


                                    <h3 class="cart-product-name">


                                        <a
                                            href="<?= e(
                                                url(
                                                    'product.php?id=' .
                                                    $productId
                                                )
                                            ) ?>"
                                        >

                                            <?= e(
                                                $item['name']
                                            ) ?>

                                        </a>


                                    </h3>


                                    <p class="cart-product-price">

                                        <?= money(
                                            $item['price']
                                        ) ?>

                                        /

                                        <?= e(
                                            $item['unit']
                                            ?: 'item'
                                        ) ?>

                                    </p>



                                    <?php if (
                                        !$available
                                    ): ?>


                                        <span class="stock-note unavailable">

                                            Currently unavailable

                                        </span>


                                    <?php elseif (
                                        $quantity >
                                        $stock
                                    ): ?>


                                        <span class="stock-note unavailable">

                                            Only <?= $stock ?> available

                                        </span>


                                    <?php elseif (
                                        $stock <= 10
                                    ): ?>


                                        <span class="stock-note low">

                                            Only <?= $stock ?> left

                                        </span>


                                    <?php endif; ?>


                                </div>


                            </div>



                            <!-- =====================================
                                 QUANTITY
                            ====================================== -->

                            <div>


                                <span class="quantity-label">

                                    Quantity

                                </span>


                                <?php if (
                                    $available
                                ): ?>


                                    <form
                                        method="post"
                                        action="<?= e(
                                            url(
                                                'update-cart.php'
                                            )
                                        ) ?>"
                                        class="quantity-form"
                                    >


                                        <?= csrfField() ?>


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $productId ?>"
                                        >


                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?= $quantity ?>"
                                            min="1"
                                            max="<?= max(
                                                1,
                                                $stock
                                            ) ?>"
                                            class="form-control"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-green
                                                quantity-update
                                            "
                                            aria-label="Update quantity"
                                        >

                                            <i class="bi bi-arrow-repeat"></i>

                                        </button>


                                    </form>


                                <?php else: ?>


                                    <strong class="text-danger">

                                        —

                                    </strong>


                                <?php endif; ?>


                            </div>



                            <!-- =====================================
                                 SUBTOTAL
                            ====================================== -->

                            <div>


                                <span class="cart-subtotal-label">

                                    Subtotal

                                </span>


                                <span class="cart-subtotal">

                                    <?= money(
                                        $item['subtotal']
                                    ) ?>

                                </span>


                            </div>



                            <!-- =====================================
                                 REMOVE
                            ====================================== -->

                            <div class="cart-item-remove">


                                <form
                                    method="post"
                                    action="<?= e(
                                        url(
                                            'remove-from-cart.php'
                                        )
                                    ) ?>"
                                    class="m-0"
                                >


                                    <?= csrfField() ?>


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= $productId ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="remove-button"
                                        data-confirm="Remove <?= e(
                                            $item['name']
                                        ) ?> from your cart?"
                                        aria-label="Remove <?= e(
                                            $item['name']
                                        ) ?>"
                                    >

                                        <i class="bi bi-trash3"></i>

                                    </button>


                                </form>


                            </div>


                        </div>


                    <?php endforeach; ?>



                    <!-- =================================================
                         CART ACTIONS
                    ================================================== -->

                    <div class="cart-actions">


                        <a
                            href="<?= e(
                                url(
                                    'shop.php'
                                )
                            ) ?>"
                            class="btn btn-outline-green"
                        >

                            <i class="bi bi-arrow-left me-1"></i>

                            Continue Shopping

                        </a>


                        <span class="cart-actions-note">

                            Update quantities individually above.

                        </span>


                    </div>


                </div>



                <!-- =================================================
                     ORDER SUMMARY
                ================================================== -->

                <aside>


                    <div class="summary-card order-summary">


                        <h2>

                            Order Summary

                        </h2>



                        <?php if (
                            $hasUnavailableItems
                        ): ?>


                            <div class="cart-warning">


                                <i class="bi bi-exclamation-triangle-fill"></i>


                                <div>

                                    Some products are unavailable
                                    or exceed available stock.

                                </div>


                            </div>


                        <?php endif; ?>



                        <div class="summary-row">


                            <span>

                                Subtotal

                            </span>


                            <strong>

                                <?= money(
                                    $totals['subtotal']
                                ) ?>

                            </strong>


                        </div>



                        <div class="summary-row">


                            <span>

                                Delivery

                            </span>


                            <strong>

                                <?= money(
                                    $totals['delivery']
                                ) ?>

                            </strong>


                        </div>



                        <hr class="summary-divider">



                        <div class="summary-total">


                            <span>

                                Grand Total

                            </span>


                            <strong>

                                <?= money(
                                    $totals['total']
                                ) ?>

                            </strong>


                        </div>



                        <?php if (
                            isLoggedIn()
                            &&
                            !$hasUnavailableItems
                        ): ?>


                            <a
                                href="<?= e(
                                    url(
                                        'checkout.php'
                                    )
                                ) ?>"
                                class="
                                    btn
                                    btn-green
                                    w-100
                                "
                            >

                                Proceed to Checkout

                                <i class="bi bi-arrow-right ms-1"></i>

                            </a>


                        <?php elseif (
                            isLoggedIn()
                        ): ?>


                            <button
                                type="button"
                                class="
                                    btn
                                    btn-secondary
                                    w-100
                                "
                                disabled
                            >

                                Update Cart First

                            </button>


                        <?php else: ?>


                            <a
                                href="<?= e(
                                    url(
                                        'login.php?redirect=' .
                                        urlencode(
                                            'checkout.php'
                                        )
                                    )
                                ) ?>"
                                class="
                                    btn
                                    btn-green
                                    w-100
                                "
                            >

                                Sign In to Checkout

                            </a>


                        <?php endif; ?>



                        <div class="delivery-note">


                            <i class="bi bi-truck"></i>


                            <span>

                                Delivery is currently

                                <?= money(
                                    $totals['delivery']
                                ) ?>

                                for this basket.

                            </span>


                        </div>


                    </div>


                </aside>


            </div>


        <?php endif; ?>


    </div>


</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>