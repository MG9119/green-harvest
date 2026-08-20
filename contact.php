<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - CONTACT PAGE
 * =========================================================
 */

require_once __DIR__ . '/includes/bootstrap.php';


/*
|--------------------------------------------------------------------------
| Contact Information
|--------------------------------------------------------------------------
*/

$contactEmail =
    getenv('CONTACT_EMAIL')
    ?: 'hello@greenharvest.com';


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$name = '';

$email = '';

$phone = '';

$subject = '';

$message = '';

$formErrors = [];


/*
|--------------------------------------------------------------------------
| Prefill Logged-in Customer
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    try {

        $currentCustomer =
            currentUser($pdo);


        if ($currentCustomer) {

            $name =
                trim(
                    (string) (
                        $currentCustomer['full_name']
                        ?? ''
                    )
                );


            $email =
                trim(
                    (string) (
                        $currentCustomer['email']
                        ?? ''
                    )
                );


            $phone =
                trim(
                    (string) (
                        $currentCustomer['phone']
                        ?? ''
                    )
                );

        }


    } catch (PDOException $e) {

        error_log(
            'Green Harvest contact customer loading error: ' .
            $e->getMessage()
        );

    }

}


/*
|--------------------------------------------------------------------------
| Contact Subject Options
|--------------------------------------------------------------------------
*/

$subjectOptions = [

    'Product Enquiry',

    'Order Support',

    'Delivery Enquiry',

    'Account Support',

    'Farmer Partnership',

    'Business Enquiry',

    'General Enquiry',

];


/*
|--------------------------------------------------------------------------
| Handle Contact Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


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


    $email =
        strtolower(
            trim(
                (string) (
                    $_POST['email']
                    ?? ''
                )
            )
        );


    $phone =
        trim(
            (string) (
                $_POST['phone']
                ?? ''
            )
        );


    $subject =
        trim(
            (string) (
                $_POST['subject']
                ?? ''
            )
        );


    $message =
        trim(
            (string) (
                $_POST['message']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        $formErrors[] =
            'Invalid contact request. Please refresh the page and try again.';

    }


    /*
    |--------------------------------------------------------------------------
    | Name Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $formErrors[] =
            'Your name is required.';

    } elseif (
        strlen($name) < 2
    ) {

        $formErrors[] =
            'Please enter a valid name.';

    } elseif (
        strlen($name) > 120
    ) {

        $formErrors[] =
            'Your name cannot exceed 120 characters.';

    }


    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $formErrors[] =
            'Your email address is required.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $formErrors[] =
            'Please enter a valid email address.';

    } elseif (
        strlen($email) > 150
    ) {

        $formErrors[] =
            'Your email address is too long.';

    }


    /*
    |--------------------------------------------------------------------------
    | Phone Validation
    |--------------------------------------------------------------------------
    */

    if ($phone !== '') {

        if (
            strlen($phone) < 7
            ||
            strlen($phone) > 30
        ) {

            $formErrors[] =
                'Please enter a valid phone number.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Subject Validation
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $subject,
            $subjectOptions,
            true
        )
    ) {

        $formErrors[] =
            'Please select a valid enquiry type.';

    }


    /*
    |--------------------------------------------------------------------------
    | Message Validation
    |--------------------------------------------------------------------------
    */

    if ($message === '') {

        $formErrors[] =
            'Please enter your message.';

    } elseif (
        strlen($message) < 10
    ) {

        $formErrors[] =
            'Your message should contain at least 10 characters.';

    } elseif (
        strlen($message) > 5000
    ) {

        $formErrors[] =
            'Your message is too long.';

    }


    /*
    |--------------------------------------------------------------------------
    | Save Contact Message
    |--------------------------------------------------------------------------
    */

    if (!$formErrors) {

        try {

            $stmt =
                $pdo->prepare(
                    '
                    INSERT INTO contact_messages
                    (
                        name,
                        email,
                        phone,
                        subject,
                        message
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                    '
                );


            $stmt->execute([

                $name,

                $email,

                $phone !== ''
                    ? $phone
                    : null,

                $subject,

                $message,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Admin Email Notification
            |--------------------------------------------------------------------------
            */

            try {

                $adminNotify =
                    getenv('CONTACT_EMAIL')
                    ?: 'hello@greenharvest.com';


                if ($adminNotify) {

                    $mailSubject =
                        '[Green Harvest] New contact: ' .
                        (
                            $subject
                            ?: 'No subject'
                        );


                    $mailBody =
                        "You have received a new message from Green Harvest contact form:\n\n" .

                        "Name: " .
                        $name .
                        "\n" .

                        "Email: " .
                        $email .
                        "\n" .

                        (
                            $phone !== ''
                                ? (
                                    "Phone: " .
                                    $phone .
                                    "\n"
                                )
                                : ''
                        ) .

                        "Subject: " .
                        $subject .
                        "\n\n" .

                        "Message:\n" .
                        $message .
                        "\n\n" .

                        "View in admin: " .
                        url(
                            'admin/admin-feedback.php'
                        ) .
                        "\n";


                    $headers =
                        "From: " .
                        e($name) .
                        " <" .
                        $email .
                        ">\r\n" .

                        "Reply-To: " .
                        $email .
                        "\r\n" .

                        "X-Mailer: PHP/" .
                        phpversion();


                    @mail(
                        $adminNotify,
                        $mailSubject,
                        $mailBody,
                        $headers
                    );

                }


            } catch (
                Throwable $mailEx
            ) {

                error_log(
                    'Contact notification email error: ' .
                    $mailEx->getMessage()
                );

            }


            setFlash(
                'success',
                'Thank you for contacting Green Harvest. Your message has been received successfully.'
            );


            redirectTo(
                'contact.php'
            );


        } catch (
            PDOException $e
        ) {

            error_log(
                'Green Harvest contact message database error: ' .
                $e->getMessage()
            );


            $formErrors[] =
                'Your message could not be submitted right now. Please try again.';

        }

    }

}


/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Contact Us';


require_once __DIR__ . '/includes/header.php';

?>


<style>

/* =========================================================
   GREEN HARVEST CONTACT
========================================================= */

.gh-contact-page {

    --contact-dark:
        #092516;

    --contact-dark-2:
        #14532d;

    --contact-green:
        #15803d;

    --contact-green-light:
        #22c55e;

    --contact-soft:
        #f0fdf4;

    --contact-bg:
        #f5f9f6;

    --contact-white:
        #ffffff;

    --contact-ink:
        #102519;

    --contact-text:
        #33463a;

    --contact-muted:
        #718078;

    --contact-border:
        rgba(
            20,
            83,
            45,
            .11
        );

}


/* =========================================================
   PAGE INTRO
========================================================= */

.gh-contact-intro {

    padding:
        65px
        20px
        52px;

    background:
        #ffffff;

}


.gh-contact-intro-inner {

    max-width:
        790px;

}


.gh-contact-eyebrow {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        13px;

    color:
        var(--contact-green);

    font-size:
        .66rem;

    font-weight:
        800;

    letter-spacing:
        .13em;

    text-transform:
        uppercase;

}


.gh-contact-eyebrow::before {

    content:
        "";

    width:
        25px;

    height:
        2px;

    border-radius:
        999px;

    background:
        var(--contact-green);

}


.gh-contact-intro h1 {

    margin:
        0
        0
        13px;

    color:
        var(--contact-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            3rem,
            7vw,
            5rem
        );

    font-weight:
        800;

    line-height:
        .96;

    letter-spacing:
        -.06em;

}


.gh-contact-intro p {

    max-width:
        610px;

    margin:
        0;

    color:
        var(--contact-muted);

    font-size:
        .91rem;

    line-height:
        1.75;

}


/* =========================================================
   MAIN SECTION
========================================================= */

.gh-contact-main {

    padding:
        28px
        20px
        90px;

    background:
        var(--contact-bg);

}


.gh-contact-layout {

    display:
        grid;

    grid-template-columns:
        minmax(
            0,
            .88fr
        )
        minmax(
            420px,
            1.12fr
        );

    gap:
        27px;

    align-items:
        stretch;

}


/* =========================================================
   INFORMATION PANEL
========================================================= */

.gh-contact-info {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        100%;

    padding:
        42px;

    border-radius:
        24px;

    background:
        linear-gradient(
            145deg,
            #071d10 0%,
            #092516 52%,
            #14532d 100%
        );

    color:
        #ffffff;

}


.gh-contact-info::before {

    content:
        "";

    position:
        absolute;

    width:
        310px;

    height:
        310px;

    right:
        -160px;

    top:
        -170px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .08
        );

    border-radius:
        50%;

}


.gh-contact-info::after {

    content:
        "";

    position:
        absolute;

    width:
        180px;

    height:
        180px;

    left:
        -90px;

    bottom:
        -105px;

    border-radius:
        50%;

    background:
        rgba(
            134,
            239,
            172,
            .04
        );

}


.gh-contact-info >
* {

    position:
        relative;

    z-index:
        2;

}


/* =========================================================
   INFO HEADING
========================================================= */

.gh-contact-info-label {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    margin-bottom:
        13px;

    color:
        #9cebb0;

    font-size:
        .63rem;

    font-weight:
        800;

    letter-spacing:
        .12em;

    text-transform:
        uppercase;

}


.gh-contact-info-label::before {

    content:
        "";

    width:
        23px;

    height:
        2px;

    border-radius:
        99px;

    background:
        #86efac;

}


.gh-contact-info h2 {

    max-width:
        470px;

    margin:
        0
        0
        15px;

    color:
        #ffffff;

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            2.2rem,
            4vw,
            3.3rem
        );

    font-weight:
        800;

    line-height:
        1;

    letter-spacing:
        -.045em;

}


.gh-contact-info-description {

    max-width:
        480px;

    margin:
        0
        0
        34px;

    color:
        rgba(
            255,
            255,
            255,
            .62
        );

    font-size:
        .83rem;

    line-height:
        1.75;

}


/* =========================================================
   CONTACT DETAILS
========================================================= */

.gh-contact-details {

    display:
        grid;

    gap:
        13px;

}


.gh-contact-detail {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    padding:
        13px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .08
        );

    border-radius:
        14px;

    background:
        rgba(
            255,
            255,
            255,
            .035
        );

}


.gh-contact-detail-icon {

    width:
        40px;

    height:
        40px;

    flex-shrink:
        0;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        11px;

    background:
        rgba(
            134,
            239,
            172,
            .10
        );

    color:
        #86efac;

    font-size:
        .95rem;

}


.gh-contact-detail-label {

    display:
        block;

    margin-bottom:
        2px;

    color:
        rgba(
            255,
            255,
            255,
            .43
        );

    font-size:
        .55rem;

    font-weight:
        800;

    letter-spacing:
        .09em;

    text-transform:
        uppercase;

}


.gh-contact-detail-value {

    display:
        block;

    color:
        #ffffff;

    font-size:
        .72rem;

    font-weight:
        700;

    line-height:
        1.4;

}


.gh-contact-detail-value a {

    color:
        #ffffff;

    text-decoration:
        none;

}


.gh-contact-detail-value a:hover {

    color:
        #86efac;

}


/* =========================================================
   HELP LIST
========================================================= */

.gh-contact-help {

    margin-top:
        31px;

    padding-top:
        27px;

    border-top:
        1px solid
        rgba(
            255,
            255,
            255,
            .08
        );

}


.gh-contact-help h3 {

    margin:
        0
        0
        14px;

    color:
        #ffffff;

    font-size:
        .82rem;

    font-weight:
        800;

}


.gh-contact-help-list {

    display:
        grid;

    gap:
        9px;

}


.gh-contact-help-item {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        rgba(
            255,
            255,
            255,
            .60
        );

    font-size:
        .69rem;

}


.gh-contact-help-item i {

    color:
        #86efac;

    font-size:
        .74rem;

}


/* =========================================================
   RIGHT FORM BACKGROUND
========================================================= */

.gh-contact-form-panel {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        34px;

    border-radius:
        24px;

    background:
        linear-gradient(
            180deg,
            #f7faf7 0%,
            #edf7ef 100%
        );

}


/* =========================================================
   FORM CARD
========================================================= */

.gh-contact-card {

    position:
        relative;

    width:
        100%;

    max-width:
        610px;

    overflow:
        hidden;

    padding:
        35px
        34px
        31px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            .9
        );

    border-radius:
        18px;

    background:
        rgba(
            255,
            255,
            255,
            .98
        );

    box-shadow:
        0 22px 55px
        rgba(
            9,
            37,
            22,
            .09
        );

}


.gh-contact-card::before {

    content:
        "";

    position:
        absolute;

    top:
        0;

    left:
        0;

    right:
        0;

    height:
        4px;

    background:
        linear-gradient(
            90deg,
            #166534,
            #22c55e,
            #4ade80
        );

}


/* =========================================================
   FORM HEADER
========================================================= */

.gh-contact-card-header {

    margin-bottom:
        25px;

    text-align:
        center;

}


.gh-contact-card-icon {

    width:
        54px;

    height:
        54px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        14px;

    border:
        1px solid
        rgba(
            20,
            83,
            45,
            .08
        );

    border-radius:
        16px;

    background:
        #f0fdf4;

    color:
        #15803d;

    font-size:
        1.18rem;

}


.gh-contact-card-badge {

    width:
        fit-content;

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

    margin:
        0 auto
        9px;

    padding:
        5px
        9px;

    border-radius:
        999px;

    background:
        #f0fdf4;

    color:
        #166534;

    font-size:
        .57rem;

    font-weight:
        800;

    letter-spacing:
        .08em;

    text-transform:
        uppercase;

}


.gh-contact-card-badge-dot {

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        #22c55e;

}


.gh-contact-card h2 {

    margin:
        0
        0
        6px;

    color:
        var(--contact-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        1.6rem;

    font-weight:
        800;

    line-height:
        1.15;

    letter-spacing:
        -.035em;

}


.gh-contact-card-description {

    max-width:
        430px;

    margin:
        0 auto;

    color:
        var(--contact-muted);

    font-size:
        .72rem;

    line-height:
        1.55;

}


/* =========================================================
   ERRORS
========================================================= */

.gh-contact-errors {

    margin-bottom:
        18px;

    padding:
        12px
        14px;

    border:
        1px solid
        #fecaca;

    border-radius:
        11px;

    background:
        #fef2f2;

    color:
        #b91c1c;

    font-size:
        .69rem;

}


.gh-contact-errors strong {

    display:
        block;

    margin-bottom:
        5px;

}


.gh-contact-errors ul {

    margin:
        0;

    padding-left:
        18px;

}


/* =========================================================
   FORM GRID
========================================================= */

.gh-contact-form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px;

}


.gh-contact-field-full {

    grid-column:
        1 / -1;

}


/* =========================================================
   LABEL
========================================================= */

.gh-contact-label {

    display:
        block;

    margin-bottom:
        6px;

    color:
        #263a2d;

    font-size:
        .67rem;

    font-weight:
        700;

}


.gh-contact-optional {

    color:
        #909b94;

    font-size:
        .59rem;

    font-weight:
        500;

}


/* =========================================================
   INPUTS
========================================================= */

.gh-contact-input,
.gh-contact-select,
.gh-contact-textarea {

    width:
        100%;

    border:
        1.5px solid
        transparent;

    outline:
        none;

    background:
        #f4f6f4;

    color:
        #17271d;

    font-family:
        'Inter',
        sans-serif;

    font-size:
        .77rem;

    font-weight:
        500;

    transition:
        border-color .2s ease,
        background-color .2s ease,
        box-shadow .2s ease;

}


.gh-contact-input,
.gh-contact-select {

    height:
        45px;

    padding:
        0
        13px;

    border-radius:
        9px;

}


.gh-contact-select {

    cursor:
        pointer;

}


.gh-contact-textarea {

    min-height:
        125px;

    padding:
        13px;

    border-radius:
        10px;

    resize:
        vertical;

    line-height:
        1.6;

}


.gh-contact-input::placeholder,
.gh-contact-textarea::placeholder {

    color:
        #a1aaa4;

}


.gh-contact-input:hover,
.gh-contact-select:hover,
.gh-contact-textarea:hover {

    background:
        #f0f4f1;

}


.gh-contact-input:focus,
.gh-contact-select:focus,
.gh-contact-textarea:focus {

    border-color:
        #22a550;

    background:
        #ffffff;

    box-shadow:
        0 0 0 3px
        rgba(
            34,
            165,
            80,
            .10
        );

}


/* =========================================================
   SEND BUTTON
========================================================= */

.gh-contact-submit {

    width:
        100%;

    min-height:
        46px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    margin-top:
        20px;

    padding:
        10px
        16px;

    border:
        0;

    border-radius:
        999px;

    background:
        linear-gradient(
            135deg,
            #15803d,
            #14532d
        );

    color:
        #ffffff;

    font-family:
        inherit;

    font-size:
        .78rem;

    font-weight:
        800;

    cursor:
        pointer;

    box-shadow:
        0 10px 22px
        rgba(
            20,
            83,
            45,
            .17
        );

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.gh-contact-submit:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 14px 28px
        rgba(
            20,
            83,
            45,
            .22
        );

}


/* =========================================================
   PRIVACY NOTICE
========================================================= */

.gh-contact-notice {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        8px;

    margin-top:
        15px;

    padding:
        10px
        11px;

    border-radius:
        9px;

    background:
        #f0fdf4;

    color:
        #4d6855;

    font-size:
        .61rem;

    line-height:
        1.5;

}


.gh-contact-notice i {

    flex-shrink:
        0;

    margin-top:
        1px;

    color:
        #15803d;

}


/* =========================================================
   QUICK LINKS
========================================================= */

.gh-contact-quick {

    padding:
        78px
        20px;

    background:
        #ffffff;

}


.gh-contact-quick-header {

    max-width:
        640px;

    margin-bottom:
        30px;

}


.gh-contact-quick-header h2 {

    margin:
        0
        0
        8px;

    color:
        var(--contact-ink);

    font-family:
        'Manrope',
        'Inter',
        sans-serif;

    font-size:
        clamp(
            2rem,
            4vw,
            3rem
        );

    font-weight:
        800;

    letter-spacing:
        -.04em;

}


.gh-contact-quick-header p {

    margin:
        0;

    color:
        var(--contact-muted);

    font-size:
        .8rem;

    line-height:
        1.7;

}


.gh-contact-quick-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap:
        14px;

}


.gh-contact-quick-card {

    padding:
        23px;

    border:
        1px solid
        var(--contact-border);

    border-radius:
        17px;

    background:
        #ffffff;

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


.gh-contact-quick-card:hover {

    transform:
        translateY(-4px);

    border-color:
        rgba(
            21,
            128,
            61,
            .20
        );

    box-shadow:
        0 15px 34px
        rgba(
            9,
            37,
            22,
            .06
        );

}


.gh-contact-quick-icon {

    width:
        43px;

    height:
        43px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        14px;

    border-radius:
        13px;

    background:
        var(--contact-soft);

    color:
        var(--contact-green);

    font-size:
        1rem;

}


.gh-contact-quick-card h3 {

    margin:
        0
        0
        6px;

    color:
        var(--contact-ink);

    font-size:
        .94rem;

    font-weight:
        800;

}


.gh-contact-quick-card p {

    margin:
        0
        0
        14px;

    color:
        var(--contact-muted);

    font-size:
        .71rem;

    line-height:
        1.65;

}


.gh-contact-quick-link {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--contact-green);

    font-size:
        .67rem;

    font-weight:
        800;

    text-decoration:
        none;

}


.gh-contact-quick-link:hover {

    color:
        var(--contact-dark-2);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 991.98px
) {

    .gh-contact-layout {

        grid-template-columns:
            1fr;

    }


    .gh-contact-info {

        min-height:
            auto;

    }


    .gh-contact-form-panel {

        padding:
            32px;

    }

}


@media (
    max-width: 767.98px
) {

    .gh-contact-intro {

        padding:
            54px
            18px
            38px;

    }


    .gh-contact-main {

        padding:
            18px
            18px
            65px;

    }


    .gh-contact-quick {

        padding:
            65px
            18px;

    }


    .gh-contact-quick-grid {

        grid-template-columns:
            1fr;

    }

}


@media (
    max-width: 575.98px
) {

    .gh-contact-intro h1 {

        font-size:
            3rem;

    }


    .gh-contact-info {

        padding:
            29px
            23px;

        border-radius:
            19px;

    }


    .gh-contact-form-panel {

        padding:
            18px;

        border-radius:
            19px;

    }


    .gh-contact-card {

        padding:
            30px
            21px
            25px;

        border-radius:
            15px;

    }


    .gh-contact-form-grid {

        grid-template-columns:
            1fr;

    }


    .gh-contact-field-full {

        grid-column:
            auto;

    }


    .gh-contact-input,
    .gh-contact-select {

        height:
            44px;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (
    prefers-reduced-motion:
    reduce
) {

    .gh-contact-submit,
    .gh-contact-quick-card {

        transition:
            none;

    }

}

</style>



<div class="gh-contact-page">


<!-- =========================================================
     INTRO
========================================================= -->

<section class="gh-contact-intro">


    <div class="container">


        <div class="gh-contact-intro-inner">


            <p class="gh-contact-eyebrow">

                Contact Green Harvest

            </p>


            <h1>

                How can we help?

            </h1>


            <p>

                Have a question about an order,
                a product or working with Green Harvest?
                Send us a message and we'll point you
                in the right direction.

            </p>


        </div>


    </div>


</section>



<!-- =========================================================
     MAIN CONTACT AREA
========================================================= -->

<section class="gh-contact-main">


    <div class="container">


        <?php displayFlash(); ?>



        <div class="gh-contact-layout">


            <!-- =================================================
                 LEFT INFORMATION
            ================================================== -->

            <aside class="gh-contact-info">


                <p class="gh-contact-info-label">

                    Get in Touch

                </p>


                <h2>

                    We're here
                    when you need us.

                </h2>


                <p class="gh-contact-info-description">

                    Contact Green Harvest for help with
                    products, orders, deliveries,
                    customer accounts or partnership
                    opportunities.

                </p>



                <div class="gh-contact-details">


                    <!-- LOCATION -->

                    <div class="gh-contact-detail">


                        <span class="gh-contact-detail-icon">

                            <i class="bi bi-geo-alt-fill"></i>

                        </span>


                        <div>


                            <span class="gh-contact-detail-label">

                                Location

                            </span>


                            <span class="gh-contact-detail-value">

                                Accra, Ghana

                            </span>


                        </div>


                    </div>



                    <!-- EMAIL -->

                    <div class="gh-contact-detail">


                        <span class="gh-contact-detail-icon">

                            <i class="bi bi-envelope-fill"></i>

                        </span>


                        <div>


                            <span class="gh-contact-detail-label">

                                Email

                            </span>


                            <span class="gh-contact-detail-value">


                                <a
                                    href="mailto:<?= e(
                                        $contactEmail
                                    ) ?>"
                                >

                                    <?= e(
                                        $contactEmail
                                    ) ?>

                                </a>


                            </span>


                        </div>


                    </div>



                    <!-- SERVICE -->

                    <div class="gh-contact-detail">


                        <span class="gh-contact-detail-icon">

                            <i class="bi bi-truck"></i>

                        </span>


                        <div>


                            <span class="gh-contact-detail-label">

                                Service

                            </span>


                            <span class="gh-contact-detail-value">

                                Fresh food ordering
                                and delivery support

                            </span>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     HELP TOPICS
                ================================================== -->

                <div class="gh-contact-help">


                    <h3>

                        We can help with

                    </h3>


                    <div class="gh-contact-help-list">


                        <span class="gh-contact-help-item">

                            <i class="bi bi-check-circle-fill"></i>

                            Product enquiries

                        </span>


                        <span class="gh-contact-help-item">

                            <i class="bi bi-check-circle-fill"></i>

                            Order and delivery support

                        </span>


                        <span class="gh-contact-help-item">

                            <i class="bi bi-check-circle-fill"></i>

                            Account assistance

                        </span>


                        <span class="gh-contact-help-item">

                            <i class="bi bi-check-circle-fill"></i>

                            Farmer and business partnerships

                        </span>


                    </div>


                </div>


            </aside>



            <!-- =================================================
                 RIGHT FORM PANEL
            ================================================== -->

            <div class="gh-contact-form-panel">


                <div class="gh-contact-card">


                    <!-- =========================================
                         FORM HEADER
                    ========================================== -->

                    <div class="gh-contact-card-header">


                        <span class="gh-contact-card-icon">

                            <i class="bi bi-chat-dots"></i>

                        </span>


                        <div class="gh-contact-card-badge">

                            <span class="gh-contact-card-badge-dot"></span>

                            Green Harvest Support

                        </div>


                        <h2>

                            Send us a message

                        </h2>


                        <p class="gh-contact-card-description">

                            Complete the form and provide
                            enough information for our team
                            to understand your enquiry.

                        </p>


                    </div>



                    <!-- =========================================
                         ERRORS
                    ========================================== -->

                    <?php if (
                        $formErrors
                    ): ?>


                        <div class="gh-contact-errors">


                            <strong>

                                Please correct the following:

                            </strong>


                            <ul>


                                <?php foreach (
                                    $formErrors as
                                    $error
                                ): ?>


                                    <li>

                                        <?= e(
                                            $error
                                        ) ?>

                                    </li>


                                <?php endforeach; ?>


                            </ul>


                        </div>


                    <?php endif; ?>



                    <!-- =========================================
                         FORM
                    ========================================== -->

                    <form
                        method="post"
                        action="<?= e(
                            url(
                                'contact.php'
                            )
                        ) ?>"
                        autocomplete="on"
                    >


                        <?= csrfField() ?>



                        <div class="gh-contact-form-grid">


                            <!-- =====================================
                                 NAME
                            ====================================== -->

                            <div>


                                <label
                                    for="contact_name"
                                    class="gh-contact-label"
                                >

                                    Full Name

                                </label>


                                <input
                                    id="contact_name"
                                    type="text"
                                    name="name"
                                    value="<?= e(
                                        $name
                                    ) ?>"
                                    class="gh-contact-input"
                                    minlength="2"
                                    maxlength="120"
                                    autocomplete="name"
                                    placeholder="Your full name"
                                    required
                                >


                            </div>



                            <!-- =====================================
                                 EMAIL
                            ====================================== -->

                            <div>


                                <label
                                    for="contact_email"
                                    class="gh-contact-label"
                                >

                                    Email Address

                                </label>


                                <input
                                    id="contact_email"
                                    type="email"
                                    name="email"
                                    value="<?= e(
                                        $email
                                    ) ?>"
                                    class="gh-contact-input"
                                    maxlength="150"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    required
                                >


                            </div>



                            <!-- =====================================
                                 PHONE
                            ====================================== -->

                            <div>


                                <label
                                    for="contact_phone"
                                    class="gh-contact-label"
                                >

                                    Phone Number

                                    <span class="gh-contact-optional">

                                        optional

                                    </span>


                                </label>


                                <input
                                    id="contact_phone"
                                    type="tel"
                                    name="phone"
                                    value="<?= e(
                                        $phone
                                    ) ?>"
                                    class="gh-contact-input"
                                    maxlength="30"
                                    autocomplete="tel"
                                    placeholder="+233..."
                                >


                            </div>



                            <!-- =====================================
                                 SUBJECT
                            ====================================== -->

                            <div>


                                <label
                                    for="contact_subject"
                                    class="gh-contact-label"
                                >

                                    Enquiry Type

                                </label>


                                <select
                                    id="contact_subject"
                                    name="subject"
                                    class="gh-contact-select"
                                    required
                                >


                                    <option value="">

                                        Select enquiry type

                                    </option>



                                    <?php foreach (
                                        $subjectOptions as
                                        $option
                                    ): ?>


                                        <option
                                            value="<?= e(
                                                $option
                                            ) ?>"
                                            <?= $subject === $option
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e(
                                                $option
                                            ) ?>

                                        </option>


                                    <?php endforeach; ?>


                                </select>


                            </div>



                            <!-- =====================================
                                 MESSAGE
                            ====================================== -->

                            <div class="gh-contact-field-full">


                                <label
                                    for="contact_message"
                                    class="gh-contact-label"
                                >

                                    Message

                                </label>


                                <textarea
                                    id="contact_message"
                                    name="message"
                                    class="gh-contact-textarea"
                                    minlength="10"
                                    maxlength="5000"
                                    placeholder="Tell us how we can help..."
                                    required
                                ><?= e(
                                    $message
                                ) ?></textarea>


                            </div>


                        </div>



                        <!-- =========================================
                             SUBMIT
                        ========================================== -->

                        <button
                            type="submit"
                            class="gh-contact-submit"
                        >

                            <i class="bi bi-send"></i>

                            Send Message

                        </button>



                        <!-- =========================================
                             PRIVACY NOTE
                        ========================================== -->

                        <div class="gh-contact-notice">


                            <i class="bi bi-shield-check"></i>


                            <span>

                                Your contact details are used
                                only to respond to your enquiry.

                            </span>


                        </div>


                    </form>


                </div>


            </div>


        </div>


    </div>


</section>



<!-- =========================================================
     QUICK SUPPORT LINKS
========================================================= -->

<section class="gh-contact-quick">


    <div class="container">


        <div class="gh-contact-quick-header">


            <p class="gh-contact-eyebrow">

                Quick Help

            </p>


            <h2>

                You may not need
                to send a message.

            </h2>


            <p>

                Use one of these Green Harvest shortcuts
                to quickly access orders, products or
                your customer account.

            </p>


        </div>



        <div class="gh-contact-quick-grid">


            <!-- =================================================
                 ORDERS
            ================================================== -->

            <article class="gh-contact-quick-card">


                <span class="gh-contact-quick-icon">

                    <i class="bi bi-bag-check"></i>

                </span>


                <h3>

                    Check an Order

                </h3>


                <p>

                    Review your Green Harvest order
                    history and current order status.

                </p>


                <?php if (
                    isLoggedIn()
                ): ?>


                    <a
                        href="<?= e(
                            url(
                                'orders.php'
                            )
                        ) ?>"
                        class="gh-contact-quick-link"
                    >

                        My Orders

                        <i class="bi bi-arrow-right"></i>

                    </a>


                <?php else: ?>


                    <a
                        href="<?= e(
                            url(
                                'login.php?redirect=' .
                                urlencode(
                                    'orders.php'
                                )
                            )
                        ) ?>"
                        class="gh-contact-quick-link"
                    >

                        Sign In

                        <i class="bi bi-arrow-right"></i>

                    </a>


                <?php endif; ?>


            </article>



            <!-- =================================================
                 PRODUCTS
            ================================================== -->

            <article class="gh-contact-quick-card">


                <span class="gh-contact-quick-icon">

                    <i class="bi bi-basket"></i>

                </span>


                <h3>

                    Product Questions

                </h3>


                <p>

                    Browse products, pricing,
                    categories and current
                    stock availability.

                </p>


                <a
                    href="<?= e(
                        url(
                            'shop.php'
                        )
                    ) ?>"
                    class="gh-contact-quick-link"
                >

                    Visit Shop

                    <i class="bi bi-arrow-right"></i>

                </a>


            </article>



            <!-- =================================================
                 ACCOUNT
            ================================================== -->

            <article class="gh-contact-quick-card">


                <span class="gh-contact-quick-icon">

                    <i class="bi bi-person"></i>

                </span>


                <h3>

                    Account Support

                </h3>


                <p>

                    Manage your profile,
                    delivery address and
                    customer information.

                </p>


                <?php if (
                    isLoggedIn()
                ): ?>


                    <a
                        href="<?= e(
                            url(
                                'account.php'
                            )
                        ) ?>"
                        class="gh-contact-quick-link"
                    >

                        My Account

                        <i class="bi bi-arrow-right"></i>

                    </a>


                <?php else: ?>


                    <a
                        href="<?= e(
                            url(
                                'login.php'
                            )
                        ) ?>"
                        class="gh-contact-quick-link"
                    >

                        Sign In

                        <i class="bi bi-arrow-right"></i>

                    </a>


                <?php endif; ?>


            </article>


        </div>


    </div>


</section>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>