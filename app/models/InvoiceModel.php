<?php
require_once __DIR__ . '/../config/config.php';

class InvoiceModel {
    private $conn;

    public function __construct($conn) {
        if (!$conn) {
            throw new Exception('Invalid database connection');
        }
        $this->conn = $conn;
    }

    public function createInvoice($orderId, $userId, $movieTitle, $showTime, $roomName, $totalAmount, $paymentStatus, $transactionRef) {
        try {
            $sqlCheck = "SELECT id FROM invoices WHERE order_id = :order_id";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $existingInvoice = $stmtCheck->fetchColumn();

            if ($existingInvoice) {
                return ['success' => false, 'data' => [], 'message' => 'Hóa đơn đã tồn tại cho đơn hàng này'];
            }

            $sqlSeats = "
                SELECT GROUP_CONCAT(s.seat_number SEPARATOR ', ') AS seat_numbers
                FROM order_seats os
                JOIN seats s ON os.seat_id = s.id
                WHERE os.order_id = :order_id
            ";
            $stmtSeats = $this->conn->prepare($sqlSeats);
            $stmtSeats->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtSeats->execute();
            $seatNumbers = $stmtSeats->fetchColumn() ?: '';

            $sqlServices = "
                SELECT GROUP_CONCAT(CONCAT(svc.name, ' x', os.quantity) SEPARATOR ', ') AS services
                FROM order_services os
                JOIN services svc ON os.service_id = svc.id
                WHERE os.order_id = :order_id
            ";
            $stmtServices = $this->conn->prepare($sqlServices);
            $stmtServices->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmtServices->execute();
            $services = $stmtServices->fetchColumn() ?: 'Không có';

            $sql = "INSERT INTO invoices (order_id, user_id, movie_title, show_time, room_name, seat_numbers, services, total_amount, payment_status, transaction_ref, created_at)
                    VALUES (:order_id, :user_id, :movie_title, :show_time, :room_name, :seat_numbers, :services, :total_amount, :payment_status, :transaction_ref, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':order_id' => $orderId,
                ':user_id' => $userId,
                ':movie_title' => $movieTitle,
                ':show_time' => $showTime,
                ':room_name' => $roomName,
                ':seat_numbers' => $seatNumbers,
                ':services' => $services,
                ':total_amount' => $totalAmount,
                ':payment_status' => $paymentStatus,
                ':transaction_ref' => $transactionRef
            ]);
            $invoiceId = $this->conn->lastInsertId();
            return ['success' => true, 'data' => ['invoice_id' => $invoiceId], 'message' => 'Tạo hóa đơn thành công'];
        } catch (PDOException $e) {
            error_log("InvoiceModel::createInvoice - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi khi tạo hóa đơn: ' . $e->getMessage()];
        }
    }

    public function getAllInvoices() {
        try {
            $sql = "
                SELECT 
                    i.id, i.order_id, i.user_id, i.movie_title, i.show_time, i.room_name, 
                    i.seat_numbers, i.services, i.total_amount, i.payment_status, 
                    i.transaction_ref, i.created_at, u.username, o.status AS order_status
                FROM invoices i
                JOIN users u ON i.user_id = u.id
                JOIN orders o ON i.order_id = o.id
                GROUP BY i.id
                ORDER BY i.created_at DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoices as &$invoice) {
                $invoice['seat_numbers'] = !empty($invoice['seat_numbers']) ? $invoice['seat_numbers'] : '';
                $invoice['services'] = !empty($invoice['services']) ? $invoice['services'] : 'Không có';
                $invoice['transaction_ref'] = !empty($invoice['transaction_ref']) ? $invoice['transaction_ref'] : 'Chưa có';
                if ($invoice['payment_status'] !== $invoice['order_status']) {
                    error_log("InvoiceModel::getAllInvoices - Status mismatch for invoiceId: {$invoice['id']}, Order status: {$invoice['order_status']}, Invoice status: {$invoice['payment_status']}");
                    $invoice['payment_status'] = $invoice['order_status'];
                }
            }

            return [
                'success' => true,
                'data' => $invoices,
                'message' => empty($invoices) ? 'Không có hóa đơn nào' : 'Lấy danh sách hóa đơn thành công'
            ];
        } catch (Exception $e) {
            error_log("InvoiceModel::getAllInvoices - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy danh sách hóa đơn: ' . $e->getMessage()];
        }
    }

    public function getUserInvoices($userId) {
        try {
            $sql = "
                SELECT 
                    i.id, i.order_id, i.user_id, i.movie_title, i.show_time, i.room_name, 
                    i.seat_numbers, i.services, i.total_amount, i.payment_status, 
                    i.transaction_ref, i.created_at, u.username, o.status AS order_status
                FROM invoices i
                JOIN users u ON i.user_id = u.id
                JOIN orders o ON i.order_id = o.id
                WHERE i.user_id = :user_id
                GROUP BY i.id
                ORDER BY i.created_at DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoices as &$invoice) {
                $invoice['seat_numbers'] = !empty($invoice['seat_numbers']) ? $invoice['seat_numbers'] : '';
                $invoice['services'] = !empty($invoice['services']) ? $invoice['services'] : 'Không có';
                $invoice['transaction_ref'] = !empty($invoice['transaction_ref']) ? $invoice['transaction_ref'] : 'Chưa có';
                if ($invoice['payment_status'] !== $invoice['order_status']) {
                    error_log("InvoiceModel::getUserInvoices - Status mismatch for invoiceId: {$invoice['id']}, Order status: {$invoice['order_status']}, Invoice status: {$invoice['payment_status']}");
                    $invoice['payment_status'] = $invoice['order_status'];
                }
            }

            return [
                'success' => true,
                'data' => $invoices,
                'message' => empty($invoices) ? 'Không có hóa đơn nào' : 'Lấy danh sách hóa đơn thành công'
            ];
        } catch (Exception $e) {
            error_log("InvoiceModel::getUserInvoices - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy danh sách hóa đơn: ' . $e->getMessage()];
        }
    }

    public function getInvoiceDetails($invoiceId, $userId) {
        try {
            $sql = "
                SELECT 
                    i.id, i.order_id, i.user_id, i.movie_title, i.show_time, i.room_name, 
                    i.seat_numbers AS seats, i.services, i.total_amount, i.payment_status, 
                    i.transaction_ref, i.created_at, o.status AS order_status
                FROM invoices i
                JOIN orders o ON i.order_id = o.id
                WHERE i.id = :invoice_id AND i.user_id = :user_id
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn'];
            }

            $invoice['seats'] = !empty($invoice['seats']) ? $invoice['seats'] : '';
            $invoice['services'] = !empty($invoice['services']) ? $invoice['services'] : 'Không có';
            $invoice['transaction_ref'] = !empty($invoice['transaction_ref']) ? $invoice['transaction_ref'] : 'Chưa có';
            if ($invoice['payment_status'] !== $invoice['order_status']) {
                error_log("InvoiceModel::getInvoiceDetails - Status mismatch for invoiceId: {$invoice['id']}, Order status: {$invoice['order_status']}, Invoice status: {$invoice['payment_status']}");
                $invoice['payment_status'] = $invoice['order_status'];
            }

            return ['success' => true, 'data' => $invoice, 'message' => 'Lấy chi tiết hóa đơn thành công'];
        } catch (Exception $e) {
            error_log("InvoiceModel::getInvoiceDetails - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy chi tiết hóa đơn: ' . $e->getMessage()];
        }
    }

    public function getAggregatedInvoiceDetails($invoiceId) {
        try {
            $sql = "
                SELECT 
                    i.id, i.order_id, i.user_id, i.movie_title, i.show_time, i.room_name, 
                    i.seat_numbers, i.services, i.total_amount, i.payment_status, 
                    i.transaction_ref, i.created_at, o.status AS order_status
                FROM invoices i
                JOIN orders o ON i.order_id = o.id
                WHERE i.id = :invoice_id
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn'];
            }

            $invoice['seat_numbers'] = !empty($invoice['seat_numbers']) ? $invoice['seat_numbers'] : '';
            $invoice['services'] = !empty($invoice['services']) ? $invoice['services'] : 'Không có';
            $invoice['transaction_ref'] = !empty($invoice['transaction_ref']) ? $invoice['transaction_ref'] : 'Chưa có';
            if ($invoice['payment_status'] !== $invoice['order_status']) {
                error_log("InvoiceModel::getAggregatedInvoiceDetails - Status mismatch for invoiceId: {$invoice['id']}, Order status: {$invoice['order_status']}, Invoice status: {$invoice['payment_status']}");
                $invoice['payment_status'] = $invoice['order_status'];
            }

            return ['success' => true, 'data' => $invoice, 'message' => 'Lấy chi tiết hóa đơn thành công'];
        } catch (Exception $e) {
            error_log("InvoiceModel::getAggregatedInvoiceDetails - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy chi tiết hóa đơn: ' . $e->getMessage()];
        }
    }

    public function getTransactionDetails($invoiceId) {
        try {
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
                WHERE order_id = (SELECT order_id FROM invoices WHERE id = :invoice_id)
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                return ['success' => true, 'data' => $transaction, 'message' => 'Lấy chi tiết giao dịch thành công'];
            } else {
                return ['success' => true, 'data' => [], 'message' => 'Chưa có thông tin giao dịch'];
            }
        } catch (Exception $e) {
            error_log("InvoiceModel::getTransactionDetails - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy chi tiết giao dịch: ' . $e->getMessage()];
        }
    }

    public function getInvoiceByOrderId($orderId) {
        try {
            $sql = "
                SELECT 
                    i.id, i.order_id, i.user_id, i.movie_title, i.show_time, i.room_name, 
                    i.seat_numbers, i.services, i.total_amount, i.payment_status, 
                    i.transaction_ref, i.created_at, u.username, o.status AS order_status
                FROM invoices i
                JOIN users u ON i.user_id = u.id
                JOIN orders o ON i.order_id = o.id
                WHERE i.order_id = :order_id
                LIMIT 1
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn'];
            }

            $invoice['seat_numbers'] = !empty($invoice['seat_numbers']) ? $invoice['seat_numbers'] : '';
            $invoice['services'] = !empty($invoice['services']) ? $invoice['services'] : 'Không có';
            $invoice['transaction_ref'] = !empty($invoice['transaction_ref']) ? $invoice['transaction_ref'] : 'Chưa có';
            if ($invoice['payment_status'] !== $invoice['order_status']) {
                error_log("InvoiceModel::getInvoiceByOrderId - Status mismatch for invoiceId: {$invoice['id']}, Order status: {$invoice['order_status']}, Invoice status: {$invoice['payment_status']}");
                $invoice['payment_status'] = $invoice['order_status'];
            }

            return ['success' => true, 'data' => $invoice, 'message' => 'Lấy hóa đơn thành công'];
        } catch (Exception $e) {
            error_log("InvoiceModel::getInvoiceByOrderId - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi lấy hóa đơn: ' . $e->getMessage()];
        }
    }

    public function updateInvoiceStatus($invoiceId, $status, $userId) {
        try {
            $this->conn->beginTransaction();

            $sqlCheck = "SELECT payment_status, order_id FROM invoices WHERE id = :invoice_id AND user_id = :user_id";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmtCheck->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $invoice = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn'];
            }

            if ($invoice['payment_status'] === $status) {
                $this->conn->rollBack();
                return ['success' => true, 'data' => [], 'message' => 'Trạng thái hóa đơn đã là ' . $status];
            }

            $sqlInvoice = "UPDATE invoices SET payment_status = :status, updated_at = NOW() WHERE id = :invoice_id AND user_id = :user_id";
            $stmtInvoice = $this->conn->prepare($sqlInvoice);
            $stmtInvoice->bindValue(':status', $status, PDO::PARAM_STR);
            $stmtInvoice->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmtInvoice->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtInvoice->execute();

            if ($stmtInvoice->rowCount() > 0) {
                $sqlOrder = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :order_id";
                $stmtOrder = $this->conn->prepare($sqlOrder);
                $stmtOrder->bindValue(':status', $status, PDO::PARAM_STR);
                $stmtOrder->bindValue(':order_id', $invoice['order_id'], PDO::PARAM_INT);
                $stmtOrder->execute();

                $this->conn->commit();
                error_log("InvoiceModel::updateInvoiceStatus - Updated invoiceId: $invoiceId to status: $status");
                return ['success' => true, 'data' => [], 'message' => 'Cập nhật trạng thái hóa đơn thành công'];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Không thể cập nhật trạng thái hóa đơn'];
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("InvoiceModel::updateInvoiceStatus - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi cập nhật trạng thái: ' . $e->getMessage()];
        }
    }

    public function deleteInvoice($invoiceId) {
        try {
            $this->conn->beginTransaction();

            $sqlOrderId = "SELECT order_id FROM invoices WHERE id = :invoice_id";
            $stmtOrderId = $this->conn->prepare($sqlOrderId);
            $stmtOrderId->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmtOrderId->execute();
            $orderId = $stmtOrderId->fetchColumn();

            if ($orderId === false) {
                $this->conn->rollBack();
                return ['success' => false, 'data' => [], 'message' => 'Không tìm thấy hóa đơn'];
            }

            $logSql = "INSERT INTO deleted_orders_log (order_id) VALUES (:order_id)";
            $logStmt = $this->conn->prepare($logSql);
            $logStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $logStmt->execute();

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
            $this->conn->prepare("DELETE FROM invoices WHERE id = :invoice_id")
                       ->execute(['invoice_id' => $invoiceId]);

            $this->conn->commit();
            error_log("InvoiceModel::deleteInvoice - Deleted invoiceId: $invoiceId and orderId: $orderId");
            return ['success' => true, 'data' => [], 'message' => 'Xóa hóa đơn và các dữ liệu liên quan thành công'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("InvoiceModel::deleteInvoice - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi hệ thống khi xóa hóa đơn: ' . $e->getMessage()];
        }
    }
}
?>