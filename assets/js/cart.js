"use strict";

/**
 * =========================================================
 * GREEN HARVEST - AJAX CART
 * =========================================================
 *
 * Responsibilities:
 * - Open/close compact basket drawer
 * - Intercept Add to Cart forms
 * - Intercept quantity updates
 * - Intercept remove actions
 * - Refresh drawer from cart.php?drawer=1
 * - Update navbar basket count
 * - Keep full cart page synchronized
 * =========================================================
 */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const drawer =
            document.querySelector(
                "[data-cart-drawer]"
            );

        const overlay =
            document.querySelector(
                "[data-cart-overlay]"
            );

        if (!drawer || !overlay) {
            return;
        }


        const drawerBody =
            drawer.querySelector(
                "[data-cart-drawer-body]"
            );

        const drawerCount =
            drawer.querySelector(
                "[data-cart-drawer-count]"
            );

        const drawerSubtotal =
            drawer.querySelector(
                "[data-cart-drawer-subtotal]"
            );

        const drawerDelivery =
            drawer.querySelector(
                "[data-cart-drawer-delivery]"
            );

        const drawerTotal =
            drawer.querySelector(
                "[data-cart-drawer-total]"
            );

        const checkoutLink =
            drawer.querySelector(
                "[data-cart-checkout]"
            );

        const notice =
            drawer.querySelector(
                "[data-cart-notice]"
            );


        const cartUrl =
            drawer.dataset.cartUrl;

        const shopUrl =
            drawer.dataset.shopUrl;

        let noticeTimer =
            null;

        let activeRequest =
            null;

        let drawerOpen =
            false;

        let justAddedProductId =
            null;


        /*
        |--------------------------------------------------------------------------
        | Utility: Parse JSON Safely
        |--------------------------------------------------------------------------
        */

        async function parseJson(
            response
        ) {

            const text =
                await response.text();


            if (text.trim() === "") {
                return {};
            }


            try {

                return JSON.parse(
                    text
                );

            } catch (error) {

                throw new Error(
                    "The cart server returned an invalid response."
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Utility: Set Loading
        |--------------------------------------------------------------------------
        */

        function setDrawerLoading() {

            if (!drawerBody) {
                return;
            }


            drawerBody.innerHTML = `
                <div class="gh-cart-loading">
                    <div>
                        <div
                            class="spinner-border"
                            role="status"
                            aria-hidden="true"
                        ></div>
                        <div>Loading your basket...</div>
                    </div>
                </div>
            `;

        }


        /*
        |--------------------------------------------------------------------------
        | Utility: Notice
        |--------------------------------------------------------------------------
        */

        function showNotice(
            message,
            type = "success"
        ) {

            if (!notice) {
                return;
            }


            window.clearTimeout(
                noticeTimer
            );


            notice.classList.remove(
                "error"
            );


            if (type === "error") {

                notice.classList.add(
                    "error"
                );

            }


            notice.innerHTML = "";


            const icon =
                document.createElement(
                    "i"
                );


            icon.className =
                type === "error"
                    ? "bi bi-exclamation-circle-fill"
                    : "bi bi-check-circle-fill";


            const text =
                document.createElement(
                    "span"
                );


            text.textContent =
                message;


            notice.appendChild(
                icon
            );


            notice.appendChild(
                text
            );


            notice.classList.add(
                "show"
            );


            noticeTimer =
                window.setTimeout(
                    function () {

                        notice.classList.remove(
                            "show"
                        );

                    },
                    2600
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Open / Close
        |--------------------------------------------------------------------------
        */

        function openDrawer() {

            drawerOpen =
                true;


            drawer.classList.add(
                "open"
            );


            overlay.classList.add(
                "show"
            );


            drawer.setAttribute(
                "aria-hidden",
                "false"
            );


            overlay.setAttribute(
                "aria-hidden",
                "false"
            );


            document.body.style.overflow =
                "hidden";

        }


        function closeDrawer() {

            drawerOpen =
                false;


            drawer.classList.remove(
                "open"
            );


            overlay.classList.remove(
                "show"
            );


            drawer.setAttribute(
                "aria-hidden",
                "true"
            );


            overlay.setAttribute(
                "aria-hidden",
                "true"
            );


            document.body.style.overflow =
                "";

        }


        /*
        |--------------------------------------------------------------------------
        | Navbar Count
        |--------------------------------------------------------------------------
        */

        function updateCartCount(
            count
        ) {

            const value =
                Number.isFinite(
                    Number(count)
                )
                    ? Number(count)
                    : 0;


            document
                .querySelectorAll(
                    "[data-cart-count]"
                )
                .forEach(
                    function (
                        badge
                    ) {

                        badge.textContent =
                            String(value);


                        badge.classList.toggle(
                            "d-none",
                            value <= 0
                        );

                    }
                );


            document
                .querySelectorAll(
                    "[data-cart-button]"
                )
                .forEach(
                    function (
                        button
                    ) {

                        button.setAttribute(
                            "aria-label",
                            "Shopping basket with " +
                            value +
                            " item" +
                            (value === 1
                                ? ""
                                : "s")
                        );

                    }
                );


            if (drawerCount) {

                drawerCount.textContent =
                    "(" +
                    value +
                    ")";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Full Cart Page Sync
        |--------------------------------------------------------------------------
        */

        function renderCartPageEmpty() {

            const layout =
                document.querySelector(
                    "[data-cart-page-layout]"
                );


            if (!layout) {
                return;
            }


            const wrapper =
                layout.parentElement;


            if (!wrapper) {
                return;
            }


            layout.remove();


            const empty =
                document.createElement(
                    "div"
                );


            empty.className =
                "auth-card empty-cart";


            empty.setAttribute(
                "data-cart-page-empty",
                ""
            );


            empty.innerHTML = `
                <span class="empty-cart-icon">
                    <i class="bi bi-basket"></i>
                </span>

                <h2>Your basket is empty.</h2>

                <p>
                    Add something fresh from the Green Harvest shop.
                </p>

                <a
                    href="${shopUrl}"
                    class="btn btn-green"
                >
                    Continue Shopping
                </a>
            `;


            wrapper.appendChild(
                empty
            );

        }


        function syncFullCartPage(
            data
        ) {

            const page =
                document.querySelector(
                    "[data-cart-page]"
                );


            if (!page) {
                return;
            }


            const itemMap =
                new Map();


            if (
                Array.isArray(
                    data.items
                )
            ) {

                data.items.forEach(
                    function (
                        item
                    ) {

                        itemMap.set(
                            String(
                                item.product_id
                            ),
                            item
                        );

                    }
                );

            }


            document
                .querySelectorAll(
                    "[data-cart-row]"
                )
                .forEach(
                    function (
                        row
                    ) {

                        const productId =
                            String(
                                row.dataset.productId
                                || ""
                            );


                        const item =
                            itemMap.get(
                                productId
                            );


                        if (!item) {

                            row.remove();

                            return;

                        }


                        const quantityInput =
                            row.querySelector(
                                "[data-cart-quantity-input]"
                            );


                        if (quantityInput) {

                            quantityInput.value =
                                String(
                                    item.quantity
                                );


                            quantityInput.max =
                                String(
                                    Math.max(
                                        1,
                                        Number(
                                            item.stock_quantity
                                        ) || 1
                                    )
                                );

                        }


                        const lineTotal =
                            row.querySelector(
                                "[data-cart-line-total]"
                            );


                        if (lineTotal) {

                            lineTotal.textContent =
                                item.subtotal_formatted;

                        }

                    }
                );


            const countLabel =
                page.querySelector(
                    "[data-cart-page-count]"
                );


            if (countLabel) {

                countLabel.textContent =
                    data.count +
                    " item" +
                    (
                        Number(data.count) === 1
                            ? ""
                            : "s"
                    );

            }


            const subtotal =
                page.querySelector(
                    "[data-cart-page-subtotal]"
                );


            const delivery =
                page.querySelector(
                    "[data-cart-page-delivery]"
                );


            const total =
                page.querySelector(
                    "[data-cart-page-total]"
                );


            if (subtotal) {

                subtotal.textContent =
                    data.subtotal_formatted;

            }


            if (delivery) {

                delivery.textContent =
                    data.delivery_formatted;

            }


            if (total) {

                total.textContent =
                    data.total_formatted;

            }


            const warning =
                page.querySelector(
                    "[data-cart-page-warning]"
                );


            if (warning) {

                warning.classList.toggle(
                    "d-none",
                    !data.has_unavailable_items
                );

            }


            const checkout =
                page.querySelector(
                    "[data-cart-page-checkout]"
                );


            if (checkout) {

                if (
                    data.has_unavailable_items
                    || Number(data.count) <= 0
                ) {

                    checkout.classList.add(
                        "disabled"
                    );


                    checkout.setAttribute(
                        "aria-disabled",
                        "true"
                    );


                    checkout.addEventListener(
                        "click",
                        preventDisabledCheckout
                    );

                } else {

                    checkout.classList.remove(
                        "disabled"
                    );


                    checkout.removeAttribute(
                        "aria-disabled"
                    );


                    checkout.removeEventListener(
                        "click",
                        preventDisabledCheckout
                    );

                }

            }


            if (
                Number(data.count) <= 0
            ) {

                renderCartPageEmpty();

            }

        }


        function preventDisabledCheckout(
            event
        ) {

            event.preventDefault();

        }


        /*
        |--------------------------------------------------------------------------
        | Render Drawer State
        |--------------------------------------------------------------------------
        */

        function renderDrawer(
            data
        ) {

            if (drawerBody) {

                drawerBody.innerHTML =
                    data.html
                    || "";

            }


            updateCartCount(
                data.count
            );


            if (drawerSubtotal) {

                drawerSubtotal.textContent =
                    data.subtotal_formatted
                    || "GH₵ 0.00";

            }


            if (drawerDelivery) {

                drawerDelivery.textContent =
                    data.delivery_formatted
                    || "GH₵ 0.00";

            }


            if (drawerTotal) {

                drawerTotal.textContent =
                    data.total_formatted
                    || "GH₵ 0.00";

            }


            if (checkoutLink) {

                checkoutLink.href =
                    data.checkout_url
                    || checkoutLink.href;


                const disabled =
                    Number(data.count) <= 0
                    || data.has_unavailable_items
                    || data.logged_in === false;


                checkoutLink.classList.toggle(
                    "disabled",
                    disabled
                );


                checkoutLink.setAttribute(
                    "aria-disabled",
                    disabled
                        ? "true"
                        : "false"
                );


                if (disabled) {

                    checkoutLink.setAttribute(
                        "tabindex",
                        "-1"
                    );

                } else {

                    checkoutLink.removeAttribute(
                        "tabindex"
                    );

                }

            }


            if (
                justAddedProductId !== null
            ) {

                const item =
                    drawer.querySelector(
                        '[data-product-id="' +
                        justAddedProductId +
                        '"]'
                    );


                if (item) {

                    item.classList.add(
                        "just-added"
                    );


                    window.setTimeout(
                        function () {

                            item.classList.remove(
                                "just-added"
                            );

                        },
                        1800
                    );

                }


                justAddedProductId =
                    null;

            }


            syncFullCartPage(
                data
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Refresh Cart
        |--------------------------------------------------------------------------
        */

        async function refreshCart(
            options = {}
        ) {

            if (
                options.open === true
            ) {

                openDrawer();

            }


            if (
                options.loading !== false
            ) {

                setDrawerLoading();

            }


            if (
                activeRequest
            ) {

                activeRequest.abort();

            }


            activeRequest =
                new AbortController();


            try {

                const separator =
                    cartUrl.includes("?")
                        ? "&"
                        : "?";


                const response =
                    await fetch(
                        cartUrl +
                        separator +
                        "drawer=1&_=" +
                        Date.now(),
                        {
                            method:
                                "GET",

                            headers: {
                                "Accept":
                                    "application/json"
                            },

                            cache:
                                "no-store",

                            signal:
                                activeRequest.signal
                        }
                    );


                const data =
                    await parseJson(
                        response
                    );


                if (
                    !response.ok
                    || data.success !== true
                ) {

                    throw new Error(
                        data.message
                        || "We could not load your basket."
                    );

                }


                renderDrawer(
                    data
                );


                return data;


            } catch (error) {

                if (
                    error.name ===
                    "AbortError"
                ) {

                    return null;

                }


                if (drawerBody) {

                    drawerBody.innerHTML = `
                        <div class="gh-mini-cart-empty">
                            <span class="gh-mini-cart-empty-icon">
                                <i class="bi bi-exclamation-circle"></i>
                            </span>

                            <h3>Basket unavailable</h3>

                            <p>
                                ${escapeHtml(
                                    error.message
                                    || "Please try again."
                                )}
                            </p>
                        </div>
                    `;

                }


                showNotice(
                    error.message
                    || "We could not load your basket.",
                    "error"
                );


                return null;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Escape HTML
        |--------------------------------------------------------------------------
        */

        function escapeHtml(
            value
        ) {

            const div =
                document.createElement(
                    "div"
                );


            div.textContent =
                String(
                    value
                );


            return div.innerHTML;

        }


        /*
        |--------------------------------------------------------------------------
        | AJAX Form Submit
        |--------------------------------------------------------------------------
        */

        async function submitCartForm(
            form,
            options = {}
        ) {

            const submitButton =
                form.querySelector(
                    '[type="submit"]'
                );


            const originalHtml =
                submitButton
                    ? submitButton.innerHTML
                    : "";


            if (submitButton) {

                submitButton.disabled =
                    true;


                submitButton.setAttribute(
                    "aria-busy",
                    "true"
                );


                submitButton.innerHTML = `
                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                    ></span>
                `;

            }


            const formData =
                new FormData(
                    form
                );


            formData.set(
                "ajax",
                "1"
            );


            try {

                const response =
                    await fetch(
                        form.action,
                        {
                            method:
                                "POST",

                            body:
                                formData,

                            headers: {
                                "Accept":
                                    "application/json",

                                "X-Requested-With":
                                    "XMLHttpRequest"
                            }
                        }
                    );


                const data =
                    await parseJson(
                        response
                    );


                if (
                    data.requires_login
                    && data.login_url
                ) {

                    window.location.href =
                        data.login_url;

                    return;

                }


                if (
                    !response.ok
                    || data.success !== true
                ) {

                    throw new Error(
                        data.message
                        || "The basket could not be updated."
                    );

                }


                if (
                    options.justAdded === true
                ) {

                    justAddedProductId =
                        String(
                            data.product_id
                        );

                }


                await refreshCart({
                    open:
                        options.openDrawer === true
                        || drawerOpen,

                    loading:
                        options.openDrawer === true
                        || drawerOpen
                });


                showNotice(
                    data.message
                    || "Basket updated."
                );


            } catch (error) {

                showNotice(
                    error.message
                    || "The basket could not be updated.",
                    "error"
                );


                if (
                    options.openDrawer === true
                ) {

                    openDrawer();

                }


            } finally {

                if (
                    submitButton
                ) {

                    submitButton.disabled =
                        false;


                    submitButton.removeAttribute(
                        "aria-busy"
                    );


                    submitButton.innerHTML =
                        originalHtml;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Open Basket Buttons
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "click",
            function (
                event
            ) {

                const openButton =
                    event.target.closest(
                        "[data-cart-open]"
                    );


                if (openButton) {

                    event.preventDefault();


                    refreshCart({
                        open:
                            true
                    });


                    return;

                }


                const closeButton =
                    event.target.closest(
                        "[data-cart-close]"
                    );


                if (closeButton) {

                    event.preventDefault();

                    closeDrawer();

                    return;

                }


                const stepButton =
                    event.target.closest(
                        "[data-cart-step]"
                    );


                if (stepButton) {

                    event.preventDefault();


                    const form =
                        stepButton.closest(
                            "[data-cart-update-form]"
                        );


                    if (!form) {
                        return;
                    }


                    const input =
                        form.querySelector(
                            "[data-cart-quantity-input]"
                        );


                    if (!input) {
                        return;
                    }


                    const direction =
                        Number(
                            stepButton.dataset.cartStep
                            || 0
                        );


                    const current =
                        Number(
                            input.value
                            || 0
                        );


                    const min =
                        Number(
                            input.min
                            || 0
                        );


                    const max =
                        Number(
                            input.max
                            || 999
                        );


                    let next =
                        current
                        +
                        direction;


                    next =
                        Math.max(
                            min,
                            Math.min(
                                max,
                                next
                            )
                        );


                    input.value =
                        String(
                            next
                        );


                    form.requestSubmit();

                    return;

                }

            }
        );


        overlay.addEventListener(
            "click",
            closeDrawer
        );


        document.addEventListener(
            "keydown",
            function (
                event
            ) {

                if (
                    event.key === "Escape"
                    && drawerOpen
                ) {

                    closeDrawer();

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Submit Delegation
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "submit",
            function (
                event
            ) {

                const form =
                    event.target;


                if (
                    !(form instanceof HTMLFormElement)
                ) {

                    return;

                }


                if (
                    form.matches(
                        "[data-cart-add-form]"
                    )
                    || form.action.includes(
                        "add-to-cart.php"
                    )
                ) {

                    event.preventDefault();


                    submitCartForm(
                        form,
                        {
                            openDrawer:
                                true,

                            justAdded:
                                true
                        }
                    );


                    return;

                }


                if (
                    form.matches(
                        "[data-cart-update-form]"
                    )
                    || form.action.includes(
                        "update-cart.php"
                    )
                ) {

                    event.preventDefault();


                    submitCartForm(
                        form,
                        {
                            openDrawer:
                                form.closest(
                                    "[data-cart-drawer]"
                                ) !== null
                        }
                    );


                    return;

                }


                if (
                    form.matches(
                        "[data-cart-remove-form]"
                    )
                    || form.action.includes(
                        "remove-from-cart.php"
                    )
                ) {

                    const confirmMessage =
                        form.dataset.confirm
                        || form
                            .querySelector(
                                "[data-confirm]"
                            )
                            ?.dataset
                            .confirm;


                    if (
                        confirmMessage
                        && !window.confirm(
                            confirmMessage
                        )
                    ) {

                        event.preventDefault();

                        return;

                    }


                    event.preventDefault();


                    submitCartForm(
                        form,
                        {
                            openDrawer:
                                form.closest(
                                    "[data-cart-drawer]"
                                ) !== null
                        }
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Quantity Input Change
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "change",
            function (
                event
            ) {

                const input =
                    event.target.closest(
                        "[data-cart-quantity-input]"
                    );


                if (!input) {
                    return;
                }


                const form =
                    input.closest(
                        "[data-cart-update-form]"
                    );


                if (!form) {
                    return;
                }


                const value =
                    Number(
                        input.value
                    );


                const min =
                    Number(
                        input.min
                        || 0
                    );


                const max =
                    Number(
                        input.max
                        || 999
                    );


                if (
                    Number.isNaN(value)
                ) {

                    return;

                }


                input.value =
                    String(
                        Math.max(
                            min,
                            Math.min(
                                max,
                                value
                            )
                        )
                    );


                form.requestSubmit();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Disabled Checkout
        |--------------------------------------------------------------------------
        */

        if (checkoutLink) {

            checkoutLink.addEventListener(
                "click",
                function (
                    event
                ) {

                    if (
                        checkoutLink.classList.contains(
                            "disabled"
                        )
                    ) {

                        event.preventDefault();

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Initial Count/Data Sync
        |--------------------------------------------------------------------------
        |
        | The navbar starts with a server-rendered count.
        | We silently refresh on the full cart page so totals and
        | controls stay synchronized without opening the drawer.
        |--------------------------------------------------------------------------
        */

        if (
            document.querySelector(
                "[data-cart-page]"
            )
        ) {

            refreshCart({
                open:
                    false,

                loading:
                    false
            });

        }

    }
);
