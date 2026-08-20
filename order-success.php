<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ORDER SUCCESS
 * =========================================================
 *
 * Responsibilities:
 * - Require customer authentication
 * - Validate order ID
 * - Ensure order belongs to current customer
 * - Display order confirmation
 * - Display order summary
 *
 * No order data is modified on this page.
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

$itemCount = 0;

$lineCount = 0;


try {

    /*
     * Only return the order if it belongs
     * to the currently logged-in customer.
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


    /*
     * Order does not exist or belongs
     * to another account.
     */
    if (!$order) {

        setFlash(
            'error',
            'The requested order could not be found.'
        );

        redirectTo('orders.php');
    }


    /*
     * Count order lines and total purchased quantity.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            COUNT(*) AS line_count,
            COALESCE(
                SUM(quantity),
                0
            ) AS item_count

        FROM order_items

        WHERE order_id = ?
        '
    );


    $stmt->execute([
        $orderId,
    ]);


    $itemSummary =
        $stmt->fetch(PDO::FETCH_ASSOC);


    $lineCount =
        (int) (
            $itemSummary['line_count']
            ?? 0
        );


    $itemCount =
        (int) (
            $itemSummary['item_count']
            ?? 0
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest order-success error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'We could not load your order confirmation.'
    );


    redirectTo('orders.php');
}


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
| Status Styling
|--------------------------------------------------------------------------
*/

$status =
    (string) (
        $order['order_status']
        ?? 'Pending'
    );


$statusClass = match (
    strtolower($status)
) {

    'confirmed' =>
        'status-confirmed',

    'processing' =>
        'status-processing',

    'out for delivery' =>
        'status-delivery',

    'delivered' =>
        'status-delivered',

    'cancelled' =>
        'status-cancelled',

    default =>
        'status-pending',
};


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Order Confirmation';

require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST - ORDER SUCCESS
========================================================= */

.order-success-section {

    padding:
        70px 20px 90px;

}


.order-success-wrapper {

    max-width:
        880px;

    margin:
        0 auto;

}


/*
|--------------------------------------------------------------------------
| Success Card
|--------------------------------------------------------------------------
*/

.order-success-card {

    overflow: hidden;

    background:
        #ffffff;

    border:
        1px solid
        var(--gh-border);

    border-radius:
        24px;

    box-shadow:
        var(--gh-shadow);

}


.order-success-header {

    padding:
        48px 35px 38px;

    text-align:
        center;

    background:
        linear-gradient(
            180deg,
            var(--gh-green-50),
            #ffffff
        );

}


.success-icon {

    width:
        78px;

    height:
        78px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        20px;

    border-radius:
        50%;

    background:
        var(--gh-green-700);

    color:
        #ffffff;

    font-size:
        2rem;

    box-shadow:
        0 12px 25px
        rgba(21, 128, 61, .20);

}


.order-success-header h1 {

    margin-bottom:
        10px;

    font-size:
        clamp(
            2rem,
            5vw,
            2.9rem
        );

}


.order-success-header p {

    max-width:
        610px;

    margin:
        0 auto;

    color:
        var(--gh-muted);

    line-height:
        1.7;

}


/*
|--------------------------------------------------------------------------
| Order Number
|--------------------------------------------------------------------------
*/

.order-number-box {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    margin-top:
        22px;

    padding:
        10px 16px;

    border:
        1px solid
        var(--gh-border);

    border-radius:
        999px;

    background:
        #ffffff;

    color:
        var(--gh-dark);

    font-size:
        .82rem;

    font-weight:
        800;

}


/*
|--------------------------------------------------------------------------
| Details
|--------------------------------------------------------------------------
*/

.order-confirmation-body {

    padding:
        0 35px 38px;

}


.order-details-box {

    overflow:
        hidden;

    border:
        1px solid
        var(--gh-border);

    border-radius:
        17px;

}


.order-detail-row {

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        25px;

    padding:
        15px 18px;

    border-bottom:
        1px solid
        var(--gh-border);

}


.order-detail-row:last-child {

    border-bottom:
        0;

}


.order-detail-label {

    color:
        var(--gh-muted);

    font-size:
        .8rem;

    font-weight:
        700;

}


.order-detail-value {

    max-width:
        60%;

    color:
        var(--gh-dark);

    font-size:
        .85rem;

    font-weight:
        800;

    text-align:
        right;

}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

.order-status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        6px 10px;

    border-radius:
        999px;

    font-size:
        .72rem;

    font-weight:
        800;

}


.status-pending {

    background:
        #fffbeb;

    color:
        #92400e;

}


.status-confirmed {

    background:
        #eff6ff;

    color:
        #1d4ed8;

}


.status-processing {

    background:
        #faf5ff;

    color:
        #7e22ce;

}


.status-delivery {

    background:
        #ecfeff;

    color:
        #0e7490;

}


.status-delivered {

    background:
        #ecfdf3;

    color:
        #166534;

}


.status-cancelled {

    background:
        #fff1f2;

    color:
        #be123c;

}


/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

.order-success-actions {

    display:
        flex;

    flex-wrap:
        wrap;

    justify-content:
        center;

    gap:
        10px;

    margin-top:
        28px;

}


/*
|--------------------------------------------------------------------------
| Next Steps
|--------------------------------------------------------------------------
*/

.order-next-steps {

    margin-top:
        25px;

    padding:
        28px;

}


.order-next-steps h2 {

    margin-bottom:
        18px;

    font-size:
        1.25rem;

}


.next-step {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        13px;

    margin-bottom:
        16px;

}


.next-step:last-child {

    margin-bottom:
        0;

}


.next-step-icon {

    flex-shrink:
        0;

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

    border-radius:
        11px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

}


.next-step strong {

    display:
        block;

    margin-bottom:
        2px;

    color:
        var(--gh-dark);

    font-size:
        .86rem;

}


.next-step p {

    margin:
        0;

    color:
        var(--gh-muted);

    font-size:
        .8rem;

    line-height:
        1.55;

}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 575.98px) {

    .order-success-header {

        padding:
            38px 20px 30px;

    }


    .order-confirmation-body {

        padding:
            0 20px 30px;

    }


    .order-detail-row {

        flex-direction:
            column;

        gap:
            5px;

    }


    .order-detail-value {

        max-width:
            100%;

        text-align:
            left;

    }


    .order-success-actions {

        flex-direction:
            column;

    }


    .order-success-actions .btn {

        width:
            100%;

    }

}

</style>


<!-- =========================================================
     ORDER CONFIRMATION
========================================================= -->

<section class="order-success-section">

    <div class="container">

        <div class="order-success-wrapper">


            <?php displayFlash(); ?>


            <div class="order-success-card">


                <!-- =============================================
                     Success Header
                ============================================== -->

                <div class="order-success-header">


                    <span class="success-icon">

                        <i class="bi bi-check-lg"></i>

                    </span>


                    <p class="section-eyebrow">
                        Order Confirmed
                    </p>


                    <h1>
                        Thank you for your order.
                    </h1>


                    <p>

                        Your Green Harvest order has been
                        received successfully and is now
                        waiting to be processed.

                    </p>


                    <span class="order-number-box">

                        <i class="bi bi-receipt"></i>

                        Order

                        <?= e(
                            $order['order_number']
                        ) ?>

                    </span>


                </div>



                <!-- =============================================
                     Order Summary
                ============================================== -->

                <div class="order-confirmation-body">


                    <div class="order-details-box">


                        <!-- Order Date -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Order Date
                            </span>

                            <span class="order-detail-value">

                                <?= date(
                                    'd F Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </span>

                        </div>


                        <!-- Customer -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Customer
                            </span>

                            <span class="order-detail-value">

                                <?= e(
                                    $order['customer_name']
                                ) ?>

                            </span>

                        </div>


                        <!-- Items -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Items
                            </span>

                            <span class="order-detail-value">

                                <?= $itemCount ?>

                                item<?= $itemCount === 1 ? '' : 's' ?>

                                <?php if ($lineCount > 0): ?>

                                    across

                                    <?= $lineCount ?>

                                    product<?= $lineCount === 1 ? '' : 's' ?>

                                <?php endif; ?>

                            </span>

                        </div>


                        <!-- Delivery Address -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Delivery Address
                            </span>

                            <span class="order-detail-value">

                                <?= e(
                                    $deliveryLocation
                                ) ?>

                            </span>

                        </div>


                        <!-- Payment -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Payment Method
                            </span>

                            <span class="order-detail-value">

                                <?= e(
                                    $order['payment_method']
                                ) ?>

                            </span>

                        </div>


                        <!-- Status -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Order Status
                            </span>

                            <span class="order-detail-value">

                                <span
                                    class="
                                        order-status
                                        <?= e($statusClass) ?>
                                    "
                                >

                                    <i class="bi bi-circle-fill"></i>

                                    <?= e($status) ?>

                                </span>

                            </span>

                        </div>


                        <!-- Total -->

                        <div class="order-detail-row">

                            <span class="order-detail-label">
                                Total Amount
                            </span>

                            <span
                                class="order-detail-value"
                                style="
                                    color:
                                    var(--gh-green-800);
                                    font-size:
                                    1rem;
                                "
                            >

                                <?= money(
                                    $order['total_amount']
                                ) ?>

                            </span>

                        </div>


                    </div>



                    <!-- =========================================
                         Actions
                    ========================================== -->

                    <div class="order-success-actions">


                        <a
                            href="<?= url(
                                'order-details.php?order_id=' .
                                $orderId
                            ) ?>"
                            class="btn btn-green btn-lg"
                        >

                            <i class="bi bi-receipt me-1"></i>

                            View Order Details

                        </a>


                        <a
                            href="<?= url('orders.php') ?>"
                            class="btn btn-outline-green btn-lg"
                        >

                            My Orders

                        </a>


                        <a
                            href="<?= url('shop.php') ?>"
                            class="btn btn-outline-green btn-lg"
                        >

                            Continue Shopping

                        </a>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 NEXT STEPS
            ================================================== -->

            <div class="summary-card order-next-steps">


                <h2>
                    What happens next?
                </h2>


                <div class="next-step">


                    <span class="next-step-icon">

                        <i class="bi bi-check2-circle"></i>

                    </span>


                    <div>

                        <strong>
                            Order review
                        </strong>

                        <p>

                            The Green Harvest team can review
                            and confirm your order from the
                            administration system.

                        </p>

                    </div>


                </div>



                <div class="next-step">


                    <span class="next-step-icon">

                        <i class="bi bi-truck"></i>

                    </span>


                    <div>

                        <strong>
                            Track your status
                        </strong>

                        <p>

                            Visit My Orders to see the latest
                            status of your order as it moves
                            from Pending through delivery.

                        </p>

                    </div>


                </div>



                <div class="next-step">


                    <span class="next-step-icon">

                        <i class="bi bi-headset"></i>

                    </span>


                    <div>

                        <strong>
                            Need assistance?
                        </strong>

                        <p>

                            If you have a question about this
                            order, contact Green Harvest and
                            include your order number
                            <?= e($order['order_number']) ?>.

                        </p>

                    </div>


                </div>


            </div>


        </div>

    </div>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>