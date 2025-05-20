<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../app/config/config.php';
require_once '../app/controller/ServiceController.php';

session_start();

if (!isset($conn)) {
    error_log("service.php - Database connection not established");
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: Kết nối cơ sở dữ liệu thất bại']);
    exit();
}

$serviceController = new ServiceController($conn);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ob_start();

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu action']);
        exit();
    }

    switch ($action) {
        case 'get_all':
            $result = $serviceController->getAll();
            error_log("service.php - get_all result: " . json_encode($result));
            echo json_encode($result);
            break;

        case 'get_by_id':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số id']);
                exit();
            }
            $result = $serviceController->getById($id);
            error_log("service.php - get_by_id result: " . json_encode($result));
            echo json_encode($result);
            break;

        case 'add':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            error_log("service.php - add data: " . json_encode($data));
            $result = $serviceController->add($data, $_FILES);
            error_log("service.php - add result: " . json_encode($result));
            echo json_encode($result);
            break;

        case 'update':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            error_log("service.php - update data: " . json_encode($data));
            $result = $serviceController->update($data, $_FILES);
            error_log("service.php - update result: " . json_encode($result));
            echo json_encode($result);
            break;

        case 'delete':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = $data['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số id']);
                exit();
            }
            $result = $serviceController->delete($id);
            error_log("service.php - delete result: " . json_encode($result));
            echo json_encode($result);
            break;

        case 'upload_image':
            if (!isset($_FILES['image'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Không có file được tải lên']);
                exit();
            }
            $result = $serviceController->uploadImage($_FILES);
            error_log("service.php - upload_image result: " . json_encode($result));
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'data' => [], 'message' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("service.php - Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

ob_end_flush();
exit();
?>