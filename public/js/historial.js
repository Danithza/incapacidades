document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const modalFases = document.getElementById('modalFases');
    const modalObservaciones = document.getElementById('modalObservaciones');
    const searchInput = document.getElementById('searchInput');
    const historialRows = document.querySelectorAll('.historial-row');
    const rowCountElement = document.getElementById('rowCount');
    
    // Función para abrir modal de fases
    document.querySelectorAll('.ver-fases').forEach(btn => {
        btn.addEventListener('click', function() {
            const fases = JSON.parse(this.dataset.fases || "[]");
            const empleado = this.dataset.empleado;
            const incapacidad = this.dataset.incapacidad;
            
            // Actualizar información del modal
            document.getElementById('modalEmpleado').textContent = empleado;
            document.getElementById('modalIncapacidad').textContent = `Incapacidad #${incapacidad}`;
            
            const listaFases = document.getElementById('listaFases');
            const noFases = document.getElementById('noFases');
            
            // Limpiar contenido anterior
            listaFases.innerHTML = '';
            
            if (fases.length === 0) {
                listaFases.style.display = 'none';
                noFases.style.display = 'block';
            } else {
                listaFases.style.display = 'block';
                noFases.style.display = 'none';
                
                fases.forEach((fase, index) => {
                    const faseCard = document.createElement('div');
                    faseCard.className = 'fase-card';
                    faseCard.style.animationDelay = `${index * 0.1}s`;
                    
                    const fechaActualizacion = fase.fecha_actualizacion ? 
                        new Date(fase.fecha_actualizacion).toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'No actualizada';
                    
                    faseCard.innerHTML = `
                        <div class="fase-header">
                            <div class="fase-nombre">
                                <i class="fas fa-layer-group"></i>
                                ${fase.nombre_fase}
                            </div>
                            <div class="fase-fecha">
                                <i class="fas fa-calendar-alt"></i>
                                ${fechaActualizacion}
                            </div>
                        </div>
                        
                        <div class="fase-descripcion">
                            ${fase.descripcion ? fase.descripcion : '<em>Sin descripción</em>'}
                        </div>
                        
                        <div class="fase-evidencia">
                            ${fase.evidencia ? 
                                `<a href="/incapacidades/uploads/fases/${fase.evidencia}" 
                                   target="_blank" 
                                   class="btn-evidencia">
                                    <i class="fas fa-paperclip"></i>
                                    Ver Evidencia
                                </a>` : 
                                `<span class="no-evidencia">
                                    <i class="fas fa-times-circle"></i>
                                    Sin evidencia adjunta
                                </span>`
                            }
                        </div>
                    `;
                    
                    listaFases.appendChild(faseCard);
                });
            }
            
            // Mostrar modal
            modalFases.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Función para abrir modal de observaciones
    document.querySelectorAll('.btn-observaciones').forEach(btn => {
        btn.addEventListener('click', function() {
            const observaciones = this.dataset.observaciones;
            document.getElementById('observacionesContent').textContent = observaciones;
            modalObservaciones.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Cerrar modales
    document.querySelectorAll('.modal-close, .close-observaciones').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    });
    
    // Cerrar modales al hacer clic fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-overlay')) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Función de búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleRows = 0;
            
            historialRows.forEach(row => {
                const searchData = row.dataset.search || '';
                if (searchTerm === '' || searchData.includes(searchTerm)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Actualizar contador
            if (rowCountElement) {
                rowCountElement.textContent = visibleRows;
            }
        });
    }
    
    // Efecto hover en filas
    historialRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.zIndex = '1';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.zIndex = '0';
        });
    });
    
    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    });
});