<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'error' => 'Méthode non autorisée'
    ]);

    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') include 'lister.php';
 
// Le fichier de données est stocké HORS de public_html (non accessible depuis le web)
$dir  = __DIR__ . '/../data';
$file = $dir . '/messages.json';
if (!is_dir($dir)) mkdir($dir, 0750, true);
 
$fp = fopen($file, 'c+');
if (!$fp) {
    http_response_code(500);
    exit(json_encode(['error' => 'fichier inaccessible']));
}
 
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
 
// Verrou : exclusif pour écrire, partagé pour lire (évite les écritures simultanées)
flock($fp, $isPost ? LOCK_EX : LOCK_SH);
$messages = json_decode(stream_get_contents($fp), true) ?: [];
 
if ($isPost) {
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $email = $data['email'] ?? null;
    $gender = $data['gender'] ?? null;
    $message = $data['message'] ?? null;
    $subject = $data['subject'] ?? null;
    $company = $data['company'] ?? null;
    $lastname = $data['lastname'] ?? null;
    $firstname = $data['firstname'] ?? null;
    $isCompany = $data['is_company'] ?? null;
 
    $messages[] = [
        'id' => count($messages) + 1,
        'email' => $data['email'],
        'gender' => $data['gender'],
        'message' => $data['message'],
        'subject' => $data['subject'],
        'company' => $data['company'],
        'lastname' => $data['lastname'],
        'firstname' => $data['firstname'],
        'isCompany' => $data['is_company'],
        'date' => date('c')
    ];
 
    // On réécrit tout le fichier
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    http_response_code(201);
}
 
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
