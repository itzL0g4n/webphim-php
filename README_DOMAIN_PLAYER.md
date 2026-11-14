# Bảo vệ player theo tên miền

- Admin > Cài đặt > Domain được phép mở player.
- Nhập mỗi domain một dòng hoặc phân cách bằng dấu phẩy.
- Endpoint `api/player.php` kiểm tra Referer/Origin/Sec-Fetch-Site.
- Request không đến từ domain đã cấu hình sẽ trả HTTP 403.
- Token player vẫn phải hợp lệ và đúng phim/tập.
- Đây là bảo vệ truy cập theo nguồn gọi; URL tuyệt đối không thể được giấu khỏi DevTools nếu trình duyệt phải tải dữ liệu từ nguồn phát.
