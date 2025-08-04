
<?php
// KudoPixel - Admin Module - Get Users Handler
// The captain's log, showing a list of all crew members aboard the ship.

/**
 * Fetches a list of all users.
 *
 * SECURITY: This is a protected endpoint. In a real application, we would have
 * a robust session/token system to verify the user is an admin.
 * For now, we will simulate this by requiring a 'user_id' to be sent,
 * and we will check if that user has the 'admin' role.
 */

require_once __DIR__ . '/../../../core/bootstrap.php';

// --- Simple Security Check ---
// We expect the frontend to send the ID of the user making the request.
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
    // Fetch all users and their profile data
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.email, 
            u.role, 
            u.created_at,
            p.xp,
            p.level
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'users' => $users]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
