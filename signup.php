<?php
// signup.php
header('Content-Type: application/json');

// ----------------- DATABASE CONFIGURATION -----------------
$host = 'localhost';
$dbname = 'ewallet';
$username = 'root';
$password = ''; // Default XAMPP password is empty. Change if you set a DB password.

try {
    // Connect to MySQL using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the JSON sent from the frontend
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON payload.');
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Server-side Validation
    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception('All fields are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters.');
    }

    // Check if email already exists in the database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        throw new Exception('This email is already registered.');
    }

    // Hash the password (NEVER store plain text passwords!)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user into the database
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Welcome $name! Your account has been created. Please log in."
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>