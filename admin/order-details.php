<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN ORDER DETAILS
 * =========================================================
 *
 * Responsibilities:
 * - Protect administrator access
 * - Validate requested order
 * - Display order items
 * - Display customer and delivery information
 * - Display financial summary
 * - Safely update order status
 * - Restore inventory when an order is cancelled
 * - Reserve inventory again if a cancelled order is reactivated
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Valid Order Statuses
|--------------------------------------------------------------------------
*/

$statuses = [
    'Pending',
    'Confirmed',
    'Processing',
    'Out for Delivery',
    'Delivered',
    'Cancelled',
];


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

    redirectTo('admin/orders.php');
}


/*
|--------------------------------------------------------------------------
| Safe Order Status Update
|--------------------------------------------------------------------------
|
| Inventory rules:
|
| Non-cancelled -> Cancelled
|     Restore ordered quantities to inventory.
|
| Cancelled -> Non-cancelled
|     Deduct those quantities again.
|
| Non-cancelled -> Non-cancelled
|     Do not change inventory.
|
| All changes happen inside one transaction.
|
*/

$changeOrderStatus = static function (
    PDO $pdo,
    int $orderId,
    string $newStatus,
    array $validStatuses
): array {

    if (
        !in_array(
            $newStatus,
            $validStatuses,
            true
        )
    ) {

        return [
            'type' => 'error',
            'message' => 'Invalid order status selected.',
        ];
    }


    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Lock Order
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            '
            SELECT
                id,
                order_number,
                order_status

            FROM orders

            WHERE id = ?

            FOR UPDATE
            '
        );


        $stmt->execute([
            $orderId,
        ]);


        $order =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$order) {

            $pdo->rollBack();

            return [
                'type' => 'error',
                'message' => 'The selected order could not be found.',
            ];
        }


        $oldStatus =
            (string) $order['order_status'];


        /*
        |--------------------------------------------------------------------------
        | No Change
        |--------------------------------------------------------------------------
        */

        if ($oldStatus === $newStatus) {

            $pdo->commit();

            return [
                'type' => 'info',
                'message' =>
                    'Order status is already set to ' .
                    $newStatus .
                    '.',
            ];
        }


        $wasCancelled =
            $oldStatus === 'Cancelled';


        $willBeCancelled =
            $newStatus === 'Cancelled';


        /*
        |--------------------------------------------------------------------------
        | Does Inventory Need Adjustment?
        |--------------------------------------------------------------------------
        */

        if (
            $wasCancelled !==
            $willBeCancelled
        ) {

            /*
            |--------------------------------------------------------------------------
            | Load Order Items
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                '
                SELECT
                    product_id,
                    product_name,
                    quantity

                FROM order_items

                WHERE order_id = ?
                  AND product_id IS NOT NULL
                '
            );


            $stmt->execute([
                $orderId,
            ]);


            $orderItems =
                $stmt->fetchAll(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | Aggregate Quantities by Product
            |--------------------------------------------------------------------------
            */

            $productQuantities = [];


            foreach ($orderItems as $item) {

                $productId =
                    (int) $item['product_id'];


                $quantity =
                    max(
                        0,
                        (int) $item['quantity']
                    );


                if (
                    $productId <= 0 ||
                    $quantity <= 0
                ) {

                    continue;
                }


                if (
                    !isset(
                        $productQuantities[
                            $productId
                        ]
                    )
                ) {

                    $productQuantities[
                        $productId
                    ] = [
                        'quantity' => 0,
                        'name' =>
                            (string) $item['product_name'],
                    ];
                }


                $productQuantities[
                    $productId
                ]['quantity'] +=
                    $quantity;
            }


            /*
            |--------------------------------------------------------------------------
            | Lock and Adjust Product Stock
            |--------------------------------------------------------------------------
            */

            foreach (
                $productQuantities as
                $productId =>
                $productData
            ) {

                $requiredQuantity =
                    (int)
                    $productData['quantity'];


                $productName =
                    (string)
                    $productData['name'];


                /*
                 * Lock inventory row.
                 */
                $stmt = $pdo->prepare(
                    '
                    SELECT
                        id,
                        name,
                        stock_quantity

                    FROM products

                    WHERE id = ?

                    FOR UPDATE
                    '
                );


                $stmt->execute([
                    $productId,
                ]);


                $product =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if (!$product) {

                    throw new RuntimeException(
                        'Inventory could not be updated for "' .
                        $productName .
                        '".'
                    );
                }


                $currentStock =
                    max(
                        0,
                        (int)
                        $product['stock_quantity']
                    );


                /*
                |--------------------------------------------------------------------------
                | Cancelling Order
                |--------------------------------------------------------------------------
                |
                | Checkout already deducted stock.
                | Return it to inventory.
                |
                */

                if ($willBeCancelled) {

                    $stmt = $pdo->prepare(
                        '
                        UPDATE products

                        SET stock_quantity =
                            stock_quantity + ?

                        WHERE id = ?
                        '
                    );


                    $stmt->execute([
                        $requiredQuantity,
                        $productId,
                    ]);


                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Reactivating Cancelled Order
                    |--------------------------------------------------------------------------
                    |
                    | Stock was previously restored.
                    | Reserve it again.
                    |
                    */

                    if (
                        $currentStock <
                        $requiredQuantity
                    ) {

                        throw new RuntimeException(
                            'Order cannot be reactivated because "' .
                            $productName .
                            '" requires ' .
                            $requiredQuantity .
                            ' but only ' .
                            $currentStock .
                            ' is currently available.'
                        );
                    }


                    $stmt = $pdo->prepare(
                        '
                        UPDATE products

                        SET stock_quantity =
                            stock_quantity - ?

                        WHERE id = ?
                        '
                    );


                    $stmt->execute([
                        $requiredQuantity,
                        $productId,
                    ]);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update Order Status
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            '
            UPDATE orders

            SET order_status = ?

            WHERE id = ?
            '
        );


        $stmt->execute([
            $newStatus,
            $orderId,
        ]);


        $pdo->commit();


        /*
        |--------------------------------------------------------------------------
        | Success Message
        |--------------------------------------------------------------------------
        */

        if ($willBeCancelled) {

            $message =
                'Order ' .
                $order['order_number'] .
                ' was cancelled and its product quantities were returned to inventory.';

        } elseif ($wasCancelled) {

            $message =
                'Order ' .
                $order['order_number'] .
                ' was reactivated and its product quantities were reserved again.';

        } else {

            $message =
                'Order ' .
                $order['order_number'] .
                ' status updated to ' .
                $newStatus .
                '.';
        }


        return [
            'type' => 'success',
            'message' => $message,
        ];


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }


        if ($e instanceof RuntimeException) {

            return [
                'type' => 'error',
                'message' => $e->getMessage(),
            ];
        }


        error_log(
            'Green Harvest order details status transaction error: ' .
            $e->getMessage()
        );


        return [
            'type' => 'error',
            'message' =>
                'The order status could not be updated. Please try again.',
        ];
    }
};


/*
|--------------------------------------------------------------------------
| Handle Status Update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );


    if ($action === 'update_status') {

        /*
        |--------------------------------------------------------------------------
        | CSRF
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
                'Invalid order request. Please try again.'
            );


            redirectTo(
                'admin/order-details.php?order_id=' .
                $orderId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Requested Status
        |--------------------------------------------------------------------------
        */

        $newStatus = trim(
            (string) (
                $_POST['order_status']
                ?? ''
            )
        );


        if (
            !in_array(
                $newStatus,
                $statuses,
                true
            )
        ) {

            setFlash(
                'error',
                'Invalid order status selected.'
            );


            redirectTo(
                'admin/order-details.php?order_id=' .
                $orderId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Perform Transactional Status Update
        |--------------------------------------------------------------------------
        */

        $result =
            $changeOrderStatus(
                $pdo,
                $orderId,
                $newStatus,
                $statuses
            );


        setFlash(
            $result['type'],
            $result['message']
        );


        redirectTo(
            'admin/order-details.php?order_id=' .
            $orderId
        );
    }
}


/*
|--------------------------------------------------------------------------
| Load Order
|--------------------------------------------------------------------------
*/

$order = null;

$items = [];

$itemSubtotal = 0.00;

$totalQuantity = 0;


try {

    $stmt = $pdo->prepare(
        '
        SELECT
            o.id,
            o.user_id,
            o.order_number,
            o.customer_name,
            o.email,
            o.phone,
            o.delivery_address,
            o.city,
            o.region,
            o.delivery_instructions,
            o.payment_method,
            o.total_amount,
            o.order_status,
            o.created_at,
            o.updated_at

        FROM orders o

        WHERE o.id = ?

        LIMIT 1
        '
    );


    $stmt->execute([
        $orderId,
    ]);


    $order =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        setFlash(
            'error',
            'The requested order could not be found.'
        );


        redirectTo(
            'admin/orders.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Load Ordered Products
    |--------------------------------------------------------------------------
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

            p.image,
            p.unit

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


    $items =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($items as $item) {

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
        'Green Harvest admin order details loading error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'The order details could not be loaded.'
    );


    redirectTo(
        'admin/orders.php'
    );
}


/*
|--------------------------------------------------------------------------
| Financial Summary
|--------------------------------------------------------------------------
*/

$orderTotal =
    round(
        (float) $order['total_amount'],
        2
    );


/*
 * Delivery is not stored separately in the current schema.
 * Derive it from the difference between total_amount
 * and the order-item snapshot subtotal.
 */

$deliveryAmount =
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
| Current Status
|--------------------------------------------------------------------------
*/

$currentStatus =
    trim(
        (string) (
            $order['order_status']
            ?? 'Pending'
        )
    );


/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$orderStatusClass =
    static function (
        string $status
    ): string {

        return match (
            strtolower(
                trim($status)
            )
        ) {

            'confirmed' =>
                'bg-blue-50 text-blue-700 border-blue-200',

            'processing' =>
                'bg-purple-50 text-purple-700 border-purple-200',

            'out for delivery' =>
                'bg-cyan-50 text-cyan-700 border-cyan-200',

            'delivered' =>
                'bg-emerald-50 text-emerald-700 border-emerald-200',

            'cancelled' =>
                'bg-rose-50 text-rose-700 border-rose-200',

            default =>
                'bg-amber-50 text-amber-700 border-amber-200',
        };
    };


$orderStatusIcon =
    static function (
        string $status
    ): string {

        return match (
            strtolower(
                trim($status)
            )
        ) {

            'confirmed' =>
                'circle-check',

            'processing' =>
                'settings',

            'out for delivery' =>
                'truck',

            'delivered' =>
                'badge-check',

            'cancelled' =>
                'circle-x',

            default =>
                'clock-3',
        };
    };


/*
|--------------------------------------------------------------------------
| Delivery Location
|--------------------------------------------------------------------------
*/

$deliveryParts = array_filter(
    [

        trim(
            (string)
            $order['delivery_address']
        ),

        trim(
            (string)
            $order['city']
        ),

        trim(
            (string)
            $order['region']
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
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Order ' .
    $order['order_number'];


require_once __DIR__ .
    '/includes/admin-header.php';

?>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div
    class="
        mb-6
        flex
        flex-col
        lg:flex-row
        lg:items-center
        lg:justify-between
        gap-5
    "
>

    <div>

        <a
            href="<?= url(
                'admin/orders.php'
            ) ?>"
            class="
                inline-flex
                items-center
                gap-1
                mb-3
                text-xs
                font-bold
                text-brand-600
                hover:text-brand-700
            "
        >

            <i
                data-lucide="arrow-left"
                class="w-4 h-4"
            ></i>

            Back to Orders

        </a>


        <h2
            class="
                text-xl
                font-bold
                text-slate-900
            "
        >

            <?= e(
                $order['order_number']
            ) ?>

        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >

            Placed on

            <?= date(
                'd M Y \a\t h:i A',
                strtotime(
                    $order['created_at']
                )
            ) ?>

        </p>

    </div>


    <span
        class="
            inline-flex
            items-center
            gap-1.5
            px-3
            py-1.5
            rounded-full
            border
            text-xs
            font-bold
            <?= e(
                $orderStatusClass(
                    $currentStatus
                )
            ) ?>
        "
    >

        <i
            data-lucide="<?= e(
                $orderStatusIcon(
                    $currentStatus
                )
            ) ?>"
            class="w-3.5 h-3.5"
        ></i>

        <?= e($currentStatus) ?>

    </span>

</div>


<!-- =========================================================
     MAIN GRID
========================================================= -->

<div
    class="
        grid
        grid-cols-1
        xl:grid-cols-12
        gap-7
        items-start
    "
>


    <!-- =====================================================
         LEFT COLUMN
    ====================================================== -->

    <div
        class="
            xl:col-span-8
            space-y-6
        "
    >


        <!-- =================================================
             ORDERED PRODUCTS
        ================================================== -->

        <section
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                shadow-sm
                overflow-hidden
            "
        >

            <div
                class="
                    px-6
                    py-5
                    border-b
                    border-slate-100
                    flex
                    items-center
                    justify-between
                    gap-3
                "
            >

                <div>

                    <h3
                        class="
                            font-bold
                            text-slate-900
                        "
                    >
                        Ordered Products
                    </h3>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-400
                        "
                    >

                        <?= $totalQuantity ?>

                        item<?= $totalQuantity === 1
                            ? ''
                            : 's'
                        ?>

                        in this order

                    </p>

                </div>


                <span
                    class="
                        inline-flex
                        items-center
                        px-3
                        py-1
                        rounded-full
                        bg-slate-100
                        text-slate-600
                        text-xs
                        font-bold
                    "
                >

                    <?= count($items) ?>

                    product<?= count($items) === 1
                        ? ''
                        : 's'
                    ?>

                </span>

            </div>


            <div class="overflow-x-auto">

                <table
                    class="
                        w-full
                        text-left
                        text-sm
                        border-collapse
                    "
                >

                    <thead>

                        <tr
                            class="
                                bg-slate-50/70
                                border-b
                                border-slate-100
                                text-slate-500
                                text-xs
                                font-bold
                                uppercase
                                tracking-wider
                            "
                        >

                            <th class="py-3.5 px-6">
                                Product
                            </th>

                            <th class="py-3.5 px-6">
                                Price
                            </th>

                            <th class="py-3.5 px-6 text-center">
                                Qty
                            </th>

                            <th class="py-3.5 px-6 text-right">
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">


                        <?php if (!$items): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="
                                        py-14
                                        px-6
                                        text-center
                                        text-slate-400
                                    "
                                >

                                    <i
                                        data-lucide="package-open"
                                        class="
                                            w-8
                                            h-8
                                            mx-auto
                                            mb-3
                                        "
                                    ></i>

                                    No line items were found
                                    for this order.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $items as
                                $item
                            ): ?>

                                <?php

                                $productId =
                                    (int) (
                                        $item['product_id']
                                        ?? 0
                                    );


                                $quantity =
                                    max(
                                        0,
                                        (int)
                                        $item['quantity']
                                    );

                                ?>


                                <tr
                                    class="
                                        hover:bg-slate-50/60
                                        transition-colors
                                    "
                                >


                                    <!-- Product -->

                                    <td
                                        class="
                                            py-4
                                            px-6
                                        "
                                    >

                                        <div
                                            class="
                                                flex
                                                items-center
                                                gap-3
                                                min-w-[220px]
                                            "
                                        >

                                            <img
                                                src="<?= e(
                                                    productImageUrl(
                                                        $item['image']
                                                        ?? null
                                                    )
                                                ) ?>"
                                                alt="<?= e(
                                                    $item['product_name']
                                                ) ?>"
                                                class="
                                                    w-12
                                                    h-12
                                                    rounded-xl
                                                    object-cover
                                                    border
                                                    border-slate-100
                                                    bg-slate-50
                                                    flex-shrink-0
                                                "
                                            >


                                            <div class="min-w-0">


                                                <?php if (
                                                    $productId > 0
                                                ): ?>

                                                    <a
                                                        href="<?= url(
                                                            'admin/edit-product.php?id=' .
                                                            $productId
                                                        ) ?>"
                                                        class="
                                                            block
                                                            font-bold
                                                            text-slate-900
                                                            hover:text-brand-700
                                                        "
                                                    >

                                                        <?= e(
                                                            $item['product_name']
                                                        ) ?>

                                                    </a>

                                                <?php else: ?>

                                                    <strong
                                                        class="
                                                            block
                                                            text-slate-900
                                                        "
                                                    >

                                                        <?= e(
                                                            $item['product_name']
                                                        ) ?>

                                                    </strong>

                                                <?php endif; ?>


                                                <?php if (
                                                    trim(
                                                        (string) (
                                                            $item['unit']
                                                            ?? ''
                                                        )
                                                    ) !== ''
                                                ): ?>

                                                    <span
                                                        class="
                                                            block
                                                            mt-1
                                                            text-[11px]
                                                            text-slate-400
                                                        "
                                                    >

                                                        Unit:
                                                        <?= e(
                                                            $item['unit']
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>


                                            </div>

                                        </div>

                                    </td>


                                    <!-- Price -->

                                    <td
                                        class="
                                            py-4
                                            px-6
                                            text-slate-600
                                            whitespace-nowrap
                                        "
                                    >

                                        <?= money(
                                            $item['price']
                                        ) ?>

                                    </td>


                                    <!-- Quantity -->

                                    <td
                                        class="
                                            py-4
                                            px-6
                                            text-center
                                        "
                                    >

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                min-w-8
                                                px-2
                                                py-1
                                                rounded-full
                                                bg-slate-100
                                                text-slate-700
                                                text-xs
                                                font-bold
                                            "
                                        >
                                            <?= $quantity ?>
                                        </span>

                                    </td>


                                    <!-- Subtotal -->

                                    <td
                                        class="
                                            py-4
                                            px-6
                                            text-right
                                            font-bold
                                            text-slate-900
                                            whitespace-nowrap
                                        "
                                    >

                                        <?= money(
                                            $item['subtotal']
                                        ) ?>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 FINANCIAL SUMMARY
            ================================================== -->

            <div
                class="
                    px-6
                    py-5
                    bg-slate-50/70
                    border-t
                    border-slate-100
                "
            >

                <div
                    class="
                        max-w-sm
                        ml-auto
                        space-y-3
                    "
                >

                    <div
                        class="
                            flex
                            justify-between
                            gap-4
                            text-sm
                        "
                    >

                        <span class="text-slate-500">
                            Product Subtotal
                        </span>

                        <strong class="text-slate-800">

                            <?= money(
                                $itemSubtotal
                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="
                            flex
                            justify-between
                            gap-4
                            text-sm
                        "
                    >

                        <span class="text-slate-500">
                            Delivery
                        </span>

                        <strong class="text-slate-800">

                            <?= money(
                                $deliveryAmount
                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="
                            flex
                            justify-between
                            gap-4
                            pt-3
                            border-t
                            border-slate-200
                        "
                    >

                        <span
                            class="
                                text-base
                                font-bold
                                text-slate-900
                            "
                        >
                            Order Total
                        </span>

                        <strong
                            class="
                                text-xl
                                font-bold
                                text-brand-700
                            "
                        >

                            <?= money(
                                $orderTotal
                            ) ?>

                        </strong>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             DELIVERY
        ================================================== -->

        <section
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                shadow-sm
                p-6
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-2
                    mb-5
                "
            >

                <i
                    data-lucide="truck"
                    class="
                        w-5
                        h-5
                        text-brand-600
                    "
                ></i>

                <h3
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Delivery Information
                </h3>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-4
                "
            >


                <div
                    class="
                        p-4
                        rounded-xl
                        bg-slate-50
                        border
                        border-slate-100
                    "
                >

                    <span
                        class="
                            block
                            mb-1
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                        "
                    >
                        Delivery Address
                    </span>

                    <span
                        class="
                            text-sm
                            font-semibold
                            text-slate-700
                            leading-relaxed
                        "
                    >

                        <?= e(
                            $deliveryLocation !== ''
                                ? $deliveryLocation
                                : 'Not provided'
                        ) ?>

                    </span>

                </div>


                <div
                    class="
                        p-4
                        rounded-xl
                        bg-slate-50
                        border
                        border-slate-100
                    "
                >

                    <span
                        class="
                            block
                            mb-1
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                        "
                    >
                        Payment Method
                    </span>

                    <span
                        class="
                            text-sm
                            font-semibold
                            text-slate-700
                        "
                    >

                        <?= e(
                            $order['payment_method']
                            ?: 'Not specified'
                        ) ?>

                    </span>

                </div>


                <?php if (
                    trim(
                        (string) (
                            $order['delivery_instructions']
                            ?? ''
                        )
                    ) !== ''
                ): ?>

                    <div
                        class="
                            md:col-span-2
                            p-4
                            rounded-xl
                            bg-amber-50
                            border
                            border-amber-100
                        "
                    >

                        <span
                            class="
                                block
                                mb-1
                                text-[10px]
                                font-bold
                                uppercase
                                tracking-wider
                                text-amber-700
                            "
                        >
                            Delivery Instructions
                        </span>

                        <p
                            class="
                                text-sm
                                text-amber-900
                                leading-relaxed
                                mb-0
                            "
                        >

                            <?= nl2br(
                                e(
                                    $order['delivery_instructions']
                                )
                            ) ?>

                        </p>

                    </div>

                <?php endif; ?>


            </div>

        </section>


    </div>


    <!-- =====================================================
         RIGHT COLUMN
    ====================================================== -->

    <aside
        class="
            xl:col-span-4
            space-y-6
        "
    >


        <!-- Customer -->

        <section
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                shadow-sm
                p-6
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-2
                    pb-4
                    mb-4
                    border-b
                    border-slate-100
                "
            >

                <i
                    data-lucide="user-round"
                    class="
                        w-5
                        h-5
                        text-brand-600
                    "
                ></i>

                <h3
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Customer Details
                </h3>

            </div>


            <div class="space-y-4">


                <div>

                    <span
                        class="
                            block
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                            mb-1
                        "
                    >
                        Customer Name
                    </span>

                    <span
                        class="
                            block
                            text-sm
                            font-semibold
                            text-slate-800
                        "
                    >

                        <?= e(
                            $order['customer_name']
                        ) ?>

                    </span>

                </div>


                <div>

                    <span
                        class="
                            block
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                            mb-1
                        "
                    >
                        Email
                    </span>

                    <a
                        href="mailto:<?= e(
                            $order['email']
                        ) ?>"
                        class="
                            text-sm
                            text-brand-700
                            hover:text-brand-800
                            break-all
                        "
                    >

                        <?= e(
                            $order['email']
                        ) ?>

                    </a>

                </div>


                <div>

                    <span
                        class="
                            block
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                            mb-1
                        "
                    >
                        Phone
                    </span>

                    <a
                        href="tel:<?= e(
                            $order['phone']
                        ) ?>"
                        class="
                            text-sm
                            text-slate-700
                        "
                    >

                        <?= e(
                            $order['phone']
                        ) ?>

                    </a>

                </div>


                <div>

                    <span
                        class="
                            block
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-400
                            mb-1
                        "
                    >
                        Customer Account
                    </span>

                    <span class="text-sm text-slate-700">

                        User #<?= (int)
                            $order['user_id']
                        ?>

                    </span>

                </div>


            </div>

        </section>


        <!-- =================================================
             STATUS MANAGEMENT
        ================================================== -->

        <section
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                shadow-sm
                p-6
            "
        >

            <div
                class="
                    pb-4
                    mb-5
                    border-b
                    border-slate-100
                "
            >

                <h3
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Order Status
                </h3>


                <p
                    class="
                        text-xs
                        text-slate-400
                        mt-1
                    "
                >
                    Update the fulfilment stage
                    for this order.
                </p>

            </div>


            <form
                method="post"
                action="<?= url(
                    'admin/order-details.php?order_id=' .
                    $orderId
                ) ?>"
                class="space-y-4"
            >

                <?= csrfField() ?>


                <input
                    type="hidden"
                    name="action"
                    value="update_status"
                >


                <div>

                    <label
                        for="order-status"
                        class="
                            block
                            text-xs
                            font-bold
                            text-slate-600
                            mb-2
                        "
                    >
                        Status
                    </label>


                    <select
                        id="order-status"
                        name="order_status"
                        class="
                            w-full
                            px-3
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                        <?php foreach (
                            $statuses as
                            $status
                        ): ?>

                            <option
                                value="<?= e(
                                    $status
                                ) ?>"
                                <?= $currentStatus === $status
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= e(
                                    $status
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="
                        w-full
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        px-5
                        py-2.5
                        rounded-xl
                        bg-brand-600
                        hover:bg-brand-700
                        text-white
                        font-semibold
                        text-sm
                    "
                >

                    <i
                        data-lucide="refresh-cw"
                        class="w-4 h-4"
                    ></i>

                    Update Status

                </button>

            </form>


            <?php if (
                $currentStatus ===
                'Cancelled'
            ): ?>

                <div
                    class="
                        mt-4
                        p-3
                        rounded-xl
                        bg-rose-50
                        border
                        border-rose-100
                        text-xs
                        text-rose-700
                        leading-relaxed
                    "
                >

                    <i
                        data-lucide="info"
                        class="
                            inline
                            w-4
                            h-4
                            mr-1
                        "
                    ></i>

                    Product quantities from this order
                    have been returned to inventory.

                </div>

            <?php endif; ?>


        </section>


        <!-- Order Info -->

        <section
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                shadow-sm
                p-6
            "
        >

            <h3
                class="
                    font-bold
                    text-slate-900
                    pb-4
                    mb-4
                    border-b
                    border-slate-100
                "
            >
                Order Information
            </h3>


            <div class="space-y-3">


                <div
                    class="
                        flex
                        justify-between
                        gap-4
                        text-sm
                    "
                >

                    <span class="text-slate-400">
                        Order ID
                    </span>

                    <strong class="text-slate-700">
                        #<?= $orderId ?>
                    </strong>

                </div>


                <div
                    class="
                        flex
                        justify-between
                        gap-4
                        text-sm
                    "
                >

                    <span class="text-slate-400">
                        Order Number
                    </span>

                    <strong
                        class="
                            text-slate-700
                            text-right
                        "
                    >

                        <?= e(
                            $order['order_number']
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        flex
                        justify-between
                        gap-4
                        text-sm
                    "
                >

                    <span class="text-slate-400">
                        Created
                    </span>

                    <strong
                        class="
                            text-slate-700
                            text-right
                        "
                    >

                        <?= date(
                            'd M Y',
                            strtotime(
                                $order['created_at']
                            )
                        ) ?>

                    </strong>

                </div>


                <div
                    class="
                        flex
                        justify-between
                        gap-4
                        text-sm
                    "
                >

                    <span class="text-slate-400">
                        Total Items
                    </span>

                    <strong class="text-slate-700">
                        <?= $totalQuantity ?>
                    </strong>

                </div>


            </div>

        </section>


    </aside>


</div>


<?php

require_once __DIR__ .
    '/includes/admin-footer.php';

?>