<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN PRODUCT MANAGEMENT
 * =========================================================
 *
 * Responsibilities:
 * - Protect the admin page
 * - Search and filter products
 * - Display inventory information
 * - Allow editing
 * - Safely delete/deactivate products
 * - Remove unused local product images
 * - Paginate the product catalogue
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Local Product Image Check
|--------------------------------------------------------------------------
|
| Only simple locally managed filenames inside uploads/products/
| may be physically deleted.
|
*/

$isLocalProductImage =
    static function (?string $image): bool {

        $image = trim((string) $image);

        if ($image === '') {
            return false;
        }

        if (
            filter_var(
                $image,
                FILTER_VALIDATE_URL
            )
        ) {
            return false;
        }

        if (
            basename($image) !== $image ||
            str_contains($image, '..') ||
            str_contains($image, '/') ||
            str_contains($image, '\\')
        ) {
            return false;
        }

        $extension = strtolower(
            pathinfo(
                $image,
                PATHINFO_EXTENSION
            )
        );

        return in_array(
            $extension,
            [
                'jpg',
                'jpeg',
                'png',
                'webp',
            ],
            true
        );
    };


/*
|--------------------------------------------------------------------------
| Delete Local Product Image
|--------------------------------------------------------------------------
*/

$deleteLocalProductImage =
    static function (?string $image) use ($isLocalProductImage): void {

        if (
            !$isLocalProductImage(
                $image
            )
        ) {
            return;
        }

        $path =
            PRODUCT_UPLOAD_PATH .
            DIRECTORY_SEPARATOR .
            $image;

        if (is_file($path)) {
            @unlink($path);
        }
    };


/*
|--------------------------------------------------------------------------
| Delete Product
|--------------------------------------------------------------------------
|
| POST actions are handled before the admin header outputs HTML.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );


    if ($action === 'delete') {

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
                'Invalid product request. Please try again.'
            );

            redirectTo(
                'admin/products.php'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Product ID
        |--------------------------------------------------------------------------
        */

        $productId = filter_var(
            $_POST['id']
            ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $productId === false ||
            $productId === null ||
            $productId <= 0
        ) {

            setFlash(
                'error',
                'Invalid product selected.'
            );

            redirectTo(
                'admin/products.php'
            );
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Load Product
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                '
                SELECT
                    id,
                    name,
                    image,
                    status

                FROM products

                WHERE id = ?

                LIMIT 1
                '
            );


            $stmt->execute([
                $productId,
            ]);


            $product =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$product) {

                setFlash(
                    'error',
                    'The selected product could not be found.'
                );

                redirectTo(
                    'admin/products.php'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Check Order History
            |--------------------------------------------------------------------------
            |
            | Products that have already appeared in customer orders
            | should be preserved for historical purposes.
            |
            */

            $stmt = $pdo->prepare(
                '
                SELECT COUNT(*)

                FROM order_items

                WHERE product_id = ?
                '
            );


            $stmt->execute([
                $productId,
            ]);


            $orderItemCount =
                (int) $stmt->fetchColumn();


            /*
            |--------------------------------------------------------------------------
            | Product Has Order History
            |--------------------------------------------------------------------------
            */

            if ($orderItemCount > 0) {

                $stmt = $pdo->prepare(
                    '
                    UPDATE products

                    SET status = ?

                    WHERE id = ?
                    '
                );


                $stmt->execute([
                    'inactive',
                    $productId,
                ]);


                setFlash(
                    'warning',
                    'This product has order history, so it was marked inactive instead of being permanently deleted.'
                );


                redirectTo(
                    'admin/products.php'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Permanently Delete Product
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                '
                DELETE FROM products

                WHERE id = ?
                '
            );


            $stmt->execute([
                $productId,
            ]);


            if ($stmt->rowCount() === 1) {

                /*
                 * Database deletion succeeded.
                 *
                 * The product image is now unused, so remove it
                 * only if it is one of our locally managed uploads.
                 */
                $deleteLocalProductImage(
                    $product['image']
                    ?? null
                );


                setFlash(
                    'success',
                    'Product deleted successfully.'
                );

            } else {

                setFlash(
                    'error',
                    'The product could not be deleted.'
                );
            }


        } catch (PDOException $e) {

            error_log(
                'Green Harvest product deletion error: ' .
                $e->getMessage()
            );


            /*
             * If a database constraint prevents deletion,
             * preserve the product and deactivate it.
             */
            try {

                $stmt = $pdo->prepare(
                    '
                    UPDATE products

                    SET status = ?

                    WHERE id = ?
                    '
                );


                $stmt->execute([
                    'inactive',
                    $productId,
                ]);


                setFlash(
                    'warning',
                    'The product could not be permanently deleted, so it was marked inactive instead.'
                );


            } catch (PDOException $updateException) {

                error_log(
                    'Green Harvest product deactivation error: ' .
                    $updateException->getMessage()
                );


                setFlash(
                    'error',
                    'The product could not be removed. Please try again.'
                );
            }
        }


        redirectTo(
            'admin/products.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Filters
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


$statusFilter = strtolower(
    trim(
        (string) (
            $_GET['status']
            ?? ''
        )
    )
);


if (
    !in_array(
        $statusFilter,
        [
            '',
            'active',
            'inactive',
        ],
        true
    )
) {

    $statusFilter = '';
}


$categoryFilter = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);


if (
    $categoryFilter === false ||
    $categoryFilter === null ||
    $categoryFilter < 1
) {

    $categoryFilter = 0;
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
| Load Categories
|--------------------------------------------------------------------------
*/

$categories = [];


try {

    $stmt = $pdo->query(
        '
        SELECT
            id,
            name

        FROM categories

        ORDER BY name ASC
        '
    );


    $categories =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest admin product category loading error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| Build Product Filters
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


if ($search !== '') {

    /*
     * IMPORTANT:
     *
     * Native PDO prepared statements must not reuse the same
     * named placeholder multiple times.
     *
     * Therefore each LIKE expression gets its own placeholder.
     */

    $where[] = '
        (
            p.name LIKE :search_name
            OR p.description LIKE :search_description
            OR c.name LIKE :search_category
        )
    ';


    $searchTerm =
        '%' . $search . '%';


    $params[':search_name'] =
        $searchTerm;


    $params[':search_description'] =
        $searchTerm;


    $params[':search_category'] =
        $searchTerm;
}


if ($statusFilter !== '') {

    $where[] =
        'p.status = :status';


    $params[':status'] =
        $statusFilter;
}


if ($categoryFilter > 0) {

    $where[] =
        'p.category_id = :category_id';


    $params[':category_id'] =
        $categoryFilter;
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
| Load Products
|--------------------------------------------------------------------------
*/

$totalProducts = 0;

$totalPages = 1;

$offset = 0;

$products = [];

$productLoadError = false;


try {

    /*
    |--------------------------------------------------------------------------
    | Count Products
    |--------------------------------------------------------------------------
    */

    $countSql = '
        SELECT COUNT(*)

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

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

        if (
            $key === ':category_id'
        ) {

            $stmt->bindValue(
                $key,
                (int) $value,
                PDO::PARAM_INT
            );

        } else {

            $stmt->bindValue(
                $key,
                (string) $value,
                PDO::PARAM_STR
            );
        }
    }


    $stmt->execute();


    $totalProducts =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $totalPages = max(
        1,
        (int) ceil(
            $totalProducts /
            $itemsPerPage
        )
    );


    if ($page > $totalPages) {

        $page = $totalPages;
    }


    $offset =
        ($page - 1) *
        $itemsPerPage;


    /*
    |--------------------------------------------------------------------------
    | Fetch Products
    |--------------------------------------------------------------------------
    */

    $productSql = '
        SELECT
            p.id,
            p.category_id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.unit,
            p.stock_quantity,
            p.image,
            p.is_organic,
            p.is_featured,
            p.status,
            p.created_at,
            p.updated_at,

            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        ' .
        $whereSql .

        '
        ORDER BY
            p.created_at DESC,
            p.id DESC

        LIMIT :limit
        OFFSET :offset
        ';


    $stmt =
        $pdo->prepare(
            $productSql
        );


    foreach (
        $params as
        $key => $value
    ) {

        if (
            $key === ':category_id'
        ) {

            $stmt->bindValue(
                $key,
                (int) $value,
                PDO::PARAM_INT
            );

        } else {

            $stmt->bindValue(
                $key,
                (string) $value,
                PDO::PARAM_STR
            );
        }
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


    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest admin products loading error: ' .
        $e->getMessage()
    );


    $productLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Stock Badge Helper
|--------------------------------------------------------------------------
*/

$stockClass =
    static function (
        int $quantity
    ): string {

        if ($quantity <= 0) {

            return
                'bg-rose-50 text-rose-700 border-rose-200';
        }


        if ($quantity <= 10) {

            return
                'bg-amber-50 text-amber-700 border-amber-200';
        }


        return
            'bg-emerald-50 text-emerald-700 border-emerald-200';
    };


/*
|--------------------------------------------------------------------------
| Product Page URL
|--------------------------------------------------------------------------
*/

$productPageUrl =
    static function (
        int $pageNumber
    ) use (
        $search,
        $statusFilter,
        $categoryFilter
    ): string {

        $query = [
            'page' =>
                $pageNumber,
        ];


        if ($search !== '') {

            $query['search'] =
                $search;
        }


        if ($statusFilter !== '') {

            $query['status'] =
                $statusFilter;
        }


        if ($categoryFilter > 0) {

            $query['category'] =
                $categoryFilter;
        }


        return url(
            'admin/products.php?' .
            http_build_query(
                $query
            )
        );
    };


/*
|--------------------------------------------------------------------------
| Showing Range
|--------------------------------------------------------------------------
*/

if ($totalProducts > 0) {

    $showingFrom =
        $offset + 1;


    $showingTo = min(
        $offset +
        $itemsPerPage,
        $totalProducts
    );

} else {

    $showingFrom = 0;

    $showingTo = 0;
}


/*
|--------------------------------------------------------------------------
| Render Admin Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Product Management';


require_once __DIR__ .
    '/includes/admin-header.php';

?>


<!-- =========================================================
     PRODUCT PAGE HEADER
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
            Products Catalog
        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >
            Manage products, inventory,
            pricing and availability.
        </p>

    </div>


    <a
        href="<?= url(
            'admin/add-product.php'
        ) ?>"
        class="
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
            transition-all
            shadow-md
            shadow-brand-600/20
        "
    >

        <i
            data-lucide="plus"
            class="w-4 h-4"
        ></i>

        Add New Product

    </a>

</div>


<!-- =========================================================
     LOAD ERROR
========================================================= -->

<?php if ($productLoadError): ?>

    <div
        class="
            mb-6
            px-4
            py-3
            rounded-xl
            bg-rose-50
            border
            border-rose-200
            text-rose-700
            text-sm
        "
    >

        <div class="flex gap-2">

            <i
                data-lucide="triangle-alert"
                class="w-5 h-5 flex-shrink-0"
            ></i>

            <span>
                We could not load the product catalogue.
                Please refresh the page and try again.
            </span>

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
        action="<?= url(
            'admin/products.php'
        ) ?>"
        class="
            grid
            grid-cols-1
            md:grid-cols-2
            xl:grid-cols-12
            gap-4
            items-end
        "
    >


        <!-- Search -->

        <div class="xl:col-span-5">

            <label
                for="product-search"
                class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                "
            >
                Quick Search
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
                    id="product-search"
                    type="search"
                    name="search"
                    value="<?= e($search) ?>"
                    maxlength="100"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Search products by name, category or description"
                    placeholder="Search by product name, category or description..."
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
                        transition-colors
                        focus:border-brand-500
                        focus:ring-4
                        focus:ring-brand-100
                    "
                >

            </div>

        </div>


        <!-- Category -->

        <div class="xl:col-span-3">

            <label
                for="product-category"
                class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                "
            >
                Category
            </label>


            <select
                id="product-category"
                name="category"
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
                    All Categories
                </option>


                <?php foreach (
                    $categories as
                    $category
                ): ?>

                    <?php

                    $categoryId =
                        (int) $category['id'];

                    ?>

                    <option
                        value="<?= $categoryId ?>"
                        <?= $categoryFilter === $categoryId
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= e(
                            $category['name']
                        ) ?>

                    </option>

                <?php endforeach; ?>


            </select>

        </div>


        <!-- Status -->

        <div class="xl:col-span-2">

            <label
                for="product-status"
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
                id="product-status"
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

                <option
                    value=""
                    <?= $statusFilter === ''
                        ? 'selected'
                        : ''
                    ?>
                >
                    All Statuses
                </option>


                <option
                    value="active"
                    <?= $statusFilter === 'active'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Active
                </option>


                <option
                    value="inactive"
                    <?= $statusFilter === 'inactive'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Inactive
                </option>

            </select>

        </div>


        <!-- Filter -->

        <div
            class="
                xl:col-span-2
                flex
                gap-2
            "
        >

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
                $statusFilter !== '' ||
                $categoryFilter > 0
            ): ?>

                <a
                    href="<?= url(
                        'admin/products.php'
                    ) ?>"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        w-11
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        text-slate-500
                        hover:text-rose-600
                        hover:bg-rose-50
                        hover:border-rose-200
                    "
                    title="Clear filters"
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
     PRODUCTS TABLE
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
            flex
            flex-wrap
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
                All Products
            </h3>


            <p
                class="
                    text-xs
                    text-slate-400
                    mt-1
                "
            >

                <?php if (
                    $totalProducts > 0
                ): ?>

                    Showing
                    <?= $showingFrom ?>
                    –
                    <?= $showingTo ?>
                    of
                    <?= number_format(
                        $totalProducts
                    ) ?>

                <?php else: ?>

                    No matching products

                <?php endif; ?>

            </p>

        </div>


        <span
            class="
                inline-flex
                items-center
                px-3
                py-1
                rounded-full
                bg-brand-50
                text-brand-700
                border
                border-brand-100
                text-xs
                font-bold
            "
        >

            <?= number_format(
                $totalProducts
            ) ?>

            product<?= $totalProducts === 1
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
                        Category
                    </th>

                    <th class="py-3.5 px-6">
                        Price
                    </th>

                    <th
                        class="
                            py-3.5
                            px-6
                            text-center
                        "
                    >
                        Stock
                    </th>

                    <th class="py-3.5 px-6">
                        Status
                    </th>

                    <th
                        class="
                            py-3.5
                            px-6
                            text-right
                        "
                    >
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody
                class="
                    divide-y
                    divide-slate-100
                "
            >


                <?php if (!$products): ?>

                    <tr>

                        <td
                            colspan="6"
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
                                        flex
                                        items-center
                                        justify-center
                                        text-slate-400
                                        mb-3
                                    "
                                >

                                    <i
                                        data-lucide="package-open"
                                        class="w-6 h-6"
                                    ></i>

                                </span>


                                <h4
                                    class="
                                        font-bold
                                        text-slate-700
                                    "
                                >
                                    No products found
                                </h4>


                                <p
                                    class="
                                        text-xs
                                        text-slate-400
                                        mt-1
                                    "
                                >

                                    <?php if (
                                        $search !== '' ||
                                        $statusFilter !== '' ||
                                        $categoryFilter > 0
                                    ): ?>

                                        Try changing your
                                        search or filters.

                                    <?php else: ?>

                                        Add your first product
                                        to Green Harvest.

                                    <?php endif; ?>

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $products as
                        $product
                    ): ?>

                        <?php

                        $productId =
                            (int) $product['id'];


                        $productStatus =
                            strtolower(
                                trim(
                                    (string) (
                                        $product['status']
                                        ?? 'inactive'
                                    )
                                )
                            );


                        $stockQty =
                            max(
                                0,
                                (int) (
                                    $product['stock_quantity']
                                    ?? 0
                                )
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
                                        min-w-[230px]
                                    "
                                >


                                    <img
                                        src="<?= e(
                                            productImageUrl(
                                                $product['image']
                                                ?? null
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $product['name']
                                        ) ?>"
                                        class="
                                            w-12
                                            h-12
                                            object-cover
                                            rounded-xl
                                            border
                                            border-slate-100
                                            bg-slate-50
                                            flex-shrink-0
                                        "
                                        loading="lazy"
                                    >


                                    <div class="min-w-0">

                                        <a
                                            href="<?= url(
                                                'admin/edit-product.php?id=' .
                                                $productId
                                            ) ?>"
                                            class="
                                                font-bold
                                                text-slate-900
                                                hover:text-brand-700
                                                block
                                                truncate
                                            "
                                        >

                                            <?= e(
                                                $product['name']
                                            ) ?>

                                        </a>


                                        <div
                                            class="
                                                flex
                                                flex-wrap
                                                items-center
                                                gap-1.5
                                                mt-1.5
                                            "
                                        >


                                            <?php if (
                                                (bool)
                                                $product['is_organic']
                                            ): ?>

                                                <span
                                                    class="
                                                        px-1.5
                                                        py-0.5
                                                        text-[10px]
                                                        font-bold
                                                        bg-emerald-50
                                                        text-emerald-700
                                                        border
                                                        border-emerald-200
                                                        rounded
                                                    "
                                                >
                                                    Organic
                                                </span>

                                            <?php endif; ?>


                                            <?php if (
                                                (bool)
                                                $product['is_featured']
                                            ): ?>

                                                <span
                                                    class="
                                                        px-1.5
                                                        py-0.5
                                                        text-[10px]
                                                        font-bold
                                                        bg-amber-50
                                                        text-amber-700
                                                        border
                                                        border-amber-200
                                                        rounded
                                                    "
                                                >
                                                    Featured
                                                </span>

                                            <?php endif; ?>


                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- Category -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    text-slate-600
                                    font-medium
                                    whitespace-nowrap
                                "
                            >

                                <?= e(
                                    $product['category_name']
                                    ?: 'Unassigned'
                                ) ?>

                            </td>


                            <!-- Price -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    whitespace-nowrap
                                "
                            >

                                <span
                                    class="
                                        font-semibold
                                        text-slate-800
                                    "
                                >

                                    <?= money(
                                        $product['price']
                                    ) ?>

                                </span>


                                <span
                                    class="
                                        text-xs
                                        text-slate-400
                                    "
                                >
                                    /
                                    <?= e(
                                        $product['unit']
                                        ?: 'item'
                                    ) ?>
                                </span>

                            </td>


                            <!-- Stock -->

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
                                        px-2.5
                                        py-1
                                        rounded-full
                                        text-xs
                                        font-bold
                                        border
                                        <?= e(
                                            $stockClass(
                                                $stockQty
                                            )
                                        ) ?>
                                    "
                                >

                                    <?= $stockQty ?>

                                </span>

                            </td>


                            <!-- Status -->

                            <td
                                class="
                                    py-4
                                    px-6
                                "
                            >

                                <?php if (
                                    $productStatus ===
                                    'active'
                                ): ?>

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            text-xs
                                            font-bold
                                            text-emerald-600
                                        "
                                    >

                                        <span
                                            class="
                                                w-1.5
                                                h-1.5
                                                rounded-full
                                                bg-emerald-500
                                            "
                                        ></span>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            text-xs
                                            font-bold
                                            text-slate-400
                                        "
                                    >

                                        <span
                                            class="
                                                w-1.5
                                                h-1.5
                                                rounded-full
                                                bg-slate-400
                                            "
                                        ></span>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Actions -->

                            <td
                                class="
                                    py-4
                                    px-6
                                    text-right
                                    whitespace-nowrap
                                "
                            >

                                <div
                                    class="
                                        inline-flex
                                        items-center
                                        gap-2
                                    "
                                >


                                    <?php if (
                                        $productStatus ===
                                        'active'
                                    ): ?>

                                        <a
                                            href="<?= url(
                                                'product.php?id=' .
                                                $productId
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener"
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                w-8
                                                h-8
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                text-slate-500
                                                hover:text-brand-700
                                                hover:bg-brand-50
                                                hover:border-brand-200
                                            "
                                            title="View product"
                                        >

                                            <i
                                                data-lucide="eye"
                                                class="w-4 h-4"
                                            ></i>

                                        </a>

                                    <?php endif; ?>


                                    <a
                                        href="<?= url(
                                            'admin/edit-product.php?id=' .
                                            $productId
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
                                            data-lucide="pencil"
                                            class="w-3.5 h-3.5"
                                        ></i>

                                        Edit

                                    </a>


                                    <form
                                        method="post"
                                        action="<?= url(
                                            'admin/products.php'
                                        ) ?>"
                                        class="inline-block"
                                    >

                                        <?= csrfField() ?>


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >


                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $productId ?>"
                                        >


                                        <button
                                            type="submit"
                                            data-confirm="Are you sure you want to remove this product? Products with order history will be marked inactive instead of permanently deleted."
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                px-3
                                                py-1.5
                                                rounded-lg
                                                bg-rose-50
                                                hover:bg-rose-100
                                                text-rose-600
                                                font-semibold
                                                text-xs
                                            "
                                        >

                                            <i
                                                data-lucide="trash-2"
                                                class="w-3.5 h-3.5"
                                            ></i>

                                            Delete

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
        aria-label="Product pagination"
    >


        <?php if ($page > 1): ?>

            <a
                href="<?= $productPageUrl(
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

        $startPage = max(
            1,
            $page - 2
        );


        $endPage = min(
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
                    href="<?= $productPageUrl(
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
            $page <
            $totalPages
        ): ?>

            <a
                href="<?= $productPageUrl(
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