FILMNOVA WINDOWS APP - AUTOIT

1) Biên dịch
- Cài AutoIt + SciTE.
- Mở apps/FilmNovaPlayer.au3.
- Nhấn F7 hoặc Compile Script to .exe.

2) Cấu hình web
- Đăng nhập Admin FilmNova.
- Vào Admin -> Ứng dụng.
- Lấy API Key.
- Copy API URL, ví dụ https://tenmien.com/api/app.php

3) Cấu hình app
- Chạy FilmNovaPlayer.exe.
- Bấm Cấu hình API.
- API URL: dán đúng API URL của website.
- API Key: dán khóa lấy từ Admin.
- Bấm Kiểm tra kết nối.
- Bấm Lưu.

4) Sử dụng
- App tải danh sách phim từ SQL FilmNova qua API.
- Tìm phim, chọn phim rồi bấm Xem phim.
- App mở trang xem phim FilmNova bằng Edge App Mode (nếu có Microsoft Edge), hoặc trình duyệt mặc định.

5) Đưa lên host
- API URL tự dùng domain bạn cấu hình trên website; không hard-code 127.0.0.1.
- Không để API Key trong mã nguồn PHP công khai. Chỉ nhập khóa trong app/config.

Lưu ý: source này là launcher/app client. Player thực tế vẫn do trang xem phim FilmNova đảm nhiệm. Nếu muốn player nhúng hoàn toàn trong cửa sổ EXE, có thể nâng tiếp sang WebView2.
