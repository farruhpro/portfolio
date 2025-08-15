const paramsObj = ()=>Object.fromEntries(new URLSearchParams(location.search).entries());
const setParam = (k,v)=>{
  const u = new URL(location.href);
  if (v==null || v==='') u.searchParams.delete(k); else u.searchParams.set(k,v);
  history.replaceState({},'',u.toString());
};

async function load(page=1){
  const grid = q('#projectsGrid'); const pag = q('#pagination');
  grid.innerHTML = ''; pag.innerHTML='';
  const p = paramsObj(); const qstr = new URLSearchParams({
    status:'published',
    page: page || p.page || 1,
    limit: 12,
    search: p.search || ''
  }).toString();
  const data = await apiGet('/projects?'+qstr);
  (data.items||[]).forEach(pr=>{
    const a = document.createElement('a');
    a.href = `project/index.html?slug=${encodeURIComponent(pr.slug)}`;
    a.className='card fadein';
    a.innerHTML=`
      <div class="cover"><img src="${pr.cover_url||'/images/placeholder.webp'}" loading="lazy" alt="${pr.title}"></div>
      <div class="meta"><h3>${pr.title}</h3></div>`;
    grid.appendChild(a); reveal(a);
  });
  // пагинация
  const total = data.total||0, limit = data.limit||12;
  const pages = Math.max(1, Math.ceil(total/limit));
  const cur = data.page||1;
  for(let i=1;i<=pages;i++){
    const a = document.createElement('a'); a.className='page'+(i===cur?' active':'');
    a.textContent=i; a.href='#'; a.onclick=(ev)=>{ev.preventDefault(); setParam('page',i); load(i)};
    pag.appendChild(a);
  }
}
document.addEventListener('DOMContentLoaded', ()=>{
  const s = q('#search'); const p = paramsObj();
  if (p.search) s.value=p.search;
  s.addEventListener('input', e=>{ setParam('search', e.target.value.trim()); setParam('page',1); load(1); });
  load(Number(p.page||1));
});
