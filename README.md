Hướng dẫn thiết lập môi trường phát triển Web (PHP, HTML, CSS, JS, MySQL)
Hướng dẫn này giúp bạn thiết lập môi trường phát triển web sử dụng XAMPP hoặc Laragon trên Windows để chạy ứng dụng sử dụng PHP, HTML, CSS, JavaScript, và MySQL. Bạn sẽ được hướng dẫn cách chọn giữa XAMPP và Laragon, cài đặt MySQL (nếu dùng XAMPP), sao chép và chạy file SQL, đồng thời cấu hình kết nối database với dự án.
Mục lục

Yêu cầu hệ thống
Lựa chọn công cụ
Cài đặt XAMPP
Cài đặt Laragon
Cấu hình MySQL và chạy file SQL
Cấu hình kết nối database
Kiểm tra môi trường
Tạo dự án mẫu
Lưu ý

Yêu cầu hệ thống

Hệ điều hành: Windows 10/11 (64-bit khuyến nghị).
RAM: Tối thiểu 4GB (khuyến nghị 8GB trở lên).
Dung lượng ổ đĩa: Tối thiểu 2GB trống.
Quyền quản trị viên để cài đặt phần mềm.

Lựa chọn công cụ
Bạn có thể chọn XAMPP hoặc Laragon để thiết lập môi trường:

XAMPP: Bộ công cụ tích hợp Apache, MySQL, PHP, và phpMyAdmin. Phù hợp nếu bạn muốn một giải pháp phổ biến, dễ cấu hình.
Laragon: Nhẹ, dễ dùng, hỗ trợ tốt cho các dự án PHP, tích hợp Apache/Nginx và MySQL. Khuyến nghị nếu bạn muốn môi trường nhanh và linh hoạt.

Lưu ý: Nếu chọn XAMPP, bạn cần đảm bảo cài đặt MySQL (đã bao gồm trong gói XAMPP). Laragon tự động tích hợp MySQL.
Cài đặt XAMPP
XAMPP cung cấp môi trường phát triển đầy đủ với Apache, MySQL, PHP, và phpMyAdmin.

Tải XAMPP:

Truy cập trang chính thức của XAMPP.
Tải phiên bản mới nhất cho Windows (khuyến nghị PHP 8.x).
Đảm bảo MySQL được bao gồm trong gói cài đặt (mặc định đã có).


Cài đặt XAMPP:

Chạy file cài đặt (.exe) với quyền quản trị viên.
Chọn các thành phần: Apache, MySQL, PHP, và phpMyAdmin.
Cài đặt vào thư mục mặc định (C:\xampp) hoặc thư mục tùy chọn.


Khởi động XAMPP:

Mở XAMPP Control Panel.
Khởi động Apache và MySQL bằng cách nhấn nút Start tương ứng.


Kiểm tra cài đặt:

Mở trình duyệt, truy cập http://localhost.
Nếu thấy trang chào mừng của XAMPP, cài đặt thành công.



Cài đặt Laragon
Laragon là giải pháp nhẹ, tích hợp Apache/Nginx, MySQL, PHP, và các công cụ quản lý database như HeidiSQL.

Tải Laragon:

Truy cập trang chính thức của Laragon.
Tải phiên bản Laragon Full (bao gồm PHP, MySQL, Apache/Nginx).


Cài đặt Laragon:

Chạy file cài đặt (.exe) với quyền quản trị viên.
Cài đặt vào thư mục mặc định (C:\laragon) hoặc thư mục tùy chọn.


Khởi động Laragon:

Mở giao diện Laragon hoặc Laragon Terminal.
Nhấn nút Start All để khởi động Apache/Nginx và MySQL.


Kiểm tra cài đặt:

Mở trình duyệt, truy cập http://localhost.
Nếu thấy trang chào mừng của Laragon, cài đặt thành công.



Cấu hình MySQL và chạy file SQL
1. Tạo database

XAMPP:
Truy cập phpMyAdmin tại http://localhost/phpmyadmin.
Nhấn New → đặt tên database là webphimm (hoặc tên khác nếu muốn) → nhấn Create.


Laragon:
Truy cập phpMyAdmin tại http://localhost/phpmyadmin hoặc dùng HeidiSQL (đi kèm Laragon).
Tạo database tương tự: đặt tên webphimm hoặc tên tùy chọn.



Lưu ý: Nếu bạn đổi tên database, cần cập nhật tên trong file cấu hình (xem phần Cấu hình kết nối database).
2. Sao chép và chạy file SQL
Giả sử dự án của bạn có file webphimm.sql chứa cấu trúc bảng và dữ liệu mẫu. Bạn cần sao chép code SQL và chạy qua công cụ như MySQL (trong Laragon) hoặc MySQL Workbench, sau đó xóa file SQL để tránh xung đột.

Sử dụng MySQL (Laragon):

Mở Laragon Terminal (hoặc CMD trong thư mục C:\laragon\bin\mysql\mysql-<version>\bin).
Đăng nhập vào MySQL:mysql -u root -p

(Nhấn Enter nếu không có mật khẩu).
Chọn database:USE webphimm;


Mở file webphimm.sql bằng trình soạn thảo (như Notepad hoặc VS Code), sao chép toàn bộ code SQL.
Dán code vào terminal MySQL và nhấn Enter để chạy.
Kiểm tra bảng:SHOW TABLES;


Xóa file webphimm.sql trong thư mục dự án (C:\laragon\www\myproject) để tránh xung đột.


Sử dụng MySQL Workbench:

Cài đặt MySQL Workbench nếu chưa có.
Mở MySQL Workbench, kết nối đến server MySQL (localhost, user root, không có mật khẩu mặc định).
Chọn database webphimm.
Vào menu Server → Data Import hoặc mở tab Query, sao chép code từ file webphimm.sql và chạy.
Kiểm tra bảng bằng câu lệnh SHOW TABLES;.
Xóa file webphimm.sql trong thư mục dự án (C:\xampp\htdocs\myproject hoặc C:\laragon\www\myproject).


Kiểm tra: Sau khi chạy SQL, dùng phpMyAdmin hoặc HeidiSQL để kiểm tra xem các bảng đã được tạo trong database webphimm chưa.

Lưu ý: Xóa file webphimm.sql sau khi chạy để tránh trùng lặp hoặc xung đột khi triển khai dự án.


Cấu hình kết nối database
Dự án sử dụng PDO để kết nối MySQL. Dưới đây là cách cấu hình kết nối thông qua file app/config/config.php.
1. Cấu hình file app/config/config.php

Vào đường dẫn app/config/config.php trong thư mục dự án.

Kiểm tra biến $dbname. Mặc định:
<?php
$dbname = "webphimm"; // Tên database mặc định
$servername = "localhost";
$username = "root";
$password = "";
?>


Hướng dẫn:

Nếu bạn đã tạo database với tên khác webphimm, thay đổi $dbname trong file này thành tên database bạn đã tạo.
Nếu bạn giữ tên database là webphimm, không cần thay đổi.
File này được sử dụng để cung cấp thông tin kết nối trong các file PHP khác. Ví dụ:require_once 'app/config/config.php';
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage()]);
    exit();
}




Đảm bảo: File webphimm.sql đã được chạy trong phpMyAdmin, HeidiSQL, MySQL (Laragon), hoặc MySQL Workbench để tạo database và bảng trước khi kết nối.


Kiểm tra môi trường
1. Kiểm tra PHP

Tạo file test.php trong thư mục dự án (C:\xampp\htdocs hoặc C:\laragon\www):<?php
phpinfo();
?>


Truy cập http://localhost/test.php. Nếu thấy thông tin PHP, môi trường PHP hoạt động.

2. Kiểm tra kết nối MySQL

Tạo file db_test.php:<?php
require_once 'app/config/config.php';
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    echo "Kết nối MySQL thành công!";
} catch (PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
}
?>


Truy cập http://localhost/db_test.php. Nếu thấy thông báo thành công, kết nối MySQL hoạt động.

3. Kiểm tra HTML/CSS/JS

Tạo file index.html:<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <style>
        body { background-color: lightblue; }
        h1 { color: navy; }
    </style>
</head>
<body>
    <h1>Xin chào</h1>
    <script>
        alert("HTML, CSS, JS hoạt động!");
    </script>
</body>
</html>


Truy cập http://localhost/index.html. Nếu trang hiển thị đúng, môi trường hoạt động tốt.

Tạo dự án mẫu

Tạo thư mục myproject trong C:\xampp\htdocs (XAMPP) hoặc C:\laragon\www (Laragon).
Tạo cấu trúc thư mục:myproject/
├── app/
│   └── config/
│       └── config.php
├── index.php
├── style.css
├── script.js
└── webphimm.sql


Ví dụ file index.php:<?php
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
    <h1>Chào mừng đến với dự án của tôi</h1>
    <script src="script.js"></script>
</body>
</html>


Đảm bảo file webphimm.sql đã được chạy và xóa khỏi thư mục dự án sau khi import.

Lưu ý

Cổng xung đột: Nếu Apache hoặc MySQL không khởi động, kiểm tra cổng 80 (Apache) hoặc 3306 (MySQL) bằng lệnh netstat -aon trong CMD.
Bảo mật: Đặt mật khẩu cho user root của MySQL trong môi trường sản xuất.
Cập nhật tên database: Nếu bạn đổi tên database khác webphimm, cập nhật trong app/config/config.php.
Sao lưu SQL: Sao lưu file webphimm.sql trước khi xóa khỏi dự án.
Tài liệu tham khảo:
Tài liệu PHP
Tài liệu MySQL
Tài liệu PDO
Hướng dẫn Laragon
Hướng dẫn XAMPP



