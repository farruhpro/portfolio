document.addEventListener('DOMContentLoaded', async () => {
  const grid = document.getElementById('projectsGrid');
  try {
    const data = await apiGet('/projects?status=published&page=1&limit=12');
    (data.items||[]).forEach(p=>{
      const a = document.createElement('a');
      a.href = `project/index.html?slug=${encodeURIComponent(p.slug)}`;
      a.className='card fadein';
      a.innerHTML = `
        <div class="cover">
          <img src="${p.cover_url||'/images/placeholder.webp'}" loading="lazy" alt="${p.title}">
        </div>
        <div class="meta">
          <h3>${p.title}</h3>
          <div class="muted">${p.client?('Клиент: '+p.client):''}</div>
          <div class="action">Смотреть проект</div>
        </div>`;
      grid.appendChild(a); reveal(a);
    });
  } catch(e){
    grid.innerHTML = `<p class="muted">Не удалось загрузить проекты.</p>`;
  }
});
