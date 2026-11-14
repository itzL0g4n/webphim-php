FILMNOVA THEME V2 - AI TEMPLATE

Hãy copy thư mục THEME_TEMPLATE vào một gói ZIP rồi code theo đúng chuẩn.

Bắt buộc:
- theme.json
- style.css
- views/header.php
- views/home.php
- views/browse.php
- views/watch.php
- views/footer.php

Không đổi tên các view bắt buộc.

theme.json tối thiểu:
{
  "name":"Tên theme",
  "slug":"ten-theme",
  "version":"1.0.0",
  "engine":"filmnova-theme-v2",
  "contract":"filmnova-theme-v2",
  "entry":"style.css",
  "script":"script.js",
  "features":["header","nav","search","hero","catalog","sidebar","ads","player","comments","rating","footer"]
}

Không có PHP ngoài views/.
Theme mới sẽ thay HTML/CSS/JS của PUBLIC; Core FilmNova vẫn giữ API, database, player, bình luận, rating, lượt xem, admin.
