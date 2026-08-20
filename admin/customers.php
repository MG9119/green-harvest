<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN CUSTOMER MANAGEMENT
 * =========================================================
 *
 * Responsibilities:
 * - Protect administrator access
 * - List registered customer accounts
 * - Search customers
 * - Show order activity
 * - Show non-cancelled order value
 * - Paginate customer records
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


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
| Build Customer Filters
|--------------------------------------------------------------------------
*/

$where = [
    'u.role = :role',
];


$params = [
    ':role' => 'customer',
];


if ($search !== '') {

    /*
     * Native PDO prepared statements are enabled,
     * therefore every placeholder must have a
     * unique name.
     */
    $where[] = '
        (
            u.full_name LIKE :search_name
            OR u.email LIKE :search_email
            OR u.phone LIKE :search_phone
        )
    ';


    $searchTerm =
        '%' . $search . '%';


    $params[':search_name'] =
        $searchTerm;


    $params[':search_email'] =
        $searchTerm;


    $params[':search_phone'] =
        $searchTerm;
}


$whereSql =
    ' WHERE ' .
    implode(
        ' AND ',
        $where
    );


/*
|--------------------------------------------------------------------------
| Customer Statistics
|--------------------------------------------------------------------------
*/

$totalCustomers = 0;

$totalPages = 1;

$offset = 0;

$customers = [];

$customersLoadError = false;


try {

    /*
    |--------------------------------------------------------------------------
    | Count Matching Customers
    |--------------------------------------------------------------------------
    */

    $countSql = '
        SELECT COUNT(*)

        FROM users u

        ' . $whereSql;


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


    $totalCustomers =
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
            $totalCustomers /
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
    | Load Customers
    |--------------------------------------------------------------------------
    |
    | We deliberately select only the fields required
    | by this page. The users.password column is never
    | loaded into the customer listing.
    |
    */

    $customersSql = "
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            u.created_at,

            COUNT(o.id) AS orders_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN o.order_status <> 'Cancelled'
                        THEN o.total_amount
                        ELSE 0
                    END
                ),
                0
            ) AS order_value,

            MAX(o.created_at) AS last_order_at

        FROM users u

        LEFT JOIN orders o
            ON o.user_id = u.id

        " . $whereSql . "

        GROUP BY
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.address,
            u.created_at

        ORDER BY
            u.created_at DESC,
            u.id DESC

        LIMIT :limit
        OFFSET :offset
    ";


    $stmt =
        $pdo->prepare(
            $customersSql
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


    $customers =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest admin customers loading error: ' .
        $e->getMessage()
    );


    $customersLoadError =
        true;
}


/*
|--------------------------------------------------------------------------
| Result Range
|--------------------------------------------------------------------------
*/

if ($totalCustomers > 0) {

    $showingFrom =
        $offset + 1;


    $showingTo = min(
        $offset +
        $itemsPerPage,
        $totalCustomers
    );

} else {

    $showingFrom = 0;

    $showingTo = 0;
}


/*
|--------------------------------------------------------------------------
| Pagination URL
|--------------------------------------------------------------------------
*/

$customerPageUrl =
    static function (
        int $pageNumber
    ) use (
        $search
    ): string {

        $query = [
            'page' =>
                $pageNumber,
        ];


        if ($search !== '') {

            $query['search'] =
                $search;
        }


        return url(
            'admin/customers.php?' .
            http_build_query(
                $query
            )
        );
    };


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Customer Management';


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

        <h2
            class="
                text-xl
                font-bold
                text-slate-900
            "
        >
            Registered Customers
        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >
            View customer accounts and their
            Green Harvest order activity.
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
            data-lucide="users"
            class="w-4 h-4"
        ></i>

        <?= number_format(
            $totalCustomers
        ) ?>

        customer<?= $totalCustomers === 1
            ? ''
            : 's'
        ?>

    </span>

</div>


<!-- =========================================================
     LOAD ERROR
========================================================= -->

<?php if ($customersLoadError): ?>

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

            The customer list could not be loaded.
            Please refresh the page.

        </div>

    </div>

<?php endif; ?>


<!-- =========================================================
     SEARCH
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
        action="<?= url(
            'admin/customers.php'
        ) ?>"
        class="
            flex
            flex-col
            sm:flex-row
            gap-3
        "
    >

        <div class="relative flex-1">

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
                type="search"
                name="search"
                value="<?= e($search) ?>"
                maxlength="100"
                placeholder="Search name, email or phone..."
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


        <button
            type="submit"
            class="
                inline-flex
                items-center
                justify-center
                gap-2
                px-5
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
                data-lucide="search"
                class="w-4 h-4"
            ></i>

            Search

        </button>


        <?php if ($search !== ''): ?>

            <a
                href="<?= url(
                    'admin/customers.php'
                ) ?>"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    px-4
                    py-2.5
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    hover:bg-rose-50
                    hover:border-rose-200
                    text-slate-500
                    hover:text-rose-600
                    text-sm
                    font-semibold
                "
            >

                <i
                    data-lucide="x"
                    class="w-4 h-4"
                ></i>

                Clear

            </a>

        <?php endif; ?>


    </form>

</div>


<!-- =========================================================
     CUSTOMER TABLE
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

        <h3
            class="
                font-bold
                text-slate-900
            "
        >
            Customer Accounts
        </h3>


        <p
            class="
                text-xs
                text-slate-400
                mt-1
            "
        >

            <?php if ($totalCustomers > 0): ?>

                Showing

                <?= $showingFrom ?>

                –

                <?= $showingTo ?>

                of

                <?= number_format(
                    $totalCustomers
                ) ?>

            <?php else: ?>

                No matching customers

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

                    <th class="py-3.5 px-6">
                        Customer
                    </th>

                    <th class="py-3.5 px-6">
                        Contact
                    </th>

                    <th class="py-3.5 px-6">
                        Joined
                    </th>

                    <th
                        class="
                            py-3.5
                            px-6
                            text-center
                        "
                    >
                        Orders
                    </th>

                    <th class="py-3.5 px-6">
                        Order Value
                    </th>

                    <th class="py-3.5 px-6">
                        Last Order
                    </th>

                    <th
                        class="
                            py-3.5
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


                <?php if (!$customers): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="
                                py-14
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
                                        data-lucide="users"
                                        class="w-6 h-6"
                                    ></i>

                                </span>


                                <h4
                                    class="
                                        font-bold
                                        text-slate-700
                                    "
                                >
                                    No customers found
                                </h4>


                                <p
                                    class="
                                        text-xs
                                        text-slate-400
                                        mt-1
                                    "
                                >

                                    <?php if (
                                        $search !== ''
                                    ): ?>

                                        Try another search.

                                    <?php else: ?>

                                        Registered customer
                                        accounts will appear here.

                                    <?php endif; ?>

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $customers as
                        $customer
                    ): ?>

                        <?php

                        $customerId =
                            (int)
                            $customer['id'];


                        $customerName =
                            trim(
                                (string) (
                                    $customer['full_name']
                                    ?? ''
                                )
                            );


                        if ($customerName === '') {

                            $customerName =
                                'Customer';
                        }


                        $initial =
                            strtoupper(
                                substr(
                                    $customerName,
                                    0,
                                    1
                                )
                            );


                        $ordersCount =
                            (int) (
                                $customer['orders_count']
                                ?? 0
                            );


                        $lastOrder =
                            trim(
                                (string) (
                                    $customer['last_order_at']
                                    ?? ''
                                )
                            );

                        ?>


                        <tr
                            class="
                                hover:bg-slate-50/60
                                transition-colors
                            "
                        >


                            <!-- Customer -->

                            <td class="py-4 px-6">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                        min-w-[190px]
                                    "
                                >

                                    <div
                                        class="
                                            w-10
                                            h-10
                                            rounded-full
                                            bg-brand-50
                                            border
                                            border-brand-100
                                            flex
                                            items-center
                                            justify-center
                                            font-bold
                                            text-brand-700
                                            text-sm
                                            flex-shrink-0
                                        "
                                    >

                                        <?= e($initial) ?>

                                    </div>


                                    <div class="min-w-0">

                                        <strong
                                            class="
                                                block
                                                text-slate-900
                                                truncate
                                            "
                                        >

                                            <?= e(
                                                $customerName
                                            ) ?>

                                        </strong>


                                        <span
                                            class="
                                                block
                                                text-[11px]
                                                text-slate-400
                                                mt-1
                                            "
                                        >

                                            Customer #<?= $customerId ?>

                                        </span>

                                    </div>

                                </div>

                            </td>


                            <!-- Contact -->

                            <td class="py-4 px-6">

                                <a
                                    href="mailto:<?= e(
                                        $customer['email']
                                    ) ?>"
                                    class="
                                        block
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        hover:text-brand-700
                                    "
                                >

                                    <?= e(
                                        $customer['email']
                                    ) ?>

                                </a>


                                <?php if (
                                    !empty(
                                        $customer['phone']
                                    )
                                ): ?>

                                    <a
                                        href="tel:<?= e(
                                            $customer['phone']
                                        ) ?>"
                                        class="
                                            block
                                            text-xs
                                            text-slate-400
                                            hover:text-brand-700
                                            mt-1
                                        "
                                    >

                                        <?= e(
                                            $customer['phone']
                                        ) ?>

                                    </a>

                                <?php else: ?>

                                    <span
                                        class="
                                            block
                                            text-xs
                                            text-slate-400
                                            mt-1
                                        "
                                    >
                                        No phone
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Joined -->

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
                                        $customer['created_at']
                                    )
                                ) ?>

                            </td>


                            <!-- Orders -->

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
                                        min-w-9
                                        px-2.5
                                        py-1
                                        rounded-full
                                        text-xs
                                        font-bold

                                        <?= $ordersCount > 0
                                            ? 'bg-brand-50 text-brand-700 border border-brand-100'
                                            : 'bg-slate-100 text-slate-500'
                                        ?>
                                    "
                                >

                                    <?= $ordersCount ?>

                                </span>

                            </td>


                            <!-- Order Value -->

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
                                    $customer['order_value']
                                    ?? 0
                                ) ?>

                            </td>


                            <!-- Last Order -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    text-xs
                                    text-slate-500
                                    whitespace-nowrap
                                "
                            >

                                <?php if (
                                    $lastOrder !== ''
                                ): ?>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $lastOrder
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-slate-400">
                                        No orders
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Action -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    text-right
                                    whitespace-nowrap
                                "
                            >

                                <?php if (
                                    $ordersCount > 0
                                ): ?>

                                    <a
                                        href="<?= url(
                                            'admin/orders.php?search=' .
                                            urlencode(
                                                $customer['email']
                                            )
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
                                            data-lucide="shopping-bag"
                                            class="w-3.5 h-3.5"
                                        ></i>

                                        View Orders

                                    </a>

                                <?php else: ?>

                                    <span
                                        class="
                                            text-xs
                                            text-slate-300
                                        "
                                    >
                                        No orders
                                    </span>

                                <?php endif; ?>

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
        aria-label="Customer pagination"
    >


        <?php if ($page > 1): ?>

            <a
                href="<?= $customerPageUrl(
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
                    href="<?= $customerPageUrl(
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
                href="<?= $customerPageUrl(
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