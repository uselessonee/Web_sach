<?php

// --- Cấu hình ---
$databaseFile = '../DB/books.db';

// biến cho thông báo
$message = '';
$messageType = ''; // 'success' hoặc 'error'

// --- Kết nối ---
try {
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Bật ngoại lệ cho lỗi
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Lấy dòng dưới dạng mảng kết hợp

    // --- Tạo bảng nếu chưa tồn tại (chỉ một lần) ---
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

    // --- Xử lý gửi form ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
        // Làm sạch và kiểm tra dữ liệu đầu vào
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
        $cover_image_url = filter_input(INPUT_POST, 'cover_image_url', FILTER_SANITIZE_URL);
        $summary = filter_input(INPUT_POST, 'summary', FILTER_SANITIZE_STRING);
        $published_date = filter_input(INPUT_POST, 'published_date', FILTER_SANITIZE_STRING);
        $read_link = filter_input(INPUT_POST, 'read_link', FILTER_SANITIZE_URL);

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
            // Kiểm tra định dạng ngày tháng (tùy chọn, nhưng tốt cho tính nhất quán)
            // Đây là kiểm tra cơ bản. Để kiểm tra chặt chẽ hơn, bạn có thể dùng DateTime::createFromFormat
            else if (!empty($published_date) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $published_date)) {
                $message = 'Ngày xuất bản phải theo định dạng YYYY-MM-DD.';
                $messageType = 'error';
            } else {
                // Chuẩn bị và thực thi câu lệnh SQL INSERT
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
                    // Xóa dữ liệu form sau khi gửi thành công (tùy chọn, cho UX)
                    // Lưu ý: Thông thường sẽ cần chuyển hướng để tránh gửi lại khi refresh.
                    // Đơn giản trong ví dụ một file này, chỉ để form tự reset.

                } catch (PDOException $e) {
                    // Kiểm tra vi phạm ràng buộc duy nhất trên read_link
                    if ($e->getCode() == '23000') { // Mã lỗi vi phạm unique constraint của SQLite
                        $message = 'Lỗi: Đã tồn tại sách với "Đường dẫn đọc" này.';
                    } else {
                        $message = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        }
    }

    // --- Lấy tất cả sách để hiển thị ---
    $books = []; // Khởi tạo mảng rỗng cho sách
    $selectSQL = "SELECT id, title, author, cover_image_url, summary, published_date, read_link FROM books ORDER BY title ASC";
    $stmt = $pdo->query($selectSQL);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    $message = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
    $messageType = 'error';
    // Cần xử lý điều này một cách thân thiện, có thể ghi log và hiển thị thông báo dễ hiểu cho người dùng.
}

?>
