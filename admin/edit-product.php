<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN EDIT PRODUCT
 * =========================================================
 *
 * Responsibilities:
 * - Protect the admin page
 * - Validate product ID
 * - Load existing product
 * - Load categories
 * - Validate submitted product data
 * - Maintain a unique product slug
 * - Safely replace/remove product images
 * - Update the product record
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Product ID
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (
    $productId === false ||
    $productId === null ||
    $productId <= 0
) {

    setFlash(
        'error',
        'Invalid product selected.'
    );

    redirectTo('admin/products.php');
}


/*
|--------------------------------------------------------------------------
| Load Product
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        '
        SELECT
            id,
            category_id,
            name,
            slug,
            description,
            price,
            unit,
            stock_quantity,
            image,
            is_organic,
            is_featured,
            status,
            created_at,
            updated_at

        FROM products

        WHERE id = ?

        LIMIT 1
        '
    );


    $stmt->execute([
        $productId,
    ]);


    $product =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        setFlash(
            'error',
            'The selected product could not be found.'
        );

        redirectTo('admin/products.php');
    }


} catch (PDOException $e) {

    error_log(
        'Green Harvest edit-product loading error: ' .
        $e->getMessage()
    );


    setFlash(
        'error',
        'The product could not be loaded.'
    );

    redirectTo('admin/products.php');
}


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryIds = [];

$categoriesLoadError = false;


try {

    $stmt = $pdo->query(
        '
        SELECT
            id,
            name

        FROM categories

        ORDER BY name ASC
        '
    );


    $categories =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($categories as $category) {

        $categoryIds[] =
            (int) $category['id'];
    }


} catch (PDOException $e) {

    error_log(
        'Green Harvest edit-product category error: ' .
        $e->getMessage()
    );


    $categoriesLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$name =
    (string) $product['name'];


$categoryId =
    (int) $product['category_id'];


$description =
    (string) (
        $product['description']
        ?? ''
    );


$price =
    (string) $product['price'];


$unit =
    (string) (
        $product['unit']
        ?: 'kg'
    );


$stockQuantity =
    (int) $product['stock_quantity'];


$status =
    strtolower(
        (string) $product['status']
    );


$isOrganic =
    (bool) $product['is_organic'];


$isFeatured =
    (bool) $product['is_featured'];


$currentImage =
    trim(
        (string) (
            $product['image']
            ?? ''
        )
    );


$formErrors = [];


/*
|--------------------------------------------------------------------------
| Unique Product Slug
|--------------------------------------------------------------------------
*/

$createProductSlug = static function (
    PDO $pdo,
    string $productName,
    int $excludeProductId
): string {

    $slug =
        strtolower(
            trim($productName)
        );


    /*
     * Improve slugs containing accented characters.
     */
    if (function_exists('iconv')) {

        $converted =
            @iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $slug
            );


        if (
            $converted !== false &&
            $converted !== ''
        ) {

            $slug =
                $converted;
        }
    }


    $slug =
        preg_replace(
            '/[^a-z0-9]+/i',
            '-',
            $slug
        );


    $slug =
        trim(
            (string) $slug,
            '-'
        );


    if ($slug === '') {

        $slug =
            'product';
    }


    /*
     * products.slug = VARCHAR(180)
     */
    $baseSlug =
        substr(
            $slug,
            0,
            165
        );


    $slug =
        $baseSlug;


    $counter = 2;


    while (true) {

        $stmt = $pdo->prepare(
            '
            SELECT COUNT(*)

            FROM products

            WHERE slug = ?
              AND id != ?
            '
        );


        $stmt->execute([
            $slug,
            $excludeProductId,
        ]);


        if (
            (int) $stmt->fetchColumn() === 0
        ) {

            return $slug;
        }


        $suffix =
            '-' . $counter;


        $slug =
            substr(
                $baseSlug,
                0,
                180 - strlen($suffix)
            ) .
            $suffix;


        $counter++;
    }
};


/*
|--------------------------------------------------------------------------
| Determine Whether Image Is Managed Locally
|--------------------------------------------------------------------------
|
| Only images that are simple filenames stored inside
| uploads/products should ever be physically deleted.
|
| This prevents accidentally treating:
|
| https://example.com/photo.jpg
| assets/images/photo.jpg
| ../photo.jpg
|
| as locally managed upload files.
|
*/

$isLocalProductImage = static function (
    ?string $image
): bool {

    $image =
        trim(
            (string) $image
        );


    if ($image === '') {

        return false;
    }


    /*
     * No URLs.
     */
    if (
        filter_var(
            $image,
            FILTER_VALIDATE_URL
        )
    ) {

        return false;
    }


    /*
     * Must be filename only.
     */
    if (
        basename($image) !== $image
    ) {

        return false;
    }


    /*
     * Prevent traversal.
     */
    if (
        str_contains(
            $image,
            '..'
        ) ||
        str_contains(
            $image,
            '/'
        ) ||
        str_contains(
            $image,
            '\\'
        )
    ) {

        return false;
    }


    $extension =
        strtolower(
            pathinfo(
                $image,
                PATHINFO_EXTENSION
            )
        );


    return in_array(
        $extension,
        [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ],
        true
    );
};


/*
|--------------------------------------------------------------------------
| Delete Managed Local Product Image
|--------------------------------------------------------------------------
*/

$deleteLocalProductImage =
    static function (
        ?string $image
    ) use (
        $isLocalProductImage
    ): void {

        if (
            !$isLocalProductImage(
                $image
            )
        ) {

            return;
        }


        $path =
            PRODUCT_UPLOAD_PATH .
            DIRECTORY_SEPARATOR .
            $image;


        if (
            is_file($path)
        ) {

            @unlink($path);
        }
    };


/*
|--------------------------------------------------------------------------
| Upload Replacement Image
|--------------------------------------------------------------------------
|
| Returns:
| - null if no replacement image was selected
| - generated filename when upload succeeds
|
*/

$uploadProductImage =
    static function (): ?string {

        if (
            !isset($_FILES['image']) ||
            !is_array($_FILES['image'])
        ) {

            return null;
        }


        $file =
            $_FILES['image'];


        $error =
            (int) (
                $file['error']
                ?? UPLOAD_ERR_NO_FILE
            );


        if (
            $error === UPLOAD_ERR_NO_FILE
        ) {

            return null;
        }


        if (
            $error !== UPLOAD_ERR_OK
        ) {

            throw new RuntimeException(
                'The replacement product image could not be uploaded.'
            );
        }


        $temporaryPath =
            (string) (
                $file['tmp_name']
                ?? ''
            );


        $fileSize =
            (int) (
                $file['size']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Maximum File Size: 5 MB
        |--------------------------------------------------------------------------
        */

        $maximumFileSize =
            5 * 1024 * 1024;


        if (
            $fileSize <= 0 ||
            $fileSize > $maximumFileSize
        ) {

            throw new RuntimeException(
                'Product images must be 5 MB or smaller.'
            );
        }


        if (
            $temporaryPath === '' ||
            !is_uploaded_file(
                $temporaryPath
            )
        ) {

            throw new RuntimeException(
                'The uploaded product image is invalid.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Confirm It Is Actually an Image
        |--------------------------------------------------------------------------
        */

        $imageInformation =
            @getimagesize(
                $temporaryPath
            );


        if (
            $imageInformation === false
        ) {

            throw new RuntimeException(
                'Please upload a valid image file.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Detect MIME Type
        |--------------------------------------------------------------------------
        */

        $mimeType = '';


        if (
            function_exists(
                'finfo_open'
            )
        ) {

            $fileInfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            if (
                $fileInfo !== false
            ) {

                $detectedMime =
                    finfo_file(
                        $fileInfo,
                        $temporaryPath
                    );


                if (
                    is_string(
                        $detectedMime
                    )
                ) {

                    $mimeType =
                        $detectedMime;
                }


                finfo_close(
                    $fileInfo
                );
            }
        }


        if (
            $mimeType === '' &&
            isset(
                $imageInformation['mime']
            )
        ) {

            $mimeType =
                (string)
                $imageInformation['mime'];
        }


        $allowedMimeTypes = [

            'image/jpeg' => 'jpg',

            'image/png' => 'png',

            'image/webp' => 'webp',

        ];


        if (
            !isset(
                $allowedMimeTypes[
                    $mimeType
                ]
            )
        ) {

            throw new RuntimeException(
                'Only JPG, PNG and WebP product images are allowed.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Ensure Upload Directory Exists
        |--------------------------------------------------------------------------
        */

        if (
            !is_dir(
                PRODUCT_UPLOAD_PATH
            )
        ) {

            if (
                !mkdir(
                    PRODUCT_UPLOAD_PATH,
                    0755,
                    true
                ) &&
                !is_dir(
                    PRODUCT_UPLOAD_PATH
                )
            ) {

                throw new RuntimeException(
                    'The product upload directory could not be created.'
                );
            }
        }


        if (
            !is_writable(
                PRODUCT_UPLOAD_PATH
            )
        ) {

            throw new RuntimeException(
                'The product upload directory is not writable.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Secure Filename
        |--------------------------------------------------------------------------
        */

        $extension =
            $allowedMimeTypes[
                $mimeType
            ];


        $newFileName =
            bin2hex(
                random_bytes(16)
            ) .
            '.' .
            $extension;


        $destination =
            PRODUCT_UPLOAD_PATH .
            DIRECTORY_SEPARATOR .
            $newFileName;


        if (
            !move_uploaded_file(
                $temporaryPath,
                $destination
            )
        ) {

            throw new RuntimeException(
                'The replacement product image could not be saved.'
            );
        }


        return $newFileName;
    };


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /*
    |--------------------------------------------------------------------------
    | Preserve Form Values
    |--------------------------------------------------------------------------
    */

    $name =
        trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );


    $categoryId =
        (int) (
            $_POST['category_id']
            ?? 0
        );


    $description =
        trim(
            (string) (
                $_POST['description']
                ?? ''
            )
        );


    $priceInput =
        trim(
            (string) (
                $_POST['price']
                ?? ''
            )
        );


    $price =
        $priceInput;


    $unit =
        trim(
            (string) (
                $_POST['unit']
                ?? ''
            )
        );


    $stockInput =
        trim(
            (string) (
                $_POST['stock_quantity']
                ?? ''
            )
        );


    $stockQuantity =
        is_numeric($stockInput)
            ? (int) $stockInput
            : 0;


    $status =
        strtolower(
            trim(
                (string) (
                    $_POST['status']
                    ?? ''
                )
            )
        );


    $isOrganic =
        isset(
            $_POST['is_organic']
        );


    $isFeatured =
        isset(
            $_POST['is_featured']
        );


    $removeImage =
        isset(
            $_POST['remove_image']
        );


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        $formErrors[] =
            'Invalid product request. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Product Name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $formErrors[] =
            'Product name is required.';

    } elseif (
        strlen($name) > 160
    ) {

        $formErrors[] =
            'Product name cannot exceed 160 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    if ($categoryId <= 0) {

        $formErrors[] =
            'Please select a product category.';

    } elseif (
        !in_array(
            $categoryId,
            $categoryIds,
            true
        )
    ) {

        $formErrors[] =
            'The selected product category is invalid.';
    }


    /*
    |--------------------------------------------------------------------------
    | Price
    |--------------------------------------------------------------------------
    */

    $validatedPrice =
        filter_var(
            $priceInput,
            FILTER_VALIDATE_FLOAT
        );


    if (
        $validatedPrice === false ||
        $validatedPrice < 0
    ) {

        $formErrors[] =
            'Please enter a valid product price.';

    } elseif (
        $validatedPrice >
        99999999.99
    ) {

        $formErrors[] =
            'The product price is too large.';
    }


    /*
    |--------------------------------------------------------------------------
    | Unit
    |--------------------------------------------------------------------------
    */

    if ($unit === '') {

        $formErrors[] =
            'Product unit is required.';

    } elseif (
        strlen($unit) > 30
    ) {

        $formErrors[] =
            'Product unit cannot exceed 30 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */

    $validatedStock =
        filter_var(
            $stockInput,
            FILTER_VALIDATE_INT
        );


    if (
        $validatedStock === false ||
        $validatedStock < 0
    ) {

        $formErrors[] =
            'Stock quantity must be zero or greater.';
    }


    /*
    |--------------------------------------------------------------------------
    | Product Status
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $status,
            [
                'active',
                'inactive',
            ],
            true
        )
    ) {

        $formErrors[] =
            'Please select a valid product status.';
    }


    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    if (!$formErrors) {

        $newUploadedImage = null;


        try {

            /*
             * Upload a replacement image if one
             * was submitted.
             */
            $newUploadedImage =
                $uploadProductImage();


            /*
            |--------------------------------------------------------------------------
            | Determine Image to Store
            |--------------------------------------------------------------------------
            |
            | New upload takes priority.
            |
            */

            if (
                $newUploadedImage !== null
            ) {

                $imageToStore =
                    $newUploadedImage;

            } elseif (
                $removeImage
            ) {

                $imageToStore =
                    null;

            } else {

                $imageToStore =
                    $currentImage !== ''
                        ? $currentImage
                        : null;
            }


            /*
             * Generate unique slug.
             */
            $slug =
                $createProductSlug(
                    $pdo,
                    $name,
                    $productId
                );


            /*
            |--------------------------------------------------------------------------
            | Update Database
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
                    '
                    UPDATE products

                    SET
                        category_id = ?,
                        name = ?,
                        slug = ?,
                        description = ?,
                        price = ?,
                        unit = ?,
                        stock_quantity = ?,
                        image = ?,
                        is_organic = ?,
                        is_featured = ?,
                        status = ?

                    WHERE id = ?
                    '
                );


            $stmt->execute([

                $categoryId,

                $name,

                $slug,

                $description !== ''
                    ? $description
                    : null,

                round(
                    (float)
                    $validatedPrice,
                    2
                ),

                $unit,

                (int)
                $validatedStock,

                $imageToStore,

                $isOrganic
                    ? 1
                    : 0,

                $isFeatured
                    ? 1
                    : 0,

                $status,

                $productId,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Remove Previous Local Image
            |--------------------------------------------------------------------------
            |
            | Only after the database update succeeds.
            |
            */

            $imageChanged =
                (
                    $newUploadedImage !== null
                    ||
                    $removeImage
                );


            if (
                $imageChanged &&
                $currentImage !== '' &&
                $currentImage !==
                    $imageToStore
            ) {

                $deleteLocalProductImage(
                    $currentImage
                );
            }


            setFlash(
                'success',
                'Product updated successfully.'
            );


            redirectTo(
                'admin/products.php'
            );


        } catch (
            RuntimeException $e
        ) {

            /*
             * Upload validation failure.
             */
            $formErrors[] =
                $e->getMessage();


        } catch (
            PDOException $e
        ) {

            error_log(
                'Green Harvest edit-product database error: ' .
                $e->getMessage()
            );


            /*
             * Database update failed after a new image
             * was uploaded. Remove the unused new file.
             */
            if (
                $newUploadedImage !== null
            ) {

                $deleteLocalProductImage(
                    $newUploadedImage
                );
            }


            $formErrors[] =
                'The product could not be updated. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Render Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Edit Product';


require_once __DIR__ .
    '/includes/admin-header.php';

?>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div
    class="
        mb-6
        flex
        flex-col
        sm:flex-row
        sm:items-center
        sm:justify-between
        gap-4
    "
>

    <div>

        <h2
            class="
                text-xl
                font-bold
                text-slate-900
            "
        >

            Edit Product

        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >

            Update product #<?= $productId ?>
            information, inventory and availability.

        </p>

    </div>


    <a
        href="<?= url('admin/products.php') ?>"
        class="
            inline-flex
            items-center
            gap-2
            text-sm
            font-semibold
            text-slate-600
            hover:text-brand-700
            transition-colors
        "
    >

        <i
            data-lucide="arrow-left"
            class="w-4 h-4"
        ></i>

        Back to Products

    </a>

</div>


<!-- =========================================================
     ERRORS
========================================================= -->

<?php if ($formErrors): ?>

    <div
        class="
            mb-6
            rounded-2xl
            border
            border-rose-200
            bg-rose-50
            p-4
            text-rose-700
        "
    >

        <div
            class="
                flex
                items-start
                gap-3
            "
        >

            <i
                data-lucide="circle-alert"
                class="
                    w-5
                    h-5
                    mt-0.5
                    flex-shrink-0
                "
            ></i>


            <div>

                <strong
                    class="
                        block
                        text-sm
                        mb-1
                    "
                >
                    Please correct the following:
                </strong>


                <ul
                    class="
                        text-xs
                        list-disc
                        pl-5
                        space-y-1
                    "
                >

                    <?php foreach (
                        $formErrors as $error
                    ): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        </div>

    </div>

<?php endif; ?>


<?php if ($categoriesLoadError): ?>

    <div
        class="
            mb-6
            rounded-xl
            border
            border-amber-200
            bg-amber-50
            px-4
            py-3
            text-sm
            text-amber-800
        "
    >

        Product categories could not be loaded.
        Please refresh the page before updating this product.

    </div>

<?php endif; ?>


<!-- =========================================================
     PRODUCT FORM
========================================================= -->

<div
    class="
        bg-white
        rounded-2xl
        border
        border-slate-200
        shadow-sm
        overflow-hidden
    "
>


    <form
        method="post"
        action="<?= url(
            'admin/edit-product.php?id=' .
            $productId
        ) ?>"
        enctype="multipart/form-data"
    >

        <?= csrfField() ?>


        <!-- =================================================
             PRODUCT INFORMATION
        ================================================== -->

        <section
            class="
                p-6
                lg:p-8
                border-b
                border-slate-100
            "
        >


            <div class="mb-6">

                <h3
                    class="
                        text-base
                        font-bold
                        text-slate-900
                    "
                >
                    Product Information
                </h3>


                <p
                    class="
                        text-xs
                        text-slate-500
                        mt-1
                    "
                >
                    Update the information customers
                    see in the Green Harvest store.
                </p>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-6
                "
            >


                <!-- Product Name -->

                <div>

                    <label
                        for="product-name"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >

                        Product Name

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <input
                        id="product-name"
                        type="text"
                        name="name"
                        value="<?= e($name) ?>"
                        maxlength="160"
                        required
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                </div>


                <!-- Category -->

                <div>

                    <label
                        for="product-category"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >

                        Category

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <select
                        id="product-category"
                        name="category_id"
                        required
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                        <option value="">
                            Select Category
                        </option>


                        <?php foreach (
                            $categories as $category
                        ): ?>

                            <?php

                            $optionCategoryId =
                                (int)
                                $category['id'];

                            ?>

                            <option
                                value="<?= $optionCategoryId ?>"
                                <?= $categoryId ===
                                    $optionCategoryId
                                        ? 'selected'
                                        : ''
                                ?>
                            >

                                <?= e(
                                    $category['name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Description -->

                <div class="md:col-span-2">

                    <label
                        for="product-description"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >
                        Description
                    </label>


                    <textarea
                        id="product-description"
                        name="description"
                        rows="5"
                        class="
                            w-full
                            px-4
                            py-3
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                            resize-y
                        "
                    ><?= e($description) ?></textarea>

                </div>


            </div>

        </section>


        <!-- =================================================
             PRICING & INVENTORY
        ================================================== -->

        <section
            class="
                p-6
                lg:p-8
                border-b
                border-slate-100
            "
        >


            <div class="mb-6">

                <h3
                    class="
                        text-base
                        font-bold
                        text-slate-900
                    "
                >
                    Pricing & Inventory
                </h3>


                <p
                    class="
                        text-xs
                        text-slate-500
                        mt-1
                    "
                >
                    Manage price, unit, stock level
                    and product availability.
                </p>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    sm:grid-cols-2
                    xl:grid-cols-4
                    gap-6
                "
            >


                <!-- Price -->

                <div>

                    <label
                        for="product-price"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >

                        Price (GH₵)

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <input
                        id="product-price"
                        type="number"
                        name="price"
                        value="<?= e($price) ?>"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        required
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                </div>


                <!-- Unit -->

                <div>

                    <label
                        for="product-unit"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >

                        Unit

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <input
                        id="product-unit"
                        type="text"
                        name="unit"
                        value="<?= e($unit) ?>"
                        maxlength="30"
                        required
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                </div>


                <!-- Stock -->

                <div>

                    <label
                        for="product-stock"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >

                        Stock Quantity

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <input
                        id="product-stock"
                        type="number"
                        name="stock_quantity"
                        value="<?= $stockQuantity ?>"
                        min="0"
                        step="1"
                        required
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                </div>


                <!-- Status -->

                <div>

                    <label
                        for="product-status"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >
                        Status
                    </label>


                    <select
                        id="product-status"
                        name="status"
                        class="
                            w-full
                            px-4
                            py-2.5
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-sm
                            text-slate-700
                        "
                    >

                        <option
                            value="active"
                            <?= $status === 'active'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $status === 'inactive'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


            </div>

        </section>


        <!-- =================================================
             IMAGE
        ================================================== -->

        <section
            class="
                p-6
                lg:p-8
                border-b
                border-slate-100
            "
        >


            <div class="mb-6">

                <h3
                    class="
                        text-base
                        font-bold
                        text-slate-900
                    "
                >
                    Product Image
                </h3>


                <p
                    class="
                        text-xs
                        text-slate-500
                        mt-1
                    "
                >
                    Replace or remove the product's
                    current image.
                </p>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    lg:grid-cols-2
                    gap-8
                "
            >


                <!-- Current Image -->

                <div>

                    <span
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-3
                        "
                    >
                        Current Image
                    </span>


                    <div
                        class="
                            flex
                            items-start
                            gap-4
                        "
                    >

                        <img
                            src="<?= e(
                                productImageUrl(
                                    $currentImage !== ''
                                        ? $currentImage
                                        : null
                                )
                            ) ?>"
                            alt="<?= e($name) ?>"
                            class="
                                w-28
                                h-28
                                object-cover
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50
                            "
                        >


                        <?php if (
                            $currentImage !== ''
                        ): ?>

                            <label
                                class="
                                    inline-flex
                                    items-start
                                    gap-2
                                    text-sm
                                    text-slate-600
                                    cursor-pointer
                                    mt-2
                                "
                            >

                                <input
                                    type="checkbox"
                                    name="remove_image"
                                    value="1"
                                    class="
                                        w-4
                                        h-4
                                        rounded
                                        text-rose-600
                                        border-slate-300
                                    "
                                >

                                <span>

                                    <strong
                                        class="
                                            block
                                            text-slate-700
                                        "
                                    >
                                        Remove image
                                    </strong>

                                    <small
                                        class="
                                            block
                                            text-slate-400
                                            mt-1
                                        "
                                    >
                                        Leave unchecked to
                                        keep the current image.
                                    </small>

                                </span>

                            </label>

                        <?php endif; ?>


                    </div>

                </div>


                <!-- Replacement -->

                <div>

                    <label
                        for="product-image"
                        class="
                            block
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-slate-700
                            mb-2
                        "
                    >
                        Replacement Image
                    </label>


                    <input
                        id="product-image"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        class="
                            block
                            w-full
                            text-sm
                            text-slate-500

                            file:mr-4
                            file:py-2.5
                            file:px-4
                            file:rounded-xl
                            file:border-0
                            file:text-xs
                            file:font-semibold
                            file:bg-brand-50
                            file:text-brand-700

                            hover:file:bg-brand-100
                        "
                    >


                    <p
                        class="
                            text-[11px]
                            text-slate-400
                            mt-2
                        "
                    >
                        JPG, PNG or WebP. Maximum 5 MB.
                        Selecting a new image replaces the old one.
                    </p>


                    <div
                        id="new-image-preview-wrapper"
                        class="hidden mt-4"
                    >

                        <img
                            id="new-image-preview"
                            src=""
                            alt="New product image preview"
                            class="
                                w-28
                                h-28
                                object-cover
                                rounded-2xl
                                border
                                border-slate-200
                            "
                        >

                    </div>

                </div>


            </div>

        </section>


        <!-- =================================================
             ATTRIBUTES
        ================================================== -->

        <section
            class="
                p-6
                lg:p-8
                border-b
                border-slate-100
            "
        >


            <h3
                class="
                    text-base
                    font-bold
                    text-slate-900
                    mb-5
                "
            >
                Product Attributes
            </h3>


            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-4
                "
            >


                <!-- Organic -->

                <label
                    class="
                        flex
                        items-start
                        gap-3
                        p-4
                        rounded-xl
                        border
                        border-slate-200
                        hover:bg-slate-50
                        cursor-pointer
                    "
                >

                    <input
                        type="checkbox"
                        name="is_organic"
                        value="1"
                        <?= $isOrganic
                            ? 'checked'
                            : ''
                        ?>
                        class="
                            w-4
                            h-4
                            mt-0.5
                            rounded
                            text-brand-600
                            border-slate-300
                            focus:ring-brand-600
                        "
                    >


                    <span>

                        <strong
                            class="
                                block
                                text-sm
                                text-slate-800
                            "
                        >
                            Organic
                        </strong>


                        <span
                            class="
                                block
                                text-xs
                                text-slate-500
                                mt-1
                            "
                        >
                            Display the Organic badge
                            for this product.
                        </span>

                    </span>

                </label>


                <!-- Featured -->

                <label
                    class="
                        flex
                        items-start
                        gap-3
                        p-4
                        rounded-xl
                        border
                        border-slate-200
                        hover:bg-slate-50
                        cursor-pointer
                    "
                >

                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        <?= $isFeatured
                            ? 'checked'
                            : ''
                        ?>
                        class="
                            w-4
                            h-4
                            mt-0.5
                            rounded
                            text-brand-600
                            border-slate-300
                            focus:ring-brand-600
                        "
                    >


                    <span>

                        <strong
                            class="
                                block
                                text-sm
                                text-slate-800
                            "
                        >
                            Featured Product
                        </strong>


                        <span
                            class="
                                block
                                text-xs
                                text-slate-500
                                mt-1
                            "
                        >
                            Give this product priority
                            in featured sections.
                        </span>

                    </span>

                </label>


            </div>

        </section>


        <!-- =================================================
             ACTIONS
        ================================================== -->

        <div
            class="
                p-6
                lg:p-8
                bg-slate-50/60
                flex
                flex-col-reverse
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
            "
        >


            <a
                href="<?= url(
                    'product.php?id=' .
                    $productId
                ) ?>"
                target="_blank"
                rel="noopener"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    px-5
                    py-3
                    rounded-xl
                    bg-white
                    border
                    border-slate-200
                    hover:bg-slate-100
                    text-slate-600
                    font-semibold
                    text-sm
                "
            >

                <i
                    data-lucide="external-link"
                    class="w-4 h-4"
                ></i>

                View Product

            </a>


            <div
                class="
                    flex
                    flex-col-reverse
                    sm:flex-row
                    gap-3
                "
            >

                <a
                    href="<?= url('admin/products.php') ?>"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        px-6
                        py-3
                        rounded-xl
                        bg-white
                        border
                        border-slate-200
                        hover:bg-slate-100
                        text-slate-700
                        font-semibold
                        text-sm
                    "
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    <?= (
                        $categoriesLoadError ||
                        !$categories
                    )
                        ? 'disabled'
                        : ''
                    ?>
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        px-6
                        py-3
                        rounded-xl
                        bg-brand-600
                        hover:bg-brand-700
                        disabled:bg-slate-300
                        disabled:cursor-not-allowed
                        text-white
                        font-semibold
                        text-sm
                        shadow-md
                        shadow-brand-600/20
                    "
                >

                    <i
                        data-lucide="save"
                        class="w-4 h-4"
                    ></i>

                    Update Product

                </button>

            </div>


        </div>


    </form>


</div>


<!-- =========================================================
     IMAGE PREVIEW
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const input =
            document.getElementById(
                'product-image'
            );

        const preview =
            document.getElementById(
                'new-image-preview'
            );

        const wrapper =
            document.getElementById(
                'new-image-preview-wrapper'
            );


        if (
            !input ||
            !preview ||
            !wrapper
        ) {

            return;
        }


        input.addEventListener(
            'change',
            function () {

                const file =
                    this.files &&
                    this.files[0]
                        ? this.files[0]
                        : null;


                if (!file) {

                    preview.src = '';

                    wrapper.classList.add(
                        'hidden'
                    );

                    return;
                }


                const objectUrl =
                    URL.createObjectURL(
                        file
                    );


                preview.src =
                    objectUrl;


                wrapper.classList.remove(
                    'hidden'
                );


                preview.onload =
                    function () {

                        URL.revokeObjectURL(
                            objectUrl
                        );

                    };

            }
        );

    }
);

</script>


<?php

require_once __DIR__ .
    '/includes/admin-footer.php';

?>