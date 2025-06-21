<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Đọc sách online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/disign.css">
</head>

<body>
    <header> <?php include '../Elements/header.php'?></header>

    <?php
    // Bao gồm file kết nối database
    require_once '../Elements/connect.php'; // Điều chỉnh đường dẫn nếu cần thiết

    $pdo = connect_db();
    $book = null; // Khởi tạo biến book

    // Lấy ID từ URL
    $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Chuyển đổi sang số nguyên

    if ($book_id > 0) {
        try {
            // Chuẩn bị câu truy vấn để lấy thông tin sách dựa trên ID
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
            $stmt->bindParam(':id', $book_id, PDO::PARAM_INT);
            $stmt->execute();
            $book = $stmt->fetch(); // Lấy một hàng dữ liệu
        } catch (PDOException $e) {
            error_log("Lỗi truy vấn chi tiết sách: " . $e->getMessage());
            // Có thể hiển thị thông báo lỗi hoặc chuyển hướng
        }
    }

    // Nếu không tìm thấy sách, có thể chuyển hướng hoặc hiển thị thông báo
    if (!$book) {
        // Có thể chuyển hướng về trang chủ hoặc trang lỗi
        // header('Location: index.php');
        // exit;
        echo "<div class='content-book' style='text-align: center; color: #fff;'><p>Không tìm thấy sách với ID này.</p></div>";
        echo "</body></html>";
        exit; // Dừng việc render phần còn lại của trang
    }

    // Cập nhật tiêu đề trang
    echo "<script>document.title = '" . htmlspecialchars($book['title']) . " - Đọc sách online';</script>";
    ?>

    <div class="content-book">
        <div class="book-detail">
            <div class="book-info-row">
                <div class="book-left">
                    <img src="../Admin/<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="Bìa sách: <?php echo htmlspecialchars($book['title']); ?>">
                </div>

                <div class="book-meta">
                    <p class="book-title">Tác phẩm: <?php echo htmlspecialchars($book['title']); ?></p>
                    <p class="book-author">Tác giả: <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="book-author">Năm xuất bản: <?php echo htmlspecialchars($book['published_date']); ?></p>
                    <p class="book-author">Thể loại: <?php echo htmlspecialchars($book['genre']); ?></p>
                    <br>
                    <p class="book-author">
                        <?php echo nl2br(htmlspecialchars($book['summary'])); ?>
                    </p>
                    <?php if (!empty($book['wiki_link'])): // chưa áp dụng hết thời gian rồi cmnr ?>
                    <a href="<?php echo htmlspecialchars($book['wiki_link']); ?>"><p style="color: rgb(253, 3, 3);">Tìm hiểu thêm về tác giả và tác phẩm</p></a>
                    <?php endif; ?>
                    <br>

                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>" class="read-icon">
                        <i class="fas fa-book-open"></i> Đọc truyện ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
<footer> <?php include '../Elements/footer.html' ?></footer>
</html>