<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - PUBLIC FOOTER
 * =========================================================
 */

$footerLogoUrl =
    url('assets/images/placeholder.svg');

?>

</main>


<style>

/* =========================================================
   GREEN HARVEST FOOTER
========================================================= */

.gh-footer {

    position: relative;
    overflow: hidden;

    margin-top: 70px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #082516 0%,
            #0d3a20 48%,
            #14532d 100%
        );

}


.gh-footer::before {

    content: '';

    position: absolute;

    width: 430px;
    height: 430px;

    right: -180px;
    top: -220px;

    border-radius: 50%;

    background:
        rgba(34, 197, 94, .08);

}


.gh-footer::after {

    content: '';

    position: absolute;

    width: 330px;
    height: 330px;

    left: -170px;
    bottom: -200px;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, .035);

}


/* =========================================================
   FOOTER SHELL
========================================================= */

.gh-footer-shell {

    position: relative;

    z-index: 2;

    width: 100%;

    padding:
        68px
        40px
        28px;

}


/* =========================================================
   MAIN GRID
========================================================= */

.footer-grid {

    display: grid;

    grid-template-columns:
        minmax(300px, 1.45fr)
        minmax(145px, .65fr)
        minmax(170px, .75fr)
        minmax(230px, 1fr);

    column-gap:
        clamp(45px, 5vw, 90px);

    row-gap: 40px;

    align-items: start;

    padding-bottom: 45px;

}


.footer-column {

    min-width: 0;

    text-align: left;

}


/*
|--------------------------------------------------------------------------
| Equal Header Height
|--------------------------------------------------------------------------
*/

.footer-column-head {

    min-height: 48px;

    display: flex;

    align-items: center;

    margin-bottom: 18px;

}


/* =========================================================
   BRAND
========================================================= */

.footer-brand-column {

    max-width: 430px;

}


.footer-brand {

    min-height: 48px;

    display: inline-flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 18px;

    text-decoration: none;

}


.footer-logo-wrapper {

    width: 48px;
    height: 48px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

}


.footer-site-logo {

    display: block;

    width: 48px;
    height: 48px;

    object-fit: contain;

}


.footer-logo-fallback {

    width: 46px;
    height: 46px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        rgba(255, 255, 255, .12);

    border-radius: 13px;

    background:
        rgba(255, 255, 255, .10);

    color: #ffffff;

    font-size: 1.2rem;

}


.footer-brand-name {

    color: #ffffff;

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size: 1.45rem;

    font-weight: 700;

    line-height: 1.15;

    letter-spacing: -.025em;

    white-space: nowrap;

}


.footer-brand:hover
.footer-brand-name {

    color: #bbf7d0;

}


.footer-description {

    max-width: 390px;

    margin:
        0
        0
        14px;

    color:
        rgba(255, 255, 255, .68);

    font-size: .9rem;

    line-height: 1.75;

}


.footer-tagline {

    margin: 0;

    color:
        rgba(255, 255, 255, .44);

    font-size: .8rem;

    font-weight: 600;

    line-height: 1.6;

}


/* =========================================================
   COLUMN TITLES
========================================================= */

.footer-title {

    margin: 0;

    color: #86efac;

    font-family:
        'Inter',
        sans-serif;

    font-size: .72rem;

    font-weight: 800;

    line-height: 1.2;

    letter-spacing: .14em;

    text-transform: uppercase;

}


/* =========================================================
   FOOTER LINKS
========================================================= */

.footer-links {

    margin: 0;

    padding: 0;

    list-style: none;

}


.footer-links li {

    margin-bottom: 12px;

}


.footer-links li:last-child {

    margin-bottom: 0;

}


.footer-links a {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color:
        rgba(255, 255, 255, .65);

    font-size: .88rem;

    line-height: 1.5;

    text-decoration: none;

    transition:
        color 180ms ease,
        transform 180ms ease;

}


.footer-links a::before {

    content: '→';

    width: 0;

    overflow: hidden;

    color: #4ade80;

    opacity: 0;

    transition:
        width 180ms ease,
        opacity 180ms ease;

}


.footer-links a:hover {

    color: #ffffff;

    transform:
        translateX(3px);

}


.footer-links a:hover::before {

    width: 14px;

    opacity: 1;

}


/* =========================================================
   CONTACT
========================================================= */

.footer-contact-list {

    display: flex;

    flex-direction: column;

    gap: 14px;

}


.footer-contact-item {

    display: flex;

    align-items: center;

    gap: 11px;

    min-height: 32px;

    margin: 0;

    color:
        rgba(255, 255, 255, .66);

    font-size: .88rem;

    line-height: 1.5;

}


.footer-contact-icon {

    width: 32px;
    height: 32px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    flex:
        0 0 32px;

    border-radius: 9px;

    background:
        rgba(255, 255, 255, .07);

    color: #86efac;

}


.footer-contact-item a {

    color:
        rgba(255, 255, 255, .66);

    text-decoration: none;

    overflow-wrap: anywhere;

}


.footer-contact-item a:hover {

    color: #ffffff;

}


/* =========================================================
   CENTERED SOCIAL MEDIA
========================================================= */

.footer-social {

    width: 100%;

    padding:
        28px
        0
        34px;

    text-align: center;

    border-top:
        1px solid
        rgba(255, 255, 255, .07);

}


.footer-social-title {

    display: block;

    margin-bottom: 14px;

    color:
        rgba(255, 255, 255, .5);

    font-size: .7rem;

    font-weight: 800;

    letter-spacing: .12em;

    text-transform: uppercase;

}


.footer-social-links {

    display: flex;

    align-items: center;

    justify-content: center;

    flex-wrap: wrap;

    gap: 10px;

}


.footer-social-link {

    width: 42px;
    height: 42px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        rgba(255, 255, 255, .12);

    border-radius: 12px;

    background:
        rgba(255, 255, 255, .07);

    color: #ffffff;

    font-size: 1rem;

    text-decoration: none;

    transition:
        transform 180ms ease,
        background 180ms ease,
        border-color 180ms ease;

}


.footer-social-link:hover {

    color: #ffffff;

    background:
        var(--gh-green-600);

    border-color:
        var(--gh-green-500);

    transform:
        translateY(-3px);

}


/* =========================================================
   BOTTOM BAR
========================================================= */

.footer-bottom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    padding-top: 26px;

    border-top:
        1px solid
        rgba(255, 255, 255, .09);

}


.footer-bottom p {

    margin: 0;

    color:
        rgba(255, 255, 255, .43);

    font-size: .78rem;

    line-height: 1.5;

}


.footer-bottom-links {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 22px;

}


.footer-bottom-links a {

    color:
        rgba(255, 255, 255, .48);

    font-size: .78rem;

    text-decoration: none;

}


.footer-bottom-links a:hover {

    color: #ffffff;

}


/* =========================================================
   LARGE DESKTOP
========================================================= */

@media (min-width: 1600px) {

    .gh-footer-shell {

        padding-left: 50px;
        padding-right: 50px;

    }

}


/* =========================================================
   SMALL DESKTOP / TABLET
========================================================= */

@media (max-width: 1100px) {

    .footer-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        column-gap: 60px;

        row-gap: 42px;

    }


    .footer-brand-column {

        max-width: none;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 575.98px) {

    .gh-footer {

        margin-top: 50px;

    }


    .gh-footer-shell {

        padding:
            52px
            20px
            25px;

    }


    .footer-grid {

        grid-template-columns: 1fr;

        gap: 34px;

        padding-bottom: 34px;

    }


    .footer-column-head {

        min-height: auto;

        margin-bottom: 14px;

    }


    .footer-brand {

        margin-bottom: 16px;

    }


    .footer-site-logo,
    .footer-logo-wrapper {

        width: 42px;
        height: 42px;

    }


    .footer-brand-name {

        font-size: 1.25rem;

    }


    .footer-description {

        max-width: 100%;

    }


    .footer-social {

        padding:
            26px
            0
            30px;

    }


    .footer-bottom {

        flex-direction: column;

        align-items: flex-start;

        gap: 14px;

    }


    .footer-bottom-links {

        justify-content: flex-start;

        flex-wrap: wrap;

        gap:
            10px
            18px;

    }

}

</style>


<footer class="gh-footer">

    <div class="container-fluid gh-footer-shell">


        <!-- =================================================
             MAIN FOOTER GRID
        ================================================== -->

        <div class="footer-grid">


            <!-- =================================================
                 BRAND
            ================================================== -->

            <div
                class="
                    footer-column
                    footer-brand-column
                "
            >


                <a
                    href="<?= url('index.php') ?>"
                    class="footer-brand"
                    aria-label="Green Harvest home"
                >


                    <span class="footer-logo-wrapper">


                        <img
                            src="<?= e($footerLogoUrl) ?>"
                            alt="<?= e(APP_NAME) ?>"
                            class="footer-site-logo"
                            width="48"
                            height="48"
                            loading="lazy"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='inline-flex';
                            "
                        >


                        <span
                            class="footer-logo-fallback"
                            style="display:none;"
                            aria-hidden="true"
                        >

                            <i class="bi bi-leaf-fill"></i>

                        </span>


                    </span>


                    <span class="footer-brand-name">
                        <?= e(APP_NAME) ?>
                    </span>


                </a>


                <p class="footer-description">

                    Fresh, responsibly sourced foods from
                    trusted growers to your table. Green
                    Harvest makes healthy shopping simpler
                    while supporting responsible farming
                    and better communities.

                </p>


                <p class="footer-tagline">

                    Fresh food. Honest sourcing.
                    Healthier communities.

                </p>


            </div>


            <!-- =================================================
                 SHOP
            ================================================== -->

            <div class="footer-column">


                <div class="footer-column-head">

                    <h3 class="footer-title">
                        Shop
                    </h3>

                </div>


                <ul class="footer-links">


                    <li>

                        <a href="<?= url('index.php') ?>">
                            Home
                        </a>

                    </li>


                    <li>

                        <a href="<?= url('shop.php') ?>">
                            Browse Products
                        </a>

                    </li>


                    <li>

                        <a href="<?= url('category.php') ?>">
                            Categories
                        </a>

                    </li>


                    <li>

                        <a href="<?= url('cart.php') ?>">
                            Shopping Cart
                        </a>

                    </li>


                </ul>


            </div>


            <!-- =================================================
                 GREEN HARVEST
            ================================================== -->

            <div class="footer-column">


                <div class="footer-column-head">

                    <h3 class="footer-title">
                        Green Harvest
                    </h3>

                </div>


                <ul class="footer-links">


                    <li>

                        <a href="<?= url('about.php') ?>">
                            About Us
                        </a>

                    </li>


                    <li>

                        <a href="<?= url('contact.php') ?>">
                            Contact
                        </a>

                    </li>


                    <?php if (isLoggedIn()): ?>


                        <?php if (isAdmin()): ?>


                            <li>

                                <a
                                    href="<?= url(
                                        'admin/dashboard.php'
                                    ) ?>"
                                >
                                    Admin Dashboard
                                </a>

                            </li>


                        <?php else: ?>


                            <li>

                                <a
                                    href="<?= url(
                                        'account.php'
                                    ) ?>"
                                >
                                    My Account
                                </a>

                            </li>


                            <li>

                                <a
                                    href="<?= url(
                                        'orders.php'
                                    ) ?>"
                                >
                                    My Orders
                                </a>

                            </li>


                        <?php endif; ?>


                    <?php else: ?>


                        <li>

                            <a href="<?= url('login.php') ?>">
                                Sign In
                            </a>

                        </li>


                        <li>

                            <a href="<?= url('register.php') ?>">
                                Create Account
                            </a>

                        </li>


                    <?php endif; ?>


                </ul>


            </div>


            <!-- =================================================
                 CONTACT
            ================================================== -->

            <div class="footer-column">


                <div class="footer-column-head">

                    <h3 class="footer-title">
                        Contact
                    </h3>

                </div>


                <div class="footer-contact-list">


                    <div class="footer-contact-item">


                        <span class="footer-contact-icon">

                            <i class="bi bi-geo-alt"></i>

                        </span>


                        <span>
                            Accra, Ghana
                        </span>


                    </div>


                    <div class="footer-contact-item">


                        <span class="footer-contact-icon">

                            <i class="bi bi-envelope"></i>

                        </span>


                        <a
                            href="mailto:hello@greenharvest.com"
                        >
                            hello@greenharvest.com
                        </a>


                    </div>


                    <div class="footer-contact-item">


                        <span class="footer-contact-icon">

                            <i class="bi bi-people"></i>

                        </span>


                        <a href="<?= url('contact.php') ?>">
                            Farmer Partnerships
                        </a>


                    </div>


                    <div class="footer-contact-item">


                        <span class="footer-contact-icon">

                            <i class="bi bi-chat-dots"></i>

                        </span>


                        <a href="<?= url('contact.php') ?>">
                            Customer Support
                        </a>


                    </div>


                </div>


            </div>


        </div>


        <!-- =================================================
             CENTERED SOCIAL MEDIA
        ================================================== -->

        <div class="footer-social">


            <span class="footer-social-title">
                Follow Green Harvest
            </span>


            <div class="footer-social-links">


                <a
                    href="#"
                    class="footer-social-link"
                    aria-label="Facebook"
                    title="Facebook"
                >
                    <i class="bi bi-facebook"></i>
                </a>


                <a
                    href="#"
                    class="footer-social-link"
                    aria-label="Instagram"
                    title="Instagram"
                >
                    <i class="bi bi-instagram"></i>
                </a>


                <a
                    href="#"
                    class="footer-social-link"
                    aria-label="X"
                    title="X"
                >
                    <i class="bi bi-twitter-x"></i>
                </a>


                <a
                    href="#"
                    class="footer-social-link"
                    aria-label="TikTok"
                    title="TikTok"
                >
                    <i class="bi bi-tiktok"></i>
                </a>


            </div>


        </div>


        <!-- =================================================
             FOOTER BOTTOM
        ================================================== -->

        <div class="footer-bottom">


            <p>

                &copy;
                <?= date('Y') ?>
                <?= e(APP_NAME) ?>.

                Based in Accra, Ghana.
                All rights reserved.

            </p>

            <div class="footer-bottom-links">


                <a href="<?= url('about.php') ?>">
                    About
                </a>


                <a href="<?= url('contact.php') ?>">
                    Contact
                </a>


                <a href="<?= url('shop.php') ?>">
                    Shop
                </a>


            </div>


        </div>


    </div>

</footer>


<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | Quantity Increase
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('[data-qty-plus]')
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();


                        const selector =
                            button.dataset.qtyPlus;


                        if (!selector) {
                            return;
                        }


                        const input =
                            document.querySelector(selector);


                        if (!input) {
                            return;
                        }


                        const current =
                            parseInt(input.value, 10) || 1;


                        const max =
                            parseInt(input.max, 10);


                        if (
                            Number.isFinite(max) &&
                            max > 0
                        ) {

                            input.value =
                                Math.min(
                                    current + 1,
                                    max
                                );

                        } else {

                            input.value =
                                current + 1;

                        }

                    }
                );

            });


        /*
        |--------------------------------------------------------------------------
        | Quantity Decrease
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('[data-qty-minus]')
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    function (event) {

                        event.preventDefault();


                        const selector =
                            button.dataset.qtyMinus;


                        if (!selector) {
                            return;
                        }


                        const input =
                            document.querySelector(selector);


                        if (!input) {
                            return;
                        }


                        const current =
                            parseInt(input.value, 10) || 1;


                        const min =
                            parseInt(input.min, 10) || 1;


                        input.value =
                            Math.max(
                                current - 1,
                                min
                            );

                    }
                );

            });


        /*
        |--------------------------------------------------------------------------
        | Confirmation Actions
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('[data-confirm]')
            .forEach(function (element) {

                element.addEventListener(
                    'click',
                    function (event) {

                        const message =
                            element.dataset.confirm ||
                            'Are you sure?';


                        if (!window.confirm(message)) {

                            event.preventDefault();

                        }

                    }
                );

            });

    }
);

</script>


</body>
</html>