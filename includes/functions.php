<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - GENERAL HELPER FUNCTIONS
 * =========================================================
 *
 * Responsibilities:
 * - URL helpers
 * - Output escaping
 * - Flash messages
 * - CSRF protection
 * - Formatting
 * - Product/category helpers
 * - Image URL helpers
 * - Cart helpers
 * - Order number generation
 *
 * Authentication functions DO NOT belong here.
 * They will live in includes/auth.php.
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| URL Helpers
|--------------------------------------------------------------------------
*/

/**
 * Build a complete application URL.
 *
 * Example:
 * url('login.php')
 *
 * Result:
 * http://localhost/Green_harvest/login.php
 */
function url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}


/**
 * Redirect to another page.
 *
 * If a relative application path is supplied, it will automatically
 * be converted into a Green Harvest URL.
 */
function redirectTo(string $path): never
{
    if (!preg_match('/^https?:\/\//i', $path)) {
        $path = url($path);
    }

    header('Location: ' . $path);
    exit;
}


/**
 * Validate an internal redirect path.
 *
 * Prevents redirects to external websites.
 */
function safeRedirectPath(
    ?string $path,
    string $default = 'index.php'
): string {
    $path = trim((string) $path);

    if ($path === '') {
        return $default;
    }

    // Prevent external/protocol-relative redirects.
    if (
        preg_match('/^https?:\/\//i', $path) ||
        str_starts_with($path, '//') ||
        str_contains($path, '\\') ||
        str_contains($path, "\r") ||
        str_contains($path, "\n")
    ) {
        return $default;
    }

    return ltrim($path, '/');
}


/*
|--------------------------------------------------------------------------
| Safe Output
|--------------------------------------------------------------------------
*/

/**
 * Escape text before displaying it in HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


/**
 * Legacy-compatible alias.
 *
 * Some older Green Harvest pages currently use escapeOutput().
 * We will eventually standardize them on e().
 */
function escapeOutput(mixed $value): string
{
    return e($value);
}


/*
|--------------------------------------------------------------------------
| Input Helpers
|--------------------------------------------------------------------------
*/

/**
 * Basic string cleanup.
 *
 * IMPORTANT:
 * Database security is provided by prepared statements.
 * HTML escaping should happen when OUTPUTTING data using e().
 */
function sanitizeInput(mixed $input): string
{
    return trim(strip_tags((string) $input));
}


/**
 * Validate an email address.
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/*
|--------------------------------------------------------------------------
| Money / Formatting
|--------------------------------------------------------------------------
*/

/**
 * Format a numeric price.
 */
function formatPrice(float|int|string $amount): string
{
    return number_format((float) $amount, 2, '.', ',');
}


/**
 * Format Ghanaian currency.
 *
 * Example:
 * money(25)
 *
 * GH₵ 25.00
 */
function money(float|int|string $amount): string
{
    return 'GH₵ ' . formatPrice($amount);
}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

/**
 * Store a message that should be displayed on the next page load.
 */
function setFlash(string $type, string $message): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}


/**
 * Retrieve and remove all flash messages.
 */
function getFlashMessages(): array
{
    if (
        !isset($_SESSION['flash']) ||
        !is_array($_SESSION['flash'])
    ) {
        return [];
    }

    $messages = $_SESSION['flash'];

    unset($_SESSION['flash']);

    return $messages;
}


/**
 * Display flash messages.
 *
 * Uses simple inline styling so messages work on both
 * Bootstrap public pages and Tailwind admin pages.
 */
function displayFlash(): void
{
    $messages = getFlashMessages();

    if (!$messages) {
        return;
    }

    foreach ($messages as $flash) {

        $type = $flash['type'] ?? 'info';

        $styles = match ($type) {
            'success' =>
                'background:#ecfdf3;color:#166534;border:1px solid #bbf7d0;',

            'error', 'danger' =>
                'background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;',

            'warning' =>
                'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',

            default =>
                'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
        };

        echo '<div style="' .
            $styles .
            'padding:12px 16px;' .
            'border-radius:10px;' .
            'margin-bottom:16px;' .
            'font-size:14px;' .
            'font-weight:600;">';

        echo e($flash['message'] ?? '');

        echo '</div>';
    }
}


/**
 * Temporary compatibility helper for older pages.
 */
function setFlashMessage(
    string $message,
    string $type = 'info'
): void {
    setFlash($type, $message);
}


/**
 * Temporary compatibility helper for older pages.
 *
 * Returns one message at a time.
 */
function getFlashMessage(): ?array
{
    if (
        !isset($_SESSION['flash']) ||
        !is_array($_SESSION['flash']) ||
        empty($_SESSION['flash'])
    ) {
        return null;
    }

    $flash = array_shift($_SESSION['flash']);

    if (empty($_SESSION['flash'])) {
        unset($_SESSION['flash']);
    }

    return $flash;
}


/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

/**
 * Return or create the current CSRF token.
 */
function csrfToken(): string
{
    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/**
 * Generate a hidden CSRF input field.
 */
function csrfField(): string
{
    return sprintf(
        '<input type="hidden" name="csrf_token" value="%s">',
        e(csrfToken())
    );
}


/**
 * Validate a submitted CSRF token.
 */
function verifyCsrf(?string $token): bool
{
    if (
        empty($_SESSION['csrf_token']) ||
        !$token
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| Category Helpers
|--------------------------------------------------------------------------
*/

/**
 * Get all product categories.
 *
 * Uses the CURRENT Green Harvest database columns:
 * categories.id
 * categories.name
 * categories.description
 * categories.image
 */
function getCategories(PDO $pdo): array
{
    $stmt = $pdo->query(
        '
        SELECT
            id,
            name,
            description,
            image
        FROM categories
        ORDER BY name ASC
        '
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Image Helpers
|--------------------------------------------------------------------------
*/

/**
 * Generate a valid image URL.
 *
 * Database image fields should normally contain only the filename,
 * for example:
 *
 * tomatoes.webp
 *
 * which becomes:
 *
 * /uploads/products/tomatoes.webp
 */
function imageUrl(
    ?string $image,
    string $folder = 'products'
): string {
    $image = trim((string) $image);

    $placeholder =
        'https://images.pexels.com/photos/4846535/' .
        'pexels-photo-4846535.jpeg?' .
        'auto=compress&cs=tinysrgb&w=800';

    if ($image === '') {
        return $placeholder;
    }

    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }

    if (
        str_starts_with($image, 'uploads/') ||
        str_starts_with($image, 'assets/')
    ) {
        return url($image);
    }

    if (
        str_starts_with($image, 'products/') ||
        str_starts_with($image, 'categories/')
    ) {
        require_once __DIR__ . '/s3.php';

        $s3Url = s3ImageUrl($image, 60);

        return $s3Url !== ''
            ? $s3Url
            : $placeholder;
    }

    return url(
        'uploads/' .
        trim($folder, '/') .
        '/' .
        ltrim($image, '/')
    );
}


/**
 * Product image shortcut.
 */
function productImageUrl(?string $image): string
{
    return imageUrl($image, 'products');
}


/**
 * Category image shortcut.
 */
function categoryImageUrl(?string $image): string
{
    return imageUrl($image, 'categories');
}


/*
|--------------------------------------------------------------------------
| Stock Helpers
|--------------------------------------------------------------------------
*/

function stockLabel(int $stock): string
{
    if ($stock <= 0) {
        return 'Out of Stock';
    }

    if ($stock <= 10) {
        return 'Low Stock';
    }

    return 'In Stock';
}


/*
|--------------------------------------------------------------------------
| Cart Helpers
|--------------------------------------------------------------------------
*/

/**
 * Get the currently logged-in user's ID directly from the session.
 *
 * Authentication itself will be handled in auth.php.
 */
function cartUserId(): int
{
    return isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;
}


/**
 * Add a product to the customer's cart.
 */
function addCartProduct(
    PDO $pdo,
    int $productId,
    int $quantity = 1
): bool {
    $userId = cartUserId();

    if ($userId <= 0) {
        setFlash(
            'warning',
            'Please log in before adding products to your cart.'
        );

        return false;
    }

    if ($productId <= 0 || $quantity <= 0) {
        setFlash('error', 'Invalid product or quantity.');

        return false;
    }

    /*
     * Confirm product exists and is available.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            name,
            stock_quantity,
            status
        FROM products
        WHERE id = ?
        LIMIT 1
        '
    );

    $stmt->execute([$productId]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['status'] !== 'active') {
        setFlash(
            'error',
            'This product is currently unavailable.'
        );

        return false;
    }

    $availableStock = (int) $product['stock_quantity'];

    if ($availableStock <= 0) {
        setFlash(
            'warning',
            $product['name'] . ' is currently out of stock.'
        );

        return false;
    }

    /*
     * Check for an existing cart row.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            quantity
        FROM carts
        WHERE user_id = ?
          AND product_id = ?
        LIMIT 1
        '
    );

    $stmt->execute([
        $userId,
        $productId,
    ]);

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $currentQuantity = $existing
        ? (int) $existing['quantity']
        : 0;

    $newQuantity = $currentQuantity + $quantity;

    /*
     * Never allow cart quantity beyond available stock.
     */
    if ($newQuantity > $availableStock) {
        setFlash(
            'warning',
            'Only ' .
            $availableStock .
            ' unit(s) of ' .
            $product['name'] .
            ' are available.'
        );

        return false;
    }

    if ($existing) {

        $stmt = $pdo->prepare(
            '
            UPDATE carts
            SET quantity = ?
            WHERE id = ?
              AND user_id = ?
            '
        );

        $stmt->execute([
            $newQuantity,
            $existing['id'],
            $userId,
        ]);

    } else {

        $stmt = $pdo->prepare(
            '
            INSERT INTO carts (
                user_id,
                product_id,
                quantity
            )
            VALUES (?, ?, ?)
            '
        );

        $stmt->execute([
            $userId,
            $productId,
            $quantity,
        ]);
    }

    setFlash(
        'success',
        $product['name'] . ' added to your cart.'
    );

    return true;
}


/**
 * Remove a product from the customer's cart.
 */
function removeCartProduct(
    PDO $pdo,
    int $productId
): bool {
    $userId = cartUserId();

    if ($userId <= 0 || $productId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        '
        DELETE FROM carts
        WHERE user_id = ?
          AND product_id = ?
        '
    );

    $stmt->execute([
        $userId,
        $productId,
    ]);

    if ($stmt->rowCount() > 0) {
        setFlash(
            'success',
            'Product removed from your cart.'
        );

        return true;
    }

    return false;
}


/**
 * Update a product quantity in the customer's cart.
 */
function updateCartQuantity(
    PDO $pdo,
    int $productId,
    int $quantity
): bool {
    $userId = cartUserId();

    if ($userId <= 0 || $productId <= 0) {
        return false;
    }

    if ($quantity <= 0) {
        return removeCartProduct(
            $pdo,
            $productId
        );
    }

    /*
     * Check current stock before updating.
     */
    $stmt = $pdo->prepare(
        '
        SELECT
            name,
            stock_quantity,
            status
        FROM products
        WHERE id = ?
        LIMIT 1
        '
    );

    $stmt->execute([$productId]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['status'] !== 'active') {
        setFlash(
            'error',
            'This product is no longer available.'
        );

        return false;
    }

    if ($quantity > (int) $product['stock_quantity']) {
        setFlash(
            'warning',
            'Only ' .
            (int) $product['stock_quantity'] .
            ' unit(s) of ' .
            $product['name'] .
            ' are available.'
        );

        return false;
    }

    $stmt = $pdo->prepare(
        '
        UPDATE carts
        SET quantity = ?
        WHERE user_id = ?
          AND product_id = ?
        '
    );

    $stmt->execute([
        $quantity,
        $userId,
        $productId,
    ]);

    return true;
}


/**
 * Get all products currently in the customer's cart.
 */
function getCartItems(PDO $pdo): array
{
    $userId = cartUserId();

    if ($userId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        '
        SELECT
            c.id AS cart_id,
            c.product_id,
            c.quantity,

            p.name,
            p.price,
            p.unit,
            p.image,
            p.stock_quantity,
            p.status,

            cat.name AS category_name,

            (c.quantity * p.price) AS subtotal

        FROM carts c

        INNER JOIN products p
            ON p.id = c.product_id

        LEFT JOIN categories cat
            ON cat.id = p.category_id

        WHERE c.user_id = ?

        ORDER BY c.created_at DESC
        '
    );

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Count the TOTAL quantity of products in the cart.
 *
 * Example:
 *
 * Tomatoes x2
 * Apples   x3
 *
 * Cart count = 5
 */
function cartCount(PDO $pdo): int
{
    $userId = cartUserId();

    if ($userId <= 0) {
        return 0;
    }

    $stmt = $pdo->prepare(
        '
        SELECT COALESCE(SUM(quantity), 0)
        FROM carts
        WHERE user_id = ?
        '
    );

    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}


/**
 * Calculate shopping cart totals.
 *
 * Current standard delivery fee:
 * GH₵10.00
 *
 * Delivery is only charged when the cart contains products.
 */
function cartTotals(
    array $items,
    float $deliveryFee = 10.00
): array {
    $subtotal = 0.0;

    foreach ($items as $item) {
        $subtotal += (float) (
            $item['subtotal'] ??
            (
                ((float) ($item['price'] ?? 0)) *
                ((int) ($item['quantity'] ?? 0))
            )
        );
    }

    $delivery = $subtotal > 0
        ? $deliveryFee
        : 0.00;

    return [
        'subtotal' => $subtotal,
        'delivery' => $delivery,
        'total'    => $subtotal + $delivery,
    ];
}


/**
 * Empty a customer's cart.
 *
 * This will be used after successful checkout.
 */
function clearCart(
    PDO $pdo,
    int $userId
): void {
    if ($userId <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        'DELETE FROM carts WHERE user_id = ?'
    );

    $stmt->execute([$userId]);
}


/*
|--------------------------------------------------------------------------
| Order Helpers
|--------------------------------------------------------------------------
*/

/**
 * Generate a unique customer-facing order number.
 *
 * Example:
 * GH-A7C412EF
 */
function generateOrderNumber(PDO $pdo): string
{
    $attempts = 0;

    while ($attempts < 10) {
        $number =
            'GH-' .
            strtoupper(
                bin2hex(random_bytes(4))
            );

        $stmt = $pdo->prepare(
            '
            SELECT id
            FROM orders
            WHERE order_number = ?
            LIMIT 1
            '
        );

        $stmt->execute([$number]);

        if (!$stmt->fetchColumn()) {
            return $number;
        }

        $attempts++;
    }

    throw new RuntimeException(
        'Unable to generate a unique order number after 10 attempts.'
    );
}