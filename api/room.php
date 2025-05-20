<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../app/config/config.php';

function sendResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'get_all':
            $sql = "SELECT id, id_room, name, capacity, status FROM rooms";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $rooms);
            break;

        case 'get':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                sendResponse(false, [], 'ID phòng không hợp lệ');
            }
            $sql = "SELECT id, id_room, name, capacity, status FROM rooms WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($room) {
                sendResponse(true, $room);
            } else {
                sendResponse(false, [], 'Phòng không tồn tại');
            }
            break;

        default:
            sendResponse(false, [], 'Hành động không hợp lệ');
    }
} catch (Exception $e) {
    sendResponse(false, [], 'Lỗi hệ thống: ' . $e->getMessage());
}
?>