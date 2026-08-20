<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - HOMEPAGE
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Homepage Data
|--------------------------------------------------------------------------
*/

$products = [];
$categories = [];
$loadError = false;


/*
|--------------------------------------------------------------------------
| Hero Background Images
|--------------------------------------------------------------------------
*/

$heroSlides = [];

$heroExtensions = [
    'jpg',
    'jpeg',
    'png',
    'webp',
];


for (
    $imageNumber = 1;
    $imageNumber <= 5;
    $imageNumber++
) {

    foreach (
        $heroExtensions as
        $extension
    ) {

        $relativePath =
            'assets/images/img' .
            $imageNumber .
            '.' .
            $extension;


        $absolutePath =
            __DIR__ .
            '/' .
            $relativePath;


        if (
            is_file(
                $absolutePath
            )
        ) {

            $heroSlides[] =
                $relativePath;

            break;

        }

    }

}


/*
|--------------------------------------------------------------------------
| Homepage Products / Categories
|--------------------------------------------------------------------------
*/

try {

    /*
     * Categories
     */
    $categories =
        getCategories(
            $pdo
        );


    /*
     * Active products
     */
    $stmt =
        $pdo->query(
            "
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
                c.name AS category_name

            FROM products p

            LEFT JOIN categories c
                ON c.id = p.category_id

            WHERE p.status = 'active'

            ORDER BY
                p.is_featured DESC,
                p.created_at DESC

            LIMIT 12
            "
        );


    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (
    PDOException $e
) {

    error_log(
        'Green Harvest homepage loading error: ' .
        $e->getMessage()
    );


    $loadError = true;

}


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Fresh Organic Foods';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST HOMEPAGE
========================================================= */

.gh-home-page {

    --home-dark:
        var(
            --green-950,
            #092516
        );

    --home-green-dark:
        var(
            --green-dark,
            #174f2a
        );

    --home-green:
        var(
            --green,
            #2f8f46
        );

    --home-green-bright:
        var(
            --green-500,
            #4ade80
        );

    --home-green-light:
        var(
            --green-light,
            #eaf6ec
        );

    --home-green-soft:
        var(
            --green-soft,
            #f3faf4
        );

    --home-cream:
        var(
            --cream,
            #fbf7ed
        );

    --home-ink:
        var(
            --ink,
            #17321f
        );

    --home-muted:
        var(
            --muted,
            #647568
        );

    --home-border:
        var(
            --border,
            rgba(23, 79, 42, .11)
        );

}


/* =========================================================
   HERO
========================================================= */

.gh-home-hero {

    position: relative;

    min-height: 690px;

    display: flex;

    align-items: center;

    overflow: hidden;

    background:
        var(--home-dark);

    color:
        #ffffff;

}


/* =========================================================
   HERO BACKGROUND SLIDES
========================================================= */

.gh-home-hero-backgrounds {

    position: absolute;

    inset: 0;

    z-index: 0;

    overflow: hidden;

}


.gh-home-hero-slide {

    position: absolute;

    inset: 0;

    width: 100%;

    height: 100%;

    opacity: 0;

    visibility: hidden;

    background-position:
        center;

    background-size:
        cover;

    background-repeat:
        no-repeat;

    transform:
        scale(1.02);

    transition:
        opacity 1.25s ease,
        visibility 1.25s ease,
        transform 7s ease;

}


.gh-home-hero-slide.active {

    opacity: 1;

    visibility: visible;

    transform:
        scale(1.065);

}


.gh-home-hero-fallback {

    position: absolute;

    inset: 0;

    background:
        url(
            'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85'
        )
        center / cover
        no-repeat;

}


/* =========================================================
   HERO OVERLAY
========================================================= */

.gh-home-hero-overlay {

    position: absolute;

    inset: 0;

    z-index: 1;

    background:
        linear-gradient(
            90deg,
            rgba(5, 26, 14, .97)
            0%,
            rgba(8, 43, 23, .91)
            37%,
            rgba(12, 53, 29, .67)
            67%,
            rgba(5, 30, 16, .30)
            100%
        );

}


/* =========================================================
   HERO DECORATION
========================================================= */

.gh-home-hero::before {

    content: "";

    position: absolute;

    z-index: 2;

    width: 540px;

    height: 540px;

    top: -340px;

    right: -120px;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.08);

    pointer-events: none;

}


.gh-home-hero::after {

    content: "";

    position: absolute;

    z-index: 2;

    width: 270px;

    height: 270px;

    right: 6%;

    bottom: -180px;

    border-radius: 50%;

    background:
        rgba(74, 222, 128, .055);

    pointer-events: none;

}


/* =========================================================
   HERO CONTENT
========================================================= */

.gh-home-hero-container {

    position: relative;

    z-index: 3;

}


.gh-home-hero-content {

    max-width: 790px;

    padding:
        110px 0
        100px;

}


.gh-home-hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 25px;

    padding:
        8px 14px;

    border:
        1px solid
        rgba(255,255,255,.14);

    border-radius: 999px;

    background:
        rgba(255,255,255,.08);

    color:
        #c8f7d4;

    font-size: .7rem;

    font-weight: 800;

    letter-spacing: .12em;

    text-transform: uppercase;

    backdrop-filter:
        blur(12px);

}


.gh-home-hero-badge-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    background:
        var(--home-green-bright);

    box-shadow:
        0 0 0 5px
        rgba(74, 222, 128, .1);

}


.gh-home-hero h1 {

    max-width: 780px;

    margin-bottom: 24px;

    color: #ffffff;

    font-size:
        clamp(
            3.4rem,
            7.4vw,
            6rem
        );

    line-height: .94;

    letter-spacing: -.06em;

}


.gh-home-hero-description {

    max-width: 610px;

    margin-bottom: 34px;

    color:
        rgba(255,255,255,.71);

    font-size:
        clamp(
            1rem,
            1.4vw,
            1.13rem
        );

    line-height: 1.8;

}


/* =========================================================
   HERO ACTIONS
========================================================= */

.gh-home-hero-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 11px;

}


.gh-home-hero-primary,
.gh-home-hero-secondary {

    min-height: 52px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    padding:
        12px
        24px;

    border-radius: 13px;

    font-size: .9rem;

    font-weight: 800;

    transition:
        transform .22s ease,
        background-color .22s ease,
        border-color .22s ease;

}


.gh-home-hero-primary {

    border:
        1px solid
        var(--home-green);

    background:
        var(--home-green);

    color: #ffffff;

    box-shadow:
        0 15px 34px
        rgba(47, 143, 70, .24);

}


.gh-home-hero-primary:hover {

    background:
        #267c3b;

    border-color:
        #267c3b;

    color: #ffffff;

    transform:
        translateY(-2px);

}


.gh-home-hero-secondary {

    border:
        1px solid
        rgba(255,255,255,.18);

    background:
        rgba(255,255,255,.07);

    color: #ffffff;

    backdrop-filter:
        blur(10px);

}


.gh-home-hero-secondary:hover {

    background:
        rgba(255,255,255,.13);

    border-color:
        rgba(255,255,255,.28);

    color: #ffffff;

    transform:
        translateY(-2px);

}


/* =========================================================
   HERO TRUST POINTS
========================================================= */

.gh-home-hero-trust {

    display: flex;

    flex-wrap: wrap;

    gap:
        10px
        22px;

    margin-top: 34px;

}


.gh-home-hero-trust-item {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color:
        rgba(255,255,255,.67);

    font-size: .78rem;

    font-weight: 600;

}


.gh-home-hero-trust-item i {

    color:
        #86efac;

}


/* =========================================================
   SLIDER DOTS
========================================================= */

.gh-home-slider-dots {

    position: absolute;

    z-index: 4;

    left: 50%;

    bottom: 28px;

    display: flex;

    align-items: center;

    gap: 7px;

    transform:
        translateX(-50%);

}


.gh-home-slider-dot {

    width: 8px;

    height: 8px;

    padding: 0;

    border: 0;

    border-radius: 50%;

    background:
        rgba(255,255,255,.38);

    cursor: pointer;

    transition:
        width .25s ease,
        border-radius .25s ease,
        background-color .25s ease;

}


.gh-home-slider-dot.active {

    width: 26px;

    border-radius: 999px;

    background:
        #ffffff;

}


/* =========================================================
   GENERAL SECTIONS
========================================================= */

.gh-home-section {

    padding:
        92px 20px;

}


.gh-home-section-soft {

    background:
        #f7faf7;

}


.gh-home-eyebrow {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 10px;

    color:
        var(--home-green);

    font-size: .72rem;

    font-weight: 800;

    letter-spacing: .14em;

    text-transform: uppercase;

}


.gh-home-eyebrow::before {

    content: "";

    width: 24px;

    height: 2px;

    border-radius: 999px;

    background:
        var(--home-green);

}


.gh-home-title {

    margin-bottom: 13px;

    color:
        var(--home-ink);

    font-size:
        clamp(
            2.1rem,
            5vw,
            3.45rem
        );

    line-height: 1.06;

    letter-spacing: -.045em;

}


.gh-home-description {

    max-width: 670px;

    margin: 0;

    color:
        var(--home-muted);

    line-height: 1.75;

}


/* =========================================================
   CATEGORIES SECTION
========================================================= */

.gh-home-category-section {

    padding-bottom:
        75px;

}


.gh-home-category-header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 30px;

}


.gh-home-shop-link {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color:
        var(--home-green-dark);

    font-size: .86rem;

    font-weight: 800;

    white-space: nowrap;

}


.gh-home-shop-link:hover {

    color:
        var(--home-green);

}


.gh-home-shop-link i {

    transition:
        transform .2s ease;

}


.gh-home-shop-link:hover i {

    transform:
        translateX(3px);

}


/* =========================================================
   CATEGORY FILTERS
========================================================= */

.gh-home-category-filters {

    display: flex;

    flex-wrap: wrap;

    gap: 9px;

    margin-top: 31px;

}


.gh-home-category-filter {

    min-height: 42px;

    padding:
        9px
        17px;

    border:
        1px solid
        var(--home-border);

    border-radius: 999px;

    background:
        #ffffff;

    color:
        #59685e;

    font-size: .8rem;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 2px 8px
        rgba(23,79,42,.025);

    transition:
        color .2s ease,
        border-color .2s ease,
        background-color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;

}


.gh-home-category-filter:hover {

    border-color:
        rgba(47,143,70,.38);

    color:
        var(--home-green-dark);

    transform:
        translateY(-1px);

}


.gh-home-category-filter.active {

    border-color:
        var(--home-green-dark);

    background:
        var(--home-green-dark);

    color:
        #ffffff;

    box-shadow:
        0 8px 20px
        rgba(23,79,42,.14);

}


/* =========================================================
   PRODUCT SECTION HEADER
========================================================= */

.gh-home-products-header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 30px;

    margin-bottom: 42px;

}


/* =========================================================
   PRODUCT CARDS
========================================================= */

.gh-home-product-card {

    height: 100%;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    border:
        1px solid
        rgba(23,79,42,.09);

    border-radius: 22px;

    background:
        #ffffff;

    box-shadow:
        0 6px 24px
        rgba(23,79,42,.045);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;

}


.gh-home-product-card:hover {

    transform:
        translateY(-6px);

    border-color:
        rgba(47,143,70,.16);

    box-shadow:
        0 22px 52px
        rgba(23,79,42,.105);

}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.gh-home-product-image-wrap {

    position: relative;

    display: block;

    aspect-ratio: 1 / .92;

    overflow: hidden;

    background:
        var(--home-green-light);

}


.gh-home-product-image {

    width: 100%;

    height: 100%;

    object-fit: cover;

    transition:
        transform .45s
        cubic-bezier(.16,1,.3,1);

}


.gh-home-product-card:hover
.gh-home-product-image {

    transform:
        scale(1.055);

}


/* =========================================================
   PRODUCT BADGES
========================================================= */

.gh-home-product-badges {

    position: absolute;

    z-index: 2;

    top: 14px;

    left: 14px;

    display: flex;

    flex-wrap: wrap;

    gap: 6px;

}


.gh-home-product-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding:
        6px
        10px;

    border:
        1px solid
        rgba(255,255,255,.7);

    border-radius: 999px;

    background:
        rgba(255,255,255,.92);

    color:
        var(--home-green-dark);

    font-size: .65rem;

    font-weight: 800;

    backdrop-filter:
        blur(9px);

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.07);

}


.gh-home-product-badge-featured {

    background:
        rgba(9,37,22,.88);

    border-color:
        rgba(9,37,22,.15);

    color:
        #ffffff;

}


/* =========================================================
   PRODUCT BODY
========================================================= */

.gh-home-product-body {

    flex: 1;

    display: flex;

    flex-direction: column;

    padding:
        20px;

}


.gh-home-product-category {

    margin-bottom: 7px;

    color:
        var(--home-green);

    font-size: .67rem;

    font-weight: 800;

    letter-spacing: .1em;

    text-transform: uppercase;

}


.gh-home-product-name {

    margin-bottom: 8px;

    color:
        var(--home-ink);

    font-size: 1.16rem;

    line-height: 1.3;

}


.gh-home-product-name a {

    color:
        var(--home-ink);

}


.gh-home-product-name
a:hover {

    color:
        var(--home-green);

}


.gh-home-product-description {

    display:
        -webkit-box;

    overflow: hidden;

    margin-bottom: 18px;

    color:
        var(--home-muted);

    font-size: .82rem;

    line-height: 1.65;

    -webkit-line-clamp: 3;

    -webkit-box-orient: vertical;

}


/* =========================================================
   PRODUCT STOCK
========================================================= */

.gh-home-stock {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    width: fit-content;

    margin-bottom: 14px;

    padding:
        5px
        8px;

    border-radius: 8px;

    font-size: .68rem;

    font-weight: 700;

}


.gh-home-stock-in {

    background:
        #f0fdf4;

    color:
        #15803d;

}


.gh-home-stock-low {

    background:
        #fff7ed;

    color:
        #c2410c;

}


.gh-home-stock-out {

    background:
        #fef2f2;

    color:
        #b91c1c;

}


.gh-home-stock-dot {

    width: 6px;

    height: 6px;

    border-radius: 50%;

    background:
        currentColor;

}


/* =========================================================
   PRODUCT FOOTER
========================================================= */

.gh-home-product-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 13px;

    margin-top: auto;

    padding-top: 16px;

    border-top:
        1px solid
        var(--home-border);

}


.gh-home-product-price {

    display: block;

    color:
        var(--home-green-dark);

    font-size: 1.08rem;

    font-weight: 800;

}


.gh-home-product-unit {

    display: block;

    margin-top: 1px;

    color:
        var(--home-muted);

    font-size: .68rem;

}


.gh-home-cart-button {

    width: 44px;

    height: 44px;

    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 0;

    border: 0;

    border-radius: 13px;

    background:
        var(--home-green-dark);

    color:
        #ffffff;

    font-size: 1rem;

    cursor: pointer;

    box-shadow:
        0 8px 18px
        rgba(23,79,42,.17);

    transition:
        background-color .2s ease,
        transform .2s ease,
        box-shadow .2s ease;

}


.gh-home-cart-button:hover {

    background:
        var(--home-green);

    transform:
        translateY(-2px);

    box-shadow:
        0 11px 22px
        rgba(23,79,42,.2);

}


.gh-home-cart-button:disabled {

    background:
        #d8dfda;

    color:
        #87928a;

    cursor:
        not-allowed;

    box-shadow:
        none;

    transform:
        none;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.gh-home-empty-state {

    padding:
        65px
        25px;

    border:
        1px dashed
        rgba(23,79,42,.18);

    border-radius: 22px;

    background:
        #fbfdfb;

    color:
        var(--home-muted);

    text-align: center;

}


.gh-home-empty-icon {

    width: 58px;

    height: 58px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 16px;

    border-radius: 18px;

    background:
        var(--home-green-light);

    color:
        var(--home-green);

    font-size: 1.4rem;

}


/* =========================================================
   ABOUT SECTION
========================================================= */

.gh-home-about-grid {

    align-items: center;

}


.gh-home-about-copy {

    max-width: 630px;

}


.gh-home-about-lead {

    margin-bottom: 0;

    color:
        var(--home-muted);

    font-size:
        1rem;

    line-height: 1.8;

}


/* =========================================================
   VALUE CARDS
========================================================= */

.gh-home-values {

    margin-top: 30px;

}


.gh-home-value-card {

    height: 100%;

    padding: 18px;

    border:
        1px solid
        rgba(23,79,42,.08);

    border-radius: 17px;

    background:
        #ffffff;

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-home-value-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(47,143,70,.15);

    box-shadow:
        0 12px 30px
        rgba(23,79,42,.06);

}


.gh-home-value-icon {

    width: 41px;

    height: 41px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 13px;

    border-radius: 12px;

    background:
        var(--home-green-light);

    color:
        var(--home-green);

    font-size: 1rem;

}


.gh-home-value-card strong {

    display: block;

    margin-bottom: 4px;

    color:
        var(--home-ink);

    font-size: .9rem;

}


.gh-home-value-card small {

    color:
        var(--home-muted);

    font-size: .75rem;

}


/* =========================================================
   ABOUT IMAGE
========================================================= */

.gh-home-about-visual {

    position: relative;

    overflow: hidden;

    min-height: 480px;

    border-radius: 30px;

    background:
        var(--home-green-light);

    box-shadow:
        0 24px 55px
        rgba(23,79,42,.12);

}


.gh-home-about-visual img {

    width: 100%;

    height: 100%;

    min-height: 480px;

    object-fit: cover;

    transition:
        transform .5s
        cubic-bezier(.16,1,.3,1);

}


.gh-home-about-visual:hover img {

    transform:
        scale(1.035);

}


.gh-home-about-label {

    position: absolute;

    left: 20px;

    bottom: 20px;

    display: flex;

    align-items: center;

    gap: 10px;

    max-width: calc(
        100% - 40px
    );

    padding:
        12px
        15px;

    border:
        1px solid
        rgba(255,255,255,.55);

    border-radius: 14px;

    background:
        rgba(255,255,255,.9);

    color:
        var(--home-green-dark);

    font-size: .78rem;

    font-weight: 800;

    backdrop-filter:
        blur(12px);

    box-shadow:
        0 12px 28px
        rgba(0,0,0,.08);

}


.gh-home-about-label i {

    width: 30px;

    height: 30px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border-radius: 9px;

    background:
        var(--home-green-light);

    color:
        var(--home-green);

}


/* =========================================================
   CONTACT CTA
========================================================= */

.gh-home-contact {

    position: relative;

    overflow: hidden;

    border-radius: 30px;

    background:
        linear-gradient(
            135deg,
            #092516 0%,
            #123f24 55%,
            #17602f 100%
        );

    color: #ffffff;

    box-shadow:
        0 24px 60px
        rgba(9,37,22,.14);

}


.gh-home-contact::before {

    content: "";

    position: absolute;

    width: 340px;

    height: 340px;

    right: -170px;

    top: -190px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.045);

}


.gh-home-contact::after {

    content: "";

    position: absolute;

    width: 190px;

    height: 190px;

    left: -90px;

    bottom: -120px;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius: 50%;

}


.gh-home-contact-inner {

    position: relative;

    z-index: 2;

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        auto;

    align-items: center;

    gap: 55px;

    padding:
        clamp(
            45px,
            6vw,
            70px
        );

}


.gh-home-contact-copy {

    max-width: 690px;

}


.gh-home-contact
.gh-home-eyebrow {

    color:
        #9af2b2;

}


.gh-home-contact
.gh-home-eyebrow::before {

    background:
        #86efac;

}


.gh-home-contact h2 {

    max-width: 620px;

    margin-bottom: 15px;

    color:
        #ffffff;

    font-size:
        clamp(
            2.1rem,
            5vw,
            3.3rem
        );

    line-height: 1.05;

}


.gh-home-contact-description {

    max-width: 600px;

    margin-bottom: 25px;

    color:
        rgba(255,255,255,.65);

    line-height: 1.75;

}


.gh-home-contact-details {

    display: flex;

    flex-wrap: wrap;

    gap:
        10px
        22px;

}


.gh-home-contact-detail {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color:
        rgba(255,255,255,.72);

    font-size: .82rem;

}


.gh-home-contact-detail i {

    color:
        #86efac;

}


.gh-home-contact-action {

    min-width: 170px;

    min-height: 52px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding:
        12px
        22px;

    border-radius: 13px;

    background:
        #ffffff;

    color:
        var(--home-green-dark);

    font-size: .88rem;

    font-weight: 800;

    white-space: nowrap;

    transition:
        transform .2s ease,
        background-color .2s ease;

}


.gh-home-contact-action:hover {

    background:
        #f0fdf4;

    color:
        var(--home-green-dark);

    transform:
        translateY(-2px);

}


/* =========================================================
   TABLET
========================================================= */

@media (
    max-width: 991.98px
) {

    .gh-home-hero {

        min-height:
            620px;

    }


    .gh-home-hero-content {

        max-width:
            700px;

        padding:
            90px 0;

    }


    .gh-home-category-header,
    .gh-home-products-header {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap: 20px;

    }


    .gh-home-contact-inner {

        grid-template-columns:
            1fr;

        gap: 30px;

    }


    .gh-home-contact-action {

        width:
            fit-content;

    }


    .gh-home-about-visual,
    .gh-home-about-visual img {

        min-height:
            430px;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (
    max-width: 767.98px
) {

    .gh-home-hero {

        min-height:
            570px;

    }


    .gh-home-hero-content {

        padding:
            80px 0
            85px;

    }


    .gh-home-hero h1 {

        font-size:
            clamp(
                3rem,
                12vw,
                4.3rem
            );

    }


    .gh-home-hero-trust {

        margin-top:
            26px;

    }


    .gh-home-section {

        padding:
            68px 18px;

    }


    .gh-home-category-section {

        padding-bottom:
            58px;

    }


    .gh-home-product-image-wrap {

        aspect-ratio:
            1 / .88;

    }


    .gh-home-about-visual,
    .gh-home-about-visual img {

        min-height:
            370px;

        border-radius:
            24px;

    }


    .gh-home-contact {

        border-radius:
            24px;

    }


    .gh-home-contact-inner {

        padding:
            42px
            27px;

    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (
    max-width: 575.98px
) {

    .gh-home-hero {

        min-height:
            610px;

    }


    .gh-home-hero h1 {

        font-size:
            3.05rem;

        line-height:
            .98;

    }


    .gh-home-hero-description {

        font-size:
            .94rem;

    }


    .gh-home-hero-actions {

        flex-direction:
            column;

    }


    .gh-home-hero-primary,
    .gh-home-hero-secondary {

        width:
            100%;

    }


    .gh-home-slider-dots {

        bottom:
            20px;

    }


    .gh-home-category-filters {

        flex-wrap:
            nowrap;

        overflow-x:
            auto;

        margin-right:
            -18px;

        margin-left:
            -18px;

        padding:
            0 18px
            7px;

        scrollbar-width:
            none;

    }


    .gh-home-category-filters::-webkit-scrollbar {

        display:
            none;

    }


    .gh-home-category-filter {

        flex-shrink:
            0;

    }


    .gh-home-product-body {

        padding:
            18px;

    }


    .gh-home-about-visual,
    .gh-home-about-visual img {

        min-height:
            330px;

    }


    .gh-home-contact-action {

        width:
            100%;

    }


    .gh-home-contact-details {

        flex-direction:
            column;

        gap: 10px;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-home-hero-slide {

        transition:
            none;

        transform:
            none;

    }


    .gh-home-hero-slide.active {

        transform:
            none;

    }


    .gh-home-product-card,
    .gh-home-product-image,
    .gh-home-value-card,
    .gh-home-about-visual img {

        transition:
            none;

    }

}

</style>



<div class="gh-home-page">


<!-- =========================================================
     HERO
========================================================= -->

<section
    id="home"
    class="gh-home-hero"
>


    <!-- HERO BACKGROUNDS -->

    <div
        class="gh-home-hero-backgrounds"
        aria-hidden="true"
    >


        <?php if ($heroSlides): ?>


            <?php foreach (
                $heroSlides as
                $slideIndex => $slide
            ): ?>


                <div
                    class="
                        gh-home-hero-slide
                        <?= $slideIndex === 0
                            ? 'active'
                            : '' ?>
                    "
                    data-hero-slide="<?= $slideIndex ?>"
                    style="
                        background-image:
                        url('<?= e(url($slide)) ?>');
                    "
                ></div>


            <?php endforeach; ?>


        <?php else: ?>


            <div
                class="gh-home-hero-fallback"
            ></div>


        <?php endif; ?>


    </div>



    <!-- OVERLAY -->

    <div
        class="gh-home-hero-overlay"
        aria-hidden="true"
    ></div>



    <!-- CONTENT -->

    <div
        class="
            container
            gh-home-hero-container
        "
    >


        <div class="gh-home-hero-content">


            <span class="gh-home-hero-badge">


                <span
                    class="gh-home-hero-badge-dot"
                ></span>


                Fresh • Organic • Local


            </span>



            <h1>

                Fresh food,
                thoughtfully harvested.

            </h1>



            <p class="gh-home-hero-description">

                Shop fresh vegetables, fruits,
                grains, herbs and everyday produce
                sourced from trusted growers and
                delivered through Green Harvest.

            </p>



            <div class="gh-home-hero-actions">


                <a
                    href="#products"
                    class="gh-home-hero-primary"
                >

                    <i class="bi bi-basket"></i>

                    Shop Fresh Produce

                </a>


                <a
                    href="<?= e(
                        url(
                            'about.php'
                        )
                    ) ?>"
                    class="gh-home-hero-secondary"
                >

                    Learn About Us

                    <i class="bi bi-arrow-right"></i>

                </a>


            </div>



            <div class="gh-home-hero-trust">


                <span class="gh-home-hero-trust-item">

                    <i class="bi bi-check-circle-fill"></i>

                    Fresh selection

                </span>


                <span class="gh-home-hero-trust-item">

                    <i class="bi bi-check-circle-fill"></i>

                    Simple ordering

                </span>


                <span class="gh-home-hero-trust-item">

                    <i class="bi bi-check-circle-fill"></i>

                    Trusted sourcing

                </span>


            </div>


        </div>


    </div>



    <!-- SLIDER DOTS -->

    <?php if (
        count(
            $heroSlides
        ) > 1
    ): ?>


        <div
            class="gh-home-slider-dots"
            aria-label="Hero slideshow controls"
        >


            <?php foreach (
                $heroSlides as
                $slideIndex => $slide
            ): ?>


                <button
                    type="button"
                    class="
                        gh-home-slider-dot
                        <?= $slideIndex === 0
                            ? 'active'
                            : '' ?>
                    "
                    data-hero-dot="<?= $slideIndex ?>"
                    aria-label="Show slide <?= $slideIndex + 1 ?>"
                ></button>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</section>



<!-- =========================================================
     CATEGORIES
========================================================= -->

<section
    id="categories"
    class="
        gh-home-section
        gh-home-section-soft
        gh-home-category-section
    "
>


    <div class="container">


        <div class="gh-home-category-header">


            <div>


                <p class="gh-home-eyebrow">

                    Shop Your Way

                </p>


                <h2 class="gh-home-title">

                    Browse fresh categories.

                </h2>


                <p class="gh-home-description">

                    Filter today's Green Harvest
                    selection and quickly find the
                    type of food you're looking for.

                </p>


            </div>



            <a
                href="<?= e(
                    url(
                        'shop.php'
                    )
                ) ?>"
                class="gh-home-shop-link"
            >

                View Full Shop

                <i class="bi bi-arrow-right"></i>

            </a>


        </div>



        <div class="gh-home-category-filters">


            <button
                type="button"
                class="
                    gh-home-category-filter
                    active
                "
                data-category-filter="all"
            >

                All Products

            </button>



            <?php foreach (
                $categories as
                $category
            ): ?>


                <button
                    type="button"
                    class="gh-home-category-filter"
                    data-category-filter="<?= (int) $category['id'] ?>"
                >

                    <?= e(
                        $category['name']
                    ) ?>

                </button>


            <?php endforeach; ?>


        </div>


    </div>


</section>



<!-- =========================================================
     PRODUCTS
========================================================= -->

<section
    id="products"
    class="gh-home-section"
>


    <div class="container">


        <div class="gh-home-products-header">


            <div>


                <p class="gh-home-eyebrow">

                    Featured Harvest

                </p>


                <h2 class="gh-home-title">

                    Fresh picks for today.

                </h2>


                <p class="gh-home-description">

                    Discover what's fresh and
                    available from Green Harvest.

                </p>


            </div>



            <a
                href="<?= e(
                    url(
                        'shop.php'
                    )
                ) ?>"
                class="gh-home-shop-link"
            >

                Browse all products

                <i class="bi bi-arrow-right"></i>

            </a>


        </div>



        <?php if ($loadError): ?>


            <div
                class="
                    alert
                    alert-warning
                "
            >

                Some Green Harvest products
                could not be loaded right now.
                Please try again shortly.

            </div>


        <?php endif; ?>



        <?php if ($products): ?>


            <div
                id="productGrid"
                class="row g-4"
            >


                <?php foreach (
                    $products as
                    $product
                ): ?>


                    <?php

                    $stock =
                        (int)
                        $product['stock_quantity'];


                    $categoryId =
                        (int) (
                            $product['category_id']
                            ?? 0
                        );


                    $categoryName =
                        $product['category_name']
                        ?: 'Fresh Produce';


                    $productUrl =
                        url(
                            'product.php?id=' .
                            (int) $product['id']
                        );


                    if ($stock <= 0) {

                        $stockClass =
                            'gh-home-stock-out';

                        $stockText =
                            'Out of stock';

                    } elseif (
                        $stock <= 10
                    ) {

                        $stockClass =
                            'gh-home-stock-low';

                        $stockText =
                            'Only ' .
                            $stock .
                            ' left';

                    } else {

                        $stockClass =
                            'gh-home-stock-in';

                        $stockText =
                            'In stock';

                    }

                    ?>


                    <div
                        class="
                            col-sm-6
                            col-lg-4
                            col-xl-3
                            product-grid-item
                        "
                        data-product-category="<?= $categoryId ?>"
                    >


                        <article class="gh-home-product-card">


                            <!-- IMAGE -->

                            <a
                                href="<?= e(
                                    $productUrl
                                ) ?>"
                                class="gh-home-product-image-wrap"
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
                                    class="gh-home-product-image"
                                    loading="lazy"
                                >



                                <div class="gh-home-product-badges">


                                    <?php if (
                                        (bool)
                                        $product['is_organic']
                                    ): ?>


                                        <span
                                            class="gh-home-product-badge"
                                        >

                                            <i
                                                class="bi bi-leaf-fill"
                                            ></i>

                                            Organic

                                        </span>


                                    <?php endif; ?>



                                    <?php if (
                                        (bool)
                                        $product['is_featured']
                                    ): ?>


                                        <span
                                            class="
                                                gh-home-product-badge
                                                gh-home-product-badge-featured
                                            "
                                        >

                                            Featured

                                        </span>


                                    <?php endif; ?>


                                </div>


                            </a>



                            <!-- BODY -->

                            <div class="gh-home-product-body">


                                <span class="gh-home-product-category">

                                    <?= e(
                                        $categoryName
                                    ) ?>

                                </span>



                                <h3 class="gh-home-product-name">


                                    <a
                                        href="<?= e(
                                            $productUrl
                                        ) ?>"
                                    >

                                        <?= e(
                                            $product['name']
                                        ) ?>

                                    </a>


                                </h3>



                                <p class="gh-home-product-description">

                                    <?= e(
                                        $product['description']
                                        ?: 'Fresh, carefully selected produce from Green Harvest.'
                                    ) ?>

                                </p>



                                <!-- STOCK -->

                                <span
                                    class="
                                        gh-home-stock
                                        <?= e(
                                            $stockClass
                                        ) ?>
                                    "
                                >

                                    <span
                                        class="gh-home-stock-dot"
                                    ></span>

                                    <?= e(
                                        $stockText
                                    ) ?>

                                </span>



                                <!-- FOOTER -->

                                <div class="gh-home-product-footer">


                                    <div>


                                        <span class="gh-home-product-price">

                                            <?= money(
                                                $product['price']
                                            ) ?>

                                        </span>


                                        <span class="gh-home-product-unit">

                                            per
                                            <?= e(
                                                $product['unit']
                                                ?: 'item'
                                            ) ?>

                                        </span>


                                    </div>



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
                                                value="<?= (int) $product['id'] ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="quantity"
                                                value="1"
                                            >


                                            <input
                                                type="hidden"
                                                name="redirect"
                                                value="index.php#products"
                                            >


                                            <button
                                                type="submit"
                                                class="gh-home-cart-button"
                                                aria-label="Add <?= e($product['name']) ?> to cart"
                                                title="Add to cart"
                                            >

                                                <i
                                                    class="bi bi-plus-lg"
                                                ></i>

                                            </button>


                                        </form>


                                    <?php else: ?>


                                        <button
                                            type="button"
                                            class="gh-home-cart-button"
                                            disabled
                                            aria-label="Out of stock"
                                            title="Out of stock"
                                        >

                                            <i
                                                class="bi bi-x-lg"
                                            ></i>

                                        </button>


                                    <?php endif; ?>


                                </div>


                            </div>


                        </article>


                    </div>


                <?php endforeach; ?>


            </div>



            <!-- FILTER EMPTY -->

            <div
                id="categoryEmptyState"
                class="
                    gh-home-empty-state
                    d-none
                    mt-4
                "
            >


                <span class="gh-home-empty-icon">

                    <i class="bi bi-basket"></i>

                </span>


                <h3>

                    Nothing here yet.

                </h3>


                <p class="mb-0">

                    No active products are
                    currently available in
                    this category.

                </p>


            </div>


        <?php else: ?>


            <div class="gh-home-empty-state">


                <span class="gh-home-empty-icon">

                    <i class="bi bi-basket"></i>

                </span>


                <h3>

                    Fresh products coming soon.

                </h3>


                <p class="mb-0">

                    There are currently no active
                    products available in the
                    Green Harvest store.

                </p>


            </div>


        <?php endif; ?>


    </div>


</section>



<!-- =========================================================
     ABOUT
========================================================= -->

<section
    id="about"
    class="
        gh-home-section
        gh-home-section-soft
    "
>


    <div class="container">


        <div
            class="
                row
                g-5
                gh-home-about-grid
            "
        >


            <!-- COPY -->

            <div class="col-lg-6">


                <div class="gh-home-about-copy">


                    <p class="gh-home-eyebrow">

                        Our Story

                    </p>


                    <h2 class="gh-home-title">

                        Better food starts
                        with better sourcing.

                    </h2>


                    <p class="gh-home-about-lead">

                        Green Harvest connects households
                        with fresh produce selected from
                        trusted growers. Our focus is
                        simple: quality food, responsible
                        farming and an easier shopping
                        experience.

                    </p>



                    <div
                        class="
                            row
                            g-3
                            gh-home-values
                        "
                    >


                        <!-- FRESH -->

                        <div class="col-sm-4">


                            <div class="gh-home-value-card">


                                <span class="gh-home-value-icon">

                                    <i class="bi bi-truck"></i>

                                </span>


                                <strong>

                                    Fresh

                                </strong>


                                <small>

                                    Seasonal produce

                                </small>


                            </div>


                        </div>



                        <!-- LOCAL -->

                        <div class="col-sm-4">


                            <div class="gh-home-value-card">


                                <span class="gh-home-value-icon">

                                    <i
                                        class="bi bi-geo-alt"
                                    ></i>

                                </span>


                                <strong>

                                    Local

                                </strong>


                                <small>

                                    Trusted growers

                                </small>


                            </div>


                        </div>



                        <!-- SIMPLE -->

                        <div class="col-sm-4">


                            <div class="gh-home-value-card">


                                <span class="gh-home-value-icon">

                                    <i
                                        class="bi bi-basket"
                                    ></i>

                                </span>


                                <strong>

                                    Simple

                                </strong>


                                <small>

                                    Easy shopping

                                </small>


                            </div>


                        </div>


                    </div>



                    <a
                        href="<?= e(
                            url(
                                'about.php'
                            )
                        ) ?>"
                        class="
                            btn
                            btn-green
                            btn-lg
                            mt-4
                        "
                    >

                        Discover Our Story

                        <i class="bi bi-arrow-right"></i>

                    </a>


                </div>


            </div>



            <!-- IMAGE -->

            <div class="col-lg-6">


                <div class="gh-home-about-visual">


                    <img
                        src="https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1200&q=85"
                        alt="Fresh vegetables at a local market"
                        loading="lazy"
                    >


                    <div class="gh-home-about-label">


                        <i class="bi bi-globe"></i>


                        Fresh food.
                        Better connections.


                    </div>


                </div>


            </div>


        </div>


    </div>


</section>



<!-- =========================================================
     CONTACT CTA
========================================================= -->

<section
    id="contact"
    class="gh-home-section"
>


    <div class="container">


        <div class="gh-home-contact">


            <div class="gh-home-contact-inner">


                <!-- COPY -->

                <div class="gh-home-contact-copy">


                    <p class="gh-home-eyebrow">

                        Contact Green Harvest

                    </p>


                    <h2>

                        Need help with your
                        order or products?

                    </h2>


                    <p class="gh-home-contact-description">

                        Our Green Harvest team is
                        available to answer product,
                        account, delivery and farmer
                        partnership questions.

                    </p>



                    <div class="gh-home-contact-details">


                        <span class="gh-home-contact-detail">

                            <i
                                class="bi bi-geo-alt-fill"
                            ></i>

                            Accra, Ghana

                        </span>


                        <span class="gh-home-contact-detail">

                            <i
                                class="bi bi-envelope-fill"
                            ></i>

                            hello@greenharvest.com

                        </span>


                    </div>


                </div>



                <!-- CTA -->

                <div>


                    <a
                        href="<?= e(
                            url(
                                'contact.php'
                            )
                        ) ?>"
                        class="gh-home-contact-action"
                    >

                        Contact Us

                        <i class="bi bi-arrow-right"></i>

                    </a>


                </div>


            </div>


        </div>


    </div>


</section>


</div>



<!-- =========================================================
     HOMEPAGE JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | HERO SLIDER
        |--------------------------------------------------------------------------
        */

        const heroSlides =
            Array.from(
                document.querySelectorAll(
                    '.gh-home-hero-slide'
                )
            );


        const heroDots =
            Array.from(
                document.querySelectorAll(
                    '[data-hero-dot]'
                )
            );


        const reduceMotion =
            window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;


        let currentHeroSlide =
            0;


        let heroTimer =
            null;



        /*
         * Show specific slide.
         */
        function showHeroSlide(
            index
        ) {


            if (
                heroSlides.length === 0
            ) {

                return;

            }


            currentHeroSlide =
                (
                    index
                    +
                    heroSlides.length
                )
                %
                heroSlides.length;


            heroSlides.forEach(
                function (
                    slide,
                    slideIndex
                ) {


                    slide.classList.toggle(
                        'active',
                        slideIndex ===
                        currentHeroSlide
                    );


                }
            );


            heroDots.forEach(
                function (
                    dot,
                    dotIndex
                ) {


                    dot.classList.toggle(
                        'active',
                        dotIndex ===
                        currentHeroSlide
                    );


                }
            );


        }



        /*
         * Advance slideshow.
         */
        function nextHeroSlide() {


            showHeroSlide(
                currentHeroSlide + 1
            );


        }



        /*
         * Start automatic rotation.
         */
        function startHeroSlider() {


            if (
                heroSlides.length <= 1
                ||
                reduceMotion
            ) {

                return;

            }


            stopHeroSlider();


            heroTimer =
                window.setInterval(
                    nextHeroSlide,
                    6000
                );


        }



        /*
         * Stop automatic rotation.
         */
        function stopHeroSlider() {


            if (
                heroTimer !== null
            ) {


                window.clearInterval(
                    heroTimer
                );


                heroTimer =
                    null;


            }


        }



        /*
         * Click slider dots.
         */
        heroDots.forEach(
            function (
                dot,
                index
            ) {


                dot.addEventListener(
                    'click',
                    function () {


                        showHeroSlide(
                            index
                        );


                        startHeroSlider();


                    }
                );


            }
        );


        startHeroSlider();



        /*
        |--------------------------------------------------------------------------
        | CATEGORY FILTERING
        |--------------------------------------------------------------------------
        */

        const filterButtons =
            document.querySelectorAll(
                '[data-category-filter]'
            );


        const productCards =
            document.querySelectorAll(
                '[data-product-category]'
            );


        const emptyState =
            document.getElementById(
                'categoryEmptyState'
            );


        filterButtons.forEach(
            function (
                button
            ) {


                button.addEventListener(
                    'click',
                    function () {


                        const selected =
                            button.dataset
                                .categoryFilter
                            ||
                            'all';



                        /*
                         * Active button
                         */
                        filterButtons.forEach(
                            function (
                                item
                            ) {


                                item.classList.remove(
                                    'active'
                                );


                            }
                        );


                        button.classList.add(
                            'active'
                        );



                        /*
                         * Filter cards
                         */
                        let visibleCount =
                            0;


                        productCards.forEach(
                            function (
                                card
                            ) {


                                const productCategory =
                                    card.dataset
                                        .productCategory;


                                const show =
                                    selected === 'all'
                                    ||
                                    selected ===
                                    productCategory;


                                card.classList.toggle(
                                    'd-none',
                                    !show
                                );


                                if (show) {

                                    visibleCount++;

                                }


                            }
                        );



                        /*
                         * Empty state
                         */
                        if (
                            emptyState
                        ) {


                            emptyState.classList.toggle(
                                'd-none',
                                visibleCount !== 0
                            );


                        }



                        /*
                         * Move user to products
                         */
                        const productsSection =
                            document.getElementById(
                                'products'
                            );


                        if (
                            productsSection
                        ) {


                            productsSection.scrollIntoView({

                                behavior:
                                    reduceMotion
                                        ? 'auto'
                                        : 'smooth',

                                block:
                                    'start'

                            });


                        }


                    }
                );


            }
        );


    }
);

</script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>