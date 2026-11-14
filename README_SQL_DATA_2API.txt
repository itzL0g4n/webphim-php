FILMNOVA - SQL DATA / 2 API

Bản nâng cấp này chuyển dữ liệu phim sang SQL làm nguồn hiển thị chính.

1. Khi đồng bộ KKPhim hoặc VSMOV:
   - tên, slug, năm, thể loại, quốc gia, mô tả, diễn viên, đạo diễn, chất lượng... được lưu vào MySQL.
   - poster/thumb được tải về máy chủ và cột movies.thumb_url / movies.poster_url đổi thành đường dẫn nội bộ.
   - source_json chỉ lưu metadata đã chuẩn hóa, không giữ URL ảnh API.
   - stream_url / embed_url của tập vẫn giữ link phát bên ngoài như nguồn cung cấp.

2. Khi bấm phim từ catalog nhưng phim chưa có trong SQL:
   - watch.php tự hỏi API chi tiết KKPhim rồi VSMOV, lưu phim + ảnh + tập/server vào SQL trước khi hiển thị.
   - mục tiêu là tránh lỗi “Không tìm thấy phim” do catalog có nhưng database chưa có.

3. Trang chủ và mục Phim mới/Xem nhiều dùng SQL trước, không gọi API mỗi lần mở trang.

4. Chỉ còn duy nhất file database.sql trong gói. File này vừa có schema cài mới vừa có phần migration an toàn cho database hiện có.

Lưu ý thư mục uploads/posters và uploads/thumbs cần quyền ghi trên host/XAMPP.


UPGRADE: SYNC DATA + IMAGE CACHE
- Artwork from API is downloaded to uploads/posters and uploads/thumbs. Existing local artwork is reused; routine episode/data updates do not redownload existing images.
- If a local artwork file is missing or invalid, the next sync tries to download it again.
- Public pages read movie metadata from MySQL first.
- During an active sync, the home page can show a small top-right percentage progress indicator.
