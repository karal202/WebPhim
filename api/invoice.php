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
require_once '../app/models/InvoiceModel.php';

session_start();

if (!isset($conn)) {
    error_log("invoice.php - Database connection not established");
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: Kết nối cơ sở dữ liệu thất bại']);
    exit();
}

$invoiceModel = new InvoiceModel($conn);

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
        case 'get_all_invoices':
            $result = $invoiceModel->getAllInvoices();
            echo json_encode($result);
            break;

        case 'get_invoice_by_order_id':
            $orderId = $_GET['order_id'] ?? null;

            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số order_id']);
                exit();
            }

            if (!is_numeric($orderId) || $orderId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID đơn hàng không hợp lệ']);
                exit();
            }

            $result = $invoiceModel->getInvoiceByOrderId($orderId);
            echo json_encode($result);
            break;

        case 'get_transaction_details':
            $invoiceId = $_GET['invoice_id'] ?? null;

            if (!$invoiceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số invoice_id']);
                exit();
            }

            if (!is_numeric($invoiceId) || $invoiceId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID hóa đơn không hợp lệ']);
                exit();
            }

            $result = $invoiceModel->getTransactionDetails($invoiceId);
            echo json_encode($result);
            break;

        case 'update_invoice_status':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để thực hiện thao tác này']);
                exit();
            }

            $invoiceId = $_POST['invoice_id'] ?? null;
            $status = $_POST['status'] ?? null;

            if (!$invoiceId || !$status) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số invoice_id hoặc status']);
                exit();
            }

            if (!is_numeric($invoiceId) || $invoiceId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID hóa đơn không hợp lệ']);
                exit();
            }

            if (!in_array($status, ['pending', 'completed', 'cancelled'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Trạng thái không hợp lệ']);
                exit();
            }

            $userId = $_SESSION['user_id'];
            $result = $invoiceModel->updateInvoiceStatus($invoiceId, $status, $userId);
            echo json_encode($result);
            break;

        case 'delete_invoice':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để thực hiện thao tác này']);
                exit();
            }

            $invoiceId = $_POST['invoice_id'] ?? null;

            if (!$invoiceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số invoice_id']);
                exit();
            }

            if (!is_numeric($invoiceId) || $invoiceId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID hóa đơn không hợp lệ']);
                exit();
            }

            $result = $invoiceModel->deleteInvoice($invoiceId);
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'data' => [], 'message' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("invoice.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

ob_end_flush();
exit();
?>