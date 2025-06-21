<?php

// 死にたいああああああああああああああああああああああああああああああああああああああああああああ
$databaseFile = '../DB/Database.db';
$uploadDirectory = 'uploads/book_covers/'; // Thư mục để lưu ảnh bìa

// Biến cho thông báo (sẽ hiển thị trên UI)　死にたいああああああああああああああああああああああああああああああああああああああああああああ
$message = '';
$messageType = ''; // 'success' (thành công) hoặc 'error' (lỗi)

// --- Tạo thư mục tải lên nếu chưa tồn tại ---
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

// --- Kết nối Cơ sở dữ liệu ---　俺を殺せ！！！！！！！！！！
try {
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Bật ngoại lệ cho lỗi
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Lấy dòng dưới dạng mảng kết hợp

    // --- Tạo bảng 'books' nếu chưa tồn tại ---
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            author TEXT NOT NULL,
            cover_image_url TEXT, -- Vẫn giữ tên cột này nhưng sẽ lưu đường dẫn tệp
            summary TEXT,
            published_date TEXT,
            read_link TEXT UNIQUE
        );
    ";
    $pdo->exec($createTableSQL);

    // --- Xử lý Gửi Form khi thêm sách mới ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
        // Làm sạch và kiểm tra dữ liệu đầu vào từ form
        $title = htmlspecialchars(trim(filter_input(INPUT_POST, 'title', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars(trim(filter_input(INPUT_POST, 'author', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
        $summary = htmlspecialchars(trim(filter_input(INPUT_POST, 'summary', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
        $published_date = htmlspecialchars(trim(filter_input(INPUT_POST, 'published_date', FILTER_DEFAULT)), ENT_QUOTES, 'UTF-8');
        $read_link = filter_input(INPUT_POST, 'read_link', FILTER_SANITIZE_URL);

        // Loại bỏ khoảng trắng thừa từ đầu và cuối chuỗi
        $title = trim($title);
        $author = trim($author);
        $summary = trim($summary);
        $published_date = trim($published_date);

        // Xử lý tải lên ảnh bìa
        $cover_image_path = null;
        if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['cover_image_file']['tmp_name'];
            $fileName = $_FILES['cover_image_file']['name'];
            $fileSize = $_FILES['cover_image_file']['size'];
            $fileType = $_FILES['cover_image_file']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDirectory . $newFileName;

            $allowedFileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedFileExtensions)) {
                if ($fileSize < 5000000) { // Giới hạn 5MB
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $cover_image_path = $destPath;
                    } else {
                        $message = 'Không thể di chuyển tệp đã tải lên.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Kích thước tệp ảnh bìa quá lớn (tối đa 5MB).';
                    $messageType = 'error';
                }
            } else {
                $message = 'Định dạng tệp ảnh bìa không hợp lệ. Chỉ chấp nhận JPG, JPEG, PNG, GIF.';
                $messageType = 'error';
            }
        } elseif (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $message = 'Lỗi khi tải lên ảnh bìa: ' . $_FILES['cover_image_file']['error'];
            $messageType = 'error';
        }

        // Kiểm tra cơ bản cho các trường bắt buộc
        if (empty($title) || empty($author) || empty($read_link)) {
            $message = 'Tiêu đề, Tác giả và Đường dẫn đọc là các trường bắt buộc.';
            $messageType = 'error';
        } else {
            // Kiểm tra định dạng URL cho read_link nếu không rỗng
            if (!empty($read_link) && !filter_var($read_link, FILTER_VALIDATE_URL)) {
                $message = 'Trường "Đường dẫn đọc" phải là một URL hợp lệ.';
                $messageType = 'error';
            }
            // Kiểm tra định dạng ngày tháng (YYYY-MM-DD)
            else if (!empty($published_date) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $published_date)) {
                $message = 'Ngày xuất bản phải theo định dạng YYYY-MM-DD (ví dụ: 2023-01-15).';
                $messageType = 'error';
            } else {
                // Chỉ thực hiện thêm vào DB nếu không có lỗi từ việc tải lên tệp
                if ($messageType !== 'error') {
                    // Chuẩn bị câu lệnh SQL INSERT (Sử dụng Prepared Statements để ngăn chặn SQL Injection)
                    $insertSQL = "
                        INSERT INTO books (title, author, cover_image_url, summary, published_date, read_link)
                        VALUES (:title, :author, :cover_image_url, :summary, :published_date, :read_link)
                    ";
                    $stmt = $pdo->prepare($insertSQL);

                    try {
                        $stmt->execute([
                            ':title' => $title,
                            ':author' => $author,
                            ':cover_image_url' => $cover_image_path, // Lưu đường dẫn tệp vào DB
                            ':summary' => $summary,
                            ':published_date' => $published_date,
                            ':read_link' => $read_link
                        ]);
                        $message = 'Thêm sách thành công!';
                        $messageType = 'success';
                    } catch (PDOException $e) {
                        // Kiểm tra nếu lỗi là do vi phạm ràng buộc UNIQUE (ví dụ: trùng read_link)
                        if ($e->getCode() == '23000') { // Mã lỗi của SQLite cho vi phạm UNIQUE Constraint
                            $message = 'Lỗi: Đã tồn tại sách với "Đường dẫn đọc" này.';
                        } else {
                            $message = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
                        }
                        $messageType = 'error';
                    }
                }
            }
        }
    }

    // --- Lấy tất cả sách để hiển thị trong bảng ---
    $books = []; // Khởi tạo mảng rỗng cho sách
    $selectSQL = "SELECT id, title, author, cover_image_url, summary, published_date, read_link FROM books ORDER BY title ASC";
    $stmt = $pdo->query($selectSQL);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    // Xử lý lỗi kết nối cơ sở dữ liệu
    $message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    $messageType = 'error';
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Quản Trị Sách</title>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6; /* Nền xám xanh nhạt */
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
            margin-bottom: 1rem; /* Added margin-bottom for spacing */
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
            overflow: hidden; /* Đảm bảo các góc bo tròn trên bảng */
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
            background-color: #f9fafb; /* Dải màu nhạt hơn */
        }
        a {
            color: #2563eb; 
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        img {
            max-width: 64px; /* Kích thước ảnh bìa nhỏ */
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bảng Quản Trị Sách</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div>
            <h2>Thêm Sách Mới</h2>
            <form action="Book_admin.php" method="POST" enctype="multipart/form-data">
                <div>
                    <label for="title" >Tiêu đề <span>*</span></label>
                    <input type="text" id="title" name="title" required class="form-input" placeholder="Ví dụ: Đắc nhân tâm">
                </div>
                <div>
                    <label for="author" >Tác giả <span>*</span></label>
                    <input type="text" id="author" name="author" required class="form-input" placeholder="Ví dụ: Dale Carnegie">
                </div>
                <div>
                    <label for="cover_image_file">Chọn ảnh bìa</label>
                    <input type="file" id="cover_image_file" name="cover_image_file" accept="image/*" class="form-input">
                </div>
                <div>
                    <label for="summary">Tóm tắt</label>
                    <textarea id="summary" name="summary" rows="4" class="form-input" placeholder="Mô tả ngắn gọn về cuốn sách..."></textarea>
                </div>
                <div>
                    <label for="published_date">Ngày xuất bản (YYYY-MM-DD)</label>
                    <input type="date" id="published_date" name="published_date" class="form-input">
                </div>
                <div>
                    <label for="read_link">Đường dẫn đọc <span>*</span></label>
                    <input type="url" id="read_link" name="read_link" required class="form-input" placeholder="Ví dụ: https://example.com/doc-sach-nay" pattern="https?://.+" title="Vui lòng nhập một URL hợp lệ (ví dụ: https://example.com)">
                </div>
                <button type="submit" name="add_book" class="btn-primary">Thêm Sách</button>
            </form>
        </div>

        ---

        <div>
            <h2>Các Sách Hiện Có</h2>
            <?php if (!empty($books)): ?>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Tác giả</th>
                                <th>Ngày xuất bản</th>
                                <th>Đường dẫn đọc</th>
                                <th>Tóm tắt</th>
                                <th>Ảnh bìa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['id']); ?></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['published_date']); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($book['read_link']); ?>" target="_blank" class="truncate max-w-[200px] block"><?php echo htmlspecialchars($book['read_link']); ?></a>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($book['summary']); ?>">
                                        <?php echo htmlspecialchars($book['summary']); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($book['cover_image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="Ảnh bìa" onerror="this.onerror=null;this.src='https://placehold.co/64x80/cccccc/333333?text=Không+Ảnh';" title="Ảnh Bìa">
                                        <?php else: ?>
                                            <img src="https://placehold.co/64x80/cccccc/333333?text=Không+Ảnh" alt="Không Ảnh" title="Không Ảnh Bìa">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Không tìm thấy sách nào trong cơ sở dữ liệu. Vui lòng thêm sách bằng biểu mẫu trên!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>