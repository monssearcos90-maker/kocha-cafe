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