<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/OrderModel.php';

$orderModel = new OrderModel($conn);
try {
    $result = $orderModel->cleanupPendingOrders();
    echo "Cleanup completed successfully at " . date('Y-m-d H:i:s') . ": " . $result['message'] . "\n";
} catch (Exception $e) {
    error_log("Cron CleanupPendingOrders - Error: " . $e->getMessage());
    echo "Cleanup failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n";
}
?>