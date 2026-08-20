<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - FORGOT PASSWORD
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Logged-In Users
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    if (isAdmin()) {

        redirectTo(
            'admin/dashboard.php'
        );
    }

    redirectTo(
        'account.php'
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
| Local Development Detection
|--------------------------------------------------------------------------
|
| During development on XAMPP, the reset URL will be displayed
| on screen so email configuration is not required.
|
*/

$host =
    strtolower(
        (string) (
            $_SERVER['HTTP_HOST']
            ?? ''
        )
    );


$isLocalDevelopment =
    str_contains(
        $host,
        'localhost'
    )
    ||
    str_contains(
        $host,
        '127.0.0.1'
    )
    ||
    str_contains(
        $host,
        '[::1]'
    );


/*
|--------------------------------------------------------------------------
| Recover Local Testing Reset URL
|--------------------------------------------------------------------------
*/

$localResetUrl =
    $_SESSION['green_harvest_local_reset_url']
    ?? null;


unset(
    $_SESSION['green_harvest_local_reset_url']
);


/*
|--------------------------------------------------------------------------
| Handle Form Submission
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
            'Invalid password reset request. Please try again.'
        );


    } else {

        /*
        |--------------------------------------------------------------------------
        | Email
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


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            $email === ''
        ) {

            setFlash(
                'error',
                'Please enter your email address.'
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
                |--------------------------------------------------------------------------
                | Find User
                |--------------------------------------------------------------------------
                |
                | We still return the same message whether the account
                | exists or not. This prevents account enumeration.
                |
                */

                $userStmt =
                    $pdo->prepare(
                        '
                        SELECT
                            id,
                            full_name,
                            email

                        FROM users

                        WHERE email = ?

                        LIMIT 1
                        '
                    );


                $userStmt->execute([
                    $email,
                ]);


                $user =
                    $userStmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                /*
                |--------------------------------------------------------------------------
                | Account Exists
                |--------------------------------------------------------------------------
                */

                if ($user) {

                    /*
                     * Generate a secure 32-byte token.
                     *
                     * bin2hex() converts it to
                     * a 64-character hexadecimal string.
                     */
                    $token =
                        bin2hex(
                            random_bytes(32)
                        );


                    /*
                     * Store only the token hash.
                     */
                    $tokenHash =
                        hash(
                            'sha256',
                            $token
                        );


                    /*
                     * Token valid for 30 minutes.
                     */
                    $expiresAt =
                        (new DateTimeImmutable(
                            '+30 minutes'
                        ))
                        ->format(
                            'Y-m-d H:i:s'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Transaction
                    |--------------------------------------------------------------------------
                    */

                    $pdo->beginTransaction();


                    /*
                     * Invalidate previous active
                     * reset tokens for this user.
                     */
                    $invalidateStmt =
                        $pdo->prepare(
                            '
                            UPDATE password_reset_tokens

                            SET used_at = NOW()

                            WHERE user_id = ?
                            AND used_at IS NULL
                            '
                        );


                    $invalidateStmt->execute([
                        (int)
                        $user['id'],
                    ]);


                    /*
                     * Store new reset token.
                     */
                    $insertStmt =
                        $pdo->prepare(
                            '
                            INSERT INTO password_reset_tokens
                            (
                                user_id,
                                token_hash,
                                expires_at
                            )

                            VALUES
                            (
                                ?,
                                ?,
                                ?
                            )
                            '
                        );


                    $insertStmt->execute([
                        (int)
                        $user['id'],
                        $tokenHash,
                        $expiresAt,
                    ]);


                    $pdo->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | Reset URL
                    |--------------------------------------------------------------------------
                    */

                    $resetUrl =
                        url(
                            'reset-password.php?token=' .
                            urlencode(
                                $token
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Local XAMPP Testing
                    |--------------------------------------------------------------------------
                    |
                    | Since localhost usually has no SMTP server,
                    | display the reset link after redirect.
                    |
                    */

                    if (
                        $isLocalDevelopment
                    ) {

                        $_SESSION[
                            'green_harvest_local_reset_url'
                        ] =
                            $resetUrl;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Production Email
                    |--------------------------------------------------------------------------
                    |
                    | Later, when Green Harvest is deployed,
                    | this is where PHPMailer / SMTP should
                    | send $resetUrl to $user['email'].
                    |
                    | Do NOT store the raw reset token
                    | anywhere permanent.
                    |
                    */

                }


                /*
                |--------------------------------------------------------------------------
                | Generic Response
                |--------------------------------------------------------------------------
                |
                | Do not tell the visitor whether the
                | supplied email exists.
                |
                */

                setFlash(
                    'success',
                    'If an account exists for that email address, a password reset link has been created.'
                );


                /*
                 * PRG pattern:
                 * Prevent accidental token generation
                 * when refreshing the browser.
                 */
                redirectTo(
                    'forgot-password.php'
                );


            } catch (
                Throwable $e
            ) {

                /*
                 * Roll back transaction if necessary.
                 */
                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();

                }


                error_log(
                    'Green Harvest forgot password error: ' .
                    $e->getMessage()
                );


                setFlash(
                    'error',
                    'We could not process your password reset request right now. Please try again.'
                );

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Forgot Password';

?>


<style>

@import url(
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap'
);


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --forgot-950:
        #092516;

    --forgot-900:
        #14532d;

    --forgot-800:
        #166534;

    --forgot-700:
        #15803d;

    --forgot-600:
        #16a34a;

    --forgot-500:
        #4ade80;

    --forgot-50:
        #f0fdf4;

    --forgot-cream:
        #fafcf9;

    --forgot-white:
        #ffffff;

    --forgot-text:
        #1a2a22;

    --forgot-muted:
        #66756b;

    --forgot-border:
        rgba(
            20,
            83,
            45,
            .14
        );

}


/* =========================================================
   RESET
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
        var(--forgot-cream);

    color:
        var(--forgot-text);

    font-family:
        'Inter',
        'Segoe UI',
        sans-serif;

}


body,
input,
button {

    font-family:
        'Inter',
        'Segoe UI',
        sans-serif;

}


/* =========================================================
   HIDE NORMAL WEBSITE NAVIGATION
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
   MAIN PAGE
========================================================= */

.gh-forgot-shell {

    min-height:
        100vh;

}


.gh-forgot-layout {

    min-height:
        100vh;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.28fr)
        minmax(390px, .72fr);

}


/* =========================================================
   LEFT VISUAL PANEL
========================================================= */

.gh-forgot-visual {

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
            rgba(9,37,22,.98),
            rgba(20,83,45,.94) 55%,
            rgba(22,101,52,.98)
        );

    color:
        #ffffff;

}


/* Background grid */

.gh-forgot-visual::before {

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


/* Decorative circle */

.gh-forgot-visual::after {

    content:
        "";

    position:
        absolute;

    width:
        500px;

    height:
        500px;

    right:
        -230px;

    bottom:
        -230px;

    border:
        1px solid
        rgba(255,255,255,.09);

    border-radius:
        50%;

    background:
        rgba(255,255,255,.025);

}


.gh-forgot-visual
> * {

    position:
        relative;

    z-index:
        2;

}


/* =========================================================
   LEFT BRAND
========================================================= */

.gh-forgot-brand {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    width:
        fit-content;

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


.gh-forgot-brand img {

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

.gh-forgot-copy {

    max-width:
        640px;

}


.gh-forgot-copy h1 {

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


.gh-forgot-copy
.accent {

    color:
        #d7f9df;

}


.gh-forgot-copy p {

    max-width:
        500px;

    margin:
        18px
        0
        0;

    color:
        rgba(255,255,255,.72);

    font-size:
        .95rem;

    line-height:
        1.7;

}


.gh-forgot-footer {

    color:
        rgba(255,255,255,.55);

    font-size:
        .74rem;

}


/* =========================================================
   RIGHT PANEL
========================================================= */

.gh-forgot-panel {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    min-width:
        0;

    padding:
        32px
        24px;

    background:
        var(--forgot-cream);

}


.gh-forgot-form {

    width:
        100%;

    max-width:
        355px;

    margin:
        0 auto;

}


/* =========================================================
   FORM BRAND
========================================================= */

.gh-forgot-form-brand {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        21px;

    color:
        var(--forgot-900);

}


.gh-forgot-form-brand img {

    width:
        30px;

    height:
        30px;

    object-fit:
        contain;

    border-radius:
        8px;

}


.gh-forgot-form-brand span {

    font-size:
        1rem;

    font-weight:
        800;

}


/* =========================================================
   ICON
========================================================= */

.gh-forgot-icon {

    width:
        46px;

    height:
        46px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        17px;

    border-radius:
        14px;

    background:
        var(--forgot-50);

    color:
        var(--forgot-700);

    font-size:
        1.1rem;

}


/* =========================================================
   HEADING
========================================================= */

.gh-forgot-heading {

    margin:
        0
        0
        7px;

    color:
        var(--forgot-950);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            2rem,
            3vw,
            2.4rem
        );

    font-weight:
        800;

    line-height:
        1.04;

    letter-spacing:
        -.045em;

}


.gh-forgot-description {

    margin:
        0
        0
        22px;

    color:
        var(--forgot-muted);

    font-size:
        .8rem;

    line-height:
        1.6;

}


/* =========================================================
   FLASH MESSAGES
========================================================= */

.gh-forgot-form
.alert {

    margin-bottom:
        17px;

    padding:
        10px
        11px;

    border-radius:
        10px;

    font-size:
        .74rem;

    line-height:
        1.5;

}


/* =========================================================
   FORM FIELD
========================================================= */

.gh-forgot-field {

    margin-bottom:
        16px;

}


.gh-forgot-label {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #34483b;

    font-size:
        .65rem;

    font-weight:
        750;

    letter-spacing:
        .045em;

    text-transform:
        uppercase;

}


.gh-forgot-input {

    width:
        100%;

    height:
        44px;

    padding:
        0
        13px;

    border:
        1px solid
        var(--forgot-border);

    border-radius:
        10px;

    background:
        #ffffff;

    color:
        var(--forgot-text);

    font-size:
        .82rem;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-forgot-input::placeholder {

    color:
        #9ca69f;

}


.gh-forgot-input:hover {

    border-color:
        rgba(20,83,45,.23);

}


.gh-forgot-input:focus {

    border-color:
        rgba(21,128,61,.52);

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(74,222,128,.1);

}


/* =========================================================
   BUTTON
========================================================= */

.gh-forgot-submit {

    width:
        100%;

    min-height:
        44px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    padding:
        9px
        14px;

    border:
        0;

    border-radius:
        10px;

    background:
        linear-gradient(
            135deg,
            var(--forgot-700),
            var(--forgot-900)
        );

    color:
        #ffffff;

    font-size:
        .82rem;

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


.gh-forgot-submit:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 12px 24px
        rgba(20,83,45,.18);

}


/* =========================================================
   HELP TEXT
========================================================= */

.gh-forgot-help {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        8px;

    margin-top:
        15px;

    padding:
        10px
        11px;

    border:
        1px solid
        rgba(20,83,45,.08);

    border-radius:
        10px;

    background:
        #f7faf7;

    color:
        var(--forgot-muted);

    font-size:
        .65rem;

    line-height:
        1.5;

}


.gh-forgot-help i {

    flex-shrink:
        0;

    margin-top:
        1px;

    color:
        var(--forgot-700);

}


/* =========================================================
   LOCAL DEVELOPMENT LINK
========================================================= */

.gh-local-reset {

    margin-top:
        16px;

    padding:
        13px;

    border:
        1px solid
        rgba(21,128,61,.14);

    border-radius:
        11px;

    background:
        #f0fdf4;

}


.gh-local-reset-title {

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

    margin-bottom:
        7px;

    color:
        var(--forgot-900);

    font-size:
        .67rem;

    font-weight:
        800;

}


.gh-local-reset p {

    margin:
        0
        0
        9px;

    color:
        var(--forgot-muted);

    font-size:
        .62rem;

    line-height:
        1.5;

}


.gh-local-reset-link {

    width:
        100%;

    min-height:
        39px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    padding:
        8px
        10px;

    border:
        1px solid
        rgba(21,128,61,.18);

    border-radius:
        9px;

    background:
        #ffffff;

    color:
        var(--forgot-900);

    font-size:
        .67rem;

    font-weight:
        750;

    text-decoration:
        none;

}


.gh-local-reset-link:hover {

    background:
        #f7fff8;

    color:
        var(--forgot-700);

}


/* =========================================================
   BACK LINK
========================================================= */

.gh-forgot-back {

    margin-top:
        18px;

    text-align:
        center;

}


.gh-forgot-back a {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--forgot-900);

    font-size:
        .7rem;

    font-weight:
        700;

    text-decoration:
        none;

}


.gh-forgot-back
a:hover {

    text-decoration:
        underline;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 980px
) {

    .gh-forgot-layout {

        grid-template-columns:
            1fr;

    }


    .gh-forgot-visual {

        min-height:
            250px;

        padding:
            28px;

    }


    .gh-forgot-copy {

        margin:
            35px
            0
            25px;

    }


    .gh-forgot-copy h1 {

        font-size:
            3.5rem;

    }


    .gh-forgot-panel {

        padding:
            46px
            22px
            54px;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-forgot-visual {

        min-height:
            210px;

        padding:
            20px;

    }


    .gh-forgot-copy {

        margin:
            26px
            0
            17px;

    }


    .gh-forgot-copy h1 {

        font-size:
            2.45rem;

    }


    .gh-forgot-copy p {

        font-size:
            .8rem;

    }


    .gh-forgot-footer {

        display:
            none;

    }


    .gh-forgot-panel {

        padding:
            34px
            19px
            42px;

    }


    .gh-forgot-form {

        max-width:
            355px;

    }

}

</style>



<!-- =========================================================
     FORGOT PASSWORD PAGE
========================================================= -->

<section class="gh-forgot-shell">


    <div class="gh-forgot-layout">


        <!-- =================================================
             LEFT PANEL
        ================================================== -->

        <div class="gh-forgot-visual">


            <!-- BRAND -->

            <div class="gh-forgot-brand">


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



            <!-- COPY -->

            <div class="gh-forgot-copy">


                <h1>

                    Let’s get you

                    <br>

                    <span class="accent">

                        back inside.

                    </span>

                </h1>


                <p>

                    Enter the email connected to
                    your Green Harvest account and
                    we’ll help you securely create
                    a new password.

                </p>


            </div>



            <!-- FOOTER -->

            <div class="gh-forgot-footer">

                © 2026 Green Harvest.
                Fresh • Organic • Local.

            </div>


        </div>



        <!-- =================================================
             RIGHT PANEL
        ================================================== -->

        <div class="gh-forgot-panel">


            <div class="gh-forgot-form">


                <!-- BRAND -->

                <div class="gh-forgot-form-brand">


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



                <!-- ICON -->

                <span class="gh-forgot-icon">

                    <i class="bi bi-key"></i>

                </span>



                <!-- HEADING -->

                <h1 class="gh-forgot-heading">

                    Forgot password?

                </h1>


                <p class="gh-forgot-description">

                    Enter the email address associated
                    with your account. A secure reset
                    link will be created if the account
                    exists.

                </p>



                <!-- FLASH -->

                <?php displayFlash(); ?>



                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    method="post"
                    action="<?= e(
                        url(
                            'forgot-password.php'
                        )
                    ) ?>"
                    autocomplete="on"
                >


                    <?= csrfField() ?>



                    <div class="gh-forgot-field">


                        <label
                            for="email"
                            class="gh-forgot-label"
                        >

                            Email Address

                        </label>


                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= e(
                                $email
                            ) ?>"
                            class="gh-forgot-input"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                            autofocus
                        >


                    </div>



                    <button
                        type="submit"
                        class="gh-forgot-submit"
                    >

                        Create Reset Link

                        <i class="bi bi-arrow-right"></i>

                    </button>


                </form>



                <!-- SECURITY MESSAGE -->

                <div class="gh-forgot-help">


                    <i class="bi bi-shield-check"></i>


                    <span>

                        For your security, Green Harvest
                        will not reveal whether an email
                        address is registered.

                    </span>


                </div>



                <!-- =================================================
                     LOCAL XAMPP RESET LINK
                ================================================== -->

                <?php if (
                    $isLocalDevelopment
                    &&
                    is_string(
                        $localResetUrl
                    )
                    &&
                    $localResetUrl !== ''
                ): ?>


                    <div class="gh-local-reset">


                        <div class="gh-local-reset-title">


                            <i class="bi bi-pc-display"></i>

                            Local testing mode


                        </div>


                        <p>

                            Email delivery is not required
                            while testing on localhost.
                            Use the button below to open
                            your secure reset link.

                        </p>


                        <a
                            href="<?= e(
                                $localResetUrl
                            ) ?>"
                            class="gh-local-reset-link"
                        >

                            Reset My Password

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>


                <?php endif; ?>



                <!-- BACK -->

                <div class="gh-forgot-back">


                    <a
                        href="<?= e(
                            url(
                                'login.php'
                            )
                        ) ?>"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Back to sign in

                    </a>


                </div>


            </div>


        </div>


    </div>


</section>