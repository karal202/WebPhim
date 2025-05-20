<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../app/config/config.php';

function respond($data) {
    echo json_encode($data);
    exit;   
}

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'get_all') {
        $stmt = $conn->prepare("SELECT * FROM genres");
        $stmt->execute();
        $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond($genres);
    } else {
        respond(array('error' => 'Invalid action'));
    }
} catch (Exception $e) {
    error_log("Error in genre.php: " . $e->getMessage());
    respond(array('error' => 'Lỗi server: ' . $e->getMessage()));
}
?>