<?php
require_once '../app/models/ScheduleModel.php';

class ScheduleController {
    private $model;

    public function __construct($conn) {
        $this->model = new ScheduleModel($conn);
    }

    public function sendResponse($success, $data = [], $message = '') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $success,
            'data' => $data,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function getAllSchedules() {
        try {
            $date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;
            $schedules = $this->model->getAllSchedules($date);
            $this->sendResponse(true, $schedules);
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function getSchedulesByMovie($movie_id) {
        try {
            if ($movie_id <= 0) {
                $this->sendResponse(false, [], 'Invalid Movie ID');
            }
            $schedules = $this->model->getSchedulesByMovie($movie_id);
            $this->sendResponse(true, $schedules);
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function getScheduleById($schedule_id) {
        try {
            if ($schedule_id <= 0) {
                $this->sendResponse(false, [], 'Invalid Schedule ID');
            }
            $schedule = $this->model->getScheduleById($schedule_id);
            if ($schedule) {
                $this->sendResponse(true, $schedule);
            } else {
                $this->sendResponse(false, [], 'Schedule not found');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function getMoviesWithSchedules() {
        try {
            $movies = $this->model->getMoviesWithSchedules();
            $this->sendResponse(true, $movies);
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function addSchedule($movie_id, $room_id, $showtime) {
        try {
            if ($movie_id <= 0 || $room_id <= 0) {
                $this->sendResponse(false, [], 'Invalid Movie ID or Room ID');
            }
            $datetime = new DateTime($showtime);
            $date = $datetime->format('Y-m-d');
            $time = $datetime->format('H:i:s');

            if (!$this->model->isValidTimeRange($time)) {
                $this->sendResponse(false, [], 'Schedules can only be set between 7:00 and 23:00');
            }

            if (!$this->model->checkTimeGap($date, $time, $room_id)) {
                $this->sendResponse(false, [], 'This time slot is too close to an existing schedule (must be at least 2 hours apart)');
            }

            $result = $this->model->addSchedule($movie_id, $room_id, $date, $time);
            if ($result['success']) {
                $this->sendResponse(true, ['id' => $result['id']], 'Schedule added successfully');
            } else {
                $this->sendResponse(false, [], 'Error adding schedule');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function updateSchedule($schedule_id, $movie_id, $room_id, $showtime) {
        try {
            if ($schedule_id <= 0 || $movie_id <= 0 || $room_id <= 0) {
                $this->sendResponse(false, [], 'Invalid Schedule ID, Movie ID, or Room ID');
            }
            $datetime = new DateTime($showtime);
            $date = $datetime->format('Y-m-d');
            $time = $datetime->format('H:i:s');

            if (!$this->model->isValidTimeRange($time)) {
                $this->sendResponse(false, [], 'Schedules can only be set between 7:00 and 23:00');
            }

            if (!$this->model->checkTimeGap($date, $time, $room_id, $schedule_id)) {
                $this->sendResponse(false, [], 'This time slot is too close to an existing schedule (must be at least 2 hours apart)');
            }

            if ($this->model->updateSchedule($schedule_id, $movie_id, $room_id, $date, $time)) {
                $this->sendResponse(true, [], 'Schedule updated successfully');
            } else {
                $this->sendResponse(false, [], 'Error updating schedule');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }

    public function deleteSchedule($schedule_id) {
        try {
            if ($schedule_id <= 0) {
                $this->sendResponse(false, [], 'Invalid Schedule ID');
            }
            $result = $this->model->deleteSchedule($schedule_id);
            if ($result['success']) {
                $this->sendResponse(true, [], 'Schedule deleted successfully');
            } else {
                $this->sendResponse(false, [], $result['message'] ?: 'Error deleting schedule');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, [], 'Error: ' . $e->getMessage());
        }
    }
}
?>