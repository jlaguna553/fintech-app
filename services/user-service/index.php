<?php

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$host = 'mysql';
$db = 'fintech';
$user = 'root';
$pass = 'root';
// Esto intentará leer la variable de Docker primero
$secret = getenv('JWT_SECRET') ?: "esta_clave_debe_tener_mas_de_32_caracteres_minimo";

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$data = json_decode(file_get_contents("php://input"), true);


// 📝 REGISTER
if ($method === 'POST' && $uri === '/register') {

    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);

        echo json_encode(["status" => "user created"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error", "details" => $e->getMessage()]);
        #echo json_encode(["error" => "User exists"]);
    }

    exit;
}


// 🔐 LOGIN
if ($method === 'POST' && ($uri === '/login' || $uri === 'login')) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(["error" => "Email and password are required"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificación de usuario y password hash
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            "error" => "Invalid credentials",
            "debug" => "Check if password in DB is a valid BCRYPT hash"
        ]);
        exit;
    }

    $payload = [
        "user_id" => $user['id'],
        "email" => $user['email'],
        "iat" => time(),
        "exp" => time() + 3600
    ];

    try {
        $jwt = JWT::encode($payload, $secret, 'HS256');
        echo json_encode(["token" => $jwt]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Token generation failed", "details" => $e->getMessage()]);
    }
    exit;
}


// 🧪 TEST PROTEGIDO
if ($method === 'GET' && $uri === '/me') {

    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        echo json_encode(["error" => "No token"]);
        exit;
    }

    $token = str_replace("Bearer ", "", $headers['Authorization']);

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        echo json_encode([
            "message" => "Access granted",
            "user" => $decoded
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Invalid token"]);
    }

    exit;
}


// DEFAULT
echo json_encode(["service" => "user-service"]);
