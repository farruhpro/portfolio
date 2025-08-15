// VERSION 2025-08-15-3 — надёжный логин и работа вкладок

// ===== helpers =====
const authFetch = async (path, opts = {}) => {
  const r = await fetch(API_BASE + path, Object.assign({ credentials: 'include' }, opts));
  if (!r.ok) throw new Error(await r.text());
  return await r.json();
};
const by = (id) => document.getElementById(id);
const state = { csrf: null, user: null };

const showScreen = (name) => {
  document.querySelectorAll('[data-screen]').forEach(s => s.classList.add('hidden'));
  const view = document.querySelector(`[data-screen="${name}"]`);
  if (view) view.classList.remove('hidden');
};

// Скрываем интерфейс до входа
document.addEventListener('DOMContentLoaded', () => {
  const h = by('appHeader'), a = by('app');
  if (h) { h.hidden = true; h.style.display = 'none'; }
  if (a) { a.hidden = true; a.style.display = 'none'; }
});

// ===== LOGIN =====
async function doLogin(body){
  const res = await authFetch('/auth/login', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
	state.csrf = res.csrf;
	state.user = res.user;

	// убрать инлайн-стиль, который скрывает интерфейс
	document.getElementById('hideStyle')?.remove();

	// показать интерфейс (перебиваем на всякий случай)
	const h = by('appHeader'), a = by('app');
	by('login').classList.add('hidden');
	if (h) { h.hidden = false; h.style.display = 'flex'; }   // header обычно flex
	if (a) { a.hidden = false; a.style.display = 'block'; }  // main — блок

  wireNavOnce();

  // первичная загрузка
  await loadDash();
  await loadProjects();
  await loadUsers();
  await loadSettings();
}

// Экспортируем коллбэк для inline-fallback из index.html
window.__afterLogin = async (data) => {
  // когда fallback сделал POST и получил {ok:true}, вытягиваем CSRF/user заново (или используем имеющиеся)
  try{
    // запросим /admin/settings ради проверки cookie-сессии и заодно получим CSRF из сессии
    // (если он нужен — в state уже не нужен, но пусть)
    state.csrf = state.csrf || (data && data.csrf) || null;
    const h = by('appHeader'), a = by('app');
	document.getElementById('hideStyle')?.remove();
	by('login').classList.add('hidden');
	if (h) { h.hidden = false; h.style.display = 'flex'; }
	if (a) { a.hidden = false; a.style.display = 'block'; }

    wireNavOnce();
    await loadDash();
    await loadProjects();
    await loadUsers();
    await loadSettings();
  }catch(e){
    console.error('afterLogin error', e);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const form = by('loginForm');
  if (form && !form.dataset.wired){
    form.dataset.wired = '1';
    form.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const fd = new FormData(form);
      const body = {username: fd.get('username')||'', password: fd.get('password')||''};
      const msg = by('loginMsg');
      try{
        await doLogin(body);
      }catch(e){
        console.error('Login error:', e);
        if (msg) msg.textContent = 'Ошибка входа';
        // на всякий случай держим интерфейс скрытым
        const h = by('appHeader'), a = by('app');
        if (h) { h.hidden = true; h.style.display = 'none'; }
        if (a) { a.hidden = true; a.style.display = 'none'; }
      }
    });
  }
});

// logout
document.addEventListener('DOMContentLoaded', () => {
  const btn = by('logoutBtn');
  if (btn) btn.addEventListener('click', async ()=>{
    await authFetch('/auth/logout', {method:'POST'});
    location.reload();
  });
});

// ===== NAV =====
function wireNavOnce(){
  const nav = by('topNav');
  if (!nav || nav.dataset.wired) return;
  nav.dataset.wired = '1';
  nav.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-view]');
    if (!btn) return;
    const v = btn.dataset.view;
    try{
      if (v==='dash'){ showScreen('dash'); await loadDash(); }
      else if (v==='projects'){ showScreen('projects'); await loadProjects(); }
      else if (v==='media'){ showScreen('media'); await loadMediaProjects(); }
      else if (v==='settings'){ showScreen('settings'); await loadSettings(); }
      else if (v==='users'){ showScreen('users'); await loadUsers(); }
    }catch(err){ console.error(err); alert('Ошибка загрузки раздела'); }
  });
}

// ===== DASH =====
async function loadDash(){
  for (const r of ['7d','30d','90d']){
    const d = await authFetch('/admin/stats/summary?range='+r);
    by(r==='7d'?'k7':r==='30d'?'k30':'k90').textContent = d.total||0;
    if (r==='30d'){
      const ctx = by('chart').getContext('2d');
      new Chart(ctx,{type:'line',data:{labels:d.by_day.map(x=>x.day),datasets:[{label:'Просмотры',data:d.by_day.map(x=>x.count)}]},options:{tension:.3}});
    }
  }
}

// ===== PROJECTS =====
async function loadProjects(){
  const res = await authFetch('/admin/projects');
  const root = by('projectsTable');
  root.innerHTML = `
    <div class="thead"><div>ID</div><div>Название</div><div>Статус</div><div>Опубликовано</div><div>Действия</div></div>
    ${res.items.map(p=>`
      <div class="tr" draggable="true" data-id="${p.id}">
        <div>${p.id}</div>
        <div contenteditable="true" data-field="title">${p.title}</div>
        <div>${p.status}</div>
        <div>${p.published_at||'-'}</div>
        <div>
          <button data-act="edit" data-id="${p.id}">✎</button>
          <button data-act="pub" data-id="${p.id}">${p.status==='published'?'Снять':'Опубликовать'}</button>
          <button data-act="del" data-id="${p.id}">🗑</button>
        </div>
      </div>`).join('')}
  `;
  root.querySelectorAll('[contenteditable][data-field="title"]').forEach(el=>{
    el.addEventListener('blur', async ()=>{
      const id = el.closest('.tr').dataset.id;
      await authFetch('/admin/projects/'+id,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify({title:el.textContent.trim()})});
    });
  });
  root.onclick = async (e)=>{
    const btn = e.target.closest('button'); if (!btn) return;
    const id = btn.dataset.id;
    if (btn.dataset.act==='del'){
      if (!confirm('Удалить проект?')) return;
      await authFetch('/admin/projects/'+id,{method:'DELETE',headers:{'X-CSRF-Token':state.csrf}});
      loadProjects();
    } else if (btn.dataset.act==='pub'){
      const path = btn.textContent.includes('Снять')?'/unpublish':'/publish';
      await authFetch('/admin/projects/'+id+path,{method:'PUT',headers:{'X-CSRF-Token':state.csrf}});
      loadProjects();
    } else if (btn.dataset.act==='edit'){
      const title = prompt('Название'); if (!title) return;
      await authFetch('/admin/projects/'+id,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify({title})});
      loadProjects();
    }
  };
  // dnd сортировка
  let dragging=null;
  root.querySelectorAll('.tr').forEach(row=>{
    row.addEventListener('dragstart', ()=>{ dragging=row; row.classList.add('dragging'); });
    row.addEventListener('dragend', ()=>{ row.classList.remove('dragging'); saveOrder(); });
  });
  root.addEventListener('dragover', (e)=>{
    e.preventDefault();
    const rows = [...root.querySelectorAll('.tr')];
    const after = rows.find(r=> e.clientY <= r.getBoundingClientRect().top + r.offsetHeight/2);
    if (!after) root.appendChild(dragging); else root.insertBefore(dragging, after);
  });
  async function saveOrder(){
    const payload = [...root.querySelectorAll('.tr')].map((r,i)=>({id:+r.dataset.id, sort_order:i+1}));
    await authFetch('/admin/sort/projects',{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify(payload)});
  }
}
document.addEventListener('DOMContentLoaded', ()=>{
  const btn = by('newProjectBtn');
  if (btn) btn.addEventListener('click', async ()=>{
    const title = prompt('Название проекта'); if (!title) return;
    const slug = prompt('Слаг (url)') || title.toLowerCase().replace(/\s+/g,'-');
    await authFetch('/admin/projects',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify({title,slug,status:'draft'})});
    loadProjects(); loadMediaProjects();
  });
});

// ===== MEDIA =====
async function loadMediaProjects(){
  const res = await authFetch('/admin/projects');
  const sel = by('mediaProject');
  sel.innerHTML = res.items.map(p=>`<option value="${p.id}">${p.title}</option>`).join('');
  loadMedia();
}
async function loadMedia(){
  const pid = by('mediaProject').value; if (!pid) return;
  const res = await authFetch('/admin/projects?project_id='+pid);
  const p = res.items.find(x=> String(x.id)===String(pid));
  const grid = by('mediaGrid'); grid.innerHTML='';
  const media = (p && p.media) || [];
  media.sort((a,b)=>a.sort_order-b.sort_order).forEach(m=>{
    const tile = document.createElement('div'); tile.className='tile'; tile.draggable=true; tile.dataset.id=m.id;
    tile.innerHTML = `
      ${m.type==='image'?`<img src="${m.url}" alt="">`:
        m.type==='video_local'?`<video src="${m.url}" muted></video>`:`<div class="cap">Внешнее видео</div>`}
      <div class="cap">${m.caption||''}</div>
      <div class="row">
        <button data-act="cap" data-id="${m.id}">Подпись</button>
        <button data-act="del" data-id="${m.id}">Удалить</button>
      </div>`;
    grid.appendChild(tile);
  });
  let dragging=null;
  grid.querySelectorAll('.tile').forEach(t=>{
    t.addEventListener('dragstart', ()=>{dragging=t;t.classList.add('dragging');});
    t.addEventListener('dragend', ()=>{t.classList.remove('dragging');saveOrder();});
  });
  grid.addEventListener('dragover',(e)=>{
    e.preventDefault();
    const tiles = [...grid.querySelectorAll('.tile')];
    const after = tiles.find(r=> e.clientY <= r.getBoundingClientRect().top + r.offsetHeight/2);
    if (!after) grid.appendChild(dragging); else grid.insertBefore(dragging, after);
  });
  async function saveOrder(){
    const payload = [...grid.querySelectorAll('.tile')].map((r,i)=>({id:+r.dataset.id, sort_order:i+1}));
    await authFetch('/admin/sort/media',{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify(payload)});
  }
}
document.addEventListener('DOMContentLoaded', ()=>{
  const sel = by('mediaProject'); if (sel) sel.addEventListener('change', loadMedia);
  const up = by('uploadBtn'); if (up) up.addEventListener('click', async ()=>{
    const pid = by('mediaProject').value;
    const files = by('mediaFiles').files;
    if (!pid) return alert('Выберите проект');
    if (files.length===0){
      const url = prompt('Внешнее видео (YouTube/Vimeo embed URL)'); if (!url) return;
      const form = {type:'video_external', external_url:url};
      await authFetch(`/admin/projects/${pid}/media`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify(form)});
      return loadMedia();
    }
    const fd = new FormData();
    fd.append('type','image');
    for (const f of files) fd.append('files[]', f);
    const r = await fetch(API_BASE+`/admin/projects/${pid}/media`,{method:'POST',credentials:'include',headers:{'X-CSRF-Token':state.csrf},body:fd});
    if (!r.ok) alert('Ошибка загрузки'); else loadMedia();
  });
});

// ===== SETTINGS =====
async function loadSettings(){
  const s = await authFetch('/admin/settings');
  const f = by('settingsForm');
  f.header_title.value = s.header_title||'';
  f.footer_text.value = s.footer_text||'';
  f.email.value = s.email||'';
  f.phone.value = s.phone||'';
  f.hero_video_path.value = s.hero_video_path||'movies/hero.mp4';
  f.hero_poster_path.value = s.hero_poster_path||'images/hero_poster.jpg';
  f.socials.value = JSON.stringify(s.socials||{}, null, 2);
}
document.addEventListener('DOMContentLoaded', ()=>{
  const form = by('settingsForm'); if (form) form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const f = e.target;
    const payload = {
      header_title: f.header_title.value.trim(),
      footer_text: f.footer_text.value.trim(),
      email: f.email.value.trim(),
      phone: f.phone.value.trim(),
      hero_video_path: f.hero_video_path.value.trim(),
      hero_poster_path: f.hero_poster_path.value.trim(),
      socials: (()=>{ try{return JSON.parse(f.socials.value||'{}');}catch(_){return {};}})()
    };
    await authFetch('/admin/settings',{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify(payload)});
    by('settingsMsg').textContent='Сохранено';
  });
});

// ===== USERS =====
async function loadUsers(){
  const res = await authFetch('/admin/users').catch(()=>({items:[]}));
  const root = by('usersTable');
  root.innerHTML = `
    <div class="thead"><div>ID</div><div>Логин</div><div>Роль</div><div>Создан</div><div>Действия</div></div>
    ${(res.items||[]).map(u=>`
    <div class="tr">
      <div>${u.id}</div><div>${u.username}</div><div>${u.role}</div><div>${u.created_at}</div>
      <div><button data-uid="${u.id}" data-act="pwd">Сбросить пароль</button></div>
    </div>`).join('')}
  `;
  root.onclick = async (e)=>{
    const b = e.target.closest('button'); if (!b) return;
    if (b.dataset.act==='pwd'){
      const pwd = prompt('Новый пароль'); if (!pwd) return;
      await authFetch('/admin/users/'+b.dataset.uid,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':state.csrf},body:JSON.stringify({password:pwd})});
      alert('Пароль обновлён');
    }
  };
}
