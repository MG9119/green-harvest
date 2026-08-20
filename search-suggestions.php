<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - LIVE SEARCH SUGGESTIONS
 * =========================================================
 *
 * Purpose:
 * - Receive a search term through GET ?q=
 * - Search products by:
 *      - product name
 *      - product description
 *      - category name
 * - Support partial words
 * - Return JSON for the navbar live-search dropdown
 *
 * Example:
 * search-suggestions.php?q=lem
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| JSON Response
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);


/*
|--------------------------------------------------------------------------
| Default Response
|--------------------------------------------------------------------------
*/

$response = [

    'success' => true,

    'query' => '',

    'products' => [],

    'categories' => [],

    'total' => 0,

];


/*
|--------------------------------------------------------------------------
| Search Query
|--------------------------------------------------------------------------
*/

$query = trim(
    (string) (
        $_GET['q']
        ?? ''
    )
);


$response['query'] = $query;


/*
|--------------------------------------------------------------------------
| Minimum Search Length
|--------------------------------------------------------------------------
|
| One character is technically allowed, but two characters
| gives much more useful suggestions.
|
*/

if (
    mb_strlen(
        $query
    ) < 2
) {

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Limit Query Length
|--------------------------------------------------------------------------
*/

$query = mb_substr(
    $query,
    0,
    100
);


/*
|--------------------------------------------------------------------------
| Simple Search Normalisation
|--------------------------------------------------------------------------
|
| These aliases make common customer wording easier to search.
|
| Example:
| veggies -> vegetables
| drinks  -> drink
|
*/

$searchAliases = [

    'veggie' =>
        'vegetable',

    'veggies' =>
        'vegetable',

    'vegetables' =>
        'vegetable',

    'fruits' =>
        'fruit',

    'drinks' =>
        'drink',

    'beverages' =>
        'beverage',

    'juices' =>
        'juice',

    'herbs' =>
        'herb',

    'grains' =>
        'grain',

];


$normalisedQuery =
    mb_strtolower(
        $query
    );


if (
    isset(
        $searchAliases[
            $normalisedQuery
        ]
    )
) {

    $normalisedQuery =
        $searchAliases[
            $normalisedQuery
        ];

}


/*
|--------------------------------------------------------------------------
| Search Terms
|--------------------------------------------------------------------------
*/

$contains =
    '%' .
    $normalisedQuery .
    '%';


$startsWith =
    $normalisedQuery .
    '%';


try {

    /*
    |--------------------------------------------------------------------------
    | Product Suggestions
    |--------------------------------------------------------------------------
    |
    | Ranking:
    |
    | 1. Product name starts with query
    | 2. Product name contains query
    | 3. Category contains query
    | 4. Description contains query
    |
    */

    $productSql = "
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.unit,
            p.image,
            p.stock_quantity,
            p.is_organic,
            c.id AS category_id,
            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        WHERE
            p.status = 'active'

            AND
            (
                LOWER(p.name) LIKE ?
                OR LOWER(p.description) LIKE ?
                OR LOWER(c.name) LIKE ?
            )

        ORDER BY

            CASE

                WHEN LOWER(p.name) LIKE ?
                    THEN 1

                WHEN LOWER(p.name) LIKE ?
                    THEN 2

                WHEN LOWER(c.name) LIKE ?
                    THEN 3

                ELSE 4

            END,

            p.is_featured DESC,

            p.name ASC

        LIMIT 6
    ";


    $productStmt =
        $pdo->prepare(
            $productSql
        );


    $productStmt->execute([

        $contains,
        $contains,
        $contains,

        $startsWith,
        $contains,
        $contains,

    ]);


    $productRows =
        $productStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | Format Product Suggestions
    |--------------------------------------------------------------------------
    */

    foreach (
        $productRows as
        $product
    ) {

        $response['products'][] = [

            'id' =>
                (int) $product['id'],

            'name' =>
                (string) $product['name'],

            'category' =>
                (string) (
                    $product['category_name']
                    ?: 'Fresh Produce'
                ),

            'category_id' =>
                (int) (
                    $product['category_id']
                    ?? 0
                ),

            'price' =>
                money(
                    $product['price']
                ),

            'unit' =>
                (string) (
                    $product['unit']
                    ?: 'item'
                ),

            'stock' =>
                (int) $product['stock_quantity'],

            'organic' =>
                (bool) $product['is_organic'],

            'image' =>
                productImageUrl(
                    $product['image']
                ),

            'url' =>
                url(
                    'product.php?id=' .
                    (int) $product['id']
                ),

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Category Suggestions
    |--------------------------------------------------------------------------
    */

    $categorySql = "
        SELECT
            id,
            name

        FROM categories

        WHERE
            LOWER(name) LIKE ?

        ORDER BY

            CASE
                WHEN LOWER(name) LIKE ?
                    THEN 1
                ELSE 2
            END,

            name ASC

        LIMIT 4
    ";


    $categoryStmt =
        $pdo->prepare(
            $categorySql
        );


    $categoryStmt->execute([

        $contains,
        $startsWith,

    ]);


    $categoryRows =
        $categoryStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | Format Category Suggestions
    |--------------------------------------------------------------------------
    */

    foreach (
        $categoryRows as
        $category
    ) {

        $response['categories'][] = [

            'id' =>
                (int) $category['id'],

            'name' =>
                (string) $category['name'],

            'url' =>
                url(
                    'category.php?id=' .
                    (int) $category['id']
                ),

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Total Suggestions
    |--------------------------------------------------------------------------
    */

    $response['total'] =
        count(
            $response['products']
        )
        +
        count(
            $response['categories']
        );


} catch (PDOException $e) {

    error_log(
        'Green Harvest live search error: ' .
        $e->getMessage()
    );


    http_response_code(
        500
    );


    $response = [

        'success' => false,

        'query' => $query,

        'products' => [],

        'categories' => [],

        'total' => 0,

        'message' =>
            'Search suggestions are temporarily unavailable.',

    ];

}


/*
|--------------------------------------------------------------------------
| Output JSON
|--------------------------------------------------------------------------
*/

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
);