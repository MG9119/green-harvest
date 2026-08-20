<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN DASHBOARD
 * =========================================================
 *
 * Responsibilities:
 * - Display store statistics
 * - Display recent orders
 * - Display top-selling products
 * - Display low-stock products
 * - Provide quick admin actions
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Dashboard Defaults
|--------------------------------------------------------------------------
*/

$totalProducts = 0;
$totalCategories = 0;
$totalCustomers = 0;
$totalOrders = 0;
$pendingOrders = 0;
$deliveredOrders = 0;
$totalRevenue = 0.00;
$lowStockCount = 0;

$recentOrders = [];
$topProducts = [];
$lowStock = [];

$dashboardLoadError = false;


/*
|--------------------------------------------------------------------------
| Order Status Badge Helper
|--------------------------------------------------------------------------
*/

$orderStatusClass = static function (
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

        'out for delivery',
        'shipped' =>
            'bg-cyan-50 text-cyan-700 border-cyan-200',

        'delivered',
        'completed' =>
            'bg-emerald-50 text-emerald-700 border-emerald-200',

        'cancelled',
        'canceled',
        'failed' =>
            'bg-rose-50 text-rose-700 border-rose-200',

        default =>
            'bg-amber-50 text-amber-700 border-amber-200',
    };
};


/*
|--------------------------------------------------------------------------
| Dashboard Metrics
|--------------------------------------------------------------------------
*/

try {

    /*
     * Products
     */
    $totalProducts = (int) $pdo
        ->query(
            'SELECT COUNT(*) FROM products'
        )
        ->fetchColumn();


    /*
     * Categories
     */
    $totalCategories = (int) $pdo
        ->query(
            'SELECT COUNT(*) FROM categories'
        )
        ->fetchColumn();


    /*
     * Customers
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)

        FROM users

        WHERE role = ?
        '
    );

    $stmt->execute([
        'customer',
    ]);

    $totalCustomers =
        (int) $stmt->fetchColumn();


    /*
     * Orders
     */
    $totalOrders = (int) $pdo
        ->query(
            'SELECT COUNT(*) FROM orders'
        )
        ->fetchColumn();


    /*
     * Pending Orders
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)

        FROM orders

        WHERE LOWER(order_status) = ?
        '
    );

    $stmt->execute([
        'pending',
    ]);

    $pendingOrders =
        (int) $stmt->fetchColumn();


    /*
     * Delivered Orders
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)

        FROM orders

        WHERE LOWER(order_status) IN (?, ?)
        '
    );

    $stmt->execute([
        'delivered',
        'completed',
    ]);

    $deliveredOrders =
        (int) $stmt->fetchColumn();


    /*
     * Revenue
     *
     * Cancelled orders are excluded.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            COALESCE(
                SUM(total_amount),
                0
            )

        FROM orders

        WHERE LOWER(order_status)
        NOT IN (?, ?)
        '
    );

    $stmt->execute([
        'cancelled',
        'canceled',
    ]);

    $totalRevenue =
        (float) $stmt->fetchColumn();


    /*
     * Low Stock
     *
     * Only active products are counted.
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)

        FROM products

        WHERE status = ?
          AND stock_quantity <= 10
        '
    );

    $stmt->execute([
        'active',
    ]);

    $lowStockCount =
        (int) $stmt->fetchColumn();


} catch (PDOException $e) {

    error_log(
        'Green Harvest dashboard metrics error: ' .
        $e->getMessage()
    );

    $dashboardLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query(
        '
        SELECT
            id,
            order_number,
            customer_name,
            total_amount,
            order_status,
            created_at

        FROM orders

        ORDER BY created_at DESC

        LIMIT 6
        '
    );


    $recentOrders =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest recent orders error: ' .
        $e->getMessage()
    );

    $dashboardLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Top-Selling Products
|--------------------------------------------------------------------------
|
| Use the product snapshot stored in order_items.
|
| This allows historical sales information to remain
| available even when the current product changes.
|
*/

try {

    $stmt = $pdo->prepare(
        '
        SELECT
            oi.product_name,
            SUM(oi.quantity) AS sold

        FROM order_items oi

        INNER JOIN orders o
            ON o.id = oi.order_id

        WHERE LOWER(o.order_status)
        NOT IN (?, ?)

        GROUP BY
            oi.product_name

        ORDER BY
            sold DESC,
            oi.product_name ASC

        LIMIT 6
        '
    );


    $stmt->execute([
        'cancelled',
        'canceled',
    ]);


    $topProducts =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest top products error: ' .
        $e->getMessage()
    );

    $dashboardLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Low-Stock Products
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            name,
            stock_quantity

        FROM products

        WHERE status = ?
          AND stock_quantity <= 10

        ORDER BY
            stock_quantity ASC,
            name ASC

        LIMIT 6
        '
    );


    $stmt->execute([
        'active',
    ]);


    $lowStock =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest low stock error: ' .
        $e->getMessage()
    );

    $dashboardLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Statistics Cards
|--------------------------------------------------------------------------
*/

$statCards = [

    [
        'label' => 'Total Products',
        'value' => number_format(
            $totalProducts
        ),
        'icon'  => 'package',
        'color' => 'bg-blue-50 text-blue-600',
    ],

    [
        'label' => 'Categories',
        'value' => number_format(
            $totalCategories
        ),
        'icon'  => 'folder-tree',
        'color' => 'bg-purple-50 text-purple-600',
    ],

    [
        'label' => 'Customers',
        'value' => number_format(
            $totalCustomers
        ),
        'icon'  => 'users',
        'color' => 'bg-indigo-50 text-indigo-600',
    ],

    [
        'label' => 'Total Orders',
        'value' => number_format(
            $totalOrders
        ),
        'icon'  => 'shopping-bag',
        'color' => 'bg-brand-50 text-brand-600',
    ],

    [
        'label' => 'Pending Orders',
        'value' => number_format(
            $pendingOrders
        ),
        'icon'  => 'clock-3',
        'color' => 'bg-amber-50 text-amber-600',
    ],

    [
        'label' => 'Delivered',
        'value' => number_format(
            $deliveredOrders
        ),
        'icon'  => 'circle-check-big',
        'color' => 'bg-emerald-50 text-emerald-600',
    ],

    [
        'label' => 'Order Revenue',
        'value' => money(
            $totalRevenue
        ),
        'icon'  => 'banknote',
        'color' => 'bg-emerald-50 text-emerald-700',
    ],

    [
        'label' => 'Low Stock',
        'value' => number_format(
            $lowStockCount
        ),
        'icon'  => 'triangle-alert',
        'color' => 'bg-rose-50 text-rose-600',
    ],

];


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';

require_once __DIR__ . '/includes/admin-header.php';

?>


<?php if ($dashboardLoadError): ?>

    <div
        class="
            mb-6
            px-4
            py-3
            bg-amber-50
            border
            border-amber-200
            text-amber-800
            rounded-xl
            text-sm
        "
    >

        <div class="flex items-start gap-3">

            <i
                data-lucide="triangle-alert"
                class="w-5 h-5 mt-0.5 flex-shrink-0"
            ></i>

            <div>

                <strong>
                    Some dashboard information could not be loaded.
                </strong>

                <p class="mt-1 text-xs text-amber-700">
                    Other parts of the admin system may continue
                    working normally.
                </p>

            </div>

        </div>

    </div>

<?php endif; ?>


<!-- =========================================================
     QUICK ACTIONS
========================================================= -->

<div
    class="
        flex
        flex-col
        sm:flex-row
        sm:items-center
        sm:justify-between
        gap-4
        mb-7
    "
>

    <div>

        <p
            class="
                text-sm
                text-slate-500
            "
        >
            Monitor your Green Harvest store and manage
            products, customers and orders.
        </p>

    </div>


    <div
        class="
            flex
            flex-wrap
            gap-2
        "
    >

        <a
            href="<?= url('admin/add-product.php') ?>"
            class="
                inline-flex
                items-center
                gap-2
                px-4
                py-2.5
                rounded-xl
                bg-brand-600
                hover:bg-brand-700
                text-white
                text-sm
                font-semibold
                transition-colors
            "
        >

            <i
                data-lucide="plus"
                class="w-4 h-4"
            ></i>

            Add Product

        </a>


        <a
            href="<?= url('admin/orders.php') ?>"
            class="
                inline-flex
                items-center
                gap-2
                px-4
                py-2.5
                rounded-xl
                bg-white
                border
                border-slate-200
                hover:border-slate-300
                hover:bg-slate-50
                text-slate-700
                text-sm
                font-semibold
                transition-colors
            "
        >

            <i
                data-lucide="shopping-bag"
                class="w-4 h-4"
            ></i>

            Manage Orders

        </a>

    </div>

</div>


<!-- =========================================================
     STATISTICS
========================================================= -->

<div
    class="
        grid
        grid-cols-1
        sm:grid-cols-2
        xl:grid-cols-4
        gap-5
        mb-8
    "
>

    <?php foreach ($statCards as $card): ?>


        <div
            class="
                bg-white
                rounded-2xl
                border
                border-slate-200
                p-5
                shadow-sm
                flex
                items-center
                justify-between
                gap-4
            "
        >

            <div class="min-w-0">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                        mb-1.5
                    "
                >

                    <?= e($card['label']) ?>

                </p>


                <h3
                    class="
                        text-2xl
                        font-bold
                        text-slate-900
                        truncate
                    "
                >

                    <?= e(
                        (string) $card['value']
                    ) ?>

                </h3>

            </div>


            <div
                class="
                    w-12
                    h-12
                    rounded-xl
                    <?= e($card['color']) ?>
                    flex
                    items-center
                    justify-center
                    flex-shrink-0
                "
            >

                <i
                    data-lucide="<?= e($card['icon']) ?>"
                    class="w-6 h-6"
                ></i>

            </div>

        </div>


    <?php endforeach; ?>

</div>


<!-- =========================================================
     DASHBOARD CONTENT
========================================================= -->

<div
    class="
        grid
        grid-cols-1
        xl:grid-cols-12
        gap-6
    "
>


    <!-- =====================================================
         RECENT ORDERS
    ====================================================== -->

    <section
        class="
            xl:col-span-6
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
                gap-4
            "
        >

            <div>

                <h2
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Recent Orders
                </h2>

                <p
                    class="
                        text-xs
                        text-slate-400
                        mt-1
                    "
                >
                    Latest customer purchases
                </p>

            </div>


            <a
                href="<?= url('admin/orders.php') ?>"
                class="
                    text-xs
                    font-bold
                    text-brand-600
                    hover:text-brand-700
                    whitespace-nowrap
                "
                style="display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:10px;border:1px solid rgba(21,128,61,0.12);color:#15803d;text-decoration:none;"
                aria-label="View all orders"
            >
                View All
            </a>

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

                        <th class="py-3 px-6">
                            Order
                        </th>

                        <th class="py-3 px-6">
                            Amount
                        </th>

                        <th class="py-3 px-6">
                            Status
                        </th>

                        <th
                            class="
                                py-3
                                px-6
                                text-right
                            "
                        >
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                        divide-y
                        divide-slate-100
                    "
                >


                    <?php if (!$recentOrders): ?>


                        <tr>

                            <td
                                colspan="4"
                                class="
                                    py-12
                                    px-6
                                    text-center
                                "
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
                                            w-11
                                            h-11
                                            rounded-xl
                                            bg-slate-100
                                            flex
                                            items-center
                                            justify-center
                                            text-slate-400
                                            mb-3
                                        "
                                    >

                                        <i
                                            data-lucide="shopping-bag"
                                            class="w-5 h-5"
                                        ></i>

                                    </span>

                                    <span
                                        class="
                                            text-sm
                                            font-semibold
                                            text-slate-500
                                        "
                                    >
                                        No orders recorded yet.
                                    </span>

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($recentOrders as $order): ?>

                            <?php

                            $orderId =
                                (int) $order['id'];

                            $status =
                                trim(
                                    (string) (
                                        $order['order_status']
                                        ?? 'Pending'
                                    )
                                );

                            ?>


                            <tr
                                class="
                                    hover:bg-slate-50/60
                                    transition-colors
                                "
                            >


                                <td
                                    class="
                                        py-4
                                        px-6
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
                                            block
                                        "
                                    >

                                        <?= e(
                                            $order['order_number']
                                        ) ?>

                                    </a>


                                    <span
                                        class="
                                            text-xs
                                            text-slate-400
                                            block
                                            mt-0.5
                                        "
                                    >

                                        <?= e(
                                            $order['customer_name']
                                            ?: 'Customer'
                                        ) ?>

                                    </span>

                                </td>


                                <td
                                    class="
                                        py-4
                                        px-6
                                        font-semibold
                                        text-slate-800
                                        whitespace-nowrap
                                    "
                                >

                                    <?= money(
                                        $order['total_amount']
                                    ) ?>

                                </td>


                                <td
                                    class="
                                        py-4
                                        px-6
                                    "
                                >

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            px-2.5
                                            py-1
                                            rounded-full
                                            text-xs
                                            font-bold
                                            border
                                            <?= e(
                                                $orderStatusClass(
                                                    $status
                                                )
                                            ) ?>
                                        "
                                    >

                                        <?= e($status) ?>

                                    </span>

                                </td>


                                <td
                                    class="
                                        py-4
                                        px-6
                                        text-right
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
                                            justify-center
                                            w-8
                                            h-8
                                            rounded-lg
                                            text-slate-500
                                            hover:text-brand-700
                                            hover:bg-brand-50
                                            transition-colors
                                        "
                                        title="View order"
                                    >

                                        <i
                                            data-lucide="arrow-up-right"
                                            class="w-4 h-4"
                                        ></i>

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


    <!-- =====================================================
         TOP PRODUCTS
    ====================================================== -->

    <section
        class="
            xl:col-span-3
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
                justify-between
                gap-3
                border-b
                border-slate-100
                pb-4
                mb-4
            "
        >

            <div>

                <h2
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Top Products
                </h2>

                <p
                    class="
                        text-xs
                        text-slate-400
                        mt-1
                    "
                >
                    By units ordered
                </p>

            </div>


            <i
                data-lucide="chart-no-axes-column-increasing"
                class="
                    w-5
                    h-5
                    text-brand-600
                "
            ></i>

        </div>


        <?php if (!$topProducts): ?>


            <div
                class="
                    py-8
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

                <p class="text-xs">
                    No sales recorded yet.
                </p>

            </div>


        <?php else: ?>


            <div class="space-y-3">


                <?php foreach (
                    $topProducts as
                    $index => $item
                ): ?>


                    <div
                        class="
                            flex
                            items-center
                            gap-3
                            border-b
                            border-slate-100
                            pb-3
                            last:border-b-0
                            last:pb-0
                        "
                    >


                        <span
                            class="
                                w-7
                                h-7
                                rounded-lg
                                bg-slate-100
                                text-slate-500
                                text-xs
                                font-bold
                                flex
                                items-center
                                justify-center
                                flex-shrink-0
                            "
                        >

                            <?= $index + 1 ?>

                        </span>


                        <span
                            class="
                                text-xs
                                font-semibold
                                text-slate-700
                                truncate
                                flex-1
                            "
                        >

                            <?= e(
                                $item['product_name']
                            ) ?>

                        </span>


                        <span
                            class="
                                text-[11px]
                                font-bold
                                bg-brand-50
                                text-brand-700
                                px-2
                                py-1
                                rounded-md
                                border
                                border-brand-100
                                whitespace-nowrap
                            "
                        >

                            <?= (int) $item['sold'] ?>

                            sold

                        </span>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


    <!-- =====================================================
         LOW STOCK
    ====================================================== -->

    <section
        class="
            xl:col-span-3
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
                justify-between
                gap-3
                border-b
                border-slate-100
                pb-4
                mb-4
            "
        >

            <div>

                <h2
                    class="
                        font-bold
                        text-slate-900
                    "
                >
                    Low Stock
                </h2>

                <p
                    class="
                        text-xs
                        text-slate-400
                        mt-1
                    "
                >
                    10 units or fewer
                </p>

            </div>


            <?php if ($lowStockCount > 0): ?>

                <span
                    class="
                        w-2.5
                        h-2.5
                        rounded-full
                        bg-rose-500
                        animate-pulse
                    "
                ></span>

            <?php else: ?>

                <span
                    class="
                        w-2.5
                        h-2.5
                        rounded-full
                        bg-emerald-500
                    "
                ></span>

            <?php endif; ?>


        </div>


        <?php if (!$lowStock): ?>


            <div
                class="
                    py-8
                    text-center
                    text-slate-400
                "
            >

                <i
                    data-lucide="circle-check-big"
                    class="
                        w-8
                        h-8
                        mx-auto
                        mb-3
                        text-emerald-500
                    "
                ></i>

                <p class="text-xs">
                    All active products are well stocked.
                </p>

            </div>


        <?php else: ?>


            <div class="space-y-3">


                <?php foreach ($lowStock as $item): ?>


                    <div
                        class="
                            flex
                            items-center
                            justify-between
                            gap-3
                            border-b
                            border-slate-100
                            pb-3
                            last:border-b-0
                            last:pb-0
                        "
                    >


                        <div class="min-w-0">


                            <a
                                href="<?= url(
                                    'admin/edit-product.php?id=' .
                                    (int) $item['id']
                                ) ?>"
                                class="
                                    text-xs
                                    font-semibold
                                    text-slate-700
                                    hover:text-brand-700
                                    truncate
                                    block
                                "
                            >

                                <?= e(
                                    $item['name']
                                ) ?>

                            </a>


                        </div>


                        <?php

                        $remaining =
                            (int) $item['stock_quantity'];

                        ?>


                        <span
                            class="
                                text-[11px]
                                font-bold
                                px-2
                                py-1
                                rounded-md
                                border
                                whitespace-nowrap

                                <?= $remaining === 0
                                    ? 'bg-rose-100 text-rose-800 border-rose-200'
                                    : 'bg-rose-50 text-rose-700 border-rose-100'
                                ?>
                            "
                        >

                            <?= $remaining ?>

                            left

                        </span>


                    </div>


                <?php endforeach; ?>


            </div>


            <a
                href="<?= url('admin/products.php') ?>"
                class="
                    inline-flex
                    items-center
                    gap-2
                    mt-5
                    text-xs
                    font-bold
                    text-brand-600
                    hover:text-brand-700
                "
                style="display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:10px;border:1px solid rgba(21,128,61,0.12);color:#15803d;text-decoration:none;background:transparent;"
                aria-label="Manage inventory"
            >

                <i data-lucide="package" class="w-4 h-4"></i>

                <span>View all available stock</span>

            </a>


        <?php endif; ?>


    </section>


</div>


<?php

require_once __DIR__ . '/includes/admin-footer.php';

?>