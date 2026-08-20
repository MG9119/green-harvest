'use strict';

/**
 * =========================================================
 * GREEN HARVEST - SHARED JAVASCRIPT
 * =========================================================
 *
 * Shared functionality that is not already handled
 * by the public or administrator footers.
 *
 * Current responsibility:
 * - Generic image preview support
 * =========================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | Image Preview
    |--------------------------------------------------------------------------
    |
    | Usage:
    |
    | <input
    |     type="file"
    |     data-preview-input="#preview-image"
    | >
    |
    | <img
    |     id="preview-image"
    |     class="hidden"
    | >
    |
    */

    document
        .querySelectorAll('[data-preview-input]')
        .forEach((input) => {

            const selector =
                input.dataset.previewInput;


            if (!selector) {
                return;
            }


            const targetImage =
                document.querySelector(selector);


            if (!targetImage) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | File Upload Preview
            |--------------------------------------------------------------------------
            */

            if (input.type === 'file') {

                input.addEventListener(
                    'change',
                    () => {

                        const file =
                            input.files?.[0];


                        if (!file) {
                            return;
                        }


                        if (
                            !file.type.startsWith(
                                'image/'
                            )
                        ) {
                            return;
                        }


                        const reader =
                            new FileReader();


                        reader.addEventListener(
                            'load',
                            () => {

                                if (
                                    typeof reader.result
                                    !== 'string'
                                ) {
                                    return;
                                }


                                targetImage.src =
                                    reader.result;


                                targetImage.classList.remove(
                                    'd-none',
                                    'hidden'
                                );
                            }
                        );


                        reader.readAsDataURL(
                            file
                        );
                    }
                );


                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Image URL Preview
            |--------------------------------------------------------------------------
            */

            input.addEventListener(
                'input',
                () => {

                    const imageUrl =
                        input.value.trim();


                    if (imageUrl === '') {
                        return;
                    }


                    /*
                     * Only allow normal HTTP/HTTPS URLs
                     * for direct preview fields.
                     */
                    try {

                        const parsedUrl =
                            new URL(imageUrl);


                        if (
                            parsedUrl.protocol !== 'http:' &&
                            parsedUrl.protocol !== 'https:'
                        ) {
                            return;
                        }


                    } catch {

                        return;
                    }


                    targetImage.src =
                        imageUrl;


                    targetImage.classList.remove(
                        'd-none',
                        'hidden'
                    );
                }
            );

        });

});