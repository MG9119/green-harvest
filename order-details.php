<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CUSTOMER ORDER DETAILS
 * =========================================================
 *
 * Responsibilities:
 * - Require customer authentication
 * - Validate order ID
 * - Ensure order belongs to logged-in customer
 * - Display order information
 * - Display purchased items
 * - Display delivery information
 * - Display payment method and order status
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
    redirectTo('admin/dashboard.php');
}


$userId = getUserId();


if ($userId === null) {

    setFlash(
        'error',
        'Please sign in to view your order.'
    );

    redirectTo('login.php');
}


/*
|--------------------------------------------------------------------------
| Order ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_GET,
    'order_id',
    FILTER_VALIDATE_INT
);


if (
    $orderId === false ||
    $orderId === null ||
    $orderId <= 0
) {

    setFlash(
        'error',
        'Invalid order selected.'
    );

    redirectTo('orders.php');
}


/*
|--------------------------------------------------------------------------
| Load Order
|--------------------------------------------------------------------------
*/

$order = null;

$orderItems = [];

$itemSubtotal = 0.00;

$totalQuantity = 0;


try {

    /*
     * Security:
     *
     * The user_id condition ensures customers
     * can only access their own orders.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            id,
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
            order_status,
            created_at

        FROM orders

        WHERE id = ?
          AND user_id = ?

        LIMIT 1
        '
    );


    $stmt->execute([
        $orderId,
        $userId,
    ]);


    $order =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        setFlash(
            'error',
            'The requested order could not be found.'
        );

        redirectTo('orders.php');
    }


    /*
    |--------------------------------------------------------------------------
    | Load Order Items
    |--------------------------------------------------------------------------
    |
    | product_name and price come from order_items.
    |
    | This is important because they represent the
    | product information at the time the order
    | was placed.
    |
    | We only join products to obtain the current
    | product image.
    |
    */

    $stmt = $pdo->prepare(
        '
        SELECT
            oi.id,
            oi.product_id,
            oi.product_name,
            oi.price,
            oi.quantity,
            oi.subtotal,

            p.image

        FROM order_items oi

        LEFT JOIN products p
            ON p.id = oi.product_id

        WHERE oi.order_id = ?

        ORDER BY oi.id ASC
        '
    );


    $stmt->execute([
        $orderId,
    ]);


    $orderItems =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | Calculate Stored Item Totals
    |--------------------------------------------------------------------------
    */

    foreach ($orderItems as $item) {

        $itemSubtotal +=
            (float) $item['subtotal'];

        $totalQuantity +=
            (int) $item['quantity'];
    }


    $itemSubtotal =
        round(
            $itemSubtotal,
            2
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest order-details error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'We could not load the order details.'
    );


    redirectTo('orders.php');
}


/*
|--------------------------------------------------------------------------
| Order Totals
|--------------------------------------------------------------------------
|
| Current checkout stores:
|
| product subtotal + delivery fee = total_amount
|
| Since delivery is not stored in its own database
| column, we derive the difference here.
|
*/

$orderTotal =
    round(
        (float) $order['total_amount'],
        2
    );


$deliveryFee =
    max(
        0,
        round(
            $orderTotal -
            $itemSubtotal,
            2
        )
    );


/*
|--------------------------------------------------------------------------
| Delivery Location
|--------------------------------------------------------------------------
*/

$deliveryParts = array_filter(
    [
        trim(
            (string) $order['delivery_address']
        ),

        trim(
            (string) $order['city']
        ),

        trim(
            (string) $order['region']
        ),
    ],

    static fn ($value): bool =>
        $value !== ''
);


$deliveryLocation =
    implode(
        ', ',
        $deliveryParts
    );


/*
|--------------------------------------------------------------------------
| Order Status
|--------------------------------------------------------------------------
*/

$status =
    trim(
        (string) (
            $order['order_status']
            ?? 'Pending'
        )
    );


$statusClass = match (
    strtolower($status)
) {

    'confirmed' =>
        'order-status-confirmed',

    'processing' =>
        'order-status-processing',

    'out for delivery',
    'shipped' =>
        'order-status-delivery',

    'delivered' =>
        'order-status-delivered',

    'cancelled',
    'canceled' =>
        'order-status-cancelled',

    default =>
        'order-status-pending',
};


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Order ' .
    $order['order_number'];


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST - ORDER DETAILS
========================================================= */

.order-details-section {

    padding:
        60px 20px 85px;

}


/*
|--------------------------------------------------------------------------
| Breadcrumb
|--------------------------------------------------------------------------
*/

.order-breadcrumb {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 7px;

    margin-bottom: 24px;

    color:
        var(--gh-muted);

    font-size: .82rem;

}


.order-breadcrumb a {

    color:
        var(--gh-green-700);

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

.order-details-header {

    display: flex;

    flex-wrap: wrap;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 28px;

}


.order-details-header h1 {

    margin-bottom: 7px;

}


.order-number-text {

    color:
        var(--gh-muted);

    font-size: .86rem;

}


.order-number-text strong {

    color:
        var(--gh-dark);

}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

.order-status {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        8px 12px;

    border-radius:
        999px;

    font-size: .74rem;

    font-weight: 800;

}


.order-status i {

    font-size: .46rem;

}


.order-status-pending {

    background: #fffbeb;
    color: #92400e;

}


.order-status-confirmed {

    background: #eff6ff;
    color: #1d4ed8;

}


.order-status-processing {

    background: #faf5ff;
    color: #7e22ce;

}


.order-status-delivery {

    background: #ecfeff;
    color: #0e7490;

}


.order-status-delivered {

    background: #ecfdf3;
    color: #166534;

}


.order-status-cancelled {

    background: #fff1f2;
    color: #be123c;

}


/*
|--------------------------------------------------------------------------
| Main Layout
|--------------------------------------------------------------------------
*/

.order-details-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        360px;

    gap: 28px;

    align-items: start;

}


/*
|--------------------------------------------------------------------------
| Section Card
|--------------------------------------------------------------------------
*/

.order-section-card {

    margin-bottom: 22px;

    padding: 27px;

}


.order-section-card h2 {

    margin-bottom: 20px;

    font-size: 1.3rem;

}


/*
|--------------------------------------------------------------------------
| Order Items
|--------------------------------------------------------------------------
*/

.order-item {

    display: grid;

    grid-template-columns:
        82px
        minmax(0, 1fr)
        auto;

    gap: 17px;

    align-items: center;

    padding:
        18px 0;

    border-bottom:
        1px solid
        var(--gh-border);

}


.order-item:first-of-type {

    padding-top: 0;

}


.order-item:last-child {

    padding-bottom: 0;

    border-bottom: 0;

}


.order-item-image {

    width: 82px;

    height: 82px;

    overflow: hidden;

    border-radius: 13px;

    background:
        var(--gh-green-50);

}


.order-item-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.order-item-name {

    margin-bottom: 5px;

    font-size: 1rem;

}


.order-item-meta {

    margin: 0;

    color:
        var(--gh-muted);

    font-size: .79rem;

    line-height: 1.6;

}


.order-item-subtotal {

    color:
        var(--gh-dark);

    font-weight: 800;

    white-space: nowrap;

}


/*
|--------------------------------------------------------------------------
| Delivery Information
|--------------------------------------------------------------------------
*/

.delivery-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 15px;

}


.delivery-info {

    padding: 16px;

    border-radius: 13px;

    background:
        #f8faf8;

}


.delivery-info.full {

    grid-column:
        1 / -1;

}


.delivery-label {

    display: block;

    margin-bottom: 5px;

    color:
        var(--gh-muted);

    font-size: .69rem;

    font-weight: 800;

    letter-spacing: .06em;

    text-transform: uppercase;

}


.delivery-value {

    color:
        var(--gh-dark);

    font-size: .87rem;

    font-weight: 700;

    line-height: 1.6;

}


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

.order-summary {

    position: sticky;

    top: 100px;

    padding: 27px;

}


.order-summary h2 {

    margin-bottom: 20px;

    font-size: 1.3rem;

}


.order-summary-info {

    margin-bottom: 22px;

}


.summary-info-row {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 15px;

    padding:
        10px 0;

    border-bottom:
        1px solid
        var(--gh-border);

    font-size: .81rem;

}


.summary-info-row:last-child {

    border-bottom: 0;

}


.summary-info-label {

    color:
        var(--gh-muted);

}


.summary-info-value {

    color:
        var(--gh-dark);

    font-weight: 700;

    text-align: right;

}


/*
|--------------------------------------------------------------------------
| Financial Totals
|--------------------------------------------------------------------------
*/

.order-price-summary {

    margin-top: 20px;

    padding-top: 18px;

    border-top:
        1px solid
        var(--gh-border);

}


.price-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 12px;

    color:
        var(--gh-muted);

    font-size: .86rem;

}


.price-row strong {

    color:
        var(--gh-dark);

}


.price-total {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-top: 17px;

    padding-top: 17px;

    border-top:
        1px solid
        var(--gh-border);

}


.price-total span {

    color:
        var(--gh-dark);

    font-weight: 800;

}


.price-total strong {

    color:
        var(--gh-green-800);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size: 1.45rem;

}


/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

.order-detail-actions {

    display: grid;

    gap: 8px;

    margin-top: 23px;

}


/*
|--------------------------------------------------------------------------
| Order Note
|--------------------------------------------------------------------------
*/

.order-info-note {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-top: 20px;

    padding: 13px;

    border-radius: 12px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-800);

    font-size: .76rem;

    line-height: 1.55;

}


/*
|--------------------------------------------------------------------------
| Empty Item State
|--------------------------------------------------------------------------
*/

.order-items-empty {

    padding:
        35px 20px;

    text-align: center;

    color:
        var(--gh-muted);

}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 991.98px) {

    .order-details-layout {

        grid-template-columns: 1fr;

    }


    .order-summary {

        position: static;

    }

}


@media (max-width: 575.98px) {

    .order-section-card,
    .order-summary {

        padding: 20px;

    }


    .order-item {

        grid-template-columns:
            65px
            minmax(0, 1fr);

    }


    .order-item-image {

        width: 65px;

        height: 65px;

    }


    .order-item-subtotal {

        grid-column:
            2;

    }


    .delivery-grid {

        grid-template-columns: 1fr;

    }


    .delivery-info.full {

        grid-column: auto;

    }

}

</style>


<!-- =========================================================
     ORDER HERO
========================================================= -->

<section class="page-hero">

    <div class="container">

        <p class="section-eyebrow">
            My Orders
        </p>

        <h1>
            Order Details
        </h1>

    </div>

</section>


<!-- =========================================================
     ORDER DETAILS
========================================================= -->

<section class="order-details-section">

    <div class="container">


        <?php displayFlash(); ?>


        <!-- =================================================
             Breadcrumb
        ================================================== -->

        <div class="order-breadcrumb">

            <a href="<?= url('account.php') ?>">
                My Account
            </a>

            <i class="bi bi-chevron-right"></i>

            <a href="<?= url('orders.php') ?>">
                My Orders
            </a>

            <i class="bi bi-chevron-right"></i>

            <span>
                <?= e($order['order_number']) ?>
            </span>

        </div>


        <!-- =================================================
             Order Header
        ================================================== -->

        <div class="order-details-header">


            <div>

                <p class="section-eyebrow">
                    Order
                </p>


                <h1>
                    <?= e($order['order_number']) ?>
                </h1>


                <p class="order-number-text">

                    Placed on

                    <strong>

                        <?= date(
                            'd F Y, h:i A',
                            strtotime(
                                $order['created_at']
                            )
                        ) ?>

                    </strong>

                </p>

            </div>


            <span
                class="
                    order-status
                    <?= e($statusClass) ?>
                "
            >

                <i class="bi bi-circle-fill"></i>

                <?= e($status) ?>

            </span>


        </div>


        <!-- =================================================
             Main Layout
        ================================================== -->

        <div class="order-details-layout">


            <!-- =================================================
                 LEFT SIDE
            ================================================== -->

            <div>


                <!-- =============================================
                     Order Items
                ============================================== -->

                <div class="summary-card order-section-card">


                    <h2>
                        Order Items
                    </h2>


                    <?php if ($orderItems): ?>


                        <?php foreach ($orderItems as $item): ?>

                            <?php

                            $productId =
                                (int) $item['product_id'];

                            $quantity =
                                (int) $item['quantity'];

                            ?>


                            <div class="order-item">


                                <!-- Image -->

                                <div class="order-item-image">


                                    <?php if (
                                        !empty($item['image'])
                                    ): ?>

                                        <img
                                            src="<?= e(
                                                productImageUrl(
                                                    $item['image']
                                                )
                                            ) ?>"
                                            alt="<?= e(
                                                $item['product_name']
                                            ) ?>"
                                            loading="lazy"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="
                                                w-100
                                                h-100
                                                d-flex
                                                align-items-center
                                                justify-content-center
                                            "
                                            style="
                                                color:
                                                var(--gh-green-700);
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    bi-basket
                                                    fs-4
                                                "
                                            ></i>

                                        </div>

                                    <?php endif; ?>


                                </div>


                                <!-- Product Information -->

                                <div>


                                    <h3 class="order-item-name">

                                        <?php if ($productId > 0): ?>

                                            <a
                                                href="<?= url(
                                                    'product.php?id=' .
                                                    $productId
                                                ) ?>"
                                            >
                                                <?= e(
                                                    $item['product_name']
                                                ) ?>
                                            </a>

                                        <?php else: ?>

                                            <?= e(
                                                $item['product_name']
                                            ) ?>

                                        <?php endif; ?>


                                    </h3>


                                    <p class="order-item-meta">

                                        Quantity:
                                        <?= $quantity ?>

                                        <br>

                                        Unit price:
                                        <?= money(
                                            $item['price']
                                        ) ?>

                                    </p>


                                </div>


                                <!-- Item Subtotal -->

                                <div class="order-item-subtotal">

                                    <?= money(
                                        $item['subtotal']
                                    ) ?>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="order-items-empty">

                            <i
                                class="
                                    bi
                                    bi-bag
                                    fs-2
                                    d-block
                                    mb-2
                                "
                            ></i>

                            No items were found for this order.

                        </div>


                    <?php endif; ?>


                </div>


                <!-- =============================================
                     Delivery Information
                ============================================== -->

                <div class="summary-card order-section-card">


                    <h2>
                        Delivery Information
                    </h2>


                    <div class="delivery-grid">


                        <!-- Customer -->

                        <div class="delivery-info">

                            <span class="delivery-label">
                                Customer
                            </span>

                            <span class="delivery-value">

                                <?= e(
                                    $order['customer_name']
                                ) ?>

                            </span>

                        </div>


                        <!-- Phone -->

                        <div class="delivery-info">

                            <span class="delivery-label">
                                Phone
                            </span>

                            <span class="delivery-value">

                                <?= e(
                                    $order['phone']
                                ) ?>

                            </span>

                        </div>


                        <!-- Email -->

                        <div class="delivery-info full">

                            <span class="delivery-label">
                                Email
                            </span>

                            <span class="delivery-value">

                                <?= e(
                                    $order['email']
                                ) ?>

                            </span>

                        </div>


                        <!-- Address -->

                        <div class="delivery-info full">

                            <span class="delivery-label">
                                Delivery Address
                            </span>

                            <span class="delivery-value">

                                <?= e(
                                    $deliveryLocation
                                ) ?>

                            </span>

                        </div>


                        <!-- Instructions -->

                        <?php if (
                            trim(
                                (string) (
                                    $order['delivery_instructions']
                                    ?? ''
                                )
                            ) !== ''
                        ): ?>


                            <div class="delivery-info full">

                                <span class="delivery-label">
                                    Delivery Instructions
                                </span>

                                <span class="delivery-value">

                                    <?= nl2br(
                                        e(
                                            $order['delivery_instructions']
                                        )
                                    ) ?>

                                </span>

                            </div>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 RIGHT SIDE - SUMMARY
            ================================================== -->

            <aside>


                <div class="summary-card order-summary">


                    <h2>
                        Order Summary
                    </h2>


                    <!-- =========================================
                         Order Information
                    ========================================== -->

                    <div class="order-summary-info">


                        <div class="summary-info-row">

                            <span class="summary-info-label">
                                Order Number
                            </span>

                            <span class="summary-info-value">

                                <?= e(
                                    $order['order_number']
                                ) ?>

                            </span>

                        </div>


                        <div class="summary-info-row">

                            <span class="summary-info-label">
                                Order Date
                            </span>

                            <span class="summary-info-value">

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </span>

                        </div>


                        <div class="summary-info-row">

                            <span class="summary-info-label">
                                Items
                            </span>

                            <span class="summary-info-value">

                                <?= $totalQuantity ?>

                                item<?= $totalQuantity === 1 ? '' : 's' ?>

                            </span>

                        </div>


                        <div class="summary-info-row">

                            <span class="summary-info-label">
                                Payment
                            </span>

                            <span class="summary-info-value">

                                <?= e(
                                    $order['payment_method']
                                    ?: 'Not specified'
                                ) ?>

                            </span>

                        </div>


                        <div class="summary-info-row">

                            <span class="summary-info-label">
                                Status
                            </span>

                            <span class="summary-info-value">

                                <?= e($status) ?>

                            </span>

                        </div>


                    </div>


                    <!-- =========================================
                         Price Summary
                    ========================================== -->

                    <div class="order-price-summary">


                        <div class="price-row">

                            <span>
                                Product Subtotal
                            </span>

                            <strong>

                                <?= money(
                                    $itemSubtotal
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-row">

                            <span>
                                Delivery Fee
                            </span>

                            <strong>

                                <?= money(
                                    $deliveryFee
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-total">

                            <span>
                                Total
                            </span>

                            <strong>

                                <?= money(
                                    $orderTotal
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <!-- =========================================
                         Status Information
                    ========================================== -->

                    <div class="order-info-note">

                        <i class="bi bi-info-circle"></i>

                        <span>

                            Your current order status is

                            <strong>
                                <?= e($status) ?>
                            </strong>.

                            Updates made by Green Harvest will
                            appear on this page.

                        </span>

                    </div>


                    <!-- =========================================
                         Actions
                    ========================================== -->

                    <div class="order-detail-actions">


                        <a
                            href="<?= url('orders.php') ?>"
                            class="btn btn-green"
                        >

                            <i class="bi bi-arrow-left me-1"></i>

                            Back to My Orders

                        </a>


                        <a
                            href="<?= url('shop.php') ?>"
                            class="btn btn-outline-green"
                        >

                            <i class="bi bi-basket me-1"></i>

                            Continue Shopping

                        </a>


                        <a
                            href="<?= url('contact.php') ?>"
                            class="btn btn-outline-green"
                        >

                            <i class="bi bi-headset me-1"></i>

                            Need Help?

                        </a>


                    </div>


                </div>


            </aside>


        </div>


    </div>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>