
<?php
// KudoPixel Modular Backend - config/app.php
// The central treasure chest for all our secrets and settings.

// --- Application Environment ---
// Set to 'development' to see detailed errors, 'production' to hide them.
define('APP_ENV', 'development');

// --- Database Credentials ---
define('DB_HOST', 'YOUR_DATABASE_HOST');
define('DB_USER', 'YOUR_DATABASE_USERNAME');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('DB_NAME', 'YOUR_DATABASE_NAME');

// --- YouTube API Credentials ---
define('YOUTUBE_API_KEY', 'YOUR_YOUTUBE_API_KEY');
define('YOUTUBE_CHANNEL_ID', 'YOUR_YOUTUBE_CHANNEL_ID');

// --- Minecraft Server Info ---
define('MC_SERVER_IP', 'YOUR_SERVER_IP');
define('MC_SERVER_PORT', 25565);

// --- Security Salt ---
// IMPORTANT: Change this to a long, random string!
// It's used for creating secure tokens in the future.
define('SECURITY_SALT', 'change-this-to-a-super-long-random-string-please');

?>
