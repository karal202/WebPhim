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
require_once '../app/models/OrderModel.php';

session_start();

if (!isset($conn)) {
    error_log("order.php - Database connection not established");
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: Kết nối cơ sở dữ liệu thất bại']);
    exit();
}

$orderModel = new OrderModel($conn);

$orderModel->cleanupPendingOrders();

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
        case 'get_bookings':
            $date = $_POST['date'] ?? null;
            $movieId = $_POST['movie_id'] ?? null;

            if (!$date || !$movieId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số date hoặc movie_id']);
                exit();
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Ngày không hợp lệ']);
                exit();
            }

            if (!is_numeric($movieId) || $movieId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID phim không hợp lệ']);
                exit();
            }

            $result = $orderModel->getBookings($date, $movieId);
            echo json_encode($result);
            break;

        case 'get_available_seats':
            $roomId = $_POST['room_id'] ?? null;
            $date = $_POST['date'] ?? null;
            $time = $_POST['time'] ?? null;

            if (!$roomId || !$date || !$time) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số room_id, date hoặc time']);
                exit();
            }

            if (!is_numeric($roomId) || $roomId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID phòng không hợp lệ']);
                exit();
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Ngày không hợp lệ']);
                exit();
            }

            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $time)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thời gian không hợp lệ']);
                exit();
            }

            $result = $orderModel->getAvailableSeats($roomId, $date, $time);
            echo json_encode($result);
            break;

        case 'create_order':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để đặt vé']);
                exit();
            }

            $userId = $_SESSION['user_id'];
            $movieId = $_POST['movie_id'] ?? null;
            $roomId = $_POST['room_id'] ?? null;
            $date = $_POST['date'] ?? null;
            $time = $_POST['time'] ?? null;
            $quantity = $_POST['quantity'] ?? null;
            $totalAmount = $_POST['total_amount'] ?? null;
            $seatIds = isset($_POST['seat_ids']) ? json_decode($_POST['seat_ids'], true) : [];
            $services = isset($_POST['services']) ? json_decode($_POST['services'], true) : [];

            if (!$movieId || !$roomId || !$date || !$time || !$quantity || !$totalAmount || empty($seatIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số bắt buộc']);
                exit();
            }

            if (!is_numeric($movieId) || $movieId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID phim không hợp lệ']);
                exit();
            }

            if (!is_numeric($roomId) || $roomId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID phòng không hợp lệ']);
                exit();
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Ngày không hợp lệ']);
                exit();
            }

            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $time)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thời gian không hợp lệ']);
                exit();
            }

            if (!is_numeric($quantity) || $quantity <= 0 || $quantity != count($seatIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Số lượng ghế không hợp lệ']);
                exit();
            }

            if (!is_numeric($totalAmount) || $totalAmount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Tổng tiền không hợp lệ']);
                exit();
            }

            if (!is_array($seatIds) || empty($seatIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Danh sách ghế không hợp lệ']);
                exit();
            }

            $result = $orderModel->createOrder($userId, $movieId, $roomId, $date, $time, $quantity, $totalAmount, $seatIds, $services);
            echo json_encode($result);
            break;

        case 'update_order_status':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này']);
                exit();
            }

            $orderId = $_POST['order_id'] ?? null;
            $status = $_POST['status'] ?? null;

            if (!$orderId || !$status) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Thiếu tham số order_id hoặc status']);
                exit();
            }

            if (!is_numeric($orderId) || $orderId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'ID đơn hàng không hợp lệ']);
                exit();
            }

            if ($status !== 'completed' && $status !== 'cancelled') {
                http_response_code(400);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Trạng thái không hợp lệ']);
                exit();
            }

            $sql = "SELECT user_id FROM orders WHERE id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng']);
                exit();
            }

            if ($order['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Bạn không có quyền cập nhật trạng thái đơn hàng này']);
                exit();
            }

            $result = $orderModel->updateOrderStatus($orderId, $status);
            echo json_encode($result);
            break;

        case 'check_order_status':
            $orderId = $_POST['order_id'] ?? null;

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

            $result = $orderModel->checkOrderStatus($orderId);
            echo json_encode($result);
            break;

        case 'get_user_orders':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để xem đơn hàng']);
                exit();
            }

            $userId = $_SESSION['user_id'];
            $result = $orderModel->getUserOrders($userId);
            echo json_encode($result);
            break;

        case 'get_order_details':
            $orderId = $_POST['order_id'] ?? null;

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

            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để xem chi tiết đơn hàng']);
                exit();
            }

            $sql = "SELECT user_id FROM orders WHERE id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng']);
                exit();
            }

            if ($order['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Bạn không có quyền xem chi tiết đơn hàng này']);
                exit();
            }

            $result = $orderModel->getOrderDetails($orderId);
            echo json_encode($result);
            break;

        case 'get_transaction_details':
            $orderId = $_POST['order_id'] ?? null;

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

            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Vui lòng đăng nhập để xem chi tiết giao dịch']);
                exit();
            }

            $sql = "SELECT user_id FROM orders WHERE id = :order_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng']);
                exit();
            }

            if ($order['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Bạn không có quyền xem chi tiết giao dịch này']);
                exit();
            }

            $sql = "
                SELECT 
                    COALESCE(vnp_txn_ref, '') AS vnp_txn_ref, 
                    COALESCE(vnp_response_code, '') AS vnp_response_code,
                    COALESCE(vnp_transaction_no, '') AS vnp_transaction_no, 
                    COALESCE(vnp_amount, 0) AS vnp_amount,
                    COALESCE(momo_txn_ref, '') AS momo_txn_ref, 
                    COALESCE(momo_result_code, '') AS momo_result_code,
                    COALESCE(momo_trans_id, '') AS momo_trans_id, 
                    COALESCE(momo_amount, 0) AS momo_amount,
                    COALESCE(created_at, NOW()) AS created_at
                FROM transactions
                WHERE order_id = :order_id
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                echo json_encode([
                    'success' => true,
                    'data' => $transaction,
                    'message' => 'Lấy chi tiết giao dịch thành công'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => 'Chưa có thông tin giao dịch'
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'data' => [], 'message' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("order.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

ob_end_flush();
exit();
?>