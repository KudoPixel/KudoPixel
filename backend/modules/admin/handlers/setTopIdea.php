
<?php
// KudoPixel - Admin Module - Set Top Idea Handler
// The commander's scepter, used to knight the best idea of the week.

/**
 * Sets a specific idea as the "Top Idea of the Week".
 *
 * SECURITY: This is a protected endpoint. It requires an admin user_id.
 * Expects a POST request with a JSON body containing:
 * - user_id (of the admin)
 * - idea_id (of the idea to feature)
 */

require_once __DIR__ . '/../../../core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->idea_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Admin User ID and Idea ID are required.']);
    exit();
}

$current_user_id = filter_var($data->user_id, FILTER_VALIDATE_INT);
$top_idea_id = filter_var($data->idea_id, FILTER_VALIDATE_INT);

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
    // Use INSERT ... ON DUPLICATE KEY UPDATE.
    // This will create the setting if it doesn't exist, or update it if it does.
    // It's a very efficient and clean way to handle settings.
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value) 
        VALUES ('top_idea_id', ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$top_idea_id, $top_idea_id]);

    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => 'Idea #' . $top_idea_id . ' is now the Top Idea of the Week!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
