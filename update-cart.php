<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - UPDATE CART
 * =========================================================
 *
 * Supports both normal form submissions and AJAX requests.
 * Quantity 0 removes the item.
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


function ghUpdateCartWantsJson(): bool
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


function ghUpdateCartJson(
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


function ghUpdateCartFail(
    string $type,
    string $message,
    int $status = 400
): never {

    if (ghUpdateCartWantsJson()) {

        ghUpdateCartJson(
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


/*
|--------------------------------------------------------------------------
| POST / CSRF / Authentication
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    ghUpdateCartFail(
        'warning',
        'Invalid cart update request.',
        405
    );
}


if (
    !verifyCsrf(
        $_POST['csrf_token'] ?? null
    )
) {

    ghUpdateCartFail(
        'error',
        'Your cart request expired. Please refresh the page and try again.',
        419
    );
}


if (!isLoggedIn()) {

    if (ghUpdateCartWantsJson()) {

        ghUpdateCartJson(
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

    ghUpdateCartFail(
        'error',
        'Your session has expired. Please sign in again.',
        401
    );
}


/*
|--------------------------------------------------------------------------
| Inputs
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
    $productId === false
    || $productId === null
    || $productId <= 0
) {

    ghUpdateCartFail(
        'error',
        'Invalid product selected.'
    );
}


if (
    $quantity === false
    || $quantity === null
) {

    ghUpdateCartFail(
        'error',
        'Please enter a valid quantity.'
    );
}


if ($quantity > 999) {

    ghUpdateCartFail(
        'warning',
        'Quantity cannot exceed 999 units.',
        422
    );
}


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    $stmt =
        $pdo->prepare(
            '
            SELECT
                c.id AS cart_id,
                c.quantity AS cart_quantity,

                p.name,
                p.stock_quantity,
                p.status

            FROM carts c

            INNER JOIN products p
                ON p.id = c.product_id

            WHERE c.user_id = ?
              AND c.product_id = ?

            LIMIT 1

            FOR UPDATE
            '
        );


    $stmt->execute([
        $userId,
        (int) $productId,
    ]);


    $item =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$item) {

        throw new DomainException(
            'This product is not in your basket.'
        );
    }


    /*
     * Quantity <= 0 means remove.
     */
    if ($quantity <= 0) {

        $deleteStmt =
            $pdo->prepare(
                '
                DELETE FROM carts

                WHERE id = ?
                  AND user_id = ?
                '
            );


        $deleteStmt->execute([
            (int) $item['cart_id'],
            $userId,
        ]);


        $message =
            $item['name'] .
            ' removed from your basket.';

    } else {

        if (
            (string) $item['status']
            !== 'active'
        ) {

            throw new DomainException(
                $item['name'] .
                ' is currently unavailable.'
            );
        }


        $stock =
            max(
                0,
                (int) $item['stock_quantity']
            );


        if ($stock <= 0) {

            throw new DomainException(
                $item['name'] .
                ' is currently out of stock.'
            );
        }


        if ((int) $quantity > $stock) {

            throw new DomainException(
                'Only ' .
                $stock .
                ' unit' .
                ($stock === 1 ? '' : 's') .
                ' of ' .
                $item['name'] .
                ' are currently available.'
            );
        }


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
            (int) $quantity,
            (int) $item['cart_id'],
            $userId,
        ]);


        $message =
            'Basket quantity updated.';
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


    $pdo->commit();


    if (ghUpdateCartWantsJson()) {

        ghUpdateCartJson(
            [
                'success' =>
                    true,

                'message' =>
                    $message,

                'product_id' =>
                    (int) $productId,

                'quantity' =>
                    max(
                        0,
                        (int) $quantity
                    ),

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


    ghUpdateCartFail(
        'warning',
        $e->getMessage(),
        422
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    error_log(
        'Green Harvest update-cart error: ' .
        $e->getMessage()
    );


    ghUpdateCartFail(
        'error',
        'We could not update your basket. Please try again.',
        500
    );
}


redirectTo(
    'cart.php'
);
