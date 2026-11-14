FILMNOVA APP API v2

URL API luôn tự theo domain hiện tại:
GET /api/app.php?action=config&key=APP_API_KEY
GET /api/app.php?action=list&page=1&limit=24&key=APP_API_KEY
GET /api/app.php?action=list&q=avengers&key=APP_API_KEY
GET /api/app.php?action=movie&slug=ten-phim&key=APP_API_KEY
GET /api/app.php?action=episode&movie=1&episode=2&key=APP_API_KEY
GET /api/app.php?action=health&key=APP_API_KEY

Khuyến nghị app dùng header X-FilmNova-App-Key thay vì đặt key trên URL.
API trả link watch/play tuyệt đối theo domain website hiện tại, không hard-code localhost.
Player URL vẫn dùng chữ ký có thời hạn.

Windows AutoIt và Android có thể dùng chung API.
