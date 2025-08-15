document.getElementById('contactForm').addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  const st = document.getElementById('status');
  st.textContent=''; 
  const fd = new FormData(ev.target);
  const payload = Object.fromEntries(fd.entries());
  try {
    const res = await apiPost('/contact', payload);
    if (res && res.ok) { st.textContent='Сообщение отправлено!'; ev.target.reset(); }
    else { st.textContent='Не удалось отправить.'; }
  } catch(e){ st.textContent='Ошибка: '+e.message; }
});
