document.addEventListener('DOMContentLoaded', async ()=>{
  const slug = new URLSearchParams(location.search).get('slug');
  if (!slug) { location.href='projects/index.html'; return; }
  try{
    const p = await apiGet('/projects/'+encodeURIComponent(slug));
    q('#docTitle').textContent = p.title; q('#projTitle').textContent=p.title;
    q('#ogTitle').setAttribute('content', p.title);
    q('#ogDesc').setAttribute('content', (p.client||'') + (p.designer?(' • '+p.designer):''));
    if (p.cover_url) q('#ogImage').setAttribute('content', p.cover_url);
    const meta = q('#meta');
    meta.innerHTML = `
      <div><b>Клиент:</b> ${p.client||'-'}</div>
      <div><b>Арт-директор:</b> ${p.art_director||'-'}</div>
      <div><b>Дизайнер:</b> ${p.designer||'-'}</div>`;
    q('#description').innerHTML = p.description_html || fmtHTML(p.description||'');
    const g = q('#gallery');
    (p.media||[]).forEach(m=>{
      const item = document.createElement('div');
      item.className = 'item '+(m.type.startsWith('video')?'video':'image');
      if (m.type==='image'){
        item.innerHTML=`<img src="${m.url}" alt="${m.caption||''}" loading="lazy">`;
      } else if (m.type==='video_local') {
        item.innerHTML=`<video controls preload="metadata"><source src="${m.url}" type="video/mp4"></video>`;
      } else {
        // YouTube/Vimeo — вставка iframe
        item.innerHTML=`<div class="responsive-iframe"><iframe src="${m.external_url}" frameborder="0" allowfullscreen loading="lazy"></iframe></div>`;
        item.style.gridColumn='span 12';
      }
      g.appendChild(item);
    });
    if (p.prev_slug) { const a=q('#prevLink'); a.href='project/index.html?slug='+encodeURIComponent(p.prev_slug); a.style.visibility='visible'; }
    else q('#prevLink').style.visibility='hidden';
    if (p.next_slug) { const a=q('#nextLink'); a.href='project/index.html?slug='+encodeURIComponent(p.next_slug); a.style.visibility='visible'; }
    else q('#nextLink').style.visibility='hidden';

    // трекинг просмотров
    apiPost('/stats/track', {path: location.pathname + location.search, project_slug: slug}).catch(()=>{});
  }catch(e){
    location.href='/404.html';
  }
});
