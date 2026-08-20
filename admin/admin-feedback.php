<?php

declare(strict_types=1);

/**
 * =========================================================
 * GREEN HARVEST - ADMIN FEEDBACK / INBOX
 * =========================================================
 *
 * Responsibilities:
 * - Display contact form submissions
 * - Search messages
 * - Track read / unread state
 * - Automatically mark opened messages as read
 * - Allow manual mark read / unread
 * - Bulk actions
 * - Delete messages
 * - Export messages to CSV
 * - Paginate messages
 * =========================================================
 */

require_once __DIR__ . '/../includes/admin-auth.php';


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Inbox / Feedback';


/*
|--------------------------------------------------------------------------
| Search / Pagination
|--------------------------------------------------------------------------
*/

$q =
    trim(
        (string) (
            $_GET['q']
            ?? ''
        )
    );


$page =
    filter_input(
        INPUT_GET,
        'page',
        FILTER_VALIDATE_INT
    );


if (
    $page === false
    ||
    $page === null
    ||
    $page < 1
) {

    $page = 1;

}


$perPage = 20;


/*
|--------------------------------------------------------------------------
| Detect Message Table
|--------------------------------------------------------------------------
|
| Prefer:
| contact_messages
|
| Fallback:
| inbox_entries
|--------------------------------------------------------------------------
*/

$messagesTable = null;


try {

    $stmt =
        $pdo->query(
            'SELECT 1 FROM contact_messages LIMIT 1'
        );


    if ($stmt) {

        $messagesTable =
            'contact_messages';

    }


} catch (
    PDOException $e
) {

    /*
     * Ignore.
     * Try fallback table.
     */

}


if ($messagesTable === null) {

    try {

        $stmt =
            $pdo->query(
                'SELECT 1 FROM inbox_entries LIMIT 1'
            );


        if ($stmt) {

            $messagesTable =
                'inbox_entries';

        }


    } catch (
        PDOException $e
    ) {

        $messagesTable = null;

    }

}


/*
|--------------------------------------------------------------------------
| Detect Columns
|--------------------------------------------------------------------------
*/

$columns = [];


if ($messagesTable !== null) {

    try {

        $columnStmt =
            $pdo->prepare(
                '
                SELECT COLUMN_NAME

                FROM INFORMATION_SCHEMA.COLUMNS

                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                '
            );


        $columnStmt->execute([
            $messagesTable,
        ]);


        $columns =
            array_map(
                'strtolower',
                $columnStmt->fetchAll(
                    PDO::FETCH_COLUMN
                )
                ?: []
            );


    } catch (
        Throwable $e
    ) {

        error_log(
            'Green Harvest feedback column detection error: ' .
            $e->getMessage()
        );

    }

}


/*
|--------------------------------------------------------------------------
| Column Helpers
|--------------------------------------------------------------------------
*/

$hasColumn =
    static function (
        string $column
    ) use (
        $columns
    ): bool {

        return in_array(
            strtolower(
                $column
            ),
            $columns,
            true
        );

    };


$hasName =
    $hasColumn(
        'name'
    );


$hasSenderName =
    $hasColumn(
        'sender_name'
    );


$hasEmail =
    $hasColumn(
        'email'
    );


$hasSenderEmail =
    $hasColumn(
        'sender_email'
    );


$hasPhone =
    $hasColumn(
        'phone'
    );


$hasSenderPhone =
    $hasColumn(
        'sender_phone'
    );


$hasSubject =
    $hasColumn(
        'subject'
    );


$hasMessage =
    $hasColumn(
        'message'
    );


$hasBody =
    $hasColumn(
        'body'
    );


$hasCreatedAt =
    $hasColumn(
        'created_at'
    );


$hasIsRead =
    $hasColumn(
        'is_read'
    );


$hasReadAt =
    $hasColumn(
        'read_at'
    );


$supportsReadStatus =
    $hasIsRead
    ||
    $hasReadAt;


/*
|--------------------------------------------------------------------------
| Safe Select Expressions
|--------------------------------------------------------------------------
*/

$nameExpression =
    $hasName
        ? 'name'
        : (
            $hasSenderName
                ? 'sender_name'
                : "''"
        );


$emailExpression =
    $hasEmail
        ? 'email'
        : (
            $hasSenderEmail
                ? 'sender_email'
                : "''"
        );


$phoneExpression =
    $hasPhone
        ? 'phone'
        : (
            $hasSenderPhone
                ? 'sender_phone'
                : "''"
        );


$subjectExpression =
    $hasSubject
        ? 'subject'
        : "''";


$messageExpression =
    $hasMessage
        ? 'message'
        : (
            $hasBody
                ? 'body'
                : "''"
        );


$createdAtExpression =
    $hasCreatedAt
        ? 'created_at'
        : 'NULL';


/*
|--------------------------------------------------------------------------
| Normalized Read Expression
|--------------------------------------------------------------------------
|
| If both columns exist:
| is_read = 1 OR read_at has a value means Read.
|--------------------------------------------------------------------------
*/

if (
    $hasIsRead
    &&
    $hasReadAt
) {

    $readExpression =
        '
        CASE
            WHEN COALESCE(is_read, 0) = 1
              OR read_at IS NOT NULL
            THEN 1
            ELSE 0
        END
        ';

} elseif ($hasIsRead) {

    $readExpression =
        '
        CASE
            WHEN COALESCE(is_read, 0) = 1
            THEN 1
            ELSE 0
        END
        ';

} elseif ($hasReadAt) {

    $readExpression =
        '
        CASE
            WHEN read_at IS NOT NULL
            THEN 1
            ELSE 0
        END
        ';

} else {

    $readExpression =
        '0';

}


/*
|--------------------------------------------------------------------------
| Read At Expression
|--------------------------------------------------------------------------
*/

$readAtExpression =
    $hasReadAt
        ? 'read_at'
        : 'NULL';


/*
|--------------------------------------------------------------------------
| Helper: Redirect Back
|--------------------------------------------------------------------------
*/

$feedbackRedirect =
    static function (): never {

        redirectTo(
            'admin/admin-feedback.php'
        );

    };


/*
|--------------------------------------------------------------------------
| Helper: Mark Messages Read
|--------------------------------------------------------------------------
*/

$markRead =
    static function (
        PDO $pdo,
        string $table,
        array $ids,
        bool $hasIsRead,
        bool $hasReadAt
    ): void {

        if (!$ids) {

            return;

        }


        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count(
                        $ids
                    ),
                    '?'
                )
            );


        if (
            $hasIsRead
            &&
            $hasReadAt
        ) {

            $sql =
                "
                UPDATE {$table}

                SET
                    is_read = 1,
                    read_at = NOW()

                WHERE id IN ({$placeholders})
                ";

        } elseif ($hasIsRead) {

            $sql =
                "
                UPDATE {$table}

                SET
                    is_read = 1

                WHERE id IN ({$placeholders})
                ";

        } elseif ($hasReadAt) {

            $sql =
                "
                UPDATE {$table}

                SET
                    read_at = NOW()

                WHERE id IN ({$placeholders})
                ";

        } else {

            throw new RuntimeException(
                'This messages table does not support read/unread status.'
            );

        }


        $stmt =
            $pdo->prepare(
                $sql
            );


        $stmt->execute(
            $ids
        );

    };


/*
|--------------------------------------------------------------------------
| Helper: Mark Messages Unread
|--------------------------------------------------------------------------
*/

$markUnread =
    static function (
        PDO $pdo,
        string $table,
        array $ids,
        bool $hasIsRead,
        bool $hasReadAt
    ): void {

        if (!$ids) {

            return;

        }


        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count(
                        $ids
                    ),
                    '?'
                )
            );


        if (
            $hasIsRead
            &&
            $hasReadAt
        ) {

            $sql =
                "
                UPDATE {$table}

                SET
                    is_read = 0,
                    read_at = NULL

                WHERE id IN ({$placeholders})
                ";

        } elseif ($hasIsRead) {

            $sql =
                "
                UPDATE {$table}

                SET
                    is_read = 0

                WHERE id IN ({$placeholders})
                ";

        } elseif ($hasReadAt) {

            $sql =
                "
                UPDATE {$table}

                SET
                    read_at = NULL

                WHERE id IN ({$placeholders})
                ";

        } else {

            throw new RuntimeException(
                'This messages table does not support read/unread status.'
            );

        }


        $stmt =
            $pdo->prepare(
                $sql
            );


        $stmt->execute(
            $ids
        );

    };


/*
|--------------------------------------------------------------------------
| Handle POST Actions
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
) {

    /*
     * Table must exist.
     */
    if ($messagesTable === null) {

        setFlash(
            'error',
            'The messages table could not be found.'
        );


        $feedbackRedirect();

    }


    /*
     * CSRF.
     */
    if (
        !verifyCsrf(
            $_POST['csrf_token']
            ?? null
        )
    ) {

        setFlash(
            'error',
            'Invalid request. Please refresh the page and try again.'
        );


        $feedbackRedirect();

    }


    $action =
        trim(
            (string) (
                $_POST['action']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Bulk Action
    |--------------------------------------------------------------------------
    */

    if (
        $action === 'bulk'
    ) {

        $bulkAction =
            trim(
                (string) (
                    $_POST['bulk_action']
                    ?? ''
                )
            );


        $rawIds =
            $_POST['ids']
            ?? [];


        if (
            !is_array(
                $rawIds
            )
        ) {

            $rawIds = [];

        }


        $ids =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $rawIds
                        ),
                        static fn (
                            int $id
                        ): bool =>
                            $id > 0
                    )
                )
            );


        if (!$ids) {

            setFlash(
                'error',
                'Please select at least one message.'
            );


            $feedbackRedirect();

        }


        try {

            switch (
                $bulkAction
            ) {

                case 'mark_read':

                    $markRead(
                        $pdo,
                        $messagesTable,
                        $ids,
                        $hasIsRead,
                        $hasReadAt
                    );


                    setFlash(
                        'success',
                        count(
                            $ids
                        ) .
                        ' selected message(s) marked as read.'
                    );

                    break;



                case 'mark_unread':

                    $markUnread(
                        $pdo,
                        $messagesTable,
                        $ids,
                        $hasIsRead,
                        $hasReadAt
                    );


                    setFlash(
                        'success',
                        count(
                            $ids
                        ) .
                        ' selected message(s) marked as unread.'
                    );

                    break;



                case 'delete':

                    $placeholders =
                        implode(
                            ',',
                            array_fill(
                                0,
                                count(
                                    $ids
                                ),
                                '?'
                            )
                        );


                    $stmt =
                        $pdo->prepare(
                            "
                            DELETE FROM {$messagesTable}

                            WHERE id IN ({$placeholders})
                            "
                        );


                    $stmt->execute(
                        $ids
                    );


                    setFlash(
                        'success',
                        'Selected messages deleted.'
                    );

                    break;



                default:

                    setFlash(
                        'error',
                        'Unknown bulk action.'
                    );

                    break;

            }


        } catch (
            Throwable $e
        ) {

            error_log(
                'Green Harvest admin feedback bulk action error: ' .
                $e->getMessage()
            );


            setFlash(
                'error',
                $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Could not perform the selected action.'
            );

        }


        $feedbackRedirect();

    }


    /*
    |--------------------------------------------------------------------------
    | Single Message Action
    |--------------------------------------------------------------------------
    */

    $messageId =
        filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );


    if (
        $messageId === false
        ||
        $messageId === null
        ||
        $messageId < 1
    ) {

        setFlash(
            'error',
            'Invalid message.'
        );


        $feedbackRedirect();

    }


    try {

        switch (
            $action
        ) {

            case 'mark_read':

                $markRead(
                    $pdo,
                    $messagesTable,
                    [
                        $messageId,
                    ],
                    $hasIsRead,
                    $hasReadAt
                );


                setFlash(
                    'success',
                    'Message marked as read.'
                );

                break;



            case 'mark_unread':

                $markUnread(
                    $pdo,
                    $messagesTable,
                    [
                        $messageId,
                    ],
                    $hasIsRead,
                    $hasReadAt
                );


                setFlash(
                    'success',
                    'Message marked as unread.'
                );

                break;



            case 'delete':

                $stmt =
                    $pdo->prepare(
                        "
                        DELETE FROM {$messagesTable}

                        WHERE id = ?
                        "
                    );


                $stmt->execute([
                    $messageId,
                ]);


                setFlash(
                    'success',
                    'Message deleted.'
                );

                break;



            default:

                setFlash(
                    'error',
                    'Unknown message action.'
                );

                break;

        }


    } catch (
        Throwable $e
    ) {

        error_log(
            'Green Harvest admin feedback action error: ' .
            $e->getMessage()
        );


        setFlash(
            'error',
            $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Could not perform this action.'
        );

    }


    $feedbackRedirect();

}


/*
|--------------------------------------------------------------------------
| Build Search
|--------------------------------------------------------------------------
*/

$whereParts = [];

$queryParams = [];


if (
    $messagesTable !== null
    &&
    $q !== ''
) {

    $searchColumns = [];


    if ($hasName) {

        $searchColumns[] =
            'name';

    } elseif ($hasSenderName) {

        $searchColumns[] =
            'sender_name';

    }


    if ($hasEmail) {

        $searchColumns[] =
            'email';

    } elseif ($hasSenderEmail) {

        $searchColumns[] =
            'sender_email';

    }


    if ($hasSubject) {

        $searchColumns[] =
            'subject';

    }


    if ($hasMessage) {

        $searchColumns[] =
            'message';

    } elseif ($hasBody) {

        $searchColumns[] =
            'body';

    }


    if ($searchColumns) {

        $searchParts = [];


        foreach (
            $searchColumns as
            $index =>
            $searchColumn
        ) {

            $placeholder =
                ':search_' .
                $index;


            $searchParts[] =
                "{$searchColumn} LIKE {$placeholder}";


            $queryParams[
                $placeholder
            ] =
                '%' .
                $q .
                '%';

        }


        $whereParts[] =
            '(' .
            implode(
                ' OR ',
                $searchParts
            ) .
            ')';

    }

}


$whereSql =
    $whereParts
        ? (
            ' WHERE ' .
            implode(
                ' AND ',
                $whereParts
            )
        )
        : '';


/*
|--------------------------------------------------------------------------
| Data Defaults
|--------------------------------------------------------------------------
*/

$messages = [];

$viewMessage = null;

$total = 0;

$totalPages = 1;

$unreadCount = 0;

$loadError = false;

$loadErrorMessage = null;


/*
|--------------------------------------------------------------------------
| Load Counts
|--------------------------------------------------------------------------
*/

try {

    if (
        $messagesTable === null
    ) {

        throw new RuntimeException(
            'No messages table was found.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Total Count
    |--------------------------------------------------------------------------
    */

    $countSql =
        "
        SELECT COUNT(*)

        FROM {$messagesTable}

        {$whereSql}
        ";


    $countStmt =
        $pdo->prepare(
            $countSql
        );


    foreach (
        $queryParams as
        $key =>
        $value
    ) {

        $countStmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );

    }


    $countStmt->execute();


    $total =
        (int)
        $countStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Unread Count
    |--------------------------------------------------------------------------
    */

    if ($supportsReadStatus) {

        if (
            $hasIsRead
            &&
            $hasReadAt
        ) {

            $unreadCondition =
                '
                COALESCE(is_read, 0) = 0
                AND read_at IS NULL
                ';

        } elseif ($hasIsRead) {

            $unreadCondition =
                '
                COALESCE(is_read, 0) = 0
                ';

        } else {

            $unreadCondition =
                '
                read_at IS NULL
                ';

        }


        $unreadStmt =
            $pdo->query(
                "
                SELECT COUNT(*)

                FROM {$messagesTable}

                WHERE {$unreadCondition}
                "
            );


        $unreadCount =
            (int)
            $unreadStmt->fetchColumn();

    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $totalPages =
        max(
            1,
            (int)
            ceil(
                $total /
                $perPage
            )
        );


    if (
        $page >
        $totalPages
    ) {

        $page =
            $totalPages;

    }


    $offset =
        (
            $page - 1
        )
        *
        $perPage;


    /*
    |--------------------------------------------------------------------------
    | CSV Export
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_GET['export']
        )
        &&
        (string)
        $_GET['export']
        ===
        '1'
    ) {

        $exportSql =
            "
            SELECT

                id,

                {$nameExpression}
                    AS name,

                {$emailExpression}
                    AS email,

                {$phoneExpression}
                    AS phone,

                {$subjectExpression}
                    AS subject,

                {$messageExpression}
                    AS message,

                {$readExpression}
                    AS is_read,

                {$readAtExpression}
                    AS read_at,

                {$createdAtExpression}
                    AS created_at

            FROM {$messagesTable}

            {$whereSql}

            ORDER BY
                created_at DESC
            ";


        $exportStmt =
            $pdo->prepare(
                $exportSql
            );


        foreach (
            $queryParams as
            $key =>
            $value
        ) {

            $exportStmt->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );

        }


        $exportStmt->execute();


        $exportRows =
            $exportStmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $filename =
            'greenharvest_messages_' .
            date(
                'Ymd_His'
            ) .
            '.csv';


        header(
            'Content-Type: text/csv; charset=utf-8'
        );


        header(
            'Content-Disposition: attachment; filename="' .
            $filename .
            '"'
        );


        /*
         * Excel UTF-8 BOM.
         */
        echo "\xEF\xBB\xBF";


        $output =
            fopen(
                'php://output',
                'w'
            );


        fputcsv(
            $output,
            [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Subject',
                'Message',
                'Status',
                'Read At',
                'Created At',
            ]
        );


        foreach (
            $exportRows as
            $row
        ) {

            fputcsv(
                $output,
                [
                    (string)
                    (
                        $row['id']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['name']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['email']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['phone']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['subject']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['message']
                        ?? ''
                    ),

                    (int)
                    (
                        $row['is_read']
                        ?? 0
                    ) === 1
                        ? 'Read'
                        : 'Unread',

                    (string)
                    (
                        $row['read_at']
                        ?? ''
                    ),

                    (string)
                    (
                        $row['created_at']
                        ?? ''
                    ),
                ]
            );

        }


        fclose(
            $output
        );


        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | Load Message List
    |--------------------------------------------------------------------------
    */

    $listSql =
        "
        SELECT

            id,

            {$nameExpression}
                AS name,

            {$emailExpression}
                AS email,

            {$subjectExpression}
                AS subject,

            SUBSTRING(
                {$messageExpression},
                1,
                280
            )
                AS snippet,

            {$readExpression}
                AS is_read,

            {$readAtExpression}
                AS read_at,

            {$createdAtExpression}
                AS created_at

        FROM {$messagesTable}

        {$whereSql}

        ORDER BY
            created_at DESC

        LIMIT :limit
        OFFSET :offset
        ";


    $listStmt =
        $pdo->prepare(
            $listSql
        );


    foreach (
        $queryParams as
        $key =>
        $value
    ) {

        $listStmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );

    }


    $listStmt->bindValue(
        ':limit',
        $perPage,
        PDO::PARAM_INT
    );


    $listStmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );


    $listStmt->execute();


    $messages =
        $listStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (
    Throwable $e
) {

    error_log(
        'Green Harvest admin feedback loading error: ' .
        $e->getMessage()
    );


    $loadError =
        true;


    $loadErrorMessage =
        $e->getMessage();

}


/*
|--------------------------------------------------------------------------
| View Single Message
|--------------------------------------------------------------------------
*/

$viewId =
    filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );


if (
    !$loadError
    &&
    $messagesTable !== null
    &&
    $viewId !== false
    &&
    $viewId !== null
    &&
    $viewId > 0
) {

    try {

        $singleSql =
            "
            SELECT

                id,

                {$nameExpression}
                    AS name,

                {$emailExpression}
                    AS email,

                {$phoneExpression}
                    AS phone,

                {$subjectExpression}
                    AS subject,

                {$messageExpression}
                    AS message,

                {$readExpression}
                    AS is_read,

                {$readAtExpression}
                    AS read_at,

                {$createdAtExpression}
                    AS created_at

            FROM {$messagesTable}

            WHERE id = ?

            LIMIT 1
            ";


        $singleStmt =
            $pdo->prepare(
                $singleSql
            );


        $singleStmt->execute([
            $viewId,
        ]);


        $viewMessage =
            $singleStmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | Automatically Mark Opened Message Read
        |--------------------------------------------------------------------------
        */

        if (
            $viewMessage
            &&
            $supportsReadStatus
            &&
            (int)
            (
                $viewMessage['is_read']
                ?? 0
            ) === 0
        ) {

            $markRead(
                $pdo,
                $messagesTable,
                [
                    $viewId,
                ],
                $hasIsRead,
                $hasReadAt
            );


            /*
             * Update local state immediately.
             */
            $viewMessage['is_read'] =
                1;


            if ($hasReadAt) {

                $viewMessage['read_at'] =
                    date(
                        'Y-m-d H:i:s'
                    );

            }


            /*
             * Keep visible list synchronized.
             */
            foreach (
                $messages as
                &$messageRow
            ) {

                if (
                    (int)
                    $messageRow['id']
                    ===
                    $viewId
                ) {

                    $messageRow['is_read'] =
                        1;


                    if ($hasReadAt) {

                        $messageRow['read_at'] =
                            $viewMessage[
                                'read_at'
                            ];

                    }


                    break;

                }

            }


            unset(
                $messageRow
            );


            /*
             * Update unread counter.
             */
            if ($unreadCount > 0) {

                $unreadCount--;

            }

        }


    } catch (
        Throwable $e
    ) {

        error_log(
            'Green Harvest admin feedback view error: ' .
            $e->getMessage()
        );


        $viewMessage = null;

    }

}


/*
|--------------------------------------------------------------------------
| URL Helper
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'adminFeedbackUrl'
    )
) {

    function adminFeedbackUrl(
        array $params = []
    ): string {

        $query =
            http_build_query(
                $params
            );


        return url(
            'admin/admin-feedback.php'
        )
        .
        (
            $query !== ''
                ? '?' .
                    $query
                : ''
        );

    }

}


/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/admin-header.php';

?>


<style>

/* =========================================================
   BOOTSTRAP ICONS
   Loads the icon font used by the Inbox action buttons.
========================================================= */
@import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css");


/* =========================================================
   GREEN HARVEST ADMIN INBOX
========================================================= */

.gh-inbox .bi {
    display: inline-block;
    font-size: 1rem;
    line-height: 1;
    vertical-align: -0.125em;
}


.gh-inbox {

    --inbox-dark:
        #092516;

    --inbox-green:
        #15803d;

    --inbox-soft:
        #f0fdf4;

    --inbox-border:
        rgba(20,83,45,.10);

}


.gh-inbox-card {

    border:
        1px solid
        var(--inbox-border);

    border-radius:
        18px;

    background:
        #ffffff;

    box-shadow:
        0 12px 35px
        rgba(9,37,22,.05);

}


.gh-inbox-stat {

    min-width:
        100px;

    padding:
        11px 14px;

    border:
        1px solid
        var(--inbox-border);

    border-radius:
        13px;

    background:
        #ffffff;

}


.gh-inbox-stat-label {

    display:
        block;

    color:
        #718078;

    font-size:
        .6rem;

    font-weight:
        800;

    letter-spacing:
        .07em;

    text-transform:
        uppercase;

}


.gh-inbox-stat-value {

    display:
        block;

    margin-top:
        2px;

    color:
        var(--inbox-dark);

    font-size:
        1rem;

    font-weight:
        800;

}


.gh-inbox-unread-row {

    background:
        #f5fbf6;

}


.gh-inbox-unread-row:hover {

    background:
        #edf8ef
        !important;

}


.gh-inbox-unread-dot {

    width:
        8px;

    height:
        8px;

    display:
        inline-block;

    border-radius:
        50%;

    background:
        #22c55e;

    box-shadow:
        0 0 0 4px
        rgba(34,197,94,.11);

}


.gh-inbox-status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        4px 8px;

    border-radius:
        999px;

    font-size:
        .58rem;

    font-weight:
        800;

}


.gh-inbox-status-unread {

    background:
        #dcfce7;

    color:
        #166534;

}


.gh-inbox-status-read {

    background:
        #f1f5f9;

    color:
        #64748b;

}


.gh-inbox-message-body {

    white-space:
        pre-wrap;

    overflow-wrap:
        anywhere;

    line-height:
        1.75;

}


.gh-inbox-avatar {

    width:
        44px;

    height:
        44px;

    flex:
        0 0 44px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        13px;

    background:
        var(--inbox-soft);

    color:
        var(--inbox-green);

    font-size:
        1rem;

    font-weight:
        800;

}


.gh-inbox-search {

    min-width:
        280px;

}


@media (
    max-width: 767.98px
) {

    .gh-inbox-search {

        min-width:
            0;

        width:
            100%;

    }

}

</style>



<div class="gh-inbox">


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div
    class="
        mb-6
        flex
        flex-col
        xl:flex-row
        xl:items-center
        xl:justify-between
        gap-4
    "
>


    <div>


        <p
            class="
                text-[11px]
                uppercase
                tracking-[0.14em]
                font-extrabold
                text-brand-600
                mb-1
            "
        >

            Customer Communication

        </p>


        <h2
            class="
                text-2xl
                font-extrabold
                text-slate-900
            "
        >

            Inbox

        </h2>


        <p
            class="
                text-xs
                text-slate-500
                mt-1
            "
        >

            Messages sent through the Green Harvest contact form.

        </p>


    </div>



    <div
        class="
            flex
            flex-wrap
            items-center
            gap-2
        "
    >


        <div class="gh-inbox-stat">


            <span class="gh-inbox-stat-label">

                Messages

            </span>


            <strong class="gh-inbox-stat-value">

                <?= number_format(
                    $total
                ) ?>

            </strong>


        </div>



        <div class="gh-inbox-stat">


            <span class="gh-inbox-stat-label">

                Unread

            </span>


            <strong
                class="
                    gh-inbox-stat-value
                    text-brand-600
                "
            >

                <?= number_format(
                    $unreadCount
                ) ?>

            </strong>


        </div>


    </div>


</div>



<?php displayFlash(); ?>



<!-- =========================================================
     SEARCH
========================================================= -->

<div
    class="
        gh-inbox-card
        p-4
        mb-5
    "
>


    <form
        method="get"
        action="<?= e(
            url(
                'admin/admin-feedback.php'
            )
        ) ?>"
        class="
            flex
            flex-col
            md:flex-row
            md:items-center
            gap-2
        "
    >


        <div
            class="
                relative
                flex-1
            "
        >


            <i
                class="
                    bi
                    bi-search
                    absolute
                    left-3
                    top-1/2
                    -translate-y-1/2
                    text-slate-400
                "
            ></i>


            <input
                type="search"
                name="q"
                value="<?= e(
                    $q
                ) ?>"
                placeholder="Search name, email, subject or message..."
                class="
                    gh-inbox-search
                    w-full
                    pl-9
                    pr-3
                    py-2.5
                    rounded-xl
                    border
                    border-slate-200
                    text-sm
                    outline-none
                    focus:border-brand-500
                    focus:ring-2
                    focus:ring-brand-100
                "
            >


        </div>



        <button
            type="submit"
            class="
                px-4
                py-2.5
                rounded-xl
                bg-brand-600
                text-white
                text-sm
                font-semibold
                hover:bg-brand-700
            "
        >

            Search

        </button>



        <?php if (
            $q !== ''
        ): ?>


            <a
                href="<?= e(
                    url(
                        'admin/admin-feedback.php'
                    )
                ) ?>"
                class="
                    px-4
                    py-2.5
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    text-sm
                    font-semibold
                    text-slate-600
                    hover:bg-slate-50
                "
            >

                Clear

            </a>


        <?php endif; ?>



        <a
            href="<?= e(
                adminFeedbackUrl([
                    'q' =>
                        $q,
                    'export' =>
                        1,
                ])
            ) ?>"
            class="
                px-4
                py-2.5
                rounded-xl
                border
                border-slate-200
                bg-white
                text-sm
                font-semibold
                text-slate-600
                hover:bg-slate-50
            "
        >

            <i class="bi bi-download me-1"></i>

            Export CSV

        </a>


    </form>


</div>



<!-- =========================================================
     ERROR
========================================================= -->

<?php if (
    $loadError
): ?>


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


        <strong>

            The message list could not be loaded.

        </strong>


        <?php if (
            $loadErrorMessage
        ): ?>


            <details class="mt-2 text-xs">


                <summary>

                    Technical details

                </summary>


                <pre
                    class="mt-2"
                    style="white-space:pre-wrap;"
                ><?= e(
                    $loadErrorMessage
                ) ?></pre>


            </details>


        <?php endif; ?>


    </div>


<?php else: ?>



    <!-- =====================================================
         OPENED MESSAGE
    ====================================================== -->

    <?php if (
        $viewMessage
    ): ?>


        <?php

        $senderName =
            trim(
                (string) (
                    $viewMessage['name']
                    ?? ''
                )
            );


        $senderInitial =
            $senderName !== ''
                ? strtoupper(
                    substr(
                        $senderName,
                        0,
                        1
                    )
                )
                : 'U';


        $viewIsRead =
            (int) (
                $viewMessage['is_read']
                ?? 0
            )
            ===
            1;

        ?>


        <section
            class="
                gh-inbox-card
                p-6
                mb-6
            "
        >


            <div
                class="
                    flex
                    items-start
                    gap-4
                "
            >


                <span class="gh-inbox-avatar">

                    <?= e(
                        $senderInitial
                    ) ?>

                </span>



                <div class="flex-1 min-w-0">


                    <div
                        class="
                            flex
                            flex-wrap
                            items-start
                            justify-between
                            gap-3
                            mb-3
                        "
                    >


                        <div>


                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                    mb-1
                                "
                            >


                                <h3
                                    class="
                                        text-lg
                                        font-extrabold
                                        text-slate-900
                                    "
                                >

                                    <?= e(
                                        $viewMessage['subject']
                                        ?: 'No subject'
                                    ) ?>

                                </h3>



                                <span
                                    class="
                                        gh-inbox-status
                                        gh-inbox-status-read
                                    "
                                >

                                    <i class="bi bi-check-circle-fill"></i>

                                    Read

                                </span>


                            </div>


                            <div
                                class="
                                    text-xs
                                    text-slate-500
                                "
                            >

                                From:

                                <strong class="text-slate-700">

                                    <?= e(
                                        $viewMessage['name']
                                        ?? ''
                                    ) ?>

                                </strong>


                                <?php if (
                                    !empty(
                                        $viewMessage['email']
                                    )
                                ): ?>

                                    &lt;<?= e(
                                        $viewMessage['email']
                                    ) ?>&gt;

                                <?php endif; ?>


                            </div>


                        </div>



                        <span
                            class="
                                text-xs
                                text-slate-400
                            "
                        >

                            <?= e(
                                (string) (
                                    $viewMessage['created_at']
                                    ?? ''
                                )
                            ) ?>

                        </span>


                    </div>



                    <?php if (
                        !empty(
                            $viewMessage['phone']
                        )
                    ): ?>


                        <div
                            class="
                                mb-4
                                text-xs
                                text-slate-500
                            "
                        >

                            <i class="bi bi-telephone me-1"></i>

                            <?= e(
                                $viewMessage['phone']
                            ) ?>

                        </div>


                    <?php endif; ?>



                    <div
                        class="
                            gh-inbox-message-body
                            text-sm
                            text-slate-700
                            bg-slate-50
                            rounded-xl
                            p-4
                            border
                            border-slate-100
                        "
                    >

                        <?= e(
                            $viewMessage['message']
                            ?? ''
                        ) ?>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 MESSAGE ACTIONS
            ================================================== -->

            <div
                class="
                    mt-5
                    pt-4
                    border-t
                    border-slate-100
                    flex
                    flex-wrap
                    items-center
                    gap-2
                "
            >


                <?php if (
                    $supportsReadStatus
                ): ?>


                    <form
                        method="post"
                        action="<?= e(
                            url(
                                'admin/admin-feedback.php'
                            )
                        ) ?>"
                        class="m-0"
                    >


                        <?= csrfField() ?>


                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)
                                $viewMessage['id'] ?>"
                        >


                        <input
                            type="hidden"
                            name="action"
                            value="mark_unread"
                        >


                        <button
                            type="submit"
                            class="
                                inline-flex
                                items-center
                                gap-2
                                px-3
                                py-2
                                rounded-lg
                                border
                                border-slate-200
                                bg-white
                                text-xs
                                font-bold
                                text-slate-600
                                hover:bg-slate-50
                            "
                        >

                            <i class="bi bi-envelope"></i>

                            Mark Unread

                        </button>


                    </form>


                <?php endif; ?>



                <?php if (
                    !empty(
                        $viewMessage['email']
                    )
                ): ?>


                    <a
                        href="mailto:<?= e(
                            $viewMessage['email']
                        ) ?>"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            px-3
                            py-2
                            rounded-lg
                            bg-brand-600
                            text-white
                            text-xs
                            font-bold
                            hover:bg-brand-700
                        "
                    >

                        <i class="bi bi-reply-fill"></i>

                        Reply by Email

                    </a>


                <?php endif; ?>



                <form
                    method="post"
                    action="<?= e(
                        url(
                            'admin/admin-feedback.php'
                        )
                    ) ?>"
                    class="m-0"
                    onsubmit="return confirm('Delete this message? This action cannot be undone.');"
                >


                    <?= csrfField() ?>


                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)
                            $viewMessage['id'] ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >


                    <button
                        type="submit"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            px-3
                            py-2
                            rounded-lg
                            border
                            border-rose-200
                            bg-white
                            text-xs
                            font-bold
                            text-rose-600
                            hover:bg-rose-50
                        "
                    >

                        <i class="bi bi-trash3"></i>

                        Delete

                    </button>


                </form>


            </div>


        </section>


    <?php elseif (
        $viewId
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
                text-amber-700
            "
        >

            The requested message could not be found.

        </div>


    <?php endif; ?>



    <!-- =====================================================
         MESSAGE LIST
    ====================================================== -->

    <section
        class="
            gh-inbox-card
            overflow-hidden
        "
    >


        <!-- =================================================
             LIST HEADER
        ================================================== -->

        <div
            class="
                px-6
                py-4
                border-b
                border-slate-100
                flex
                flex-wrap
                items-center
                justify-between
                gap-3
            "
        >


            <div>


                <h3
                    class="
                        font-extrabold
                        text-slate-900
                    "
                >

                    Messages

                </h3>


                <p
                    class="
                        text-xs
                        text-slate-400
                        mt-1
                    "
                >


                    <?php if (
                        $total > 0
                    ): ?>

                        <?= number_format(
                            $total
                        ) ?>

                        message<?= $total === 1
                            ? ''
                            : 's' ?>

                        <?php if (
                            $unreadCount > 0
                        ): ?>

                            ·

                            <?= number_format(
                                $unreadCount
                            ) ?>

                            unread

                        <?php endif; ?>


                    <?php else: ?>

                        No messages

                    <?php endif; ?>


                </p>


            </div>


        </div>



        <!-- =================================================
             BULK ACTIONS
        ================================================== -->

        <?php if (
            $messages
        ): ?>


            <div
                class="
                    px-4
                    py-3
                    flex
                    flex-wrap
                    items-center
                    gap-3
                    border-b
                    border-slate-100
                    bg-slate-50
                "
            >


                <form
                    id="bulkActionForm"
                    method="post"
                    action="<?= e(
                        url(
                            'admin/admin-feedback.php'
                        )
                    ) ?>"
                    class="
                        flex
                        flex-wrap
                        items-center
                        gap-2
                    "
                >


                    <?= csrfField() ?>


                    <input
                        type="hidden"
                        name="action"
                        value="bulk"
                    >



                    <label
                        for="bulkActionSelect"
                        class="
                            text-xs
                            text-slate-600
                            font-bold
                        "
                    >

                        Bulk Actions

                    </label>



                    <select
                        id="bulkActionSelect"
                        name="bulk_action"
                        class="
                            px-3
                            py-2
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            text-xs
                        "
                    >


                        <?php if (
                            $supportsReadStatus
                        ): ?>


                            <option value="mark_read">

                                Mark as Read

                            </option>


                            <option value="mark_unread">

                                Mark as Unread

                            </option>


                        <?php endif; ?>


                        <option value="delete">

                            Delete

                        </option>


                    </select>



                    <button
                        id="bulkApplyBtn"
                        type="button"
                        class="
                            px-3
                            py-2
                            rounded-xl
                            bg-brand-600
                            text-white
                            text-xs
                            font-bold
                        "
                    >

                        Apply

                    </button>



                    <span
                        id="selectedMessageCount"
                        class="
                            text-xs
                            text-slate-400
                            ml-2
                        "
                    >

                        0 selected

                    </span>


                </form>


            </div>


        <?php endif; ?>



        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="overflow-x-auto">


            <table
                class="
                    w-full
                    text-left
                    text-sm
                "
            >


                <thead>


                    <tr
                        class="
                            bg-slate-50/70
                            border-b
                            border-slate-100
                            text-slate-500
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wider
                        "
                    >


                        <th
                            class="
                                py-3
                                px-4
                                text-center
                            "
                        >

                            <?php if (
                                $messages
                            ): ?>


                                <input
                                    type="checkbox"
                                    id="selectAllMessages"
                                    aria-label="Select all messages"
                                >


                            <?php endif; ?>


                        </th>


                        <th class="py-3 px-4">

                            Status

                        </th>


                        <th class="py-3 px-5">

                            From

                        </th>


                        <th class="py-3 px-5">

                            Subject

                        </th>


                        <th class="py-3 px-5">

                            Message

                        </th>


                        <th class="py-3 px-5">

                            Received

                        </th>


                        <th class="py-3 px-5 text-right">

                            Actions

                        </th>


                    </tr>


                </thead>



                <tbody>


                    <?php if (
                        !$messages
                    ): ?>


                        <tr>


                            <td
                                colspan="7"
                                class="
                                    py-16
                                    px-6
                                    text-center
                                "
                            >


                                <span
                                    class="
                                        w-14
                                        h-14
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-2xl
                                        bg-brand-50
                                        text-brand-600
                                        text-xl
                                        mb-3
                                    "
                                >

                                    <i class="bi bi-inbox"></i>

                                </span>


                                <h4
                                    class="
                                        font-bold
                                        text-slate-800
                                        mb-1
                                    "
                                >

                                    No messages found

                                </h4>


                                <p
                                    class="
                                        text-xs
                                        text-slate-400
                                    "
                                >

                                    <?php if (
                                        $q !== ''
                                    ): ?>

                                        Try another search term.

                                    <?php else: ?>

                                        Contact form submissions will appear here.

                                    <?php endif; ?>

                                </p>


                            </td>


                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $messages as
                            $message
                        ): ?>


                            <?php

                            $messageId =
                                (int)
                                $message['id'];


                            $messageIsRead =
                                (int)
                                (
                                    $message['is_read']
                                    ?? 0
                                )
                                ===
                                1;


                            $messageName =
                                trim(
                                    (string) (
                                        $message['name']
                                        ?? ''
                                    )
                                );

                            ?>


                            <tr
                                class="
                                    border-b
                                    border-slate-100
                                    hover:bg-slate-50
                                    <?= !$messageIsRead
                                        ? 'gh-inbox-unread-row'
                                        : ''
                                    ?>
                                "
                            >


                                <!-- CHECKBOX -->

                                <td
                                    class="
                                        py-3
                                        px-4
                                        text-center
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        class="message-checkbox"
                                        value="<?= $messageId ?>"
                                        aria-label="Select message from <?= e(
                                            $messageName
                                        ) ?>"
                                    >

                                </td>



                                <!-- STATUS -->

                                <td
                                    class="
                                        py-3
                                        px-4
                                        whitespace-nowrap
                                    "
                                >


                                    <?php if (
                                        $messageIsRead
                                    ): ?>


                                        <span
                                            class="
                                                gh-inbox-status
                                                gh-inbox-status-read
                                            "
                                        >

                                            <i class="bi bi-check-circle"></i>

                                            Read

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                gh-inbox-status
                                                gh-inbox-status-unread
                                            "
                                        >

                                            <span class="gh-inbox-unread-dot"></span>

                                            Unread

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- FROM -->

                                <td
                                    class="
                                        py-3
                                        px-5
                                        w-52
                                    "
                                >


                                    <div
                                        class="
                                            <?= !$messageIsRead
                                                ? 'font-extrabold'
                                                : 'font-semibold'
                                            ?>
                                            text-slate-900
                                        "
                                    >

                                        <?= e(
                                            $messageName
                                            ?: 'Unknown'
                                        ) ?>

                                    </div>


                                    <div
                                        class="
                                            text-xs
                                            text-slate-500
                                            mt-1
                                        "
                                    >

                                        <?= e(
                                            $message['email']
                                            ?? ''
                                        ) ?>

                                    </div>


                                </td>



                                <!-- SUBJECT -->

                                <td
                                    class="
                                        py-3
                                        px-5
                                        w-56
                                    "
                                >

                                    <span
                                        class="
                                            <?= !$messageIsRead
                                                ? 'font-extrabold text-slate-900'
                                                : 'font-semibold text-slate-700'
                                            ?>
                                        "
                                    >

                                        <?= e(
                                            $message['subject']
                                            ?: 'No subject'
                                        ) ?>

                                    </span>

                                </td>



                                <!-- SNIPPET -->

                                <td
                                    class="
                                        py-3
                                        px-5
                                        text-xs
                                        text-slate-500
                                        max-w-md
                                    "
                                >

                                    <div
                                        style="
                                            display:-webkit-box;
                                            -webkit-line-clamp:2;
                                            -webkit-box-orient:vertical;
                                            overflow:hidden;
                                        "
                                    >

                                        <?= e(
                                            $message['snippet']
                                            ?? ''
                                        ) ?>

                                    </div>


                                </td>



                                <!-- DATE -->

                                <td
                                    class="
                                        py-3
                                        px-5
                                        text-xs
                                        text-slate-500
                                        whitespace-nowrap
                                    "
                                >

                                    <?= e(
                                        (string) (
                                            $message['created_at']
                                            ?? ''
                                        )
                                    ) ?>

                                </td>



                                <!-- ACTIONS -->

                                <td
                                    class="
                                        py-3
                                        px-5
                                        whitespace-nowrap
                                    "
                                >


                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-end
                                            gap-1
                                        "
                                    >


                                        <!-- OPEN -->

                                        <a
                                            href="<?= e(
                                                adminFeedbackUrl([
                                                    'id' =>
                                                        $messageId,
                                                    'q' =>
                                                        $q,
                                                    'page' =>
                                                        $page,
                                                ])
                                            ) ?>"
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1
                                                px-2.5
                                                py-1.5
                                                rounded-lg
                                                bg-brand-50
                                                border
                                                border-brand-100
                                                text-brand-700
                                                text-[11px]
                                                font-bold
                                                hover:bg-brand-100
                                            "
                                        >

                                            <i class="bi bi-eye"></i>

                                            Open

                                        </a>



                                        <!-- READ / UNREAD -->

                                        <?php if (
                                            $supportsReadStatus
                                        ): ?>


                                            <form
                                                method="post"
                                                action="<?= e(
                                                    url(
                                                        'admin/admin-feedback.php'
                                                    )
                                                ) ?>"
                                                class="m-0"
                                            >


                                                <?= csrfField() ?>


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $messageId ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="<?= $messageIsRead
                                                        ? 'mark_unread'
                                                        : 'mark_read'
                                                    ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="
                                                        w-8
                                                        h-8
                                                        inline-flex
                                                        items-center
                                                        justify-center
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        text-slate-500
                                                        hover:bg-slate-50
                                                    "
                                                    title="<?= $messageIsRead
                                                        ? 'Mark unread'
                                                        : 'Mark read'
                                                    ?>"
                                                >

                                                    <i
                                                        class="bi <?= $messageIsRead
                                                            ? 'bi-envelope-open'
                                                            : 'bi-envelope'
                                                        ?>"
                                                    ></i>

                                                </button>


                                            </form>


                                        <?php endif; ?>



                                        <!-- DELETE -->

                                        <form
                                            method="post"
                                            action="<?= e(
                                                url(
                                                    'admin/admin-feedback.php'
                                                )
                                            ) ?>"
                                            class="m-0"
                                            onsubmit="return confirm('Delete this message? This cannot be undone.');"
                                        >


                                            <?= csrfField() ?>


                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $messageId ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >


                                            <button
                                                type="submit"
                                                class="
                                                    w-8
                                                    h-8
                                                    inline-flex
                                                    items-center
                                                    justify-center
                                                    rounded-lg
                                                    border
                                                    border-rose-200
                                                    bg-red-50
                                                    text-rose-500
                                                    hover:bg-rose-50
                                                "
                                                title="Delete message"
                                                aria-label="Delete message"
                                            >

                                                <i class="bi bi-trash"></i>

                                            </button>


                                        </form>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>



        <!-- =================================================
             PAGINATION
        ================================================== -->

        <?php if (
            $total > 0
        ): ?>


            <div
                class="
                    px-6
                    py-4
                    border-t
                    border-slate-100
                    flex
                    flex-wrap
                    items-center
                    justify-between
                    gap-3
                "
            >


                <div
                    class="
                        text-xs
                        text-slate-500
                    "
                >

                    Page

                    <strong>

                        <?= $page ?>

                    </strong>

                    of

                    <strong>

                        <?= $totalPages ?>

                    </strong>

                </div>



                <div
                    class="
                        flex
                        items-center
                        gap-2
                    "
                >


                    <?php if (
                        $page > 1
                    ): ?>


                        <a
                            href="<?= e(
                                adminFeedbackUrl([
                                    'q' =>
                                        $q,
                                    'page' =>
                                        $page - 1,
                                ])
                            ) ?>"
                            class="
                                px-3
                                py-2
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                text-xs
                                font-bold
                                text-slate-600
                                hover:bg-slate-50
                            "
                        >

                            <i class="bi bi-chevron-left me-1"></i>

                            Previous

                        </a>


                    <?php endif; ?>



                    <?php if (
                        $page < $totalPages
                    ): ?>


                        <a
                            href="<?= e(
                                adminFeedbackUrl([
                                    'q' =>
                                        $q,
                                    'page' =>
                                        $page + 1,
                                ])
                            ) ?>"
                            class="
                                px-3
                                py-2
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                text-xs
                                font-bold
                                text-slate-600
                                hover:bg-slate-50
                            "
                        >

                            Next

                            <i class="bi bi-chevron-right ms-1"></i>

                        </a>


                    <?php endif; ?>


                </div>


            </div>


        <?php endif; ?>


    </section>


<?php endif; ?>


</div>



<!-- =========================================================
     BULK ACTION JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const selectAll =
            document.getElementById(
                'selectAllMessages'
            );


        const bulkApplyButton =
            document.getElementById(
                'bulkApplyBtn'
            );


        const bulkForm =
            document.getElementById(
                'bulkActionForm'
            );


        const bulkSelect =
            document.getElementById(
                'bulkActionSelect'
            );


        const selectedCount =
            document.getElementById(
                'selectedMessageCount'
            );



        function getCheckboxes() {

            return Array.from(
                document.querySelectorAll(
                    '.message-checkbox'
                )
            );

        }



        function getSelected() {

            return getCheckboxes()
                .filter(
                    function (
                        checkbox
                    ) {

                        return checkbox.checked;

                    }
                );

        }



        function updateSelectionState() {


            const checkboxes =
                getCheckboxes();


            const selected =
                getSelected();


            if (selectedCount) {

                selectedCount.textContent =
                    selected.length +
                    ' selected';

            }


            if (
                selectAll
                &&
                checkboxes.length > 0
            ) {

                selectAll.checked =
                    selected.length
                    ===
                    checkboxes.length;


                selectAll.indeterminate =
                    selected.length > 0
                    &&
                    selected.length
                    <
                    checkboxes.length;

            }


        }



        if (selectAll) {


            selectAll.addEventListener(
                'change',
                function () {


                    getCheckboxes()
                        .forEach(
                            function (
                                checkbox
                            ) {

                                checkbox.checked =
                                    selectAll.checked;

                            }
                        );


                    updateSelectionState();


                }
            );


        }



        getCheckboxes()
            .forEach(
                function (
                    checkbox
                ) {


                    checkbox.addEventListener(
                        'change',
                        updateSelectionState
                    );


                }
            );



        if (
            bulkApplyButton
            &&
            bulkForm
            &&
            bulkSelect
        ) {


            bulkApplyButton.addEventListener(
                'click',
                function () {


                    const selected =
                        getSelected();


                    if (
                        selected.length === 0
                    ) {

                        alert(
                            'Please select at least one message.'
                        );

                        return;

                    }


                    if (
                        bulkSelect.value
                        ===
                        'delete'
                    ) {


                        const confirmed =
                            window.confirm(
                                'Delete selected messages? This action cannot be undone.'
                            );


                        if (!confirmed) {

                            return;

                        }


                    }


                    /*
                     * Remove previously generated IDs.
                     */
                    bulkForm
                        .querySelectorAll(
                            'input[name="ids[]"]'
                        )
                        .forEach(
                            function (
                                input
                            ) {

                                input.remove();

                            }
                        );


                    /*
                     * Add selected message IDs.
                     */
                    selected.forEach(
                        function (
                            checkbox
                        ) {


                            const hidden =
                                document.createElement(
                                    'input'
                                );


                            hidden.type =
                                'hidden';


                            hidden.name =
                                'ids[]';


                            hidden.value =
                                checkbox.value;


                            bulkForm.appendChild(
                                hidden
                            );


                        }
                    );


                    bulkForm.submit();


                }
            );


        }


        updateSelectionState();


    }
);

</script>


<?php

require_once __DIR__ . '/includes/admin-footer.php';

?>