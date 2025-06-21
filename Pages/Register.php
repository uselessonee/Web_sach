<?php
session_start(); // Bắt đầu phiên làm việc

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbmDKy'])) {
    $name = trim($_POST['txtHTen'] ?? '');// htmlspecialchars(trim(filter_input(INPUT_POST, 'author', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
    $email = trim($_POST['txtEmail'] ?? '');
    $password = $_POST['txtMK'] ?? '';
    $confirm_password = $_POST['txtMKXN'] ?? '';

    // Đường dẫn đến file cơ sở dữ liệu SQLite
    $database_file = '../DB/Database.db';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Vui lòng điền đầy đủ tất cả các trường.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Địa chỉ email không hợp lệ.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    } elseif (strlen($password) < 6) { // Ví dụ: yêu cầu mật khẩu tối thiểu 6 ký tự
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        try {
            $pdo = new PDO("sqlite:" . $database_file);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Kiểm tra xem tên đăng nhập hoặc email đã tồn tại chưa
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = :name OR email = :email");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'Tên đăng nhập hoặc Email đã tồn tại.';
            } else {
                // Băm mật khẩu trước khi lưu vào cơ sở dữ liệu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password); // Sử dụng mật khẩu đã hashed
                $stmt->execute();

                $success_message = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                // Chuyển hướng người dùng về trang đăng nhập sau khi đăng ký thành công
                // header('Location: index.php?registration=success');
                // exit();
            }
        } catch (PDOException $e) {
            $error_message = 'Lỗi đăng ký: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trang đăng ký</title>
    <link rel="stylesheet" href="../css/Login.css" /> 
    <link rel="icon" href="../images/book.png" type="image/x-icon">
</head>
<body>
    <div class="background"></div>
    <div class="login-container">
        <h1>Đăng ký</h1>
        <?php if ($error_message): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p style="color: green; text-align: center;"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="txtHTen">Tên đăng nhập:</label>
            <input type="text" name="txtHTen" id="txtHTen" value="<?php echo htmlspecialchars($name ?? ''); ?>" required />

            <label for="txtEmail">Email:</label>
            <input type="email" name="txtEmail" id="txtEmail" value="<?php echo htmlspecialchars($email ?? ''); ?>" required />

            <label for="txtMK">Mật khẩu:</label>
            <input type="password" name="txtMK" id="txtMK" maxlength="30" required />

            <label for="txtMKXN">Xác nhận mật khẩu:</label>
            <input type="password" name="txtMKXN" id="txtMKXN" maxlength="30" required />

            <div class="buttons">
                <input type="submit" name="sbmDKy" value="Đăng ký" />
                <a href="../Pages/index.php" class="button-link">Đăng nhập</a>
            </div>
        </form>
    </div>
</body>
</html>