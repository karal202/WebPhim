<?php
class ServiceModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getConn() {
        return $this->conn;
    }

    public function getAllServices() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM services");
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'data' => $services,
                'message' => empty($services) ? 'Không có dịch vụ nào' : 'Lấy danh sách dịch vụ thành công'
            ];
        } catch (PDOException $e) {
            error_log("ServiceModel::getAllServices - Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi truy vấn: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("ServiceModel::getAllServices - Unexpected error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi không xác định: ' . $e->getMessage()];
        }
    }

    public function addService($name, $price, $image_url) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO services (name, price, image_url, created_at) VALUES (:name, :price, :image_url, NOW())");
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':image_url' => $image_url
            ]);
            return ['success' => true, 'data' => [], 'message' => 'Thêm dịch vụ thành công'];
        } catch (PDOException $e) {
            error_log("ServiceModel::addService - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getServiceById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM services WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($service) {
                return ['success' => true, 'data' => $service, 'message' => 'Lấy dịch vụ thành công'];
            }
            return ['success' => false, 'data' => [], 'message' => 'Dịch vụ không tồn tại'];
        } catch (PDOException $e) {
            error_log("ServiceModel::getServiceById - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi truy vấn: ' . $e->getMessage()];
        }
    }

    public function updateService($id, $name, $price, $image_url) {
        try {
            $stmt = $this->conn->prepare("UPDATE services SET name = :name, price = :price, image_url = :image_url WHERE id = :id");
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':price' => $price,
                ':image_url' => $image_url
            ]);
            return ['success' => true, 'data' => [], 'message' => 'Cập nhật dịch vụ thành công'];
        } catch (PDOException $e) {
            error_log("ServiceModel::updateService - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function deleteService($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM services WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'data' => [], 'message' => 'Xóa dịch vụ thành công'];
            }
            return ['success' => false, 'data' => [], 'message' => 'Dịch vụ không tồn tại'];
        } catch (PDOException $e) {
            error_log("ServiceModel::deleteService - Error: " . $e->getMessage());
            return ['success' => false, 'data' => [], 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
?>