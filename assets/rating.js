document.addEventListener('DOMContentLoaded',()=>{
 const C=window.FILMNOVA_RATING;if(!C)return;
 const stars=[...document.querySelectorAll('#ratingStars button')],avg=document.querySelector('#ratingAverage'),big=document.querySelector('#ratingBigStars'),count=document.querySelector('#ratingCount'),my=document.querySelector('#ratingMy');
 const api=async(url,opt={})=>{const r=await fetch(url,{credentials:'same-origin',...opt,headers:{'Content-Type':'application/json',...(opt.headers||{})}});return r.json()};
 const paint=(n)=>{stars.forEach((b,i)=>b.classList.toggle('active',i<n));};
 const bigStars=(n)=>{let full=Math.floor(Number(n)||0),s='';for(let i=1;i<=5;i++)s+=i<=full?'★':'☆';return s;};
 const apply=(r)=>{const d=r.rating||{};avg.textContent=Number(d.average||0).toFixed(1);count.textContent=`${d.count||0} lượt đánh giá`;big.textContent=bigStars(d.average||0);C.mine=Number(d.mine||0);paint(C.mine);my.textContent=C.mine?`Bạn đã đánh giá ${C.mine}/5 sao · có thể thay đổi`:'Chọn sao để đánh giá';};
 stars.forEach(b=>{b.addEventListener('mouseenter',()=>paint(Number(b.dataset.rating)));b.addEventListener('mouseleave',()=>paint(C.mine||0));b.addEventListener('click',async()=>{const n=Number(b.dataset.rating);stars.forEach(x=>x.disabled=true);const r=await api(`${C.base}?action=vote&movie_id=${C.movieId}`,{method:'POST',body:JSON.stringify({rating:n})});stars.forEach(x=>x.disabled=false);if(!r.ok){my.textContent=r.message||'Không thể lưu đánh giá.';return;}apply(r);my.textContent=r.message||`Đã đánh giá ${n}/5 sao.`;});});
 apply({rating:{average:0,count:0,mine:C.mine||0}}); api(`${C.base}?action=get&movie_id=${C.movieId}`).then(r=>{if(r.ok)apply(r);}).catch(()=>{});
});
