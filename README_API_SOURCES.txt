FilmNova API sources

Admin > Cập nhật từ API has 3 choices: KKPhim, VSMOV, Cả hai.
VSMOV endpoints configured from user-provided URLs:
- /api/danh-sach/phim-moi-cap-nhat?page={page}
- /api/danh-sach/subteam
- /api/tim-kiem?keyword={keyword}&limit={limit}&page={page}
- /api/the-loai
- /api/the-loai/{slug}?limit={limit}&page={page}&year={year}&country={country}&type={type}&status={status}
- /api/nam
- /api/nam/{year}?limit={limit}&page={page}&type={type}&status={status}

The web runtime uses generic JSON list/meta detection, so small schema variations are tolerated. VSMOV detail is configured as /api/phim/{slug} for episode import. If that endpoint is unavailable, metadata still imports from the list endpoint and the sync logs the error without deleting existing episodes.
