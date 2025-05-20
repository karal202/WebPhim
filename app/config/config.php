<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webphimm";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage()]);
    exit();
}
?>