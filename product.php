<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - PRODUCT DETAILS
 * =========================================================
 *
 * Responsibilities:
 * - Load one active product
 * - Display category and product information
 * - Show stock availability
 * - Allow secure Add to Cart
 * - Display related products
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Product ID
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (
    $productId === false ||
    $productId === null ||
    $productId <= 0
) {

    setFlash(
        'error',
        'The requested product could not be found.'
    );

    redirectTo('shop.php');
}


/*
|--------------------------------------------------------------------------
| Load Product
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        '
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

        WHERE p.id = ?
          AND p.status = ?

        LIMIT 1
        '
    );


    $stmt->execute([
        $productId,
        'active',
    ]);


    $product =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        setFlash(
            'error',
            'The requested product is unavailable.'
        );

        redirectTo('shop.php');
    }


} catch (PDOException $e) {

    error_log(
        'Green Harvest product loading error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'The product could not be loaded.'
    );

    redirectTo('shop.php');
}


/*
|--------------------------------------------------------------------------
| Related Products
|--------------------------------------------------------------------------
*/

$relatedProducts = [];


try {

    $stmt = $pdo->prepare(
        '
        SELECT
            p.id,
            p.name,
            p.price,
            p.unit,
            p.stock_quantity,
            p.image,
            p.is_organic,
            p.is_featured,
            p.status,
            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        WHERE p.category_id = ?
          AND p.id != ?
          AND p.status = ?

        ORDER BY
            p.is_featured DESC,
            p.created_at DESC

        LIMIT 4
        '
    );


    $stmt->execute([
        (int) $product['category_id'],
        $productId,
        'active',
    ]);


    $relatedProducts =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest related products error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| Product Values
|--------------------------------------------------------------------------
*/

$productName =
    trim(
        (string) $product['name']
    );


$description =
    trim(
        (string) (
            $product['description']
            ?? ''
        )
    );


$stockQuantity =
    max(
        0,
        (int) $product['stock_quantity']
    );


$unit =
    trim(
        (string) (
            $product['unit']
            ?? ''
        )
    );


$categoryName =
    trim(
        (string) (
            $product['category_name']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| Stock Information
|--------------------------------------------------------------------------
*/

if ($stockQuantity <= 0) {

    $stockLabel = 'Out of Stock';

    $stockClass =
        'stock-out';

    $stockIcon =
        'bi-x-circle-fill';

} elseif ($stockQuantity <= 10) {

    $stockLabel =
        'Low Stock';

    $stockClass =
        'stock-low';

    $stockIcon =
        'bi-exclamation-circle-fill';

} else {

    $stockLabel =
        'In Stock';

    $stockClass =
        'stock-in';

    $stockIcon =
        'bi-check-circle-fill';
}


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    $productName;


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST - PRODUCT DETAILS
========================================================= */


/*
|--------------------------------------------------------------------------
| Breadcrumb
|--------------------------------------------------------------------------
*/

.product-breadcrumb {
    padding: 28px 0 0;
}


.product-breadcrumb a {
    color: var(--gh-green-700);
    text-decoration: none;
    font-weight: 600;
}


.product-breadcrumb a:hover {
    color: var(--gh-green-900);
}


.product-breadcrumb span {
    color: var(--gh-muted);
}


/*
|--------------------------------------------------------------------------
| Product Section
|--------------------------------------------------------------------------
*/

.product-section {
    padding: 32px 0 75px;
}


.product-detail-card {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, 1fr);
    gap: 50px;
    padding: 38px;
    background: #ffffff;
    border: 1px solid var(--gh-border);
    border-radius: 24px;
}


/*
|--------------------------------------------------------------------------
| Product Image
|--------------------------------------------------------------------------
*/

.product-image-wrapper {
    position: relative;
    overflow: hidden;
    min-height: 480px;
    border-radius: 20px;
    background: var(--gh-green-50);
}


.product-image {
    width: 100%;
    height: 100%;
    min-height: 480px;
    max-height: 580px;
    object-fit: cover;
}


.product-badges {
    position: absolute;
    top: 18px;
    left: 18px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}


.product-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
}


.product-badge.organic {
    background: #dcfce7;
    color: #166534;
}


.product-badge.featured {
    background: #fef3c7;
    color: #92400e;
}


/*
|--------------------------------------------------------------------------
| Product Information
|--------------------------------------------------------------------------
*/

.product-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}


.product-category {
    margin-bottom: 10px;
    color: var(--gh-green-700);
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .09em;
    text-transform: uppercase;
}


.product-title {
    margin-bottom: 15px;
    color: var(--gh-dark);
    font-size: clamp(
        2rem,
        5vw,
        3.2rem
    );
    line-height: 1.08;
}


.product-price-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}


.product-price {
    color: var(--gh-green-700);
    font-size: 2rem;
    font-weight: 800;
}


.product-unit {
    color: var(--gh-muted);
    font-size: .85rem;
    font-weight: 600;
}


.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: .74rem;
    font-weight: 800;
}


.stock-in {
    background: #dcfce7;
    color: #166534;
}


.stock-low {
    background: #fef3c7;
    color: #92400e;
}


.stock-out {
    background: #fee2e2;
    color: #991b1b;
}


.product-description {
    margin-bottom: 28px;
    color: #536159;
    font-size: .98rem;
    line-height: 1.85;
}


/*
|--------------------------------------------------------------------------
| Product Meta
|--------------------------------------------------------------------------
*/

.product-meta-box {
    margin-bottom: 28px;
    padding: 18px 20px;
    border: 1px solid var(--gh-border);
    border-radius: 15px;
    background: #fafcfb;
}


.product-meta-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 11px 0;
    border-bottom: 1px solid var(--gh-border);
}


.product-meta-row:last-child {
    border-bottom: 0;
}


.product-meta-label {
    color: var(--gh-muted);
    font-size: .82rem;
    font-weight: 600;
}


.product-meta-value {
    color: var(--gh-dark);
    font-size: .82rem;
    font-weight: 800;
    text-align: right;
}


/*
|--------------------------------------------------------------------------
| Add To Cart
|--------------------------------------------------------------------------
*/

.product-cart-form {
    margin-top: 5px;
}


.quantity-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    margin-bottom: 15px;
}


.quantity-field {
    max-width: 130px;
}


.quantity-field label {
    display: block;
    margin-bottom: 7px;
    color: #45564a;
    font-size: .75rem;
    font-weight: 800;
}


.quantity-field input {
    width: 100%;
    min-height: 48px;
    padding: 10px 12px;
    border: 1px solid var(--gh-border);
    border-radius: 11px;
    text-align: center;
}


.stock-note {
    padding-bottom: 12px;
    color: var(--gh-muted);
    font-size: .75rem;
}


.product-actions {
    display: flex;
    gap: 10px;
}


.product-actions .btn {
    min-height: 50px;
}


.out-of-stock-box {
    padding: 16px;
    border: 1px solid #fecaca;
    border-radius: 13px;
    background: #fef2f2;
    color: #991b1b;
    font-size: .84rem;
}


/*
|--------------------------------------------------------------------------
| Related Products
|--------------------------------------------------------------------------
*/

.related-products {
    padding: 70px 0;
    background: #f5faf6;
}


.related-header {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 20px;
    margin-bottom: 28px;
}


.related-header h2 {
    margin: 0;
    color: var(--gh-dark);
    font-size: 1.8rem;
}


.related-card {
    height: 100%;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid var(--gh-border);
    border-radius: 18px;
    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.related-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 15px 35px rgba(
            21,
            80,
            45,
            .10
        );
}


.related-card-image {
    width: 100%;
    height: 210px;
    object-fit: cover;
}


.related-card-body {
    padding: 18px;
}


.related-card-category {
    margin-bottom: 5px;
    color: var(--gh-green-700);
    font-size: .67rem;
    font-weight: 800;
    text-transform: uppercase;
}


.related-card-name {
    margin-bottom: 9px;
    color: var(--gh-dark);
    font-size: 1rem;
    font-weight: 800;
}


.related-card-price {
    margin-bottom: 15px;
    color: var(--gh-green-700);
    font-size: 1rem;
    font-weight: 800;
}


.related-card-actions {
    display: flex;
    gap: 8px;
}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 991.98px) {

    .product-detail-card {
        grid-template-columns: 1fr;
        gap: 30px;
    }


    .product-image-wrapper,
    .product-image {
        min-height: 400px;
    }
}


@media (max-width: 575.98px) {

    .product-detail-card {
        padding: 20px;
        border-radius: 18px;
    }


    .product-image-wrapper,
    .product-image {
        min-height: 300px;
    }


    .product-actions,
    .related-card-actions {
        flex-direction: column;
    }


    .related-header {
        align-items: flex-start;
        flex-direction: column;
    }
}

</style>


<!-- =========================================================
     BREADCRUMB
========================================================= -->

<section class="product-breadcrumb">

    <div class="container">

        <a href="<?= url('shop.php') ?>">
            Shop
        </a>

        <span class="mx-2">
            /
        </span>


        <?php if ($categoryName !== ''): ?>

            <a
                href="<?= url(
                    'category.php?id=' .
                    (int) $product['category_id']
                ) ?>"
            >
                <?= e($categoryName) ?>
            </a>

            <span class="mx-2">
                /
            </span>

        <?php endif; ?>


        <span>
            <?= e($productName) ?>
        </span>

    </div>

</section>


<!-- =========================================================
     PRODUCT DETAILS
========================================================= -->

<section class="product-section">

    <div class="container">


        <?php displayFlash(); ?>


        <div class="product-detail-card">


            <!-- =================================================
                 PRODUCT IMAGE
            ================================================== -->

            <div class="product-image-wrapper">


                <img
                    src="<?= e(
                        productImageUrl(
                            $product['image']
                            ?? null
                        )
                    ) ?>"
                    alt="<?= e($productName) ?>"
                    class="product-image"
                >


                <div class="product-badges">


                    <?php if (
                        (bool) $product['is_organic']
                    ): ?>

                        <span class="product-badge organic">

                            <i class="bi bi-leaf-fill"></i>

                            Organic

                        </span>

                    <?php endif; ?>


                    <?php if (
                        (bool) $product['is_featured']
                    ): ?>

                        <span class="product-badge featured">

                            <i class="bi bi-star-fill"></i>

                            Featured

                        </span>

                    <?php endif; ?>


                </div>


            </div>


            <!-- =================================================
                 PRODUCT INFORMATION
            ================================================== -->

            <div class="product-info">


                <?php if ($categoryName !== ''): ?>

                    <div class="product-category">

                        <?= e($categoryName) ?>

                    </div>

                <?php endif; ?>


                <h1 class="product-title">

                    <?= e($productName) ?>

                </h1>


                <!-- Price & Stock -->

                <div class="product-price-row">


                    <div>

                        <span class="product-price">

                            <?= money(
                                $product['price']
                            ) ?>

                        </span>


                        <?php if ($unit !== ''): ?>

                            <span class="product-unit">

                                / <?= e($unit) ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <span
                        class="
                            stock-badge
                            <?= e($stockClass) ?>
                        "
                    >

                        <i
                            class="
                                bi
                                <?= e($stockIcon) ?>
                            "
                        ></i>

                        <?= e($stockLabel) ?>

                    </span>


                </div>


                <!-- Description -->

                <?php if ($description !== ''): ?>

                    <div class="product-description">

                        <?= nl2br(
                            e($description)
                        ) ?>

                    </div>

                <?php else: ?>

                    <div class="product-description">

                        Fresh Green Harvest product
                        available for ordering.

                    </div>

                <?php endif; ?>


                <!-- Product Information -->

                <div class="product-meta-box">


                    <div class="product-meta-row">

                        <span class="product-meta-label">
                            Product ID
                        </span>

                        <span class="product-meta-value">
                            #<?= $productId ?>
                        </span>

                    </div>


                    <div class="product-meta-row">

                        <span class="product-meta-label">
                            Available Stock
                        </span>

                        <span class="product-meta-value">

                            <?= $stockQuantity ?>

                            <?= e(
                                $unit !== ''
                                    ? $unit
                                    : 'item'
                            ) ?>

                        </span>

                    </div>


                    <?php if ($categoryName !== ''): ?>

                        <div class="product-meta-row">

                            <span class="product-meta-label">
                                Category
                            </span>

                            <span class="product-meta-value">

                                <?= e($categoryName) ?>

                            </span>

                        </div>

                    <?php endif; ?>


                    <div class="product-meta-row">

                        <span class="product-meta-label">
                            Product Type
                        </span>

                        <span class="product-meta-value">

                            <?= (bool) $product['is_organic']
                                ? 'Organic'
                                : 'Standard'
                            ?>

                        </span>

                    </div>


                </div>


                <!-- =================================================
                     ADD TO CART
                ================================================== -->

                <?php if ($stockQuantity > 0): ?>


                    <form
                        method="post"
                        action="<?= url(
                            'add-to-cart.php'
                        ) ?>"
                        class="
                            product-cart-form
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
                            name="redirect"
                            value="<?= e(
                                'product.php?id=' .
                                $productId
                            ) ?>"
                        >


                        <div class="quantity-row">


                            <div class="quantity-field">

                                <label for="quantity">
                                    Quantity
                                </label>


                                <input
                                    id="quantity"
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?= $stockQuantity ?>"
                                    step="1"
                                    required
                                >

                            </div>


                            <div class="stock-note">

                                Maximum:
                                <?= $stockQuantity ?>

                            </div>


                        </div>


                        <div class="product-actions">


                            <button
                                type="submit"
                                class="
                                    btn
                                    btn-green
                                    flex-grow-1
                                "
                                data-cart-submit
                            >

                                <i class="bi bi-cart-plus me-1"></i>

                                Add to Cart

                            </button>


                            <a
                                href="<?= url('cart.php') ?>"
                                class="
                                    btn
                                    btn-outline-green
                                "
                                data-cart-open
                            >

                                <i class="bi bi-bag"></i>

                                View Cart

                            </a>


                        </div>


                    </form>


                <?php else: ?>


                    <div class="out-of-stock-box">

                        <strong>
                            This product is currently out of stock.
                        </strong>

                        <div class="mt-1">

                            You can browse other Green Harvest
                            products or contact us for assistance.

                        </div>

                    </div>


                    <div class="product-actions mt-3">


                        <a
                            href="<?= url('shop.php') ?>"
                            class="
                                btn
                                btn-outline-green
                                flex-grow-1
                            "
                        >
                            Browse Products
                        </a>


                        <a
                            href="<?= url(
                                'contact.php'
                            ) ?>"
                            class="
                                btn
                                btn-green
                            "
                        >
                            Contact Us
                        </a>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>

</section>


<!-- =========================================================
     RELATED PRODUCTS
========================================================= -->

<?php if ($relatedProducts): ?>

    <section class="related-products">

        <div class="container">


            <div class="related-header">

                <div>

                    <p class="section-eyebrow mb-2">
                        You May Also Like
                    </p>

                    <h2>
                        Related Products
                    </h2>

                </div>


                <?php if (
                    (int) $product['category_id'] > 0
                ): ?>

                    <a
                        href="<?= url(
                            'category.php?id=' .
                            (int) $product['category_id']
                        ) ?>"
                        class="
                            btn
                            btn-outline-green
                        "
                    >
                        View Category
                    </a>

                <?php endif; ?>


            </div>


            <div class="row g-4">


                <?php foreach (
                    $relatedProducts as
                    $related
                ): ?>

                    <?php

                    $relatedId =
                        (int) $related['id'];


                    $relatedStock =
                        max(
                            0,
                            (int) $related['stock_quantity']
                        );

                    ?>


                    <div
                        class="
                            col-sm-6
                            col-lg-3
                        "
                    >


                        <article class="related-card">


                            <a
                                href="<?= url(
                                    'product.php?id=' .
                                    $relatedId
                                ) ?>"
                            >

                                <img
                                    src="<?= e(
                                        productImageUrl(
                                            $related['image']
                                            ?? null
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $related['name']
                                    ) ?>"
                                    class="related-card-image"
                                    loading="lazy"
                                >

                            </a>


                            <div class="related-card-body">


                                <?php if (
                                    !empty(
                                        $related['category_name']
                                    )
                                ): ?>

                                    <div class="related-card-category">

                                        <?= e(
                                            $related['category_name']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <a
                                    href="<?= url(
                                        'product.php?id=' .
                                        $relatedId
                                    ) ?>"
                                    class="
                                        text-decoration-none
                                    "
                                >

                                    <div class="related-card-name">

                                        <?= e(
                                            $related['name']
                                        ) ?>

                                    </div>

                                </a>


                                <div class="related-card-price">

                                    <?= money(
                                        $related['price']
                                    ) ?>


                                    <?php if (
                                        !empty(
                                            $related['unit']
                                        )
                                    ): ?>

                                        <small
                                            class="
                                                text-muted
                                                fw-normal
                                            "
                                        >
                                            /
                                            <?= e(
                                                $related['unit']
                                            ) ?>
                                        </small>

                                    <?php endif; ?>


                                </div>


                                <div class="related-card-actions">


                                    <a
                                        href="<?= url(
                                            'product.php?id=' .
                                            $relatedId
                                        ) ?>"
                                        class="
                                            btn
                                            btn-outline-green
                                            flex-grow-1
                                        "
                                    >
                                        View
                                    </a>


                                    <?php if (
                                        $relatedStock > 0
                                    ): ?>

                                        <form
                                            method="post"
                                            action="<?= url(
                                                'add-to-cart.php'
                                            ) ?>"
                                            class="
                                                flex-grow-1
                                                gh-cart-add-form
                                            "
                                            data-cart-add-form
                                        >

                                            <?= csrfField() ?>


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= $relatedId ?>"
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
                                                    'product.php?id=' .
                                                    $productId
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="
                                                    btn
                                                    btn-green
                                                    w-100
                                                "
                                                data-cart-submit
                                                aria-label="<?= e(
                                                    'Add ' .
                                                    $related['name'] .
                                                    ' to cart'
                                                ) ?>"
                                            >

                                                <i class="bi bi-cart-plus"></i>

                                            </button>


                                        </form>


                                    <?php endif; ?>


                                </div>


                            </div>


                        </article>


                    </div>


                <?php endforeach; ?>


            </div>


        </div>

    </section>

<?php endif; ?>


<?php

require_once __DIR__ . '/includes/footer.php';

?>