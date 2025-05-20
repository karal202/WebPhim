<?php
require_once __DIR__ . '/../config/config.php';

class MovieModel {
    private $conn;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception('Không thể kết nối đến database');
        }
        $this->conn = $conn;
    }

    private function handleImageUpload($file, $movieId) {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi khi tải lên hình: ' . $file['error']);
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Chỉ hỗ trợ định dạng JPEG, PNG, GIF');
        }
        $imgDir = __DIR__ . '/../../img/';
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "movie_{$movieId}_" . time() . ".{$ext}";
        $destination = $imgDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Không thể lưu hình');
        }
        return "img/{$filename}";
    }

    private function handleVideoUpload($file, $movieId) {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi khi tải lên video: ' . $file['error']);
        }
        $allowedTypes = ['video/mp4', 'video/avi', 'video/mkv'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Chỉ hỗ trợ định dạng MP4, AVI, MKV');
        }
        $videoDir = __DIR__ . '/../../Trailer/';
        if (!is_dir($videoDir)) {
            mkdir($videoDir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "movie_{$movieId}_" . time() . ".{$ext}";
        $destination = $videoDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Không thể lưu video');
        }
        return "Trailer/{$filename}";
    }

    public function getAllMovies($random = false, $limit = 0) {
        try {
            $query = "SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies";
            if ($random) {
                $query .= " ORDER BY RAND()";
            }
            if ($limit > 0) {
                $query .= " LIMIT " . (int)$limit;
            }
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getAllMovies: " . $e->getMessage());
            return false;
        }
    }

    public function getTrendingMovies() {
        try {
            $stmt = $this->conn->prepare("SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies ORDER BY ticket_price DESC LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getTrendingMovies: " . $e->getMessage());
            return false;
        }
    }

    public function getThuyetMinhMovies() {
        try {
            $stmt = $this->conn->prepare("SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies WHERE language LIKE '%Thuyết Minh%' ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getThuyetMinhMovies: " . $e->getMessage());
            return false;
        }
    }

    public function getVietsubMovies() {
        try {
            $stmt = $this->conn->prepare("SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies WHERE language LIKE '%Vietsub%' ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getVietsubMovies: " . $e->getMessage());
            return false;
        }
    }

    public function getTheaterMovies($random = false, $limit = 0) {
        try {
            $query = "SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies WHERE status = 'Hoàn thành' AND release_year < 2020";
            if ($random) {
                $query .= " ORDER BY RAND()";
            }
            if ($limit > 0) {
                $query .= " LIMIT " . (int)$limit;
            }
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getTheaterMovies: " . $e->getMessage());
            return false;
        }
    }

    public function getMovieById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies WHERE id = ?");
            $stmt->execute(array($id));
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMovieById: " . $e->getMessage());
            return null;
        }
    }

    public function getMoviesByGenre($genre) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*
                FROM movies m
                JOIN movie_genres mg ON m.id = mg.movie_id
                JOIN genres g ON mg.genre_id = g.id
                WHERE g.name = ?
            ");
            $stmt->execute(array($genre));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMoviesByGenre: " . $e->getMessage());
            return array();
        }
    }

    public function getMoviesByCountry($country) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*
                FROM movies m
                JOIN movie_countries mc ON m.id = mc.movie_id
                JOIN countries c ON mc.country_id = c.id
                WHERE c.name = ?
            ");
            $stmt->execute(array($country));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMoviesByCountry: " . $e->getMessage());
            return array();
        }
    }

    public function searchMovies($query) {
        try {
            $stmt = $this->conn->prepare("SELECT id, title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration FROM movies WHERE title LIKE ?");
            $stmt->execute(array("%$query%"));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in searchMovies: " . $e->getMessage());
            return array();
        }
    }

    public function addMovie($movieData, $genres, $countries, $actors, $thumbnailFile, $videoFile) {
        try {
            $this->conn->beginTransaction();

            // Insert movie
            $stmt = $this->conn->prepare("
                INSERT INTO movies (title, video_path, language, status, release_year, ticket_price, created_at, thumbnail, description, duration)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
            ");
            $stmt->execute([
                $movieData['title'],
                null, // Placeholder for video_path
                $movieData['language'] ?: null,
                $movieData['status'] ?: null,
                $movieData['release_year'] ?: null,
                $movieData['ticket_price'] ?: null,
                null, // Placeholder for thumbnail
                $movieData['description'] ?: null,
                $movieData['duration'] ?: null
            ]);
            $movieId = $this->conn->lastInsertId();

            // Handle image upload
            $thumbnailPath = $this->handleImageUpload($thumbnailFile, $movieId);
            if ($thumbnailPath) {
                $stmt = $this->conn->prepare("UPDATE movies SET thumbnail = ? WHERE id = ?");
                $stmt->execute([$thumbnailPath, $movieId]);
            }

            // Handle video upload
            $videoPath = $this->handleVideoUpload($videoFile, $movieId);
            if ($videoPath) {
                $stmt = $this->conn->prepare("UPDATE movies SET video_path = ? WHERE id = ?");
                $stmt->execute([$videoPath, $movieId]);
            }

            // Insert genres
            foreach (array_filter($genres) as $genre) {
                $stmt = $this->conn->prepare("SELECT id FROM genres WHERE name = ?");
                $stmt->execute([$genre]);
                $genreId = $stmt->fetchColumn();
                if (!$genreId) {
                    $stmt = $this->conn->prepare("INSERT INTO genres (name) VALUES (?)");
                    $stmt->execute([$genre]);
                    $genreId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$movieId, $genreId]);
            }

            // Insert countries
            foreach (array_filter($countries) as $country) {
                $stmt = $this->conn->prepare("SELECT id FROM countries WHERE name = ?");
                $stmt->execute([$country]);
                $countryId = $stmt->fetchColumn();
                if (!$countryId) {
                    $stmt = $this->conn->prepare("INSERT INTO countries (name) VALUES (?)");
                    $stmt->execute([$country]);
                    $countryId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_countries (movie_id, country_id) VALUES (?, ?)");
                $stmt->execute([$movieId, $countryId]);
            }

            // Insert actors
            foreach (array_filter($actors) as $actor) {
                $stmt = $this->conn->prepare("SELECT id FROM actors WHERE name = ?");
                $stmt->execute([$actor]);
                $actorId = $stmt->fetchColumn();
                if (!$actorId) {
                    $stmt = $this->conn->prepare("INSERT INTO actors (name) VALUES (?)");
                    $stmt->execute([$actor]);
                    $actorId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_actors (movie_id, actor_id) VALUES (?, ?)");
                $stmt->execute([$movieId, $actorId]);
            }

            $this->conn->commit();
            return $movieId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in addMovie: " . $e->getMessage() . " | Data: " . json_encode($movieData));
            throw new Exception("Không thể thêm phim: " . $e->getMessage());
        }
    }

    public function updateMovie($movieData, $genres, $countries, $actors, $thumbnailFile, $videoFile) {
        try {
            $this->conn->beginTransaction();

            // Get current thumbnail and video for potential deletion
            $stmt = $this->conn->prepare("SELECT thumbnail, video_path FROM movies WHERE id = ?");
            $stmt->execute([$movieData['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldThumbnail = $row['thumbnail'];
            $oldVideoPath = $row['video_path'];

            // Handle image upload
            $thumbnailPath = $this->handleImageUpload($thumbnailFile, $movieData['id']);
            if ($thumbnailPath && $oldThumbnail) {
                @unlink(__DIR__ . '/../../' . $oldThumbnail);
            }

            // Handle video upload
            $videoPath = $this->handleVideoUpload($videoFile, $movieData['id']);
            if ($videoPath && $oldVideoPath) {
                @unlink(__DIR__ . '/../../' . $oldVideoPath);
            }

            // Update movie
            $stmt = $this->conn->prepare("
                UPDATE movies
                SET title = ?, video_path = ?, language = ?, status = ?, release_year = ?, ticket_price = ?, thumbnail = ?, description = ?, duration = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $movieData['title'],
                $videoPath ?: $oldVideoPath,
                $movieData['language'] ?: null,
                $movieData['status'] ?: null,
                $movieData['release_year'] ?: null,
                $movieData['ticket_price'] ?: null,
                $thumbnailPath ?: $oldThumbnail,
                $movieData['description'] ?: null,
                $movieData['duration'] ?: null,
                $movieData['id']
            ]);

            // Clear existing relationships
            $this->conn->prepare("DELETE FROM movie_genres WHERE movie_id = ?")->execute([$movieData['id']]);
            $this->conn->prepare("DELETE FROM movie_countries WHERE movie_id = ?")->execute([$movieData['id']]);
            $this->conn->prepare("DELETE FROM movie_actors WHERE movie_id = ?")->execute([$movieData['id']]);

            // Insert genres
            foreach (array_filter($genres) as $genre) {
                $stmt = $this->conn->prepare("SELECT id FROM genres WHERE name = ?");
                $stmt->execute([$genre]);
                $genreId = $stmt->fetchColumn();
                if (!$genreId) {
                    $stmt = $this->conn->prepare("INSERT INTO genres (name) VALUES (?)");
                    $stmt->execute([$genre]);
                    $genreId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$movieData['id'], $genreId]);
            }

            // Insert countries
            foreach (array_filter($countries) as $country) {
                $stmt = $this->conn->prepare("SELECT id FROM countries WHERE name = ?");
                $stmt->execute([$country]);
                $countryId = $stmt->fetchColumn();
                if (!$countryId) {
                    $stmt = $this->conn->prepare("INSERT INTO countries (name) VALUES (?)");
                    $stmt->execute([$country]);
                    $countryId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_countries (movie_id, country_id) VALUES (?, ?)");
                $stmt->execute([$movieData['id'], $countryId]);
            }

            // Insert actors
            foreach (array_filter($actors) as $actor) {
                $stmt = $this->conn->prepare("SELECT id FROM actors WHERE name = ?");
                $stmt->execute([$actor]);
                $actorId = $stmt->fetchColumn();
                if (!$actorId) {
                    $stmt = $this->conn->prepare("INSERT INTO actors (name) VALUES (?)");
                    $stmt->execute([$actor]);
                    $actorId = $this->conn->lastInsertId();
                }
                $stmt = $this->conn->prepare("INSERT INTO movie_actors (movie_id, actor_id) VALUES (?, ?)");
                $stmt->execute([$movieData['id'], $actorId]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in updateMovie: " . $e->getMessage() . " | Data: " . json_encode($movieData));
            throw new Exception("Không thể cập nhật phim: " . $e->getMessage());
        }
    }

    public function deleteMovie($id) {
        try {
            $this->conn->beginTransaction();

            // Get thumbnail and video for deletion
            $stmt = $this->conn->prepare("SELECT thumbnail, video_path FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $thumbnail = $row['thumbnail'];
            $videoPath = $row['video_path'];
            if ($thumbnail) {
                @unlink(__DIR__ . '/../../' . $thumbnail);
            }
            if ($videoPath) {
                @unlink(__DIR__ . '/../../' . $videoPath);
            }

            // Delete relationships
            $this->conn->prepare("DELETE FROM movie_genres WHERE movie_id = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM movie_countries WHERE movie_id = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM movie_actors WHERE movie_id = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM ratings WHERE movie_id = ?")->execute([$id]);

            // Delete movie
            $stmt = $this->conn->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in deleteMovie: " . $e->getMessage());
            return false;
        }
    }

    public function getGenresByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT g.name
                FROM genres g
                JOIN movie_genres mg ON g.id = mg.genre_id
                WHERE mg.movie_id = ?
            ");
            $stmt->execute(array($movie_id));
            $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return implode(', ', $genres);
        } catch (Exception $e) {
            error_log("Error in getGenresByMovieId: " . $e->getMessage());
            return '';
        }
    }

    public function getCountriesByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.name
                FROM countries c
                JOIN movie_countries mc ON c.id = mc.country_id
                WHERE mc.movie_id = ?
            ");
            $stmt->execute(array($movie_id));
            $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return implode(', ', $countries);
        } catch (Exception $e) {
            error_log("Error in getCountriesByMovieId: " . $e->getMessage());
            return '';
        }
    }

    public function getActorsByMovieId($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.name
                FROM actors a
                JOIN movie_actors ma ON a.id = ma.actor_id
                WHERE ma.movie_id = ?
            ");
            $stmt->execute(array($movie_id));
            $actors = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return implode(', ', $actors);
        } catch (Exception $e) {
            error_log("Error in getActorsByMovieId: " . $e->getMessage());
            return '';
        }
    }

    public function getAverageRating($movie_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(rating) as average_rating
                FROM ratings
                WHERE movie_id = ?
            ");
            $stmt->execute(array($movie_id));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $average = isset($result['average_rating']) && $result['average_rating'] !== null ? $result['average_rating'] : 0;
            return round($average, 1);
        } catch (Exception $e) {
            error_log("Error in getAverageRating: " . $e->getMessage());
            return 0;
        }
    }
}
?>