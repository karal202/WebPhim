<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();
header('Content-Type: application/json; charset=UTF-8');

// Log session ID for debugging
error_log("auth.php - Session ID: " . session_id());
error_log("auth.php - Session data at start: " . print_r($_SESSION, true));

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!file_exists(__DIR__ . '/../app/models/UserModel.php')) {
    respond(['success' => false, 'message' => 'Không tìm thấy file UserModel.php']);
}

require_once __DIR__ . '/../app/models/UserModel.php';

try {
    $userModel = new UserModel();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'Phương thức không được hỗ trợ!']);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            respond(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
        }

        if (strlen($username) < 3 || strlen($password) < 6) {
            respond(['success' => false, 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự và mật khẩu ít nhất 6 ký tự!']);
        }

        $user = $userModel->getUserByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            respond([
                'success' => true,
                'message' => 'Đăng nhập thành công!',
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user'
            ]);
        } else {
            respond(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng!']);
        }
    } elseif ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            respond(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(['success' => false, 'message' => 'Email không hợp lệ!']);
        }

        if (strlen($username) < 3 || strlen($password) < 6) {
            respond(['success' => false, 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự và mật khẩu ít nhất 6 ký tự!']);
        }

        $existingUser = $userModel->getUserByUsername($username);
        if ($existingUser) {
            respond(['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại!']);
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($userModel->createUser($username, $email, $hashedPassword)) {
            $user = $userModel->getUserByUsername($username);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                respond([
                    'success' => true,
                    'message' => 'Đăng ký thành công!',
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'] ?? 'user'
                ]);
            } else {
                respond(['success' => false, 'message' => 'Đăng ký thành công nhưng không thể đăng nhập tự động!']);
            }
        } else {
            respond(['success' => false, 'message' => 'Đăng ký thất bại! Có lỗi xảy ra.']);
        }
    } elseif ($action === 'check_session') {
        if (isset($_SESSION['user_id'])) {
            $user = $userModel->getUserById($_SESSION['user_id']);
            if ($user) {
                respond([
                    'success' => true,
                    'message' => 'Phiên đăng nhập hợp lệ!',
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user',
                    'created_at' => $user['created_at'],
                    'avatar' => $user['avatar']
                ]);
            }
        }
        respond(['success' => false, 'message' => 'Chưa đăng nhập!']);
    } elseif ($action === 'logout') {
        session_unset();
        session_destroy();
        respond(['success' => true, 'message' => 'Đăng xuất thành công!']);
    } elseif ($action === 'update_avatar') {
        if (!isset($_SESSION['user_id'])) {
            respond(['success' => false, 'message' => 'Chưa đăng nhập']);
        }

        $user_id = $_SESSION['user_id'];

        if (isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            $upload_dir = __DIR__ . '/../uploads/avatars/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024;

            if (!in_array($file['type'], $allowed_types)) {
                respond(['success' => false, 'message' => 'Định dạng file không hợp lệ. Chỉ chấp nhận JPEG, PNG, GIF']);
            }

            if ($file['size'] > $max_size) {
                respond(['success' => false, 'message' => 'File quá lớn. Kích thước tối đa là 5MB']);
            }

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $result = $userModel->updateAvatar($user_id, 'uploads/avatars/' . $new_filename);
                if ($result) {
                    respond(['success' => true, 'message' => 'Cập nhật avatar thành công', 'avatar' => 'uploads/avatars/' . $new_filename]);
                } else {
                    unlink($upload_path);
                    respond(['success' => false, 'message' => 'Không thể cập nhật avatar']);
                }
            } else {
                respond(['success' => false, 'message' => 'Không thể tải file lên']);
            }
        } else {
            respond(['success' => false, 'message' => 'Vui lòng cung cấp file ảnh']);
        }
    } elseif ($action === 'get_payment_status') {
        error_log("get_payment_status - Session ID: " . session_id());
        error_log("get_payment_status - Session data: " . print_r($_SESSION, true));
        if (isset($_SESSION['payment_status'])) {
            respond([
                'success' => true,
                'payment_status' => $_SESSION['payment_status'],
                'vnp_txn_ref' => $_SESSION['vnp_TxnRef'] ?? 'N/A'
            ]);
        } else {
            respond([
                'success' => false,
                'message' => 'Không có thông tin trạng thái thanh toán'
            ]);
        }
    } elseif ($action === 'clear_payment_status') {
        unset($_SESSION['payment_status']);
        unset($_SESSION['vnp_TxnRef']);
        error_log("clear_payment_status - Session data after clearing: " . print_r($_SESSION, true));
        respond(['success' => true, 'message' => 'Đã xóa trạng thái thanh toán']);
    } else {
        respond(['success' => false, 'message' => 'Hành động không hợp lệ!']);
    }
} catch (Exception $e) {
    error_log("Error in auth.php: " . $e->getMessage());
    respond(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?>