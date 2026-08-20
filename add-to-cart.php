<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADD TO CART
 * =========================================================
 *
 * Supports:
 * - Normal POST form submissions
 * - AJAX cart submissions from index/shop/product pages
 * - CSRF protection
 * - Authentication
 * - Stock validation
 * - Database persistence
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function ghCartWantsJson(): bool
{
    $accept = strtolower(
        (string) ($_SERVER['HTTP_ACCEPT'] ?? '')
    );

    $requestedWith = strtolower(
        (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')
    );

    return
        ($_POST['ajax'] ?? '') === '1'
        || str_contains($accept, 'application/json')
        || $requestedWith === 'xmlhttprequest';
}


function ghCartJson(
    array $payload,
    int $status = 200
): never {

    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}


function ghCartRedirectOrJson(
    string $type,
    string $message,
    string $redirect,
    int $status = 400,
    array $extra = []
): never {

    if (ghCartWantsJson()) {

        ghCartJson(
            array_merge(
                [
                    'success' => false,
                    'type' => $type,
                    'message' => $message,
                ],
                $extra
            ),
            $status
        );
    }

    setFlash(
        $type,
        $message
    );

    redirectTo(
        $redirect
    );
}


/*
|--------------------------------------------------------------------------
| Redirect Destination
|--------------------------------------------------------------------------
*/

$redirect = safeRedirectPath(
    $_POST['redirect'] ?? 'shop.php',
    'shop.php'
);


/*
|--------------------------------------------------------------------------
| POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    ghCartRedirectOrJson(
        'warning',
        'Please select a product before adding it to your cart.',
        'shop.php',
        405
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !verifyCsrf(
        $_POST['csrf_token'] ?? null
    )
) {

    ghCartRedirectOrJson(
        'error',
        'Your cart request expired. Please refresh the page and try again.',
        $redirect,
        419
    );
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    $loginUrl =
        url(
            'login.php?redirect=' .
            urlencode($redirect)
        );

    if (ghCartWantsJson()) {

        ghCartJson(
            [
                'success' => false,
                'requires_login' => true,
                'message' =>
                    'Please sign in before adding products to your basket.',
                'login_url' =>
                    $loginUrl,
            ],
            401
        );
    }

    setFlash(
        'warning',
        'Please sign in before adding products to your basket.'
    );

    redirectTo(
        'login.php?redirect=' .
        urlencode($redirect)
    );
}


$userId = getUserId();

if ($userId === null) {

    ghCartRedirectOrJson(
        'error',
        'Your session has expired. Please sign in again.',
        'login.php?redirect=' .
        urlencode($redirect),
        401
    );
}


/*
|--------------------------------------------------------------------------
| Request Data
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    'quantity',
    FILTER_VALIDATE_INT
);


if (
    $quantity === false
    || $quantity === null
) {

    $quantity = 1;
}


if (
    $productId === false
    || $productId === null
    || $productId <= 0
) {

    ghCartRedirectOrJson(
        'error',
        'Invalid product selected.',
        $redirect
    );
}


if ($quantity <= 0) {

    ghCartRedirectOrJson(
        'error',
        'Quantity must be at least 1.',
        $redirect
    );
}


if ($quantity > 999) {

    ghCartRedirectOrJson(
        'warning',
        'You cannot add more than 999 units at once.',
        $redirect
    );
}


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
     * Lock product while checking stock.
     */
    $productStmt =
        $pdo->prepare(
            '
            SELECT
                id,
                name,
                stock_quantity,
                status

            FROM products

            WHERE id = ?

            LIMIT 1

            FOR UPDATE
            '
        );


    $productStmt->execute([
        (int) $productId,
    ]);


    $product =
        $productStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$product) {

        throw new DomainException(
            'The selected product could not be found.'
        );
    }


    if (
        (string) $product['status']
        !== 'active'
    ) {

        throw new DomainException(
            'This product is currently unavailable.'
        );
    }


    $stock =
        max(
            0,
            (int) $product['stock_quantity']
        );


    if ($stock <= 0) {

        throw new DomainException(
            'This product is currently out of stock.'
        );
    }


    /*
     * Check existing cart quantity.
     */
    $cartStmt =
        $pdo->prepare(
            '
            SELECT
                id,
                quantity

            FROM carts

            WHERE user_id = ?
              AND product_id = ?

            LIMIT 1

            FOR UPDATE
            '
        );


    $cartStmt->execute([
        $userId,
        (int) $productId,
    ]);


    $existing =
        $cartStmt->fetch(
            PDO::FETCH_ASSOC
        );


    $existingQuantity =
        $existing
            ? (int) $existing['quantity']
            : 0;


    $newQuantity =
        $existingQuantity
        +
        (int) $quantity;


    if ($newQuantity > $stock) {

        throw new DomainException(
            'Only ' .
            $stock .
            ' unit' .
            ($stock === 1 ? '' : 's') .
            ' of ' .
            $product['name'] .
            ' are currently available.'
        );
    }


    if ($existing) {

        $updateStmt =
            $pdo->prepare(
                '
                UPDATE carts

                SET
                    quantity = ?,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = ?
                  AND user_id = ?
                '
            );


        $updateStmt->execute([
            $newQuantity,
            (int) $existing['id'],
            $userId,
        ]);

    } else {

        $insertStmt =
            $pdo->prepare(
                '
                INSERT INTO carts
                (
                    user_id,
                    product_id,
                    quantity
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
            $userId,
            (int) $productId,
            (int) $quantity,
        ]);
    }


    /*
     * Fresh cart count.
     */
    $countStmt =
        $pdo->prepare(
            '
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )

            FROM carts

            WHERE user_id = ?
            '
        );


    $countStmt->execute([
        $userId,
    ]);


    $cartCount =
        (int) $countStmt->fetchColumn();


    $pdo->commit();


    $message =
        $product['name'] .
        ' added to your basket.';


    if (ghCartWantsJson()) {

        ghCartJson(
            [
                'success' =>
                    true,

                'message' =>
                    $message,

                'product_id' =>
                    (int) $productId,

                'quantity' =>
                    $newQuantity,

                'count' =>
                    $cartCount,
            ]
        );
    }


    setFlash(
        'success',
        $message
    );


} catch (DomainException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ghCartRedirectOrJson(
        'warning',
        $e->getMessage(),
        $redirect,
        422
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    error_log(
        'Green Harvest add-to-cart error: ' .
        $e->getMessage()
    );


    ghCartRedirectOrJson(
        'error',
        'We could not add this product to your basket. Please try again.',
        $redirect,
        500
    );
}


/*
|--------------------------------------------------------------------------
| Normal Form Fallback
|--------------------------------------------------------------------------
*/

redirectTo(
    $redirect
);
