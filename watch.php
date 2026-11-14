<?php
require __DIR__.'/bootstrap.php';
$slug=trim($_GET['slug']??'');
$s=db()->prepare('SELECT * FROM movies WHERE slug=? AND is_hidden=0');$s->execute([$slug]);$m=$s->fetch();
if(!$m){$m=hydrate_movie_by_slug($slug);}
if(!$m){http_response_code(404);exit('Không tìm thấy phim.');}
// When app-gate mode is enabled, direct web viewing is blocked unless the
// current session has passed the Apple web-view gate on an Apple device.
if(app_feature_enabled() && !app_ios_verified()){
  $pageTitle='Xem phim · '.setv('site_name','FilmNova');
  require __DIR__.'/partials/header.php';
  echo '<div class="container" style="max-width:760px;margin:34px auto 48px">';
  render_app_gate();
  echo '<div style="margin-top:14px;padding:16px 18px;border:1px solid #263448;border-radius:14px;background:#101721;color:#9aa8b8;text-align:center">Phim <strong style="color:#eef3f8">'.e($m['name']).'</strong> chỉ mở sau khi bạn chọn phương thức xem phù hợp.</div></div>';
  require __DIR__.'/partials/footer.php';
  exit;
}
register_view((int)$m['id']);
$s=db()->prepare('SELECT * FROM episodes WHERE movie_id=? ORDER BY server_name,season,episode_number,id');$s->execute([$m['id']]);$eps=$s->fetchAll();
$servers=[];foreach($eps as $ep)$servers[$ep['server_name']?:'Server 1'][]=$ep;
$rating=movie_rating((int)$m['id']);
$latest=latest_movies(8);
$topRated=top_rated_movies(8);
$pageTitle=$m['name'].' · '.setv('site_name','FilmNova');
function watch_card_img(array $m):string{return img($m['poster_url']??$m['thumb_url']??'');}
function rating_stars(float $avg):string{$full=(int)floor($avg);$s='';for($i=1;$i<=5;$i++)$s.=$i<=$full?'★':'☆';return$s;}
if(theme_has_view('watch.php') && theme_has_view('header.php') && theme_has_view('footer.php')){
  theme_render_public('watch.php',['m'=>$m,'eps'=>$eps,'servers'=>$servers,'rating'=>$rating,'latest'=>$latest,'topRated'=>$topRated,'pageTitle'=>$pageTitle]);
  exit;
}
require __DIR__.'/partials/header.php';
?>
<div class="container fm-watch"><div class="fm-watch-layout">
  <main>
    <div class="fm-player player-protected" data-movie-id="<?=e((string)$m['id'])?>"><iframe id="frame" src="<?=e($eps[0]['id']?player_frame_url((int)$m['id'],(int)$eps[0]['id']):'about:blank')?>" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
    <?php if($servers): ?>
      <div class="fm-server"><span class="server-label">Nguồn:</span><?php $i=0;foreach($servers as $name=>$arr): ?><button data-server="s<?=$i?>" class="srv <?=$i===0?'active':''?>"><?=e($name)?></button><?php $i++;endforeach;?></div>
      <?php $i=0;foreach($servers as $name=>$arr): ?><div id="s<?=$i?>" class="fm-server eps <?=$i===0?'active':''?>"><?php foreach($arr as $ep): ?><button type="button" data-episode-id="<?=e((string)$ep['id'])?>" data-player-src="<?=e(player_frame_url((int)$m['id'],(int)$ep['id']))?>"><?=e($ep['name']?:'Tập '.$ep['episode_number'])?></button><?php endforeach;?></div><?php $i++;endforeach;?>
    <?php else: ?><div class="empty">Phim chưa có tập phát.</div><?php endif; ?>

    <section class="watch-description">
      <div class="watch-titlebar"><div><small>THÔNG TIN PHIM</small><h1><?=e($m['name'])?></h1></div><div class="watch-view-pill">👁 <?=number_format((int)$m['view_count'])?></div></div>
      <div class="watch-meta-row"><span><?=e($m['type']?:'PHIM')?></span><span><?=e($m['quality']?:'HD')?></span><span><?=e((string)$m['year'])?></span><span><?=e($m['lang']?:'Vietsub')?></span><?php if($m['time']): ?><span><?=e($m['time'])?></span><?php endif; ?></div>
      <div class="watch-desc"><h2>Mô tả</h2><p><?=nl2br(e(strip_tags($m['description']??'')))?></p></div>
      <div class="watch-details">
        <div><b>Thể loại</b><span><?=e($m['categories']?:'Đang cập nhật')?></span></div>
        <div><b>Quốc gia</b><span><?=e($m['countries']?:'Đang cập nhật')?></span></div>
        <div><b>Đạo diễn</b><span><?=e($m['director']?:'Đang cập nhật')?></span></div>
        <div><b>Diễn viên</b><span><?=e($m['actors']?:'Đang cập nhật')?></span></div>
      </div>
    </section>

    <section class="fm-comments comments-box" data-movie-id="<?=e((string)$m['id'])?>">
      <div class="comments-head"><div><small>BÌNH LUẬN</small><h2>Cộng đồng đang nói gì?</h2></div><button id="commentLoginBtn" class="comment-auth-btn">Đăng nhập bình luận</button></div>
      <div id="commentNotice" class="comment-notice" hidden></div>
      <div id="commentComposer" class="comment-composer locked"><div id="commentReplying" class="comment-replying" hidden><span>↩ Đang trả lời <b id="commentReplyName"></b></span><button type="button" id="commentCancelReply">Hủy</button></div><div class="comment-tools"><div class="comment-emoji-picker" id="commentEmojiPicker"><span class="picker-label">Icon</span><button type="button" data-icon="❤️">❤️</button><button type="button" data-icon="👍">👍</button><button type="button" data-icon="😂">😂</button><button type="button" data-icon="😮">😮</button><button type="button" data-icon="😢">😢</button><button type="button" data-icon="🔥">🔥</button><button type="button" data-icon="👏">👏</button><button type="button" data-icon="😍">😍</button><button type="button" data-icon="🎉">🎉</button><button type="button" data-icon="💯">💯</button></div><label class="emoji-motion"><input id="commentIconAnimated" type="checkbox"> <span>Icon động</span></label><span id="commentIconPreview" class="comment-icon-preview" hidden></span></div><textarea id="commentBody" maxlength="1000" placeholder="Viết bình luận của bạn..." disabled></textarea><div class="comment-composer-foot"><span id="commentIdentity">Chưa xác nhận tài khoản</span><button id="commentSubmit" class="primary" disabled>Đăng bình luận</button></div></div>
      <div id="commentList" class="comment-list"><div class="empty">Đang tải bình luận...</div></div>
      <div id="commentMore" class="comment-more" hidden><button class="btn" id="commentNext">Xem thêm</button></div>

      <div class="comment-modal local-comment-modal" id="commentModal" hidden role="dialog" aria-modal="true" aria-labelledby="commentModalTitle">
        <div class="comment-modal-card">
          <button class="comment-close" id="commentClose" type="button" aria-label="Đóng">×</button>
          <small>TÀI KHOẢN BÌNH LUẬN</small><h3 id="commentModalTitle">Đăng nhập hoặc tạo tài khoản</h3>
          <p class="muted">Tài khoản đã có: chỉ cần Email + PIN. Tài khoản mới: thêm tên hiển thị ở lần đầu.</p>
          <form id="commentAuthForm"><label class="new-account-field">Tên hiển thị <span class="hint">chỉ cần khi tạo mới</span><input name="display_name" maxlength="120" autocomplete="nickname"></label><label>Email<input type="email" name="email" maxlength="190" autocomplete="email" required></label><label>Mã PIN <span class="hint">4–8 số</span><input type="password" name="pin" inputmode="numeric" pattern="\d{4,8}" maxlength="8" autocomplete="current-password" required></label><div id="commentAuthError" class="error" hidden></div><button class="primary" type="submit">Đăng nhập / Tạo tài khoản</button><button class="link-button" id="openForgotPin" type="button">Quên mã PIN?</button></form>
        </div>
      </div>

      <div class="comment-modal local-comment-modal account-modal" id="commentAccountModal" hidden role="dialog" aria-modal="true" aria-labelledby="commentAccountTitle">
        <div class="comment-modal-card">
          <button class="comment-close" id="commentAccountClose" type="button" aria-label="Đóng">×</button>
          <small>TÀI KHOẢN BÌNH LUẬN</small><h3 id="commentAccountTitle">Bạn đã đăng nhập</h3>
          <div class="account-summary"><div class="account-avatar" id="commentAccountAvatar">?</div><div><b id="commentAccountName">-</b><span id="commentAccountEmail">-</span></div></div>
          <div class="account-status">✓ Đã đăng nhập · Bạn có thể bình luận trên tất cả phim</div>
          <form id="commentPinForm" class="pin-form"><label>PIN hiện tại<input type="password" name="old_pin" inputmode="numeric" pattern="\d{4,8}" maxlength="8" autocomplete="current-password" required></label><label>PIN mới<input type="password" name="new_pin" inputmode="numeric" pattern="\d{4,8}" maxlength="8" autocomplete="new-password" required></label><div id="commentPinError" class="error" hidden></div><div class="account-actions"><button class="primary" type="submit">Đổi mã PIN</button><button class="btn" type="button" id="commentLogoutBtn">Đăng xuất</button></div></form>
          <button class="link-button account-forgot" id="openForgotFromAccount" type="button">Quên mã PIN?</button>
        </div>
      </div>

      <div class="comment-modal local-comment-modal" id="commentForgotModal" hidden role="dialog" aria-modal="true" aria-labelledby="commentForgotTitle">
        <div class="comment-modal-card">
          <button class="comment-close" id="commentForgotClose" type="button" aria-label="Đóng">×</button>
          <small>KHÔI PHỤC MÃ PIN</small><h3 id="commentForgotTitle">Đặt lại mã PIN</h3>
          <p class="muted">Nhập email tài khoản và mã PIN mới. Sau khi đặt lại, bạn sẽ được đăng nhập tự động.</p>
          <form id="commentForgotForm" class="pin-form"><label>Email tài khoản<input type="email" name="email" maxlength="190" autocomplete="email" required></label><label>Mã PIN mới<input type="password" name="new_pin" inputmode="numeric" pattern="\d{4,8}" maxlength="8" autocomplete="new-password" required></label><div id="commentForgotError" class="error" hidden></div><button class="primary" type="submit">Đặt lại PIN</button></form>
        </div>
      </div>
    </section>
  </main>

  <aside class="watch-side">
    <section class="side-panel movie-rating-card" data-movie-id="<?=e((string)$m['id'])?>">
      <div class="side-title">ĐÁNH GIÁ PHIM</div>
      <div class="rating-overview"><strong id="ratingAverage"><?=e(number_format((float)$rating['average'],1))?></strong><div class="rating-big-stars" id="ratingBigStars"><?=e(rating_stars((float)$rating['average']))?></div><small id="ratingCount"><?=e((string)$rating['count'])?> lượt đánh giá</small></div>
      <div class="rating-label">Bạn đánh giá phim này thế nào?</div>
      <div class="rating-stars" id="ratingStars" role="radiogroup" aria-label="Chọn số sao"><?php for($i=1;$i<=5;$i++): ?><button type="button" data-rating="<?=$i?>" aria-label="<?=$i?> sao">★</button><?php endfor; ?></div>
      <div class="rating-my" id="ratingMy">Chọn sao để đánh giá</div>
    </section>

    <section class="side-panel"><div class="side-title">PHIM MỚI CẬP NHẬT</div><div class="side-list"><?php foreach($latest as $x): ?><a href="<?=e(site('watch.php?slug='.urlencode($x['slug'])))?>"><img src="<?=e(watch_card_img($x))?>" alt="<?=e($x['name'])?>"><div><b><?=e($x['name'])?></b><span><?=e((string)($x['year']?:''))?> · <?=e((string)($x['episodes_count']?:0))?> tập</span></div></a><?php endforeach; ?></div></section>

    <section class="side-panel"><div class="side-title">PHIM ĐƯỢC NHIỀU SAO NHẤT</div><div class="side-list"><?php if($topRated): foreach($topRated as $x): ?><a href="<?=e(site('watch.php?slug='.urlencode($x['slug'])))?>"><img src="<?=e(watch_card_img($x))?>" alt="<?=e($x['name'])?>"><div><b><?=e($x['name'])?></b><span class="side-stars">★ <?=e(number_format((float)$x['rating'],1))?> · <?=e((string)$x['rating_count'])?> đánh giá</span></div></a><?php endforeach; else: ?><div class="side-empty">Chưa có phim được đánh giá.</div><?php endif; ?></div></section>
  </aside>
</div></div>

<script>window.FILMNOVA_PLAYER={endpoint:'<?=e(site('api/player.php'))?>',movieId:<?=json_encode((int)$m['id'])?>};window.FILMNOVA_COMMENTS={movieId:<?=json_encode((int)$m['id'])?>,base:'<?=e(site('api/comments.php'))?>'};window.FILMNOVA_RATING={movieId:<?=json_encode((int)$m['id'])?>,base:'<?=e(site('api/rating.php'))?>',mine:<?=json_encode((int)$rating['mine'])?>};</script>
<script src="<?=e(site('assets/comments.js'))?>"></script>
<script src="<?=e(site('assets/rating.js'))?>"></script>
<?php require __DIR__.'/partials/footer.php'; ?>
