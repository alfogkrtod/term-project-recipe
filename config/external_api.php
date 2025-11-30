<?php
/**
 * External API configuration (FoodSafetyKorea COOKRCP01)
 *
 * NOTE: This is an example config file. Put your actual key into
 * `config/external_api.php` (or better, use environment variables)
 * and do not commit sensitive keys to version control.
 */

// Base path (per spec): http://openapi.foodsafetykorea.go.kr/api/{key}/{serviceId}/{dataType}/{startIdx}/{endIdx}/...
define('EXTERNAL_API_BASE_PATH', 'http://openapi.foodsafetykorea.go.kr/api');
define('EXTERNAL_API_SERVICE_ID', 'COOKRCP01');

// API key: prefer environment variable `EXTERNAL_API_KEY` to avoid committing secrets.
// If the env var is not set, this falls back to the placeholder below.
$envKey = getenv('EXTERNAL_API_KEY');
if ($envKey && is_string($envKey) && strlen($envKey) > 0) {
	define('EXTERNAL_API_KEY', $envKey);
} else {
	// Replace the placeholder with your key for local-only testing OR set the
	// environment variable as described below. Do NOT commit your real key.
	define('EXTERNAL_API_KEY', 'YOUR_KEY_ID_HERE'); // replace with real key if necessary
}

// We show 50 results per page per project spec
define('EXTERNAL_API_PER_PAGE', 50);

/*
How to set the environment variable (examples):

PowerShell (current session):
$env:EXTERNAL_API_KEY = 'your_real_key_here'

PowerShell (persist for future sessions):
setx EXTERNAL_API_KEY "your_real_key_here"

Apache (httpd.conf / vhost):
SetEnv EXTERNAL_API_KEY "your_real_key_here"

NGINX + php-fpm: export EXTERNAL_API_KEY in the php-fpm env or systemd unit, then restart php-fpm.

Alternatively, keep a local-only `config/external_api.php` that defines the key, but add it to .gitignore.
*/

?>
