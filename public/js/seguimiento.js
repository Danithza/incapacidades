document.addEventListener('DOMContentLoaded', function() {

  // ================================
  // Modal editar fase
  // ================================
  const modal = document.getElementById('modalFase');
  const modalClose = document.getElementById('modalClose');
  const form = document.getElementById('formFase');
  const modalTitle = document.getElementById('modalTitle');
  const incapInput = document.getElementById('incapacidad_id');
  const nombreFaseInput = document.getElementById('nombre_fase');
  const descripcionInput = document.getElementById('descripcion');
  const evidenciaInput = document.getElementById('evidencia');
  const existingDiv = document.getElementById('existingEvidencia');

  // Abrir modal al hacer click en "Editar"
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', async () => {
      const incapacidadId = btn.dataset.incapacidad;
      const fase = btn.dataset.fase;

      modalTitle.textContent = `Editar fase: ${fase}`;
      incapInput.value = incapacidadId;
      nombreFaseInput.value = fase;
      descripcionInput.value = '';
      existingDiv.innerHTML = '';

      try {
        const res = await fetch(`/incapacidades/api/get_fase.php?incapacidad_id=${incapacidadId}&nombre_fase=${encodeURIComponent(fase)}`);
        const j = await res.json();

        if (j.success && j.fase) {
          descripcionInput.value = j.fase.descripcion || '';

          if (j.fase.evidencia) {
            existingDiv.innerHTML = `
              <div>Archivo actual: 
                <a href="/incapacidades/uploads/fases/${encodeURIComponent(j.fase.evidencia)}" target="_blank">Ver</a>
              </div>`;
          }

          if (j.fase.fecha_actualizacion) {
            existingDiv.innerHTML += `<div>Última actualización: <small>${j.fase.fecha_actualizacion}</small></div>`;
          }
        }

      } catch (err) {
        console.error(err);
      }

      modal.style.display = 'flex';
    });
  });

  // Cerrar modal
  modalClose.addEventListener('click', () => modal.style.display = 'none');
  window.addEventListener('click', e => {
    if (e.target === modal) modal.style.display = 'none';
  });

  // ================================
  // Guardar fase
  // ================================
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);

    try {
      const res = await fetch('/incapacidades/api/save_fase.php', {
        method: 'POST',
        body: fd
      });
      const j = await res.json();

      if (j.success) {
        alert('Fase guardada');
        modal.style.display = 'none';
        location.reload();
      } else {
        alert('Error: ' + (j.error || 'No especificado'));
      }

    } catch (err) {
      console.error(err);
      alert('Error al guardar fase');
    }
  });

  // ================================
  // Cambio de estado (círculo rojo → verde)
  // ================================
  document.querySelectorAll('.estado-circle').forEach(circle => {
    circle.addEventListener('click', async () => {
      const id = circle.dataset.id;

      try {
        const res = await fetch('/incapacidades/api/update_estado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });

        const j = await res.json();

        if (!j.success) {
          alert("Error: " + j.error);
          return;
        }

        // Quitar fila de la tabla sin recargar
        const fila = circle.closest("tr");
        fila.remove();

        // Redirigir al historial (opcional)
        if (j.redirect) {
          window.location.href = j.redirect;
        }

      } catch (err) {
        console.error(err);
        alert("Error al procesar el cambio de estado");
      }
    });
  });

});
