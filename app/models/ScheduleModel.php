<?php
require_once '../app/config/config.php';

class ScheduleModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("SET NAMES 'utf8'");
    }

    public function isValidTimeRange($time) {
        $hour = (int) date('H', strtotime($time));
        return $hour >= 7 && $hour <= 23;
    }

    public function checkTimeGap($new_date, $new_time, $room_id, $schedule_id = 0) {
        $new_datetime = strtotime("$new_date $new_time");
        $sql = "
            SELECT date, time
            FROM room_detail
            WHERE room_id = :room_id 
            AND date = :date
        ";
        if ($schedule_id > 0) {
            $sql .= " AND id != :schedule_id";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $new_date, PDO::PARAM_STR);
        if ($schedule_id > 0) {
            $stmt->bindValue(':schedule_id', $schedule_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $existing_showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existing_showtimes as $existing) {
            $existing_datetime = strtotime($existing['date'] . ' ' . $existing['time']);
            $time_diff = abs($new_datetime - $existing_datetime) / 3600;
            if ($time_diff < 2) {
                return false;
            }
        }
        return true;
    }

    public function getAllSchedules($date = null) {
        $sql = "
            SELECT rd.id, rd.movie_id, rd.room_id, rd.date, rd.time, m.title as movie_title, r.name as room_name
            FROM room_detail rd
            JOIN movies m ON rd.movie_id = m.id
            JOIN rooms r ON rd.room_id = r.id
            WHERE CONCAT(rd.date, ' ', rd.time) > NOW()
        ";
        if ($date) {
            $sql .= " AND rd.date = :date";
        }
        $sql .= " ORDER BY rd.date, rd.time";
        $stmt = $this->conn->prepare($sql);
        if ($date) {
            $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSchedulesByMovie($movie_id) {
        $sql = "
            SELECT rd.id, rd.movie_id, rd.room_id, rd.date, rd.time, m.title as movie_title, r.name as room_name
            FROM room_detail rd
            JOIN movies m ON rd.movie_id = m.id
            JOIN rooms r ON rd.room_id = r.id
            WHERE rd.movie_id = :movie_id
            AND CONCAT(rd.date, ' ', rd.time) > NOW()
            ORDER BY rd.date, rd.time
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':movie_id', $movie_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getScheduleById($schedule_id) {
        $sql = "
            SELECT rd.id, rd.movie_id, rd.room_id, rd.date, rd.time, m.title as movie_title, r.name as room_name
            FROM room_detail rd
            JOIN movies m ON rd.movie_id = m.id
            JOIN rooms r ON rd.room_id = r.id
            WHERE rd.id = :schedule_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMoviesWithSchedules() {
        $sql = "
            SELECT DISTINCT 
                m.id, 
                m.title, 
                m.thumbnail, 
                GROUP_CONCAT(DISTINCT g.name) AS genres, 
                GROUP_CONCAT(DISTINCT c.name) AS countries, 
                GROUP_CONCAT(DISTINCT a.name) AS actors
            FROM room_detail rd
            JOIN movies m ON rd.movie_id = m.id
            LEFT JOIN movie_genres mg ON m.id = mg.movie_id
            LEFT JOIN genres g ON mg.genre_id = g.id
            LEFT JOIN movie_countries mc ON m.id = mc.movie_id
            LEFT JOIN countries c ON mc.country_id = c.id
            LEFT JOIN movie_actors ma ON m.id = ma.movie_id
            LEFT JOIN actors a ON ma.actor_id = a.id
            WHERE CONCAT(rd.date, ' ', rd.time) > NOW()
            GROUP BY m.id, m.title, m.thumbnail
            ORDER BY m.title
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSchedule($movie_id, $room_id, $date, $time) {
        $sql = "
            INSERT INTO room_detail (movie_id, room_id, date, time) 
            VALUES (:movie_id, :room_id, :date, :time)
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':movie_id', $movie_id, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':time', $time, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false];
    }

    public function updateSchedule($schedule_id, $movie_id, $room_id, $date, $time) {
        $sql = "
            UPDATE room_detail 
            SET movie_id = :movie_id, 
                room_id = :room_id, 
                date = :date, 
                time = :time
            WHERE id = :schedule_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':movie_id', $movie_id, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':time', $time, PDO::PARAM_STR);
        $stmt->bindValue(':schedule_id', $schedule_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteSchedule($schedule_id) {
        $sql = "
            SELECT COUNT(*) as order_count
            FROM orders
            WHERE movie_id = (SELECT movie_id FROM room_detail WHERE id = :schedule_id)
            AND room_id = (SELECT room_id FROM room_detail WHERE id = :schedule_id)
            AND date = (SELECT date FROM room_detail WHERE id = :schedule_id)
            AND time = (SELECT time FROM room_detail WHERE id = :schedule_id)
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->execute();
        $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];

        if ($order_count > 0) {
            return ['success' => false, 'message' => 'Cannot delete schedule due to related orders'];
        }

        $sql = "DELETE FROM room_detail WHERE id = :schedule_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':schedule_id', $schedule_id, PDO::PARAM_INT);

        return ['success' => $stmt->execute()];
    }
}
?>