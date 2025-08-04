
<?php
// KudoPixel - Community Module - Vote Handler (V2 - With XP Rewards!)

require_once __DIR__ . '/../../../core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// --- Validation ---
// IMPORTANT: We now also need the ID of the user who is voting!
if (!isset($data->idea_id) || !isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Idea ID and User ID are required to vote.']);
    exit();
}

$idea_id = filter_var($data->idea_id, FILTER_VALIDATE_INT);
$user_id = filter_var($data->user_id, FILTER_VALIDATE_INT);

if (empty($idea_id) || empty($user_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'A valid Idea ID and User ID are required.']);
    exit();
}

try {
    // --- Cast the Vote! ---
    $stmt = $pdo->prepare("UPDATE ideas SET votes = votes + 1 WHERE id = ?");
    $stmt->execute([$idea_id]);

    if ($stmt->rowCount() > 0) {
        // --- NEW: Give the user 10 XP for voting! ---
        $xp_reward = 10;
        $stmt = $pdo->prepare("UPDATE user_profiles SET xp = xp + ? WHERE user_id = ?");
        $stmt->execute([$xp_reward, $user_id]);
        
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => "Vote counted! You earned {$xp_reward} XP!"]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Idea not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
