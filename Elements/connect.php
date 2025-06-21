<?php
function connect_db() {
    $database_file = '../DB/Database.db';
    try {
        $pdo = new PDO("sqlite:" . $database_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Lỗi kết nối database: " . $e->getMessage());
    }
}
?>