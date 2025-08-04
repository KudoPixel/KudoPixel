
<?php
// KudoPixel - Admin Module - Delete Idea Handler
// The commander's final verdict, removing ideas from the board.

/**
 * Handles the deletion of a specific idea.
 *
 * SECURITY: This is a protected endpoint. It requires an admin user_id.
 * Expects a POST request with a JSON body containing:
 * - user_id (of the admin)
 * - idea_id (of the idea to be deleted)
 */

require_once __DIR__ . '/../../../core/bootstrap.php';

// --- Simple Security Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || !isset($data->idea_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Admin User ID and Idea ID are required.']);
    exit();
}

$current_user_id = filter_var($data->user_id, FILTER_VALIDATE_INT);
$idea_id_to_delete = filter_var($data->idea_id, FILTER_VALIDATE_INT);

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
    // Proceed with deleting the idea
    $stmt = $pdo->prepare("DELETE FROM ideas WHERE id = ?");
    $stmt->execute([$idea_id_to_delete]);

    // Check if any row was actually deleted
    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Idea has been successfully deleted.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Idea not found or already deleted.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
