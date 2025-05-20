<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/InvoiceModel.php';

class OrderModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function cleanupPendingOrders() {
        try {
            // Chỉ xóa các đơn hàng pending không có payment_memo (chưa bắt đầu thanh toán)
            $sql = "SELECT id FROM orders WHERE status = 'pending' AND created_at < NOW() - INTERVAL 15 MINUTE AND payment_memo IS NULL";
            $stmt = $this->conn->query($sql);
            $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            foreach ($orderIds as $orderId) {
                $logSql = "INSERT INTO deleted_orders_log (order_id) VALUES (:order_id)";
                $logStmt = $this->conn->prepare($logSql);
                $logStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $logStmt->execute();

                $this->conn->beginTransaction();
                $this->conn->prepare("DELETE FROM order_seats WHERE order_id = :order_id")
                          ->execute(['order_id' => $orderId]);
                $this->conn->prepare("DELETE FROM order_services WHERE order_id = :order_id")
                          ->execute(['order_id' => $orderId]);
                $this->conn->prepare("DELETE FROM tickets WHERE order_id = :order_id")
                          ->execute(['order_id' => $orderId]);
                $this->conn->prepare("DELETE FROM transactions WHERE order_id = :order_id")
                          ->execute(['order_id' => $orderId]);
                $this->conn->prepare("DELETE FROM orders WHERE id = :order_id")
                          ->execute(['order_id' => $orderId]);
                $this->conn->commit();

                error_log("CleanupPendingOrders - Successfully deleted orderId: $orderId");
            }
            return [
                'success' => true,
                'data' => [],
                'message' => 'Dọn dẹp đơn hàng pending thành công'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("CleanupPendingOrders - Error: " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'message' => 'Lỗi hệ thống khi dọn dẹp đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    public function getBookings($date, $movieId) {
        try {
            error_log("OrderModel::getBookings - Nhận date: '$date', movie_id: '$movieId'");

            $sql = "
                SELECT rd.room_id, rd.date, rd.time, r.capacity,
                       COALESCE(SUM(o.quantity), 0) AS total_tickets
                FROM room_detail rd
                LEFT JOIN orders o ON rd.movie_id = o.movie_id 
                    AND rd.room_id = o.room_id 
                    AND rd.date = o.date 
                    AND rd.time = o.time 
                    AND o.status = 'completed'
                JOIN rooms r ON rd.room_id = r.id
                WHERE rd.date = :date AND rd.movie_id = :movie_id
                GROUP BY rd.room_id, rd.date, rd.time
                ORDER BY rd.time
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':date', $date, PDO::PARAM_STR);
            $stmt->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("OrderModel::getBookings - Kết quả: " . print_r($results, true));
            return [
                'success' => true,
                'data' => $results,
                'message' => empty($results) ? 'Không có dữ liệu khung giờ cho ngày này' : 'Lấy thông tin lịch chiếu thành công'
            ];
        } catch (Exception $e) {
            error_log("OrderModel::getBookings - Lỗi: " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'message' => 'Lỗi hệ thống khi lấy lịch chiếu: ' . $e->getMessage()
            ];
        }
    }

    public function getAvailableSeats($roomId, $date, $time) {
        try {
            error_log("OrderModel::getAvailableSeats - Nhận dữ liệu: room_id=$roomId, date='$date', time='$time'");

            $sqlSeats = "
                SELECT id, seat_number
                FROM seats
                WHERE room_id = :room_id
            ";
            $stmtSeats = $this->conn->prepare($sqlSeats);
            $stmtSeats->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtSeats->execute();
            $seats = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);

            if (empty($seats)) {
                error_log("OrderModel::getAvailableSeats - Không tìm thấy ghế nào cho room_id=$roomId");
                return [
                    'success' => false,
                    'data' => [],
                    'message' => 'Không tìm thấy ghế nào cho phòng này'
                ];
            }

            $seatData = [];
            foreach ($seats as $seat) {
                $seatData[] = [
                    'id' => $seat['id'],
                    'seat_number' => $seat['seat_number'],
                    'status' => 'available'
                ];
            }

            // Check for seats booked by both completed and pending orders
            $sqlBooked = "
                SELECT os.seat_id
                FROM orders o
                JOIN order_seats os ON o.id = os.order_id
                WHERE o.room_id = :room_id 
                    AND o.date = :date 
                    AND o.time = :time 
                    AND (o.status = 'completed' OR o.status = 'pending')
            ";
            $stmtBooked = $this->conn->prepare($sqlBooked);
            $stmtBooked->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtBooked->bindValue(':date', $date, PDO::PARAM_STR);
            $stmtBooked->bindValue(':time', $time, PDO::PARAM_STR);
            $stmtBooked->execute();
            $bookedSeats = $stmtBooked->fetchAll(PDO::FETCH_COLUMN);

            error_log("OrderModel::getAvailableSeats - Ghế đã đặt (completed or pending): " . print_r($bookedSeats, true));

            foreach ($seatData as &$seat) {
                if (in_array($seat['id'], $bookedSeats)) {
                    $seat['status'] = 'booked';
                }
            }

            return [
                'success' => true,
                'data' => $seatData,
                'message' => 'Lấy danh sách ghế thành công'
            ];
        } catch (Exception $e) {
            error_log("OrderModel::getAvailableSeats - Lỗi: " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'message' => 'Lỗi hệ thống khi lấy danh sách ghế: ' . $e->getMessage()
            ];
        }
    }

    public function createOrder($userId, $movieId, $roomId, $date, $time, $quantity, $totalAmount, $seatIds, $services) {
        try {
            $this->conn->beginTransaction();

            // Check for existing order with the same user, movie, room, date, time, and seats
            $seatParams = [];
            $placeholders = [];
            foreach ($seatIds as $index => $seatId) {
                $paramName = ":seat_id_$index";
                $seatParams[$paramName] = $seatId;
                $placeholders[] = $paramName;
            }

            $sqlCheckOrder = "
                SELECT o.id
                FROM orders o
                JOIN order_seats os ON o.id = os.order_id
                WHERE o.user_id = :user_id
                    AND o.movie_id = :movie_id
                    AND o.room_id = :room_id
                    AND o.date = :date
                    AND o.time = :time
                    AND o.status = 'pending'
                    AND os.seat_id IN (" . implode(',', $placeholders) . ")
                GROUP BY o.id
                HAVING COUNT(DISTINCT os.seat_id) = :seat_count
            ";
            $stmtCheckOrder = $this->conn->prepare($sqlCheckOrder);
            $stmtCheckOrder->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheckOrder->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
            $stmtCheckOrder->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtCheckOrder->bindValue(':date', $date, PDO::PARAM_STR);
            $stmtCheckOrder->bindValue(':time', $time, PDO::PARAM_STR);
            $stmtCheckOrder->bindValue(':seat_count', count($seatIds), PDO::PARAM_INT);
            foreach ($seatParams as $paramName => $seatId) {
                $stmtCheckOrder->bindValue($paramName, $seatId, PDO::PARAM_INT);
            }
            $stmtCheckOrder->execute();
            $existingOrderId = $stmtCheckOrder->fetchColumn();

            if ($existingOrderId) {
                // Check if an invoice already exists for this order
                $sqlCheckInvoice = "SELECT id FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
                $stmtCheckInvoice = $this->conn->prepare($sqlCheckInvoice);
                $stmtCheckInvoice->bindValue(':order_id', $existingOrderId, PDO::PARAM_INT);
                $stmtCheckInvoice->execute();
                $existingInvoiceId = $stmtCheckInvoice->fetchColumn();

                $this->conn->commit();
                return [
                    'success' => true,
                    'data' => ['order_id' => $existingOrderId, 'invoice_id' => $existingInvoiceId ?: null],
                    'message' => 'Đơn hàng đã tồn tại, không tạo mới'
                ];
            }

            // Validate movie and room
            $sqlMovie = "SELECT title FROM movies WHERE id = :movie_id";
            $stmtMovie = $this->conn->prepare($sqlMovie);
            $stmtMovie->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
            $stmtMovie->execute();
            $movie = $stmtMovie->fetch(PDO::FETCH_ASSOC);
            if (!$movie) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Phim không tồn tại'];
            }

            $sqlRoom = "SELECT name FROM rooms WHERE id = :room_id";
            $stmtRoom = $this->conn->prepare($sqlRoom);
            $stmtRoom->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtRoom->execute();
            $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);
            if (!$room) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Phòng không tồn tại'];
            }

            if (empty($seatIds)) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Danh sách ghế không hợp lệ'];
            }

            // Check seat availability for both completed and pending orders
            $sqlCheckSeats = "
                SELECT s.id
                FROM seats s
                LEFT JOIN order_seats os ON s.id = os.seat_id
                LEFT JOIN orders o ON os.order_id = o.id
                    AND o.date = :date
                    AND o.time = :time
                    AND o.room_id = :room_id
                    AND (o.status = 'completed' OR o.status = 'pending')
                WHERE s.id IN (" . implode(',', $placeholders) . ")
                    AND s.room_id = :room_id
                    AND o.id IS NULL
            ";
            $stmtCheckSeats = $this->conn->prepare($sqlCheckSeats);
            foreach ($seatParams as $paramName => $seatId) {
                $stmtCheckSeats->bindValue($paramName, $seatId, PDO::PARAM_INT);
            }
            $stmtCheckSeats->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtCheckSeats->bindValue(':date', $date, PDO::PARAM_STR);
            $stmtCheckSeats->bindValue(':time', $time, PDO::PARAM_STR);
            $stmtCheckSeats->execute();
            $availableSeats = $stmtCheckSeats->fetchAll(PDO::FETCH_COLUMN);

            if (count($availableSeats) !== count($seatIds)) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Một số ghế đã được đặt hoặc đang chờ xử lý'];
            }

            // Create order
            $sqlOrder = "
                INSERT INTO orders (user_id, movie_id, room_id, date, time, quantity, total_amount, status, created_at)
                VALUES (:user_id, :movie_id, :room_id, :date, :time, :quantity, :total_amount, 'pending', NOW())
            ";
            $stmtOrder = $this->conn->prepare($sqlOrder);
            $stmtOrder->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtOrder->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
            $stmtOrder->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            $stmtOrder->bindValue(':date', $date, PDO::PARAM_STR);
            $stmtOrder->bindValue(':time', $time, PDO::PARAM_STR);
            $stmtOrder->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $stmtOrder->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $stmtOrder->execute();
            $orderId = $this->conn->lastInsertId();

            // Insert order seats
            $sqlOrderSeats = "INSERT INTO order_seats (order_id, seat_id) VALUES (:order_id, :seat_id)";
            $stmtOrderSeats = $this->conn->prepare($sqlOrderSeats);
            foreach ($seatIds as $seatId) {
                $stmtOrderSeats->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmtOrderSeats->bindValue(':seat_id', $seatId, PDO::PARAM_INT);
                $stmtOrderSeats->execute();
            }

            // Insert order services
            if (!empty($services)) {
                $sqlOrderServices = "INSERT INTO order_services (order_id, service_id, quantity) VALUES (:order_id, :service_id, :quantity)";
                $stmtOrderServices = $this->conn->prepare($sqlOrderServices);
                foreach ($services as $serviceId => $qty) {
                    if ($qty > 0) {
                        $stmtOrderServices->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                        $stmtOrderServices->bindValue(':service_id', $serviceId, PDO::PARAM_INT);
                        $stmtOrderServices->bindValue(':quantity', $qty, PDO::PARAM_INT);
                        $stmtOrderServices->execute();
                    }
                }
            }

            // Create invoice
            $invoiceModel = new InvoiceModel($this->conn);
            $showTime = "$date $time";
            $invoiceResult = $invoiceModel->createInvoice(
                $orderId,
                $userId,
                $movie['title'],
                $showTime,
                $room['name'],
                $totalAmount,
                'pending',
                null
            );

            if (!$invoiceResult['success']) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Lỗi khi tạo hóa đơn: ' . $invoiceResult['message']];
            }

            $this->conn->commit();
            error_log("OrderModel::createOrder - Created orderId: $orderId with invoiceId: " . $invoiceResult['data']['invoice_id']);
            return [
                'success' => true,
                'data' => ['order_id' => $orderId, 'invoice_id' => $invoiceResult['data']['invoice_id']],
                'message' => 'Tạo đơn hàng và hóa đơn thành công'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("OrderModel::createOrder - Lỗi: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi tạo đơn hàng: ' . $e->getMessage()];
        }
    }

    public function updateOrderStatus($orderId, $status) {
        try {
            $this->conn->beginTransaction();

            // Check current status of the order
            $sqlCheckOrder = "SELECT status FROM orders WHERE id = :order_id";
            $stmtCheckOrder = $this->conn->prepare($sqlCheckOrder);
            $stmtCheckOrder->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtCheckOrder->execute();
            $currentOrderStatus = $stmtCheckOrder->fetchColumn();

            if ($currentOrderStatus === false) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng'];
            }

            // Check current status of the invoice
            $sqlCheckInvoice = "SELECT id, payment_status FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
            $stmtCheckInvoice = $this->conn->prepare($sqlCheckInvoice);
            $stmtCheckInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtCheckInvoice->execute();
            $currentInvoice = $stmtCheckInvoice->fetch(PDO::FETCH_ASSOC);

            if ($currentInvoice === false) {
                // Nếu không tìm thấy hóa đơn, tạo mới một hóa đơn với trạng thái phù hợp
                $sqlOrderDetails = "
                    SELECT o.user_id, m.title AS movie_title, rd.date, rd.time, r.name AS room_name, o.total_amount
                    FROM orders o
                    JOIN room_detail rd ON o.movie_id = rd.movie_id AND o.room_id = rd.room_id AND o.date = rd.date AND o.time = rd.time
                    JOIN movies m ON o.movie_id = m.id
                    JOIN rooms r ON o.room_id = r.id
                    WHERE o.id = :order_id
                ";
                $stmtOrderDetails = $this->conn->prepare($sqlOrderDetails);
                $stmtOrderDetails->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmtOrderDetails->execute();
                $orderDetails = $stmtOrderDetails->fetch(PDO::FETCH_ASSOC);

                if ($orderDetails) {
                    $invoiceModel = new InvoiceModel($this->conn);
                    $showTime = $orderDetails['date'] . ' ' . $orderDetails['time'];
                    $invoiceResult = $invoiceModel->createInvoice(
                        $orderId,
                        $orderDetails['user_id'],
                        $orderDetails['movie_title'],
                        $showTime,
                        $orderDetails['room_name'],
                        $orderDetails['total_amount'],
                        $status,
                        null
                    );

                    if (!$invoiceResult['success']) {
                        $this->conn->rollBack();
                        return ['success' => false, 'data' => [], 'message' => 'Lỗi khi tạo hóa đơn mới: ' . $invoiceResult['message']];
                    }
                } else {
                    $this->conn->rollBack();
                    return ['success' => false, 'data' => [], 'message' => 'Không thể lấy thông tin đơn hàng để tạo hóa đơn'];
                }
            }

            if ($currentOrderStatus === $status) {
                $this->conn->rollBack();
                return ['success' => true, 'data' => [], 'message' => 'Trạng thái đơn hàng đã là ' . $status];
            }

            // Update the order status
            $sqlUpdateOrder = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :order_id";
            $stmtUpdateOrder = $this->conn->prepare($sqlUpdateOrder);
            $stmtUpdateOrder->bindValue(':status', $status, PDO::PARAM_STR);
            $stmtUpdateOrder->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtUpdateOrder->execute();

            // Update the invoice status to match the order status
            $sqlUpdateInvoice = "UPDATE invoices SET payment_status = :status WHERE order_id = :order_id";
            $stmtUpdateInvoice = $this->conn->prepare($sqlUpdateInvoice);
            $stmtUpdateInvoice->bindValue(':status', $status, PDO::PARAM_STR);
            $stmtUpdateInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtUpdateInvoice->execute();

            if ($stmtUpdateOrder->rowCount() > 0 || $stmtUpdateInvoice->rowCount() > 0) {
                $this->conn->commit();
                error_log("OrderModel::updateOrderStatus - Updated orderId: $orderId to status: $status");
                return ['success' => true, 'data' => [], 'message' => 'Cập nhật trạng thái đơn hàng và hóa đơn thành công'];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Không thể cập nhật trạng thái đơn hàng hoặc hóa đơn'];
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("OrderModel::updateOrderStatus - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi cập nhật trạng thái: ' . $e->getMessage()];
        }
    }

    public function checkOrderStatus($orderId) {
        try {
            $sql = "SELECT status FROM orders WHERE id = :order_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $status = $stmt->fetchColumn();

            if ($status === false) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng'];
            }

            // Also check the invoice status for consistency
            $sqlInvoice = "SELECT payment_status FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
            $stmtInvoice = $this->conn->prepare($sqlInvoice);
            $stmtInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtInvoice->execute();
            $invoiceStatus = $stmtInvoice->fetchColumn();

            if ($invoiceStatus === false) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn cho đơn hàng này'];
            }

            if ($status !== $invoiceStatus) {
                error_log("OrderModel::checkOrderStatus - Status mismatch for orderId: $orderId. Order status: $status, Invoice status: $invoiceStatus");
            }

            return ['success' => true, 'data' => ['status' => $status, 'invoice_status' => $invoiceStatus], 'message' => 'Lấy trạng thái đơn hàng thành công'];
        } catch (Exception $e) {
            error_log("OrderModel::checkOrderStatus - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi kiểm tra trạng thái đơn hàng: ' . $e->getMessage()];
        }
    }

    public function getUserOrders($userId) {
        try {
            $sql = "
                SELECT o.id, o.movie_id, o.room_id, o.date, o.time, o.quantity, o.total_amount, o.status,
                       m.title AS movie_title, m.thumbnail AS movie_thumbnail,
                       r.name AS room_name,
                       i.payment_status AS invoice_status
                FROM orders o
                JOIN movies m ON o.movie_id = m.id
                JOIN rooms r ON o.room_id = r.id
                LEFT JOIN invoices i ON o.id = i.order_id
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check for status mismatches
            foreach ($orders as $order) {
                if ($order['status'] !== $order['invoice_status']) {
                    error_log("OrderModel::getUserOrders - Status mismatch for userId: $userId, orderId: {$order['id']}. Order status: {$order['status']}, Invoice status: {$order['invoice_status']}");
                }
            }

            return [
                'success' => true,
                'data' => $orders,
                'message' => empty($orders) ? 'Không có đơn hàng nào' : 'Lấy danh sách đơn hàng thành công'
            ];
        } catch (Exception $e) {
            error_log("OrderModel::getUserOrders - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy danh sách đơn hàng: ' . $e->getMessage()];
        }
    }

    public function getOrderDetails($orderId) {
        try {
            $sqlOrder = "
                SELECT o.id, o.movie_id, o.room_id, o.date, o.time, o.quantity, o.total_amount, o.status,
                       m.title AS movie_title, m.thumbnail AS movie_thumbnail,
                       r.name AS room_name,
                       i.payment_status AS invoice_status,
                       o.user_id
                FROM orders o
                JOIN movies m ON o.movie_id = m.id
                JOIN rooms r ON o.room_id = r.id
                LEFT JOIN invoices i ON o.id = i.order_id
                WHERE o.id = :order_id
            ";
            $stmtOrder = $this->conn->prepare($sqlOrder);
            $stmtOrder->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtOrder->execute();
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy đơn hàng'];
            }

            // Check for status mismatch
            if ($order['status'] !== $order['invoice_status']) {
                error_log("OrderModel::getOrderDetails - Status mismatch for orderId: $orderId. Order status: {$order['status']}, Invoice status: {$order['invoice_status']}");
            }

            $sqlSeats = "SELECT s.seat_number FROM order_seats os JOIN seats s ON os.seat_id = s.id WHERE os.order_id = :order_id";
            $stmtSeats = $this->conn->prepare($sqlSeats);
            $stmtSeats->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtSeats->execute();
            $seats = $stmtSeats->fetchAll(PDO::FETCH_COLUMN);

            $sqlServices = "SELECT os.service_id, os.quantity, s.name FROM order_services os JOIN services s ON os.service_id = s.id WHERE os.order_id = :order_id";
            $stmtServices = $this->conn->prepare($sqlServices);
            $stmtServices->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtServices->execute();
            $servicesRaw = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

            $services = [];
            $serviceDetails = [];
            foreach ($servicesRaw as $service) {
                $services[$service['service_id']] = $service['quantity'];
                $serviceDetails[] = ['id' => $service['service_id'], 'name' => $service['name']];
            }

            $order['seats'] = $seats;
            $order['services'] = $services;
            $order['service_details'] = $serviceDetails;

            return ['success' => true, 'data' => $order, 'message' => 'Lấy chi tiết đơn hàng thành công'];
        } catch (Exception $e) {
            error_log("OrderModel::getOrderDetails - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy chi tiết đơn hàng: ' . $e->getMessage()];
        }
    }
}
?>