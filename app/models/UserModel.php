<?php
require_once __DIR__ . '/../config/config.php';

class UserModel {
    private $conn;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception('Không thể kết nối đến database: Kiểm tra file config.php');
        }
        $this->conn = $conn;
    }

    public function getUserByUsername($username) {
        $username = trim($username);
        if (empty($username)) {
            return false;
        }
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: false;
        } catch (PDOException $e) {
            error_log("Lỗi trong getUserByUsername: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            return false;
        }
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, role, created_at, avatar FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: false;
        } catch (PDOException $e) {
            error_log("Lỗi trong getUserById: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($username, $email, $password) {
        try {
            // Kiểm tra email đã tồn tại
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return false; // Email đã tồn tại
            }

            // Thêm người dùng mới
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function updateAvatar($user_id, $avatar_path) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->bindParam(':avatar', $avatar_path, PDO::PARAM_STR);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Lỗi trong updateAvatar: " . $e->getMessage());
            return false;
        }
    }
}
?>