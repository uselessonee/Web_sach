<?php

// --- Cấu hình ---
$databaseFile = '../DB/Database.db';

$uploadDir = 'uploads/user_pfp/';

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
            password TEXT NOT NULL,
            profile_picture TEXT DEFAULT NULL
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
        $profilePicturePath = null; // Initialize profile picture path

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = basename($_FILES['profile_picture']['name']);
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Generate a unique file name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $profilePicturePath = $destPath;
                } else {
                    $message = 'Lỗi khi tải lên ảnh đại diện.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Loại tệp ảnh đại diện không hợp lệ. Chỉ chấp nhận JPG, GIF, PNG, JPEG.';
                $messageType = 'error';
            }
        }

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
                INSERT INTO users (name, email, password, profile_picture)
                VALUES (:name, :email, :password, :profile_picture)
            ";
            $stmt = $pdo->prepare($insertSQL);

            try {
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':profile_picture' => $profilePicturePath // Save the path to the database
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
            u.profile_picture, -- Select the profile picture
            GROUP_CONCAT(b.title, '; ') AS followed_books_titles
        FROM
            users u
        LEFT JOIN
            user_books_followed ubf ON u.id = ubf.user_id
        LEFT JOIN
            books b ON ubf.book_id = b.id
        GROUP BY
            u.id, u.name, u.email, u.profile_picture
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
    <link rel="icon" href="../images/logo/book.png" type="image/x-icon">
    <title>User Admin Panel</title>
    <style>
        body {
            background-color: #f4f7f6;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 960px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        form div {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4b5563;
        }
        label span {
            color: #ef4444; /* Red color for required indicator */
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db; 
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box; /* Include padding in width */
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
            margin-top: 1rem;
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
            overflow: hidden; /* Ensure rounded corners for the table */
            margin-top: 2rem;
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
        .profile-pic-thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Admin Panel</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div>
            <h2>Thêm người dùng mới</h2>
            <form action="User_admin.php" method="POST" enctype="multipart/form-data">
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
                <div>
                    <label for="profile_picture">Ảnh đại diện</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="form-input">
                </div>
                <button type="submit" name="add_user" class="btn-primary">Thêm người dùng</button>
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
                                <th>Ảnh</th> <th>Tên</th>
                                <th>Email</th>
                                <th>Sách đang theo dõi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td>
                                        <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-pic-thumbnail">
                                        <?php else: ?>
                                            <img src="uploads/profile_pictures/default.png" alt="Default Profile Picture" class="profile-pic-thumbnail">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
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