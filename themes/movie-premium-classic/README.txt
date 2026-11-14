FilmNova Theme Package - Movie Premium Classic v1.0.0
=====================================================

Cau truc:
theme/
├── theme.json        (khai bao theme: ten, mau, layout, danh sach CSS)
├── style.css         (giao dien chinh, da tach tu template Blogger hotav.in)
├── responsive.css    (lop responsive: may tinh -> tablet -> dien thoai)
├── assets/
│   ├── images/       (de anh nen, banner rieng cua ban)
│   ├── icons/        (icon sprite)
│   └── fonts/        (font tu host neu can)
└── preview.png       (anh xem truoc trong trang Giao dien)

Cach cai:
1. Nen ca thu muc theme/ thanh file ZIP.
2. Vao Admin > Giao dien > Chon tep > Upload & cai dat.
3. Bam "Cai dat / kich hoat".

Luu y:
- Goi nay chi chua CSS + anh, khong co PHP/JS nen khong the pha header,
  menu hay player cua FilmNova.
- style.css con mot so anh nen tro toi link ngoai cua template goc.
  Neu muon tu chu hoan toan, tai anh ve assets/images/ va thay duong dan
  trong style.css thanh assets/images/ten-anh.jpg
- responsive.css phai duoc nap SAU style.css (theme.json da sap xep dung thu tu).
