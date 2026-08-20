<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN ORDER MANAGEMENT
 * =========================================================
 *
 * Responsibilities:
 * - Protect administrator access
 * - List customer orders
 * - Search/filter orders
 * - Paginate order history
 * - Safely update order statuses
 * - Restore stock when an order is cancelled
 * - Re-deduct stock when a cancelled order is reactivated
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
| Safe Order Status Update
|--------------------------------------------------------------------------
|
| Inventory behaviour:
|
| Non-cancelled -> Cancelled
|     Restore ordered quantities.
|
| Cancelled -> Non-cancelled
|     Deduct ordered quantities again.
|
| Non-cancelled -> Non-cancelled
|     Do not modify stock.
|
| All status + stock changes happen in ONE database transaction.
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
        | No Status Change
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
        | Inventory Adjustment Required?
        |--------------------------------------------------------------------------
        |
        | Only crossing the Cancelled boundary changes inventory.
        |
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
             * Aggregate quantities by product ID.
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
            | Lock and Adjust Products
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
                 * Lock product inventory row.
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
                        'Inventory could not be updated for ' .
                        $productName .
                        '.'
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
                | Put it back.
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
                    | Stock was previously restored when cancelled.
                    | We must reserve it again.
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
            'Green Harvest order status transaction error: ' .
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
        | Safe Return Route
        |--------------------------------------------------------------------------
        */

        $returnRoute =
            safeRedirectPath(
                $_POST['redirect']
                ?? null,
                'admin/orders.php'
            );


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
                $returnRoute
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Order ID
        |--------------------------------------------------------------------------
        */

        $orderId = filter_var(
            $_POST['order_id']
            ?? null,
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

            redirectTo(
                $returnRoute
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Requested Status
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
                $returnRoute
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Perform Transactional Update
        |--------------------------------------------------------------------------
        */

        $result =
            $changeOrderStatus(
                $pdo,
                (int) $orderId,
                $newStatus,
                $statuses
            );


        setFlash(
            $result['type'],
            $result['message']
        );


        redirectTo(
            $returnRoute
        );
    }
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim(
    (string) (
        $_GET['search']
        ?? ''
    )
);


if (strlen($search) > 100) {

    $search = substr(
        $search,
        0,
        100
    );
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

$statusFilter = trim(
    (string) (
        $_GET['status']
        ?? ''
    )
);


if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $statuses,
        true
    )
) {

    $statusFilter = '';
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$itemsPerPage = 12;


$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);


if (
    $page === false ||
    $page === null ||
    $page < 1
) {

    $page = 1;
}


/*
|--------------------------------------------------------------------------
| Build Filters
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


if ($search !== '') {

    /*
     * Each occurrence uses its own named placeholder because
     * PDO native prepared statements are enabled.
     */
    $where[] = '
        (
            o.order_number LIKE :search_order
            OR o.customer_name LIKE :search_customer
            OR o.email LIKE :search_email
            OR o.phone LIKE :search_phone
        )
    ';


    $searchTerm =
        '%' . $search . '%';


    $params[':search_order'] =
        $searchTerm;


    $params[':search_customer'] =
        $searchTerm;


    $params[':search_email'] =
        $searchTerm;


    $params[':search_phone'] =
        $searchTerm;
}


if ($statusFilter !== '') {

    $where[] =
        'o.order_status = :status';


    $params[':status'] =
        $statusFilter;
}


$whereSql =
    $where
        ? ' WHERE ' .
          implode(
              ' AND ',
              $where
          )
        : '';


/*
|--------------------------------------------------------------------------
| Load Orders
|--------------------------------------------------------------------------
*/

$orders = [];

$totalOrders = 0;

$totalPages = 1;

$offset = 0;

$ordersLoadError = false;


try {

    /*
    |--------------------------------------------------------------------------
    | Count Matching Orders
    |--------------------------------------------------------------------------
    */

    $countSql = '
        SELECT COUNT(*)

        FROM orders o

        ' .
        $whereSql;


    $stmt =
        $pdo->prepare(
            $countSql
        );


    foreach (
        $params as
        $key => $value
    ) {

        $stmt->bindValue(
            $key,
            (string) $value,
            PDO::PARAM_STR
        );
    }


    $stmt->execute();


    $totalOrders =
        (int)
        $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $totalPages = max(
        1,
        (int) ceil(
            $totalOrders /
            $itemsPerPage
        )
    );


    if ($page > $totalPages) {

        $page =
            $totalPages;
    }


    $offset =
        ($page - 1) *
        $itemsPerPage;


    /*
    |--------------------------------------------------------------------------
    | Fetch Orders
    |--------------------------------------------------------------------------
    */

    $ordersSql = '
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
            o.payment_method,
            o.total_amount,
            o.order_status,
            o.created_at,

            COALESCE(
                (
                    SELECT
                        SUM(oi.quantity)

                    FROM order_items oi

                    WHERE oi.order_id = o.id
                ),
                0
            ) AS item_count

        FROM orders o

        ' .
        $whereSql .

        '
        ORDER BY
            o.created_at DESC,
            o.id DESC

        LIMIT :limit
        OFFSET :offset
        ';


    $stmt =
        $pdo->prepare(
            $ordersSql
        );


    foreach (
        $params as
        $key => $value
    ) {

        $stmt->bindValue(
            $key,
            (string) $value,
            PDO::PARAM_STR
        );
    }


    $stmt->bindValue(
        ':limit',
        $itemsPerPage,
        PDO::PARAM_INT
    );


    $stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );


    $stmt->execute();


    $orders =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest admin orders loading error: ' .
        $e->getMessage()
    );


    $ordersLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Result Range
|--------------------------------------------------------------------------
*/

if ($totalOrders > 0) {

    $showingFrom =
        $offset + 1;


    $showingTo = min(
        $offset +
        $itemsPerPage,
        $totalOrders
    );

} else {

    $showingFrom = 0;

    $showingTo = 0;
}


/*
|--------------------------------------------------------------------------
| Status Styling
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
| Pagination URL
|--------------------------------------------------------------------------
*/

$orderPageUrl =
    static function (
        int $pageNumber
    ) use (
        $search,
        $statusFilter
    ): string {

        $query = [
            'page' => $pageNumber,
        ];


        if ($search !== '') {

            $query['search'] =
                $search;
        }


        if ($statusFilter !== '') {

            $query['status'] =
                $statusFilter;
        }


        return url(
            'admin/orders.php?' .
            http_build_query(
                $query
            )
        );
    };


/*
|--------------------------------------------------------------------------
| Current Return Route
|--------------------------------------------------------------------------
*/

$currentQuery = [];


if ($search !== '') {

    $currentQuery['search'] =
        $search;
}


if ($statusFilter !== '') {

    $currentQuery['status'] =
        $statusFilter;
}


if ($page > 1) {

    $currentQuery['page'] =
        $page;
}


$currentReturnRoute =
    'admin/orders.php';


if ($currentQuery) {

    $currentReturnRoute .=
        '?' .
        http_build_query(
            $currentQuery
        );
}


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Order Management';


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
        gap-4
    "
>

    <div>

        <h2 class="text-xl font-bold text-slate-900">
            Customer Orders
        </h2>

        <p class="text-xs text-slate-500 mt-1">
            Review customer purchases and manage
            fulfilment status.
        </p>

    </div>


    <span
        class="
            inline-flex
            items-center
            gap-2
            px-3
            py-1.5
            rounded-full
            bg-brand-50
            border
            border-brand-100
            text-brand-700
            text-xs
            font-bold
        "
    >

        <i
            data-lucide="shopping-bag"
            class="w-4 h-4"
        ></i>

        <?= number_format($totalOrders) ?>

        order<?= $totalOrders === 1 ? '' : 's' ?>

    </span>

</div>


<!-- =========================================================
     LOAD ERROR
========================================================= -->

<?php if ($ordersLoadError): ?>

    <div
        class="
            mb-6
            rounded-xl
            border
            border-rose-200
            bg-rose-50
            px-4
            py-3
            text-sm
            text-rose-700
        "
    >

        <div class="flex items-center gap-2">

            <i
                data-lucide="triangle-alert"
                class="w-5 h-5"
            ></i>

            The order list could not be loaded.
            Please refresh the page.

        </div>

    </div>

<?php endif; ?>


<!-- =========================================================
     FILTERS
========================================================= -->

<div
    class="
        bg-white
        rounded-2xl
        border
        border-slate-200
        shadow-sm
        p-5
        mb-6
    "
>

    <form
        method="get"
        action="<?= url('admin/orders.php') ?>"
        class="
            grid
            grid-cols-1
            md:grid-cols-12
            gap-4
            items-end
        "
    >

        <!-- Search -->

        <div class="md:col-span-7 lg:col-span-8">

            <label
                for="order-search"
                class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                "
            >
                Search Orders
            </label>


            <div class="relative">

                <i
                    data-lucide="search"
                    class="
                        absolute
                        left-3.5
                        top-1/2
                        -translate-y-1/2
                        w-4
                        h-4
                        text-slate-400
                    "
                ></i>


                <input
                    id="order-search"
                    type="search"
                    name="search"
                    value="<?= e($search) ?>"
                    maxlength="100"
                    placeholder="Order number, customer, email or phone..."
                    class="
                        w-full
                        pl-10
                        pr-4
                        py-2.5
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-sm
                        text-slate-700
                    "
                >

            </div>

        </div>


        <!-- Status -->

        <div class="md:col-span-3 lg:col-span-2">

            <label
                for="order-status-filter"
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
                id="order-status-filter"
                name="status"
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

                <option value="">
                    All Statuses
                </option>


                <?php foreach (
                    $statuses as
                    $filterStatus
                ): ?>

                    <option
                        value="<?= e($filterStatus) ?>"
                        <?= $statusFilter === $filterStatus
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= e($filterStatus) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Actions -->

        <div class="md:col-span-2 flex gap-2">

            <button
                type="submit"
                class="
                    flex-1
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    px-4
                    py-2.5
                    rounded-xl
                    bg-slate-900
                    hover:bg-slate-800
                    text-white
                    text-sm
                    font-semibold
                "
            >

                <i
                    data-lucide="sliders-horizontal"
                    class="w-4 h-4"
                ></i>

                Filter

            </button>


            <?php if (
                $search !== '' ||
                $statusFilter !== ''
            ): ?>

                <a
                    href="<?= url('admin/orders.php') ?>"
                    title="Clear filters"
                    class="
                        w-11
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        hover:text-rose-600
                        hover:bg-rose-50
                        hover:border-rose-200
                    "
                >

                    <i
                        data-lucide="x"
                        class="w-4 h-4"
                    ></i>

                </a>

            <?php endif; ?>

        </div>

    </form>

</div>


<!-- =========================================================
     ORDERS TABLE
========================================================= -->

<div
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
            py-4
            border-b
            border-slate-100
        "
    >

        <h3 class="font-bold text-slate-900">
            All Orders
        </h3>


        <p class="text-xs text-slate-400 mt-1">

            <?php if ($totalOrders > 0): ?>

                Showing
                <?= $showingFrom ?>
                –
                <?= $showingTo ?>
                of
                <?= number_format($totalOrders) ?>

            <?php else: ?>

                No matching orders

            <?php endif; ?>

        </p>

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

                    <th class="py-3.5 px-6">Order</th>
                    <th class="py-3.5 px-6">Customer</th>
                    <th class="py-3.5 px-6">Date</th>
                    <th class="py-3.5 px-6 text-center">Items</th>
                    <th class="py-3.5 px-6">Amount</th>
                    <th class="py-3.5 px-6">Payment</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>

                </tr>

            </thead>


            <tbody class="divide-y divide-slate-100">


                <?php if (!$orders): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="py-14 px-6 text-center"
                        >

                            <div
                                class="
                                    flex
                                    flex-col
                                    items-center
                                "
                            >

                                <span
                                    class="
                                        w-14
                                        h-14
                                        rounded-2xl
                                        bg-slate-100
                                        text-slate-400
                                        flex
                                        items-center
                                        justify-center
                                        mb-3
                                    "
                                >

                                    <i
                                        data-lucide="shopping-bag"
                                        class="w-6 h-6"
                                    ></i>

                                </span>


                                <h4
                                    class="
                                        font-bold
                                        text-slate-700
                                    "
                                >
                                    No orders found
                                </h4>


                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-400
                                    "
                                >

                                    <?php if (
                                        $search !== '' ||
                                        $statusFilter !== ''
                                    ): ?>

                                        Try changing your
                                        search or status filter.

                                    <?php else: ?>

                                        Customer orders will
                                        appear here after checkout.

                                    <?php endif; ?>

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $orders as
                        $order
                    ): ?>

                        <?php

                        $orderId =
                            (int) $order['id'];


                        $currentStatus =
                            (string)
                            $order['order_status'];


                        $itemCount =
                            (int)
                            $order['item_count'];

                        ?>


                        <tr
                            class="
                                hover:bg-slate-50/60
                                transition-colors
                            "
                        >


                            <!-- Order -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    whitespace-nowrap
                                "
                            >

                                <a
                                    href="<?= url(
                                        'admin/order-details.php?order_id=' .
                                        $orderId
                                    ) ?>"
                                    class="
                                        font-bold
                                        text-slate-900
                                        hover:text-brand-700
                                    "
                                >

                                    <?= e(
                                        $order['order_number']
                                    ) ?>

                                </a>


                                <span
                                    class="
                                        block
                                        mt-1
                                        text-[11px]
                                        text-slate-400
                                    "
                                >

                                    ID #<?= $orderId ?>

                                </span>

                            </td>


                            <!-- Customer -->

                            <td class="py-4 px-6">

                                <strong
                                    class="
                                        block
                                        text-sm
                                        text-slate-800
                                    "
                                >

                                    <?= e(
                                        $order['customer_name']
                                    ) ?>

                                </strong>


                                <span
                                    class="
                                        block
                                        mt-1
                                        text-[11px]
                                        text-slate-400
                                    "
                                >

                                    <?= e(
                                        $order['email']
                                    ) ?>

                                </span>

                            </td>


                            <!-- Date -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    text-xs
                                    text-slate-500
                                    whitespace-nowrap
                                "
                            >

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>


                                <span
                                    class="
                                        block
                                        mt-1
                                        text-[11px]
                                        text-slate-400
                                    "
                                >

                                    <?= date(
                                        'h:i A',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- Items -->

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
                                        text-slate-600
                                        text-xs
                                        font-bold
                                    "
                                >

                                    <?= $itemCount ?>

                                </span>

                            </td>


                            <!-- Amount -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    font-bold
                                    text-slate-900
                                    whitespace-nowrap
                                "
                            >

                                <?= money(
                                    $order['total_amount']
                                ) ?>

                            </td>


                            <!-- Payment -->

                            <td class="py-4 px-6">

                                <span
                                    class="
                                        inline-flex
                                        items-center
                                        px-2.5
                                        py-1
                                        rounded-lg
                                        bg-slate-100
                                        text-slate-600
                                        text-[11px]
                                        font-bold
                                        whitespace-nowrap
                                    "
                                >

                                    <?= e(
                                        $order['payment_method']
                                        ?: 'Not specified'
                                    ) ?>

                                </span>

                            </td>


                            <!-- Status -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    whitespace-nowrap
                                "
                            >

                                <span
                                    class="
                                        inline-flex
                                        items-center
                                        gap-1.5
                                        px-2.5
                                        py-1
                                        rounded-full
                                        text-xs
                                        font-bold
                                        border
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

                            </td>


                            <!-- Actions -->

                            <td class="py-4 px-6">

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-end
                                        gap-2
                                        min-w-[235px]
                                    "
                                >

                                    <a
                                        href="<?= url(
                                            'admin/order-details.php?order_id=' .
                                            $orderId
                                        ) ?>"
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            px-3
                                            py-1.5
                                            rounded-lg
                                            bg-slate-100
                                            hover:bg-slate-200
                                            text-slate-700
                                            font-semibold
                                            text-xs
                                        "
                                    >

                                        <i
                                            data-lucide="eye"
                                            class="w-3.5 h-3.5"
                                        ></i>

                                        View Details

                                    </a>


                                    <form
                                        method="post"
                                        action="<?= url(
                                            'admin/orders.php'
                                        ) ?>"
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                        "
                                    >

                                        <?= csrfField() ?>


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update_status"
                                        >


                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= $orderId ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="redirect"
                                            value="<?= e(
                                                $currentReturnRoute
                                            ) ?>"
                                        >


                                        <select
                                            name="order_status"
                                            class="
                                                px-2
                                                py-1.5
                                                rounded-lg
                                                border
                                                border-slate-200
                                                text-xs
                                                bg-white
                                                text-slate-700
                                            "
                                            aria-label="Order status"
                                        >

                                            <?php foreach (
                                                $statuses as
                                                $availableStatus
                                            ): ?>

                                                <option
                                                    value="<?= e(
                                                        $availableStatus
                                                    ) ?>"
                                                    <?= $currentStatus ===
                                                        $availableStatus
                                                            ? 'selected'
                                                            : ''
                                                    ?>
                                                >

                                                    <?= e(
                                                        $availableStatus
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>


                                        <button
                                            type="submit"
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                px-3
                                                py-1.5
                                                rounded-lg
                                                bg-brand-600
                                                hover:bg-brand-700
                                                text-white
                                                font-semibold
                                                text-xs
                                            "
                                        >
                                            Update
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>


<!-- =========================================================
     PAGINATION
========================================================= -->

<?php if ($totalPages > 1): ?>

    <nav
        class="
            mt-6
            flex
            flex-wrap
            justify-center
            gap-2
        "
        aria-label="Order pagination"
    >


        <?php if ($page > 1): ?>

            <a
                href="<?= $orderPageUrl(
                    $page - 1
                ) ?>"
                class="
                    w-10
                    h-10
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    text-slate-600
                    hover:bg-brand-50
                    hover:text-brand-700
                "
                aria-label="Previous page"
            >

                <i
                    data-lucide="chevron-left"
                    class="w-4 h-4"
                ></i>

            </a>

        <?php endif; ?>


        <?php

        $startPage =
            max(
                1,
                $page - 2
            );


        $endPage =
            min(
                $totalPages,
                $page + 2
            );

        ?>


        <?php for (
            $pageNumber = $startPage;
            $pageNumber <= $endPage;
            $pageNumber++
        ): ?>

            <?php if (
                $pageNumber === $page
            ): ?>

                <span
                    class="
                        min-w-10
                        h-10
                        px-3
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-brand-600
                        text-white
                        font-bold
                        text-sm
                    "
                    aria-current="page"
                >

                    <?= $pageNumber ?>

                </span>

            <?php else: ?>

                <a
                    href="<?= $orderPageUrl(
                        $pageNumber
                    ) ?>"
                    class="
                        min-w-10
                        h-10
                        px-3
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-slate-600
                        hover:bg-brand-50
                        hover:text-brand-700
                        font-semibold
                        text-sm
                    "
                >

                    <?= $pageNumber ?>

                </a>

            <?php endif; ?>

        <?php endfor; ?>


        <?php if (
            $page < $totalPages
        ): ?>

            <a
                href="<?= $orderPageUrl(
                    $page + 1
                ) ?>"
                class="
                    w-10
                    h-10
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    text-slate-600
                    hover:bg-brand-50
                    hover:text-brand-700
                "
                aria-label="Next page"
            >

                <i
                    data-lucide="chevron-right"
                    class="w-4 h-4"
                ></i>

            </a>

        <?php endif; ?>


    </nav>

<?php endif; ?>


<?php

require_once __DIR__ .
    '/includes/admin-footer.php';

?>