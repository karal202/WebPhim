<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json; charset=UTF-8');
require_once '../app/models/BookmarkModel.php';
require_once '../app/models/MovieModel.php';
require_once '../app/models/UserModel.php';

session_start();

function respond($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    respond(false, 'Vui lòng đăng nhập để thực hiện thao tác này!');
}

try {
    $bookmarkModel = new BookmarkModel();
    $movieModel = new MovieModel();
    $userModel = new UserModel();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Phương thức không được hỗ trợ!');
    }

    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'];

    // Kiểm tra user tồn tại
    $user = $userModel->getUserById($userId);
    if (!$user) {
        respond(false, 'Người dùng không tồn tại!');
    }

    if ($action === 'get') {
        $movies = $bookmarkModel->getBookmarkedMovies($userId);
        if ($movies === false) {
            respond(false, 'Lỗi khi lấy danh sách phim đã lưu!');
        }
        respond(true, 'Lấy danh sách phim đã lưu thành công!', ['data' => $movies]);
    } elseif ($action === 'add') {
        $movieId = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

        if ($movieId <= 0) {
            respond(false, 'Movie ID không hợp lệ!');
        }

        $movie = $movieModel->getMovieById($movieId);
        if (!$movie) {
            respond(false, 'Phim không tồn tại!');
        }

        if ($bookmarkModel->isBookmarked($userId, $movieId)) {
            respond(false, 'Phim đã được lưu!');
        }

        if ($bookmarkModel->addBookmark($userId, $movieId)) {
            respond(true, 'Đã lưu phim thành công!');
        } else {
            respond(false, 'Lỗi khi lưu phim!');
        }
    } elseif ($action === 'remove') {
        $movieId = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

        if ($movieId <= 0) {
            respond(false, 'Movie ID không hợp lệ!');
        }

        $movie = $movieModel->getMovieById($movieId);
        if (!$movie) {
            respond(false, 'Phim không tồn tại!');
        }

        if (!$bookmarkModel->isBookmarked($userId, $movieId)) {
            respond(false, 'Phim không có trong danh sách đã lưu!');
        }

        if ($bookmarkModel->removeBookmark($userId, $movieId)) {
            respond(true, 'Đã xóa phim khỏi danh sách đã lưu!');
        } else {
            respond(false, 'Lỗi khi xóa phim khỏi danh sách đã lưu!');
        }
    } else {
        respond(false, 'Hành động không hợp lệ!');
    }
} catch (Exception $e) {
    error_log("Error in bookmark.php: " . $e->getMessage());
    respond(false, 'Lỗi server: ' . $e->getMessage());
}
?>