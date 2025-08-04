
<?php
// KudoPixel - Auth Module - Login Handler (V2 - With Secure Sessions!)

require_once __DIR__ . '/../../../core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// --- Validation ---
if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit();
}

$email = $data->email;
$password = $data->password;

try {
    // Find the user by their email address
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // --- The Magic Check: Verify the Password ---
    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct! The gate opens!

        // --- NEW: Issue the Secure Digital ID Card! ---
        // We store the user's info in the server's secure session memory.
        // The user only gets a random cookie, they can't see or change this data.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful! Welcome back, ' . htmlspecialchars($user['username']) . '!',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);

    } else {
        // Wrong email or password!
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
