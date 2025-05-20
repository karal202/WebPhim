<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json; charset=UTF-8');
require_once '../app/models/HistoryModel.php';
require_once '../app/models/UserModel.php';
require_once '../app/models/MovieModel.php';

session_start();

function respond($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $historyModel = new HistoryModel();
    $userModel = new UserModel();
    $movieModel = new MovieModel();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Phương thức không được hỗ trợ!');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $userId = $_SESSION['user_id'] ?? 0;
        $movieId = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

        if (!$userId) {
            respond(false, 'Vui lòng đăng nhập để lưu lịch sử xem phim!');
        }

        if ($movieId <= 0) {
            respond(false, 'Movie ID không hợp lệ!');
        }

        $user = $userModel->getUserById($userId);
        if (!$user) {
            respond(false, 'Người dùng không tồn tại!');
        }

        $movie = $movieModel->getMovieById($movieId);
        if (!$movie) {
            respond(false, 'Phim không tồn tại!');
        }

        if ($historyModel->addHistory($userId, $movieId)) {
            respond(true, 'Đã lưu lịch sử xem phim thành công!');
        } else {
            respond(false, 'Lỗi khi lưu lịch sử xem phim!');
        }
    } elseif ($action === 'get') {
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$userId) {
            respond(false, 'Vui lòng đăng nhập để xem lịch sử xem phim!');
        }

        $history = $historyModel->getHistoryByUserId($userId);
        if ($history === false) {
            respond(false, 'Lỗi khi lấy lịch sử xem phim!');
        }
        respond(true, 'Lấy lịch sử xem phim thành công!', ['data' => $history]);
    } elseif ($action === 'delete') {
        $userId = $_SESSION['user_id'] ?? 0;
        $movieId = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

        if (!$userId) {
            respond(false, 'Vui lòng đăng nhập để xóa lịch sử xem phim!');
        }

        if ($movieId <= 0) {
            respond(false, 'Movie ID không hợp lệ!');
        }

        if ($historyModel->deleteHistory($userId, $movieId)) {
            respond(true, 'Đã xóa lịch sử xem phim thành công!');
        } else {
            respond(false, 'Lỗi khi xóa lịch sử xem phim!');
        }
    } elseif ($action === 'delete_all') {
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$userId) {
            respond(false, 'Vui lòng đăng nhập để xóa toàn bộ lịch sử xem phim!');
        }

        if ($historyModel->deleteAllHistory($userId)) {
            respond(true, 'Đã xóa toàn bộ lịch sử xem phim thành công!');
        } else {
            respond(false, 'Lỗi khi xóa toàn bộ lịch sử xem phim!');
        }
    } else {
        respond(false, 'Hành động không hợp lệ!');
    }
} catch (Exception $e) {
    error_log("Error in history.php: " . $e->getMessage());
    respond(false, 'Lỗi server: ' . $e->getMessage());
}
?>