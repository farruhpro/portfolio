// Простейшая "конфигурация". Если проект лежит в /portfolio, оставь как есть.
const BASE = (document.querySelector('base')?.getAttribute('href') || '/').replace(/\/+$/,'');
const API_BASE = BASE + '/api/public';

// нормализация путей из настроек (если в БД они начинаются с "/")
const normalize = p => !p ? '' : p.replace(/^\/+/, '');

const apiGet = async (path) => {
  const r = await fetch(API_BASE + path, {credentials:'include'});
  if (!r.ok) throw new Error(await r.text());
  return await r.json();
};
const apiPost = async (path, data) => {
  const r = await fetch(API_BASE + path, {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(data||{})
  });
  if (!r.ok) throw new Error(await r.text());
  return await r.json();
};

const fmtHTML = (s='') => s
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('\n','<br>');

const q = (sel,root=document)=>root.querySelector(sel);
const qa = (sel,root=document)=>Array.from(root.querySelectorAll(sel));

const io = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }});
},{threshold:.12});
const reveal = (el)=>{ el.classList.add('fadein'); io.observe(el); };

document.addEventListener('DOMContentLoaded', async () => {
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();
  try {
    const s = await apiGet('/settings');
    if (s.footer_text) {
      const f = document.getElementById('siteFooter');
      if (f) f.textContent = s.footer_text;
    }
    if (s.header_title) {
      const el = document.getElementById('headerTitle');
      if (el) el.textContent = s.header_title;
    }
	if (s.hero_poster_path) {
	  const v = document.getElementById('heroVideo');
	  if (v) v.setAttribute('poster', normalize(s.hero_poster_path));
	}
  } catch(e) { /* молча */ }
});
