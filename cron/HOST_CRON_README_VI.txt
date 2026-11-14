FILMNOVA - CHẠY AUTO 00:00 TRÊN HOST

Bản FilmNova này chạy được cả XAMPP Windows và hosting có Cron Job.

A. HOST CÓ PHP CLI (KHUYÊN DÙNG)
1. Upload toàn bộ thư mục FilmNova lên public_html.
2. Sửa config.php với thông tin MySQL của host.
3. Import database.sql nếu là cài mới; nếu đang dùng database cũ thì giữ nguyên database.
4. Vào Cài đặt, đặt Token cron thật dài và khó đoán.
5. Tạo Cron Job chạy mỗi ngày lúc 00:00, ví dụ:

/usr/bin/php /home/USERNAME/public_html/cron/sync.php --worker all

Một số host dùng:
/usr/local/bin/php /home/USERNAME/public_html/cron/sync.php --worker all

Có thể kiểm tra đường dẫn PHP bằng:
php -v
which php

Worker sẽ tự cập nhật CẢ KKPhim + VSMOV, chia nhỏ theo từng chunk và ghi log tại:
cron/auto_sync.log

B. HOST KHÔNG CHO PHP CLI - CÓ URL CRON / CURL
Dùng Cron Job gọi URL mỗi phút trong khoảng thời gian bạn muốn hệ thống tiếp tục xử lý. Ví dụ:

curl -fsS "https://YOUR-DOMAIN.com/cron/sync.php?token=YOUR_CRON_TOKEN&source=all&ajax=1" >/dev/null 2>&1

Mỗi lần gọi xử lý một chunk. File sync_state.json giữ tiến độ nên các lần gọi tiếp theo sẽ chạy tiếp, không bắt đầu lại.

Khuyến nghị Cron: mỗi 1 phút. Khi đồng bộ xong, state sẽ được xóa.
Nếu host chạy cron mỗi phút cả ngày, KHÔNG nên dùng URL mode nếu chưa có lịch lọc giờ; hãy dùng PHP CLI mode ở mục A.

C. TIMEZONE
Trong config.php:
'site'=>['timezone'=>'Asia/Ho_Chi_Minh']

00:00 sẽ theo timezone PHP của hệ thống/host. Với host có timezone khác, kiểm tra date_default_timezone_get() hoặc đặt timezone trong PHP/cPanel.

D. XAMPP WINDOWS
Giữ nguyên:
cron/install-midnight-task.bat -> Run as administrator một lần.
Task sẽ chạy cron/sync.php --worker all lúc 00:00.

E. AN TOÀN
- Không đưa token cron lên ảnh/chia sẻ công khai.
- Không mở quyền ghi toàn bộ project nếu host không cần.
- sync.php chỉ chấp nhận token đúng với Cài đặt.
