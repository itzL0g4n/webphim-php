SYNC FIX
========

Nút "Cập nhật từ API" giờ chạy qua cron/sync.php?redirect=1 và tự quay về admin/index.php.

Kết quả đồng bộ được hiển thị dưới dạng:
API 72 | phim mới X | phim cập nhật Y | phim trùng Z | tập mới A | tập cập nhật B | tập trùng C | lỗi D

API endpoint:
https://phimapi.com/danh-sach/phim-moi-cap-nhat?page={page}

API hiện trả 24 items/page. Nếu latest_pages=3 thì tối đa 72 items được duyệt mỗi lần.

Kiểm tra trùng:
- Phim: theo slug, nếu đã có thì UPDATE thay vì INSERT.
- Trong cùng một lần API: slug lặp lại sẽ bị bỏ qua.
- Tập: theo movie + server + season + episode_number; nếu đã có thì UPDATE, không tạo bản sao.
- Tập thủ công không bị cron xóa.

Cron tự động vẫn có thể chạy CLI:
php cron/sync.php

Hoặc hosting:
5 0 * * * /usr/bin/php /home/USER/public_html/cron/sync.php >/dev/null 2>&1

ADMIN LIST: Quản lý phim phân trang 30 phim/trang để tránh tải quá nhiều dữ liệu cùng lúc.
IMAGE: API image URL được normalize và có fallback placeholder khi ảnh từ nguồn không tải được.
