
<?php
// KudoPixel - Admin Module - Update Settings Handler
// The main control panel for the entire KudoVerse!

/**
 * Updates a specific site setting.
 *
 * SECURITY: This is a protected endpoint. It requires an admin user_id.
 * Expects a POST request with a JSON body containing:
 * - user_id (of the admin)
 * - setting_key (e.g., 'registration_enabled')
 * - setting_value (e.g., 'true' or 'false')
 */

require_once __DIR__ . '/../../../core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->setting_key) || !isset($data->setting_value)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Admin User ID, Setting Key, and Setting Value are required.']);
    exit();
}

$current_user_id = filter_var($data->user_id, FILTER_VALIDATE_INT);
$setting_key = htmlspecialchars(strip_tags($data->setting_key));
$setting_value = htmlspecialchars(strip_tags($data->setting_value));

try {
    // Check if the user is an admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to perform this action.']);
        exit();
    }

    // --- If we are here, the user is an admin! ---
    // Use INSERT ... ON DUPLICATE KEY UPDATE to create or update the setting.
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$setting_key, $setting_value, $setting_value]);

    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => "Setting '{$setting_key}' has been updated."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
