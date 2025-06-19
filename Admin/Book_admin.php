<?php

// --- Cấu hình ---
// Điều chỉnh đường dẫn đến file cơ sở dữ liệu dựa trên cấu trúc thư mục của bạn.
// Ví dụ: nếu admin_books.php nằm trong 'my_website/admin/' và Database.db nằm trong 'my_website/DB/',
// thì '../DB/Database.db' là đúng.
$databaseFile = '../DB/Database.db';

// Biến cho thông báo (sẽ hiển thị trên UI)
$message = '';
$messageType = ''; // 'success' (thành công) hoặc 'error' (lỗi)

// --- Kết nối Cơ sở dữ liệu ---
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
            cover_image_url TEXT,
            summary TEXT,
            published_date TEXT,
            read_link TEXT UNIQUE
        );
    ";
    $pdo->exec($createTableSQL);

    // --- Xử lý Gửi Form khi thêm sách mới ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
        // Làm sạch và kiểm tra dữ liệu đầu vào từ form
        // Sử dụng FILTER_SANITIZE_STRING hoặc FILTER_UNSAFE_RAW kết hợp với htmlspecialchars cho hiển thị
        // FILTER_SANITIZE_URL là tốt nhất cho URL
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
        $cover_image_url = filter_input(INPUT_POST, 'cover_image_url', FILTER_SANITIZE_URL);
        $summary = filter_input(INPUT_POST, 'summary', FILTER_SANITIZE_STRING);
        $published_date = filter_input(INPUT_POST, 'published_date', FILTER_SANITIZE_STRING);
        $read_link = filter_input(INPUT_POST, 'read_link', FILTER_SANITIZE_URL);

        // Loại bỏ khoảng trắng thừa từ đầu và cuối chuỗi
        $title = trim($title);
        $author = trim($author);
        $summary = trim($summary);
        $published_date = trim($published_date);

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
                        ':cover_image_url' => $cover_image_url,
                        ':summary' => $summary,
                        ':published_date' => $published_date,
                        ':read_link' => $read_link
                    ]);
                    $message = 'Thêm sách thành công!';
                    $messageType = 'success';
                    // Sau khi thêm thành công, có thể làm trống các trường form (tùy chọn)
                    // Để giữ các trường không bị xóa sau khi gửi, bạn sẽ cần thêm giá trị vào thuộc tính 'value' của input.

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

    // --- Lấy tất cả sách để hiển thị trong bảng ---
    $books = []; // Khởi tạo mảng rỗng cho sách
    $selectSQL = "SELECT id, title, author, cover_image_url, summary, published_date, read_link FROM books ORDER BY title ASC";
    $stmt = $pdo->query($selectSQL);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    // Xử lý lỗi kết nối cơ sở dữ liệu
    $message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    $messageType = 'error';
    // Đây là nơi bạn nên ghi log lỗi chi tiết hơn và hiển thị một thông báo thân thiện với người dùng.
    // Lỗi nghiêm trọng: Đây là một lỗi nghiêm trọng, ứng dụng không thể tiếp tục nếu không kết nối được DB.
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Quản Trị Sách</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
            border: 1px solid #d1d5db; /* Gray-300 */
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6; /* Blue-500 */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* Blue-300 với độ trong suốt */
        }
        .btn-primary {
            background-color: #22c55e; /* Green-500 */
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out;
            cursor: pointer;
            border: none;
        }
        .btn-primary:hover {
            background-color: #16a34a; /* Green-600 */
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .message.success {
            background-color: #dcfce7; /* Green-100 */
            color: #15803d; /* Green-700 */
            border: 1px solid #bbf7d0; /* Green-200 */
        }
        .message.error {
            background-color: #fee2e2; /* Red-100 */
            color: #b91c1c; /* Red-700 */
            border: 1px solid #fca5a5; /* Red-200 */
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
            border-bottom: 1px solid #e5e7eb; /* Gray-200 */
        }
        th {
            background-color: #f3f4f6; /* Gray-100 */
            font-weight: 600;
            color: #4b5563; /* Gray-600 */
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
            color: #2563eb; /* Blue-600 */
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">Bảng Quản Trị Sách</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-gray-50 p-6 rounded-xl shadow-inner mb-8">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Thêm Sách Mới</h2>
            <form action="admin_books.php" method="POST" class="space-y-5">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required class="form-input" placeholder="Ví dụ: Đắc nhân tâm">
                </div>
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Tác giả <span class="text-red-500">*</span></label>
                    <input type="text" id="author" name="author" required class="form-input" placeholder="Ví dụ: Dale Carnegie">
                </div>
                <div>
                    <label for="cover_image_url" class="block text-sm font-medium text-gray-700 mb-1">URL ảnh bìa</label>
                    <input type="url" id="cover_image_url" name="cover_image_url" class="form-input" placeholder="Ví dụ: https://example.com/anh-bia.jpg">
                </div>
                <div>
                    <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Tóm tắt</label>
                    <textarea id="summary" name="summary" rows="4" class="form-input" placeholder="Mô tả ngắn gọn về cuốn sách..."></textarea>
                </div>
                <div>
                    <label for="published_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày xuất bản (YYYY-MM-DD)</label>
                    <input type="date" id="published_date" name="published_date" class="form-input">
                </div>
                <div>
                    <label for="read_link" class="block text-sm font-medium text-gray-700 mb-1">Đường dẫn đọc <span class="text-red-500">*</span></label>
                    <input type="url" id="read_link" name="read_link" required class="form-input" placeholder="Ví dụ: https://example.com/doc-sach-nay" pattern="https?://.+" title="Vui lòng nhập một URL hợp lệ (ví dụ: https://example.com)">
                </div>
                <button type="submit" name="add_book" class="btn-primary w-full sm:w-auto">Thêm Sách</button>
            </form>
        </div>

        <div class="mt-10">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Các Sách Hiện Có</h2>
            <?php if (!empty($books)): ?>
                <div class="overflow-x-auto rounded-xl shadow">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-3 px-4">ID</th>
                                <th class="py-3 px-4">Tiêu đề</th>
                                <th class="py-3 px-4">Tác giả</th>
                                <th class="py-3 px-4">Ngày xuất bản</th>
                                <th class="py-3 px-4">Đường dẫn đọc</th>
                                <th class="py-3 px-4">Tóm tắt</th>
                                <th class="py-3 px-4">Ảnh bìa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($book['id']); ?></td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($book['published_date']); ?></td>
                                    <td class="py-3 px-4 text-blue-600 hover:underline">
                                        <a href="<?php echo htmlspecialchars($book['read_link']); ?>" target="_blank" class="truncate max-w-[200px] block"><?php echo htmlspecialchars($book['read_link']); ?></a>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 text-sm max-w-[300px] overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($book['summary']); ?>">
                                        <?php echo htmlspecialchars($book['summary']); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if (!empty($book['cover_image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="Ảnh bìa" class="w-16 h-20 object-cover rounded-md shadow" onerror="this.onerror=null;this.src='https://placehold.co/64x80/cccccc/333333?text=Không+Ảnh';" title="Ảnh Bìa">
                                        <?php else: ?>
                                            <img src="https://placehold.co/64x80/cccccc/333333?text=Không+Ảnh" alt="Không Ảnh" class="w-16 h-20 object-cover rounded-md shadow" title="Không Ảnh Bìa">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Không tìm thấy sách nào trong cơ sở dữ liệu. Vui lòng thêm sách bằng biểu mẫu trên!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
