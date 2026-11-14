FILMNOVA - BẢN NÂNG CẤP API + VIEW ANTI-SPAM

1. SỬA LỖI TIMEOUT API
- Nút "Cập nhật từ API" không còn chạy toàn bộ đồng bộ trong một PHP request.
- Đồng bộ được chia thành nhiều chunk nhỏ.
- Mỗi chunk tối đa khoảng 5 API request, timeout mỗi request tối đa 8 giây.
- Trình duyệt tự chuyển sang chunk tiếp theo cho tới khi hoàn tất.
- Dữ liệu trạng thái nằm trong cron/sync_state.json và được xóa sau khi hoàn tất.
- Có cron/sync.lock để chặn chạy đồng thời.

2. LƯỢT XEM CHỐNG GIAN LẬN CƠ BẢN
- Bảng view_events được tự tạo khi chạy site.
- Không lưu IP thô trong view_events.
- Tạo visitor_key bằng HMAC dựa trên cookie, IP và User-Agent.
- Một visitor_key chỉ được cộng tối đa 1 lượt/phim/ngày.
- F5 liên tục cùng trình duyệt không tăng lượt.
- Lượt xem hợp lệ mới cộng vào movies.view_count.
- Không thể chống tuyệt đối việc đổi IP/xóa cookie, nhưng khó gian lận kiểu F5 tự động hơn cách cũ.

3. DATABASE CŨ
- Không bắt buộc import lại database.sql.
- Khi mở web, bootstrap.php tự kiểm tra và tạo bảng view_events.
- Tự bổ sung episodes.source_type nếu database cũ thiếu cột.

4. DATABASE MỚI
- Có thể dùng database.sql cho cài đặt mới.

5. THEME
- Core CSS vẫn được tải SAU theme CSS để chức năng/layout lõi không bị theme ghi đè.
- Theme chỉ thay đổi phần trình bày; dữ liệu/API/comment/admin vẫn là core.

HOST: dùng cron/host-cron.php hoặc trực tiếp cron/sync.php --worker all. Xem cron/HOST_CRON_README_VI.txt.
