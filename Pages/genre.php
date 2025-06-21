<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> Đọc sách online</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
 <link rel="stylesheet" href="../../css/The_loai.css">
 <link rel="icon" href="../images/book.png" type="image/x-icon">

</head>
 <header><?php include '../Elements/header.php'; ?></header>
<body style="padding-top: 2rem;">
 <?php  require_once '../Elements/connect.php';

    $pdo = connect_db();

    // Lấy giá trị 'title' từ URL
    $genre_param = isset($_GET['title']) ? $_GET['title'] : '';

    $display_title = 'Thể Loại Sách'; // Tiêu đề mặc định
    $sql_genre = ''; // Biến để lưu thể loại dùng trong câu truy vấn SQL...nhưng vô dụng và chưa biết làm gì hết

    // map tham số URL với tên hiển thị và giá trị genre trong DB
    $genre_mapping = [
        'ngontinh' => ['Sách Ngôn Tình', 'Ngôn Tình'],
        'tieuthuyet' => ['Sách Tiểu Thuyết', 'Tiểu Thuyết'],
        'kinhdi' => ['Sách Kinh Dị', 'Kinh Dị'],
        'hdpl' => ['Sách Hành Động & Phiêu Lưu', 'Hành Động,Phiêu Lưu'], // Giả sử genre có thể là nhiều giá trị cách nhau bằng dấu phẩy
        'truyen3d' => ['Sách Truyện 3D', 'Truyện 3D'],
        'truyenngan' => ['Sách Truyện Ngắn', 'Truyện Ngắn'],
        'lichsu' => ['Sách Lịch Sử', 'Lịch Sử'],
        'tamly' => ['Sách Tâm Lý', 'Tâm Lý'],
        'cotich' => ['Sách Cổ Tích', 'Cổ Tích'],
    ];

    if (isset($genre_mapping[$genre_param])) {
        $display_title = $genre_mapping[$genre_param][0];
        $sql_genre = $genre_mapping[$genre_param][1];
    }

    // Cập nhật tiêu đề trang
    echo "<script>document.title = '" . $display_title . " - Đọc sách online';</script>";
    ?>
    <!-- hiển thị tiêu đề -->
    <h1 class="tieude"><?php echo $display_title; ?></h1>

<p style="font-size: 2.5rem; margin-left: 5rem; font-weight: bold; color: #fffdfd;">Đọc nhiều nhất</p>
 <div class="featured-books">
        <?php
        $books = [];
        if (!empty($genre_param)) {
            // Chuẩn bị câu truy vấn SQL với LIMIT 8
            // ORDER BY id DESC để lấy các cuốn mới nhất (hoặc theo tiêu chí khác nếu bạn có)
            $stmt = $pdo->prepare("SELECT * FROM books WHERE genre LIKE :genre_pattern ORDER BY id DESC LIMIT 8");
            $stmt->bindValue(':genre_pattern', '%' . $genre_param . '%'); // Tìm kiếm genre chứa chuỗi
            $stmt->execute();
            $books = $stmt->fetchAll();
        }

        if (count($books) > 0) {
            foreach ($books as $book) {
                ?>
                <div class="book-item">
                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>">
                                <?php if (!empty($book['cover_image_url'])): ?>
                          <img src="../Admin/<?php echo htmlspecialchars($book['cover_image_url']); ?>" alt="Ảnh bìa" >
                            <?php else: ?>
                                            <img src="https://placehold.co/64x80/cccccc/333333?text=Không+Ảnh" alt="Không Ảnh" title="Không Ảnh Bìa">
                                        <?php endif; ?>
              
                    </a>
                    <a href="<?php echo htmlspecialchars($book['read_link']); ?>">
                        <div class="book-info">
                            <div class="book-title">Tác phẩm: <?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="book-title">Tác giả: <?php echo htmlspecialchars($book['author']); ?></div>
                            <div class="book-title">Năm xuất bản: <?php echo htmlspecialchars($book['published_date']); ?></div>
                            <div class="book-title">Thể loại: <?php echo htmlspecialchars($book['genre']); ?></div>
                            </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo "<p style='font-size: 2rem; margin-left: 5rem; color: #ccc;'>Không tìm thấy sách nào cho thể loại này.</p>";
        }
        ?>
 
</div>


</body>
<footer><?php include '../Elements/footer.html'; ?></footer>


</html>