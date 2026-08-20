<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - REMOVE FROM CART
 * =========================================================
 *
 * Supports normal POST and AJAX requests.
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


function ghRemoveCartWantsJson(): bool
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


function ghRemoveCartJson(
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


function ghRemoveCartFail(
    string $type,
    string $message,
    int $status = 400
): never {

    if (ghRemoveCartWantsJson()) {

        ghRemoveCartJson(
            [
                'success' => false,
                'type' => $type,
                'message' => $message,
            ],
            $status
        );
    }

    setFlash(
        $type,
        $message
    );

    redirectTo(
        'cart.php'
    );
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    ghRemoveCartFail(
        'warning',
        'Invalid cart removal request.',
        405
    );
}


if (
    !verifyCsrf(
        $_POST['csrf_token'] ?? null
    )
) {

    ghRemoveCartFail(
        'error',
        'Your cart request expired. Please refresh the page and try again.',
        419
    );
}


if (!isLoggedIn()) {

    if (ghRemoveCartWantsJson()) {

        ghRemoveCartJson(
            [
                'success' =>
                    false,

                'requires_login' =>
                    true,

                'message' =>
                    'Please sign in to manage your basket.',

                'login_url' =>
                    url(
                        'login.php?redirect=' .
                        urlencode('cart.php')
                    ),
            ],
            401
        );
    }

    setFlash(
        'warning',
        'Please sign in to manage your basket.'
    );

    redirectTo(
        'login.php?redirect=' .
        urlencode('cart.php')
    );
}


$userId = getUserId();

if ($userId === null) {

    ghRemoveCartFail(
        'error',
        'Your session has expired. Please sign in again.',
        401
    );
}


$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);


if (
    $productId === false
    || $productId === null
    || $productId <= 0
) {

    ghRemoveCartFail(
        'error',
        'Invalid product selected.'
    );
}


try {

    /*
     * Fetch name before deleting so the response
     * can remain user friendly.
     */
    $itemStmt =
        $pdo->prepare(
            '
            SELECT
                p.name

            FROM carts c

            INNER JOIN products p
                ON p.id = c.product_id

            WHERE c.user_id = ?
              AND c.product_id = ?

            LIMIT 1
            '
        );


    $itemStmt->execute([
        $userId,
        (int) $productId,
    ]);


    $item =
        $itemStmt->fetch(
            PDO::FETCH_ASSOC
        );


    $deleteStmt =
        $pdo->prepare(
            '
            DELETE FROM carts

            WHERE user_id = ?
              AND product_id = ?
            '
        );


    $deleteStmt->execute([
        $userId,
        (int) $productId,
    ]);


    if ($deleteStmt->rowCount() < 1) {

        ghRemoveCartFail(
            'warning',
            'This product was not found in your basket.',
            404
        );
    }


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


    $message =
        ($item['name'] ?? 'Product') .
        ' removed from your basket.';


    if (ghRemoveCartWantsJson()) {

        ghRemoveCartJson(
            [
                'success' =>
                    true,

                'message' =>
                    $message,

                'product_id' =>
                    (int) $productId,

                'count' =>
                    $cartCount,
            ]
        );
    }


    setFlash(
        'success',
        $message
    );


} catch (Throwable $e) {

    error_log(
        'Green Harvest remove-from-cart error: ' .
        $e->getMessage()
    );


    ghRemoveCartFail(
        'error',
        'We could not remove this product from your basket. Please try again.',
        500
    );
}


redirectTo(
    'cart.php'
);
