<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN LOGIN
 * =========================================================
 *
 * Authentication logic unchanged.
 * UI redesigned using the supplied minimal login reference.
 * =========================================================
 */

require_once __DIR__ . '/../includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Already Logged In
|--------------------------------------------------------------------------
*/

if (isAdmin()) {
    redirectTo('admin/dashboard.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF validation
     */
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        setFlash(
            'error',
            'Invalid login request. Please try again.'
        );

    } else {

        $email = strtolower(
            trim($_POST['email'] ?? '')
        );

        $password =
            (string) ($_POST['password'] ?? '');


        /*
         * Basic validation
         */
        if ($email === '' || $password === '') {

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
                 * Only administrator accounts are accepted.
                 */
                $stmt = $pdo->prepare(
                    '
                    SELECT
                        id,
                        full_name,
                        email,
                        password,
                        role
                    FROM users
                    WHERE email = ?
                      AND role = ?
                    LIMIT 1
                    '
                );

                $stmt->execute([
                    $email,
                    'admin',
                ]);

                $admin =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                /*
                 * Verify password
                 */
                if (
                    $admin &&
                    password_verify(
                        $password,
                        $admin['password']
                    )
                ) {

                    /*
                     * Prevent session fixation.
                     */
                    session_regenerate_id(true);

                    $_SESSION['user_id'] =
                        (int) $admin['id'];

                    $_SESSION['role'] =
                        'admin';

                    $_SESSION['full_name'] =
                        (string) $admin['full_name'];


                    setFlash(
                        'success',
                        'Welcome back, ' .
                        $admin['full_name'] .
                        '!'
                    );


                    redirectTo(
                        'admin/dashboard.php'
                    );
                }


                /*
                 * Generic error.
                 */
                setFlash(
                    'error',
                    'Invalid admin email or password.'
                );

            } catch (PDOException $e) {

                error_log(
                    'Green Harvest admin login error: ' .
                    $e->getMessage()
                );


                setFlash(
                    'error',
                    'We could not complete the login request. Please try again.'
                );
            }
        }
    }
}

?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Admin Login | <?= e(APP_NAME) ?>
    </title>


    <meta
        name="description"
        content="Secure Green Harvest administrator login."
    >


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap"
        rel="stylesheet"
    >


<style>

/* =========================================================
   RESET
========================================================= */

*,
*::before,
*::after {
    box-sizing: border-box;
}


html,
body {
    min-height: 100%;
}


body {
    margin: 0;

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 28px;

    font-family:
        'Inter',
        'Segoe UI',
        sans-serif;

    color: #17271d;

    background:
        radial-gradient(
            circle at 18% 18%,
            rgba(74, 222, 128, .18),
            transparent 26%
        ),
        radial-gradient(
            circle at 82% 85%,
            rgba(34, 197, 94, .12),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #071b10 0%,
            #0d321c 48%,
            #17652f 100%
        );

    overflow-x: hidden;
}


/* =========================================================
   BACKGROUND DETAILS
========================================================= */

body::before {
    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    opacity: .3;

    background-image:
        linear-gradient(
            rgba(255,255,255,.025) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.025) 1px,
            transparent 1px
        );

    background-size:
        32px 32px;
}


body::after {
    content: "";

    position: fixed;

    width: 420px;

    height: 420px;

    right: -180px;

    top: -180px;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.06);

    background:
        rgba(255,255,255,.015);

    pointer-events: none;
}


/* =========================================================
   PAGE CONTAINER
========================================================= */

.admin-login-container {
    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 410px;
}


/* =========================================================
   LOGIN CARD
========================================================= */

.admin-login-card {
    position: relative;

    overflow: hidden;

    padding:
        38px
        36px
        32px;

    border:
        1px solid
        rgba(255,255,255,.75);

    border-radius: 18px;

    background:
        rgba(255,255,255,.98);

    box-shadow:
        0 30px 80px
        rgba(0,0,0,.23);
}


/* Top Green Accent */

.admin-login-card::before {
    content: "";

    position: absolute;

    top: 0;

    left: 0;

    right: 0;

    height: 4px;

    background:
        linear-gradient(
            90deg,
            #166534,
            #22c55e,
            #4ade80
        );
}


/* =========================================================
   HEADER
========================================================= */

.admin-login-header {
    margin-bottom: 27px;

    text-align: center;
}


/* Logo */

.admin-logo-wrap {
    width: 58px;

    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin:
        0 auto
        17px;

    padding: 8px;

    border:
        1px solid
        rgba(20,83,45,.09);

    border-radius: 17px;

    background:
        #f0fdf4;

    box-shadow:
        0 8px 20px
        rgba(20,83,45,.08);
}


.admin-logo-wrap img {
    width: 100%;

    height: 100%;

    display: block;

    object-fit: contain;

    border-radius: 10px;
}


/* Admin Tag */

.admin-login-tag {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    margin-bottom: 10px;

    padding:
        5px
        9px;

    border-radius: 999px;

    background:
        #f0fdf4;

    color:
        #166534;

    font-size: .61rem;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.admin-login-tag-dot {
    width: 6px;

    height: 6px;

    border-radius: 50%;

    background:
        #22c55e;

    box-shadow:
        0 0 0 3px
        rgba(34,197,94,.1);
}


/* Heading */

.admin-login-header h1 {
    margin:
        0
        0
        6px;

    color:
        #092516;

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size: 1.65rem;

    font-weight: 800;

    line-height: 1.15;

    letter-spacing: -.04em;
}


.admin-login-header p {
    margin: 0;

    color:
        #718078;

    font-size: .79rem;

    line-height: 1.55;
}


/* =========================================================
   FLASH MESSAGE
========================================================= */

.admin-login-card .alert {
    margin-bottom: 17px;

    padding:
        10px
        12px;

    border-radius: 10px;

    font-size: .72rem;

    line-height: 1.5;
}


/* =========================================================
   FIELDS
========================================================= */

.admin-form-group {
    margin-bottom: 17px;
}


.admin-form-label {
    display: block;

    margin-bottom: 7px;

    color:
        #1f3326;

    font-size: .7rem;

    font-weight: 700;
}


/* Input */

.admin-input {
    width: 100%;

    height: 47px;

    padding:
        0
        14px;

    border:
        1.5px solid
        transparent;

    border-radius: 9px;

    outline: none;

    background:
        #f4f6f4;

    color:
        #17271d;

    font-family: inherit;

    font-size: .84rem;

    font-weight: 500;

    transition:
        background-color .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}


.admin-input::placeholder {
    color:
        #a1aaa4;
}


.admin-input:hover {
    background:
        #f0f4f1;
}


.admin-input:focus {
    border-color:
        #22a550;

    background:
        #ffffff;

    box-shadow:
        0 0 0 3px
        rgba(34,165,80,.11);
}


/* =========================================================
   PASSWORD
========================================================= */

.admin-password-wrapper {
    position: relative;
}


.admin-password-wrapper .admin-input {
    padding-right:
        48px;
}


.admin-password-toggle {
    position: absolute;

    top: 50%;

    right: 9px;

    width: 31px;

    height: 31px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    transform:
        translateY(-50%);

    padding: 0;

    border: 0;

    border-radius: 8px;

    background:
        transparent;

    color:
        #718078;

    cursor: pointer;

    transition:
        color .2s ease,
        background-color .2s ease;
}


.admin-password-toggle:hover {
    background:
        #eaf6ec;

    color:
        #166534;
}


.admin-password-toggle:focus-visible {
    outline:
        2px solid
        #22c55e;

    outline-offset:
        2px;
}


.admin-password-toggle svg {
    width: 18px;

    height: 18px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}


.eye-off {
    display: none;
}


.admin-password-toggle.visible
.eye-on {
    display: none;
}


.admin-password-toggle.visible
.eye-off {
    display: block;
}


/* =========================================================
   SECURITY LINE
========================================================= */

.admin-security-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin:
        -2px
        0
        21px;
}


.admin-security {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    color:
        #738078;

    font-size: .64rem;

    font-weight: 600;
}


.admin-security svg {
    width: 14px;

    height: 14px;

    color:
        #15803d;
}


/* =========================================================
   SUBMIT
========================================================= */

.admin-login-button {
    position: relative;

    width: 100%;

    min-height: 47px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding:
        10px
        16px;

    overflow: hidden;

    border: 0;

    border-radius: 999px;

    background:
        linear-gradient(
            135deg,
            #15803d,
            #166534
        );

    color:
        #ffffff;

    font-family: inherit;

    font-size: .82rem;

    font-weight: 800;

    letter-spacing: .01em;

    cursor: pointer;

    box-shadow:
        0 10px 22px
        rgba(20,83,45,.18);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}


.admin-login-button:hover {
    transform:
        translateY(-1px);

    box-shadow:
        0 14px 28px
        rgba(20,83,45,.23);

    background:
        linear-gradient(
            135deg,
            #16a34a,
            #14532d
        );
}


.admin-login-button:active {
    transform:
        translateY(0);
}


/* =========================================================
   DIVIDER
========================================================= */

.admin-divider {
    position: relative;

    margin:
        22px
        0;

    text-align: center;
}


.admin-divider::before {
    content: "";

    position: absolute;

    top: 50%;

    left: 0;

    right: 0;

    height: 1px;

    background:
        #e8ece9;
}


.admin-divider span {
    position: relative;

    padding:
        0
        11px;

    background:
        #ffffff;

    color:
        #9aa49d;

    font-size: .62rem;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .06em;
}


/* =========================================================
   RETURN BUTTON
========================================================= */

.admin-storefront-link {
    width: 100%;

    min-height: 43px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding:
        9px
        14px;

    border:
        1px solid
        #dfe5e1;

    border-radius: 999px;

    background:
        #ffffff;

    color:
        #294332;

    font-size: .72rem;

    font-weight: 700;

    text-decoration: none;

    transition:
        border-color .2s ease,
        background-color .2s ease,
        color .2s ease,
        transform .2s ease;
}


.admin-storefront-link:hover {
    transform:
        translateY(-1px);

    border-color:
        rgba(21,128,61,.28);

    background:
        #f7faf7;

    color:
        #166534;
}


.admin-storefront-link svg {
    width: 15px;

    height: 15px;
}


/* =========================================================
   FOOTER
========================================================= */

.admin-login-footer {
    margin-top: 19px;

    text-align: center;

    color:
        rgba(255,255,255,.58);

    font-size: .62rem;

    letter-spacing: .04em;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 480px
) {

    body {
        padding: 16px;
    }


    .admin-login-card {
        padding:
            31px
            23px
            27px;

        border-radius:
            15px;
    }


    .admin-logo-wrap {
        width: 52px;

        height: 52px;

        margin-bottom:
            14px;
    }


    .admin-login-header {
        margin-bottom:
            23px;
    }


    .admin-login-header h1 {
        font-size:
            1.45rem;
    }


    .admin-input {
        height:
            45px;
    }


    .admin-login-button {
        min-height:
            45px;
    }


    .admin-security-row {
        align-items:
            flex-start;
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
        animation-duration:
            .01ms
            !important;

        animation-iteration-count:
            1
            !important;

        transition-duration:
            .01ms
            !important;
    }

}

</style>

</head>


<body>


    <main class="admin-login-container">


        <section class="admin-login-card">


            <!-- =============================================
                 HEADER
            ============================================== -->

            <div class="admin-login-header">


                <div class="admin-logo-wrap">


                    <img
                        src="<?= e(
                            url(
                                'assets/images/placeholder.svg'
                            )
                        ) ?>"
                        alt="Green Harvest"
                    >


                </div>



                <div class="admin-login-tag">


                    <span class="admin-login-tag-dot"></span>

                    Administrator Portal


                </div>



                <h1>

                    Sign in to Green Harvest

                </h1>


                <p>

                    Enter your administrator credentials
                    to manage the store.

                </p>


            </div>



            <!-- =============================================
                 FLASH MESSAGE
            ============================================== -->

            <?php displayFlash(); ?>



            <!-- =============================================
                 LOGIN FORM
            ============================================== -->

            <form
                method="post"
                autocomplete="on"
            >


                <?= csrfField() ?>



                <!-- EMAIL -->

                <div class="admin-form-group">


                    <label
                        for="admin_email"
                        class="admin-form-label"
                    >

                        Email address

                    </label>


                    <input
                        id="admin_email"
                        type="email"
                        name="email"
                        value="<?= e(
                            $email
                        ) ?>"
                        class="admin-input"
                        placeholder="admin@greenharvest.com"
                        autocomplete="email"
                        required
                        autofocus
                    >


                </div>



                <!-- PASSWORD -->

                <div class="admin-form-group">


                    <label
                        for="admin_password"
                        class="admin-form-label"
                    >

                        Password

                    </label>


                    <div class="admin-password-wrapper">


                        <input
                            id="admin_password"
                            type="password"
                            name="password"
                            class="admin-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >



                        <button
                            type="button"
                            id="togglePassword"
                            class="admin-password-toggle"
                            aria-label="Show password"
                            aria-pressed="false"
                        >


                            <!-- EYE OPEN -->

                            <svg
                                class="eye-on"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >

                                <path
                                    d="
                                        M2.7 12
                                        C4.9 7.9
                                        8.1 5.8
                                        12 5.8
                                        C15.9 5.8
                                        19.1 7.9
                                        21.3 12
                                        C19.1 16.1
                                        15.9 18.2
                                        12 18.2
                                        C8.1 18.2
                                        4.9 16.1
                                        2.7 12
                                        Z
                                    "
                                ></path>


                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                ></circle>


                            </svg>



                            <!-- EYE CLOSED -->

                            <svg
                                class="eye-off"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >

                                <path
                                    d="M3 3L21 21"
                                ></path>


                                <path
                                    d="
                                        M10.7 6
                                        C11.1 5.9
                                        11.6 5.8
                                        12 5.8
                                        C15.9 5.8
                                        19.1 7.9
                                        21.3 12
                                    "
                                ></path>


                                <path
                                    d="
                                        M15.5 17.3
                                        C14.4 17.9
                                        13.2 18.2
                                        12 18.2
                                        C8.1 18.2
                                        4.9 16.1
                                        2.7 12
                                    "
                                ></path>


                            </svg>


                        </button>


                    </div>


                </div>



                <!-- SECURITY MESSAGE -->

                <div class="admin-security-row">


                    <span class="admin-security">


                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >

                            <path
                                d="
                                    M12 22
                                    S20 18
                                    20 12
                                    V5
                                    L12 2
                                    L4 5
                                    V12
                                    C4 18
                                    12 22
                                    12 22Z
                                "
                            ></path>


                            <path
                                d="
                                    M9 12
                                    L11 14
                                    L15 10
                                "
                            ></path>


                        </svg>


                        Secure admin access


                    </span>


                </div>



                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="admin-login-button"
                >

                    Access Dashboard

                </button>


            </form>



            <!-- =============================================
                 DIVIDER
            ============================================== -->

            <div class="admin-divider">


                <span>

                    Green Harvest

                </span>


            </div>



            <!-- =============================================
                 RETURN TO STORE
            ============================================== -->

            <a
                href="<?= e(
                    url(
                        'index.php'
                    )
                ) ?>"
                class="admin-storefront-link"
            >


                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >

                    <path
                        d="M19 12H5"
                    ></path>

                    <path
                        d="M12 19L5 12L12 5"
                    ></path>

                </svg>


                Back to public storefront


            </a>


        </section>



        <!-- =============================================
             PAGE FOOTER
        ============================================== -->

        <div class="admin-login-footer">

            © 2026 Green Harvest · Administrator Access

        </div>


    </main>



<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /*
        |--------------------------------------------------------------------------
        | Password Visibility
        |--------------------------------------------------------------------------
        */

        const password =
            document.getElementById(
                'admin_password'
            );


        const toggle =
            document.getElementById(
                'togglePassword'
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


                toggle.classList.toggle(
                    'visible',
                    hidden
                );


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


</body>

</html>