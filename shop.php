<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - SHOP
 * =========================================================
 *
 * Responsibilities:
 * - Display active products
 * - Filter by category
 * - Sort products
 * - Paginate products
 * - Add products to cart
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Shop Settings
|--------------------------------------------------------------------------
*/

$itemsPerPage = 12;

$validSorts = [
    'featured',
    'price-low',
    'price-high',
    'newest',
];


/*
|--------------------------------------------------------------------------
| Request Filters
|--------------------------------------------------------------------------
*/

$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);

if (
    $categoryId === false ||
    $categoryId === null ||
    $categoryId <= 0
) {
    $categoryId = null;
}


$sortBy = trim(
    (string) ($_GET['sort'] ?? 'featured')
);

if (!in_array($sortBy, $validSorts, true)) {
    $sortBy = 'featured';
}


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

    $categories = getCategories($pdo);

} catch (PDOException $e) {

    error_log(
        'Green Harvest shop categories error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| Validate Selected Category
|--------------------------------------------------------------------------
*/

$selectedCategoryName = null;

if ($categoryId !== null) {

    $categoryExists = false;

    foreach ($categories as $category) {

        if (
            (int) $category['id'] ===
            (int) $categoryId
        ) {

            $categoryExists = true;

            $selectedCategoryName =
                (string) $category['name'];

            break;
        }
    }


    /*
     * Ignore invalid category IDs.
     */
    if (!$categoryExists) {
        $categoryId = null;
    }
}


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$orderClause = match ($sortBy) {

    'price-low' =>
        'p.price ASC, p.name ASC',

    'price-high' =>
        'p.price DESC, p.name ASC',

    'newest' =>
        'p.created_at DESC',

    default =>
        'p.is_featured DESC, p.created_at DESC',
};


/*
|--------------------------------------------------------------------------
| Build Product Query
|--------------------------------------------------------------------------
*/

$where = [
    'p.status = :status',
];

$queryParameters = [
    ':status' => 'active',
];


if ($categoryId !== null) {

    $where[] =
        'p.category_id = :category_id';

    $queryParameters[':category_id'] =
        $categoryId;
}


$whereSql =
    implode(' AND ', $where);


/*
|--------------------------------------------------------------------------
| Product Count
|--------------------------------------------------------------------------
*/

$totalProducts = 0;

$products = [];

$shopLoadError = false;


try {

    $countSql = '
        SELECT COUNT(*)

        FROM products p

        WHERE ' . $whereSql;


    $countStmt =
        $pdo->prepare($countSql);


    foreach (
        $queryParameters as
        $parameter => $value
    ) {

        $countStmt->bindValue(
            $parameter,
            $value
        );
    }


    $countStmt->execute();


    $totalProducts =
        (int) $countStmt->fetchColumn();


    /*
     * Calculate pagination.
     */
    $totalPages = max(
        1,
        (int) ceil(
            $totalProducts /
            $itemsPerPage
        )
    );


    /*
     * Prevent invalid page numbers.
     */
    if ($page > $totalPages) {
        $page = $totalPages;
    }


    /*
     * IMPORTANT:
     * Offset is calculated AFTER page validation.
     */
    $offset =
        ($page - 1) *
        $itemsPerPage;


    /*
     * Load products.
     */
    $productSql = '
        SELECT

            p.id,
            p.name,
            p.description,
            p.price,
            p.unit,
            p.stock_quantity,
            p.image,
            p.is_organic,
            p.is_featured,
            p.category_id,
            p.created_at,

            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        WHERE ' . $whereSql . '

        ORDER BY ' . $orderClause . '

        LIMIT :limit
        OFFSET :offset
    ';


    $stmt =
        $pdo->prepare($productSql);


    foreach (
        $queryParameters as
        $parameter => $value
    ) {

        $stmt->bindValue(
            $parameter,
            $value
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


    $products =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'Green Harvest shop product error: ' .
        $e->getMessage()
    );

    $shopLoadError = true;

    $totalPages = 1;
    $offset = 0;
}


/*
|--------------------------------------------------------------------------
| Product Range
|--------------------------------------------------------------------------
*/

if ($totalProducts > 0) {

    $showingFrom =
        $offset + 1;

    $showingTo =
        min(
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
| URL Builder
|--------------------------------------------------------------------------
|
| Keeps category and sorting options when changing pages.
|
*/

$shopUrl = static function (
    array $changes = []
) use (
    $categoryId,
    $sortBy
): string {

    $parameters = [
        'sort' => $sortBy,
    ];


    if ($categoryId !== null) {

        $parameters['category'] =
            $categoryId;
    }


    foreach (
        $changes as
        $key => $value
    ) {

        if ($value === null) {

            unset(
                $parameters[$key]
            );

        } else {

            $parameters[$key] =
                $value;
        }
    }


    return url(
        'shop.php?' .
        http_build_query($parameters)
    );
};


/*
|--------------------------------------------------------------------------
| Current Shop Redirect
|--------------------------------------------------------------------------
|
| Used after adding a product to the cart.
|
*/

$currentShopParameters = [
    'sort' => $sortBy,
    'page' => $page,
];

if ($categoryId !== null) {

    $currentShopParameters['category'] =
        $categoryId;
}

$currentShopRedirect =
    'shop.php?' .
    http_build_query(
        $currentShopParameters
    );


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Shop';

require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST SHOP
========================================================= */

.shop-section {
    padding: 65px 20px 80px;
}

.shop-layout {
    display: grid;
    grid-template-columns: 245px minmax(0, 1fr);
    gap: 32px;
    align-items: start;
}


/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

.shop-sidebar {
    position: sticky;
    top: 100px;
    padding: 24px;
}

.shop-filter-title {
    margin-bottom: 16px;
    font-size: 1.05rem;
}

.shop-category-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.shop-category-list li {
    margin-bottom: 6px;
}

.shop-category-link {

    display: flex;
    align-items: center;
    justify-content: space-between;

    min-height: 42px;

    padding: 9px 11px;

    border-radius: 10px;

    color: #526057;

    font-size: .85rem;
    font-weight: 700;

    transition: .2s ease;
}

.shop-category-link:hover {
    color: var(--gh-green-800);
    background: var(--gh-green-50);
}

.shop-category-link.active {
    color: #ffffff;
    background: var(--gh-green-700);
}


/*
|--------------------------------------------------------------------------
| Toolbar
|--------------------------------------------------------------------------
*/

.shop-toolbar {

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;

    padding: 18px 20px;

    background: #ffffff;

    border: 1px solid var(--gh-border);

    border-radius: 14px;
}

.shop-result-count {

    color: var(--gh-muted);

    font-size: .85rem;
    font-weight: 700;
}

.shop-sort-form {

    display: flex;

    align-items: center;

    gap: 9px;
}

.shop-sort-form label {

    color: var(--gh-muted);

    font-size: .78rem;

    font-weight: 700;

    white-space: nowrap;
}

.shop-sort-form .form-select {

    min-height: 40px;

    min-width: 175px;

    padding-top: 6px;
    padding-bottom: 6px;
}


/*
|--------------------------------------------------------------------------
| Product Card
|--------------------------------------------------------------------------
*/

.shop-product-card {

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    background: #ffffff;

    border: 1px solid rgba(20, 83, 45, .08);

    border-radius: 20px;

    box-shadow:
        0 6px 25px
        rgba(20, 83, 45, .04);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

.shop-product-card:hover {

    transform:
        translateY(-5px);

    box-shadow:
        0 20px 45px
        rgba(20, 83, 45, .10);
}

.shop-product-image-wrap {

    position: relative;

    aspect-ratio: 1 / 1;

    overflow: hidden;

    background:
        var(--gh-green-50);
}

.shop-product-image {

    width: 100%;
    height: 100%;

    object-fit: cover;

    transition:
        transform .4s ease;
}

.shop-product-card:hover
.shop-product-image {

    transform:
        scale(1.04);
}

.shop-product-badges {

    position: absolute;

    top: 12px;
    left: 12px;

    display: flex;

    flex-wrap: wrap;

    gap: 6px;
}

.shop-badge {

    padding:
        6px 9px;

    border-radius:
        999px;

    background:
        rgba(255, 255, 255, .92);

    color:
        var(--gh-green-800);

    font-size: .66rem;

    font-weight: 800;

    box-shadow:
        0 4px 12px
        rgba(0, 0, 0, .07);
}

.shop-product-content {

    flex: 1;

    display: flex;

    flex-direction: column;

    padding: 19px;
}

.shop-product-category {

    margin-bottom: 5px;

    color:
        var(--gh-green-700);

    font-size: .68rem;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}

.shop-product-name {

    margin-bottom: 7px;

    font-size: 1.15rem;
}

.shop-product-name a,
.shop-product-name a:link,
.shop-product-name a:visited {
    color: #166534 !important;
    font-weight: 800;
    text-decoration: none !important;
}

.shop-product-name a:hover,
.shop-product-name a:focus {
    color: #15803d !important;
}

.shop-product-description {

    flex: 1;

    margin-bottom: 15px;

    color: var(--gh-muted);

    font-size: .82rem;

    line-height: 1.6;
}

.shop-product-stock {

    margin-bottom: 15px;

    font-size: .75rem;

    font-weight: 700;
}

.shop-product-stock.in {
    color: var(--gh-green-700);
}

.shop-product-stock.low {
    color: var(--gh-warning);
}

.shop-product-stock.out {
    color: var(--gh-danger);
}

.shop-product-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding-top: 15px;

    border-top:
        1px solid
        var(--gh-border);
}

.shop-price {

    color: var(--gh-dark);

    font-size: 1.02rem;

    font-weight: 800;
}

.shop-unit {

    display: block;

    color: var(--gh-muted);

    font-size: .7rem;
}

.shop-actions {

    display: flex;

    gap: 7px;
}

.shop-icon-button {

    width: 41px;

    height: 41px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 0;

    border-radius: 11px;

    border:
        1px solid
        var(--gh-border);

    background: #ffffff;

    color:
        var(--gh-green-800);

    transition: .2s ease;
}

.shop-icon-button:hover {

    color: #ffffff;

    background:
        var(--gh-green-700);

    border-color:
        var(--gh-green-700);
}

.shop-icon-button.primary {

    border-color:
        var(--gh-dark);

    background:
        var(--gh-dark);

    color: #ffffff;
}

.shop-icon-button.primary:hover {

    border-color:
        var(--gh-green-700);

    background:
        var(--gh-green-700);
}

.shop-icon-button:disabled {

    color: #ffffff;

    border-color: #cbd5e1;

    background: #cbd5e1;

    cursor: not-allowed;
}


/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

.shop-empty {

    padding:
        70px 25px;

    text-align: center;

    background: #ffffff;

    border:
        1px dashed
        #cbd8ce;

    border-radius:
        18px;
}

.shop-empty-icon {

    width: 70px;
    height: 70px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 18px;

    border-radius: 22px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

    font-size: 1.8rem;
}

.shop-empty p {
    color: var(--gh-muted);
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

.shop-pagination {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 7px;

    margin-top: 40px;
}

.shop-page-link {

    min-width: 41px;

    min-height: 41px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    padding:
        8px 12px;

    border:
        1px solid
        var(--gh-border);

    border-radius: 10px;

    background: #ffffff;

    color:
        var(--gh-green-800);

    font-size: .82rem;

    font-weight: 700;

    transition: .2s ease;
}

.shop-page-link:hover {

    color: #ffffff;

    background:
        var(--gh-green-700);

    border-color:
        var(--gh-green-700);
}

.shop-page-link.active {

    color: #ffffff;

    background:
        var(--gh-green-700);

    border-color:
        var(--gh-green-700);
}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 991.98px) {

    .shop-layout {
        grid-template-columns: 1fr;
    }

    .shop-sidebar {
        position: static;
    }

    .shop-category-list {

        display: flex;

        flex-wrap: wrap;

        gap: 8px;
    }

    .shop-category-list li {
        margin: 0;
    }

}

@media (max-width: 575.98px) {

    .shop-toolbar {

        flex-direction: column;

        align-items: stretch;
    }

    .shop-sort-form {

        flex-direction: column;

        align-items: stretch;
    }

    .shop-sort-form .form-select {

        width: 100%;

        min-width: 0;
    }

}

</style>



<!-- =========================================================
     SHOP HERO
========================================================= -->

<section class="page-hero">

    <div class="container">

        <p class="section-eyebrow">
            Green Harvest Shop
        </p>

        <h1>

            <?php if ($selectedCategoryName): ?>

                <?= e($selectedCategoryName) ?>

            <?php else: ?>

                Shop fresh produce.

            <?php endif; ?>

        </h1>

    </div>

</section>



<!-- =========================================================
     SHOP CONTENT
========================================================= -->

<section class="shop-section">

    <div class="container">


        <?php displayFlash(); ?>


        <?php if ($shopLoadError): ?>

            <div class="alert alert-danger">

                Some products could not be loaded.
                Please try again shortly.

            </div>

        <?php endif; ?>


        <div class="shop-layout">


            <!-- =================================================
                 CATEGORY SIDEBAR
            ================================================== -->

            <aside>

                <div class="summary-card shop-sidebar">

                    <h2 class="shop-filter-title">
                        Categories
                    </h2>


                    <ul class="shop-category-list">


                        <li>

                            <a
                                href="<?= $shopUrl([
                                    'category' => null,
                                    'page' => 1,
                                ]) ?>"
                                class="shop-category-link <?= $categoryId === null ? 'active' : '' ?>"
                            >

                                <span>
                                    All Products
                                </span>

                            </a>

                        </li>


                        <?php foreach ($categories as $category): ?>

                            <?php

                            $catId =
                                (int) $category['id'];

                            ?>


                            <li>

                                <a
                                    href="<?= $shopUrl([
                                        'category' => $catId,
                                        'page' => 1,
                                    ]) ?>"
                                    class="shop-category-link <?= $categoryId === $catId ? 'active' : '' ?>"
                                >

                                    <span>
                                        <?= e($category['name']) ?>
                                    </span>

                                    <i
                                        class="bi bi-chevron-right"
                                    ></i>

                                </a>

                            </li>

                        <?php endforeach; ?>


                    </ul>

                </div>

            </aside>



            <!-- =================================================
                 PRODUCTS
            ================================================== -->

            <div>


                <!-- =============================================
                     Toolbar
                ============================================== -->

                <div class="shop-toolbar">


                    <div class="shop-result-count">

                        Showing

                        <?= $showingFrom ?>

                        –

                        <?= $showingTo ?>

                        of

                        <?= $totalProducts ?>

                        product<?= $totalProducts === 1 ? '' : 's' ?>

                    </div>



                    <form
                        method="get"
                        action="<?= url('shop.php') ?>"
                        class="shop-sort-form"
                    >


                        <?php if ($categoryId !== null): ?>

                            <input
                                type="hidden"
                                name="category"
                                value="<?= $categoryId ?>"
                            >

                        <?php endif; ?>


                        <label for="sort">
                            Sort By
                        </label>


                        <select
                            id="sort"
                            name="sort"
                            class="form-select"
                            onchange="this.form.submit()"
                        >

                            <option
                                value="featured"
                                <?= $sortBy === 'featured' ? 'selected' : '' ?>
                            >
                                Featured
                            </option>

                            <option
                                value="price-low"
                                <?= $sortBy === 'price-low' ? 'selected' : '' ?>
                            >
                                Price: Low to High
                            </option>

                            <option
                                value="price-high"
                                <?= $sortBy === 'price-high' ? 'selected' : '' ?>
                            >
                                Price: High to Low
                            </option>

                            <option
                                value="newest"
                                <?= $sortBy === 'newest' ? 'selected' : '' ?>
                            >
                                Newest
                            </option>

                        </select>

                    </form>


                </div>



                <?php if ($products): ?>


                    <!-- =========================================
                         Product Grid
                    ========================================== -->

                    <div class="row g-4">


                        <?php foreach ($products as $product): ?>

                            <?php

                            $productId =
                                (int) $product['id'];

                            $stock =
                                (int) $product['stock_quantity'];

                            $categoryName =
                                $product['category_name']
                                ?: 'Fresh Produce';

                            ?>


                            <div
                                class="
                                    col-sm-6
                                    col-xl-4
                                "
                            >


                                <article class="shop-product-card">


                                    <!-- Image -->

                                    <a
                                        href="<?= url(
                                            'product.php?id=' .
                                            $productId
                                        ) ?>"
                                        class="shop-product-image-wrap"
                                    >

                                        <img
                                            src="<?= e(
                                                productImageUrl(
                                                    $product['image']
                                                )
                                            ) ?>"
                                            alt="<?= e($product['name']) ?>"
                                            class="shop-product-image"
                                            loading="lazy"
                                        >


                                        <div class="shop-product-badges">


                                            <?php if (
                                                (bool) $product['is_organic']
                                            ): ?>

                                                <span class="shop-badge">

                                                    <i class="bi bi-leaf-fill me-1"></i>

                                                    Organic

                                                </span>

                                            <?php endif; ?>


                                            <?php if (
                                                (bool) $product['is_featured']
                                            ): ?>

                                                <span class="shop-badge">
                                                    Featured
                                                </span>

                                            <?php endif; ?>


                                        </div>

                                    </a>



                                    <!-- Content -->

                                    <div class="shop-product-content">


                                        <span class="shop-product-category">

                                            <?= e($categoryName) ?>

                                        </span>


                                        <h2 class="shop-product-name">

                                            <a
                                                href="<?= url(
                                                    'product.php?id=' .
                                                    $productId
                                                ) ?>"
                                            >

                                                <?= e($product['name']) ?>

                                            </a>

                                        </h2>


                                        <p class="shop-product-description">

                                            <?= e(
                                                $product['description']
                                                ?: 'Fresh, carefully selected produce from Green Harvest.'
                                            ) ?>

                                        </p>



                                        <!-- Stock -->

                                        <?php if ($stock <= 0): ?>

                                            <div class="shop-product-stock out">
                                                Out of stock
                                            </div>

                                        <?php elseif ($stock <= 10): ?>

                                            <div class="shop-product-stock low">

                                                Only <?= $stock ?> left

                                            </div>

                                        <?php else: ?>

                                            <div class="shop-product-stock in">
                                                In stock
                                            </div>

                                        <?php endif; ?>



                                        <div class="shop-product-footer">


                                            <!-- Price -->

                                            <div>

                                                <span class="shop-price">

                                                    <?= money(
                                                        $product['price']
                                                    ) ?>

                                                </span>

                                                <span class="shop-unit">

                                                    per

                                                    <?= e(
                                                        $product['unit']
                                                        ?: 'item'
                                                    ) ?>

                                                </span>

                                            </div>



                                            <!-- Actions -->

                                            <div class="shop-actions">


                                                <a
                                                    href="<?= url(
                                                        'product.php?id=' .
                                                        $productId
                                                    ) ?>"
                                                    class="shop-icon-button"
                                                    title="View details"
                                                    aria-label="View <?= e($product['name']) ?>"
                                                >

                                                    <i class="bi bi-eye"></i>

                                                </a>



                                                <?php if ($stock > 0): ?>


                                                    <form
                                                        method="post"
                                                        action="<?= url('add-to-cart.php') ?>"
                                                        class="
                                                            m-0
                                                            gh-cart-add-form
                                                        "
                                                        data-cart-add-form
                                                    >

                                                        <?= csrfField() ?>


                                                        <input
                                                            type="hidden"
                                                            name="product_id"
                                                            value="<?= $productId ?>"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="quantity"
                                                            value="1"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="redirect"
                                                            value="<?= e(
                                                                $currentShopRedirect
                                                            ) ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="shop-icon-button primary"
                                                            data-cart-submit
                                                            title="Add to cart"
                                                            aria-label="Add <?= e($product['name']) ?> to cart"
                                                        >

                                                            <i class="bi bi-plus-lg"></i>

                                                        </button>

                                                    </form>


                                                <?php else: ?>


                                                    <button
                                                        type="button"
                                                        class="shop-icon-button primary"
                                                        disabled
                                                        title="Out of stock"
                                                    >

                                                        <i class="bi bi-x-lg"></i>

                                                    </button>


                                                <?php endif; ?>


                                            </div>


                                        </div>


                                    </div>


                                </article>


                            </div>


                        <?php endforeach; ?>


                    </div>



                    <!-- =========================================
                         Pagination
                    ========================================== -->

                    <?php if ($totalPages > 1): ?>


                        <nav
                            class="shop-pagination"
                            aria-label="Product pagination"
                        >


                            <?php if ($page > 1): ?>


                                <a
                                    href="<?= $shopUrl([
                                        'page' => $page - 1,
                                    ]) ?>"
                                    class="shop-page-link"
                                    aria-label="Previous page"
                                >
                                    <i class="bi bi-chevron-left"></i>
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
                                        class="shop-page-link active"
                                        aria-current="page"
                                    >
                                        <?= $pageNumber ?>
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="<?= $shopUrl([
                                            'page' => $pageNumber,
                                        ]) ?>"
                                        class="shop-page-link"
                                    >
                                        <?= $pageNumber ?>
                                    </a>

                                <?php endif; ?>


                            <?php endfor; ?>



                            <?php if (
                                $page < $totalPages
                            ): ?>


                                <a
                                    href="<?= $shopUrl([
                                        'page' => $page + 1,
                                    ]) ?>"
                                    class="shop-page-link"
                                    aria-label="Next page"
                                >
                                    <i class="bi bi-chevron-right"></i>
                                </a>


                            <?php endif; ?>


                        </nav>


                    <?php endif; ?>



                <?php else: ?>


                    <!-- =========================================
                         Empty Products
                    ========================================== -->

                    <div class="shop-empty">

                        <span class="shop-empty-icon">

                            <i class="bi bi-basket"></i>

                        </span>


                        <h2>
                            No products found.
                        </h2>


                        <p>

                            There are currently no active products
                            matching this category.

                        </p>


                        <a
                            href="<?= url('shop.php') ?>"
                            class="btn btn-green"
                        >
                            View All Products
                        </a>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>