FILMNOVA UNIVERSAL THEME V1

1) GÓI ZIP/FNTHEME
- theme.json (khuyến nghị)
- style.css (bắt buộc sau khi hệ thống chuẩn hóa)
- assets/...
- views/ (tuỳ chọn, chỉ HTML/PHP view an toàn theo chuẩn FilmNova nếu được bật)
- KHÔNG dùng PHP tùy ý trong assets/theme package.

2) THEME.JSON TỐI THIỂU
{
  "name": "Tên theme",
  "slug": "ten-theme",
  "version": "1.0.0",
  "description": "Mô tả",
  "author": "Tác giả",
  "entry": "style.css",
  "engine": "filmnova-universal-v1",
  "features": ["header","nav","search","hero","catalog","sidebar","ads","player","comments","footer"]
}

3) CSS
Theme được scope vào body.theme-<slug>, nên có thể style body, .fm-head, .fm-menu, .fm-hero,
.fm-card, .fm-watch, .fm-comments, .watch-side, .fm-sidebar... mà không ảnh hưởng Admin.
Core CSS được tải trước theme CSS để theme có thể override phần trình bày.

4) QUY TRÌNH
Upload -> kiểm tra -> tạo bản xem thử -> admin xem preview -> Cài đặt & kích hoạt.
Sai cấu trúc/định dạng sẽ bị chặn trước khi cài.


5) QUAN TRỌNG
FilmNova không chạy PHP tùy ý từ theme upload. Không thể nhận nguyên xi mọi theme WordPress/PHP/Blogger rồi tự biến thành 100% dynamic mà không có adapter.
Universal Engine tập trung vào theme giao diện: theme AI chỉ cần target các class FilmNova (fm-head, fm-menu, fm-hero, fm-grid, fm-card, fm-watch, fm-comments, watch-side, fm-sidebar...) và CSS sẽ được scope tự động.

6) UPLOAD FLOW
Upload -> validate ZIP/FNTHEME -> kiểm tra theme.json/CSS/file nguy hiểm -> tạo staging -> Preview -> Cài đặt & kích hoạt. Theme lỗi bị chặn trước khi ảnh hưởng theme đang chạy.
