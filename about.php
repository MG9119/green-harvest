<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ABOUT PAGE
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


$pageTitle = 'About Us';

require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   ABOUT PAGE VARIABLES
========================================================= */

.about-page {
    --about-dark: var(--gh-dark, #092516);
    --about-green: var(--gh-green-700, #15803d);
    --about-green-bright: var(--gh-green-600, #16a34a);
    --about-soft: var(--gh-green-50, #f0fdf4);
    --about-muted: var(--gh-muted, #66756b);
    --about-border: var(--gh-border, rgba(20,83,45,.10));
    --about-radius: 24px;
    --about-shadow: 0 18px 48px rgba(9,37,22,.08);
}


/* =========================================================
   HERO
========================================================= */

.about-page-hero {
    position: relative;
    overflow: hidden;
    padding: 88px 20px 82px;
    background:
        linear-gradient(
            135deg,
            #edf9f0 0%,
            #f8fcf9 55%,
            #ffffff 100%
        );
    border-bottom: 1px solid rgba(20,83,45,.06);
}


.about-page-hero::before {
    content: "";
    position: absolute;
    width: 430px;
    height: 430px;
    top: -270px;
    right: -110px;
    border-radius: 50%;
    background: rgba(22,163,74,.055);
}


.about-page-hero::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: 17%;
    bottom: -140px;
    border-radius: 50%;
    border: 1px solid rgba(22,163,74,.09);
}


.about-hero-content {
    position: relative;
    z-index: 2;
    max-width: 850px;
}


.about-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    padding: 7px 12px;
    border: 1px solid rgba(22,163,74,.12);
    border-radius: 999px;
    background: rgba(255,255,255,.75);
    color: var(--about-green);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .10em;
    text-transform: uppercase;
}


.about-hero-tag-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--about-green-bright);
}


.about-page-hero h1 {
    max-width: 800px;
    margin-bottom: 18px;
    color: var(--about-dark);
    font-size: clamp(3rem, 7vw, 5.2rem);
    line-height: .98;
    letter-spacing: -.055em;
}


.about-hero-description {
    max-width: 650px;
    margin: 0;
    color: var(--about-muted);
    font-size: 1.04rem;
    line-height: 1.75;
}


/* =========================================================
   GENERAL
========================================================= */

.about-section {
    padding: 92px 20px;
}


.about-section-soft {
    background: #f6faf7;
}


.about-section-heading {
    max-width: 720px;
    margin-bottom: 48px;
}


.about-section-heading.centered {
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}


.about-eyebrow {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
    color: var(--about-green);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
}


.about-section-heading.centered .about-eyebrow {
    justify-content: center;
}


.about-eyebrow::before {
    content: "";
    width: 24px;
    height: 2px;
    border-radius: 99px;
    background: var(--about-green-bright);
}


.about-section-heading h2,
.about-intro-copy h2 {
    margin-bottom: 14px;
    color: var(--about-dark);
    font-size: clamp(2.1rem, 5vw, 3.3rem);
    line-height: 1.07;
    letter-spacing: -.04em;
}


.about-section-heading p {
    max-width: 650px;
    margin: 0;
    color: var(--about-muted);
    line-height: 1.75;
}


.about-section-heading.centered p {
    margin-left: auto;
    margin-right: auto;
}


/* =========================================================
   INTRO
========================================================= */

.about-intro-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) minmax(0,.92fr);
    gap: clamp(45px,7vw,90px);
    align-items: center;
}


.about-intro-copy {
    max-width: 680px;
}


.about-intro-copy p:not(.about-eyebrow) {
    margin-bottom: 17px;
    color: var(--about-muted);
    font-size: .96rem;
    line-height: 1.85;
}


.about-intro-action {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-top: 10px;
}


/* =========================================================
   INTRO VISUAL
========================================================= */

.about-visual {
    position: relative;
    min-height: 490px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    padding: 42px;
    border-radius: 30px;
    background:
        radial-gradient(
            circle at 82% 18%,
            rgba(134,239,172,.27),
            transparent 29%
        ),
        linear-gradient(
            145deg,
            #092516 0%,
            #0f4326 52%,
            #166534 100%
        );
    box-shadow: 0 28px 65px rgba(9,37,22,.16);
}


.about-visual::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    top: -115px;
    right: -70px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.14);
}


.about-visual::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    left: -65px;
    bottom: 45px;
    border-radius: 50%;
    background: rgba(255,255,255,.035);
}


.about-visual-pattern {
    position: absolute;
    inset: 0;
    opacity: .25;
    background-image:
        linear-gradient(
            rgba(255,255,255,.045) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.045) 1px,
            transparent 1px
        );
    background-size: 32px 32px;
}


.about-visual-content {
    position: relative;
    z-index: 3;
    max-width: 500px;
}


.about-visual-icon {
    width: 68px;
    height: 68px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 20px;
    background: rgba(255,255,255,.09);
    color: #86efac;
    font-size: 1.8rem;
}


.about-visual h3 {
    max-width: 440px;
    margin-bottom: 13px;
    color: #fff;
    font-size: clamp(1.8rem,4vw,2.55rem);
    line-height: 1.08;
}


.about-visual p {
    max-width: 460px;
    margin: 0;
    color: rgba(255,255,255,.7);
    font-size: .9rem;
    line-height: 1.75;
}


.about-visual-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 24px;
}


.about-visual-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.78);
    font-size: .7rem;
    font-weight: 700;
}


/* =========================================================
   MISSION
========================================================= */

.about-mission-card {
    position: relative;
    height: 100%;
    overflow: hidden;
    padding: 28px;
    border: 1px solid var(--about-border);
    border-radius: var(--about-radius);
    background: #fff;
    box-shadow: 0 8px 28px rgba(9,37,22,.035);
    transition: .25s ease;
}


.about-mission-card:hover {
    transform: translateY(-5px);
    border-color: rgba(22,163,74,.18);
    box-shadow: var(--about-shadow);
}


.about-mission-icon {
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 21px;
    border-radius: 15px;
    background: var(--about-soft);
    color: var(--about-green);
    font-size: 1.22rem;
}


.about-mission-card h3 {
    margin-bottom: 9px;
    color: var(--about-dark);
    font-size: 1.12rem;
}


.about-mission-card p {
    margin: 0;
    color: var(--about-muted);
    font-size: .84rem;
    line-height: 1.72;
}


/* =========================================================
   VALUES
========================================================= */

.about-values-grid {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap: 22px;
}


.about-value-card {
    min-height: 230px;
    padding: 30px;
    border: 1px solid var(--about-border);
    border-radius: var(--about-radius);
    background:
        linear-gradient(
            145deg,
            #fff,
            #f8fcf9
        );
    transition: .25s ease;
}


.about-value-card:hover {
    transform: translateY(-4px);
    border-color: rgba(22,163,74,.18);
    box-shadow: 0 18px 42px rgba(9,37,22,.07);
}


.about-value-number {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
    border-radius: 14px;
    background: var(--about-soft);
    color: var(--about-green);
    font-size: .92rem;
    font-weight: 800;
}


.about-value-card h3 {
    margin-bottom: 9px;
    color: var(--about-dark);
    font-size: 1.15rem;
}


.about-value-card p {
    margin: 0;
    color: var(--about-muted);
    font-size: .85rem;
    line-height: 1.72;
}


/* =========================================================
   WHY GREEN HARVEST
========================================================= */

.about-why-section {
    position: relative;
    overflow: hidden;
    padding: 94px 20px;
    background:
        linear-gradient(
            145deg,
            #071d10,
            #092516 50%,
            #123d23
        );
    color: #fff;
}


.about-why-heading {
    max-width: 720px;
    margin-bottom: 48px;
}


.about-why-heading .about-eyebrow {
    color: #86efac;
}


.about-why-heading .about-eyebrow::before {
    background: #86efac;
}


.about-why-heading h2 {
    margin-bottom: 13px;
    color: #fff;
    font-size: clamp(2.2rem,5vw,3.3rem);
}


.about-why-heading p {
    max-width: 620px;
    margin: 0;
    color: rgba(255,255,255,.62);
    line-height: 1.75;
}


.about-why-item {
    height: 100%;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 24px;
    border: 1px solid rgba(255,255,255,.09);
    border-radius: 19px;
    background: rgba(255,255,255,.035);
    transition: .22s ease;
}


.about-why-item:hover {
    transform: translateY(-3px);
    border-color: rgba(134,239,172,.16);
    background: rgba(255,255,255,.055);
}


.about-why-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: rgba(134,239,172,.10);
    color: #86efac;
    font-size: 1.05rem;
}


.about-why-item h3 {
    margin-bottom: 6px;
    color: #fff;
    font-size: 1rem;
}


.about-why-item p {
    margin: 0;
    color: rgba(255,255,255,.59);
    font-size: .81rem;
    line-height: 1.68;
}


/* =========================================================
   PROCESS
========================================================= */

.about-process-card {
    height: 100%;
    padding: 29px;
    border: 1px solid var(--about-border);
    border-radius: var(--about-radius);
    background: #fff;
    box-shadow: 0 6px 22px rgba(9,37,22,.03);
    transition: .24s ease;
}


.about-process-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 42px rgba(9,37,22,.07);
}


.about-process-number {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 21px;
    border-radius: 14px;
    background: var(--about-green);
    color: #fff;
    font-size: .82rem;
    font-weight: 800;
}


.about-process-card h3 {
    margin-bottom: 8px;
    color: var(--about-dark);
    font-size: 1.08rem;
}


.about-process-card p {
    margin: 0;
    color: var(--about-muted);
    font-size: .82rem;
    line-height: 1.72;
}


/* =========================================================
   TEAM SECTION
========================================================= */

.about-team-section {
    position: relative;
    overflow: hidden;
    padding: 92px 20px;
    background:
        linear-gradient(
            180deg,
            #f4faf5 0%,
            #ffffff 100%
        );
    border-top: 1px solid rgba(20,83,45,.05);
    border-bottom: 1px solid rgba(20,83,45,.05);
}


.about-team-section::before {
    content: "";
    position: absolute;
    width: 420px;
    height: 420px;
    right: -240px;
    top: -240px;
    border-radius: 50%;
    background: rgba(22,163,74,.05);
}


.about-team-grid {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: repeat(5,minmax(0,1fr));
    gap: 18px;
}


/* Individual Team Card */

.about-team-card {
    position: relative;
    height: 100%;
    padding: 27px 18px 24px;
    overflow: hidden;
    border: 1px solid var(--about-border);
    border-radius: 21px;
    background: #fff;
    text-align: center;
    box-shadow: 0 8px 26px rgba(9,37,22,.035);
    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}


.about-team-card::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    right: -70px;
    bottom: -70px;
    border-radius: 50%;
    background: rgba(22,163,74,.045);
}


.about-team-card:hover {
    transform: translateY(-5px);
    border-color: rgba(22,163,74,.20);
    box-shadow: 0 18px 42px rgba(9,37,22,.075);
}


/* Icon Avatar */

.about-team-avatar {
    position: relative;
    z-index: 2;
    width: 82px;
    height: 82px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    border: 1px solid rgba(22,163,74,.10);
    border-radius: 50%;
    background:
        linear-gradient(
            145deg,
            #e8f8eb,
            #f7fcf8
        );
    color: var(--about-green);
    font-size: 2rem;
    box-shadow:
        inset 0 0 0 6px rgba(255,255,255,.65),
        0 10px 24px rgba(20,83,45,.08);
}


/* Different subtle icon backgrounds */

.about-team-card:nth-child(2)
.about-team-avatar {
    background:
        linear-gradient(
            145deg,
            #edf8f0,
            #ffffff
        );
}


.about-team-card:nth-child(3)
.about-team-avatar {
    background:
        linear-gradient(
            145deg,
            #eaf7ed,
            #f9fcfa
        );
}


.about-team-card:nth-child(4)
.about-team-avatar {
    background:
        linear-gradient(
            145deg,
            #f0faf2,
            #ffffff
        );
}


.about-team-card:nth-child(5)
.about-team-avatar {
    background:
        linear-gradient(
            145deg,
            #e8f6eb,
            #fafffb
        );
}


.about-team-card h3 {
    position: relative;
    z-index: 2;
    margin: 0 0 5px;
    color: var(--about-dark);
    font-size: .99rem;
    font-weight: 800;
    line-height: 1.3;
}


.about-team-role {
    position: relative;
    z-index: 2;
    margin: 0;
    color: var(--about-green);
    font-size: .69rem;
    font-weight: 700;
    line-height: 1.5;
}


/* =========================================================
   CTA
========================================================= */

.about-cta-section {
    padding: 24px 20px 95px;
}


.about-cta {
    position: relative;
    overflow: hidden;
    padding: clamp(50px,7vw,76px) 35px;
    border-radius: 30px;
    background:
        linear-gradient(
            135deg,
            #092516 0%,
            #14532d 55%,
            #15803d 100%
        );
    text-align: center;
    box-shadow: 0 24px 60px rgba(9,37,22,.14);
}


.about-cta::before {
    content: "";
    position: absolute;
    width: 330px;
    height: 330px;
    top: -190px;
    right: -90px;
    border-radius: 50%;
    background: rgba(255,255,255,.045);
}


.about-cta-content {
    position: relative;
    z-index: 2;
    max-width: 720px;
    margin: 0 auto;
}


.about-cta .about-eyebrow {
    justify-content: center;
    color: #bbf7d0;
}


.about-cta .about-eyebrow::before {
    background: #86efac;
}


.about-cta h2 {
    margin-bottom: 15px;
    color: #fff;
    font-size: clamp(2.2rem,5vw,3.4rem);
    line-height: 1.06;
}


.about-cta p {
    max-width: 610px;
    margin: 0 auto 28px;
    color: rgba(255,255,255,.67);
    line-height: 1.75;
}


.about-cta-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 11px;
}


.about-white-button,
.about-outline-button {
    min-height: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 11px 24px;
    border-radius: 12px;
    font-size: .88rem;
    font-weight: 800;
    text-decoration: none;
    transition: .2s ease;
}


.about-white-button {
    border: 1px solid #fff;
    background: #fff;
    color: var(--about-dark);
}


.about-white-button:hover {
    background: #f0fdf4;
    color: var(--about-dark);
    transform: translateY(-2px);
}


.about-outline-button {
    border: 1px solid rgba(255,255,255,.35);
    background: rgba(255,255,255,.04);
    color: #fff;
}


.about-outline-button:hover {
    background: rgba(255,255,255,.10);
    color: #fff;
    transform: translateY(-2px);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1199.98px) {

    .about-team-grid {
        grid-template-columns:
            repeat(3,minmax(0,1fr));
    }

}


@media (max-width: 991.98px) {

    .about-intro-grid {
        grid-template-columns: 1fr;
        gap: 42px;
    }


    .about-visual {
        min-height: 430px;
    }

}


@media (max-width: 767.98px) {

    .about-section,
    .about-why-section,
    .about-team-section {
        padding: 68px 18px;
    }


    .about-page-hero {
        padding: 65px 18px 60px;
    }


    .about-values-grid {
        grid-template-columns: 1fr;
    }


    .about-team-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }


    .about-visual {
        min-height: 390px;
        padding: 30px;
        border-radius: 24px;
    }


    .about-cta-section {
        padding: 15px 18px 72px;
    }

}


@media (max-width: 575.98px) {

    .about-page-hero {
        padding: 55px 18px;
    }


    .about-page-hero h1 {
        font-size: 2.8rem;
    }


    .about-section,
    .about-why-section,
    .about-team-section {
        padding: 60px 18px;
    }


    .about-team-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }


    .about-team-card {
        max-width: 360px;
        width: 100%;
        margin: 0 auto;
        padding: 25px 20px;
    }


    .about-team-avatar {
        width: 76px;
        height: 76px;
        font-size: 1.8rem;
    }


    .about-cta {
        padding: 47px 22px;
    }


    .about-cta-actions {
        flex-direction: column;
    }


    .about-white-button,
    .about-outline-button {
        width: 100%;
    }

}


@media (prefers-reduced-motion: reduce) {

    .about-mission-card,
    .about-value-card,
    .about-process-card,
    .about-team-card,
    .about-why-item {
        transition: none;
    }

}

</style>



<div class="about-page">


<!-- =========================================================
     HERO
========================================================= -->

<section class="about-page-hero">

    <div class="container">

        <div class="about-hero-content">

            <span class="about-hero-tag">

                <span class="about-hero-tag-dot"></span>

                About Green Harvest

            </span>


            <h1>

                Fresh food.
                Better choices.

            </h1>


            <p class="about-hero-description">

                Green Harvest is building a simpler way
                for households to discover fresh food,
                shop conveniently and connect with
                trusted food suppliers.

            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     OUR STORY
========================================================= -->

<section class="about-section">

    <div class="container">

        <?php displayFlash(); ?>


        <div class="about-intro-grid">


            <div class="about-intro-copy">

                <p class="about-eyebrow">
                    Our Story
                </p>


                <h2>

                    Making fresh,
                    organic-focused shopping simpler.

                </h2>


                <p>

                    Green Harvest is an online food
                    marketplace created to make it easier
                    for customers to discover and purchase
                    fresh food from one convenient platform.

                </p>


                <p>

                    Our goal is to connect people with
                    quality produce while creating
                    opportunities for farmers and food
                    suppliers to reach customers through
                    digital commerce.

                </p>


                <p>

                    From browsing products and categories
                    to managing a shopping cart, placing an
                    order and tracking its status,
                    Green Harvest is designed around a
                    simple and convenient customer
                    experience.

                </p>


                <a
                    href="<?= e(url('shop.php')) ?>"
                    class="btn btn-green btn-lg about-intro-action"
                >

                    Explore Our Products

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>



            <div class="about-visual">

                <div
                    class="about-visual-pattern"
                    aria-hidden="true"
                ></div>


                <div class="about-visual-content">

                    <span class="about-visual-icon">

                        <i class="bi bi-leaf-fill"></i>

                    </span>


                    <h3>

                        Better access to fresh food
                        starts with better connections.

                    </h3>


                    <p>

                        Green Harvest brings products,
                        customers and suppliers together
                        through a simple digital marketplace.

                    </p>


                    <div class="about-visual-tags">

                        <span class="about-visual-tag">
                            <i class="bi bi-basket"></i>
                            Fresh produce
                        </span>

                        <span class="about-visual-tag">
                            <i class="bi bi-phone"></i>
                            Simple ordering
                        </span>

                        <span class="about-visual-tag">
                            <i class="bi bi-people"></i>
                            Better access
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     MISSION
========================================================= -->

<section class="about-section about-section-soft">

    <div class="container">

        <div class="about-section-heading">

            <p class="about-eyebrow">
                Our Mission
            </p>


            <h2>
                Building a better food marketplace.
            </h2>


            <p>

                Green Harvest focuses on accessibility,
                responsible sourcing, customer convenience
                and stronger connections across the food
                supply chain.

            </p>

        </div>


        <div class="row g-4">


            <div class="col-md-6 col-xl-3">

                <article class="about-mission-card">

                    <span class="about-mission-icon">
                        <i class="bi bi-people"></i>
                    </span>

                    <h3>
                        Support Producers
                    </h3>

                    <p>

                        Create a digital channel through
                        which farmers and food suppliers
                        can connect with customers.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-mission-card">

                    <span class="about-mission-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </span>

                    <h3>
                        Better Choices
                    </h3>

                    <p>

                        Make fresh and organic-focused food
                        easier to discover and purchase.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-mission-card">

                    <span class="about-mission-icon">
                        <i class="bi bi-phone"></i>
                    </span>

                    <h3>
                        Convenience
                    </h3>

                    <p>

                        Give customers a simple way to
                        browse, order and manage purchases.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-mission-card">

                    <span class="about-mission-icon">
                        <i class="bi bi-globe"></i>
                    </span>

                    <h3>
                        Sustainability
                    </h3>

                    <p>

                        Encourage responsible food choices
                        and better supply relationships.

                    </p>

                </article>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     VALUES
========================================================= -->

<section class="about-section">

    <div class="container">

        <div class="about-section-heading">

            <p class="about-eyebrow">
                What We Value
            </p>


            <h2>
                Principles behind Green Harvest.
            </h2>

        </div>


        <div class="about-values-grid">


            <article class="about-value-card">

                <span class="about-value-number">
                    01
                </span>

                <h3>
                    Quality
                </h3>

                <p>

                    Present customers with products that
                    are clearly described and supported
                    with useful pricing and stock information.

                </p>

            </article>


            <article class="about-value-card">

                <span class="about-value-number">
                    02
                </span>

                <h3>
                    Transparency
                </h3>

                <p>

                    Give customers clear product, pricing,
                    delivery and order information.

                </p>

            </article>


            <article class="about-value-card">

                <span class="about-value-number">
                    03
                </span>

                <h3>
                    Community
                </h3>

                <p>

                    Build connections between customers,
                    food producers and businesses.

                </p>

            </article>


            <article class="about-value-card">

                <span class="about-value-number">
                    04
                </span>

                <h3>
                    Sustainability
                </h3>

                <p>

                    Encourage responsible food production,
                    purchasing and supply practices.

                </p>

            </article>

        </div>

    </div>

</section>



<!-- =========================================================
     WHY GREEN HARVEST
========================================================= -->

<section class="about-why-section">

    <div class="container">

        <div class="about-why-heading">

            <p class="about-eyebrow">
                Why Green Harvest
            </p>

            <h2>
                Designed around the customer.
            </h2>

            <p>

                Green Harvest combines product discovery,
                ordering and customer account tools in one
                straightforward shopping experience.

            </p>

        </div>


        <div class="row g-4">


            <div class="col-md-6">

                <article class="about-why-item">

                    <span class="about-why-icon">
                        <i class="bi bi-basket"></i>
                    </span>

                    <div>

                        <h3>
                            Fresh Product Selection
                        </h3>

                        <p>

                            Browse vegetables, fruits,
                            grains and other products through
                            organized categories.

                        </p>

                    </div>

                </article>

            </div>


            <div class="col-md-6">

                <article class="about-why-item">

                    <span class="about-why-icon">
                        <i class="bi bi-box-seam"></i>
                    </span>

                    <div>

                        <h3>
                            Clear Availability
                        </h3>

                        <p>

                            See product prices, units and
                            stock information before ordering.

                        </p>

                    </div>

                </article>

            </div>


            <div class="col-md-6">

                <article class="about-why-item">

                    <span class="about-why-icon">
                        <i class="bi bi-cart-check"></i>
                    </span>

                    <div>

                        <h3>
                            Simple Ordering
                        </h3>

                        <p>

                            Add products to your basket,
                            review quantities and complete
                            checkout easily.

                        </p>

                    </div>

                </article>

            </div>


            <div class="col-md-6">

                <article class="about-why-item">

                    <span class="about-why-icon">
                        <i class="bi bi-receipt"></i>
                    </span>

                    <div>

                        <h3>
                            Order History
                        </h3>

                        <p>

                            Registered customers can review
                            previous orders and track status.

                        </p>

                    </div>

                </article>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section class="about-section">

    <div class="container">

        <div class="about-section-heading">

            <p class="about-eyebrow">
                How It Works
            </p>

            <h2>
                From harvest to your basket.
            </h2>

            <p>

                Shopping with Green Harvest is designed
                to be simple from discovery to delivery.

            </p>

        </div>


        <div class="row g-4">


            <div class="col-md-6 col-xl-3">

                <article class="about-process-card">

                    <span class="about-process-number">
                        01
                    </span>

                    <h3>
                        Browse
                    </h3>

                    <p>

                        Explore products using the shop,
                        categories or search feature.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-process-card">

                    <span class="about-process-number">
                        02
                    </span>

                    <h3>
                        Add to Basket
                    </h3>

                    <p>

                        Select products and choose the
                        quantities you want.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-process-card">

                    <span class="about-process-number">
                        03
                    </span>

                    <h3>
                        Checkout
                    </h3>

                    <p>

                        Enter delivery details and place
                        your order.

                    </p>

                </article>

            </div>


            <div class="col-md-6 col-xl-3">

                <article class="about-process-card">

                    <span class="about-process-number">
                        04
                    </span>

                    <h3>
                        Track
                    </h3>

                    <p>

                        Review your order and monitor
                        its current status.

                    </p>

                </article>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     TEAM
========================================================= -->

<section class="about-team-section">

    <div class="container">

        <div class="about-section-heading centered">

            <p class="about-eyebrow">
                Our Team
            </p>


            <h2>
                Meet the team behind Green Harvest.
            </h2>


            <p>

                A focused team working together to create
                a better digital marketplace for fresh
                food, customers and suppliers.

            </p>

        </div>



        <div class="about-team-grid">


            <!-- MEMBER 1 -->

            <article class="about-team-card">

                <div class="about-team-avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <h3>
                    Kwame Mensah
                </h3>


                <p class="about-team-role">
                    Founder &amp; CEO
                </p>

            </article>



            <!-- MEMBER 2 -->

            <article class="about-team-card">

                <div class="about-team-avatar">

                    <i class="bi bi-person-workspace"></i>

                </div>


                <h3>
                    Ama Boateng
                </h3>


                <p class="about-team-role">
                    Operations Manager
                </p>

            </article>



            <!-- MEMBER 3 -->

            <article class="about-team-card">

                <div class="about-team-avatar">

                    <i class="bi bi-code-slash"></i>

                </div>


                <h3>
                    Daniel Owusu
                </h3>


                <p class="about-team-role">
                    Technology Lead
                </p>

            </article>



            <!-- MEMBER 4 -->

            <article class="about-team-card">

                <div class="about-team-avatar">

                    <i class="bi bi-headset"></i>

                </div>


                <h3>
                    Efua Asare
                </h3>


                <p class="about-team-role">
                    Customer Experience
                </p>

            </article>



            <!-- MEMBER 5 -->

            <article class="about-team-card">

                <div class="about-team-avatar">

                    <i class="bi bi-box-seam"></i>

                </div>


                <h3>
                    Michael Addo
                </h3>


                <p class="about-team-role">
                    Supply &amp; Partnerships
                </p>

            </article>


        </div>

    </div>

</section>



<!-- =========================================================
     CTA
========================================================= -->

<section class="about-cta-section">

    <div class="container">

        <div class="about-cta">

            <div class="about-cta-content">

                <p class="about-eyebrow">
                    Start Shopping
                </p>


                <h2>
                    Ready to fill your basket?
                </h2>


                <p>

                    Explore Green Harvest and discover
                    the products currently available in
                    our marketplace.

                </p>


                <div class="about-cta-actions">

                    <a
                        href="<?= e(url('shop.php')) ?>"
                        class="about-white-button"
                    >

                        Browse Products

                        <i class="bi bi-arrow-right"></i>

                    </a>


                    <a
                        href="<?= e(url('contact.php')) ?>"
                        class="about-outline-button"
                    >

                        Contact Us

                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>