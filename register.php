<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CUSTOMER REGISTRATION
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
| Already Logged In
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    if (isAdmin()) {

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

$name = '';
$email = '';
$phone = '';


/*
|--------------------------------------------------------------------------
| Handle Registration
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
            'Invalid registration request. Please try again.'
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Form Values
        |--------------------------------------------------------------------------
        */

        $name = trim(
            (string) (
                $_POST['full_name']
                ?? ''
            )
        );


        $email = strtolower(
            trim(
                (string) (
                    $_POST['email']
                    ?? ''
                )
            )
        );


        $phone = normalizePhoneNumber(
            (string) (
                $_POST['phone']
                ?? ''
            )
        );


        $password =
            (string) (
                $_POST['password']
                ?? ''
            );


        $confirmPassword =
            (string) (
                $_POST['confirm_password']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            $name === ''
            ||
            $email === ''
            ||
            $phone === ''
            ||
            $password === ''
            ||
            $confirmPassword === ''
        ) {

            setFlash(
                'error',
                'Please complete all required fields.'
            );


        } elseif (
            strlen($name) < 2
        ) {

            setFlash(
                'error',
                'Please enter your full name.'
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


        } elseif (
            $phone === ''
            ||
            !preg_match(
                '/^\+[1-9]\d{6,14}$/',
                $phone
            )
        ) {

            setFlash(
                'error',
                'Please enter a valid phone number.'
            );


        /*
        |--------------------------------------------------------------------------
        | Password Requirements
        |--------------------------------------------------------------------------
        */

        } elseif (
            strlen($password) < 8
        ) {

            setFlash(
                'error',
                'Password must be at least 8 characters.'
            );


        } elseif (
            strlen($password) > 128
        ) {

            setFlash(
                'error',
                'Password must not exceed 128 characters.'
            );


        } elseif (
            !preg_match(
                '/[A-Z]/',
                $password
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one uppercase letter.'
            );


        } elseif (
            !preg_match(
                '/[a-z]/',
                $password
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one lowercase letter.'
            );


        } elseif (
            !preg_match(
                '/[0-9]/',
                $password
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one number.'
            );


        } elseif (
            !preg_match(
                '/[^A-Za-z0-9\s]/',
                $password
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one special character.'
            );


        } elseif (
            preg_match(
                '/\s/',
                $password
            )
        ) {

            setFlash(
                'error',
                'Password must not contain spaces.'
            );


        } elseif (
            $password !==
            $confirmPassword
        ) {

            setFlash(
                'error',
                'Passwords do not match.'
            );


        } else {

            /*
            |--------------------------------------------------------------------------
            | Register Customer
            |--------------------------------------------------------------------------
            */

            $result = registerUser(
                $pdo,
                $name,
                $email,
                $phone,
                $password
            );


            if (
                $result['success']
                ?? false
            ) {

                setFlash(
                    'success',
                    'Your Green Harvest account has been created successfully.'
                );


                redirectTo(
                    $redirect
                );
            }


            setFlash(
                'error',
                $result['error']
                ?? 'Registration could not be completed.'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Create Account';

?>


<style>

@import url(
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap'
);


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --register-950: #092516;
    --register-900: #14532d;
    --register-800: #166534;
    --register-700: #15803d;
    --register-600: #16a34a;
    --register-500: #4ade80;

    --register-50: #f0fdf4;
    --register-cream: #fafcf9;

    --register-white: #ffffff;

    --register-text: #1a2a22;
    --register-muted: #66756b;

    --register-border:
        rgba(20, 83, 45, .14);

    --register-danger:
        #dc2626;

}


/* =========================================================
   BASE
========================================================= */

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
        var(--register-cream);

    color:
        var(--register-text);

    font-family:
        'Inter',
        'Segoe UI',
        sans-serif;

}


body,
input,
button,
select,
textarea {

    font-family:
        'Inter',
        'Segoe UI',
        sans-serif;

}


/* =========================================================
   HIDE NORMAL WEBSITE ELEMENTS
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


/* =========================================================
   MAIN LAYOUT
========================================================= */

.gh-register-shell {

    min-height:
        100vh;

}


.gh-register-layout {

    min-height:
        100vh;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.3fr)
        minmax(400px, .7fr);

}


/* =========================================================
   LEFT VISUAL
========================================================= */

.gh-register-visual {

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
        52px
        56px
        42px;

    background:
        linear-gradient(
            135deg,
            rgba(9, 37, 22, .98),
            rgba(20, 83, 45, .94) 55%,
            rgba(22, 101, 52, .98)
        );

    color:
        #ffffff;

}


.gh-register-visual::before {

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
        30px 30px;

}


.gh-register-visual::after {

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
        rgba(255,255,255,.08);

    border-radius:
        50%;

    background:
        rgba(255,255,255,.025);

}


.gh-register-visual
> * {

    position:
        relative;

    z-index:
        2;

}


/* =========================================================
   LEFT BRAND
========================================================= */

.gh-register-brand {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    width:
        fit-content;

    padding:
        8px
        14px;

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


.gh-register-brand img {

    width:
        29px;

    height:
        29px;

    object-fit:
        contain;

    border-radius:
        8px;

}


/* =========================================================
   LEFT COPY
========================================================= */

.gh-register-copy {

    max-width:
        630px;

}


.gh-register-copy h1 {

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
            3rem,
            5vw,
            5rem
        );

    font-weight:
        800;

    line-height:
        .96;

    letter-spacing:
        -.055em;

}


.gh-register-copy
.accent {

    color:
        #d7f9df;

}


.gh-register-copy p {

    max-width:
        500px;

    margin:
        24px
        0
        0;

    color:
        rgba(255,255,255,.72);

    font-size:
        .96rem;

    line-height:
        1.7;

}


.gh-register-footer {

    color:
        rgba(255,255,255,.55);

    font-size:
        .74rem;

}


/* =========================================================
   RIGHT PANEL
========================================================= */

.gh-register-form-panel {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    min-width:
        0;

    padding:
        40px
        32px;

    background:
        var(--register-cream);

}


/*
 * Smaller form width.
 */
.gh-register-form-wrap {

    width:
        100%;

    max-width:
        380px;

    margin:
        0 auto;

}


/* =========================================================
   FORM BRAND
========================================================= */

.gh-register-form-brand {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        24px;

    color:
        var(--register-900);

}


.gh-register-form-brand img {

    width:
        32px;

    height:
        32px;

    object-fit:
        contain;

    border-radius:
        8px;

}


.gh-register-form-brand span {

    font-size:
        1rem;

    font-weight:
        800;

}


/* =========================================================
   FORM HEADING
========================================================= */

.gh-register-heading {

    margin:
        0
        0
        8px;

    color:
        var(--register-950);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            1.95rem,
            3vw,
            2.35rem
        );

    font-weight:
        800;

    line-height:
        1.04;

    letter-spacing:
        -.045em;

}


.gh-register-subtitle {

    margin:
        0
        0
        28px;

    color:
        var(--register-muted);

    font-size:
        .79rem;

    line-height:
        1.6;

}


.gh-register-subtitle a {

    color:
        var(--register-900);

    font-weight:
        700;

    text-decoration:
        none;

}


.gh-register-subtitle
a:hover {

    text-decoration:
        underline;

}


/* =========================================================
   FLASH MESSAGES
========================================================= */

.gh-register-form-wrap
.alert {

    margin-bottom:
        24px;

    padding:
        12px
        14px;

    border-radius:
        10px;

    font-size:
        .75rem;

}


/* =========================================================
   FORM GRID
========================================================= */

.register-grid {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, 1fr);

    column-gap:
        14px;

    row-gap:
        20px;

}


.register-field {

    min-width:
        0;

}


.register-field.full {

    grid-column:
        1 / -1;

}


/* =========================================================
   LABEL STYLING
========================================================= */

.register-label {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #34483b;

    font-size:
        .68rem;

    font-weight:
        750;

    letter-spacing:
        .045em;

    text-transform:
        uppercase;

}


/* =========================================================
   INPUT FIELD STYLING
========================================================= */

.register-input-wrap {

    position:
        relative;

}


.register-input {

    width:
        100%;

    height:
        46px;

    padding:
        0
        14px;

    border:
        1px solid
        var(--register-border);

    border-radius:
        10px;

    background:
        #ffffff;

    color:
        var(--register-text);

    font-size:
        .82rem;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background-color .2s ease;

}


.register-input::placeholder {

    color:
        #9ca69f;

}


.register-input:hover {

    border-color:
        rgba(20,83,45,.24);

}


.register-input:focus {

    border-color:
        rgba(21,128,61,.52);

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(74,222,128,.10);

}


/* Invalid State */

.register-input.is-invalid {

    border-color:
        rgba(220,38,38,.65);

    background:
        #fffafa;

}


.register-input.is-invalid:focus {

    box-shadow:
        0 0 0 3px
        rgba(220,38,38,.08);

}


/* =========================================================
   FIELD ERROR MESSAGES
========================================================= */

.register-field-error {

    display:
        none;

    margin-top:
        7px;

    color:
        var(--register-danger);

    font-size:
        .65rem;

    font-weight:
        600;

    line-height:
        1.4;

}


.register-field-error.show {

    display:
        block;

}


/* =========================================================
   PASSWORD INPUT
========================================================= */

.register-password-wrap
.register-input {

    padding-right:
        50px;

}


/* =========================================================
   PASSWORD VISIBILITY TOGGLE
========================================================= */

.password-toggle {

    position:
        absolute;

    right:
        6px;

    top:
        50%;

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

    transform:
        translateY(-50%);

    padding:
        0;

    border:
        0;

    border-radius:
        9px;

    background:
        var(--register-50);

    color:
        var(--register-700);

    cursor:
        pointer;

    transition:
        background-color .2s ease,
        color .2s ease,
        box-shadow .2s ease;

}


.password-toggle:hover {

    background:
        #dcfce7;

    color:
        var(--register-900);

}


.password-toggle:focus-visible {

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(74,222,128,.17);

}


.password-toggle svg {

    width:
        18px;

    height:
        18px;

    display:
        block;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.8;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;

}


.password-toggle
.eye-open {

    display:
        block;

}


.password-toggle
.eye-closed {

    display:
        none;

}


.password-toggle.is-visible
.eye-open {

    display:
        none;

}


.password-toggle.is-visible
.eye-closed {

    display:
        block;

}


/* =========================================================
   PASSWORD STRENGTH INDICATOR
========================================================= */

.password-strength {

    margin-top:
        12px;

}


.password-strength-head {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    margin-bottom:
        7px;

}


.password-strength-title,
.password-strength-label {

    font-size:
        .66rem;

}


.password-strength-title {

    color:
        var(--register-muted);

    font-weight:
        600;

}


.password-strength-label {

    font-weight:
        800;

}


.password-strength-track {

    width:
        100%;

    height:
        5px;

    overflow:
        hidden;

    border-radius:
        999px;

    background:
        #e6ebe7;

}


.password-strength-bar {

    width:
        0;

    height:
        100%;

    border-radius:
        inherit;

    transition:
        width .25s ease,
        background-color .25s ease;

}


/* Weak Strength */

.password-strength[data-strength="weak"]
.password-strength-bar {

    width:
        25%;

    background:
        #dc2626;

}


.password-strength[data-strength="weak"]
.password-strength-label {

    color:
        #dc2626;

}


/* Fair Strength */

.password-strength[data-strength="fair"]
.password-strength-bar {

    width:
        50%;

    background:
        #f59e0b;

}


.password-strength[data-strength="fair"]
.password-strength-label {

    color:
        #d97706;

}


/* Good Strength */

.password-strength[data-strength="good"]
.password-strength-bar {

    width:
        75%;

    background:
        #84cc16;

}


.password-strength[data-strength="good"]
.password-strength-label {

    color:
        #65a30d;

}


/* Strong Strength */

.password-strength[data-strength="strong"]
.password-strength-bar {

    width:
        100%;

    background:
        var(--register-600);

}


.password-strength[data-strength="strong"]
.password-strength-label {

    color:
        var(--register-700);

}


/* =========================================================
   PASSWORD REQUIREMENTS CHECKLIST
========================================================= */

.password-requirements {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        8px
        14px;

    margin:
        12px
        0
        0;

    padding:
        0;

    list-style:
        none;

}


.password-condition {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    min-width:
        0;

    color:
        var(--register-muted);

    font-size:
        .64rem;

    line-height:
        1.35;

}


/*
 * Neutral state = dot
 */
.password-condition-icon {

    width:
        16px;

    height:
        16px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        50%;

    background:
        #edf1ee;

    color:
        #8d9991;

    font-size:
        0;

}


.password-condition-icon::before {

    content:
        "•";

    font-size:
        .68rem;

}


/*
 * Valid state = check
 */
.password-condition.valid {

    color:
        var(--register-800);

}


.password-condition.valid
.password-condition-icon {

    background:
        #dcfce7;

    color:
        var(--register-700);

}


.password-condition.valid
.password-condition-icon::before {

    content:
        "✓";

    font-size:
        .54rem;

    font-weight:
        800;

}


/* =========================================================
   PASSWORD MATCH STATUS
========================================================= */

.password-match-state {

    min-height:
        18px;

    margin-top:
        7px;

    font-size:
        .65rem;

    font-weight:
        700;

}


.password-match-state:empty {

    display:
        none;

}


.password-match-state.match {

    color:
        var(--register-700);

}


.password-match-state.no-match {

    color:
        var(--register-danger);

}


/* =========================================================
   SUBMIT BUTTON
========================================================= */

.register-submit {

    width:
        100%;

    min-height:
        46px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-top:
        24px;

    padding:
        10px
        16px;

    border:
        0;

    border-radius:
        10px;

    background:
        linear-gradient(
            135deg,
            var(--register-700),
            var(--register-900)
        );

    color:
        #ffffff;

    font-size:
        .84rem;

    font-weight:
        750;

    cursor:
        pointer;

    box-shadow:
        0 9px 20px
        rgba(20,83,45,.14);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.register-submit:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 28px
        rgba(20,83,45,.22);

}


.register-submit:active {

    transform:
        translateY(0);

}


/* =========================================================
   FOOTER LINK
========================================================= */

.gh-register-foot {

    margin-top:
        20px;

    text-align:
        center;

}


.gh-register-foot a {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    color:
        var(--register-900);

    font-size:
        .72rem;

    font-weight:
        700;

    text-decoration:
        none;

    transition:
        color .2s ease;

}


.gh-register-foot
a:hover {

    text-decoration:
        underline;

    color:
        var(--register-700);

}


/* =========================================================
   MEDIUM DESKTOP (1140px - 981px)
========================================================= */

@media (
    max-width: 1140px
)
and
(
    min-width: 981px
) {

    .gh-register-layout {

        grid-template-columns:
            minmax(0, 1fr)
            410px;

    }


    .gh-register-visual {

        padding:
            48px
            48px
            38px;

    }


    .gh-register-form-panel {

        padding:
            36px
            24px;

    }


    .gh-register-form-wrap {

        max-width:
            365px;

    }

}


/* =========================================================
   TABLET (980px and below)
========================================================= */

@media (
    max-width: 980px
) {

    .gh-register-layout {

        min-height:
            auto;

        grid-template-columns:
            1fr;

    }


    .gh-register-visual {

        min-height:
            280px;

        padding:
            36px
            36px
            32px;

    }


    .gh-register-copy {

        margin:
            48px
            0
            36px;

    }


    .gh-register-copy h1 {

        font-size:
            clamp(
                2.6rem,
                8vw,
                3.8rem
            );

    }


    .gh-register-form-panel {

        padding:
            48px
            28px
            56px;

    }


    .gh-register-form-wrap {

        max-width:
            380px;

    }

}


/* =========================================================
   MOBILE (575px and below)
========================================================= */

@media (
    max-width: 575.98px
) {

    .gh-register-visual {

        min-height:
            240px;

        padding:
            28px
            24px;

    }


    .gh-register-brand {

        padding:
            7px
            12px;

    }


    .gh-register-brand img {

        width:
            26px;

        height:
            26px;

    }


    .gh-register-copy {

        margin:
            32px
            0
            24px;

    }


    .gh-register-copy h1 {

        font-size:
            2.45rem;

    }


    .gh-register-copy p {

        font-size:
            .82rem;

        margin-top:
            16px;

    }


    .gh-register-footer {

        display:
            none;

    }


    .gh-register-form-panel {

        padding:
            36px
            20px
            48px;

    }


    .gh-register-form-wrap {

        max-width:
            380px;

    }


    .gh-register-form-brand {

        margin-bottom:
            20px;

    }


    .gh-register-heading {

        font-size:
            2rem;

        margin-bottom:
            6px;

    }


    .gh-register-subtitle {

        margin-bottom:
            24px;

    }


    .register-grid {

        grid-template-columns:
            1fr;

        row-gap:
            18px;

    }


    .register-field.full {

        grid-column:
            auto;

    }

}


/* =========================================================
   VERY SMALL MOBILE (360px and below)
========================================================= */

@media (
    max-width: 360px
) {

    .gh-register-visual {

        padding:
            24px
            16px;

    }


    .gh-register-form-panel {

        padding:
            28px
            16px
            40px;

    }


    .password-requirements {

        grid-template-columns:
            1fr;

    }

}

</style>


<!-- =========================================================
     REGISTRATION PAGE
========================================================= -->

<section class="gh-register-shell">

    <div class="gh-register-layout">

        <!-- =================================================
             LEFT PANEL - VISUAL BRANDING
        ================================================== -->

        <div class="gh-register-visual">

            <div class="gh-register-brand">
                <img src="<?= e(url('assets/images/placeholder.svg')) ?>" alt="Green Harvest logo">
                <span>Green Harvest</span>
            </div>

            <div class="gh-register-copy">
                <h1>Fresh goodness<br><span class="accent">starts here.</span></h1>
                <p>Create your account to shop fresh produce, manage orders and enjoy a simpler way to bring quality food home.</p>
            </div>

            <div class="gh-register-footer">
                © 2026 Green Harvest. Fresh • Organic • Local.
            </div>
        </div>

        <!-- =================================================
             RIGHT PANEL - REGISTRATION FORM
        ================================================== -->

        <div class="gh-register-form-panel">

            <div class="gh-register-form-wrap">

                <div class="gh-register-form-brand">
                    <img src="<?= e(url('assets/images/placeholder.svg')) ?>" alt="Green Harvest logo">
                    <span>Green Harvest</span>
                </div>

                <h2 class="gh-register-heading">Create account</h2>

                <p class="gh-register-subtitle">
                    Join Green Harvest and start shopping fresh produce.
                    <br>
                    Already have an account?
                    <a href="<?= e(url('login.php?redirect=' . urlencode($redirect))) ?>">Sign in.</a>
                </p>

                <?php displayFlash(); ?>

                <form method="post" action="<?= e(url('register.php')) ?>" autocomplete="on" id="registrationForm" novalidate>

                    <?= csrfField() ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="register-grid">

                        <!-- Full Name -->
                        <div class="register-field full">
                            <label for="full_name" class="register-label">Full Name</label>
                            <div class="register-input-wrap">
                                <input id="full_name" type="text" name="full_name" value="<?= e($name) ?>" class="register-input" placeholder="Enter your full name" autocomplete="name" required>
                            </div>
                            <div class="register-field-error" id="fullNameError"></div>
                        </div>

                        <!-- Email -->
                        <div class="register-field">
                            <label for="email" class="register-label">Email Address</label>
                            <div class="register-input-wrap">
                                <input id="email" type="email" name="email" value="<?= e($email) ?>" class="register-input" placeholder="you@example.com" autocomplete="email" required>
                            </div>
                            <div class="register-field-error" id="emailError"></div>
                        </div>

                        <!-- Phone -->
                        <div class="register-field">
                            <label for="phone" class="register-label">Phone Number</label>
                            <div class="register-input-wrap">
                                <input id="phone" type="tel" name="phone" value="<?= e($phone) ?>" class="register-input" placeholder="+233..." autocomplete="tel" required>
                            </div>
                            <div class="register-field-error" id="phoneError"></div>
                        </div>

                        <!-- Password -->
                        <div class="register-field full">
                            <label for="password" class="register-label">Password</label>
                            <div class="register-input-wrap register-password-wrap">
                                <input id="password" type="password" name="password" class="register-input" placeholder="Create a strong password" autocomplete="new-password" maxlength="128" required>
                                <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2.5 12 C4.7 7.8 8 5.7 12 5.7 C16 5.7 19.3 7.8 21.5 12 C19.3 16.2 16 18.3 12 18.3 C8 18.3 4.7 16.2 2.5 12 Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 3 L21 21"></path>
                                        <path d="M10.7 6 C11.1 5.9 11.6 5.8 12 5.8 C16 5.8 19.3 7.9 21.5 12 C20.7 13.5 19.8 14.7 18.7 15.7"></path>
                                        <path d="M15.5 17.4 C14.4 17.9 13.2 18.2 12 18.2 C8 18.2 4.7 16.1 2.5 12 C3.4 10.3 4.5 8.9 5.8 7.9"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="register-field-error" id="passwordError"></div>

                            <div class="password-strength" id="passwordStrength" data-strength="">
                                <div class="password-strength-head">
                                    <span class="password-strength-title">Password strength</span>
                                    <span class="password-strength-label" id="passwordStrengthLabel">—</span>
                                </div>
                                <div class="password-strength-track">
                                    <div class="password-strength-bar"></div>
                                </div>
                            </div>

                            <ul class="password-requirements">
                                <li class="password-condition" id="conditionLength"><span class="password-condition-icon"></span>8+ characters</li>
                                <li class="password-condition" id="conditionUppercase"><span class="password-condition-icon"></span>Uppercase letter</li>
                                <li class="password-condition" id="conditionLowercase"><span class="password-condition-icon"></span>Lowercase letter</li>
                                <li class="password-condition" id="conditionNumber"><span class="password-condition-icon"></span>Number</li>
                                <li class="password-condition" id="conditionSpecial"><span class="password-condition-icon"></span>Special character</li>
                                <li class="password-condition" id="conditionSpaces"><span class="password-condition-icon"></span>No spaces</li>
                            </ul>
                        </div>

                        <!-- Confirm Password -->
                        <div class="register-field full">
                            <label for="confirm_password" class="register-label">Confirm Password</label>
                            <div class="register-input-wrap register-password-wrap">
                                <input id="confirm_password" type="password" name="confirm_password" class="register-input" placeholder="Repeat your password" autocomplete="new-password" maxlength="128" required>
                                <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password" aria-pressed="false">
                                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2.5 12 C4.7 7.8 8 5.7 12 5.7 C16 5.7 19.3 7.8 21.5 12 C19.3 16.2 16 18.3 12 18.3 C8 18.3 4.7 16.2 2.5 12 Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 3 L21 21"></path>
                                        <path d="M10.7 6 C11.1 5.9 11.6 5.8 12 5.8 C16 5.8 19.3 7.9 21.5 12"></path>
                                        <path d="M15.5 17.4 C14.4 17.9 13.2 18.2 12 18.2 C8 18.2 4.7 16.1 2.5 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="password-match-state" id="passwordMatchState" aria-live="polite"></div>
                            <div class="register-field-error" id="confirmPasswordError"></div>
                        </div>

                    </div>

                    <button type="submit" class="register-submit">Create Account</button>

                </form>

                <div class="gh-register-foot">
                    <a href="<?= e(url('index.php')) ?>#products">
                        <span>←</span>
                        Continue shopping
                    </a>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     JAVASCRIPT - FORM LOGIC & VALIDATION
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('registrationForm');
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthBox = document.getElementById('passwordStrength');
    const strengthLabel = document.getElementById('passwordStrengthLabel');
    const matchState = document.getElementById('passwordMatchState');

    const errors = {
        fullName: document.getElementById('fullNameError'),
        email: document.getElementById('emailError'),
        phone: document.getElementById('phoneError'),
        password: document.getElementById('passwordError'),
        confirmPassword: document.getElementById('confirmPasswordError')
    };

    const requirements = {
        length: document.getElementById('conditionLength'),
        uppercase: document.getElementById('conditionUppercase'),
        lowercase: document.getElementById('conditionLowercase'),
        number: document.getElementById('conditionNumber'),
        special: document.getElementById('conditionSpecial'),
        spaces: document.getElementById('conditionSpaces')
    };

    // Password Toggle
    const toggleButtons = document.querySelectorAll('[data-password-toggle]');
    toggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const inputId = button.dataset.passwordToggle;
            const input = document.getElementById(inputId);
            if (!input) return;
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.classList.toggle('is-visible', hidden);
            button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            input.focus();
        });
    });

    function setCondition(element, valid) {
        if (!element) return;
        element.classList.toggle('valid', valid);
    }

    function getPasswordTests() {
        const password = passwordInput ? passwordInput.value : '';
        return {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9\s]/.test(password),
            noSpaces: password.length > 0 && !/\s/.test(password)
        };
    }

    function updatePasswordStrength() {
        if (!passwordInput || !strengthBox || !strengthLabel) return;
        const password = passwordInput.value;
        const tests = getPasswordTests();

        setCondition(requirements.length, tests.length);
        setCondition(requirements.uppercase, tests.uppercase);
        setCondition(requirements.lowercase, tests.lowercase);
        setCondition(requirements.number, tests.number);
        setCondition(requirements.special, tests.special);
        setCondition(requirements.spaces, tests.noSpaces);

        if (password.length === 0) {
            strengthBox.dataset.strength = '';
            strengthLabel.textContent = '—';
            updatePasswordMatch();
            return;
        }

        let score = 0;
        if (tests.length) score++;
        if (tests.uppercase) score++;
        if (tests.lowercase) score++;
        if (tests.number) score++;
        if (tests.special) score++;
        if (password.length >= 12) score++;
        if (!tests.noSpaces) score = Math.min(score, 2);

        let strength = 'weak', label = 'Weak';
        if (score >= 6 && tests.noSpaces) {
            strength = 'strong';
            label = 'Strong';
        } else if (score >= 5 && tests.noSpaces) {
            strength = 'good';
            label = 'Good';
        } else if (score >= 3) {
            strength = 'fair';
            label = 'Fair';
        }

        strengthBox.dataset.strength = strength;
        strengthLabel.textContent = label;
        updatePasswordMatch();
    }

    function updatePasswordMatch() {
        if (!passwordInput || !confirmPasswordInput || !matchState) return;
        const password = passwordInput.value;
        const confirmation = confirmPasswordInput.value;

        if (confirmation.length === 0) {
            matchState.textContent = '';
            matchState.className = 'password-match-state';
            return;
        }

        if (password === confirmation) {
            matchState.className = 'password-match-state match';
            matchState.textContent = '✓ Passwords match';
        } else {
            matchState.className = 'password-match-state no-match';
            matchState.textContent = '✕ Passwords do not match';
        }
    }

    function showError(input, errorElement, message) {
        if (input) input.classList.add('is-invalid');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }

    function clearError(input, errorElement) {
        if (input) input.classList.remove('is-invalid');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('show');
        }
    }

    function attachClearHandler(input, errorElement) {
        if (!input) return;
        input.addEventListener('input', function () {
            clearError(input, errorElement);
        });
    }

    attachClearHandler(fullNameInput, errors.fullName);
    attachClearHandler(emailInput, errors.email);
    attachClearHandler(phoneInput, errors.phone);
    attachClearHandler(passwordInput, errors.password);
    attachClearHandler(confirmPasswordInput, errors.confirmPassword);

    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordStrength);
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', updatePasswordMatch);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            let firstInvalid = null;

            clearError(fullNameInput, errors.fullName);
            clearError(emailInput, errors.email);
            clearError(phoneInput, errors.phone);
            clearError(passwordInput, errors.password);
            clearError(confirmPasswordInput, errors.confirmPassword);

            const fullName = fullNameInput ? fullNameInput.value.trim() : '';
            if (fullName.length < 2) {
                showError(fullNameInput, errors.fullName, 'Please enter your full name.');
                firstInvalid = firstInvalid || fullNameInput;
            }

            const email = emailInput ? emailInput.value.trim() : '';
            if (email === '') {
                showError(emailInput, errors.email, 'Email address is required.');
                firstInvalid = firstInvalid || emailInput;
            } else if (!emailInput.validity.valid) {
                showError(emailInput, errors.email, 'Enter a valid email address.');
                firstInvalid = firstInvalid || emailInput;
            }

            const phone = phoneInput ? phoneInput.value.trim().replace(/[\s()-]/g, '') : '';
            const internationalPhone = /^\+[1-9]\d{6,14}$/;
            const localPhone = /^0\d{8,10}$/;
            if (phone === '') {
                showError(phoneInput, errors.phone, 'Phone number is required.');
                firstInvalid = firstInvalid || phoneInput;
            } else if (!internationalPhone.test(phone) && !localPhone.test(phone)) {
                showError(phoneInput, errors.phone, 'Enter a valid phone number.');
                firstInvalid = firstInvalid || phoneInput;
            }

            const tests = getPasswordTests();
            const password = passwordInput ? passwordInput.value : '';
            const validPassword = tests.length && tests.uppercase && tests.lowercase && tests.number && tests.special && tests.noSpaces && password.length <= 128;
            if (!validPassword) {
                showError(passwordInput, errors.password, 'Please satisfy all password requirements.');
                firstInvalid = firstInvalid || passwordInput;
            }

            const confirmation = confirmPasswordInput ? confirmPasswordInput.value : '';
            if (confirmation === '') {
                showError(confirmPasswordInput, errors.confirmPassword, 'Please confirm your password.');
                firstInvalid = firstInvalid || confirmPasswordInput;
            } else if (confirmation !== password) {
                showError(confirmPasswordInput, errors.confirmPassword, 'Passwords do not match.');
                firstInvalid = firstInvalid || confirmPasswordInput;
            }

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    updatePasswordStrength();
});

</script>