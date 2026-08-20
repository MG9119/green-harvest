<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - RESET PASSWORD
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
| Reset Token
|--------------------------------------------------------------------------
*/

$token =
    trim(
        (string) (
            $_POST['token']
            ?? $_GET['token']
            ?? ''
        )
    );


$tokenValid = false;
$resetRecord = null;


/*
|--------------------------------------------------------------------------
| Validate Token Format
|--------------------------------------------------------------------------
*/

if (
    $token !== ''
    &&
    preg_match(
        '/^[a-f0-9]{64}$/i',
        $token
    )
) {

    try {

        /*
         * Hash token before database lookup.
         *
         * The raw token should never be stored
         * directly in the database.
         */
        $tokenHash =
            hash(
                'sha256',
                $token
            );


        $stmt =
            $pdo->prepare(
                '
                SELECT
                    prt.id AS reset_id,
                    prt.user_id,
                    prt.expires_at,
                    u.email,
                    u.full_name

                FROM password_reset_tokens prt

                INNER JOIN users u
                    ON u.id = prt.user_id

                WHERE prt.token_hash = ?
                AND prt.used_at IS NULL
                AND prt.expires_at > NOW()

                LIMIT 1
                '
            );


        $stmt->execute([
            $tokenHash,
        ]);


        $resetRecord =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($resetRecord) {

            $tokenValid = true;

        }


    } catch (PDOException $e) {

        error_log(
            'Green Harvest reset token validation error: ' .
            $e->getMessage()
        );

        $tokenValid = false;

    }

}


/*
|--------------------------------------------------------------------------
| Handle Password Reset
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


    } elseif (
        !$tokenValid
        ||
        !$resetRecord
    ) {

        setFlash(
            'error',
            'This password reset link is invalid or has expired.'
        );


    } else {

        $newPassword =
            (string) (
                $_POST['new_password']
                ?? ''
            );


        $confirmPassword =
            (string) (
                $_POST['confirm_password']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Password Validation
        |--------------------------------------------------------------------------
        */

        if (
            $newPassword === ''
            ||
            $confirmPassword === ''
        ) {

            setFlash(
                'error',
                'Please enter and confirm your new password.'
            );


        } elseif (
            strlen($newPassword) < 8
        ) {

            setFlash(
                'error',
                'Password must be at least 8 characters.'
            );


        } elseif (
            strlen($newPassword) > 128
        ) {

            setFlash(
                'error',
                'Password must not exceed 128 characters.'
            );


        } elseif (
            !preg_match(
                '/[A-Z]/',
                $newPassword
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one uppercase letter.'
            );


        } elseif (
            !preg_match(
                '/[a-z]/',
                $newPassword
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one lowercase letter.'
            );


        } elseif (
            !preg_match(
                '/[0-9]/',
                $newPassword
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one number.'
            );


        } elseif (
            !preg_match(
                '/[^A-Za-z0-9\s]/',
                $newPassword
            )
        ) {

            setFlash(
                'error',
                'Password must contain at least one special character.'
            );


        } elseif (
            preg_match(
                '/\s/',
                $newPassword
            )
        ) {

            setFlash(
                'error',
                'Password must not contain spaces.'
            );


        } elseif (
            $newPassword !==
            $confirmPassword
        ) {

            setFlash(
                'error',
                'Passwords do not match.'
            );


        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | Transaction
                |--------------------------------------------------------------------------
                */

                $pdo->beginTransaction();


                /*
                 * Lock reset token to prevent
                 * it being used twice.
                 */
                $lockStmt =
                    $pdo->prepare(
                        '
                        SELECT
                            id,
                            user_id

                        FROM password_reset_tokens

                        WHERE id = ?
                        AND used_at IS NULL
                        AND expires_at > NOW()

                        FOR UPDATE
                        '
                    );


                $lockStmt->execute([
                    (int)
                    $resetRecord['reset_id'],
                ]);


                $lockedToken =
                    $lockStmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$lockedToken) {

                    $pdo->rollBack();


                    setFlash(
                        'error',
                        'This password reset link has already been used or has expired.'
                    );


                } else {

                    /*
                     * Hash new password.
                     */
                    $passwordHash =
                        password_hash(
                            $newPassword,
                            PASSWORD_DEFAULT
                        );


                    /*
                     * Update user password.
                     */
                    $updateUser =
                        $pdo->prepare(
                            '
                            UPDATE users

                            SET password = ?

                            WHERE id = ?
                            '
                        );


                    $updateUser->execute([
                        $passwordHash,
                        (int)
                        $lockedToken['user_id'],
                    ]);


                    /*
                     * Mark this reset link as used.
                     */
                    $markUsed =
                        $pdo->prepare(
                            '
                            UPDATE password_reset_tokens

                            SET used_at = NOW()

                            WHERE id = ?
                            '
                        );


                    $markUsed->execute([
                        (int)
                        $lockedToken['id'],
                    ]);


                    /*
                     * Invalidate any other active
                     * reset tokens for this user.
                     */
                    $invalidate =
                        $pdo->prepare(
                            '
                            UPDATE password_reset_tokens

                            SET used_at = NOW()

                            WHERE user_id = ?
                            AND used_at IS NULL
                            '
                        );


                    $invalidate->execute([
                        (int)
                        $lockedToken['user_id'],
                    ]);


                    $pdo->commit();


                    setFlash(
                        'success',
                        'Your password has been changed successfully. You can now sign in.'
                    );


                    redirectTo(
                        'login.php'
                    );

                }


            } catch (Throwable $e) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();

                }


                error_log(
                    'Green Harvest password reset error: ' .
                    $e->getMessage()
                );


                setFlash(
                    'error',
                    'We could not reset your password right now. Please try again.'
                );

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Reset Password';

?>


<style>

@import url(
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap'
);


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --reset-950:
        #092516;

    --reset-900:
        #14532d;

    --reset-800:
        #166534;

    --reset-700:
        #15803d;

    --reset-600:
        #16a34a;

    --reset-500:
        #4ade80;

    --reset-50:
        #f0fdf4;

    --reset-cream:
        #fafcf9;

    --reset-white:
        #ffffff;

    --reset-text:
        #1a2a22;

    --reset-muted:
        #66756b;

    --reset-border:
        rgba(
            20,
            83,
            45,
            .14
        );

    --reset-danger:
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
        var(--reset-cream);

    color:
        var(--reset-text);

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
   PAGE
========================================================= */

.gh-reset-shell {

    min-height:
        100vh;

}


.gh-reset-layout {

    min-height:
        100vh;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.28fr)
        minmax(390px, .72fr);

}


/* =========================================================
   LEFT PANEL
========================================================= */

.gh-reset-visual {

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


.gh-reset-visual::before {

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


.gh-reset-visual::after {

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


.gh-reset-visual
> * {

    position:
        relative;

    z-index:
        2;

}


/* =========================================================
   BRAND
========================================================= */

.gh-reset-brand {

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


.gh-reset-brand img {

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

.gh-reset-copy {

    max-width:
        640px;

}


.gh-reset-copy h1 {

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


.gh-reset-copy
.accent {

    color:
        #d7f9df;

}


.gh-reset-copy p {

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


.gh-reset-footer {

    color:
        rgba(255,255,255,.55);

    font-size:
        .74rem;

}


/* =========================================================
   FORM PANEL
========================================================= */

.gh-reset-panel {

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
        var(--reset-cream);

}


.gh-reset-form {

    width:
        100%;

    max-width:
        360px;

    margin:
        0 auto;

}


/* =========================================================
   FORM BRAND
========================================================= */

.gh-reset-form-brand {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        20px;

    color:
        var(--reset-900);

}


.gh-reset-form-brand img {

    width:
        30px;

    height:
        30px;

    object-fit:
        contain;

    border-radius:
        8px;

}


.gh-reset-form-brand span {

    font-size:
        1rem;

    font-weight:
        800;

}


/* =========================================================
   HEADING
========================================================= */

.gh-reset-heading {

    margin:
        0
        0
        6px;

    color:
        var(--reset-950);

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


.gh-reset-subtitle {

    margin:
        0
        0
        21px;

    color:
        var(--reset-muted);

    font-size:
        .8rem;

    line-height:
        1.55;

}


/* =========================================================
   EMAIL LABEL
========================================================= */

.gh-reset-account {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        18px;

    padding:
        10px
        12px;

    border:
        1px solid
        rgba(20,83,45,.1);

    border-radius:
        10px;

    background:
        #f5faf6;

}


.gh-reset-account-icon {

    width:
        30px;

    height:
        30px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        9px;

    background:
        var(--reset-50);

    color:
        var(--reset-700);

}


.gh-reset-account small {

    display:
        block;

    color:
        var(--reset-muted);

    font-size:
        .59rem;

    font-weight:
        700;

    text-transform:
        uppercase;

    letter-spacing:
        .05em;

}


.gh-reset-account strong {

    display:
        block;

    margin-top:
        1px;

    color:
        var(--reset-text);

    font-size:
        .76rem;

}


/* =========================================================
   FIELD
========================================================= */

.gh-reset-field {

    margin-bottom:
        14px;

}


.gh-reset-label {

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


.gh-reset-input-wrap {

    position:
        relative;

}


.gh-reset-input {

    width:
        100%;

    height:
        44px;

    padding:
        0
        50px
        0
        13px;

    border:
        1px solid
        var(--reset-border);

    border-radius:
        10px;

    background:
        #ffffff;

    color:
        var(--reset-text);

    font-size:
        .82rem;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-reset-input::placeholder {

    color:
        #9ca69f;

}


.gh-reset-input:focus {

    border-color:
        rgba(21,128,61,.52);

    outline:
        none;

    box-shadow:
        0 0 0 3px
        rgba(74,222,128,.1);

}


/* =========================================================
   PASSWORD EYE
========================================================= */

.gh-reset-eye {

    position:
        absolute;

    right:
        5px;

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
        var(--reset-50);

    color:
        var(--reset-700);

    cursor:
        pointer;

}


.gh-reset-eye:hover {

    background:
        #dcfce7;

}


.gh-reset-eye svg {

    width:
        18px;

    height:
        18px;

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


.gh-reset-eye
.eye-hidden {

    display:
        none;

}


.gh-reset-eye.visible
.eye-visible {

    display:
        none;

}


.gh-reset-eye.visible
.eye-hidden {

    display:
        block;

}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

.gh-reset-strength {

    margin-top:
        9px;

}


.gh-reset-strength-head {

    display:
        flex;

    justify-content:
        space-between;

    margin-bottom:
        5px;

    color:
        var(--reset-muted);

    font-size:
        .63rem;

}


.gh-reset-strength-label {

    font-weight:
        800;

}


.gh-reset-strength-track {

    height:
        4px;

    overflow:
        hidden;

    border-radius:
        999px;

    background:
        #e6ebe7;

}


.gh-reset-strength-bar {

    width:
        0;

    height:
        100%;

    transition:
        width .25s ease,
        background-color .25s ease;

}


.gh-reset-strength[data-strength="weak"]
.gh-reset-strength-bar {

    width:
        25%;

    background:
        #dc2626;

}


.gh-reset-strength[data-strength="fair"]
.gh-reset-strength-bar {

    width:
        50%;

    background:
        #f59e0b;

}


.gh-reset-strength[data-strength="good"]
.gh-reset-strength-bar {

    width:
        75%;

    background:
        #84cc16;

}


.gh-reset-strength[data-strength="strong"]
.gh-reset-strength-bar {

    width:
        100%;

    background:
        var(--reset-600);

}


/* =========================================================
   PASSWORD CONDITIONS
========================================================= */

.gh-reset-rules {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        6px
        11px;

    margin:
        10px
        0
        15px;

    padding:
        0;

    list-style:
        none;

}


.gh-reset-rule {

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--reset-muted);

    font-size:
        .59rem;

}


.gh-reset-rule-dot {

    width:
        15px;

    height:
        15px;

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

}


.gh-reset-rule-dot::before {

    content:
        "•";

}


.gh-reset-rule.valid {

    color:
        var(--reset-800);

}


.gh-reset-rule.valid
.gh-reset-rule-dot {

    background:
        #dcfce7;

    color:
        var(--reset-700);

}


.gh-reset-rule.valid
.gh-reset-rule-dot::before {

    content:
        "✓";

    font-size:
        .5rem;

    font-weight:
        800;

}


/* =========================================================
   PASSWORD MATCH
========================================================= */

.gh-reset-match {

    min-height:
        15px;

    margin-top:
        5px;

    font-size:
        .62rem;

    font-weight:
        700;

}


.gh-reset-match.match {

    color:
        var(--reset-700);

}


.gh-reset-match.no-match {

    color:
        var(--reset-danger);

}


/* =========================================================
   BUTTON
========================================================= */

.gh-reset-submit {

    width:
        100%;

    min-height:
        44px;

    margin-top:
        3px;

    border:
        0;

    border-radius:
        10px;

    background:
        linear-gradient(
            135deg,
            var(--reset-700),
            var(--reset-900)
        );

    color:
        #ffffff;

    font-size:
        .83rem;

    font-weight:
        750;

    cursor:
        pointer;

    box-shadow:
        0 9px 20px
        rgba(20,83,45,.14);

}


.gh-reset-submit:hover {

    box-shadow:
        0 12px 24px
        rgba(20,83,45,.18);

}


/* =========================================================
   INVALID LINK
========================================================= */

.gh-reset-invalid {

    padding:
        24px;

    border:
        1px solid
        rgba(220,38,38,.12);

    border-radius:
        14px;

    background:
        #fffafa;

    text-align:
        center;

}


.gh-reset-invalid-icon {

    width:
        48px;

    height:
        48px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        13px;

    border-radius:
        14px;

    background:
        #fef2f2;

    color:
        var(--reset-danger);

    font-size:
        1.2rem;

}


.gh-reset-invalid h3 {

    margin-bottom:
        7px;

    color:
        var(--reset-950);

    font-size:
        1rem;

}


.gh-reset-invalid p {

    margin-bottom:
        18px;

    color:
        var(--reset-muted);

    font-size:
        .76rem;

    line-height:
        1.6;

}


/* =========================================================
   BACK LINK
========================================================= */

.gh-reset-back {

    margin-top:
        16px;

    text-align:
        center;

}


.gh-reset-back a {

    color:
        var(--reset-900);

    font-size:
        .7rem;

    font-weight:
        700;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 980px
) {

    .gh-reset-layout {

        grid-template-columns:
            1fr;

    }


    .gh-reset-visual {

        min-height:
            250px;

        padding:
            28px;

    }


    .gh-reset-copy {

        margin:
            35px
            0
            25px;

    }


    .gh-reset-copy h1 {

        font-size:
            3.5rem;

    }


    .gh-reset-panel {

        padding:
            46px
            22px
            54px;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-reset-visual {

        min-height:
            210px;

        padding:
            20px;

    }


    .gh-reset-copy {

        margin:
            26px
            0
            17px;

    }


    .gh-reset-copy h1 {

        font-size:
            2.45rem;

    }


    .gh-reset-copy p {

        font-size:
            .8rem;

    }


    .gh-reset-footer {

        display:
            none;

    }


    .gh-reset-panel {

        padding:
            34px
            19px
            42px;

    }


    .gh-reset-form {

        max-width:
            360px;

    }

}


@media (
    max-width: 350px
) {

    .gh-reset-rules {

        grid-template-columns:
            1fr;

    }

}

</style>



<section class="gh-reset-shell">


    <div class="gh-reset-layout">


        <!-- =================================================
             LEFT PANEL
        ================================================== -->

        <div class="gh-reset-visual">


            <div class="gh-reset-brand">


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



            <div class="gh-reset-copy">


                <h1>

                    Fresh access,

                    <br>

                    <span class="accent">

                        secure again.

                    </span>

                </h1>


                <p>

                    Choose a strong new password
                    to keep your Green Harvest
                    account protected.

                </p>


            </div>



            <div class="gh-reset-footer">

                © 2026 Green Harvest.
                Fresh • Organic • Local.

            </div>


        </div>



        <!-- =================================================
             RIGHT PANEL
        ================================================== -->

        <div class="gh-reset-panel">


            <div class="gh-reset-form">


                <!-- BRAND -->

                <div class="gh-reset-form-brand">


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



                <?php displayFlash(); ?>



                <?php if (
                    $tokenValid
                    &&
                    $resetRecord
                ): ?>


                    <!-- =============================================
                         VALID RESET LINK
                    ============================================== -->

                    <h2 class="gh-reset-heading">

                        Reset password

                    </h2>


                    <p class="gh-reset-subtitle">

                        Create a new password for
                        your Green Harvest account.

                    </p>



                    <!-- ACCOUNT -->

                    <div class="gh-reset-account">


                        <span class="gh-reset-account-icon">

                            @

                        </span>


                        <div>


                            <small>

                                Account

                            </small>


                            <strong>

                                <?= e(
                                    $resetRecord['email']
                                ) ?>

                            </strong>


                        </div>


                    </div>



                    <form
                        method="post"
                        action="<?= e(
                            url(
                                'reset-password.php'
                            )
                        ) ?>"
                        autocomplete="off"
                    >


                        <?= csrfField() ?>


                        <input
                            type="hidden"
                            name="token"
                            value="<?= e(
                                $token
                            ) ?>"
                        >



                        <!-- =========================================
                             NEW PASSWORD
                        ========================================== -->

                        <div class="gh-reset-field">


                            <label
                                for="new_password"
                                class="gh-reset-label"
                            >

                                New Password

                            </label>


                            <div class="gh-reset-input-wrap">


                                <input
                                    id="new_password"
                                    type="password"
                                    name="new_password"
                                    class="gh-reset-input"
                                    placeholder="Create a strong password"
                                    autocomplete="new-password"
                                    maxlength="128"
                                    required
                                >



                                <button
                                    type="button"
                                    class="gh-reset-eye"
                                    data-password-target="new_password"
                                    aria-label="Show password"
                                >


                                    <svg
                                        class="eye-visible"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >

                                        <path
                                            d="
                                                M2.5 12
                                                C4.7 7.8 8 5.7 12 5.7
                                                C16 5.7 19.3 7.8 21.5 12
                                                C19.3 16.2 16 18.3 12 18.3
                                                C8 18.3 4.7 16.2 2.5 12Z
                                            "
                                        ></path>

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        ></circle>

                                    </svg>


                                    <svg
                                        class="eye-hidden"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >

                                        <path
                                            d="M3 3L21 21"
                                        ></path>

                                        <path
                                            d="M10.7 6C11.1 5.9 11.6 5.8 12 5.8C16 5.8 19.3 7.9 21.5 12"
                                        ></path>

                                        <path
                                            d="M15.5 17.4C14.4 17.9 13.2 18.2 12 18.2C8 18.2 4.7 16.1 2.5 12"
                                        ></path>

                                    </svg>


                                </button>


                            </div>



                            <!-- PASSWORD STRENGTH -->

                            <div
                                class="gh-reset-strength"
                                id="passwordStrength"
                                data-strength=""
                            >


                                <div class="gh-reset-strength-head">


                                    <span>

                                        Password strength

                                    </span>


                                    <span
                                        id="passwordStrengthLabel"
                                        class="gh-reset-strength-label"
                                    >

                                        —

                                    </span>


                                </div>


                                <div class="gh-reset-strength-track">


                                    <div class="gh-reset-strength-bar"></div>


                                </div>


                            </div>



                            <!-- RULES -->

                            <ul class="gh-reset-rules">


                                <li
                                    class="gh-reset-rule"
                                    id="ruleLength"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    8+ characters

                                </li>


                                <li
                                    class="gh-reset-rule"
                                    id="ruleUpper"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    Uppercase

                                </li>


                                <li
                                    class="gh-reset-rule"
                                    id="ruleLower"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    Lowercase

                                </li>


                                <li
                                    class="gh-reset-rule"
                                    id="ruleNumber"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    Number

                                </li>


                                <li
                                    class="gh-reset-rule"
                                    id="ruleSpecial"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    Special character

                                </li>


                                <li
                                    class="gh-reset-rule"
                                    id="ruleSpaces"
                                >

                                    <span class="gh-reset-rule-dot"></span>

                                    No spaces

                                </li>


                            </ul>


                        </div>



                        <!-- =========================================
                             CONFIRM PASSWORD
                        ========================================== -->

                        <div class="gh-reset-field">


                            <label
                                for="confirm_password"
                                class="gh-reset-label"
                            >

                                Confirm Password

                            </label>


                            <div class="gh-reset-input-wrap">


                                <input
                                    id="confirm_password"
                                    type="password"
                                    name="confirm_password"
                                    class="gh-reset-input"
                                    placeholder="Repeat your new password"
                                    autocomplete="new-password"
                                    maxlength="128"
                                    required
                                >


                                <button
                                    type="button"
                                    class="gh-reset-eye"
                                    data-password-target="confirm_password"
                                    aria-label="Show password"
                                >


                                    <svg
                                        class="eye-visible"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >

                                        <path
                                            d="
                                                M2.5 12
                                                C4.7 7.8 8 5.7 12 5.7
                                                C16 5.7 19.3 7.8 21.5 12
                                                C19.3 16.2 16 18.3 12 18.3
                                                C8 18.3 4.7 16.2 2.5 12Z
                                            "
                                        ></path>

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        ></circle>

                                    </svg>


                                    <svg
                                        class="eye-hidden"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >

                                        <path
                                            d="M3 3L21 21"
                                        ></path>

                                        <path
                                            d="M10.7 6C11.1 5.9 11.6 5.8 12 5.8C16 5.8 19.3 7.9 21.5 12"
                                        ></path>

                                    </svg>


                                </button>


                            </div>


                            <div
                                id="passwordMatch"
                                class="gh-reset-match"
                                aria-live="polite"
                            ></div>


                        </div>



                        <button
                            type="submit"
                            class="gh-reset-submit"
                        >

                            Reset Password

                        </button>


                    </form>



                <?php else: ?>


                    <!-- =============================================
                         INVALID / EXPIRED TOKEN
                    ============================================== -->

                    <h2 class="gh-reset-heading">

                        Reset link unavailable

                    </h2>


                    <p class="gh-reset-subtitle">

                        This password reset link
                        cannot be used.

                    </p>


                    <div class="gh-reset-invalid">


                        <span class="gh-reset-invalid-icon">

                            !

                        </span>


                        <h3>

                            Link invalid or expired

                        </h3>


                        <p>

                            Password reset links are
                            temporary and can only be
                            used once. Request a new
                            link to continue.

                        </p>


                        <a
                            href="<?= e(
                                url(
                                    'forgot-password.php'
                                )
                            ) ?>"
                            class="btn btn-green"
                        >

                            Request New Link

                        </a>


                    </div>


                <?php endif; ?>



                <!-- BACK -->

                <div class="gh-reset-back">


                    <a
                        href="<?= e(
                            url(
                                'login.php'
                            )
                        ) ?>"
                    >

                        ← Back to sign in

                    </a>


                </div>


            </div>


        </div>


    </div>


</section>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | Password Visibility
        |--------------------------------------------------------------------------
        */

        const eyeButtons =
            document.querySelectorAll(
                '[data-password-target]'
            );


        eyeButtons.forEach(
            function (button) {


                button.addEventListener(
                    'click',
                    function () {


                        const targetId =
                            button.dataset
                                .passwordTarget;


                        const input =
                            document.getElementById(
                                targetId
                            );


                        if (!input) {

                            return;

                        }


                        const hidden =
                            input.type ===
                            'password';


                        input.type =
                            hidden
                                ? 'text'
                                : 'password';


                        button.classList.toggle(
                            'visible',
                            hidden
                        );


                        button.setAttribute(
                            'aria-label',
                            hidden
                                ? 'Hide password'
                                : 'Show password'
                        );


                    }
                );


            }
        );



        /*
        |--------------------------------------------------------------------------
        | Password Strength
        |--------------------------------------------------------------------------
        */

        const password =
            document.getElementById(
                'new_password'
            );


        const confirmation =
            document.getElementById(
                'confirm_password'
            );


        if (!password) {

            return;

        }


        const strength =
            document.getElementById(
                'passwordStrength'
            );


        const strengthLabel =
            document.getElementById(
                'passwordStrengthLabel'
            );


        const match =
            document.getElementById(
                'passwordMatch'
            );


        const rules = {

            length:
                document.getElementById(
                    'ruleLength'
                ),

            upper:
                document.getElementById(
                    'ruleUpper'
                ),

            lower:
                document.getElementById(
                    'ruleLower'
                ),

            number:
                document.getElementById(
                    'ruleNumber'
                ),

            special:
                document.getElementById(
                    'ruleSpecial'
                ),

            spaces:
                document.getElementById(
                    'ruleSpaces'
                )

        };


        function setRule(
            element,
            valid
        ) {

            if (element) {

                element.classList.toggle(
                    'valid',
                    valid
                );

            }

        }


        function checkPassword() {


            const value =
                password.value;


            const validLength =
                value.length >= 8;


            const validUpper =
                /[A-Z]/.test(
                    value
                );


            const validLower =
                /[a-z]/.test(
                    value
                );


            const validNumber =
                /[0-9]/.test(
                    value
                );


            const validSpecial =
                /[^A-Za-z0-9\s]/.test(
                    value
                );


            const validSpaces =
                value.length > 0
                &&
                !/\s/.test(
                    value
                );


            setRule(
                rules.length,
                validLength
            );


            setRule(
                rules.upper,
                validUpper
            );


            setRule(
                rules.lower,
                validLower
            );


            setRule(
                rules.number,
                validNumber
            );


            setRule(
                rules.special,
                validSpecial
            );


            setRule(
                rules.spaces,
                validSpaces
            );


            if (
                value.length === 0
            ) {

                strength.dataset.strength =
                    '';


                strengthLabel.textContent =
                    '—';


                checkMatch();

                return;

            }


            let score = 0;


            if (validLength) score++;
            if (validUpper) score++;
            if (validLower) score++;
            if (validNumber) score++;
            if (validSpecial) score++;


            if (
                value.length >= 12
            ) {

                score++;

            }


            let level =
                'weak';


            let text =
                'Weak';


            if (
                score >= 6
                &&
                validSpaces
            ) {

                level =
                    'strong';

                text =
                    'Strong';


            } else if (
                score >= 5
            ) {

                level =
                    'good';

                text =
                    'Good';


            } else if (
                score >= 3
            ) {

                level =
                    'fair';

                text =
                    'Fair';

            }


            strength.dataset.strength =
                level;


            strengthLabel.textContent =
                text;


            checkMatch();


        }


        function checkMatch() {


            if (
                !confirmation
                ||
                !match
            ) {

                return;

            }


            if (
                confirmation.value === ''
            ) {

                match.textContent =
                    '';


                match.className =
                    'gh-reset-match';


                return;

            }


            if (
                confirmation.value ===
                password.value
            ) {

                match.textContent =
                    '✓ Passwords match';


                match.className =
                    'gh-reset-match match';


            } else {

                match.textContent =
                    '✕ Passwords do not match';


                match.className =
                    'gh-reset-match no-match';

            }

        }


        password.addEventListener(
            'input',
            checkPassword
        );


        if (confirmation) {

            confirmation.addEventListener(
                'input',
                checkMatch
            );

        }


    }
);

</script>