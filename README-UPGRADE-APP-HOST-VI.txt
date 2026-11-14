FILMNOVA – APP + HOST

1. API cho Windows/Android: /api/app.php
2. Trong Admin > Cài đặt có API Key. App gửi header X-FilmNova-App-Key.
3. Link API và link iOS tự lấy domain hiện tại của website; không cần sửa 127.0.0.1 khi chuyển từ XAMPP lên host.
4. Windows/Android tải file có thể lưu dạng /uploads/apps/... để hệ thống tự xuất thành URL theo domain hiện tại.
5. Apple: ios.php là trang web riêng, chỉ iPhone/iPad/iPod/Macintosh được qua bước PIN. Đây không phải file APK/EXE.
6. Ảnh poster/thumb mới được tải về sẽ resize + nén WebP/JPEG. Ảnh local cũ có thể tối ưu dần bằng Admin > Nén ảnh.
