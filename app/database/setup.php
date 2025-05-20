<?php
require_once __DIR__ . '/../config/config.php';

class DatabaseSetup {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function initialize() {
        try {
            // Tạo bảng deleted_orders_log nếu chưa có
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS deleted_orders_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tạo Trigger
            $this->conn->exec("
                DROP TRIGGER IF EXISTS after_order_delete;
                CREATE TRIGGER after_order_delete
                AFTER DELETE ON orders
                FOR EACH ROW
                BEGIN
                    UPDATE seats s
                    JOIN order_seats os ON s.id = os.seat_id
                    SET s.status = 'available'
                    WHERE os.order_id = OLD.id;
                END
            ");

            // Tạo Index
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_orders_status_created ON orders(status, created_at)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_tickets_order_id ON tickets(order_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_order_seats_order_id ON order_seats(order_id)");

            error_log("DatabaseSetup - Successfully initialized database structures");
            return true;
        } catch (Exception $e) {
            error_log("DatabaseSetup - Error: " . $e->getMessage());
            throw $e;
        }
    }
}

// Sử dụng
$setup = new DatabaseSetup($conn);
$setup->initialize();
?>