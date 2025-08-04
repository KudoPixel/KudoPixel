
<?php
// KudoPixel - Server Module - Get Status Handler
// The all-seeing eye that watches over our Minecraft world, now with a photographic memory!

/**
 * Fetches the current status of the Minecraft server.
 *
 * This script uses a caching mechanism to avoid spamming the server with requests.
 * It reads from a cache file if it's recent, otherwise, it pings the server
 * for fresh data and updates the cache.
 */

// Fire up the main engine! This loads our config and database connection.
require_once __DIR__ . '/../../../core/bootstrap.php';

// --- CACHING CONFIGURATION ---
$cache_file = __DIR__ . '/../../../cache/server_status.json';
$cache_time = 60; // Cache the result for 60 seconds

// --- Check if a recent cache file exists ---
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    // If yes, serve the data directly from the cache! This is super fast.
    header('Content-Type: application/json');
    readfile($cache_file);
    exit();
}

// --- If cache is old or doesn't exist, we ping the server ---

// Get server info from our central config file
$server_ip = MC_SERVER_IP;
$server_port = MC_SERVER_PORT;

/**
 * Pings a Minecraft server to get its status.
 * @param string $ip The server IP or domain.
 * @param int $port The server port.
 * @return array The server status.
 */
function getMinecraftServerStatus($ip, $port) {
    $timeout = 2;
    // The '@' suppresses errors if the server is offline, we handle it ourselves.
    $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

    if (!$socket) {
        return ['status' => 'offline', 'players' => ['online' => 0, 'max' => 0]];
    }

    // Send the magic packet to request status
    fwrite($socket, "\xfe\x01");
    $response = fread($socket, 256);
    fclose($socket);

    if (substr($response, 0, 1) != "\xff") {
        return ['status' => 'error', 'message' => 'Invalid response from server.'];
    }

    // Decode the weird response string from the server
    $response = substr($response, 3);
    $response = mb_convert_encoding($response, 'UTF-8', 'UCS-2BE');
    $stats = explode("\x00", $response);

    if (count($stats) < 3) {
        return ['status' => 'error', 'message' => 'Could not parse server response.'];
    }

    return [
        'status' => 'online',
        'motd' => $stats[1],
        'players' => [
            'online' => (int)$stats[2],
            'max' => (int)$stats[3]
        ]
    ];
}

// Get the fresh status from the server
$status = getMinecraftServerStatus($server_ip, $server_port);
$json_status = json_encode($status);

// --- Save the new data to our cache file for the next request! ---
// Ensure the cache directory exists
if (!is_dir(__DIR__ . '/../../../cache')) {
    mkdir(__DIR__ . '/../../../cache');
}
file_put_contents($cache_file, $json_status);

// Send the fresh data to the user
echo $json_status;

?>
