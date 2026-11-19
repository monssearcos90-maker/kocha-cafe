const hero = document.getElementById('inicio');
const rotulos = { matcha: 'MATCHA', cafes: 'CAFÉS', creamy: 'CREAMY' };

document.querySelectorAll('.hero-tabs button').forEach(b => {
  b.addEventListener('click', () => {
    const cat = b.dataset.cat;
    hero.dataset.cat = cat;
    document.querySelectorAll('.hero-tabs button').forEach(x => x.classList.toggle('activo', x === b));
    document.querySelectorAll('.hero-bebida').forEach(i => i.classList.toggle('activa', i.dataset.cat === cat));
    document.getElementById('vertical').textContent = rotulos[cat];
  });
});

const carrusel = document.getElementById('carrusel');
document.querySelectorAll('.filtros button').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.filtros button').forEach(x => x.classList.toggle('activo', x === b));
    carrusel.querySelectorAll('.tarjeta').forEach(t => {
      t.hidden = b.dataset.filtro !== 'todos' && t.dataset.cat !== b.dataset.filtro;
    });
    carrusel.scrollTo({ left: 0 });
  });
});

document.getElementById('prev').onclick = () => carrusel.scrollBy({ left: -500 });
document.getElementById('next').onclick = () => carrusel.scrollBy({ left: 500 });
document.querySelectorAll('.mas').forEach(b => {
  b.addEventListener('click', async () => {
    b.disabled = true;
    try {
      const fd = new FormData();
      fd.append('accion', 'agregar');
      fd.append('id', b.dataset.id);
      fd.append('csrf', window.CSRF);
      const r = await fetch('carrito.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        const c = document.getElementById('cart-count');
        if (c) { c.textContent = d.total; }
        b.textContent = '✓';
        aviso('Agregado al carrito');
      }
    } catch (e) {
      aviso('No se pudo agregar. Inténtalo de nuevo.');
    } finally {
      setTimeout(() => { b.textContent = '+'; b.disabled = false; }, 900);
    }
  });
});

function aviso(texto) {
  const t = document.createElement('div');
  t.className = 'toast';
  t.textContent = texto;
  document.body.append(t);
  setTimeout(() => t.remove(), 1800);
}