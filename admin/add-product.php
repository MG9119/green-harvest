<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN ADD PRODUCT
 * =========================================================
 *
 * Responsibilities:
 * - Protect the admin page
 * - Load product categories
 * - Validate product information
 * - Generate a unique slug
 * - Safely upload a product image
 * - Insert the new product
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$name = '';

$categoryId = 0;

$description = '';

$price = '';

$unit = 'kg';

$stockQuantity = 10;

$status = 'active';

$isOrganic = true;

$isFeatured = false;

$formErrors = [];

$categories = [];

$categoriesLoadError = false;


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

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


} catch (PDOException $e) {

    error_log(
        'Green Harvest add-product category error: ' .
        $e->getMessage()
    );


    $categoriesLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Category Lookup
|--------------------------------------------------------------------------
*/

$categoryIds = [];


foreach ($categories as $category) {

    $categoryIds[] =
        (int) $category['id'];
}


/*
|--------------------------------------------------------------------------
| Unique Product Slug
|--------------------------------------------------------------------------
*/

$createProductSlug = static function (
    PDO $pdo,
    string $productName
): string {

    /*
     * Normalize the name.
     */
    $slug = strtolower(
        trim($productName)
    );


    /*
     * Attempt transliteration where available.
     */
    if (function_exists('iconv')) {

        $transliterated = @iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $slug
        );


        if (
            $transliterated !== false &&
            $transliterated !== ''
        ) {

            $slug = $transliterated;
        }
    }


    /*
     * Replace unsupported characters with hyphens.
     */
    $slug = preg_replace(
        '/[^a-z0-9]+/i',
        '-',
        $slug
    );


    $slug = trim(
        (string) $slug,
        '-'
    );


    if ($slug === '') {

        $slug = 'product';
    }


    /*
     * Leave room for numeric suffixes.
     *
     * Database:
     * slug VARCHAR(180)
     */
    $baseSlug = substr(
        $slug,
        0,
        165
    );


    $slug = $baseSlug;

    $counter = 2;


    while (true) {

        $stmt = $pdo->prepare(
            '
            SELECT COUNT(*)

            FROM products

            WHERE slug = ?
            '
        );


        $stmt->execute([
            $slug,
        ]);


        $exists =
            (int) $stmt->fetchColumn() > 0;


        if (!$exists) {

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
| Product Image Upload
|--------------------------------------------------------------------------
|
| Returns:
| - null when no image was uploaded
| - filename when upload succeeds
|
| Throws RuntimeException if an attempted upload is invalid.
|
*/

$uploadProductImage = static function (): ?string {

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


    /*
     * Image is optional.
     */
    if ($error === UPLOAD_ERR_NO_FILE) {

        return null;
    }


    if ($error !== UPLOAD_ERR_OK) {

        throw new RuntimeException(
            'The product image could not be uploaded.'
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
    | File Size
    |--------------------------------------------------------------------------
    |
    | Maximum: 5 MB
    |
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
        !is_uploaded_file($temporaryPath)
    ) {

        throw new RuntimeException(
            'The uploaded image is invalid.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Actual Image
    |--------------------------------------------------------------------------
    */

    $imageInformation =
        @getimagesize(
            $temporaryPath
        );


    if ($imageInformation === false) {

        throw new RuntimeException(
            'Please upload a valid image file.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MIME Validation
    |--------------------------------------------------------------------------
    */

    $mimeType = '';


    if (
        function_exists('finfo_open')
    ) {

        $finfo =
            finfo_open(
                FILEINFO_MIME_TYPE
            );


        if ($finfo !== false) {

            $detectedMime =
                finfo_file(
                    $finfo,
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
                $finfo
            );
        }
    }


    /*
     * Fallback where Fileinfo is unavailable.
     */
    if (
        $mimeType === '' &&
        isset(
            $imageInformation['mime']
        )
    ) {

        $mimeType =
            (string) $imageInformation['mime'];
    }


    $allowedMimeTypes = [

        'image/jpeg' =>
            'jpg',

        'image/png' =>
            'png',

        'image/webp' =>
            'webp',

    ];


    if (
        !array_key_exists(
            $mimeType,
            $allowedMimeTypes
        )
    ) {

        throw new RuntimeException(
            'Only JPG, PNG and WebP product images are allowed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Upload Directory
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


    $fileName =
        bin2hex(
            random_bytes(16)
        ) .
        '.' .
        $extension;


    $destination =
        PRODUCT_UPLOAD_PATH .
        DIRECTORY_SEPARATOR .
        $fileName;


    if (
        !move_uploaded_file(
            $temporaryPath,
            $destination
        )
    ) {

        throw new RuntimeException(
            'The product image could not be saved.'
        );
    }


    return $fileName;
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
    | Preserve Submitted Values
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


    $status =
        strtolower(
            trim(
                (string) (
                    $_POST['status']
                    ?? 'active'
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


    $price =
        $priceInput;


    $stockQuantity =
        is_numeric($stockInput)
            ? (int) $stockInput
            : 0;


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
            'The selected category is invalid.';
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
        $validatedPrice > 99999999.99
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
    | Status
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
    | Insert Product
    |--------------------------------------------------------------------------
    */

    if (!$formErrors) {

        $uploadedImage = null;


        try {

            /*
             * Save image first.
             */
            $uploadedImage =
                $uploadProductImage();


            /*
             * Create unique product slug.
             */
            $slug =
                $createProductSlug(
                    $pdo,
                    $name
                );


            /*
             * Insert product.
             */
            $stmt = $pdo->prepare(
                '
                INSERT INTO products
                (
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
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
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
                    (float) $validatedPrice,
                    2
                ),

                $unit,

                (int) $validatedStock,

                $uploadedImage,

                $isOrganic
                    ? 1
                    : 0,

                $isFeatured
                    ? 1
                    : 0,

                $status,

            ]);


            setFlash(
                'success',
                'Product added successfully.'
            );


            redirectTo(
                'admin/products.php'
            );


        } catch (
            RuntimeException $e
        ) {

            /*
             * Upload/validation error.
             */
            $formErrors[] =
                $e->getMessage();


        } catch (
            PDOException $e
        ) {

            error_log(
                'Green Harvest add product database error: ' .
                $e->getMessage()
            );


            /*
             * If the image was successfully uploaded
             * but the DB insert failed, remove the
             * orphaned file.
             */
            if (
                $uploadedImage !== null
            ) {

                $uploadedPath =
                    PRODUCT_UPLOAD_PATH .
                    DIRECTORY_SEPARATOR .
                    $uploadedImage;


                if (
                    is_file(
                        $uploadedPath
                    )
                ) {

                    @unlink(
                        $uploadedPath
                    );
                }
            }


            $formErrors[] =
                'The product could not be saved. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Render Admin Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Add Product';


require_once __DIR__ . '/includes/admin-header.php';

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
            Add New Product
        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >
            Add a new product to the Green Harvest catalogue.
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
     FORM ERRORS
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


<!-- =========================================================
     CATEGORY ERROR
========================================================= -->

<?php if (
    $categoriesLoadError
): ?>

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
        Please refresh the page before adding a product.

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
        action="<?= url('admin/add-product.php') ?>"
        enctype="multipart/form-data"
    >

        <?= csrfField() ?>


        <!-- =================================================
             BASIC INFORMATION
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
                    Enter the basic information customers
                    will see in the store.
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
                        autocomplete="off"
                        placeholder="e.g. Organic Tomatoes"
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
                                (int) $category['id'];

                            ?>

                            <option
                                value="<?= $optionCategoryId ?>"
                                <?= $categoryId === $optionCategoryId
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
                        placeholder="Describe the product, quality, source or suggested use..."
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
                    Configure the selling price,
                    measurement unit and available stock.
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
                        value="<?= e(
                            (string) $price
                        ) ?>"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        required
                        inputmode="decimal"
                        placeholder="0.00"
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
                        placeholder="kg, bunch, pack, crate..."
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
                        value="<?= (int) $stockQuantity ?>"
                        min="0"
                        step="1"
                        required
                        inputmode="numeric"
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
             IMAGE & PRODUCT OPTIONS
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
                    Image & Product Options
                </h3>


                <p
                    class="
                        text-xs
                        text-slate-500
                        mt-1
                    "
                >
                    Add a product image and select
                    additional product attributes.
                </p>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    lg:grid-cols-2
                    gap-8
                    items-start
                "
            >


                <!-- Image -->

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
                        Product Image
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
                        JPG, PNG or WebP. Maximum file size: 5 MB.
                    </p>


                    <!-- Preview -->

                    <div
                        id="product-image-preview-wrapper"
                        class="
                            hidden
                            mt-4
                        "
                    >

                        <img
                            id="product-image-preview"
                            src=""
                            alt="Selected product preview"
                            class="
                                w-32
                                h-32
                                object-cover
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50
                            "
                        >

                    </div>

                </div>


                <!-- Options -->

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
                        Product Attributes
                    </span>


                    <div class="space-y-3">


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
                                        mt-0.5
                                    "
                                >
                                    Mark this product as an
                                    organic product.
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
                                        mt-0.5
                                    "
                                >
                                    Give this product priority
                                    in featured product areas.
                                </span>

                            </span>

                        </label>


                    </div>

                </div>


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
                sm:justify-end
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
                    transition-colors
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
                    transition-colors
                    shadow-md
                    shadow-brand-600/20
                "
            >

                <i
                    data-lucide="save"
                    class="w-4 h-4"
                ></i>

                Save Product

            </button>


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

        const imageInput =
            document.getElementById(
                'product-image'
            );

        const preview =
            document.getElementById(
                'product-image-preview'
            );

        const wrapper =
            document.getElementById(
                'product-image-preview-wrapper'
            );


        if (
            !imageInput ||
            !preview ||
            !wrapper
        ) {

            return;
        }


        imageInput.addEventListener(
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


                const temporaryUrl =
                    URL.createObjectURL(
                        file
                    );


                preview.src =
                    temporaryUrl;


                wrapper.classList.remove(
                    'hidden'
                );


                preview.onload =
                    function () {

                        URL.revokeObjectURL(
                            temporaryUrl
                        );

                    };

            }
        );

    }
);

</script>


<?php

require_once __DIR__ . '/includes/admin-footer.php';

?>