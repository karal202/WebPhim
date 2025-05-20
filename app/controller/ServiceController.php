<?php
require_once '../app/models/ServiceModel.php';

class ServiceController {
    private $serviceModel;

    public function __construct($conn) {
        error_log("ServiceController::construct - Initializing with conn: " . (isset($conn) ? 'Set' : 'Not set'));
        if (!$conn instanceof PDO) {
            throw new Exception('Database connection is not initialized');
        }
        $this->serviceModel = new ServiceModel($conn);
    }

    public function getAll() {
        return $this->serviceModel->getAllServices();
    }

    public function getById($id) {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'ID không hợp lệ'];
        }
        return $this->serviceModel->getServiceById($id);
    }

    public function add($data, $file = null) {
        $name = $data['name'] ?? '';
        $price = $data['price'] ?? 0;
        $image_url = $data['image_url'] ?? '';

        // Kiểm tra và tải lên ảnh nếu có
        if ($file && isset($file['image']) && $file['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadImage($file);
            if (!$uploadResult['success']) {
                return $uploadResult; // Trả về lỗi nếu tải lên thất bại
            }
            $image_url = $uploadResult['data']['path'];
        } elseif (empty($image_url)) {
            return ['success' => false, 'data' => [], 'message' => 'Không có file ảnh hoặc image_url được cung cấp'];
        }

        // Kiểm tra dữ liệu
        if (empty($name) || !is_numeric($price) || $price < 0) {
            return ['success' => false, 'data' => [], 'message' => 'Dữ liệu không hợp lệ'];
        }

        return $this->serviceModel->addService($name, $price, $image_url);
    }

    public function update($data, $file = null) {
        $id = $data['id'] ?? 0;
        $name = $data['name'] ?? '';
        $price = $data['price'] ?? 0;
        $image_url = $data['image_url'] ?? ''; // Giữ nguyên image_url cũ nếu không có ảnh mới

        // Kiểm tra và tải lên ảnh mới nếu có
        if ($file && isset($file['image']) && $file['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadImage($file);
            if (!$uploadResult['success']) {
                return $uploadResult; // Trả về lỗi nếu tải lên thất bại
            }
            $image_url = $uploadResult['data']['path'];
        }

        // Kiểm tra dữ liệu
        if (!is_numeric($id) || $id <= 0 || empty($name) || !is_numeric($price) || $price < 0 || empty($image_url)) {
            return ['success' => false, 'data' => [], 'message' => 'Dữ liệu không hợp lệ'];
        }

        return $this->serviceModel->updateService($id, $name, $price, $image_url);
    }

    public function delete($id) {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'ID không hợp lệ'];
        }

        return $this->serviceModel->deleteService($id);
    }

    public function uploadImage($file) {
        error_log("ServiceController::uploadImage - Starting upload process");
        
        if (!isset($file['image']) || $file['image']['error'] !== UPLOAD_ERR_OK) {
            error_log("ServiceController::uploadImage - Invalid file or upload error: " . ($file['image']['error'] ?? 'Unknown error'));
            return ['success' => false, 'data' => [], 'message' => 'Không có file hoặc file không hợp lệ'];
        }

        $uploadDir = __DIR__ . '/../../uploads/service/';
        error_log("ServiceController::uploadImage - Checking directory: $uploadDir");
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("ServiceController::uploadImage - Error: Failed to create directory $uploadDir");
                return ['success' => false, 'data' => [], 'message' => 'Không thể tạo thư mục lưu trữ'];
            }
            if (!is_writable($uploadDir)) {
                error_log("ServiceController::uploadImage - Error: Directory $uploadDir is not writable");
                return ['success' => false, 'data' => [], 'message' => 'Thư mục không có quyền ghi'];
            }
        }

        $ext = pathinfo($file['image']['name'], PATHINFO_EXTENSION);
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowedExt)) {
            error_log("ServiceController::uploadImage - Unsupported file format: $ext");
            return ['success' => false, 'data' => [], 'message' => 'Định dạng file không được hỗ trợ'];
        }

        $originalName = pathinfo($file['image']['name'], PATHINFO_FILENAME);
        $filename = $originalName . '_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;
        error_log("ServiceController::uploadImage - Moving file to: $destination");

        if (move_uploaded_file($file['image']['tmp_name'], $destination)) {
            $relativePath = "uploads/service/{$filename}";
            error_log("ServiceController::uploadImage - File uploaded successfully: $relativePath");
            return ['success' => true, 'data' => ['path' => $relativePath], 'message' => 'Tải lên thành công'];
        } else {
            error_log("ServiceController::uploadImage - Error: Failed to move uploaded file to $destination");
            return ['success' => false, 'data' => [], 'message' => 'Lỗi khi tải lên file: Không thể di chuyển file'];
        }
    }
}
?>