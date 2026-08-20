<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN FOOTER
 * =========================================================
 *
 * Responsibilities:
 * - Close the admin content layout
 * - Initialize Lucide icons
 * - Provide shared admin confirmation behaviour
 * - Load optional shared JavaScript
 * =========================================================
 */

?>

        </main>

    </div>

</div>


<!-- =========================================================
     ADMIN JAVASCRIPT
========================================================= -->

<script>

/*
|--------------------------------------------------------------------------
| Lucide Icons
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (
            typeof lucide !== 'undefined'
        ) {

            lucide.createIcons();

        }

    }
);


/*
|--------------------------------------------------------------------------
| Confirmation Buttons
|--------------------------------------------------------------------------
|
| Example:
|
| <button
|     data-confirm="Delete this product?"
| >
|     Delete
| </button>
|
*/

document.addEventListener(
    'click',
    function (event) {

        const element =
            event.target.closest(
                '[data-confirm]'
            );


        if (!element) {
            return;
        }


        const message =
            element.getAttribute(
                'data-confirm'
            )
            ||
            'Are you sure you want to continue?';


        if (!window.confirm(message)) {

            event.preventDefault();

            event.stopPropagation();

        }

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| Optional Shared JavaScript
|--------------------------------------------------------------------------
|
| Load assets/js/script.js only if the file actually exists.
| This prevents unnecessary 404 errors while the project
| is being cleaned.
|
*/

$sharedScript =
    ROOT_PATH .
    '/assets/js/script.js';


if (is_file($sharedScript)):

?>

    <script
        src="<?= url('assets/js/script.js') ?>"
    ></script>

<?php endif; ?>


</body>
</html>