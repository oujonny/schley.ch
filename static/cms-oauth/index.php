<?php
/**
 * Decap CMS – GitHub OAuth proxy
 *
 * Handles the server-side OAuth code exchange so the GitHub client secret
 * never leaves the server.
 *
 * Credentials are loaded from a PHP file ABOVE the web root (never
 * web-accessible, never committed to Git):
 *   ~/cms_oauth_config.php
 *
 * That file must return an array:
 *   <?php
 *   return [
 *       'client_id'     => 'YOUR_GITHUB_OAUTH_APP_CLIENT_ID',
 *       'client_secret' => 'YOUR_GITHUB_OAUTH_APP_CLIENT_SECRET',
 *   ];
 *
 * GitHub OAuth App settings:
 *   Homepage URL:              https://schleyconsult.ch
 *   Authorization callback URL: https://schleyconsult.ch/cms-oauth/callback
 */

declare(strict_types=1);

// ── Routing ──────────────────────────────────────────────────────────────────

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$action = rtrim(str_replace('/cms-oauth', '', $uri), '/');

match ($action) {
    '/auth'     => handleAuth(),
    '/callback' => handleCallback(),
    default     => respond404(),
};

// ── Handlers ─────────────────────────────────────────────────────────────────

function handleAuth(): void
{
    $config = loadConfig();

    $state = bin2hex(random_bytes(16));
    session_start();
    $_SESSION['oauth_state'] = $state;

    $scope = 'repo,user';

    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => $config['client_id'],
        'redirect_uri' => callbackUrl(),
        'scope'        => $scope,
        'state'        => $state,
    ]);

    header('Location: ' . $url, true, 302);
    exit;
}

function handleCallback(): void
{
    $config = loadConfig();

    $code  = $_GET['code']  ?? '';
    $state = $_GET['state'] ?? '';

    // Verify CSRF state
    session_start();
    $expectedState = $_SESSION['oauth_state'] ?? '';
    unset($_SESSION['oauth_state']);

    if ($state === '' || !hash_equals($expectedState, $state)) {
        renderError('Ungültiger State-Parameter. Bitte erneut versuchen.');
        return;
    }

    if ($code === '') {
        renderError('Kein Autorisierungscode von GitHub erhalten.');
        return;
    }

    // Exchange code for access token
    $response = curlPost('https://github.com/login/oauth/access_token', [
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code'          => $code,
        'redirect_uri'  => callbackUrl(),
    ], ['Accept: application/json']);

    if ($response === false) {
        renderError('Verbindung zu GitHub fehlgeschlagen.');
        return;
    }

    $data = json_decode($response, true);

    if (empty($data['access_token'])) {
        $detail = $data['error_description'] ?? $data['error'] ?? 'Unbekannter Fehler';
        renderError('Token-Austausch fehlgeschlagen: ' . $detail);
        return;
    }

    renderSuccess($data['access_token']);
}

function respond404(): never
{
    http_response_code(404);
    echo 'Not found';
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function loadConfig(): array
{
    // One level above public_html – never web-accessible
    $path = dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/cms_oauth_config.php';

    if (!is_file($path)) {
        http_response_code(500);
        // Safe to reveal the expected path since this is admin-only
        die('OAuth-Konfiguration nicht gefunden. Bitte erstellen Sie: ' . htmlspecialchars($path));
    }

    return require $path;
}

function callbackUrl(): string
{
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/cms-oauth/callback';
}

/**
 * POST via cURL (allow_url_fopen is disabled by default on cyon).
 * Returns response body on success, false on failure.
 */
function curlPost(string $url, array $fields, array $headers = []): string|false
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * Render an HTML page that sends a success postMessage to the CMS window.
 * Uses the Netlify/Decap CMS OAuth protocol.
 */
function renderSuccess(string $token): void
{
    // json_encode handles all escaping for both JSON and the JS string context
    $payload = json_encode(json_encode(['token' => $token, 'provider' => 'github']));
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de">
    <head><meta charset="utf-8"><title>Authentifizierung erfolgreich</title></head>
    <body>
    <p>Authentifizierung erfolgreich. Dieses Fenster wird geschlossen…</p>
    <script>
    (function () {
      var payload = {$payload};

      if (!window.opener) {
        document.querySelector('p').textContent =
          'Fehler: window.opener ist null – bitte Fenster schließen und erneut versuchen.';
        return;
      }

      // Step 1: tell the CMS we are starting the auth handshake
      window.opener.postMessage('authorizing:github', '*');

      // Step 2: when the CMS responds with its origin, send the token back to that origin
      window.addEventListener('message', function handler(e) {
        window.removeEventListener('message', handler);
        window.opener.postMessage('authorization:github:success:' + payload, e.origin);
        // Let the CMS close this popup; do NOT call window.close() ourselves
      }, false);
    })();
    </script>
    </body>
    </html>
    HTML;
}

/**
 * Render an HTML page that sends an error postMessage to the CMS window.
 */
function renderError(string $message): void
{
    http_response_code(400);
    $safeMsg = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $jsMsg   = json_encode($message);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de">
    <head><meta charset="utf-8"><title>Fehler bei der Authentifizierung</title></head>
    <body>
    <p>Fehler bei der Authentifizierung: {$safeMsg}</p>
    <script>
    (function () {
      if (window.opener) {
        window.opener.postMessage('authorization:github:error:' + {$jsMsg}, '*');
      }
    })();
    </script>
    </body>
    </html>
    HTML;
}
