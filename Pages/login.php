<?php
session_start(); // Bắt đầu phiên làm việc để lưu trạng thái đăng nhập

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbmDNhap'])) {
    $username = $_POST['txtHTen'] ?? '';
    $password = $_POST['txtMK'] ?? '';

    // Đường dẫn đến file cơ sở dữ liệu SQLite
    $database_file ='../DB/Database.db';

    try {
        $pdo = new PDO("sqlite:" . $database_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Chuẩn bị và thực thi truy vấn để lấy thông tin người dùng
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE name = :username OR email = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) { // Sử dụng password_verify để xác minh mật khẩu đã hashed
                // Lưu thông tin người dùng vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                header('Location: ../index.php'); // Chuyển hướng đến trang chào mừng hoặc trang chính sau khi đăng nhập thành công
                exit();
            } else {
                $error_message = 'Tên đăng nhập hoặc mật khẩu không đúng.';
            }
        } else {
            $error_message = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    } catch (PDOException $e) {
        $error_message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trang đăng nhập</title>
    <link rel="stylesheet" href="../css/Login.css" /> </head>
<body>
    <div class="background"></div>
    <div class="login-container">
        <h1>Đăng nhập</h1>
        <?php if ($error_message): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="txtHTen">Tên đăng nhập hoặc Email:</label>
            <input type="text" name="txtHTen" id="txtHTen" required />

            <label for="txtMK">Mật khẩu:</label>
            <input type="password" name="txtMK" id="txtMK" maxlength="30" required />

            <div class="buttons">
                <input type="submit" name="sbmDNhap" value="Đăng nhập" />
                <a href="./register.php" class="button-link">Đăng kí</a> </div>
        </form>
    </div>
</body>
</html>