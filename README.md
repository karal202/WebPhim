# Hướng dẫn thiết lập môi trường Web (PHP, HTML, CSS, JS, MySQL)

Hướng dẫn này giúp bạn thiết lập môi trường phát triển web trên Windows với **XAMPP** hoặc **Laragon**, sử dụng **PHP**, **HTML**, **CSS**, **JavaScript**, và **MySQL**. Bạn sẽ chọn công cụ, cài MySQL (nếu dùng XAMPP), chạy file SQL, và cấu hình kết nối database.

## Mục lục
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Lựa chọn công cụ](#lựa-chọn-công-cụ)
- [Cài đặt XAMPP](#cài-đặt-xampp)
- [Cài đặt Laragon](#cài-đặt-laragon)
- [Cấu hình MySQL và chạy file SQL](#cấu-hình-mysql-và-chạy-file-sql)
- [Cấu hình kết nối database](#cấu-hình-kết-nối-database)
- [Kiểm tra môi trường](#kiểm-tra-môi-trường)
- [Tạo dự án mẫu](#tạo-dự-án-mẫu)
- [Lưu ý](#lưu-ý)

## Yêu cầu hệ thống
- **Hệ điều hành**: Windows 10/11 (64-bit khuyến nghị).
- **RAM**: Tối thiểu 4GB (khuyến nghị 8GB).
- **Dung lượng**: Tối thiểu 2GB trống.
- **Quyền**: Quản trị viên để cài đặt.

## Lựa chọn công cụ
- **XAMPP**: Tích hợp Apache, MySQL, PHP, phpMyAdmin. Phù hợp cho giải pháp phổ biến, dễ dùng.
- **Laragon**: Nhẹ, linh hoạt, tích hợp Apache/Nginx, MySQL. Khuyến nghị cho tốc độ và tiện lợi.

**Lưu ý**: XAMPP bao gồm MySQL. Laragon tự tích hợp MySQL.

## Cài đặt XAMPP
1. **Tải XAMPP**:
   - Vào [apachefriends.org](https://www.apachefriends.org/download.html), tải phiên bản mới nhất (PHP 8.x).
2. **Cài đặt**:
   - Chạy file `.exe` với quyền quản trị viên.
   - Chọn: Apache, MySQL, PHP, phpMyAdmin.
   - Cài vào `C:\xampp` (mặc định) hoặc tùy chọn.
3. **Khởi động**:
   - Mở XAMPP Control Panel, nhấn **Start** cho Apache và MySQL.
4. **Kiểm tra**:
   - Truy cập `http://localhost`. Nếu thấy trang chào mừng, cài đặt thành công.

## Cài đặt Laragon
1. **Tải Laragon**:
   - Vào [laragon.org](https://laragon.org/download), tải phiên bản **Laragon Full**.
2. **Cài đặt**:
   - Chạy file `.exe` với quyền quản trị viên.
   - Cài vào `C:\laragon` (mặc định) hoặc tùy chọn.
3. **Khởi động**:
   - Mở Laragon, nhấn **Start All** để chạy Apache/Nginx và MySQL.
4. **Kiểm tra**:
   - Truy cập `http://localhost`. Nếu thấy trang chào mừng, cài đặt thành công.

## Cấu hình MySQL và chạy file SQL
### 1. Tạo database
- **XAMPP**:
  - Vào `http://localhost/phpmyadmin`, nhấn **New**, đặt tên `webphimm` (hoặc tùy chọn), nhấn **Create**.
- **Laragon**:
  - Vào `http://localhost/phpmyadmin` hoặc dùng HeidiSQL.
  - Tạo database `webphimm` (hoặc tùy chọn).

**Lưu ý**: Nếu đổi tên database, cập nhật trong `app/config/config.php`.

### 2. Chạy file SQL
Dự án có file `webphimm.sql`. Sao chép code SQL và chạy qua MySQL (Laragon) hoặc MySQL Workbench, sau đó xóa file để tránh xung đột.

- **MySQL (Laragon)**:
  1. Mở Laragon Terminal (hoặc CMD tại `C:\laragon\bin\mysql\mysql-<version>\bin`).
  2. Đăng nhập: `mysql -u root -p` (Enter nếu không có mật khẩu).
  3. Chọn database: `USE webphimm;`.
  4. Sao chép code từ `webphimm.sql` (dùng Notepad/VS Code), dán vào terminal, nhấn Enter.
  5. Kiểm tra: `SHOW TABLES;`.
  6. Xóa file `webphimm.sql` trong `C:\laragon\www\myproject`.

- **MySQL Workbench**:
  1. Cài [MySQL Workbench](https://dev.mysql.com/downloads/workbench/).
  2. Kết nối MySQL (localhost, user `root`, không mật khẩu).
  3. Chọn database `webphimm`.
  4. Vào **Server** → **Data Import** hoặc tab **Query**, dán code từ `webphimm.sql`, chạy.
  5. Kiểm tra: `SHOW TABLES;`.
  6. Xóa file `webphimm.sql` trong `C:\xampp\htdocs\myproject` hoặc `C:\laragon\www\myproject`.

- **Kiểm tra**: Dùng phpMyAdmin/HeidiSQL để xác nhận bảng trong `webphimm`.

## Cấu hình kết nối database
Dự án dùng PDO để kết nối MySQL qua `app/config/config.php`.

### 1. Cấu hình `app/config/config.php`
- Vào `app/config/config.php`:
  ```php
  <?php
  $dbname = "webphimm";
  $servername = "localhost";
  $username = "root";
  $password = "";
  ?>
  ```
- **Hướng dẫn**:
  - Nếu database không phải `webphimm`, đổi `$dbname` thành tên đã tạo.
  - Nếu giữ tên `webphimm`, không cần chỉnh sửa.
  - File này dùng cho kết nối PDO. Ví dụ:
    ```php
    require_once 'app/config/config.php';
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES 'utf8'");
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Kết nối thất bại: ' . $e->getMessage()]);
        exit();
    }
    ```

- **Đảm bảo**: File `webphimm.sql` đã được chạy trước khi kết nối.

## Kiểm tra môi trường
### 1. Kiểm tra PHP
- Tạo `test.php` trong `C:\xampp\htdocs` hoặc `C:\laragon\www`:
  ```php
  <?php phpinfo(); ?>
  ```
- Truy cập `http://localhost/test.php`. Nếu thấy thông tin PHP, PHP hoạt động.

### 2. Kiểm tra MySQL
- Tạo `db_test.php`:
  ```php
  <?php
  require_once 'app/config/config.php';
  try {
      $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
      echo "Kết nối MySQL thành công!";
  } catch (PDOException $e) {
      echo "Kết nối thất bại: " . $e->getMessage();
  }
  ?>
  ```
- Truy cập `http://localhost/db_test.php`. Nếu thấy thông báo thành công, MySQL hoạt động.

### 3. Kiểm tra HTML/CSS/JS
- Tạo `index.html`:
  ```html
  <!DOCTYPE html>
  <html>
  <head>
      <title>Test</title>
      <style>body { background: lightblue; } h1 { color: navy; }</style>
  </head>
  <body>
      <h1>Xin chào</h1>
      <script>alert("HTML, CSS, JS hoạt động!");</script>
  </body>
  </html>
  ```
- Truy cập `http://localhost/index.html`. Nếu hiển thị đúng, môi trường hoạt động.

## Tạo dự án mẫu
1. Tạo thư mục `myproject` trong `C:\xampp\htdocs` (XAMPP) hoặc `C:\laragon\www` (Laragon).
2. Tạo cấu trúc:
   ```
   myproject/
   ├── app/
   │   └── config/
   │       └── config.php
   ├── index.php
   ├── style.css
   ├── script.js
   └── webphimm.sql
   ```
3. Ví dụ `index.php`:
   ```php
   <?php
   require_once 'app/config/config.php';
   try {
       $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
       echo "Kết nối database thành công!";
   } catch (PDOException $e) {
       echo "Kết nối thất bại: " . $e->getMessage();
   }
   ?>
   <!DOCTYPE html>
   <html>
   <head>
       <title>My Project</title>
       <link rel="stylesheet" href="style.css">
   </head>
   <body>
       <h1>Chào mừng đến với dự án</h1>
       <script src="script.js"></script>
   </body>
   </html>
   ```
4. Đảm bảo chạy và xóa `webphimm.sql` sau khi import.

## Lưu ý
- **Cổng xung đột**: Kiểm tra cổng 80 (Apache) hoặc 3306 (MySQL) bằng `netstat -aon` nếu dịch vụ không chạy.
- **Bảo mật**: Đặt mật khẩu cho user `root` trong môi trường sản xuất.
- **Database**: Cập nhật `$dbname` trong `app/config/config.php` nếu đổi tên.
- **Sao lưu**: Lưu file `webphimm.sql` trước khi xóa.
- **Tài liệu**:
  - [PHP](https://www.php.net/docs.php)
  - [MySQL](https://dev.mysql.com/doc/)
  - [PDO](https://www.php.net/manual/en/book.pdo.php)
  - [Laragon](https://laragon.org/docs)
  - [XAMPP](https://www.apachefriends.org/docs/)
