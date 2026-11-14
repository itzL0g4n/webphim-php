FilmNova – Chế độ ứng dụng / iOS web

1) Admin > Cài đặt > Ứng dụng / chế độ xem. Bật để trang chủ hiện cùng lúc Windows / Android / Apple.
2) Windows: cấu hình link .EXE hoặc tải file EXE.
3) Android: cấu hình link .APK hoặc tải file APK.
4) Apple: bấm logo Apple sẽ vào ios.php; server chỉ cho iPhone/iPad/iPod/Macintosh tiếp tục và yêu cầu PIN. Thiết bị khác không được vào.
5) PIN Apple được lưu dạng hash, không ghi mã thật vào HTML.
6) API app: api/app.php?action=config và api/app.php?action=movie&slug=...; chỉ trả metadata + danh sách tập, không trả trực tiếp link stream.
