<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
function require_role($roles) {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', (array)$roles, true)) {
        http_response_code(403);
        exit("Access denied.");
    }
}
function notify_user($conn, $userId, $type, $content, $link = '') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id,type,content,link) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $userId, $type, $content, $link);
    $stmt->execute();
}
function districts($conn) {
    return $conn->query("SELECT district_id,district_name FROM districts ORDER BY district_name");
}
