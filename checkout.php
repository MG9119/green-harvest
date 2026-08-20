<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CHECKOUT
 * =========================================================
 *
 * Responsibilities:
 * - Require customer authentication
 * - Load and validate cart
 * - Collect delivery/customer information
 * - Recheck stock before placing order
 * - Create order and order items
 * - Reduce product stock
 * - Clear cart
 * - Redirect to order success page
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

requireLogin();


if (isAdmin()) {

    redirectTo(
        'admin/dashboard.php'
    );

}


$userId =
    getUserId();


if ($userId === null) {

    setFlash(
        'error',
        'Please sign in before checking out.'
    );


    redirectTo(
        'login.php?redirect=' .
        urlencode(
            'checkout.php'
        )
    );

}


/*
|--------------------------------------------------------------------------
| Current Customer
|--------------------------------------------------------------------------
*/

$user =
    currentUser(
        $pdo
    );


if (!$user) {

    setFlash(
        'error',
        'Your customer account could not be loaded.'
    );


    redirectTo(
        'login.php'
    );

}


/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

try {

    $cartItems =
        getCartItems(
            $pdo
        );


} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest checkout cart error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'Your cart could not be loaded.'
    );


    redirectTo(
        'cart.php'
    );

}


if (!$cartItems) {

    setFlash(
        'warning',
        'Your cart is empty.'
    );


    redirectTo(
        'cart.php'
    );

}


/*
|--------------------------------------------------------------------------
| Validate Cart Before Displaying Checkout
|--------------------------------------------------------------------------
*/

foreach (
    $cartItems as
    $item
) {

    $stock =
        (int) (
            $item['stock_quantity']
            ?? 0
        );


    $quantity =
        (int) (
            $item['quantity']
            ?? 0
        );


    $status =
        (string) (
            $item['status']
            ?? ''
        );


    if (
        $status !== 'active'
        ||
        $stock <= 0
        ||
        $quantity > $stock
    ) {

        setFlash(
            'warning',
            'Please update your cart before proceeding to checkout.'
        );


        redirectTo(
            'cart.php'
        );

    }

}


/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/

$totals =
    cartTotals(
        $cartItems
    );


/*
|--------------------------------------------------------------------------
| Payment Methods
|--------------------------------------------------------------------------
*/

$paymentMethods = [

    'Mobile Money',

    'Cash on Delivery',

    'Bank Transfer',

];


/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$customerName =
    (string) (
        $user['full_name']
        ?? ''
    );


$email =
    (string) (
        $user['email']
        ?? ''
    );


$phone =
    (string) (
        $user['phone']
        ?? ''
    );


$deliveryAddress =
    (string) (
        $user['address']
        ?? ''
    );


$city = '';

$region = '';

$deliveryInstructions = '';

$paymentMethod = '';


/*
|--------------------------------------------------------------------------
| Handle Order Submission
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | Preserve Submitted Information
    |--------------------------------------------------------------------------
    */

    $customerName =
        trim(
            (string) (
                $_POST['customer_name']
                ?? ''
            )
        );


    $email =
        strtolower(
            trim(
                (string) (
                    $_POST['email']
                    ?? ''
                )
            )
        );


    $phone =
        trim(
            (string) (
                $_POST['phone']
                ?? ''
            )
        );


    $deliveryAddress =
        trim(
            (string) (
                $_POST['delivery_address']
                ?? ''
            )
        );


    $city =
        trim(
            (string) (
                $_POST['city']
                ?? ''
            )
        );


    $region =
        trim(
            (string) (
                $_POST['region']
                ?? ''
            )
        );


    $deliveryInstructions =
        trim(
            (string) (
                $_POST['delivery_instructions']
                ?? ''
            )
        );


    $paymentMethod =
        trim(
            (string) (
                $_POST['payment_method']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        setFlash(
            'error',
            'Invalid checkout request. Please try again.'
        );

    } else {


        /*
        |--------------------------------------------------------------------------
        | Validate Checkout Information
        |--------------------------------------------------------------------------
        */

        $errors = [];


        if (
            strlen(
                $customerName
            ) < 2
            ||
            strlen(
                $customerName
            ) > 120
        ) {

            $errors[] =
                'Please enter a valid customer name.';

        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
            ||
            strlen(
                $email
            ) > 150
        ) {

            $errors[] =
                'Please enter a valid email address.';

        }


        if (
            strlen(
                $phone
            ) < 7
            ||
            strlen(
                $phone
            ) > 30
        ) {

            $errors[] =
                'Please enter a valid phone number.';

        }


        if (
            strlen(
                $deliveryAddress
            ) < 5
        ) {

            $errors[] =
                'Please enter a valid delivery address.';

        }


        if (
            strlen(
                $city
            ) < 2
            ||
            strlen(
                $city
            ) > 100
        ) {

            $errors[] =
                'Please enter a valid city.';

        }


        if (
            strlen(
                $region
            ) < 2
            ||
            strlen(
                $region
            ) > 100
        ) {

            $errors[] =
                'Please enter a valid region.';

        }


        if (
            strlen(
                $deliveryInstructions
            ) > 2000
        ) {

            $errors[] =
                'Delivery instructions are too long.';

        }


        if (
            !in_array(
                $paymentMethod,
                $paymentMethods,
                true
            )
        ) {

            $errors[] =
                'Please select a valid payment method.';

        }


        /*
        |--------------------------------------------------------------------------
        | Create Order
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            try {


                /*
                 * Start transaction.
                 */
                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Reload and Lock Cart / Products
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare(
                        '
                        SELECT
                            c.product_id,
                            c.quantity,

                            p.name,
                            p.price,
                            p.stock_quantity,
                            p.status

                        FROM carts c

                        INNER JOIN products p
                            ON p.id = c.product_id

                        WHERE c.user_id = ?

                        FOR UPDATE
                        '
                    );


                $stmt->execute([
                    $userId,
                ]);


                $freshCart =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                if (!$freshCart) {

                    throw new DomainException(
                        'Your cart is empty.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Validate Fresh Stock
                |--------------------------------------------------------------------------
                */

                foreach (
                    $freshCart as
                    &$item
                ) {

                    $requestedQuantity =
                        (int)
                        $item['quantity'];


                    $availableStock =
                        (int)
                        $item['stock_quantity'];


                    if (
                        $item['status']
                        !==
                        'active'
                    ) {

                        throw new DomainException(
                            $item['name'] .
                            ' is no longer available.'
                        );

                    }


                    if (
                        $availableStock
                        <=
                        0
                    ) {

                        throw new DomainException(
                            $item['name'] .
                            ' is currently out of stock.'
                        );

                    }


                    if (
                        $requestedQuantity
                        >
                        $availableStock
                    ) {

                        throw new DomainException(
                            'Only ' .
                            $availableStock .
                            ' unit(s) of ' .
                            $item['name'] .
                            ' are currently available.'
                        );

                    }


                    $item['subtotal'] =
                        round(
                            (
                                (float)
                                $item['price']
                            )
                            *
                            $requestedQuantity,
                            2
                        );

                }


                unset(
                    $item
                );


                /*
                |--------------------------------------------------------------------------
                | Recalculate Total
                |--------------------------------------------------------------------------
                */

                $freshTotals =
                    cartTotals(
                        $freshCart
                    );


                $orderTotal =
                    round(
                        (float)
                        $freshTotals['total'],
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Generate Order Number
                |--------------------------------------------------------------------------
                */

                $orderNumber =
                    generateOrderNumber(
                        $pdo
                    );


                /*
                |--------------------------------------------------------------------------
                | Create Order
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare(
                        '
                        INSERT INTO orders (
                            user_id,
                            order_number,
                            customer_name,
                            email,
                            phone,
                            delivery_address,
                            city,
                            region,
                            delivery_instructions,
                            payment_method,
                            total_amount,
                            order_status
                        )
                        VALUES (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                        '
                    );


                $stmt->execute([

                    $userId,

                    $orderNumber,

                    $customerName,

                    $email,

                    $phone,

                    $deliveryAddress,

                    $city,

                    $region,

                    $deliveryInstructions !== ''
                        ? $deliveryInstructions
                        : null,

                    $paymentMethod,

                    $orderTotal,

                    'Pending',

                ]);


                $orderId =
                    (int)
                    $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | Prepare Order Item Insert
                |--------------------------------------------------------------------------
                */

                $itemStmt =
                    $pdo->prepare(
                        '
                        INSERT INTO order_items (
                            order_id,
                            product_id,
                            product_name,
                            price,
                            quantity,
                            subtotal
                        )
                        VALUES (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                        '
                    );


                /*
                |--------------------------------------------------------------------------
                | Prepare Stock Update
                |--------------------------------------------------------------------------
                */

                $stockStmt =
                    $pdo->prepare(
                        '
                        UPDATE products

                        SET
                            stock_quantity =
                            stock_quantity - ?

                        WHERE id = ?
                          AND status = ?
                          AND stock_quantity >= ?
                        '
                    );


                /*
                |--------------------------------------------------------------------------
                | Insert Items and Reduce Stock
                |--------------------------------------------------------------------------
                */

                foreach (
                    $freshCart as
                    $item
                ) {

                    $productId =
                        (int)
                        $item['product_id'];


                    $quantity =
                        (int)
                        $item['quantity'];


                    $price =
                        (float)
                        $item['price'];


                    $subtotal =
                        (float)
                        $item['subtotal'];


                    /*
                     * Save order item.
                     */
                    $itemStmt->execute([

                        $orderId,

                        $productId,

                        $item['name'],

                        $price,

                        $quantity,

                        $subtotal,

                    ]);


                    /*
                     * Reduce stock.
                     */
                    $stockStmt->execute([

                        $quantity,

                        $productId,

                        'active',

                        $quantity,

                    ]);


                    if (
                        $stockStmt->rowCount()
                        !==
                        1
                    ) {

                        throw new DomainException(
                            'Stock changed for ' .
                            $item['name'] .
                            '. Please review your cart.'
                        );

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Clear Customer Cart
                |--------------------------------------------------------------------------
                */

                clearCart(
                    $pdo,
                    $userId
                );


                /*
                |--------------------------------------------------------------------------
                | Commit
                |--------------------------------------------------------------------------
                */

                $pdo->commit();


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                redirectTo(
                    'order-success.php?order_id=' .
                    $orderId
                );


            } catch (
                DomainException $e
            ) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();

                }


                setFlash(
                    'warning',
                    $e->getMessage()
                );


                redirectTo(
                    'cart.php'
                );


            } catch (
                Throwable $e
            ) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();

                }


                error_log(
                    'Green Harvest checkout order error: ' .
                    $e->getMessage()
                );


                setFlash(
                    'error',
                    'Your order could not be completed. Please try again.'
                );

            }

        } else {

            setFlash(
                'error',
                implode(
                    ' ',
                    $errors
                )
            );

        }

    }

}


/*
|--------------------------------------------------------------------------
| Render Checkout
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Checkout';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST CHECKOUT
========================================================= */

.gh-checkout-page {

    --checkout-dark:
        #092516;

    --checkout-dark-2:
        #14532d;

    --checkout-green:
        #15803d;

    --checkout-green-light:
        #22c55e;

    --checkout-lime:
        #86efac;

    --checkout-soft:
        #f0fdf4;

    --checkout-bg:
        #f5f9f6;

    --checkout-white:
        #ffffff;

    --checkout-ink:
        #102519;

    --checkout-text:
        #34463a;

    --checkout-muted:
        #718078;

    --checkout-border:
        rgba(
            20,
            83,
            45,
            .11
        );

}


/* =========================================================
   PAGE INTRO
========================================================= */

.gh-checkout-intro {

    padding:
        60px
        20px
        42px;

    background:
        #ffffff;

}


.gh-checkout-intro-inner {

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        35px;

}


.gh-checkout-eyebrow {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        12px;

    color:
        var(--checkout-green);

    font-size:
        .64rem;

    font-weight:
        800;

    letter-spacing:
        .13em;

    text-transform:
        uppercase;

}


.gh-checkout-eyebrow::before {

    content:
        "";

    width:
        25px;

    height:
        2px;

    border-radius:
        999px;

    background:
        var(--checkout-green);

}


.gh-checkout-intro h1 {

    margin:
        0
        0
        10px;

    color:
        var(--checkout-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            3rem,
            7vw,
            5rem
        );

    font-weight:
        800;

    line-height:
        .96;

    letter-spacing:
        -.06em;

}


.gh-checkout-intro-description {

    max-width:
        610px;

    margin:
        0;

    color:
        var(--checkout-muted);

    font-size:
        .84rem;

    line-height:
        1.7;

}


/* =========================================================
   CHECKOUT STEPS
========================================================= */

.gh-checkout-progress {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    flex-shrink:
        0;

    padding:
        8px
        11px;

    border:
        1px solid
        var(--checkout-border);

    border-radius:
        999px;

    background:
        #ffffff;

}


.gh-checkout-progress-item {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--checkout-muted);

    font-size:
        .58rem;

    font-weight:
        750;

}


.gh-checkout-progress-item.active {

    color:
        var(--checkout-green);

}


.gh-checkout-progress-dot {

    width:
        7px;

    height:
        7px;

    border-radius:
        50%;

    background:
        #d8e1da;

}


.gh-checkout-progress-item.active
.gh-checkout-progress-dot {

    background:
        var(--checkout-green);

}


.gh-checkout-progress-line {

    width:
        18px;

    height:
        1px;

    background:
        #dbe3dd;

}


/* =========================================================
   MAIN SECTION
========================================================= */

.gh-checkout-section {

    padding:
        28px
        20px
        90px;

    background:
        var(--checkout-bg);

}


/* =========================================================
   LAYOUT
========================================================= */

.gh-checkout-layout {

    display:
        grid;

    grid-template-columns:
        minmax(
            0,
            1fr
        )
        365px;

    gap:
        26px;

    align-items:
        start;

}


/* =========================================================
   FORM BACKGROUND
========================================================= */

.gh-checkout-form-panel {

    padding:
        32px;

    border-radius:
        24px;

    background:
        linear-gradient(
            180deg,
            #f8fbf8 0%,
            #edf7ef 100%
        );

}


/* =========================================================
   FORM CARD
========================================================= */

.gh-checkout-card {

    position:
        relative;

    overflow:
        hidden;

    max-width:
        820px;

    margin:
        0 auto;

    padding:
        35px
        35px
        31px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .95
        );

    border-radius:
        18px;

    background:
        rgba(
            255,
            255,
            255,
            .98
        );

    box-shadow:
        0 22px 55px
        rgba(
            9,
            37,
            22,
            .075
        );

}


.gh-checkout-card::before {

    content:
        "";

    position:
        absolute;

    top:
        0;

    left:
        0;

    right:
        0;

    height:
        4px;

    background:
        linear-gradient(
            90deg,
            #166534,
            #22c55e,
            #4ade80
        );

}


/* =========================================================
   FORM HEADER
========================================================= */

.gh-checkout-form-header {

    margin-bottom:
        27px;

    text-align:
        center;

}


.gh-checkout-form-icon {

    width:
        53px;

    height:
        53px;

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
        var(--checkout-soft);

    color:
        var(--checkout-green);

    font-size:
        1.15rem;

}


.gh-checkout-form-badge {

    width:
        fit-content;

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

    margin:
        0 auto
        9px;

    padding:
        5px
        9px;

    border-radius:
        999px;

    background:
        var(--checkout-soft);

    color:
        var(--checkout-dark-2);

    font-size:
        .56rem;

    font-weight:
        800;

    letter-spacing:
        .08em;

    text-transform:
        uppercase;

}


.gh-checkout-form-badge-dot {

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        var(--checkout-green-light);

}


.gh-checkout-form-header h2 {

    margin:
        0
        0
        6px;

    color:
        var(--checkout-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.6rem;

    font-weight:
        800;

    letter-spacing:
        -.035em;

}


.gh-checkout-form-header p {

    max-width:
        470px;

    margin:
        0 auto;

    color:
        var(--checkout-muted);

    font-size:
        .71rem;

    line-height:
        1.55;

}


/* =========================================================
   FORM GRID
========================================================= */

.gh-checkout-form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px;

}


.gh-checkout-field-full {

    grid-column:
        1 / -1;

}


/* =========================================================
   LABELS
========================================================= */

.gh-checkout-label {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #263a2d;

    font-size:
        .67rem;

    font-weight:
        700;

}


.gh-checkout-optional {

    color:
        #909b94;

    font-size:
        .58rem;

    font-weight:
        500;

}


/* =========================================================
   INPUTS
========================================================= */

.gh-checkout-input,
.gh-checkout-select,
.gh-checkout-textarea {

    width:
        100%;

    border:
        1.5px solid
        transparent;

    outline:
        none;

    background:
        #f4f6f4;

    color:
        #17271d;

    font-family:
        'Inter',
        sans-serif;

    font-size:
        .77rem;

    font-weight:
        500;

    transition:
        border-color .2s ease,
        background-color .2s ease,
        box-shadow .2s ease;

}


.gh-checkout-input,
.gh-checkout-select {

    height:
        45px;

    padding:
        0
        13px;

    border-radius:
        9px;

}


.gh-checkout-select {

    cursor:
        pointer;

}


.gh-checkout-textarea {

    min-height:
        100px;

    padding:
        13px;

    border-radius:
        10px;

    resize:
        vertical;

    line-height:
        1.6;

}


.gh-checkout-input::placeholder,
.gh-checkout-textarea::placeholder {

    color:
        #a1aaa4;

}


.gh-checkout-input:hover,
.gh-checkout-select:hover,
.gh-checkout-textarea:hover {

    background:
        #f0f4f1;

}


.gh-checkout-input:focus,
.gh-checkout-select:focus,
.gh-checkout-textarea:focus {

    border-color:
        #22a550;

    background:
        #ffffff;

    box-shadow:
        0 0 0 3px
        rgba(
            34,
            165,
            80,
            .10
        );

}


/* =========================================================
   PAYMENT SECTION
========================================================= */

.gh-checkout-payment {

    margin-top:
        27px;

    padding-top:
        24px;

    border-top:
        1px solid
        var(--checkout-border);

}


.gh-checkout-payment-heading {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        17px;

}


.gh-checkout-payment-icon {

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

    border-radius:
        11px;

    background:
        var(--checkout-soft);

    color:
        var(--checkout-green);

}


.gh-checkout-payment-heading h3 {

    margin:
        0;

    color:
        var(--checkout-ink);

    font-size:
        .91rem;

    font-weight:
        800;

}


.gh-checkout-note {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        7px;

    margin:
        10px
        0
        0;

    padding:
        9px
        10px;

    border-radius:
        9px;

    background:
        #f7faf7;

    color:
        var(--checkout-muted);

    font-size:
        .6rem;

    line-height:
        1.5;

}


.gh-checkout-note i {

    flex-shrink:
        0;

    margin-top:
        1px;

    color:
        var(--checkout-green);

}


/* =========================================================
   PLACE ORDER
========================================================= */

.gh-checkout-submit {

    width:
        100%;

    min-height:
        47px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    margin-top:
        21px;

    padding:
        10px
        17px;

    border:
        0;

    border-radius:
        999px;

    background:
        linear-gradient(
            135deg,
            #15803d,
            #14532d
        );

    color:
        #ffffff;

    font-family:
        inherit;

    font-size:
        .76rem;

    font-weight:
        800;

    cursor:
        pointer;

    box-shadow:
        0 11px 24px
        rgba(
            20,
            83,
            45,
            .18
        );

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.gh-checkout-submit:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 15px 29px
        rgba(
            20,
            83,
            45,
            .23
        );

}


.gh-checkout-submit-total {

    padding-left:
        7px;

    border-left:
        1px solid
        rgba(
            255,
            255,
            255,
            .28
        );

}


/* =========================================================
   RETURN BUTTON
========================================================= */

.gh-checkout-return {

    width:
        100%;

    min-height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    margin-top:
        9px;

    padding:
        9px
        15px;

    border:
        1px solid
        var(--checkout-border);

    border-radius:
        999px;

    background:
        #ffffff;

    color:
        var(--checkout-dark-2);

    font-size:
        .68rem;

    font-weight:
        800;

    text-decoration:
        none;

    transition:
        border-color .2s ease,
        background-color .2s ease,
        color .2s ease;

}


.gh-checkout-return:hover {

    border-color:
        rgba(
            21,
            128,
            61,
            .28
        );

    background:
        var(--checkout-soft);

    color:
        var(--checkout-green);

}


/* =========================================================
   SUMMARY
========================================================= */

.gh-checkout-summary {

    position:
        sticky;

    top:
        100px;

    overflow:
        hidden;

    border:
        1px solid
        var(--checkout-border);

    border-radius:
        20px;

    background:
        #ffffff;

    box-shadow:
        0 18px 45px
        rgba(
            9,
            37,
            22,
            .06
        );

}


.gh-checkout-summary::before {

    content:
        "";

    display:
        block;

    height:
        4px;

    background:
        linear-gradient(
            90deg,
            #166534,
            #22c55e,
            #4ade80
        );

}


/* =========================================================
   SUMMARY HEADER
========================================================= */

.gh-checkout-summary-header {

    padding:
        21px
        21px
        17px;

    border-bottom:
        1px solid
        var(--checkout-border);

}


.gh-checkout-summary-label {

    display:
        block;

    margin-bottom:
        4px;

    color:
        var(--checkout-green);

    font-size:
        .56rem;

    font-weight:
        800;

    letter-spacing:
        .1em;

    text-transform:
        uppercase;

}


.gh-checkout-summary h2 {

    margin:
        0;

    color:
        var(--checkout-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.27rem;

    font-weight:
        800;

    letter-spacing:
        -.03em;

}


/* =========================================================
   SUMMARY ITEMS
========================================================= */

.gh-checkout-summary-items {

    max-height:
        320px;

    overflow-y:
        auto;

    padding:
        4px
        21px;

    scrollbar-width:
        thin;

    scrollbar-color:
        rgba(
            21,
            128,
            61,
            .20
        )
        transparent;

}


.gh-checkout-summary-item {

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        13px;

    padding:
        13px
        0;

    border-bottom:
        1px solid
        rgba(
            20,
            83,
            45,
            .07
        );

}


.gh-checkout-summary-item:last-child {

    border-bottom:
        0;

}


.gh-checkout-item-name {

    display:
        block;

    color:
        var(--checkout-text);

    font-size:
        .7rem;

    font-weight:
        750;

    line-height:
        1.45;

}


.gh-checkout-item-quantity {

    display:
        block;

    margin-top:
        3px;

    color:
        var(--checkout-muted);

    font-size:
        .58rem;

}


.gh-checkout-price {

    flex-shrink:
        0;

    color:
        var(--checkout-dark-2);

    font-size:
        .68rem;

    font-weight:
        800;

    white-space:
        nowrap;

}


/* =========================================================
   SUMMARY TOTALS
========================================================= */

.gh-checkout-totals {

    padding:
        18px
        21px;

    border-top:
        1px solid
        var(--checkout-border);

    background:
        #fbfdfb;

}


.gh-checkout-total-row {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    margin-bottom:
        9px;

    color:
        var(--checkout-muted);

    font-size:
        .67rem;

}


.gh-checkout-total-row strong {

    color:
        var(--checkout-text);

    font-size:
        .69rem;

    font-weight:
        800;

}


.gh-checkout-grand-total {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    margin-top:
        14px;

    padding-top:
        14px;

    border-top:
        1px solid
        var(--checkout-border);

}


.gh-checkout-grand-total span {

    color:
        var(--checkout-ink);

    font-size:
        .72rem;

    font-weight:
        800;

}


.gh-checkout-grand-total strong {

    color:
        var(--checkout-green);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.28rem;

    font-weight:
        800;

}


/* =========================================================
   SECURITY
========================================================= */

.gh-checkout-security {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        8px;

    margin:
        0
        21px
        21px;

    padding:
        11px
        12px;

    border-radius:
        10px;

    background:
        var(--checkout-soft);

    color:
        #4d6855;

    font-size:
        .59rem;

    line-height:
        1.5;

}


.gh-checkout-security i {

    flex-shrink:
        0;

    margin-top:
        1px;

    color:
        var(--checkout-green);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 991.98px
) {

    .gh-checkout-intro-inner {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap:
            18px;

    }


    .gh-checkout-layout {

        grid-template-columns:
            1fr;

    }


    .gh-checkout-summary {

        position:
            static;

    }

}


@media (
    max-width: 767.98px
) {

    .gh-checkout-intro {

        padding:
            50px
            18px
            35px;

    }


    .gh-checkout-section {

        padding:
            18px
            18px
            65px;

    }


    .gh-checkout-form-panel {

        padding:
            25px;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-checkout-intro h1 {

        font-size:
            3rem;

    }


    .gh-checkout-progress {

        width:
            100%;

        justify-content:
            center;

    }


    .gh-checkout-form-panel {

        padding:
            17px;

        border-radius:
            19px;

    }


    .gh-checkout-card {

        padding:
            30px
            21px
            25px;

        border-radius:
            15px;

    }


    .gh-checkout-form-grid {

        grid-template-columns:
            1fr;

    }


    .gh-checkout-field-full {

        grid-column:
            auto;

    }


    .gh-checkout-input,
    .gh-checkout-select {

        height:
            44px;

    }


    .gh-checkout-summary {

        border-radius:
            16px;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-checkout-submit,
    .gh-checkout-return {

        transition:
            none;

    }

}

</style>



<div class="gh-checkout-page">


<!-- =========================================================
     CHECKOUT INTRO
========================================================= -->

<section class="gh-checkout-intro">


    <div class="container">


        <div class="gh-checkout-intro-inner">


            <div>


                <p class="gh-checkout-eyebrow">

                    Secure Checkout

                </p>


                <h1>

                    Complete your order.

                </h1>


                <p class="gh-checkout-intro-description">

                    Confirm your delivery information,
                    choose your preferred payment method
                    and review your Green Harvest order.

                </p>


            </div>



            <!-- =============================================
                 CHECKOUT PROGRESS
            ============================================== -->

            <div class="gh-checkout-progress">


                <span class="gh-checkout-progress-item">

                    <span class="gh-checkout-progress-dot"></span>

                    Basket

                </span>


                <span class="gh-checkout-progress-line"></span>


                <span
                    class="
                        gh-checkout-progress-item
                        active
                    "
                >

                    <span class="gh-checkout-progress-dot"></span>

                    Checkout

                </span>


                <span class="gh-checkout-progress-line"></span>


                <span class="gh-checkout-progress-item">

                    <span class="gh-checkout-progress-dot"></span>

                    Complete

                </span>


            </div>


        </div>


    </div>


</section>



<!-- =========================================================
     CHECKOUT CONTENT
========================================================= -->

<section class="gh-checkout-section">


    <div class="container">


        <?php displayFlash(); ?>



        <div class="gh-checkout-layout">


            <!-- =================================================
                 CHECKOUT FORM
            ================================================== -->

            <div class="gh-checkout-form-panel">


                <div class="gh-checkout-card">


                    <!-- =========================================
                         FORM HEADER
                    ========================================== -->

                    <div class="gh-checkout-form-header">


                        <span class="gh-checkout-form-icon">

                            <i class="bi bi-truck"></i>

                        </span>


                        <div class="gh-checkout-form-badge">

                            <span
                                class="gh-checkout-form-badge-dot"
                            ></span>

                            Delivery Details

                        </div>


                        <h2>

                            Where should we deliver?

                        </h2>


                        <p>

                            Confirm your contact details
                            and tell us where your Green
                            Harvest order should be delivered.

                        </p>


                    </div>



                    <form
                        method="post"
                        action="<?= e(
                            url(
                                'checkout.php'
                            )
                        ) ?>"
                        autocomplete="on"
                    >


                        <?= csrfField() ?>



                        <!-- =====================================
                             CUSTOMER DETAILS
                        ====================================== -->

                        <div class="gh-checkout-form-grid">


                            <!-- FULL NAME -->

                            <div>


                                <label
                                    for="customer_name"
                                    class="gh-checkout-label"
                                >

                                    Full Name

                                </label>


                                <input
                                    id="customer_name"
                                    type="text"
                                    name="customer_name"
                                    value="<?= e(
                                        $customerName
                                    ) ?>"
                                    class="gh-checkout-input"
                                    maxlength="120"
                                    autocomplete="name"
                                    placeholder="Your full name"
                                    required
                                >


                            </div>



                            <!-- PHONE -->

                            <div>


                                <label
                                    for="phone"
                                    class="gh-checkout-label"
                                >

                                    Phone Number

                                </label>


                                <input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value="<?= e(
                                        $phone
                                    ) ?>"
                                    class="gh-checkout-input"
                                    maxlength="30"
                                    autocomplete="tel"
                                    placeholder="+233..."
                                    required
                                >


                            </div>



                            <!-- EMAIL -->

                            <div class="gh-checkout-field-full">


                                <label
                                    for="email"
                                    class="gh-checkout-label"
                                >

                                    Email Address

                                </label>


                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="<?= e(
                                        $email
                                    ) ?>"
                                    class="gh-checkout-input"
                                    maxlength="150"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    required
                                >


                            </div>



                            <!-- DELIVERY ADDRESS -->

                            <div class="gh-checkout-field-full">


                                <label
                                    for="delivery_address"
                                    class="gh-checkout-label"
                                >

                                    Delivery Address

                                </label>


                                <textarea
                                    id="delivery_address"
                                    name="delivery_address"
                                    class="gh-checkout-textarea"
                                    autocomplete="street-address"
                                    placeholder="House number, street, area or landmark"
                                    required
                                ><?= e(
                                    $deliveryAddress
                                ) ?></textarea>


                            </div>



                            <!-- CITY -->

                            <div>


                                <label
                                    for="city"
                                    class="gh-checkout-label"
                                >

                                    City

                                </label>


                                <input
                                    id="city"
                                    type="text"
                                    name="city"
                                    value="<?= e(
                                        $city
                                    ) ?>"
                                    class="gh-checkout-input"
                                    maxlength="100"
                                    placeholder="e.g. Accra"
                                    required
                                >


                            </div>



                            <!-- REGION -->

                            <div>


                                <label
                                    for="region"
                                    class="gh-checkout-label"
                                >

                                    Region

                                </label>


                                <input
                                    id="region"
                                    type="text"
                                    name="region"
                                    value="<?= e(
                                        $region
                                    ) ?>"
                                    class="gh-checkout-input"
                                    maxlength="100"
                                    placeholder="e.g. Greater Accra"
                                    required
                                >


                            </div>



                            <!-- DELIVERY INSTRUCTIONS -->

                            <div class="gh-checkout-field-full">


                                <label
                                    for="delivery_instructions"
                                    class="gh-checkout-label"
                                >

                                    Delivery Instructions

                                    <span class="gh-checkout-optional">

                                        optional

                                    </span>


                                </label>


                                <textarea
                                    id="delivery_instructions"
                                    name="delivery_instructions"
                                    class="gh-checkout-textarea"
                                    maxlength="2000"
                                    placeholder="Landmark, gate instructions, preferred contact..."
                                ><?= e(
                                    $deliveryInstructions
                                ) ?></textarea>


                            </div>


                        </div>



                        <!-- =====================================
                             PAYMENT
                        ====================================== -->

                        <div class="gh-checkout-payment">


                            <div class="gh-checkout-payment-heading">


                                <span class="gh-checkout-payment-icon">

                                    <i class="bi bi-credit-card"></i>

                                </span>


                                <h3>

                                    Payment Method

                                </h3>


                            </div>



                            <label
                                for="payment_method"
                                class="gh-checkout-label"
                            >

                                Payment Preference

                            </label>


                            <select
                                id="payment_method"
                                name="payment_method"
                                class="gh-checkout-select"
                                required
                            >


                                <option value="">

                                    Select payment method

                                </option>



                                <?php foreach (
                                    $paymentMethods as
                                    $method
                                ): ?>


                                    <option
                                        value="<?= e(
                                            $method
                                        ) ?>"
                                        <?= $paymentMethod === $method
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e(
                                            $method
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>



                            <div class="gh-checkout-note">


                                <i class="bi bi-info-circle"></i>


                                <span>

                                    Your selected payment method
                                    is recorded with the order.
                                    Online gateway processing can
                                    be connected separately.

                                </span>


                            </div>


                        </div>



                        <!-- =====================================
                             PLACE ORDER
                        ====================================== -->

                        <button
                            type="submit"
                            class="gh-checkout-submit"
                            data-confirm="Place this Green Harvest order?"
                        >

                            <i class="bi bi-check-circle"></i>

                            Place Order

                            <span class="gh-checkout-submit-total">

                                <?= money(
                                    $totals['total']
                                ) ?>

                            </span>

                        </button>



                        <!-- =====================================
                             RETURN
                        ====================================== -->

                        <a
                            href="<?= e(
                                url(
                                    'cart.php'
                                )
                            ) ?>"
                            class="gh-checkout-return"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Return to Basket

                        </a>


                    </form>


                </div>


            </div>



            <!-- =================================================
                 ORDER SUMMARY
            ================================================== -->

            <aside class="gh-checkout-summary">


                <!-- =============================================
                     HEADER
                ============================================== -->

                <div class="gh-checkout-summary-header">


                    <span class="gh-checkout-summary-label">

                        Your Basket

                    </span>


                    <h2>

                        Order Summary

                    </h2>


                </div>



                <!-- =============================================
                     ITEMS
                ============================================== -->

                <div class="gh-checkout-summary-items">


                    <?php foreach (
                        $cartItems as
                        $item
                    ): ?>


                        <div class="gh-checkout-summary-item">


                            <div>


                                <span class="gh-checkout-item-name">

                                    <?= e(
                                        $item['name']
                                    ) ?>

                                </span>


                                <span class="gh-checkout-item-quantity">

                                    <?= (int)
                                        $item['quantity'] ?>

                                    ×

                                    <?= money(
                                        $item['price']
                                    ) ?>

                                </span>


                            </div>



                            <span class="gh-checkout-price">

                                <?= money(
                                    $item['subtotal']
                                ) ?>

                            </span>


                        </div>


                    <?php endforeach; ?>


                </div>



                <!-- =============================================
                     TOTALS
                ============================================== -->

                <div class="gh-checkout-totals">


                    <div class="gh-checkout-total-row">


                        <span>

                            Subtotal

                        </span>


                        <strong>

                            <?= money(
                                $totals['subtotal']
                            ) ?>

                        </strong>


                    </div>



                    <div class="gh-checkout-total-row">


                        <span>

                            Delivery Fee

                        </span>


                        <strong>

                            <?= money(
                                $totals['delivery']
                            ) ?>

                        </strong>


                    </div>



                    <div class="gh-checkout-grand-total">


                        <span>

                            Total

                        </span>


                        <strong>

                            <?= money(
                                $totals['total']
                            ) ?>

                        </strong>


                    </div>


                </div>



                <!-- =============================================
                     SECURITY
                ============================================== -->

                <div class="gh-checkout-security">


                    <i class="bi bi-shield-check"></i>


                    <span>

                        Stock is checked again when
                        your order is placed to prevent
                        unavailable products from being
                        ordered.

                    </span>


                </div>


            </aside>


        </div>


    </div>


</section>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>