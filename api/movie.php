<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!file_exists('../app/controller/MovieController.php')) {
    echo json_encode(array('error' => 'Không tìm thấy file MovieController.php'));
    exit;
}

require_once '../app/controller/MovieController.php';

function respond($data) {
    echo json_encode($data);
    exit;
}

try {
    $controller = new MovieController();
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_all') {
            $random = isset($_GET['random']) && $_GET['random'] === 'true';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
            $movies = $controller->getAllMovies($random, $limit);
            if ($movies === false) {
                respond(array('error' => 'Không thể lấy danh sách phim'));
            }
            respond($movies);
        } elseif (($action === 'get' || $action === 'get_by_id') && isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                respond(array('error' => 'ID không hợp lệ'));
            }
            $movie = $controller->getMovieById($id);
            respond($movie ? $movie : array('error' => 'Movie not found'));
        } elseif ($action === 'get_trending') {
            $trendingMovies = $controller->getTrendingMovies();
            if ($trendingMovies === false) {
                respond(array('error' => 'Không thể lấy danh sách phim nổi bật'));
            }
            respond($trendingMovies);
        } elseif ($action === 'get_thuyet_minh') {
            $movies = $controller->getThuyetMinhMovies();
            if ($movies === false) {
                respond(array('error' => 'Không thể lấy danh sách phim thuyết minh'));
            }
            respond($movies);
        } elseif ($action === 'get_vietsub') {
            $movies = $controller->getVietsubMovies();
            if ($movies === false) {
                respond(array('error' => 'Không thể lấy danh sách phim vietsub'));
            }
            respond($movies);
        } elseif ($action === 'get_theater_movies') {
            $random = isset($_GET['random']) && $_GET['random'] === 'true';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
            $movies = $controller->getTheaterMovies($random, $limit);
            if ($movies === false) {
                respond(array('error' => 'Không thể lấy danh sách phim đã chiếu rạp'));
            }
            respond($movies);
        } elseif ($action === 'get_by_genre' && isset($_GET['genre'])) {
            $genre = trim($_GET['genre']);
            if (empty($genre)) {
                respond(array('error' => 'Thể loại không hợp lệ'));
            }
            $movies = $controller->getMoviesByGenre($genre);
            respond($movies);
        } elseif ($action === 'get_by_country' && isset($_GET['country'])) {
            $country = trim($_GET['country']);
            if (empty($country)) {
                respond(array('error' => 'Quốc gia không hợp lệ'));
            }
            $movies = $controller->getMoviesByCountry($country);
            respond($movies);
        } elseif ($action === 'search' && isset($_GET['query'])) {
            $query = trim($_GET['query']);
            if (empty($query)) {
                respond(array('error' => 'Từ khóa tìm kiếm không hợp lệ'));
            }
            $movies = $controller->searchMovies($query);
            respond($movies);
        } else {
            respond(array('error' => 'Invalid action'));
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            $input = [
                'title' => $_POST['addTitle'] ?? '',
                'description' => $_POST['addDescription'] ?? '',
                'duration' => $_POST['addDuration'] ?? '',
                'language' => $_POST['addLanguage'] ?? '',
                'genres' => $_POST['addGenres'] ?? '',
                'countries' => $_POST['addCountries'] ?? '',
                'actors' => $_POST['addActors'] ?? '',
                'release_year' => $_POST['addReleaseYear'] ?? '',
                'status' => $_POST['addStatus'] ?? '',
                'ticket_price' => $_POST['addTicketPrice'] ?? ''
            ];
            $thumbnailFile = isset($_FILES['addThumbnail']) ? $_FILES['addThumbnail'] : null;
            $videoFile = isset($_FILES['addVideo']) ? $_FILES['addVideo'] : null;

            if (empty($input['title'])) {
                respond(array('error' => 'Tên phim là bắt buộc'));
            }

            $result = $controller->addMovie($input, $thumbnailFile, $videoFile);
            respond($result);
        } elseif ($action === 'update') {
            $input = [
                'id' => $_POST['editMovieId'] ?? '',
                'title' => $_POST['editTitle'] ?? '',
                'description' => $_POST['editDescription'] ?? '',
                'duration' => $_POST['editDuration'] ?? '',
                'language' => $_POST['editLanguage'] ?? '',
                'genres' => $_POST['editGenres'] ?? '',
                'countries' => $_POST['editCountries'] ?? '',
                'actors' => $_POST['editActors'] ?? '',
                'release_year' => $_POST['editReleaseYear'] ?? '',
                'status' => $_POST['editStatus'] ?? '',
                'ticket_price' => $_POST['editTicketPrice'] ?? ''
            ];
            $thumbnailFile = isset($_FILES['editThumbnail']) ? $_FILES['editThumbnail'] : null;
            $videoFile = isset($_FILES['editVideo']) ? $_FILES['editVideo'] : null;

            if (empty($input['id']) || empty($input['title'])) {
                respond(array('error' => 'ID và tên phim là bắt buộc'));
            }

            $result = $controller->updateMovie($input, $thumbnailFile, $videoFile);
            respond($result);
        } elseif ($action === 'delete') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($input['id'])) {
                respond(array('error' => 'ID là bắt buộc'));
            }
            $result = $controller->deleteMovie($input['id']);
            respond($result);
        } else {
            respond(array('error' => 'Invalid action'));
        }
    } else {
        respond(array('error' => 'Phương thức HTTP không được hỗ trợ'));
    }
} catch (Exception $e) {
    error_log("Error in movie.php: " . $e->getMessage());
    respond(array('error' => 'Lỗi server: ' . $e->getMessage()));
}
?>