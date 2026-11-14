(function(){
  document.addEventListener('click',function(e){
    const btn=e.target.closest('[data-menu-drop]');
    if(btn){const box=btn.parentElement;document.querySelectorAll('.fm-menu-drop.open').forEach(x=>{if(x!==box)x.classList.remove('open')});box.classList.toggle('open');e.stopPropagation();}
  });
  document.addEventListener('click',function(e){if(!e.target.closest('.fm-menu-drop'))document.querySelectorAll('.fm-menu-drop.open').forEach(x=>x.classList.remove('open'));});
  const mb=document.getElementById('mobileMenuBtn'),menu=document.getElementById('mainMenu');if(mb&&menu)mb.addEventListener('click',()=>menu.classList.toggle('open'));
  // hero switching: only the active slide receives pointer events
  const slides=[...document.querySelectorAll('.fm-hero-slide')],dots=[...document.querySelectorAll('[data-hero]')];
  if(slides.length){let cur=Math.max(0,slides.findIndex(x=>x.classList.contains('active')));const show=i=>{cur=(i+slides.length)%slides.length;slides.forEach((s,n)=>{s.classList.toggle('active',n===cur);s.style.display=n===cur?'block':'none';s.style.pointerEvents=n===cur?'auto':'none'});dots.forEach((d,n)=>d.classList.toggle('active',n===cur));};show(cur);dots.forEach((d,n)=>d.addEventListener('click',()=>show(n)));setInterval(()=>show(cur+1),7000);}
})();
