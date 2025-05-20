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

    // Check if $conn is defined and is a PDO instance (assuming config.php provides $conn as a PDO object)
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception('Database connection not properly initialized');
    }

    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'get_monthly_revenue') {
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(o.date, '%Y-%m') AS month,
                COALESCE(SUM(o.total_amount), 0) + COALESCE(SUM(os.quantity * s.price), 0) AS revenue
            FROM orders o
            LEFT JOIN order_services os ON o.id = os.order_id
            LEFT JOIN services s ON os.service_id = s.id
            WHERE o.status = 'completed'
            GROUP BY DATE_FORMAT(o.date, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert revenue to float for JSON compatibility
        foreach ($data as &$row) {
            $row['revenue'] = (float)$row['revenue'];
        }
        respond($data);
    } elseif ($action === 'get_revenue_by_movie') {
        $stmt = $conn->prepare("
            SELECT 
                m.title AS movie_title,
                COALESCE(SUM(o.total_amount), 0) + COALESCE(SUM(os.quantity * s.price), 0) AS revenue
            FROM orders o
            JOIN movies m ON o.movie_id = m.id
            LEFT JOIN order_services os ON o.id = os.order_id
            LEFT JOIN services s ON os.service_id = s.id
            WHERE o.status = 'completed'
            GROUP BY m.id, m.title
            ORDER BY revenue DESC
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert revenue to float for JSON compatibility
        foreach ($data as &$row) {
            $row['revenue'] = (float)$row['revenue'];
        }
        respond($data);
    } else {
        respond(array('error' => 'Invalid action'));
    }
} catch (Exception $e) {
    error_log("Error in revenue.php: " . $e->getMessage());
    respond(array('error' => 'Lỗi server: ' . $e->getMessage()));
}
?>