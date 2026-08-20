<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CUSTOMER ACCOUNT
 * =========================================================
 *
 * Responsibilities:
 * - Protect the page from unauthenticated visitors
 * - Display customer account information
 * - Update customer profile
 * - Display order/cart statistics
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

requireLogin();


/*
|--------------------------------------------------------------------------
| Redirect Admins
|--------------------------------------------------------------------------
|
| Administrators should use the administration dashboard rather
| than the customer account area.
|
*/

if (isAdmin()) {
    redirectTo('admin/dashboard.php');
}


/*
|--------------------------------------------------------------------------
| Current Customer
|--------------------------------------------------------------------------
*/

$user = currentUser($pdo);

if (!$user) {

    setFlash(
        'error',
        'Your account could not be found. Please sign in again.'
    );

    redirectTo('login.php');
}


$userId = getUserId();

if ($userId === null) {
    redirectTo('login.php');
}


/*
|--------------------------------------------------------------------------
| Handle Profile Update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF validation.
     */
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {

        setFlash(
            'error',
            'Invalid profile update request. Please try again.'
        );

    } else {

        $name = trim(
            (string) ($_POST['full_name'] ?? '')
        );

        $email = strtolower(
            trim(
                (string) ($_POST['email'] ?? '')
            )
        );

        $phone = trim(
            (string) ($_POST['phone'] ?? '')
        );

        $address = trim(
            (string) ($_POST['address'] ?? '')
        );


        /*
         * Keep submitted values visible if validation fails.
         */
        $user['full_name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['address'] = $address;


        /*
         * Validation.
         */
        if ($name === '') {

            setFlash(
                'error',
                'Full name is required.'
            );

        } elseif (strlen($name) < 2) {

            setFlash(
                'error',
                'Please enter a valid full name.'
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
            $phone !== '' &&
            strlen($phone) < 7
        ) {

            setFlash(
                'error',
                'Please enter a valid phone number.'
            );

        } else {

            try {

                /*
                 * Make sure another customer does not already
                 * use this email address.
                 */
                $stmt = $pdo->prepare(
                    '
                    SELECT id
                    FROM users
                    WHERE email = ?
                      AND id != ?
                    LIMIT 1
                    '
                );

                $stmt->execute([
                    $email,
                    $userId,
                ]);


                if ($stmt->fetchColumn()) {

                    setFlash(
                        'error',
                        'An account with this email address already exists.'
                    );

                } else {

                    /*
                     * Update customer.
                     */
                    $stmt = $pdo->prepare(
                        '
                        UPDATE users

                        SET
                            full_name = ?,
                            email = ?,
                            phone = ?,
                            address = ?

                        WHERE id = ?
                        '
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        $phone,
                        $address,
                        $userId,
                    ]);


                    /*
                     * Keep session name synchronized with database.
                     */
                    $_SESSION['full_name'] = $name;


                    setFlash(
                        'success',
                        'Your profile has been updated successfully.'
                    );


                    /*
                     * PRG pattern:
                     *
                     * POST
                     * ↓
                     * Redirect
                     * ↓
                     * GET
                     *
                     * Prevents accidental duplicate submissions.
                     */
                    redirectTo('account.php');
                }

            } catch (PDOException $e) {

                error_log(
                    'Green Harvest account update error: ' .
                    $e->getMessage()
                );

                setFlash(
                    'error',
                    'Your profile could not be updated. Please try again.'
                );
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Account Statistics
|--------------------------------------------------------------------------
*/

$orderCount = 0;
$cartItemCount = 0;

try {

    /*
     * Number of orders.
     */
    $stmt = $pdo->prepare(
        '
        SELECT COUNT(*)
        FROM orders
        WHERE user_id = ?
        '
    );

    $stmt->execute([$userId]);

    $orderCount =
        (int) $stmt->fetchColumn();


    /*
     * Total cart quantity.
     */
    $cartItemCount =
        cartCount($pdo);

} catch (PDOException $e) {

    error_log(
        'Green Harvest account statistics error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| Customer First Name
|--------------------------------------------------------------------------
*/

$nameParts = preg_split(
    '/\s+/',
    trim((string) $user['full_name'])
);

$firstName =
    $nameParts[0] ?? 'Customer';


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'My Account';

require_once __DIR__ . '/includes/header.php';

?>


<style>

.account-layout {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 28px;
}

.account-sidebar {
    position: sticky;
    top: 100px;
}

.account-menu {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.account-menu-link {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 46px;
    padding: 10px 14px;
    border-radius: 11px;
    color: #526057;
    font-size: .88rem;
    font-weight: 700;
    transition: .2s ease;
}

.account-menu-link:hover {
    color: var(--gh-green-800);
    background: var(--gh-green-50);
}

.account-menu-link.active {
    color: #ffffff;
    background: var(--gh-green-700);
}

.account-menu-link.logout {
    color: #b91c1c;
}

.account-menu-link.logout:hover {
    color: #991b1b;
    background: #fff1f2;
}

.account-stat {
    height: 100%;
    padding: 24px;
}

.account-stat-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    border-radius: 12px;
    background: var(--gh-green-50);
    color: var(--gh-green-700);
    font-size: 1.1rem;
}

.account-stat-label {
    margin-bottom: 5px;
    color: var(--gh-muted);
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.account-stat-value {
    margin: 0;
    color: var(--gh-dark);
    font-family: 'Manrope', 'Inter', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
}

.profile-heading {
    margin-bottom: 6px;
}

.profile-description {
    margin-bottom: 28px;
    color: var(--gh-muted);
    font-size: .9rem;
}

.member-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 999px;
    background: var(--gh-green-50);
    color: var(--gh-green-800);
    font-size: .78rem;
    font-weight: 800;
    text-transform: capitalize;
}

@media (max-width: 991.98px) {

    .account-layout {
        grid-template-columns: 1fr;
    }

    .account-sidebar {
        position: static;
    }

    .account-menu {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }

}

@media (max-width: 575.98px) {

    .account-menu {
        grid-template-columns: 1fr;
    }

}

</style>


<!-- =========================================================
     Account Hero
========================================================= -->

<section class="page-hero">

    <div class="container">

        <p class="section-eyebrow">
            My Account
        </p>

        <h1>
            Welcome, <?= e($firstName) ?>.
        </h1>

    </div>

</section>



<!-- =========================================================
     Account Content
========================================================= -->

<section class="section-pad">

    <div class="container">


        <?php displayFlash(); ?>


        <div class="account-layout">


            <!-- =================================================
                 Account Sidebar
            ================================================== -->

            <aside>

                <div class="summary-card p-3 account-sidebar">

                    <div class="account-menu">


                        <a
                            href="<?= url('account.php') ?>"
                            class="account-menu-link active"
                        >

                            <i class="bi bi-grid"></i>

                            Dashboard

                        </a>


                        <a
                            href="<?= url('orders.php') ?>"
                            class="account-menu-link"
                        >

                            <i class="bi bi-bag-check"></i>

                            My Orders

                        </a>


                        <a
                            href="<?= url('logout.php') ?>"
                            class="account-menu-link logout"
                        >

                            <i class="bi bi-box-arrow-right"></i>

                            Logout

                        </a>


                    </div>

                </div>

            </aside>



            <!-- =================================================
                 Main Account Area
            ================================================== -->

            <div>


                <!-- =============================================
                     Statistics
                ============================================== -->

                <div class="row g-3 mb-4">


                    <!-- Orders -->

                    <div class="col-md-4">

                        <div class="summary-card account-stat">

                            <span class="account-stat-icon">

                                <i class="bi bi-bag-check"></i>

                            </span>

                            <p class="account-stat-label">
                                Total Orders
                            </p>

                            <h2 class="account-stat-value">
                                <?= $orderCount ?>
                            </h2>

                        </div>

                    </div>



                    <!-- Cart -->

                    <div class="col-md-4">

                        <div class="summary-card account-stat">

                            <span class="account-stat-icon">

                                <i class="bi bi-basket"></i>

                            </span>

                            <p class="account-stat-label">
                                Cart Items
                            </p>

                            <h2 class="account-stat-value">
                                <?= $cartItemCount ?>
                            </h2>

                        </div>

                    </div>



                    <!-- Membership -->

                    <div class="col-md-4">

                        <div class="summary-card account-stat">

                            <span class="account-stat-icon">

                                <i class="bi bi-person-check"></i>

                            </span>

                            <p class="account-stat-label">
                                Member Status
                            </p>

                            <div class="member-badge">

                                <i class="bi bi-check-circle-fill"></i>

                                <?= e(
                                    $user['role'] ?? 'customer'
                                ) ?>

                            </div>

                        </div>

                    </div>


                </div>



                <!-- =============================================
                     Profile Form
                ============================================== -->

                <div class="auth-card p-4 p-md-5">


                    <h2 class="profile-heading">
                        Profile Information
                    </h2>


                    <p class="profile-description">
                        Keep your contact and delivery information
                        up to date.
                    </p>


                    <form
                        method="post"
                        action="<?= url('account.php') ?>"
                        autocomplete="on"
                    >

                        <?= csrfField() ?>


                        <div class="row g-3">


                            <!-- Full Name -->

                            <div class="col-md-6">

                                <label
                                    for="full_name"
                                    class="form-label"
                                >
                                    Full Name
                                </label>

                                <input
                                    id="full_name"
                                    type="text"
                                    name="full_name"
                                    value="<?= e($user['full_name'] ?? '') ?>"
                                    class="form-control"
                                    autocomplete="name"
                                    required
                                >

                            </div>


                            <!-- Email -->

                            <div class="col-md-6">

                                <label
                                    for="email"
                                    class="form-label"
                                >
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="<?= e($user['email'] ?? '') ?>"
                                    class="form-control"
                                    autocomplete="email"
                                    required
                                >

                            </div>


                            <!-- Phone -->

                            <div class="col-md-6">

                                <label
                                    for="phone"
                                    class="form-label"
                                >
                                    Phone Number
                                </label>

                                <input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value="<?= e($user['phone'] ?? '') ?>"
                                    class="form-control"
                                    autocomplete="tel"
                                    placeholder="+233..."
                                >

                            </div>


                            <!-- Member Since -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Member Since
                                </label>

                                <input
                                    type="text"
                                    value="<?=
                                        !empty($user['created_at'])
                                            ? e(
                                                date(
                                                    'd F Y',
                                                    strtotime(
                                                        $user['created_at']
                                                    )
                                                )
                                            )
                                            : '—'
                                    ?>"
                                    class="form-control"
                                    disabled
                                >

                            </div>


                            <!-- Address -->

                            <div class="col-12">

                                <label
                                    for="address"
                                    class="form-label"
                                >
                                    Delivery Address
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter your delivery address"
                                    autocomplete="street-address"
                                ><?= e($user['address'] ?? '') ?></textarea>

                                <small
                                    class="d-block mt-2"
                                    style="color: var(--gh-muted);"
                                >
                                    This address can be used during checkout.
                                </small>

                            </div>


                        </div>


                        <!-- Update Button -->

                        <div
                            class="
                                d-flex
                                flex-column
                                flex-sm-row
                                gap-2
                                mt-4
                            "
                        >

                            <button
                                type="submit"
                                class="btn btn-green btn-lg"
                            >

                                <i class="bi bi-check2-circle me-1"></i>

                                Save Changes

                            </button>


                            <a
                                href="<?= url('shop.php') ?>"
                                class="btn btn-outline-green btn-lg"
                            >

                                <i class="bi bi-basket me-1"></i>

                                Continue Shopping

                            </a>

                        </div>


                    </form>

                </div>


            </div>

        </div>

    </div>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>