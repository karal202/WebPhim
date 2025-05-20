<?php
require_once __DIR__ . '/../config/config.php';

class HistoryModel {
    private $conn;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception('Không thể kết nối đến database: Kiểm tra file config.php');
        }
        $this->conn = $conn;
    }

    public function addHistory($userId, $movieId) {
        try {
            // Kiểm tra xem bản ghi đã tồn tại chưa
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM watch_history WHERE user_id = ? AND movie_id = ?");
            $stmt->execute([$userId, $movieId]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                // Nếu đã tồn tại, cập nhật thời gian xem
                $stmt = $this->conn->prepare("UPDATE watch_history SET watched_at = CURRENT_TIMESTAMP WHERE user_id = ? AND movie_id = ?");
                return $stmt->execute([$userId, $movieId]);
            }

            // Nếu chưa tồn tại, thêm mới
            $stmt = $this->conn->prepare("INSERT INTO watch_history (user_id, movie_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $movieId]);
        } catch (PDOException $e) {
            error_log("Lỗi trong addHistory: " . $e->getMessage());
            return false;
        }
    }

    public function getHistoryByUserId($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*, 
                       GROUP_CONCAT(DISTINCT g.name) as genres, 
                       GROUP_CONCAT(DISTINCT c.name) as countries, 
                       GROUP_CONCAT(DISTINCT a.name) as actors, 
                       (SELECT COUNT(*) FROM watch_history wh WHERE wh.movie_id = m.id) as views
                FROM watch_history wh
                JOIN movies m ON wh.movie_id = m.id
                LEFT JOIN movie_genres mg ON m.id = mg.movie_id
                LEFT JOIN genres g ON mg.genre_id = g.id
                LEFT JOIN movie_countries mc ON m.id = mc.movie_id
                LEFT JOIN countries c ON mc.country_id = c.id
                LEFT JOIN movie_actors ma ON m.id = ma.movie_id
                LEFT JOIN actors a ON ma.actor_id = a.id
                WHERE wh.user_id = ?
                GROUP BY m.id
                ORDER BY wh.watched_at DESC
            ");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($history as &$item) {
                $item['genres'] = !empty($item['genres']) ? explode(',', $item['genres']) : [];
                $item['countries'] = !empty($item['countries']) ? explode(',', $item['countries']) : [];
                $item['actors'] = !empty($item['actors']) ? explode(',', $item['actors']) : [];
            }

            return $history;
        } catch (PDOException $e) {
            error_log("Lỗi trong getHistoryByUserId: " . $e->getMessage());
            return false;
        }
    }

    public function deleteHistory($userId, $movieId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM watch_history WHERE user_id = ? AND movie_id = ?");
            return $stmt->execute([$userId, $movieId]);
        } catch (PDOException $e) {
            error_log("Lỗi trong deleteHistory: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAllHistory($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM watch_history WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Lỗi trong deleteAllHistory: " . $e->getMessage());
            return false;
        }
    }
}
?>