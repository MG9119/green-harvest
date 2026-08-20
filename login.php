<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CUSTOMER LOGIN
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Redirect Destination
|--------------------------------------------------------------------------
*/

$redirect =
    $_POST['redirect']
    ?? $_GET['redirect']
    ?? 'account.php';


$redirect = safeRedirectPath(
    $redirect,
    'account.php'
);


/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
*/

$googleState =
    bin2hex(
        random_bytes(32)
    );


$_SESSION['google_oauth_state'] =
    $googleState;


$_SESSION['google_oauth_redirect'] =
    $redirect;


$googleClientId =
    (string) (
        getenv('GOOGLE_CLIENT_ID')
        ?: ''
    );


if (
    $googleClientId !== ''
) {

    $googleLoginUrl =
        'https://accounts.google.com/o/oauth2/v2/auth?' .
        http_build_query([
            'client_id' =>
                $googleClientId,

            'redirect_uri' =>
                url(
                    'google-callback.php'
                ),

            'response_type' =>
                'code',

            'scope' =>
                'openid email profile',

            'access_type' =>
                'online',

            'prompt' =>
                'select_account',

            'state' =>
                $googleState,
        ]);

} else {

    $googleLoginUrl =
        'https://accounts.google.com/';

}


/*
|--------------------------------------------------------------------------
| Already Logged In
|--------------------------------------------------------------------------
*/

if (
    isLoggedIn()
) {

    if (
        isAdmin()
    ) {

        redirectTo(
            'admin/dashboard.php'
        );

    }


    redirectTo(
        $redirect
    );

}


/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$email = '';


/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    /*
     * CSRF Protection
     */
    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        setFlash(
            'error',
            'Invalid login request. Please try again.'
        );


    } else {

        /*
        |--------------------------------------------------------------------------
        | Inputs
        |--------------------------------------------------------------------------
        */

        $email =
            strtolower(
                trim(
                    (string) (
                        $_POST['email']
                        ?? ''
                    )
                )
            );


        $password =
            (string) (
                $_POST['password']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            $email === ''
            ||
            $password === ''
        ) {

            setFlash(
                'error',
                'Email address and password are required.'
            );


        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            setFlash(
                'error',
                'Please enter a valid email address.'
            );


        } else {

            try {

                /*
                 * Authenticate User
                 */
                if (
                    loginUser(
                        $pdo,
                        $email,
                        $password
                    )
                ) {

                    /*
                     * Administrator
                     */
                    if (
                        isAdmin()
                    ) {

                        setFlash(
                            'success',
                            'Welcome back to Green Harvest.'
                        );


                        redirectTo(
                            'admin/dashboard.php'
                        );

                    }


                    /*
                     * Customer
                     */
                    setFlash(
                        'success',
                        'Welcome back to Green Harvest.'
                    );


                    redirectTo(
                        $redirect
                    );

                }


                /*
                 * Generic Login Error
                 */
                setFlash(
                    'error',
                    'Invalid email address or password.'
                );


            } catch (
                PDOException $e
            ) {

                error_log(
                    'Green Harvest customer login error: ' .
                    $e->getMessage()
                );


                setFlash(
                    'error',
                    'We could not complete your login. Please try again.'
                );

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Login';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   VARIABLES
========================================================= */

:root {

    --login-950: #092516;
    --login-900: #14532d;
    --login-800: #166534;
    --login-700: #15803d;
    --login-600: #16a34a;
    --login-500: #4ade80;
    --login-50: #f0fdf4;

    --login-cream: #fafcf9;
    --login-white: #ffffff;

    --login-text: #1a2a22;
    --login-muted: #66756b;

    --login-border:
        rgba(20, 83, 45, .14);

}


/* =========================================================
   HIDE PUBLIC NAVIGATION
========================================================= */

header,
nav,
footer,
.gh-navbar,
.gh-footer,
.gh-navbar-shell,
.gh-footer-shell {

    display:
        none !important;

}


*,
*::before,
*::after {

    box-sizing:
        border-box;

}


html,
body {

    min-height:
        100%;

}


body {

    margin:
        0;

    overflow-x:
        hidden;

    background:
        var(--login-cream);

    color:
        var(--login-text);

}


/* =========================================================
   MAIN LAYOUT
========================================================= */

.gh-login-shell {

    min-height:
        100vh;

}


.gh-login-layout {

    min-height:
        100vh;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.25fr)
        minmax(410px, .75fr);

}


/* =========================================================
   LEFT PANEL
========================================================= */

.gh-login-visual {

    position:
        relative;

    min-height:
        100vh;

    display:
        flex;

    flex-direction:
        column;

    justify-content:
        space-between;

    overflow:
        hidden;

    padding:
        42px
        48px
        34px;

    background:
        linear-gradient(
            135deg,
            rgba(9, 37, 22, .98),
            rgba(20, 83, 45, .94) 55%,
            rgba(22, 101, 52, .98)
        );

    color:
        var(--login-white);

}


/* Grid Pattern */

.gh-login-visual::before {

    content:
        "";

    position:
        absolute;

    inset:
        0;

    opacity:
        .4;

    background-image:
        linear-gradient(
            rgba(255,255,255,.035)
            1px,
            transparent
            1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.035)
            1px,
            transparent
            1px
        );

    background-size:
        30px
        30px;

}


/* Decorative Circle */

.gh-login-visual::after {

    content:
        "";

    position:
        absolute;

    right:
        -230px;

    bottom:
        -230px;

    width:
        500px;

    height:
        500px;

    border:
        1px solid
        rgba(255,255,255,.09);

    border-radius:
        50%;

    background:
        rgba(255,255,255,.025);

}


.gh-login-visual > * {

    position:
        relative;

    z-index:
        2;

}


/* =========================================================
   LEFT BRAND
========================================================= */

.gh-login-brand {

    width:
        fit-content;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

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
        rgba(255,255,255,.9);

    font-size:
        .68rem;

    font-weight:
        700;

    letter-spacing:
        .1em;

    text-transform:
        uppercase;

}


.gh-login-brand img {

    width:
        30px;

    height:
        30px;

    object-fit:
        contain;

    border-radius:
        8px;

}


/* =========================================================
   LEFT COPY
========================================================= */

.gh-login-copy {

    max-width:
        650px;

}


.gh-login-copy h1 {

    max-width:
        620px;

    margin:
        0;

    color:
        #ffffff;

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            3.2rem,
            5vw,
            5.2rem
        );

    font-weight:
        800;

    letter-spacing:
        -.055em;

    line-height:
        .96;

}


.gh-login-copy .accent {

    color:
        #d7f9df;

}


.gh-login-copy p {

    max-width:
        500px;

    margin:
        18px
        0
        0;

    color:
        rgba(255,255,255,.72);

    font-size:
        1rem;

    line-height:
        1.7;

}


.gh-login-visual-footer {

    color:
        rgba(255,255,255,.55);

    font-size:
        .75rem;

}


/* =========================================================
   RIGHT SIDE
========================================================= */

.gh-login-form-panel {

    position:
        relative;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        34px
        26px;

    background:
        linear-gradient(
            180deg,
            #f7faf7 0%,
            #edf7ef 100%
        );

}


/* Soft Background Shape */

.gh-login-form-panel::before {

    content:
        "";

    position:
        absolute;

    width:
        320px;

    height:
        320px;

    top:
        -150px;

    right:
        -160px;

    border-radius:
        50%;

    background:
        rgba(34,197,94,.06);

}


/* =========================================================
   LOGIN CARD
========================================================= */

.gh-login-card {

    position:
        relative;

    z-index:
        2;

    width:
        100%;

    max-width:
        390px;

    overflow:
        hidden;

    padding:
        34px
        32px
        29px;

    border:
        1px solid
        rgba(255,255,255,.85);

    border-radius:
        18px;

    background:
        rgba(255,255,255,.98);

    box-shadow:
        0 24px 60px
        rgba(9,37,22,.11);

}


/* Green accent */

.gh-login-card::before {

    content:
        "";

    position:
        absolute;

    top:
        0;

    left:
        0;

    right:
        0;

    height:
        4px;

    background:
        linear-gradient(
            90deg,
            var(--login-800),
            var(--login-600),
            var(--login-500)
        );

}


/* =========================================================
   CARD HEADER
========================================================= */

.gh-login-card-header {

    margin-bottom:
        25px;

    text-align:
        center;

}


.gh-login-logo {

    width:
        55px;

    height:
        55px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    margin:
        0 auto
        15px;

    padding:
        8px;

    border:
        1px solid
        rgba(20,83,45,.09);

    border-radius:
        16px;

    background:
        var(--login-50);

}


.gh-login-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        contain;

    border-radius:
        9px;

}


/* Small Welcome Badge */

.gh-login-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    margin-bottom:
        9px;

    padding:
        5px
        9px;

    border-radius:
        999px;

    background:
        #f0fdf4;

    color:
        var(--login-800);

    font-size:
        .59rem;

    font-weight:
        800;

    letter-spacing:
        .075em;

    text-transform:
        uppercase;

}


.gh-login-badge-dot {

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        #22c55e;

}


/* Heading */

.gh-login-heading {

    margin:
        0
        0
        5px;

    color:
        var(--login-950);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.65rem;

    font-weight:
        800;

    line-height:
        1.15;

    letter-spacing:
        -.04em;

}


.gh-login-subtitle {

    margin:
        0;

    color:
        #718078;

    font-size:
        .75rem;

    line-height:
        1.55;

}


.gh-login-subtitle a {

    color:
        var(--login-800);

    font-weight:
        750;

    text-decoration:
        none;

}


.gh-login-subtitle a:hover {

    text-decoration:
        underline;

}


/* =========================================================
   FLASH
========================================================= */

.gh-login-card .alert {

    margin-bottom:
        16px;

    padding:
        9px
        11px;

    border-radius:
        9px;

    font-size:
        .7rem;

}


/* =========================================================
   FIELDS
========================================================= */

.gh-login-field {

    margin-bottom:
        16px;

}


.gh-login-label {

    display:
        block;

    margin-bottom:
        7px;

    color:
        #25392c;

    font-size:
        .69rem;

    font-weight:
        700;

}


.gh-login-input-wrap {

    position:
        relative;

}


.gh-login-input {

    width:
        100%;

    height:
        46px;

    padding:
        0
        14px;

    border:
        1.5px solid
        transparent;

    border-radius:
        9px;

    outline:
        none;

    background:
        #f4f6f4;

    color:
        var(--login-text);

    font-family:
        'Inter',
        sans-serif;

    font-size:
        .82rem;

    transition:
        background-color .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-login-input::placeholder {

    color:
        #a0aaa3;

}


.gh-login-input:hover {

    background:
        #f0f4f1;

}


.gh-login-input:focus {

    border-color:
        #22a550;

    background:
        #ffffff;

    box-shadow:
        0 0 0 3px
        rgba(34,165,80,.1);

}


.gh-login-input-wrap
.gh-login-input {

    padding-right:
        48px;

}


/* =========================================================
   EYE BUTTON
========================================================= */

.gh-password-toggle {

    position:
        absolute;

    top:
        50%;

    right:
        8px;

    width:
        31px;

    height:
        31px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    transform:
        translateY(-50%);

    padding:
        0;

    border:
        0;

    border-radius:
        8px;

    background:
        transparent;

    color:
        #708078;

    cursor:
        pointer;

}


.gh-password-toggle:hover {

    background:
        var(--login-50);

    color:
        var(--login-800);

}


/* =========================================================
   OPTIONS
========================================================= */

.gh-login-options {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin:
        -2px
        0
        19px;

}


/* Remember Me */

.gh-login-remember {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #5f6f65;

    font-size:
        .67rem;

    font-weight:
        600;

    cursor:
        pointer;

}


.gh-login-remember input {

    width:
        15px;

    height:
        15px;

    accent-color:
        var(--login-700);

}


/* Forgot */

.gh-login-forgot {

    color:
        var(--login-800);

    font-size:
        .67rem;

    font-weight:
        700;

    text-decoration:
        none;

}


.gh-login-forgot:hover {

    text-decoration:
        underline;

}


/* =========================================================
   SIGN IN
========================================================= */

.gh-login-submit {

    width:
        100%;

    min-height:
        46px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        9px
        15px;

    border:
        0;

    border-radius:
        999px;

    background:
        linear-gradient(
            135deg,
            var(--login-700),
            var(--login-900)
        );

    color:
        #ffffff;

    font-family:
        'Inter',
        sans-serif;

    font-size:
        .8rem;

    font-weight:
        800;

    cursor:
        pointer;

    box-shadow:
        0 10px 22px
        rgba(20,83,45,.17);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.gh-login-submit:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 14px 27px
        rgba(20,83,45,.22);

}


/* =========================================================
   DIVIDER
========================================================= */

.gh-login-divider {

    position:
        relative;

    margin:
        20px
        0;

    text-align:
        center;

}


.gh-login-divider::before {

    content:
        "";

    position:
        absolute;

    top:
        50%;

    left:
        0;

    right:
        0;

    height:
        1px;

    background:
        #e5eae6;

}


.gh-login-divider span {

    position:
        relative;

    padding:
        0
        11px;

    background:
        #ffffff;

    color:
        #929d95;

    font-size:
        .61rem;

    font-weight:
        600;

}


/* =========================================================
   GOOGLE
========================================================= */

.gh-google-login {

    width:
        100%;

    min-height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        9px;

    padding:
        9px
        14px;

    border:
        1px solid
        #dfe5e1;

    border-radius:
        999px;

    background:
        #ffffff;

    color:
        #24372a;

    font-size:
        .72rem;

    font-weight:
        700;

    text-decoration:
        none;

    transition:
        border-color .2s ease,
        background-color .2s ease,
        transform .2s ease;

}


.gh-google-login:hover {

    transform:
        translateY(-1px);

    border-color:
        rgba(21,128,61,.27);

    background:
        #f8faf8;

    color:
        #166534;

}


.gh-google-icon {

    width:
        18px;

    height:
        18px;

}


/* =========================================================
   BOTTOM LINKS
========================================================= */

.gh-login-bottom {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin-top:
        19px;

    padding-top:
        17px;

    border-top:
        1px solid
        #edf0ed;

}


.gh-login-bottom-link {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        #69776e;

    font-size:
        .64rem;

    font-weight:
        650;

    text-decoration:
        none;

}


.gh-login-bottom-link i {

    color:
        var(--login-700);

}


.gh-login-bottom-link:hover {

    color:
        var(--login-800);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1080px
)
and
(
    min-width: 981px
) {

    .gh-login-layout {

        grid-template-columns:
            minmax(0, 1fr)
            430px;

    }

}


@media (
    max-width: 980px
) {

    .gh-login-layout {

        grid-template-columns:
            1fr;

    }


    .gh-login-visual {

        min-height:
            270px;

        padding:
            30px
            28px;

    }


    .gh-login-copy {

        margin:
            42px
            0
            30px;

    }


    .gh-login-copy h1 {

        font-size:
            clamp(
                2.6rem,
                8vw,
                3.8rem
            );

    }


    .gh-login-form-panel {

        min-height:
            auto;

        padding:
            48px
            22px
            55px;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-login-visual {

        min-height:
            220px;

        padding:
            22px
            20px;

    }


    .gh-login-copy {

        margin:
            28px
            0
            18px;

    }


    .gh-login-copy h1 {

        font-size:
            2.5rem;

    }


    .gh-login-copy p {

        font-size:
            .82rem;

    }


    .gh-login-visual-footer {

        display:
            none;

    }


    .gh-login-form-panel {

        padding:
            30px
            16px
            42px;

    }


    .gh-login-card {

        max-width:
            390px;

        padding:
            29px
            22px
            25px;

        border-radius:
            15px;

    }


    .gh-login-logo {

        width:
            50px;

        height:
            50px;

    }


    .gh-login-heading {

        font-size:
            1.45rem;

    }

}


@media (
    max-width: 380px
) {

    .gh-login-options {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap:
            8px;

    }


    .gh-login-bottom {

        align-items:
            flex-start;

        flex-direction:
            column;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    *,
    *::before,
    *::after {

        transition-duration:
            .01ms !important;

        animation-duration:
            .01ms !important;

    }

}

</style>



<!-- =========================================================
     CUSTOMER LOGIN
========================================================= -->

<section class="gh-login-shell">


    <div class="gh-login-layout">


        <!-- =================================================
             LEFT SIDE - UNCHANGED
        ================================================== -->

        <div class="gh-login-visual">


            <div class="gh-login-brand">


                <img
                    src="<?= e(
                        url(
                            'assets/images/placeholder.svg'
                        )
                    ) ?>"
                    alt="Green Harvest logo"
                >


                <span>

                    Green Harvest

                </span>


            </div>



            <div class="gh-login-copy">


                <h1>

                    Fresh goodness

                    <br>

                    <span class="accent">

                        starts here.

                    </span>

                </h1>


                <p>

                    Sign in to shop fresh produce,
                    manage your orders and enjoy a
                    simpler way to bring quality
                    food home.

                </p>


            </div>



            <div class="gh-login-visual-footer">

                © 2026 Green Harvest.
                Fresh • Organic • Local.

            </div>


        </div>



        <!-- =================================================
             RIGHT SIDE - NEW CARD DESIGN
        ================================================== -->

        <div class="gh-login-form-panel">


            <div class="gh-login-card">


                <!-- =========================================
                     HEADER
                ========================================== -->

                <div class="gh-login-card-header">


                    <div class="gh-login-logo">


                        <img
                            src="<?= e(
                                url(
                                    'assets/images/placeholder.svg'
                                )
                            ) ?>"
                            alt="Green Harvest"
                        >


                    </div>



                    <div class="gh-login-badge">


                        <span class="gh-login-badge-dot"></span>

                        Green Harvest


                    </div>



                    <h2 class="gh-login-heading">

                        Welcome back

                    </h2>


                    <p class="gh-login-subtitle">

                        Sign in to continue shopping.

                        <br>

                        New here?

                        <a
                            href="<?= e(
                                url(
                                    'register.php'
                                )
                            ) ?>"
                        >

                            Create an account

                        </a>

                    </p>


                </div>



                <!-- =========================================
                     FLASH
                ========================================== -->

                <?php displayFlash(); ?>



                <!-- =========================================
                     FORM
                ========================================== -->

                <form
                    method="post"
                    action="<?= e(
                        url(
                            'login.php'
                        )
                    ) ?>"
                    autocomplete="on"
                >


                    <?= csrfField() ?>


                    <input
                        type="hidden"
                        name="redirect"
                        value="<?= e(
                            $redirect
                        ) ?>"
                    >



                    <!-- EMAIL -->

                    <div class="gh-login-field">


                        <label
                            for="email"
                            class="gh-login-label"
                        >

                            Email address

                        </label>


                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= e(
                                $email
                            ) ?>"
                            class="gh-login-input"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                            autofocus
                        >


                    </div>



                    <!-- PASSWORD -->

                    <div class="gh-login-field">


                        <label
                            for="password"
                            class="gh-login-label"
                        >

                            Password

                        </label>


                        <div class="gh-login-input-wrap">


                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="gh-login-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >



                            <button
                                type="button"
                                id="togglePassword"
                                class="gh-password-toggle"
                                aria-label="Show password"
                                aria-pressed="false"
                            >

                                <i
                                    id="passwordIcon"
                                    class="bi bi-eye"
                                ></i>

                            </button>


                        </div>


                    </div>



                    <!-- OPTIONS -->

                    <div class="gh-login-options">


                        <label class="gh-login-remember">


                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >


                            <span>

                                Remember me

                            </span>


                        </label>



                        <a
                            href="<?= e(
                                url(
                                    'forgot-password.php'
                                )
                            ) ?>"
                            class="gh-login-forgot"
                        >

                            Forgot password?

                        </a>


                    </div>



                    <!-- SIGN IN -->

                    <button
                        type="submit"
                        class="gh-login-submit"
                    >

                        Sign In

                    </button>



                    <!-- DIVIDER -->

                    <div class="gh-login-divider">


                        <span>

                            or continue with

                        </span>


                    </div>



                    <!-- GOOGLE -->

                    <a
                        href="<?= e(
                            $googleLoginUrl
                        ) ?>"
                        class="gh-google-login"
                        aria-label="Sign in with Google"
                    >


                        <svg
                            class="gh-google-icon"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path
                                fill="#4285F4"
                                d="
                                    M23.49 12.275
                                    c0-.79-.07-1.55-.2-2.275
                                    H12v4.305h6.45
                                    a5.52 5.52 0 0 1-2.4 3.62
                                    v3.01h3.88
                                    c2.27-2.09
                                    3.56-5.17
                                    3.56-8.66z
                                "
                            ></path>


                            <path
                                fill="#34A853"
                                d="
                                    M12 24
                                    c3.24 0
                                    5.96-1.07
                                    7.95-2.905
                                    l-3.88-3.01
                                    c-1.08.72-2.46 1.145-4.07 1.145
                                    c-3.12 0-5.76-2.11-6.7-4.945
                                    H1.29v3.105
                                    A12 12 0 0 0 12 24z
                                "
                            ></path>


                            <path
                                fill="#FBBC05"
                                d="
                                    M5.3 14.285
                                    A7.2 7.2 0 0 1 4.93 12
                                    c0-.795.135-1.57.37-2.285
                                    V6.61H1.29
                                    A12 12 0 0 0 0 12
                                    c0 1.94.465 3.775 1.29 5.39
                                    l4.01-3.105z
                                "
                            ></path>


                            <path
                                fill="#EA4335"
                                d="
                                    M12 4.77
                                    c1.765 0 3.35.61 4.6 1.805
                                    l3.45-3.45
                                    C17.955 1.17 15.235 0 12 0
                                    A12 12 0 0 0 1.29 6.61
                                    L5.3 9.715
                                    C6.24 6.88 8.88 4.77 12 4.77z
                                "
                            ></path>


                        </svg>


                        Continue with Google


                    </a>



                    <!-- BOTTOM OPTIONS -->

                    <div class="gh-login-bottom">


                        <a
                            href="<?= e(
                                url(
                                    'admin/login.php'
                                )
                            ) ?>"
                            class="gh-login-bottom-link"
                        >

                            <i class="bi bi-shield-lock"></i>

                            Admin login

                        </a>



                        <a
                            href="<?= e(
                                url(
                                    'index.php'
                                )
                            ) ?>"
                            class="gh-login-bottom-link"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Back to store

                        </a>


                    </div>


                </form>


            </div>


        </div>


    </div>


</section>



<!-- =========================================================
     PASSWORD TOGGLE
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const password =
            document.getElementById(
                'password'
            );


        const toggle =
            document.getElementById(
                'togglePassword'
            );


        const icon =
            document.getElementById(
                'passwordIcon'
            );


        if (
            !password
            ||
            !toggle
        ) {

            return;

        }


        toggle.addEventListener(
            'click',
            function () {


                const hidden =
                    password.type ===
                    'password';


                password.type =
                    hidden
                        ? 'text'
                        : 'password';


                if (icon) {

                    icon.classList.toggle(
                        'bi-eye',
                        !hidden
                    );


                    icon.classList.toggle(
                        'bi-eye-slash',
                        hidden
                    );

                }


                toggle.setAttribute(
                    'aria-label',
                    hidden
                        ? 'Hide password'
                        : 'Show password'
                );


                toggle.setAttribute(
                    'aria-pressed',
                    hidden
                        ? 'true'
                        : 'false'
                );


                password.focus();


            }
        );


    }
);

</script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>