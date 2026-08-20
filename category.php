<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CATEGORIES
 * =========================================================
 *
 * category.php
 * Displays all categories.
 *
 * category.php?id=3
 * Displays products in a selected category.
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Request Parameters
|--------------------------------------------------------------------------
*/

$categoryId = filter_input(
    INPUT_GET,
    'id',
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
    (string) (
        $_GET['sort']
        ?? 'featured'
    )
);


$validSorts = [
    'featured',
    'price-low',
    'price-high',
    'newest',
];


if (
    !in_array(
        $sortBy,
        $validSorts,
        true
    )
) {

    $sortBy =
        'featured';

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


$itemsPerPage = 12;


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categories = [];
$categoryLoadError = false;


try {

    $stmt = $pdo->query(
        '
        SELECT
            c.id,
            c.name,
            c.description,
            c.image,
            COUNT(p.id) AS product_count

        FROM categories c

        LEFT JOIN products p
            ON p.category_id = c.id
            AND p.status = "active"

        GROUP BY
            c.id,
            c.name,
            c.description,
            c.image

        ORDER BY c.name ASC
        '
    );


    $categories =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest category listing error: ' .
        $e->getMessage()
    );


    $categoryLoadError =
        true;

}


/*
|--------------------------------------------------------------------------
| MODE 1 - ALL CATEGORIES
|--------------------------------------------------------------------------
*/

if (
    $categoryId === null
) {

    $pageTitle =
        'Categories';


    require_once __DIR__ . '/includes/header.php';

    ?>


<style>

/* =========================================================
   GREEN HARVEST - CATEGORY OVERVIEW
========================================================= */

.gh-category-page {

    --cat-dark:
        var(
            --green-950,
            #092516
        );

    --cat-green-dark:
        var(
            --green-dark,
            #174f2a
        );

    --cat-green:
        var(
            --green,
            #2f8f46
        );

    --cat-light:
        var(
            --green-light,
            #eaf6ec
        );

    --cat-soft:
        var(
            --green-soft,
            #f3faf4
        );

    --cat-ink:
        var(
            --ink,
            #17321f
        );

    --cat-muted:
        var(
            --muted,
            #647568
        );

    --cat-border:
        var(
            --border,
            rgba(23, 79, 42, .11)
        );

}


/* =========================================================
   HERO
========================================================= */

.gh-category-hero {

    position:
        relative;

    overflow:
        hidden;

    padding:
        88px 20px
        82px;

    background:
        linear-gradient(
            135deg,
            #eef8f0 0%,
            #f9fcfa 58%,
            #ffffff 100%
        );

    border-bottom:
        1px solid
        rgba(23, 79, 42, .06);

}


.gh-category-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        420px;

    height:
        420px;

    right:
        -160px;

    top:
        -270px;

    border-radius:
        50%;

    background:
        rgba(47, 143, 70, .055);

}


.gh-category-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        190px;

    height:
        190px;

    right:
        20%;

    bottom:
        -145px;

    border:
        1px solid
        rgba(47, 143, 70, .1);

    border-radius:
        50%;

}


.gh-category-hero-content {

    position:
        relative;

    z-index:
        2;

    max-width:
        780px;

}


.gh-category-hero-tag {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        17px;

    padding:
        7px
        12px;

    border:
        1px solid
        rgba(47, 143, 70, .12);

    border-radius:
        999px;

    background:
        rgba(255, 255, 255, .8);

    color:
        var(--cat-green);

    font-size:
        .7rem;

    font-weight:
        800;

    letter-spacing:
        .11em;

    text-transform:
        uppercase;

}


.gh-category-hero-tag i {

    font-size:
        .8rem;

}


.gh-category-hero h1 {

    max-width:
        760px;

    margin-bottom:
        15px;

    color:
        var(--cat-dark);

    font-size:
        clamp(
            3rem,
            7vw,
            5rem
        );

    line-height:
        .98;

    letter-spacing:
        -.055em;

}


.gh-category-hero-description {

    max-width:
        640px;

    margin:
        0;

    color:
        var(--cat-muted);

    font-size:
        1rem;

    line-height:
        1.75;

}


/* =========================================================
   CATEGORY SECTION
========================================================= */

.gh-category-section {

    padding:
        88px
        20px
        95px;

}


.gh-category-header {

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        30px;

    margin-bottom:
        42px;

}


.gh-category-eyebrow {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        9px;

    color:
        var(--cat-green);

    font-size:
        .71rem;

    font-weight:
        800;

    letter-spacing:
        .14em;

    text-transform:
        uppercase;

}


.gh-category-eyebrow::before {

    content:
        "";

    width:
        24px;

    height:
        2px;

    border-radius:
        999px;

    background:
        var(--cat-green);

}


.gh-category-title {

    margin-bottom:
        12px;

    color:
        var(--cat-ink);

    font-size:
        clamp(
            2.1rem,
            5vw,
            3.35rem
        );

    line-height:
        1.06;

    letter-spacing:
        -.045em;

}


.gh-category-intro {

    max-width:
        650px;

    margin:
        0;

    color:
        var(--cat-muted);

    line-height:
        1.75;

}


.gh-category-shop-link {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        var(--cat-green-dark);

    font-size:
        .85rem;

    font-weight:
        800;

    white-space:
        nowrap;

}


.gh-category-shop-link:hover {

    color:
        var(--cat-green);

}


.gh-category-shop-link i {

    transition:
        transform .2s ease;

}


.gh-category-shop-link:hover i {

    transform:
        translateX(3px);

}


/* =========================================================
   CATEGORY CARDS
========================================================= */

.gh-category-card {

    height:
        100%;

    display:
        flex;

    flex-direction:
        column;

    overflow:
        hidden;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        24px;

    background:
        #ffffff;

    box-shadow:
        0 7px 26px
        rgba(23, 79, 42, .045);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;

}


.gh-category-card:hover {

    transform:
        translateY(-6px);

    border-color:
        rgba(47, 143, 70, .16);

    box-shadow:
        0 22px 52px
        rgba(23, 79, 42, .1);

}


/* Image */

.gh-category-card-image-wrap {

    position:
        relative;

    display:
        block;

    overflow:
        hidden;

    aspect-ratio:
        16 / 10;

    background:
        var(--cat-light);

}


.gh-category-card-image {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform .45s
        cubic-bezier(
            .16,
            1,
            .3,
            1
        );

}


.gh-category-card:hover
.gh-category-card-image {

    transform:
        scale(1.05);

}


.gh-category-image-overlay {

    position:
        absolute;

    inset:
        auto 0 0;

    height:
        42%;

    background:
        linear-gradient(
            to top,
            rgba(9, 37, 22, .28),
            transparent
        );

    pointer-events:
        none;

}


/* Product count badge */

.gh-category-count-badge {

    position:
        absolute;

    left:
        15px;

    bottom:
        15px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        7px
        11px;

    border:
        1px solid
        rgba(255, 255, 255, .65);

    border-radius:
        999px;

    background:
        rgba(255, 255, 255, .91);

    color:
        var(--cat-green-dark);

    font-size:
        .68rem;

    font-weight:
        800;

    backdrop-filter:
        blur(8px);

    box-shadow:
        0 5px 18px
        rgba(0, 0, 0, .08);

}


/* Content */

.gh-category-card-content {

    flex:
        1;

    display:
        flex;

    flex-direction:
        column;

    padding:
        22px;

}


.gh-category-card-title {

    margin-bottom:
        9px;

    color:
        var(--cat-ink);

    font-size:
        1.32rem;

    line-height:
        1.2;

}


.gh-category-card-title a {

    color:
        var(--cat-ink);

}


.gh-category-card-title
a:hover {

    color:
        var(--cat-green);

}


.gh-category-card-description {

    display:
        -webkit-box;

    overflow:
        hidden;

    margin-bottom:
        22px;

    color:
        var(--cat-muted);

    font-size:
        .84rem;

    line-height:
        1.7;

    -webkit-line-clamp:
        3;

    -webkit-box-orient:
        vertical;

}


.gh-category-card-action {

    margin-top:
        auto;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    min-height:
        46px;

    width:
        100%;

    padding:
        10px
        15px;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        12px;

    background:
        #f9fbf9;

    color:
        var(--cat-green-dark);

    font-size:
        .8rem;

    font-weight:
        800;

}


.gh-category-card-action:hover {

    border-color:
        var(--cat-green);

    background:
        var(--cat-green);

    color:
        #ffffff;

}


.gh-category-card-action i {

    transition:
        transform .2s ease;

}


.gh-category-card-action:hover i {

    transform:
        translateX(3px);

}


/* =========================================================
   EMPTY STATE
========================================================= */

.gh-category-empty {

    padding:
        70px
        25px;

    border:
        1px dashed
        rgba(23, 79, 42, .18);

    border-radius:
        22px;

    background:
        #fbfdfb;

    text-align:
        center;

}


.gh-category-empty-icon {

    width:
        60px;

    height:
        60px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        18px;

    border-radius:
        18px;

    background:
        var(--cat-light);

    color:
        var(--cat-green);

    font-size:
        1.45rem;

}


.gh-category-empty h2 {

    margin-bottom:
        10px;

    color:
        var(--cat-ink);

}


.gh-category-empty p {

    max-width:
        500px;

    margin:
        0 auto
        24px;

    color:
        var(--cat-muted);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 767.98px
) {

    .gh-category-hero {

        padding:
            65px
            18px
            60px;

    }


    .gh-category-section {

        padding:
            65px
            18px
            75px;

    }


    .gh-category-header {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap:
            20px;

        margin-bottom:
            34px;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-category-hero h1 {

        font-size:
            2.9rem;

    }


    .gh-category-card {

        border-radius:
            19px;

    }


    .gh-category-card-content {

        padding:
            19px;

    }

}

</style>



<div class="gh-category-page">


<!-- =========================================================
     HERO
========================================================= -->

<section class="gh-category-hero">


    <div class="container">


        <div class="gh-category-hero-content">


            <span class="gh-category-hero-tag">

                <i class="bi bi-grid"></i>

                Shop by Category

            </span>


            <h1>

                Explore our
                fresh harvest.

            </h1>


            <p class="gh-category-hero-description">

                Browse Green Harvest by category
                and discover fresh food that fits
                your everyday needs.

            </p>


        </div>


    </div>


</section>



<!-- =========================================================
     CATEGORY LIST
========================================================= -->

<section class="gh-category-section">


    <div class="container">


        <?php displayFlash(); ?>



        <?php if (
            $categoryLoadError
        ): ?>


            <div class="alert alert-danger">

                We could not load the product
                categories. Please try again
                shortly.

            </div>


        <?php endif; ?>



        <div class="gh-category-header">


            <div>


                <p class="gh-category-eyebrow">

                    Categories

                </p>


                <h2 class="gh-category-title">

                    Find exactly
                    what you need.

                </h2>


                <p class="gh-category-intro">

                    Browse Green Harvest products
                    by category and discover fresh
                    produce suited to your needs.

                </p>


            </div>



            <a
                href="<?= e(
                    url(
                        'shop.php'
                    )
                ) ?>"
                class="gh-category-shop-link"
            >

                View Full Shop

                <i class="bi bi-arrow-right"></i>

            </a>


        </div>



        <?php if (
            $categories
        ): ?>


            <div class="row g-4">


                <?php foreach (
                    $categories as
                    $category
                ): ?>


                    <?php

                    $catId =
                        (int)
                        $category['id'];


                    $productCount =
                        (int)
                        $category['product_count'];


                    $description =
                        trim(
                            (string) (
                                $category['description']
                                ?? ''
                            )
                        );


                    $categoryUrl =
                        url(
                            'category.php?id=' .
                            $catId
                        );

                    ?>


                    <div
                        class="
                            col-md-6
                            col-lg-4
                        "
                    >


                        <article class="gh-category-card">


                            <!-- IMAGE -->

                            <a
                                href="<?= e(
                                    $categoryUrl
                                ) ?>"
                                class="gh-category-card-image-wrap"
                            >


                                <img
                                    src="<?= e(
                                        categoryImageUrl(
                                            $category['image']
                                            ?? null
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $category['name']
                                    ) ?>"
                                    class="gh-category-card-image"
                                    loading="lazy"
                                >


                                <span
                                    class="gh-category-image-overlay"
                                    aria-hidden="true"
                                ></span>


                                <span class="gh-category-count-badge">

                                    <i class="bi bi-basket"></i>

                                    <?= $productCount ?>

                                    product<?= $productCount === 1
                                        ? ''
                                        : 's' ?>

                                </span>


                            </a>



                            <!-- CONTENT -->

                            <div class="gh-category-card-content">


                                <h2 class="gh-category-card-title">


                                    <a
                                        href="<?= e(
                                            $categoryUrl
                                        ) ?>"
                                    >

                                        <?= e(
                                            $category['name']
                                        ) ?>

                                    </a>


                                </h2>



                                <p class="gh-category-card-description">

                                    <?= e(
                                        $description !== ''
                                            ? $description
                                            : 'Explore fresh products available in this Green Harvest category.'
                                    ) ?>

                                </p>



                                <a
                                    href="<?= e(
                                        $categoryUrl
                                    ) ?>"
                                    class="gh-category-card-action"
                                >

                                    Browse Category

                                    <i class="bi bi-arrow-right"></i>

                                </a>


                            </div>


                        </article>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="gh-category-empty">


                <span class="gh-category-empty-icon">

                    <i class="bi bi-grid"></i>

                </span>


                <h2>

                    No categories yet.

                </h2>


                <p>

                    Green Harvest product
                    categories will appear here
                    when they are added.

                </p>


                <a
                    href="<?= e(
                        url(
                            'shop.php'
                        )
                    ) ?>"
                    class="btn btn-green"
                >

                    Visit Shop

                </a>


            </div>


        <?php endif; ?>


    </div>


</section>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

exit;

}


/*
|--------------------------------------------------------------------------
| MODE 2 - SELECTED CATEGORY
|--------------------------------------------------------------------------
*/

$category = null;


try {

    $stmt =
        $pdo->prepare(
            '
            SELECT
                id,
                name,
                description,
                image

            FROM categories

            WHERE id = ?

            LIMIT 1
            '
        );


    $stmt->execute([
        $categoryId,
    ]);


    $category =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        !$category
    ) {

        setFlash(
            'error',
            'The requested category could not be found.'
        );


        redirectTo(
            'category.php'
        );

    }


} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest category details error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'We could not load this category.'
    );


    redirectTo(
        'category.php'
    );

}


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$orderClause = match (
    $sortBy
) {

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
| Count Products
|--------------------------------------------------------------------------
*/

$totalProducts = 0;
$totalPages = 1;
$products = [];
$productsLoadError = false;


try {

    $stmt =
        $pdo->prepare(
            '
            SELECT COUNT(*)

            FROM products

            WHERE category_id = ?
            AND status = ?
            '
        );


    $stmt->execute([
        $categoryId,
        'active',
    ]);


    $totalProducts =
        (int)
        $stmt->fetchColumn();


    $totalPages =
        max(
            1,
            (int)
            ceil(
                $totalProducts /
                $itemsPerPage
            )
        );


    if (
        $page >
        $totalPages
    ) {

        $page =
            $totalPages;

    }


    $offset =
        ($page - 1) *
        $itemsPerPage;


    /*
     * Products
     */

    $sql = '
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
            p.created_at

        FROM products p

        WHERE p.category_id = :category_id
        AND p.status = :status

        ORDER BY ' .
        $orderClause .
        '

        LIMIT :limit
        OFFSET :offset
    ';


    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->bindValue(
        ':category_id',
        $categoryId,
        PDO::PARAM_INT
    );


    $stmt->bindValue(
        ':status',
        'active',
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
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest category products error: ' .
        $e->getMessage()
    );


    $productsLoadError =
        true;


    $offset =
        0;

}


/*
|--------------------------------------------------------------------------
| Showing Range
|--------------------------------------------------------------------------
*/

if (
    $totalProducts > 0
) {

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
*/

$categoryUrl = static function (
    array $changes = []
) use (
    $categoryId,
    $sortBy
): string {

    $parameters = [
        'id' =>
            $categoryId,

        'sort' =>
            $sortBy,
    ];


    foreach (
        $changes as
        $key => $value
    ) {

        if (
            $value === null
        ) {

            unset(
                $parameters[$key]
            );


        } else {

            $parameters[$key] =
                $value;

        }

    }


    return url(
        'category.php?' .
        http_build_query(
            $parameters
        )
    );

};


/*
|--------------------------------------------------------------------------
| Add-To-Cart Redirect
|--------------------------------------------------------------------------
*/

$currentCategoryRedirect =
    'category.php?' .
    http_build_query([
        'id' =>
            $categoryId,

        'sort' =>
            $sortBy,

        'page' =>
            $page,
    ]);


/*
|--------------------------------------------------------------------------
| Render Category Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    (string)
    $category['name'];


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST - CATEGORY DETAIL
========================================================= */

.gh-category-detail-page {

    --cat-dark:
        var(
            --green-950,
            #092516
        );

    --cat-green-dark:
        var(
            --green-dark,
            #174f2a
        );

    --cat-green:
        var(
            --green,
            #2f8f46
        );

    --cat-light:
        var(
            --green-light,
            #eaf6ec
        );

    --cat-soft:
        var(
            --green-soft,
            #f3faf4
        );

    --cat-ink:
        var(
            --ink,
            #17321f
        );

    --cat-muted:
        var(
            --muted,
            #647568
        );

    --cat-border:
        var(
            --border,
            rgba(23, 79, 42, .11)
        );

}


/* =========================================================
   HERO
========================================================= */

.gh-category-detail-hero {

    position:
        relative;

    overflow:
        hidden;

    padding:
        82px
        20px;

    background:
        linear-gradient(
            135deg,
            #092516,
            #174f2a 58%,
            #246f38
        );

    color:
        #ffffff;

}


.gh-category-detail-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        430px;

    height:
        430px;

    top:
        -290px;

    right:
        -100px;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius:
        50%;

}


.gh-category-detail-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        230px;

    height:
        230px;

    right:
        15%;

    bottom:
        -170px;

    border-radius:
        50%;

    background:
        rgba(74,222,128,.06);

}


.gh-category-detail-hero-content {

    position:
        relative;

    z-index:
        2;

    max-width:
        780px;

}


.gh-category-detail-tag {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        17px;

    padding:
        7px
        12px;

    border:
        1px solid
        rgba(255,255,255,.13);

    border-radius:
        999px;

    background:
        rgba(255,255,255,.07);

    color:
        #baf5c9;

    font-size:
        .7rem;

    font-weight:
        800;

    letter-spacing:
        .11em;

    text-transform:
        uppercase;

}


.gh-category-detail-hero h1 {

    margin-bottom:
        14px;

    color:
        #ffffff;

    font-size:
        clamp(
            3rem,
            7vw,
            5rem
        );

    line-height:
        .98;

    letter-spacing:
        -.055em;

}


.gh-category-detail-description {

    max-width:
        680px;

    margin:
        0;

    color:
        rgba(255,255,255,.68);

    font-size:
        .98rem;

    line-height:
        1.75;

}


/* =========================================================
   CONTENT SECTION
========================================================= */

.gh-category-detail-section {

    padding:
        62px
        20px
        92px;

}


/* =========================================================
   BREADCRUMB
========================================================= */

.gh-category-breadcrumb {

    display:
        flex;

    flex-wrap:
        wrap;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        26px;

    color:
        var(--cat-muted);

    font-size:
        .78rem;

}


.gh-category-breadcrumb a {

    color:
        var(--cat-green);

    font-weight:
        700;

}


.gh-category-breadcrumb
i {

    color:
        #9ca9a0;

    font-size:
        .65rem;

}


/* =========================================================
   TOOLBAR
========================================================= */

.gh-category-toolbar {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        22px;

    margin-bottom:
        34px;

    padding:
        17px
        18px;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        17px;

    background:
        #ffffff;

    box-shadow:
        0 5px 20px
        rgba(23,79,42,.035);

}


.gh-category-results {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        var(--cat-muted);

    font-size:
        .8rem;

    font-weight:
        700;

}


.gh-category-results-icon {

    width:
        34px;

    height:
        34px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        10px;

    background:
        var(--cat-light);

    color:
        var(--cat-green);

}


.gh-category-sort {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

}


.gh-category-sort label {

    color:
        var(--cat-muted);

    font-size:
        .75rem;

    font-weight:
        700;

    white-space:
        nowrap;

}


.gh-category-sort
.form-select {

    min-width:
        190px;

    min-height:
        43px;

    padding-top:
        6px;

    padding-bottom:
        6px;

    border-radius:
        11px;

}


/* =========================================================
   PRODUCT CARDS
========================================================= */

.gh-category-product-card {

    height:
        100%;

    display:
        flex;

    flex-direction:
        column;

    overflow:
        hidden;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        22px;

    background:
        #ffffff;

    box-shadow:
        0 6px 23px
        rgba(23,79,42,.04);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;

}


.gh-category-product-card:hover {

    transform:
        translateY(-6px);

    border-color:
        rgba(47,143,70,.16);

    box-shadow:
        0 22px 50px
        rgba(23,79,42,.10);

}


/* IMAGE */

.gh-category-product-image-wrap {

    position:
        relative;

    display:
        block;

    overflow:
        hidden;

    aspect-ratio:
        1 / .92;

    background:
        var(--cat-light);

}


.gh-category-product-image {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform .45s
        cubic-bezier(
            .16,
            1,
            .3,
            1
        );

}


.gh-category-product-card:hover
.gh-category-product-image {

    transform:
        scale(1.055);

}


/* BADGES */

.gh-category-product-badges {

    position:
        absolute;

    z-index:
        2;

    top:
        14px;

    left:
        14px;

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        6px;

}


.gh-category-product-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        6px
        10px;

    border:
        1px solid
        rgba(255,255,255,.65);

    border-radius:
        999px;

    background:
        rgba(255,255,255,.92);

    color:
        var(--cat-green-dark);

    font-size:
        .65rem;

    font-weight:
        800;

    box-shadow:
        0 5px 16px
        rgba(0,0,0,.07);

    backdrop-filter:
        blur(8px);

}


.gh-category-featured-badge {

    background:
        rgba(9,37,22,.88);

    border-color:
        rgba(9,37,22,.15);

    color:
        #ffffff;

}


/* CONTENT */

.gh-category-product-content {

    flex:
        1;

    display:
        flex;

    flex-direction:
        column;

    padding:
        20px;

}


.gh-category-product-name {

    margin-bottom:
        8px;

    color:
        var(--cat-ink);

    font-size:
        1.14rem;

    line-height:
        1.3;

}


.gh-category-product-name
a {

    color:
        var(--cat-ink);

}


.gh-category-product-name
a:hover {

    color:
        var(--cat-green);

}


.gh-category-product-description {

    display:
        -webkit-box;

    overflow:
        hidden;

    margin-bottom:
        16px;

    color:
        var(--cat-muted);

    font-size:
        .81rem;

    line-height:
        1.65;

    -webkit-line-clamp:
        3;

    -webkit-box-orient:
        vertical;

}


/* STOCK */

.gh-category-stock {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    width:
        fit-content;

    margin-bottom:
        14px;

    padding:
        5px
        8px;

    border-radius:
        8px;

    font-size:
        .68rem;

    font-weight:
        700;

}


.gh-category-stock::before {

    content:
        "";

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        currentColor;

}


.gh-category-stock.in {

    background:
        #f0fdf4;

    color:
        #15803d;

}


.gh-category-stock.low {

    background:
        #fff7ed;

    color:
        #c2410c;

}


.gh-category-stock.out {

    background:
        #fef2f2;

    color:
        #b91c1c;

}


/* FOOTER */

.gh-category-product-footer {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin-top:
        auto;

    padding-top:
        16px;

    border-top:
        1px solid
        var(--cat-border);

}


.gh-category-product-price {

    display:
        block;

    color:
        var(--cat-green-dark);

    font-size:
        1.07rem;

    font-weight:
        800;

}


.gh-category-product-unit {

    display:
        block;

    margin-top:
        1px;

    color:
        var(--cat-muted);

    font-size:
        .68rem;

}


/* ACTIONS */

.gh-category-product-actions {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

}


.gh-category-action {

    width:
        43px;

    height:
        43px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        12px;

    background:
        #ffffff;

    color:
        var(--cat-green-dark);

    cursor:
        pointer;

    transition:
        color .2s ease,
        border-color .2s ease,
        background-color .2s ease,
        transform .2s ease;

}


.gh-category-action:hover {

    border-color:
        var(--cat-green);

    background:
        var(--cat-light);

    color:
        var(--cat-green-dark);

    transform:
        translateY(-1px);

}


.gh-category-action.add {

    border-color:
        var(--cat-green-dark);

    background:
        var(--cat-green-dark);

    color:
        #ffffff;

}


.gh-category-action.add:hover {

    border-color:
        var(--cat-green);

    background:
        var(--cat-green);

    color:
        #ffffff;

}


.gh-category-action:disabled {

    border-color:
        #d8dfda;

    background:
        #d8dfda;

    color:
        #87928a;

    cursor:
        not-allowed;

    transform:
        none;

}


/* =========================================================
   EMPTY PRODUCTS
========================================================= */

.gh-category-no-products {

    padding:
        70px
        25px;

    border:
        1px dashed
        rgba(23,79,42,.18);

    border-radius:
        22px;

    background:
        #fbfdfb;

    text-align:
        center;

}


.gh-category-no-products-icon {

    width:
        60px;

    height:
        60px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        17px;

    border-radius:
        18px;

    background:
        var(--cat-light);

    color:
        var(--cat-green);

    font-size:
        1.45rem;

}


.gh-category-no-products p {

    max-width:
        520px;

    margin:
        0 auto;

    color:
        var(--cat-muted);

}


/* =========================================================
   PAGINATION
========================================================= */

.gh-category-pagination {

    display:
        flex;

    flex-wrap:
        wrap;

    justify-content:
        center;

    gap:
        7px;

    margin-top:
        46px;

}


.gh-category-page {

    min-width:
        42px;

    min-height:
        42px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        8px
        12px;

    border:
        1px solid
        var(--cat-border);

    border-radius:
        11px;

    background:
        #ffffff;

    color:
        var(--cat-green-dark);

    font-size:
        .8rem;

    font-weight:
        700;

    box-shadow:
        0 3px 10px
        rgba(23,79,42,.025);

    transition:
        background-color .2s ease,
        color .2s ease,
        border-color .2s ease,
        transform .2s ease;

}


.gh-category-page:hover {

    border-color:
        var(--cat-green);

    background:
        var(--cat-light);

    color:
        var(--cat-green-dark);

    transform:
        translateY(-1px);

}


.gh-category-page.active {

    border-color:
        var(--cat-green-dark);

    background:
        var(--cat-green-dark);

    color:
        #ffffff;

}


/* =========================================================
   TABLET
========================================================= */

@media (
    max-width: 767.98px
) {

    .gh-category-detail-hero {

        padding:
            65px
            18px;

    }


    .gh-category-detail-section {

        padding:
            50px
            18px
            75px;

    }


    .gh-category-toolbar {

        align-items:
            stretch;

        flex-direction:
            column;

    }


    .gh-category-sort {

        width:
            100%;

    }


    .gh-category-sort
    .form-select {

        flex:
            1;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (
    max-width: 575.98px
) {

    .gh-category-detail-hero h1 {

        font-size:
            2.9rem;

    }


    .gh-category-toolbar {

        padding:
            15px;

    }


    .gh-category-sort {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .gh-category-sort label {

        width:
            100%;

    }


    .gh-category-sort
    .form-select {

        width:
            100%;

        min-width:
            0;

    }


    .gh-category-product-card {

        border-radius:
            19px;

    }


    .gh-category-product-content {

        padding:
            18px;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-category-product-card,
    .gh-category-product-image,
    .gh-category-action,
    .gh-category-page {

        transition:
            none;

    }


    .gh-category-product-card:hover,
    .gh-category-action:hover,
    .gh-category-page:hover {

        transform:
            none;

    }

}

</style>



<div class="gh-category-detail-page">


<!-- =========================================================
     HERO
========================================================= -->

<section class="gh-category-detail-hero">


    <div class="container">


        <div class="gh-category-detail-hero-content">


            <span class="gh-category-detail-tag">

                <i class="bi bi-basket"></i>

                Product Category

            </span>


            <h1>

                <?= e(
                    $category['name']
                ) ?>

            </h1>



            <?php if (
                trim(
                    (string) (
                        $category['description']
                        ?? ''
                    )
                ) !== ''
            ): ?>


                <p class="gh-category-detail-description">

                    <?= e(
                        $category['description']
                    ) ?>

                </p>


            <?php endif; ?>


        </div>


    </div>


</section>



<!-- =========================================================
     PRODUCTS
========================================================= -->

<section class="gh-category-detail-section">


    <div class="container">


        <?php displayFlash(); ?>



        <!-- =================================================
             BREADCRUMB
        ================================================== -->

        <nav
            class="gh-category-breadcrumb"
            aria-label="Breadcrumb"
        >


            <a
                href="<?= e(
                    url(
                        'index.php'
                    )
                ) ?>"
            >

                Home

            </a>


            <i class="bi bi-chevron-right"></i>


            <a
                href="<?= e(
                    url(
                        'category.php'
                    )
                ) ?>"
            >

                Categories

            </a>


            <i class="bi bi-chevron-right"></i>


            <span>

                <?= e(
                    $category['name']
                ) ?>

            </span>


        </nav>



        <?php if (
            $productsLoadError
        ): ?>


            <div class="alert alert-danger">

                We could not load products
                from this category.
                Please try again.

            </div>


        <?php endif; ?>



        <!-- =================================================
             TOOLBAR
        ================================================== -->

        <div class="gh-category-toolbar">


            <div class="gh-category-results">


                <span class="gh-category-results-icon">

                    <i class="bi bi-grid"></i>

                </span>


                <span>

                    Showing

                    <?= $showingFrom ?>

                    –

                    <?= $showingTo ?>

                    of

                    <?= $totalProducts ?>

                    product<?= $totalProducts === 1
                        ? ''
                        : 's' ?>

                </span>


            </div>



            <form
                method="get"
                action="<?= e(
                    url(
                        'category.php'
                    )
                ) ?>"
                class="gh-category-sort"
            >


                <input
                    type="hidden"
                    name="id"
                    value="<?= $categoryId ?>"
                >


                <label for="categorySort">

                    Sort by

                </label>


                <select
                    id="categorySort"
                    name="sort"
                    class="form-select"
                    onchange="this.form.submit()"
                >


                    <option
                        value="featured"
                        <?= $sortBy === 'featured'
                            ? 'selected'
                            : '' ?>
                    >

                        Featured

                    </option>


                    <option
                        value="price-low"
                        <?= $sortBy === 'price-low'
                            ? 'selected'
                            : '' ?>
                    >

                        Price: Low to High

                    </option>


                    <option
                        value="price-high"
                        <?= $sortBy === 'price-high'
                            ? 'selected'
                            : '' ?>
                    >

                        Price: High to Low

                    </option>


                    <option
                        value="newest"
                        <?= $sortBy === 'newest'
                            ? 'selected'
                            : '' ?>
                    >

                        Newest

                    </option>


                </select>


            </form>


        </div>



        <?php if (
            $products
        ): ?>


            <div class="row g-4">


                <?php foreach (
                    $products as
                    $product
                ): ?>


                    <?php

                    $productId =
                        (int)
                        $product['id'];


                    $stock =
                        (int)
                        $product['stock_quantity'];


                    $productUrl =
                        url(
                            'product.php?id=' .
                            $productId
                        );

                    ?>


                    <div
                        class="
                            col-sm-6
                            col-lg-4
                            col-xl-3
                        "
                    >


                        <article class="gh-category-product-card">


                            <!-- =================================
                                 IMAGE
                            ================================== -->

                            <a
                                href="<?= e(
                                    $productUrl
                                ) ?>"
                                class="gh-category-product-image-wrap"
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
                                    class="gh-category-product-image"
                                    loading="lazy"
                                >



                                <div class="gh-category-product-badges">


                                    <?php if (
                                        (bool)
                                        $product['is_organic']
                                    ): ?>


                                        <span class="gh-category-product-badge">

                                            <i class="bi bi-leaf-fill"></i>

                                            Organic

                                        </span>


                                    <?php endif; ?>



                                    <?php if (
                                        (bool)
                                        $product['is_featured']
                                    ): ?>


                                        <span
                                            class="
                                                gh-category-product-badge
                                                gh-category-featured-badge
                                            "
                                        >

                                            Featured

                                        </span>


                                    <?php endif; ?>


                                </div>


                            </a>



                            <!-- =================================
                                 CONTENT
                            ================================== -->

                            <div class="gh-category-product-content">


                                <h2 class="gh-category-product-name">


                                    <a
                                        href="<?= e(
                                            $productUrl
                                        ) ?>"
                                    >

                                        <?= e(
                                            $product['name']
                                        ) ?>

                                    </a>


                                </h2>



                                <p class="gh-category-product-description">

                                    <?= e(
                                        $product['description']
                                        ?: 'Fresh, carefully selected produce from Green Harvest.'
                                    ) ?>

                                </p>



                                <!-- STOCK -->

                                <?php if (
                                    $stock <= 0
                                ): ?>


                                    <span
                                        class="
                                            gh-category-stock
                                            out
                                        "
                                    >

                                        Out of stock

                                    </span>


                                <?php elseif (
                                    $stock <= 10
                                ): ?>


                                    <span
                                        class="
                                            gh-category-stock
                                            low
                                        "
                                    >

                                        Only
                                        <?= $stock ?>
                                        left

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            gh-category-stock
                                            in
                                        "
                                    >

                                        In stock

                                    </span>


                                <?php endif; ?>



                                <!-- FOOTER -->

                                <div class="gh-category-product-footer">


                                    <div>


                                        <span class="gh-category-product-price">

                                            <?= money(
                                                $product['price']
                                            ) ?>

                                        </span>


                                        <span class="gh-category-product-unit">

                                            per

                                            <?= e(
                                                $product['unit']
                                                ?: 'item'
                                            ) ?>

                                        </span>


                                    </div>



                                    <div class="gh-category-product-actions">


                                        <!-- VIEW -->

                                        <a
                                            href="<?= e(
                                                $productUrl
                                            ) ?>"
                                            class="gh-category-action"
                                            title="View product"
                                            aria-label="View <?= e($product['name']) ?>"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>



                                        <!-- ADD -->

                                        <?php if (
                                            $stock > 0
                                        ): ?>


                                            <form
                                                method="post"
                                                action="<?= e(
                                                    url(
                                                        'add-to-cart.php'
                                                    )
                                                ) ?>"
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
                                                        $currentCategoryRedirect
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="
                                                        gh-category-action
                                                        add
                                                    "
                                                    title="Add to cart"
                                                    aria-label="Add <?= e($product['name']) ?> to cart"
                                                >

                                                    <i class="bi bi-plus-lg"></i>

                                                </button>


                                            </form>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                class="
                                                    gh-category-action
                                                    add
                                                "
                                                disabled
                                                title="Out of stock"
                                                aria-label="Out of stock"
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
                 PAGINATION
            ================================================== -->

            <?php if (
                $totalPages > 1
            ): ?>


                <nav
                    class="gh-category-pagination"
                    aria-label="Category pagination"
                >


                    <?php if (
                        $page > 1
                    ): ?>


                        <a
                            href="<?= e(
                                $categoryUrl([
                                    'page' =>
                                        $page - 1,
                                ])
                            ) ?>"
                            class="gh-category-page"
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
                        $pageNumber =
                            $startPage;

                        $pageNumber <=
                            $endPage;

                        $pageNumber++
                    ): ?>


                        <?php if (
                            $pageNumber ===
                            $page
                        ): ?>


                            <span
                                class="
                                    gh-category-page
                                    active
                                "
                                aria-current="page"
                            >

                                <?= $pageNumber ?>

                            </span>


                        <?php else: ?>


                            <a
                                href="<?= e(
                                    $categoryUrl([
                                        'page' =>
                                            $pageNumber,
                                    ])
                                ) ?>"
                                class="gh-category-page"
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
                            href="<?= e(
                                $categoryUrl([
                                    'page' =>
                                        $page + 1,
                                ])
                            ) ?>"
                            class="gh-category-page"
                            aria-label="Next page"
                        >

                            <i class="bi bi-chevron-right"></i>

                        </a>


                    <?php endif; ?>


                </nav>


            <?php endif; ?>



        <?php else: ?>


            <!-- =================================================
                 EMPTY CATEGORY
            ================================================== -->

            <div class="gh-category-no-products">


                <span class="gh-category-no-products-icon">

                    <i class="bi bi-basket"></i>

                </span>


                <h2>

                    No products in this
                    category yet.

                </h2>


                <p>

                    Browse another category
                    or explore the complete
                    Green Harvest shop.

                </p>



                <div
                    class="
                        d-flex
                        flex-wrap
                        justify-content-center
                        gap-2
                        mt-4
                    "
                >


                    <a
                        href="<?= e(
                            url(
                                'category.php'
                            )
                        ) ?>"
                        class="btn btn-outline-green"
                    >

                        View Categories

                    </a>


                    <a
                        href="<?= e(
                            url(
                                'shop.php'
                            )
                        ) ?>"
                        class="btn btn-green"
                    >

                        Browse Shop

                    </a>


                </div>


            </div>


        <?php endif; ?>


    </div>


</section>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>