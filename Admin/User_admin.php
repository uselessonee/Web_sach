<?php

// --- Cấu hình ---
$databaseFile = '../DB/Database.db';

// Khởi tạo biến cho thông báo
$message = '';
$messageType = ''; // 'success' hoặc 'error'

// --- Kết nối cơ sở dữ liệu ---
try {
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Bật ngoại lệ cho lỗi
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Lấy dữ liệu dạng mảng kết hợp

    // --- Tạo bảng nếu chưa tồn tại ---
    // Bảng người dùng
    $createUsersTableSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        );
    ";
    $pdo->exec($createUsersTableSQL);

    // Bảng liên kết người dùng - sách theo dõi (quan hệ nhiều-nhiều)
    // Bảng này liên kết người dùng với các sách mà họ theo dõi.
    $createUserBooksFollowedTableSQL = "
        CREATE TABLE IF NOT EXISTS user_books_followed (
            user_id INTEGER NOT NULL,
            book_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, book_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        );
    ";
    $pdo->exec($createUserBooksFollowedTableSQL);

    // --- Xử lý gửi form thêm người dùng mới ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
        // Lọc và kiểm tra dữ liệu đầu vào
        $name = htmlspecialchars(trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Lấy mật khẩu thô để băm

        // Kiểm tra các trường bắt buộc
        if (empty($name) || empty($email) || empty($password)) {
            $message = 'Tên, Email và Mật khẩu là các trường bắt buộc.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Vui lòng nhập địa chỉ email hợp lệ.';
            $messageType = 'error';
        } else {
            // Hash mật khẩu
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Chuẩn bị và thực thi câu lệnh SQL INSERT
            $insertSQL = "
                INSERT INTO users (name, email, password)
                VALUES (:name, :email, :password)
            ";
            $stmt = $pdo->prepare($insertSQL);

            try {
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);
                $message = 'Thêm người dùng thành công!';
                $messageType = 'success';

            } catch (PDOException $e) {
                // Kiểm tra lỗi trùng email (unique)
                if ($e->getCode() == '23000') { // Mã lỗi vi phạm unique của SQLite
                    $message = 'Lỗi: Đã tồn tại người dùng với email này.';
                } else {
                    $message = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
                }
                $messageType = 'error';
            }
        }
    }

    // --- Lấy tất cả người dùng và sách họ theo dõi để hiển thị ---
    $users = []; // Khởi tạo mảng rỗng cho Admin
    $selectUsersSQL = "
        SELECT
            u.id,
            u.name,
            u.email,
            GROUP_CONCAT(b.title, '; ') AS followed_books_titles
        FROM
            users u
        LEFT JOIN
            user_books_followed ubf ON u.id = ubf.user_id
        LEFT JOIN
            books b ON ubf.book_id = b.id
        GROUP BY
            u.id, u.name, u.email
        ORDER BY
            u.name ASC;
    ";
    $stmt = $pdo->query($selectUsersSQL);
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    $message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    $messageType = 'error';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Admin Panel</title>
    <style>
        body {
            background-color: #f4f7f6;
            color: #333;
        }
        .container {
            max-width: 960px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db; 
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); 
        }
        .btn-primary {
            background-color: #22c55e;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out;
            cursor: pointer;
            border: none;
        }
        .btn-primary:hover {
            background-color: #16a34a;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .message.success {
            background-color: #dcfce7; 
            color: #15803d; 
            border: 1px solid #bbf7d0;
        }
        .message.error {
            background-color: #fee2e2; 
            color: #b91c1c;
            border: 1px solid #fca5a5; 
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden; /* Đảm bảo bo góc cho bảng */
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb; 
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #4b5563;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 >User Admin Panel</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div>
            <h2 >Thêm người dùng mới</h2>
            <form action="admin_users.php" method="POST">
                <div>
                    <label for="name">Tên <span>*</span></label>
                    <input type="text" id="name" name="name" required class="form-input" placeholder="Ví dụ: Rick ashley">
                </div>
                <div>
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" required class="form-input" placeholder="Ví dụ: nguyenvana@email.com">
                </div>
                <div>
                    <label for="password">Mật khẩu <span>*</span></label>
                    <input type="password" id="password" name="password" required class="form-input" placeholder="Nhập mật khẩu">
                </div>
                <button type="submit" name="add_user" >Thêm người dùng</button>
            </form>
        </div>

        <div>
            <h2>Người dùng hiện có</h2>
            <?php if (!empty($users)): ?>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th >Tên</th>
                                <th >Email</th>
                                <th>Sách đang theo dõi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td ><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td">
                                        <?php
                                        // Hiển thị sách đang theo dõi. GROUP_CONCAT nối chúng thành một chuỗi, phân tách bởi '; '
                                        echo !empty($user['followed_books_titles'])
                                            ? htmlspecialchars(str_replace(';', ', ', $user['followed_books_titles'])) // Đổi dấu chấm phẩy thành dấu phẩy để hiển thị
                                            : 'Chưa theo dõi sách nào.';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Không tìm thấy người dùng nào trong cơ sở dữ liệu. Hãy thêm mới bằng form phía trên!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
