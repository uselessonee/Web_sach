document.addEventListener('DOMContentLoaded', () => {
    // Hàm tạo một mục sách (div.book-item) từ dữ liệu
    function createBookItem(book) {
        const bookItemDiv = document.createElement('div');
        bookItemDiv.classList.add('book-item');

        const imgElement = document.createElement('img');
        imgElement.src = book.image;
        imgElement.alt = `Bìa sách: ${book.title}`; // Sử dụng tiêu đề làm alt text cho ảnh
        imgElement.onerror = () => {
            imgElement.src = '#'; // Ảnh dự phòng nếu ảnh gốc lỗi
            imgElement.alt = 'Ảnh bìa không khả dụng';
        };

        const titleParagraph = document.createElement('p');
        titleParagraph.classList.add('book-title');
        titleParagraph.textContent = book.title;

        if (book.id) {
    const anchorElement = document.createElement('a');
    anchorElement.href = `The_loai.html?id=${book.id}`;
    anchorElement.appendChild(imgElement);
    bookItemDiv.appendChild(anchorElement);
} else {
    bookItemDiv.appendChild(imgElement);
}
bookItemDiv.appendChild(titleParagraph);




return bookItemDiv;

    }

    
    // Hàm tải sách vào một container cụ thể từ nguồn dữ liệu
    async function loadBooks(containerId, dataSourcePath) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Lỗi: Không tìm thấy phần tử container với ID "${containerId}".`);
            return;
        }

        try {
            // Sử dụng fetch để lấy dữ liệu từ file JSON
            const response = await fetch(dataSourcePath);

            // Kiểm tra nếu request không thành công
            if (!response.ok) {
                throw new Error(`Lỗi HTTP! Trạng thái: ${response.status}`);
            }

            // Chuyển đổi response thành đối tượng JSON
            const books = await response.json();

            // Duyệt qua từng cuốn sách và tạo HTML tương ứng
            books.forEach(book => {
                const bookItem = createBookItem(book);
                container.appendChild(bookItem);
            });

        } catch (error) {
            console.error('Không thể tải dữ liệu sách:', error);
            // Hiển thị thông báo lỗi thân thiện với người dùng
            container.innerHTML = '<p style="color: red;">Đã xảy ra lỗi khi tải sách. Vui lòng thử lại sau.</p>';
        }
    }

    // --- Gọi hàm để tải sách vào các container cụ thể ---

    // Tải sách vào container cho "Sách nổi bật"
    // Bạn có thể chỉ định một số lượng sách nhất định nếu muốn,
    // hoặc có một file JSON khác cho sách nổi bật.
    // Ví dụ: chỉ lấy 8 cuốn đầu tiên cho phần này:
    
    loadBooks('featuredBooksContainer', '../json/data.json').then(() => {
        const container = document.getElementById('featuredBooksContainer');
        if (container) {
            // Giới hạn số lượng sách hiển thị (ví dụ 8 cuốn)
            // Lấy tất cả các book-item đã được thêm vào
            const bookItems = Array.from(container.children); 
            // Nếu có nhiều hơn 8 cuốn, ẩn các cuốn còn lại
            if (bookItems.length > 8) {
                for (let i = 8; i < bookItems.length; i++) {
                    bookItems[i].style.display = 'none';
                }
            }
        }
    });

    // Tải sách vào container cho "Mới nhất"
    // Để minh họa, tôi dùng file JSON ví dụ, nhưng có thể có file tạo file 'new_books.json' riêng.
    // Hoặc có thể lấy dữ liệu từ 'books.json' và sắp xếp/lọc theo tiêu chí "mới nhất".
    loadBooks('latestBooksContainer', '../json/data.json'); 
});