
<?php
// KudoPixel Modular Backend - core/bootstrap.php (V2 - With Sessions!)

// --- Step 1: Start the Session Engine! ---
// This MUST be the very first thing that happens.
// It tells PHP to remember who is logged in.
session_start();

// --- Step 2: Load the Treasure Chest (Configuration) ---
require_once __DIR__ . '/../config/app.php';

// --- Step 3: Set Up Error Reporting based on Environment ---
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// --- Step 4: Set Global Headers ---
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Adjust for production
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle pre-flight requests from browsers
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- Step 5: Prepare the Toolbox (Global Functions & Database) ---
require_once __DIR__ . '/../lib/functions.php';

// Create a global PDO connection object
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Critical Error: Database connection failed.']);
    exit();
}

// --- Step 6: The Magic Router ---
function handle_request($pdo) {
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $api_path = str_replace('/backend/public/', '', $request_uri);
    $parts = explode('/', trim($api_path, '/'));

    $module = $parts[0] ?? null;
    $action = $parts[1] ?? null;

    if (!$module || !$action) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'API endpoint not found.']);
        return;
    }

    $handler_file = __DIR__ . "/../modules/{$module}/handlers/{$action}.php";

    if (file_exists($handler_file)) {
        require_once $handler_file;
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => "Handler for '{$module}/{$action}' not found."]);
    }
}
