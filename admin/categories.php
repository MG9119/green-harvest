<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN CATEGORY MANAGEMENT
 * =========================================================
 *
 * Responsibilities:
 * - Protect the admin page
 * - Add categories
 * - Edit categories
 * - Upload/replace/remove category images
 * - Prevent deletion of categories containing products
 * - Safely delete unused category images
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Helper: Check Whether Image Is a Managed Local Upload
|--------------------------------------------------------------------------
*/

$isLocalCategoryImage = static function (?string $image): bool {

    $image = trim((string) $image);

    if ($image === '') {
        return false;
    }

    /*
     * External URLs must never be deleted locally.
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
     * Must be a filename only.
     */
    if (
        basename($image) !== $image ||
        str_contains($image, '..') ||
        str_contains($image, '/') ||
        str_contains($image, '\\')
    ) {
        return false;
    }

    $extension = strtolower(
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
| Helper: Delete Local Category Image
|--------------------------------------------------------------------------
*/

$deleteCategoryImage =
    static function (?string $image) use ($isLocalCategoryImage): void {

        if (
            !$isLocalCategoryImage(
                $image
            )
        ) {
            return;
        }

        $path =
            CATEGORY_UPLOAD_PATH .
            DIRECTORY_SEPARATOR .
            $image;

        if (is_file($path)) {
            @unlink($path);
        }
    };


/*
|--------------------------------------------------------------------------
| Helper: Upload Category Image
|--------------------------------------------------------------------------
|
| Returns:
| - null if no image was selected
| - generated filename if upload succeeds
|
*/

$uploadCategoryImage = static function (): ?string {

    if (
        !isset($_FILES['image']) ||
        !is_array($_FILES['image'])
    ) {
        return null;
    }


    $file = $_FILES['image'];

    $error =
        (int) (
            $file['error']
            ?? UPLOAD_ERR_NO_FILE
        );


    /*
     * Image is optional.
     */
    if (
        $error === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }


    if (
        $error !== UPLOAD_ERR_OK
    ) {

        throw new RuntimeException(
            'The category image could not be uploaded.'
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
            'Category images must be 5 MB or smaller.'
        );
    }


    if (
        $temporaryPath === '' ||
        !is_uploaded_file($temporaryPath)
    ) {

        throw new RuntimeException(
            'The uploaded category image is invalid.'
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


    /*
     * Fallback if Fileinfo is unavailable.
     */
    if (
        $mimeType === '' &&
        isset($imageInformation['mime'])
    ) {

        $mimeType =
            (string) $imageInformation['mime'];
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
            'Only JPG, PNG and WebP category images are allowed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure Category Upload Directory Exists
    |--------------------------------------------------------------------------
    */

    if (
        !is_dir(
            CATEGORY_UPLOAD_PATH
        )
    ) {

        if (
            !mkdir(
                CATEGORY_UPLOAD_PATH,
                0755,
                true
            ) &&
            !is_dir(
                CATEGORY_UPLOAD_PATH
            )
        ) {

            throw new RuntimeException(
                'The category upload directory could not be created.'
            );
        }
    }


    if (
        !is_writable(
            CATEGORY_UPLOAD_PATH
        )
    ) {

        throw new RuntimeException(
            'The category upload directory is not writable.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Secure Filename
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
        CATEGORY_UPLOAD_PATH .
        DIRECTORY_SEPARATOR .
        $fileName;


    if (
        !move_uploaded_file(
            $temporaryPath,
            $destination
        )
    ) {

        throw new RuntimeException(
            'The category image could not be saved.'
        );
    }


    return $fileName;
};


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$editId =
    filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );


if (
    $editId === false ||
    $editId === null ||
    $editId < 1
) {
    $editId = 0;
}


$formId = $editId;

$formName = '';

$formDescription = '';

$currentImage = '';

$formErrors = [];


/*
|--------------------------------------------------------------------------
| Load Category Being Edited
|--------------------------------------------------------------------------
*/

if ($editId > 0) {

    try {

        $stmt = $pdo->prepare(
            '
            SELECT
                id,
                name,
                description,
                image,
                created_at,
                updated_at

            FROM categories

            WHERE id = ?

            LIMIT 1
            '
        );


        $stmt->execute([
            $editId,
        ]);


        $editCategory =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$editCategory) {

            setFlash(
                'error',
                'The selected category could not be found.'
            );

            redirectTo(
                'admin/categories.php'
            );
        }


        $formId =
            (int) $editCategory['id'];


        $formName =
            (string) $editCategory['name'];


        $formDescription =
            (string) (
                $editCategory['description']
                ?? ''
            );


        $currentImage =
            trim(
                (string) (
                    $editCategory['image']
                    ?? ''
                )
            );


    } catch (PDOException $e) {

        error_log(
            'Green Harvest edit category loading error: ' .
            $e->getMessage()
        );


        setFlash(
            'error',
            'The selected category could not be loaded.'
        );


        redirectTo(
            'admin/categories.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Handle POST Requests
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action =
        trim(
            (string) (
                $_POST['action']
                ?? 'save'
            )
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

        setFlash(
            'error',
            'Invalid category request. Please try again.'
        );

        redirectTo(
            'admin/categories.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CATEGORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $categoryId =
            filter_var(
                $_POST['id']
                ?? null,
                FILTER_VALIDATE_INT
            );


        if (
            $categoryId === false ||
            $categoryId === null ||
            $categoryId <= 0
        ) {

            setFlash(
                'error',
                'Invalid category selected.'
            );

            redirectTo(
                'admin/categories.php'
            );
        }


        try {

            /*
             * Load category first.
             */
            $stmt = $pdo->prepare(
                '
                SELECT
                    id,
                    name,
                    image

                FROM categories

                WHERE id = ?

                LIMIT 1
                '
            );


            $stmt->execute([
                $categoryId,
            ]);


            $category =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$category) {

                setFlash(
                    'error',
                    'The selected category could not be found.'
                );

                redirectTo(
                    'admin/categories.php'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Check Assigned Products
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                '
                SELECT COUNT(*)

                FROM products

                WHERE category_id = ?
                '
            );


            $stmt->execute([
                $categoryId,
            ]);


            $productCount =
                (int) $stmt->fetchColumn();


            /*
             * products.category_id is mandatory.
             * Do not delete a category while products use it.
             */
            if ($productCount > 0) {

                setFlash(
                    'warning',
                    'This category cannot be deleted because ' .
                    $productCount .
                    ' product' .
                    ($productCount === 1 ? ' is' : 's are') .
                    ' currently assigned to it.'
                );


                redirectTo(
                    'admin/categories.php'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Delete Category
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                '
                DELETE FROM categories

                WHERE id = ?
                '
            );


            $stmt->execute([
                $categoryId,
            ]);


            if (
                $stmt->rowCount() === 1
            ) {

                /*
                 * Delete local category image only after
                 * database deletion succeeds.
                 */
                $deleteCategoryImage(
                    $category['image']
                    ?? null
                );


                setFlash(
                    'success',
                    'Category deleted successfully.'
                );

            } else {

                setFlash(
                    'error',
                    'The category could not be deleted.'
                );
            }


        } catch (PDOException $e) {

            error_log(
                'Green Harvest category deletion error: ' .
                $e->getMessage()
            );


            setFlash(
                'error',
                'The category could not be deleted. Make sure no products are assigned to it.'
            );
        }


        redirectTo(
            'admin/categories.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADD / UPDATE CATEGORY
    |--------------------------------------------------------------------------
    */

    $formId =
        (int) (
            $_POST['id']
            ?? 0
        );


    $formName =
        trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );


    $formDescription =
        trim(
            (string) (
                $_POST['description']
                ?? ''
            )
        );


    $removeImage =
        isset(
            $_POST['remove_image']
        );


    /*
    |--------------------------------------------------------------------------
    | Validate Category Name
    |--------------------------------------------------------------------------
    */

    if ($formName === '') {

        $formErrors[] =
            'Category name is required.';

    } elseif (
        strlen($formName) > 120
    ) {

        $formErrors[] =
            'Category name cannot exceed 120 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Load Existing Category for Update
    |--------------------------------------------------------------------------
    */

    $existingCategory = null;

    $existingImage = '';


    if (
        $formId > 0 &&
        !$formErrors
    ) {

        try {

            $stmt = $pdo->prepare(
                '
                SELECT
                    id,
                    name,
                    image

                FROM categories

                WHERE id = ?

                LIMIT 1
                '
            );


            $stmt->execute([
                $formId,
            ]);


            $existingCategory =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$existingCategory) {

                $formErrors[] =
                    'The category you are trying to update no longer exists.';

            } else {

                $existingImage =
                    trim(
                        (string) (
                            $existingCategory['image']
                            ?? ''
                        )
                    );


                $currentImage =
                    $existingImage;
            }


        } catch (PDOException $e) {

            error_log(
                'Green Harvest category update lookup error: ' .
                $e->getMessage()
            );


            $formErrors[] =
                'The category could not be loaded for updating.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Category Names
    |--------------------------------------------------------------------------
    */

    if (!$formErrors) {

        try {

            if ($formId > 0) {

                $stmt = $pdo->prepare(
                    '
                    SELECT id

                    FROM categories

                    WHERE name = ?
                      AND id != ?

                    LIMIT 1
                    '
                );


                $stmt->execute([
                    $formName,
                    $formId,
                ]);

            } else {

                $stmt = $pdo->prepare(
                    '
                    SELECT id

                    FROM categories

                    WHERE name = ?

                    LIMIT 1
                    '
                );


                $stmt->execute([
                    $formName,
                ]);
            }


            if ($stmt->fetch()) {

                $formErrors[] =
                    'A category with this name already exists.';
            }


        } catch (PDOException $e) {

            error_log(
                'Green Harvest category duplicate check error: ' .
                $e->getMessage()
            );


            $formErrors[] =
                'The category name could not be validated.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Category
    |--------------------------------------------------------------------------
    */

    if (!$formErrors) {

        $newUploadedImage = null;


        try {

            /*
             * Upload replacement/new image.
             */
            $newUploadedImage =
                $uploadCategoryImage();


            /*
            |--------------------------------------------------------------------------
            | Determine Image Value
            |--------------------------------------------------------------------------
            */

            if (
                $newUploadedImage !== null
            ) {

                $imageToStore =
                    $newUploadedImage;

            } elseif (
                $formId > 0 &&
                $removeImage
            ) {

                $imageToStore =
                    null;

            } elseif (
                $formId > 0 &&
                $existingImage !== ''
            ) {

                $imageToStore =
                    $existingImage;

            } else {

                $imageToStore =
                    null;
            }


            /*
            |--------------------------------------------------------------------------
            | Update Existing Category
            |--------------------------------------------------------------------------
            */

            if ($formId > 0) {

                $stmt = $pdo->prepare(
                    '
                    UPDATE categories

                    SET
                        name = ?,
                        description = ?,
                        image = ?

                    WHERE id = ?
                    '
                );


                $stmt->execute([

                    $formName,

                    $formDescription !== ''
                        ? $formDescription
                        : null,

                    $imageToStore,

                    $formId,

                ]);


                /*
                 * Remove old managed image only after
                 * the database update succeeds.
                 */
                if (
                    (
                        $newUploadedImage !== null ||
                        $removeImage
                    ) &&
                    $existingImage !== '' &&
                    $existingImage !== $imageToStore
                ) {

                    $deleteCategoryImage(
                        $existingImage
                    );
                }


                setFlash(
                    'success',
                    'Category updated successfully.'
                );

            } else {

                /*
                |--------------------------------------------------------------------------
                | Add New Category
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    '
                    INSERT INTO categories
                    (
                        name,
                        description,
                        image
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                    '
                );


                $stmt->execute([

                    $formName,

                    $formDescription !== ''
                        ? $formDescription
                        : null,

                    $imageToStore,

                ]);


                setFlash(
                    'success',
                    'Category added successfully.'
                );
            }


            redirectTo(
                'admin/categories.php'
            );


        } catch (
            RuntimeException $e
        ) {

            $formErrors[] =
                $e->getMessage();


        } catch (
            PDOException $e
        ) {

            error_log(
                'Green Harvest category save error: ' .
                $e->getMessage()
            );


            /*
             * Remove newly uploaded orphan image
             * if the database operation failed.
             */
            if (
                $newUploadedImage !== null
            ) {

                $deleteCategoryImage(
                    $newUploadedImage
                );
            }


            $formErrors[] =
                'The category could not be saved. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Category List
|--------------------------------------------------------------------------
*/

$categories = [];

$categoriesLoadError = false;


try {

    $stmt = $pdo->query(
        '
        SELECT
            c.id,
            c.name,
            c.description,
            c.image,
            c.created_at,
            c.updated_at,

            COUNT(p.id) AS product_count

        FROM categories c

        LEFT JOIN products p
            ON p.category_id = c.id

        GROUP BY
            c.id,
            c.name,
            c.description,
            c.image,
            c.created_at,
            c.updated_at

        ORDER BY
            c.name ASC
        '
    );


    $categories =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Green Harvest categories loading error: ' .
        $e->getMessage()
    );


    $categoriesLoadError = true;
}


/*
|--------------------------------------------------------------------------
| Render Admin Page
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Category Management';


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
            Product Categories
        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >
            Organize Green Harvest products into
            clear shopping categories.
        </p>

    </div>


    <span
        class="
            inline-flex
            items-center
            gap-2
            px-3
            py-1.5
            rounded-full
            bg-brand-50
            border
            border-brand-100
            text-xs
            font-bold
            text-brand-700
        "
    >

        <i
            data-lucide="folder-tree"
            class="w-4 h-4"
        ></i>

        <?= number_format(
            count($categories)
        ) ?>

        categor<?= count($categories) === 1 ? 'y' : 'ies' ?>

    </span>

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

        <div class="flex items-start gap-3">

            <i
                data-lucide="circle-alert"
                class="
                    w-5
                    h-5
                    flex-shrink-0
                    mt-0.5
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
                        list-disc
                        pl-5
                        text-xs
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
            border-rose-200
            bg-rose-50
            px-4
            py-3
            text-sm
            text-rose-700
        "
    >
        The category list could not be loaded.
        Please refresh the page.
    </div>

<?php endif; ?>


<!-- =========================================================
     CATEGORY MANAGEMENT
========================================================= -->

<div
    class="
        grid
        grid-cols-1
        xl:grid-cols-12
        gap-8
        items-start
    "
>


    <!-- =====================================================
         CATEGORY FORM
    ====================================================== -->

    <div class="xl:col-span-4">


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


            <!-- Form Header -->

            <div
                class="
                    px-6
                    py-5
                    border-b
                    border-slate-100
                    flex
                    items-center
                    justify-between
                    gap-3
                "
            >

                <div>

                    <h3
                        class="
                            font-bold
                            text-slate-900
                        "
                    >

                        <?= $formId > 0
                            ? 'Edit Category'
                            : 'Add Category'
                        ?>

                    </h3>


                    <p
                        class="
                            text-xs
                            text-slate-400
                            mt-1
                        "
                    >

                        <?= $formId > 0
                            ? 'Update the selected category.'
                            : 'Create a new product category.'
                        ?>

                    </p>

                </div>


                <?php if ($formId > 0): ?>

                    <a
                        href="<?= url(
                            'admin/categories.php'
                        ) ?>"
                        class="
                            text-xs
                            font-bold
                            text-rose-600
                            hover:text-rose-700
                        "
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>


            <!-- Form -->

            <form
                method="post"
                action="<?= url(
                    'admin/categories.php'
                ) ?>"
                enctype="multipart/form-data"
                class="p-6 space-y-5"
            >

                <?= csrfField() ?>


                <input
                    type="hidden"
                    name="action"
                    value="save"
                >


                <input
                    type="hidden"
                    name="id"
                    value="<?= $formId ?>"
                >


                <!-- Category Name -->

                <div>

                    <label
                        for="category-name"
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

                        Category Name

                        <span class="text-rose-500">
                            *
                        </span>

                    </label>


                    <input
                        id="category-name"
                        type="text"
                        name="name"
                        value="<?= e(
                            $formName
                        ) ?>"
                        maxlength="120"
                        required
                        placeholder="e.g. Organic Vegetables"
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


                <!-- Description -->

                <div>

                    <label
                        for="category-description"
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
                        id="category-description"
                        name="description"
                        rows="4"
                        placeholder="Describe the products found in this category..."
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
                    ><?= e(
                        $formDescription
                    ) ?></textarea>

                </div>


                <!-- Current Image -->

                <?php if (
                    $formId > 0 &&
                    $currentImage !== ''
                ): ?>

                    <div>

                        <span
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
                            Current Image
                        </span>


                        <div
                            class="
                                flex
                                items-center
                                gap-4
                                p-3
                                rounded-xl
                                bg-slate-50
                                border
                                border-slate-200
                            "
                        >

                            <img
                                src="<?= e(
                                    categoryImageUrl(
                                        $currentImage
                                    )
                                ) ?>"
                                alt="<?= e(
                                    $formName
                                ) ?>"
                                class="
                                    w-16
                                    h-16
                                    rounded-xl
                                    object-cover
                                    bg-white
                                    border
                                    border-slate-200
                                "
                            >


                            <label
                                class="
                                    flex
                                    items-start
                                    gap-2
                                    cursor-pointer
                                "
                            >

                                <input
                                    type="checkbox"
                                    name="remove_image"
                                    value="1"
                                    class="
                                        mt-0.5
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
                                            text-xs
                                            text-slate-700
                                        "
                                    >
                                        Remove image
                                    </strong>


                                    <small
                                        class="
                                            block
                                            text-[11px]
                                            text-slate-400
                                            mt-1
                                        "
                                    >
                                        Leave unchecked
                                        to keep it.
                                    </small>

                                </span>

                            </label>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- Image Upload -->

                <div>

                    <label
                        for="category-image"
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

                        <?= $currentImage !== ''
                            ? 'Replacement Image'
                            : 'Category Image'
                        ?>

                    </label>


                    <input
                        id="category-image"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        class="
                            block
                            w-full
                            text-sm
                            text-slate-500

                            file:mr-3
                            file:py-2
                            file:px-3
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
                            mt-2
                            text-[11px]
                            text-slate-400
                        "
                    >
                        JPG, PNG or WebP. Maximum 5 MB.
                    </p>


                    <!-- New Preview -->

                    <div
                        id="category-preview-wrapper"
                        class="hidden mt-4"
                    >

                        <img
                            id="category-preview"
                            src=""
                            alt="Category image preview"
                            class="
                                w-28
                                h-28
                                rounded-2xl
                                object-cover
                                border
                                border-slate-200
                            "
                        >

                    </div>

                </div>


                <!-- Save Button -->

                <button
                    type="submit"
                    class="
                        w-full
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        py-3
                        px-4
                        rounded-xl
                        bg-brand-600
                        hover:bg-brand-700
                        text-white
                        font-semibold
                        text-sm
                        transition-colors
                        shadow-md
                        shadow-brand-600/20
                    "
                >

                    <i
                        data-lucide="<?= $formId > 0
                            ? 'save'
                            : 'plus'
                        ?>"
                        class="w-4 h-4"
                    ></i>


                    <?= $formId > 0
                        ? 'Update Category'
                        : 'Save Category'
                    ?>

                </button>


            </form>


        </div>


    </div>


    <!-- =====================================================
         CATEGORY LIST
    ====================================================== -->

    <div class="xl:col-span-8">


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


            <!-- Table Header -->

            <div
                class="
                    px-6
                    py-5
                    border-b
                    border-slate-100
                    flex
                    items-center
                    justify-between
                "
            >

                <div>

                    <h3
                        class="
                            font-bold
                            text-slate-900
                        "
                    >
                        All Categories
                    </h3>


                    <p
                        class="
                            text-xs
                            text-slate-400
                            mt-1
                        "
                    >
                        Categories currently available
                        in Green Harvest.
                    </p>

                </div>

            </div>


            <!-- Table -->

            <div class="overflow-x-auto">


                <table
                    class="
                        w-full
                        text-left
                        text-sm
                        border-collapse
                    "
                >


                    <thead>

                        <tr
                            class="
                                bg-slate-50/70
                                border-b
                                border-slate-100
                                text-slate-500
                                text-xs
                                font-bold
                                uppercase
                                tracking-wider
                            "
                        >

                            <th class="py-3.5 px-6">
                                Category
                            </th>

                            <th class="py-3.5 px-6">
                                Description
                            </th>

                            <th
                                class="
                                    py-3.5
                                    px-6
                                    text-center
                                "
                            >
                                Products
                            </th>

                            <th
                                class="
                                    py-3.5
                                    px-6
                                    text-right
                                "
                            >
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="
                            divide-y
                            divide-slate-100
                        "
                    >


                        <?php if (!$categories): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="
                                        py-14
                                        px-6
                                        text-center
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            flex-col
                                            items-center
                                        "
                                    >

                                        <span
                                            class="
                                                w-14
                                                h-14
                                                rounded-2xl
                                                bg-slate-100
                                                text-slate-400
                                                flex
                                                items-center
                                                justify-center
                                                mb-3
                                            "
                                        >

                                            <i
                                                data-lucide="folder-plus"
                                                class="w-6 h-6"
                                            ></i>

                                        </span>


                                        <h4
                                            class="
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            No categories yet
                                        </h4>


                                        <p
                                            class="
                                                mt-1
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            Use the form to create
                                            your first category.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>


                        <?php foreach (
                            $categories as $category
                        ): ?>

                            <?php

                            $categoryId =
                                (int) $category['id'];


                            $productCount =
                                (int) $category['product_count'];


                            $description =
                                trim(
                                    (string) (
                                        $category['description']
                                        ?? ''
                                    )
                                );


                            $shortDescription =
                                $description;


                            if (
                                strlen(
                                    $shortDescription
                                ) > 90
                            ) {

                                $shortDescription =
                                    substr(
                                        $shortDescription,
                                        0,
                                        87
                                    ) .
                                    '...';
                            }

                            ?>


                            <tr
                                class="
                                    hover:bg-slate-50/60
                                    transition-colors
                                "
                            >


                                <!-- Category -->

                                <td
                                    class="
                                        py-4
                                        px-6
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            items-center
                                            gap-3
                                            min-w-[190px]
                                        "
                                    >


                                        <img
                                            src="<?= e(
                                                categoryImageUrl(
                                                    $category['image']
                                                    ?? null
                                                )
                                            ) ?>"
                                            alt="<?= e(
                                                $category['name']
                                            ) ?>"
                                            class="
                                                w-12
                                                h-12
                                                object-cover
                                                rounded-xl
                                                border
                                                border-slate-100
                                                bg-slate-50
                                                flex-shrink-0
                                            "
                                            loading="lazy"
                                        >


                                        <div>

                                            <strong
                                                class="
                                                    block
                                                    text-slate-900
                                                "
                                            >

                                                <?= e(
                                                    $category['name']
                                                ) ?>

                                            </strong>


                                            <span
                                                class="
                                                    block
                                                    mt-1
                                                    text-[11px]
                                                    text-slate-400
                                                "
                                            >

                                                Category #<?= $categoryId ?>

                                            </span>

                                        </div>


                                    </div>

                                </td>


                                <!-- Description -->

                                <td
                                    class="
                                        py-4
                                        px-6
                                        text-slate-500
                                        max-w-xs
                                    "
                                >

                                    <?= $shortDescription !== ''
                                        ? e($shortDescription)
                                        : '—'
                                    ?>

                                </td>


                                <!-- Product Count -->

                                <td
                                    class="
                                        py-4
                                        px-6
                                        text-center
                                    "
                                >

                                    <a
                                        href="<?= url(
                                            'admin/products.php?category=' .
                                            $categoryId
                                        ) ?>"
                                        class="
                                            inline-flex
                                            items-center
                                            justify-center
                                            min-w-9
                                            px-2.5
                                            py-1
                                            rounded-full
                                            bg-brand-50
                                            border
                                            border-brand-100
                                            text-brand-700
                                            text-xs
                                            font-bold
                                            hover:bg-brand-100
                                        "
                                    >

                                        <?= $productCount ?>

                                    </a>

                                </td>


                                <!-- Actions -->

                                <td
                                    class="
                                        py-4
                                        px-6
                                        text-right
                                        whitespace-nowrap
                                    "
                                >

                                    <div
                                        class="
                                            inline-flex
                                            items-center
                                            gap-2
                                        "
                                    >


                                        <!-- Public Category -->

                                        <a
                                            href="<?= url(
                                                'category.php?id=' .
                                                $categoryId
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener"
                                            class="
                                                inline-flex
                                                items-center
                                                justify-center
                                                w-8
                                                h-8
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                text-slate-500
                                                hover:text-brand-700
                                                hover:bg-brand-50
                                                transition-colors
                                            "
                                            title="View category"
                                        >

                                            <i
                                                data-lucide="eye"
                                                class="w-4 h-4"
                                            ></i>

                                        </a>


                                        <!-- Edit -->

                                        <a
                                            href="<?= url(
                                                'admin/categories.php?edit=' .
                                                $categoryId
                                            ) ?>"
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                px-3
                                                py-1.5
                                                rounded-lg
                                                bg-slate-100
                                                hover:bg-slate-200
                                                text-slate-700
                                                font-semibold
                                                text-xs
                                                transition-colors
                                            "
                                        >

                                            <i
                                                data-lucide="pencil"
                                                class="w-3.5 h-3.5"
                                            ></i>

                                            Edit

                                        </a>


                                        <!-- Delete -->

                                        <?php if (
                                            $productCount === 0
                                        ): ?>

                                            <form
                                                method="post"
                                                action="<?= url(
                                                    'admin/categories.php'
                                                ) ?>"
                                                class="inline-block"
                                            >

                                                <?= csrfField() ?>


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $categoryId ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    data-confirm="Delete this category permanently?"
                                                    class="
                                                        inline-flex
                                                        items-center
                                                        gap-1.5
                                                        px-3
                                                        py-1.5
                                                        rounded-lg
                                                        bg-rose-50
                                                        hover:bg-rose-100
                                                        text-rose-600
                                                        font-semibold
                                                        text-xs
                                                        transition-colors
                                                    "
                                                >

                                                    <i
                                                        data-lucide="trash-2"
                                                        class="w-3.5 h-3.5"
                                                    ></i>

                                                    Delete

                                                </button>

                                            </form>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                disabled
                                                title="Move or remove the assigned products before deleting this category."
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1.5
                                                    px-3
                                                    py-1.5
                                                    rounded-lg
                                                    bg-slate-50
                                                    text-slate-300
                                                    font-semibold
                                                    text-xs
                                                    cursor-not-allowed
                                                "
                                            >

                                                <i
                                                    data-lucide="lock"
                                                    class="w-3.5 h-3.5"
                                                ></i>

                                                Delete

                                            </button>


                                        <?php endif; ?>


                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </div>


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
                'category-image'
            );


        const preview =
            document.getElementById(
                'category-preview'
            );


        const previewWrapper =
            document.getElementById(
                'category-preview-wrapper'
            );


        if (
            !imageInput ||
            !preview ||
            !previewWrapper
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

                    previewWrapper.classList.add(
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


                previewWrapper.classList.remove(
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