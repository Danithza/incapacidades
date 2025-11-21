// incapacidad.js
document.addEventListener('DOMContentLoaded', ()=> {
  const form = document.querySelector('#formIncapacidad');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerText = 'Guardando...';

    const fd = new FormData(form);

    try {
      const res = await fetch('../api/create_incapacidad.php', {
        method:'POST',
        body: fd
      });
      const json = await res.json();
      if (json.success) {
        alert('Incapacidad creada (ID: ' + json.id + '). Se crearon las fases por defecto.');
        form.reset();
        // redirigir al seguimiento para completar fases
        window.location.href = 'seguimiento.php?id=' + json.id;
      } else {
        alert('Error: ' + (json.error || 'No especificado'));
      }
    } catch (err) {
      console.error(err);
      alert('Error de conexión');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Guardar';
    }
  });
});
