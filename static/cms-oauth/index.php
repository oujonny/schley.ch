<?php
/**
 * Decap CMS – GitHub OAuth proxy (auth entry point)
 *
 * Handles /cms-oauth/auth → redirects to GitHub.
 * The callback is handled by callback.php (a real file, no rewriting needed).
 *
 * Credentials file ABOVE the web root (never web-accessible, never in Git):
 *   ~/cms_oauth_config.php
 *   <?php return ['client_id' => '...', 'client_secret' => '...'];
 *
 * GitHub OAuth App:
 *   Authorization callback URL: https://schleyconsult.ch/cms-oauth/callback.php
 *   (add both beta and production URLs in the GitHub App settings)
 */

declare(strict_types=1);

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$action = rtrim(str_replace('/cms-oauth', '', $uri), '/');

match ($action) {
    '/auth' => handleAuth(),
    default => respond404(),
};

// ── Handler ───────────────────────────────────────────────────────────────────

function handleAuth(): void
{
    $config = loadConfig();

    // Self-verifying HMAC state — no PHP session needed.
    // callback.php verifies this without any server-side storage.
    $state = makeState($config['client_secret']);

    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => $config['client_id'],
        'redirect_uri' => callbackUrl(),
        'scope'        => 'repo,user',
        'state'        => $state,
    ]);

    header('Location: ' . $url, true, 302);
    exit;
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
    $path = dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/cms_oauth_config.php';
    if (!is_file($path)) {
        http_response_code(500);
        die('OAuth-Konfiguration nicht gefunden: ' . htmlspecialchars($path));
    }
    return require $path;
}

function callbackUrl(): string
{
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/cms-oauth/callback.php';
}

/**
 * Build a self-verifying HMAC state token (no server-side storage required).
 * Format: base64(timestamp:nonce:hmac)
 */
function makeState(string $secret): string
{
    $ts    = (string) time();
    $nonce = bin2hex(random_bytes(12));
    $sig   = hash_hmac('sha256', $ts . ':' . $nonce, $secret);
    // URL-safe base64: +/ → -_ so the value survives PHP query-string decoding
    return rtrim(strtr(base64_encode($ts . ':' . $nonce . ':' . $sig), '+/', '-_'), '=');
}
