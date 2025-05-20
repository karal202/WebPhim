<?php
require_once __DIR__ . '/../config/config.php';

class BookmarkModel {
    private $conn;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception('Không thể kết nối đến database: Kiểm tra file config.php');
        }
        $this->conn = $conn;
    }

    // Lấy danh sách phim đã lưu của người dùng (trả về chi tiết phim)
    public function getBookmarkedMovies($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*
                FROM movies m
                JOIN bookmarks b ON m.id = b.movie_id
                WHERE b.user_id = ?
            ");
            $stmt->execute([$userId]);
            $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Thêm thông tin genres, countries, actors
            foreach ($movies as &$movie) {
                $movie['genres'] = $this->getGenresByMovieId($movie['id']);
                $movie['countries'] = $this->getCountriesByMovieId($movie['id']);
                $movie['actors'] = $this->getActorsByMovieId($movie['id']);
                // Thêm views nếu cần
                $movie['views'] = $this->getViewsByMovieId($movie['id']);
            }
            return $movies;
        } catch (PDOException $e) {
            error_log("Lỗi trong getBookmarkedMovies: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra xem phim đã được lưu chưa
    public function isBookmarked($userId, $movieId) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ? AND movie_id = ?");
            $stmt->execute([$userId, $movieId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi trong isBookmarked: " . $e->getMessage());
            return false;
        }
    }

    // Thêm phim vào danh sách đã lưu
    public function addBookmark($userId, $movieId) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO bookmarks (user_id, movie_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $movieId]);
        } catch (PDOException $e) {
            error_log("Lỗi trong addBookmark: " . $e->getMessage());
            return false;
        }
    }

    // Xóa phim khỏi danh sách đã lưu
    public function removeBookmark($userId, $movieId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND movie_id = ?");
            return $stmt->execute([$userId, $movieId]);
        } catch (PDOException $e) {
            error_log("Lỗi trong removeBookmark: " . $e->getMessage());
            return false;
        }
    }

    // Các hàm phụ để lấy genres, countries, actors
    private function getGenresByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT g.name
                FROM genres g
                JOIN movie_genres mg ON g.id = mg.genre_id
                WHERE mg.movie_id = ?
            ");
            $stmt->execute([$movie_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // Trả về mảng
        } catch (PDOException $e) {
            error_log("Lỗi trong getGenresByMovieId: " . $e->getMessage());
            return [];
        }
    }

    private function getCountriesByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.name
                FROM countries c
                JOIN movie_countries mc ON c.id = mc.country_id
                WHERE mc.movie_id = ?
            ");
            $stmt->execute([$movie_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // Trả về mảng
        } catch (PDOException $e) {
            error_log("Lỗi trong getCountriesByMovieId: " . $e->getMessage());
            return [];
        }
    }

    private function getActorsByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.name
                FROM actors a
                JOIN movie_actors ma ON a.id = ma.actor_id
                WHERE ma.movie_id = ?
            ");
            $stmt->execute([$movie_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // Trả về mảng
        } catch (PDOException $e) {
            error_log("Lỗi trong getActorsByMovieId: " . $e->getMessage());
            return [];
        }
    }

    private function getViewsByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM watch_history WHERE movie_id = ?
            ");
            $stmt->execute([$movie_id]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Lỗi trong getViewsByMovieId: " . $e->getMessage());
            return 0;
        }
    }
}
?>