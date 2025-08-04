
<?php
// KudoPixel - Auth Module - Register Handler (V3 - With Auto-Login!)

require_once __DIR__ . '/../../../core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit();
}

// --- Check if registration is enabled by the admin ---
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'registration_enabled' LIMIT 1");
    $stmt->execute();
    $setting = $stmt->fetch();

    if ($setting && $setting['setting_value'] === 'false') {
        http_response_code(503); // Service Unavailable
        echo json_encode(['status' => 'error', 'message' => 'User registration is temporarily disabled.']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not verify registration status.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// --- Validation ---
if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username, email, and password are required.']);
    exit();
}

$username = htmlspecialchars(strip_tags($data->username));
$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
$password = $data->password;

if (empty($username) || empty($email) || strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill all fields. Password must be at least 8 characters.']);
    exit();
}

// --- Hashing Password ---
$password_hash = password_hash($password, PASSWORD_BCRYPT);

try {
    // --- Check for duplicates ---
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email or username is already taken.']);
        exit();
    }

    // --- Create the User ---
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash]);
    
    $new_user_id = $pdo->lastInsertId();

    // --- Create the User's Profile ---
    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, xp, level) VALUES (?, 0, 1)");
    $stmt->execute([$new_user_id]);

    // --- NEW: Automatically Log In the New User! ---
    // We issue their secure digital ID card right away.
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['username'] = $username;
    // New users always start with the 'user' role.
    $_SESSION['role'] = 'user';

    // Send a welcome message!
    http_response_code(201);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Welcome to the KudoVerse! Your account has been created and you are now logged in.',
        'user' => [
            'id' => $new_user_id,
            'username' => $username,
            'role' => 'user'
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
