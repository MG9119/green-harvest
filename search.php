<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - PRODUCT SEARCH
 * =========================================================
 *
 * Responsibilities:
 * - Search active products
 * - Search product names, descriptions and categories
 * - Paginate search results
 * - Display stock information
 * - Add products to cart
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Search Settings
|--------------------------------------------------------------------------
*/

$itemsPerPage = 12;


/*
|--------------------------------------------------------------------------
| Search Query
|--------------------------------------------------------------------------
*/

$searchQuery = trim(
    (string) ($_GET['q'] ?? '')
);


/*
 * Prevent excessively large search requests.
 */
if (strlen($searchQuery) > 100) {
    $searchQuery = substr(
        $searchQuery,
        0,
        100
    );
}


$searchLength = strlen($searchQuery);

$searchPerformed =
    $searchLength >= 2;


/*
|--------------------------------------------------------------------------
| Page Number
|--------------------------------------------------------------------------
*/

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
| Search Results
|--------------------------------------------------------------------------
*/

$products = [];

$totalProducts = 0;

$totalPages = 1;

$offset = 0;

$searchLoadError = false;


/*
|--------------------------------------------------------------------------
| Perform Search
|--------------------------------------------------------------------------
*/

if ($searchPerformed) {

    try {

        $searchTerm =
            '%' . $searchQuery . '%';


        /*
         * Count matching products.
         *
         * Search:
         * - product name
         * - description
         * - category name
         */
        $countStmt = $pdo->prepare(
            '
            SELECT COUNT(*)

            FROM products p

            LEFT JOIN categories c
                ON c.id = p.category_id

            WHERE p.status = :status

              AND (
                    p.name LIKE :search_name

                    OR p.description LIKE :search_description

                    OR c.name LIKE :search_category
                  )
            '
        );


        $countStmt->execute([
            ':status'             => 'active',
            ':search_name'        => $searchTerm,
            ':search_description' => $searchTerm,
            ':search_category'    => $searchTerm,
        ]);


        $totalProducts =
            (int) $countStmt->fetchColumn();


        /*
         * Pagination.
         */
        $totalPages = max(
            1,
            (int) ceil(
                $totalProducts /
                $itemsPerPage
            )
        );


        /*
         * Correct invalid page numbers BEFORE
         * calculating offset.
         */
        if ($page > $totalPages) {
            $page = $totalPages;
        }


        $offset =
            ($page - 1) *
            $itemsPerPage;


        /*
         * Load matching products.
         */
        $stmt = $pdo->prepare(
            '
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

            WHERE p.status = :status

              AND (
                    p.name LIKE :search_name

                    OR p.description LIKE :search_description

                    OR c.name LIKE :search_category
                  )

            ORDER BY
                p.is_featured DESC,
                p.created_at DESC

            LIMIT :limit
            OFFSET :offset
            '
        );


        $stmt->bindValue(
            ':status',
            'active',
            PDO::PARAM_STR
        );


        $stmt->bindValue(
            ':search_name',
            $searchTerm,
            PDO::PARAM_STR
        );


        $stmt->bindValue(
            ':search_description',
            $searchTerm,
            PDO::PARAM_STR
        );


        $stmt->bindValue(
            ':search_category',
            $searchTerm,
            PDO::PARAM_STR
        );


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
            'Green Harvest search error: ' .
            $e->getMessage()
        );

        $searchLoadError = true;
    }
}


/*
|--------------------------------------------------------------------------
| Showing Range
|--------------------------------------------------------------------------
*/

if ($totalProducts > 0) {

    $showingFrom =
        $offset + 1;

    $showingTo =
        min(
            $offset + $itemsPerPage,
            $totalProducts
        );

} else {

    $showingFrom = 0;
    $showingTo = 0;
}


/*
|--------------------------------------------------------------------------
| Search Pagination URL
|--------------------------------------------------------------------------
*/

$searchPageUrl = static function (
    int $pageNumber
) use (
    $searchQuery
): string {

    return url(
        'search.php?' .
        http_build_query([
            'q'    => $searchQuery,
            'page' => $pageNumber,
        ])
    );
};


/*
|--------------------------------------------------------------------------
| Add-To-Cart Redirect
|--------------------------------------------------------------------------
*/

$currentSearchRedirect =
    'search.php?' .
    http_build_query([
        'q'    => $searchQuery,
        'page' => $page,
    ]);


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = $searchPerformed
    ? 'Search: ' . $searchQuery
    : 'Search Products';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST SEARCH
========================================================= */

.search-hero-form {

    display: flex;

    width: 100%;

    max-width: 650px;

    margin-top: 26px;

    overflow: hidden;

    border-radius: 14px;

    background: #ffffff;

    box-shadow:
        0 12px 35px
        rgba(0, 0, 0, .12);

}


.search-hero-input {

    flex: 1;

    min-width: 0;

    height: 56px;

    padding: 0 18px;

    border: 0;

    outline: 0;

    color:
        var(--gh-dark);

    font-size: .94rem;

}


.search-hero-input:focus {
    box-shadow: none;
}


.search-hero-button {

    min-width: 125px;

    border: 0;

    background:
        var(--gh-green-600);

    color: #ffffff;

    font-size: .88rem;

    font-weight: 800;

    cursor: pointer;

    transition: .2s ease;

}


.search-hero-button:hover {

    background:
        var(--gh-green-700);

}


/*
|--------------------------------------------------------------------------
| Search Section
|--------------------------------------------------------------------------
*/

.search-section {

    padding:
        65px 20px 80px;

}


.search-results-header {

    display: flex;

    align-items: end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 30px;

}


.search-results-title {

    margin-bottom: 6px;

}


.search-results-info {

    margin: 0;

    color:
        var(--gh-muted);

    font-size: .88rem;

}


/*
|--------------------------------------------------------------------------
| Product Card
|--------------------------------------------------------------------------
*/

.search-product-card {

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    background: #ffffff;

    border:
        1px solid
        var(--gh-border);

    border-radius: 19px;

    transition:
        transform .22s ease,
        box-shadow .22s ease;

}


.search-product-card:hover {

    transform:
        translateY(-5px);

    box-shadow:
        var(--gh-shadow);

}


.search-product-image-wrap {

    position: relative;

    overflow: hidden;

    aspect-ratio: 1 / 1;

    background:
        var(--gh-green-50);

}


.search-product-image {

    width: 100%;

    height: 100%;

    object-fit: cover;

    transition:
        transform .4s ease;

}


.search-product-card:hover
.search-product-image {

    transform:
        scale(1.04);

}


.search-product-badges {

    position: absolute;

    top: 12px;

    left: 12px;

    display: flex;

    flex-wrap: wrap;

    gap: 6px;

}


.search-product-badge {

    padding:
        6px 9px;

    border-radius:
        999px;

    background:
        rgba(255, 255, 255, .94);

    color:
        var(--gh-green-800);

    font-size: .66rem;

    font-weight: 800;

}


.search-product-content {

    flex: 1;

    display: flex;

    flex-direction: column;

    padding: 19px;

}


.search-product-category {

    margin-bottom: 5px;

    color:
        var(--gh-green-700);

    font-size: .67rem;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;

}


.search-product-name {

    margin-bottom: 7px;

    font-size: 1.13rem;

}


.search-product-name a {

    color:
        var(--gh-dark);

}


.search-product-name a:hover {

    color:
        var(--gh-green-700);

}


.search-product-description {

    flex: 1;

    margin-bottom: 15px;

    color:
        var(--gh-muted);

    font-size: .82rem;

    line-height: 1.65;

}


.search-product-stock {

    margin-bottom: 14px;

    font-size: .75rem;

    font-weight: 800;

}


.search-product-stock.in {
    color: var(--gh-green-700);
}


.search-product-stock.low {
    color: var(--gh-warning);
}


.search-product-stock.out {
    color: var(--gh-danger);
}


.search-product-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding-top: 15px;

    border-top:
        1px solid
        var(--gh-border);

}


.search-product-price {

    color:
        var(--gh-dark);

    font-weight: 800;

}


.search-product-unit {

    display: block;

    color:
        var(--gh-muted);

    font-size: .7rem;

}


.search-product-actions {

    display: flex;

    gap: 6px;

}


.search-action {

    width: 41px;

    height: 41px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 0;

    border:
        1px solid
        var(--gh-border);

    border-radius: 11px;

    background: #ffffff;

    color:
        var(--gh-green-800);

    transition: .2s ease;

}


.search-action:hover {

    color: #ffffff;

    background:
        var(--gh-green-700);

    border-color:
        var(--gh-green-700);

}


.search-action.add {

    color: #ffffff;

    background:
        var(--gh-dark);

    border-color:
        var(--gh-dark);

}


.search-action.add:hover {

    background:
        var(--gh-green-700);

    border-color:
        var(--gh-green-700);

}


.search-action:disabled {

    color: #ffffff;

    background: #cbd5e1;

    border-color: #cbd5e1;

    cursor: not-allowed;

}


/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

.search-empty {

    max-width: 760px;

    margin: 0 auto;

    padding:
        70px 30px;

    text-align: center;

    background: #ffffff;

    border:
        1px dashed
        #cad8cd;

    border-radius: 20px;

}


.search-empty-icon {

    width: 75px;

    height: 75px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 20px;

    border-radius: 24px;

    background:
        var(--gh-green-50);

    color:
        var(--gh-green-700);

    font-size: 2rem;

}


.search-empty h2 {

    margin-bottom: 10px;

}


.search-empty p {

    max-width: 520px;

    margin:
        0 auto 24px;

    color:
        var(--gh-muted);

}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

.search-pagination {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 7px;

    margin-top: 40px;

}


.search-page {

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


.search-page:hover,
.search-page.active {

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

@media (max-width: 575.98px) {

    .search-hero-form {

        flex-direction: column;

        overflow: visible;

        background: transparent;

        box-shadow: none;

    }


    .search-hero-input {

        flex: none;

        width: 100%;

        border-radius: 12px;

        margin-bottom: 8px;

    }


    .search-hero-button {

        min-height: 52px;

        border-radius: 12px;

    }


    .search-results-header {

        flex-direction: column;

        align-items: flex-start;

    }

}

</style>


<!-- =========================================================
     SEARCH HERO
========================================================= -->

<section class="page-hero">

    <div class="container">


        <p class="section-eyebrow">
            Product Search
        </p>


        <h1>
            Find something fresh.
        </h1>


        <form
            method="get"
            action="<?= url('search.php') ?>"
            class="search-hero-form"
            role="search"
        >

            <input
                type="search"
                name="q"
                value="<?= e($searchQuery) ?>"
                class="search-hero-input"
                placeholder="Search vegetables, fruits, grains..."
                minlength="2"
                maxlength="100"
                autocomplete="off"
                aria-label="Search Green Harvest products"
                required
            >


            <button
                type="submit"
                class="search-hero-button"
            >

                <i class="bi bi-search me-1"></i>

                Search

            </button>

        </form>


    </div>

</section>



<!-- =========================================================
     SEARCH RESULTS
========================================================= -->

<section class="search-section">

    <div class="container">


        <?php displayFlash(); ?>


        <?php if ($searchLoadError): ?>

            <div class="alert alert-danger">

                We could not complete your search.
                Please try again.

            </div>

        <?php endif; ?>



        <?php if (
            $searchQuery !== '' &&
            !$searchPerformed
        ): ?>


            <!-- Search too short -->

            <div class="search-empty">


                <span class="search-empty-icon">

                    <i class="bi bi-search"></i>

                </span>


                <h2>
                    Search term is too short.
                </h2>


                <p>

                    Please enter at least two characters
                    to search Green Harvest products.

                </p>


                <a
                    href="<?= url('shop.php') ?>"
                    class="btn btn-green"
                >
                    Browse All Products
                </a>


            </div>



        <?php elseif (!$searchPerformed): ?>


            <!-- No Search Yet -->

            <div class="search-empty">


                <span class="search-empty-icon">

                    <i class="bi bi-search"></i>

                </span>


                <h2>
                    What are you looking for?
                </h2>


                <p>

                    Search Green Harvest by product name,
                    description or category.

                </p>


                <a
                    href="<?= url('shop.php') ?>"
                    class="btn btn-green"
                >

                    <i class="bi bi-basket me-1"></i>

                    Browse the Shop

                </a>


            </div>



        <?php elseif ($products): ?>


            <!-- =================================================
                 Results Header
            ================================================== -->

            <div class="search-results-header">


                <div>

                    <p class="section-eyebrow">
                        Search Results
                    </p>


                    <h2 class="search-results-title">

                        Results for
                        “<?= e($searchQuery) ?>”

                    </h2>


                    <p class="search-results-info">

                        Showing

                        <?= $showingFrom ?>

                        –

                        <?= $showingTo ?>

                        of

                        <?= $totalProducts ?>

                        product<?= $totalProducts === 1 ? '' : 's' ?>

                    </p>

                </div>


                <a
                    href="<?= url('shop.php') ?>"
                    class="btn btn-outline-green"
                >

                    Browse Full Shop

                </a>


            </div>



            <!-- =================================================
                 Product Grid
            ================================================== -->

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
                            col-lg-4
                            col-xl-3
                        "
                    >


                        <article class="search-product-card">


                            <!-- Product Image -->

                            <a
                                href="<?= url(
                                    'product.php?id=' .
                                    $productId
                                ) ?>"
                                class="search-product-image-wrap"
                            >

                                <img
                                    src="<?= e(
                                        productImageUrl(
                                            $product['image']
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $product['name']
                                    ) ?>"
                                    class="search-product-image"
                                    loading="lazy"
                                >


                                <div class="search-product-badges">


                                    <?php if (
                                        (bool) $product['is_organic']
                                    ): ?>

                                        <span class="search-product-badge">

                                            <i class="bi bi-leaf-fill me-1"></i>

                                            Organic

                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        (bool) $product['is_featured']
                                    ): ?>

                                        <span class="search-product-badge">
                                            Featured
                                        </span>

                                    <?php endif; ?>


                                </div>

                            </a>



                            <!-- Product Information -->

                            <div class="search-product-content">


                                <div class="search-product-category">

                                    <?= e($categoryName) ?>

                                </div>


                                <h2 class="search-product-name">

                                    <a
                                        href="<?= url(
                                            'product.php?id=' .
                                            $productId
                                        ) ?>"
                                    >

                                        <?= e(
                                            $product['name']
                                        ) ?>

                                    </a>

                                </h2>


                                <p class="search-product-description">

                                    <?= e(
                                        $product['description']
                                        ?: 'Fresh, carefully selected produce from Green Harvest.'
                                    ) ?>

                                </p>



                                <!-- Stock -->

                                <?php if ($stock <= 0): ?>

                                    <div class="search-product-stock out">
                                        Out of stock
                                    </div>

                                <?php elseif ($stock <= 10): ?>

                                    <div class="search-product-stock low">

                                        Only <?= $stock ?> left

                                    </div>

                                <?php else: ?>

                                    <div class="search-product-stock in">
                                        In stock
                                    </div>

                                <?php endif; ?>



                                <!-- Footer -->

                                <div class="search-product-footer">


                                    <!-- Price -->

                                    <div>

                                        <span class="search-product-price">

                                            <?= money(
                                                $product['price']
                                            ) ?>

                                        </span>


                                        <span class="search-product-unit">

                                            per

                                            <?= e(
                                                $product['unit']
                                                ?: 'item'
                                            ) ?>

                                        </span>

                                    </div>



                                    <!-- Actions -->

                                    <div class="search-product-actions">


                                        <a
                                            href="<?= url(
                                                'product.php?id=' .
                                                $productId
                                            ) ?>"
                                            class="search-action"
                                            title="View product"
                                            aria-label="View <?= e($product['name']) ?>"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>



                                        <?php if ($stock > 0): ?>


                                            <form
                                                method="post"
                                                action="<?= url('add-to-cart.php') ?>"
                                                class="m-0"
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
                                                        $currentSearchRedirect
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="search-action add"
                                                    title="Add to cart"
                                                    aria-label="Add <?= e($product['name']) ?> to cart"
                                                >

                                                    <i class="bi bi-plus-lg"></i>

                                                </button>

                                            </form>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                class="search-action add"
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



            <!-- =================================================
                 Pagination
            ================================================== -->

            <?php if ($totalPages > 1): ?>


                <nav
                    class="search-pagination"
                    aria-label="Search results pagination"
                >


                    <?php if ($page > 1): ?>

                        <a
                            href="<?= $searchPageUrl(
                                $page - 1
                            ) ?>"
                            class="search-page"
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
                                class="search-page active"
                                aria-current="page"
                            >
                                <?= $pageNumber ?>
                            </span>

                        <?php else: ?>

                            <a
                                href="<?= $searchPageUrl(
                                    $pageNumber
                                ) ?>"
                                class="search-page"
                            >
                                <?= $pageNumber ?>
                            </a>

                        <?php endif; ?>


                    <?php endfor; ?>


                    <?php if (
                        $page < $totalPages
                    ): ?>

                        <a
                            href="<?= $searchPageUrl(
                                $page + 1
                            ) ?>"
                            class="search-page"
                            aria-label="Next page"
                        >

                            <i class="bi bi-chevron-right"></i>

                        </a>

                    <?php endif; ?>


                </nav>


            <?php endif; ?>



        <?php else: ?>


            <!-- =================================================
                 No Results
            ================================================== -->

            <div class="search-empty">


                <span class="search-empty-icon">

                    <i class="bi bi-search"></i>

                </span>


                <h2>
                    No products found.
                </h2>


                <p>

                    We couldn't find any active products
                    matching “<?= e($searchQuery) ?>”.

                    Try another search term or browse the
                    complete Green Harvest shop.

                </p>


                <div
                    class="
                        d-flex
                        flex-wrap
                        justify-content-center
                        gap-2
                    "
                >

                    <a
                        href="<?= url('shop.php') ?>"
                        class="btn btn-green"
                    >
                        Browse All Products
                    </a>


                    <a
                        href="<?= url('category.php') ?>"
                        class="btn btn-outline-green"
                    >
                        Browse Categories
                    </a>

                </div>


            </div>


        <?php endif; ?>


    </div>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>