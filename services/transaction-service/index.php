<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$host = 'mysql';
$db = 'fintech';
$user = 'root';
$pass = 'root';
$secret = getenv('JWT_SECRET') ?: "cambia_esto_por_un_secret_largo";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    echo json_encode(["error" => "DB Connection failed"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- LOGICA DE DEPOSITO ---
if ($method === 'POST' && ($uri === '/deposit' || $uri === 'deposit')) {
    $headers = getallheaders();
    $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');

    try {
        $userData = JWT::decode($token, new Key($secret, 'HS256'));
        $data = json_decode(file_get_contents("php://input"), true);
        $amount = $data['amount'] ?? 0;

        if ($amount <= 0) {
            echo json_encode(["error" => "Invalid amount", "received" => $amount]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'deposit', 'Deposit via API')");
        $stmt->execute([$userData->user_id, $amount]);

        echo json_encode([
            "status" => "success", 
            "transaction_id" => $pdo->lastInsertId(),
            "message" => "Deposit of $amount for user " . $userData->user_id
        ]);
        exit; // IMPORTANTE: Detener la ejecución aquí
    } catch (Exception $e) {
        echo json_encode(["error" => "Auth/SQL Error", "details" => $e->getMessage()]);
        exit;
    }
}

// --- SI NADA DE LO ANTERIOR COINCIDE, MUESTRA ESTO ---
echo json_encode([
    "service" => "transaction-service",
    "status" => "running",
    "debug" => [
        "method_received" => $method,
        "uri_received" => $uri
    ]
]);
