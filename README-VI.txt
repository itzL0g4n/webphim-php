FILMNOVA - PHP + MySQL + API catalog
====================================

ĐIỂM MỚI
- Trang danh mục đọc trực tiếp API v1 nên không còn chỉ có vài chục phim cục bộ.
- Có phân trang API: toàn bộ catalog gần 30.000 phim theo dữ liệu nhà cung cấp.
- Menu Thể loại lấy từ /the-loai.
- Menu Quốc gia lấy từ /quoc-gia.
- Lọc theo Phim lẻ/Phim bộ/Hoạt hình, thể loại, quốc gia, năm.
- Tìm kiếm và trang danh mục có phân trang.
- Phim thủ công trong MySQL vẫn được giữ và bổ sung vào catalog trang 1.
- Hình ảnh dùng pathImage từ API và fallback placeholder khi ảnh lỗi.
- API count hiển thị trên trang chủ.
- Đồng bộ admin/cron vẫn cập nhật dữ liệu local + tập và link phát.

API đã dùng theo tài liệu KKPhim:
- /v1/api/danh-sach?page=...
- /v1/api/danh-sach/{slug}?page=...
- /v1/api/the-loai
- /v1/api/the-loai/{slug}?page=...
- /v1/api/quoc-gia
- /v1/api/quoc-gia/{slug}?page=...
- /v1/api/nam/{year}?page=...
- /v1/api/phim/{slug}
- /danh-sach/phim-moi-cap-nhat?page=...

LƯU Ý AN TOÀN NỘI DUNG
Menu công khai bỏ mục nội dung 18+.
Quyền sử dụng phim, ảnh, trailer và đường phát cần được bạn xác minh với nguồn cung cấp.

PHIÊN BẢN BÌNH LUẬN
- Khi bấm vào ô bình luận, popup yêu cầu tên hiển thị + email + PIN 4-8 số.
- PIN được hash bằng password_hash, không lưu PIN nguyên văn.
- Cookie token xác thực dài hạn để lần sau có thể nhập email + PIN.
- Bình luận được lọc từ tiêu cực trước khi xuất bản.
- Bot local chấm điểm; điểm cao thì che và đưa vào kiểm duyệt.
- Admin có thể ẩn/đăng lại/xóa bình luận, ban email vĩnh viễn hoặc theo số giờ.
- Auto mute: mặc định 3 vi phạm trong cửa sổ 24h -> mute 24h; có thể đổi trong Cài đặt.
- Admin > Bot lọc: thêm từ vào bộ lọc. Đây là cơ chế học theo luật/vocabulary của admin, không phải mô hình AI cloud.
- Admin > Cài đặt: đổi tên website, logo bằng URL hoặc upload logo.
