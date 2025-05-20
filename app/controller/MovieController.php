<?php
require_once __DIR__ . '/../models/MovieModel.php';

class MovieController {
    private $movieModel;

    public function __construct() {
        try {
            $this->movieModel = new MovieModel();
        } catch (Exception $e) {
            error_log("Error initializing MovieModel: " . $e->getMessage());
            throw new Exception("Không thể khởi tạo MovieController");
        }
    }

    public function getAllMovies($random = false, $limit = 0) {
        $movies = $this->movieModel->getAllMovies($random, $limit);
        if ($movies === false) {
            return false;
        }
        return $this->enrichMovies($movies);
    }

    public function getTrendingMovies() {
        $movies = $this->movieModel->getTrendingMovies();
        if ($movies === false) {
            return false;
        }
        return $this->enrichMovies($movies);
    }

    public function getThuyetMinhMovies() {
        $movies = $this->movieModel->getThuyetMinhMovies();
        if ($movies === false) {
            return false;
        }
        return $this->enrichMovies($movies);
    }

    public function getVietsubMovies() {
        $movies = $this->movieModel->getVietsubMovies();
        if ($movies === false) {
            return false;
        }
        return $this->enrichMovies($movies);
    }

    public function getTheaterMovies($random = false, $limit = 0) {
        $movies = $this->movieModel->getTheaterMovies($random, $limit);
        if ($movies === false) {
            return false;
        }
        return $this->enrichMovies($movies);
    }

    public function getMovieById($id) {
        $movie = $this->movieModel->getMovieById($id);
        if ($movie === null || $movie === false) {
            return $movie;
        }
        return $this->enrichMovie($movie);
    }

    public function getMoviesByGenre($genre) {
        $movies = $this->movieModel->getMoviesByGenre($genre);
        if (empty($movies)) {
            return array();
        }
        return $this->enrichMovies($movies);
    }

    public function getMoviesByCountry($country) {
        $movies = $this->movieModel->getMoviesByCountry($country);
        if (empty($movies)) {
            return array();
        }
        return $this->enrichMovies($movies);
    }

    public function searchMovies($query) {
        $movies = $this->movieModel->searchMovies($query);
        if (empty($movies)) {
            return array();
        }
        return $this->enrichMovies($movies);
    }

    public function addMovie($input, $thumbnailFile, $videoFile) {
        $genres = !empty($input['genres']) ? array_map('trim', explode(',', $input['genres'])) : [];
        $countries = !empty($input['countries']) ? array_map('trim', explode(',', $input['countries'])) : [];
        $actors = !empty($input['actors']) ? array_map('trim', explode(',', $input['actors'])) : [];
        $movieId = $this->movieModel->addMovie($input, $genres, $countries, $actors, $thumbnailFile, $videoFile);
        if ($movieId === false) {
            return ['error' => 'Không thể thêm phim'];
        }
        return ['success' => 'Thêm phim thành công', 'movie_id' => $movieId];
    }

    public function updateMovie($input, $thumbnailFile, $videoFile) {
        $genres = !empty($input['genres']) ? array_map('trim', explode(',', $input['genres'])) : [];
        $countries = !empty($input['countries']) ? array_map('trim', explode(',', $input['countries'])) : [];
        $actors = !empty($input['actors']) ? array_map('trim', explode(',', $input['actors'])) : [];
        $result = $this->movieModel->updateMovie($input, $genres, $countries, $actors, $thumbnailFile, $videoFile);
        if ($result === false) {
            return ['error' => 'Không thể cập nhật phim'];
        }
        return ['success' => 'Cập nhật phim thành công'];
    }

    public function deleteMovie($id) {
        $result = $this->movieModel->deleteMovie($id);
        if ($result === false) {
            return ['error' => 'Không thể xóa phim'];
        }
        return ['success' => 'Xóa phim thành công'];
    }

    private function enrichMovies($movies) {
        foreach ($movies as &$movie) {
            $movie['genres'] = $this->movieModel->getGenresByMovieId($movie['id']);
            $movie['countries'] = $this->movieModel->getCountriesByMovieId($movie['id']);
            $movie['actors'] = $this->movieModel->getActorsByMovieId($movie['id']);
            $movie['average_rating'] = $this->movieModel->getAverageRating($movie['id']);
        }
        return $movies;
    }

    private function enrichMovie($movie) {
        $movie['genres'] = $this->movieModel->getGenresByMovieId($movie['id']);
        $movie['countries'] = $this->movieModel->getCountriesByMovieId($movie['id']);
        $movie['actors'] = $this->movieModel->getActorsByMovieId($movie['id']);
        $movie['average_rating'] = $this->movieModel->getAverageRating($movie['id']);
        return $movie;
    }
}
?>