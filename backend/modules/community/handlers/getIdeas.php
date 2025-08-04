
<?php
// KudoPixel - Community Module - Get Ideas Handler
// The town crier, announcing all the brilliant ideas from the KudoVerse!

/**
 * Handles fetching a list of all submitted ideas.
 *
 * This script expects a GET request.
 * It joins the 'ideas' and 'users' tables to include the username of the submitter.
 * Ideas are ordered by the number of votes in descending order, so the best rise to the top.
 */

// The $pdo object is available because it was created in bootstrap.php
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection is not available.']);
    exit();
}

// We only accept GET requests for fetching data
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Only GET method is allowed.']);
    exit();
}

// --- Fetch All Ideas from the Treasure Chest ---
try {
    // This is a powerful SQL query!
    // It selects all ideas, and also grabs the username of the person who submitted it
    // by joining the 'users' table.
    $stmt = $pdo->prepare("
        SELECT 
            ideas.id, 
            ideas.title, 
            ideas.description, 
            ideas.votes, 
            ideas.created_at,
            users.username 
        FROM ideas
        JOIN users ON ideas.user_id = users.id
        ORDER BY ideas.votes DESC, ideas.created_at DESC
    ");

    $stmt->execute();

    // Fetch all the ideas into an array
    $ideas = $stmt->fetchAll();

    // Send the list of ideas back to the frontend!
    http_response_code(200); // OK
    echo json_encode([
        'status' => 'success',
        'ideas' => $ideas
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
