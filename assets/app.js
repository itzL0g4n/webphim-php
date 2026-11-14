document.addEventListener('DOMContentLoaded',()=>{
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  const hero=document.getElementById('fmHero');
  if(hero){
    const slides=$$('.fm-hero-slide',hero), dots=$$('[data-hero]',hero);
    let i=Math.max(0,slides.findIndex(x=>x.classList.contains('active')));
    const go=n=>{
      if(!slides.length)return;
      i=(n+slides.length)%slides.length;
      slides.forEach((x,k)=>{
        const active=k===i;
        x.classList.toggle('active',active);
        x.hidden=!active;
        x.setAttribute('aria-hidden',active?'false':'true');
        const link=x.querySelector('a.primary');
        const url=x.dataset.watchUrl;
        if(link && url) link.setAttribute('href',url);
      });
      dots.forEach((x,k)=>x.classList.toggle('active',k===i));
    };
    dots.forEach((d,k)=>d.addEventListener('click',e=>{e.preventDefault();go(k)}));
    if(slides.length>1){setInterval(()=>go(i+1),6500);}
  }
  const fr=$('#frame');
  $$('.eps button').forEach(b=>b.onclick=()=>{if(fr&&b.dataset.playerSrc){fr.src=b.dataset.playerSrc;}else if(fr&&b.dataset.url){fr.src=b.dataset.url;}$$('.eps button').forEach(x=>x.classList.remove('playing'));b.classList.add('playing')});
  $$('.srv').forEach(b=>b.onclick=()=>{$$('.srv').forEach(x=>x.classList.remove('active'));b.classList.add('active');$$('.eps').forEach(x=>x.classList.toggle('active',x.id===b.dataset.server))});
  const btn=$('#mobileMenuBtn'),menu=$('#mainMenu');
  if(btn&&menu)btn.addEventListener('click',()=>menu.classList.toggle('open'));
  $$('[data-menu-drop]').forEach(b=>b.addEventListener('click',e=>{e.stopPropagation();const p=b.parentElement;$$('.fm-menu-drop.open').forEach(x=>{if(x!==p)x.classList.remove('open')});p.classList.toggle('open')}));
  document.addEventListener('click',()=>$$('.fm-menu-drop.open').forEach(x=>x.classList.remove('open')));
});

// Global image fallback for broken remote posters/backgrounds.
document.addEventListener('error',function(ev){
  const el=ev.target;
  if(el&&el.tagName==='IMG'&&!el.dataset.fallback){
    el.dataset.fallback='1';
    const base=(window.FILMNOVA_BASE||'').replace(/\/$/,'');
    el.src=(base?base:'/')+'/assets/placeholder.svg';
  }
},true);


// Live sync progress: shown only while FilmNova is processing an automatic/manual API update.
document.addEventListener('DOMContentLoaded',()=>{
  const isHome=location.pathname.endsWith('/')||location.pathname.endsWith('/index.php')||!location.pathname.split('/').pop();
  if(!isHome)return;
  const base=(window.FILMNOVA_BASE||'').replace(/\/$/,'');
  const endpoint=(base||'')+'/cron/progress.php';
  const box=document.createElement('div'); box.id='fmSyncProgress'; box.hidden=true;
  box.innerHTML='<div class="fm-sync-progress-inner"><span class="fm-sync-progress-label">Đang cập nhật phim bộ & phim lẻ • tập mới</span><strong class="fm-sync-progress-pct">0%</strong><div class="fm-sync-progress-track"><i></i></div></div>';
  document.body.appendChild(box);
  const label=box.querySelector('.fm-sync-progress-label'),pct=box.querySelector('.fm-sync-progress-pct'),bar=box.querySelector('i');
  let timer=null;
  async function poll(){
    try{const r=await fetch(endpoint+'?t='+Date.now(),{cache:'no-store'});const d=await r.json();if(d.active){box.hidden=false;label.textContent=d.label||'Đang cập nhật dữ liệu...';const n=Math.max(0,Math.min(100,Number(d.percent||0)));pct.textContent=n+'%';bar.style.width=n+'%';}else{box.hidden=true;if(timer){clearTimeout(timer);timer=null;}}}catch(e){}
    if(!box.hidden)timer=setTimeout(poll,1200);
    else timer=setTimeout(poll,5000);
  }
  poll();
});

// Apple app tile: only Apple devices can continue; all devices can see the tile.
document.addEventListener('DOMContentLoaded',()=>{
  const tile=document.querySelector('[data-apple-view]');
  if(!tile)return;
  tile.addEventListener('click',e=>{
    const apple=/iPhone|iPad|iPod|Macintosh/i.test(navigator.userAgent);
    if(!apple){e.preventDefault();const m=document.getElementById('fmAppleGateMsg');if(m){m.hidden=false;m.textContent='Tùy chọn Apple chỉ dành cho iPhone, iPad hoặc Mac.';}}
  });
});
