<?php
require_once '../app/controller/ScheduleController.php';

try {
    $controller = new ScheduleController($conn);
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

    switch ($action) {
        case 'get_all':
            $controller->getAllSchedules();
            break;
        case 'get_schedules':
            if (!isset($_POST['movie_id']) || $_POST['movie_id'] <= 0) {
                $controller->sendResponse(false, [], 'Invalid Movie ID');
            }
            $controller->getSchedulesByMovie($_POST['movie_id']);
            break;
        case 'get':
            if (!isset($_GET['id']) || $_GET['id'] <= 0) {
                $controller->sendResponse(false, [], 'Invalid Schedule ID');
            }
            $controller->getScheduleById($_GET['id']);
            break;
        case 'add_schedule':
            if (!isset($_POST['movie_id']) || !isset($_POST['room_id']) || !isset($_POST['show_time'])) {
                $controller->sendResponse(false, [], 'Missing required information');
            }
            $controller->addSchedule($_POST['movie_id'], $_POST['room_id'], $_POST['show_time']);
            break;
        case 'update_schedule':
            if (!isset($_POST['schedule_id']) || !isset($_POST['movie_id']) || !isset($_POST['room_id']) || !isset($_POST['show_time'])) {
                $controller->sendResponse(false, [], 'Missing required information');
            }
            $controller->updateSchedule($_POST['schedule_id'], $_POST['movie_id'], $_POST['room_id'], $_POST['show_time']);
            break;
        case 'delete_schedule':
            if (!isset($_POST['schedule_id']) || $_POST['schedule_id'] <= 0) {
                $controller->sendResponse(false, [], 'Invalid Schedule ID');
            }
            $controller->deleteSchedule($_POST['schedule_id']);
            break;
        default:
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'data' => [],
                'message' => 'Invalid action'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'System error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>