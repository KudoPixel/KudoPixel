
<?php
// KudoPixel - Admin Module - Get Ideas Handler
// The command center for viewing and managing all community suggestions.

/**
 * Fetches a complete list of all ideas for the admin dashboard.
 *
 * SECURITY: This is a protected endpoint. It performs the same admin check
 * as the getUsers handler to ensure only authorized personnel can access it.
 */

require_once __DIR__ . '/../../../core/bootstrap.php';

// --- Simple Security Check ---
if (!isset($_GET['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);

try {
    // Check if the user is an admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view this content.']);
        exit();
    }

    // --- If we are here, the user is an admin! ---
    // Fetch all ideas, joining with the users table to get the submitter's username.
    $stmt = $pdo->prepare("
        SELECT 
            i.id, 
            i.title, 
            i.description, 
            i.votes, 
            i.created_at,
            u.username as author
        FROM ideas i
        JOIN users u ON i.user_id = u.id
        ORDER BY i.created_at DESC
    ");
    $stmt->execute();
    $ideas = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'ideas' => $ideas]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
