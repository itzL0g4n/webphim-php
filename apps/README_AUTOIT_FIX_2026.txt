FILMNOVA AUTOIT - BẢN FIX 2026

Đã sửa các lỗi AU3Check của bản trước:
- Không còn phụ thuộc MsgBoxConstants.au3: dùng hằng số nội bộ $MB_ICONINFORMATION/$MB_ICONERROR.
- Sửa mảng rỗng thành Global/ReDim $gMovies[0][5].
- Sửa regex AutoIt (\\d/\\s) bị escape sai.
- Sửa chuỗi JSON unescape để không gây lỗi ký tự.
- Bỏ @LocalAppDataDir, dùng EnvGet("LOCALAPPDATA") tương thích tốt hơn.
- File FilmNovaPlayer.au3 được lưu UTF-8 WITH BOM.

Cách dùng:
1. Mở FilmNovaPlayer.au3 bằng SciTE.
2. Bấm F5 để chạy thử.
3. Nếu chưa có cấu hình, nhập API URL và API Key lấy trong Quản trị > Ứng dụng của FilmNova.
4. API URL ví dụ: http://127.0.0.1/api/app.php (XAMPP) hoặc https://tenmien.com/api/app.php (Host).
5. Bấm Kiểm tra kết nối.
6. Khi chạy được, dùng Tools > Compile/Build hoặc Ctrl+F7 để tạo EXE.

Lưu ý:
- Không cần cài thêm MsgBoxConstants.au3 cho file này.
- Nếu AutoIt/SciTE của bạn quá cũ và thiếu một trong các GUI*.au3 cơ bản, hãy cài AutoIt3 bản đầy đủ rồi mở lại SciTE.
