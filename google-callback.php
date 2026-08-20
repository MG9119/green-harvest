<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function googleHttpRequest(string $url, array $postData = [], ?string $bearerToken = null): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        $headers = [];
        if ($bearerToken !== null && $bearerToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($postData !== []) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData, '', '&');
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            error_log('Green Harvest Google OAuth HTTP error: ' . $curlError);
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    $context = stream_context_create([
        'http' => [
            'method' => $postData === [] ? 'GET' : 'POST',
            'header' => $bearerToken !== null && $bearerToken !== ''
                ? "Authorization: Bearer $bearerToken\r\nContent-Type: application/x-www-form-urlencoded\r\n"
                : "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData === [] ? null : http_build_query($postData, '', '&'),
            'timeout' => 30,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

$clientId = (string) getenv('GOOGLE_CLIENT_ID');
$clientSecret = (string) getenv('GOOGLE_CLIENT_SECRET');

if ($clientId === '' || $clientSecret === '') {
    setFlash('error', 'Google sign-in is not configured yet. Please add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.');
    redirectTo('login.php');
}

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$expectedState = $_SESSION['google_oauth_state'] ?? '';

if ($code === '' || $state === '' || !hash_equals($expectedState, $state)) {
    setFlash('error', 'Google sign-in could not be verified. Please try again.');
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);
    redirectTo('login.php');
}

$redirectUri = url('google-callback.php');

$tokenData = googleHttpRequest(
    'https://oauth2.googleapis.com/token',
    [
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => $redirectUri,
    ]
);

$accessToken = trim((string) ($tokenData['access_token'] ?? ''));

if ($accessToken === '') {
    setFlash('error', 'Google sign-in failed while exchanging the authorization code.');
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);
    redirectTo('login.php');
}

$userInfo = googleHttpRequest(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    [],
    $accessToken
);

if (!is_array($userInfo)) {
    setFlash('error', 'Google account information could not be loaded.');
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);
    redirectTo('login.php');
}

$email = strtolower(trim((string) ($userInfo['email'] ?? '')));
$fullName = trim((string) ($userInfo['name'] ?? $userInfo['given_name'] ?? 'Google User'));
$phone = $userInfo['phone_number'] ?? null;

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Google did not return a valid email address.');
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);
    redirectTo('login.php');
}

if (!loginGoogleUser($pdo, $email, $fullName, $phone)) {
    setFlash('error', 'Google sign-in could not complete your account setup.');
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);
    redirectTo('login.php');
}

$redirectTarget = safeRedirectPath(
    $_SESSION['google_oauth_redirect'] ?? 'account.php',
    'account.php'
);
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_redirect']);

setFlash('success', 'Welcome back to Green Harvest.');
redirectTo($redirectTarget);
