// Manejo dinámico de filtros
document.addEventListener('DOMContentLoaded', function() {
    
    // Actualizar filtros cuando cambia el tipo de reporte
    const tipoReporte = document.getElementById('tipo_reporte');
    if (tipoReporte) {
        tipoReporte.addEventListener('change', function() {
            // Mantener otros filtros en la URL
            const params = new URLSearchParams(window.location.search);
            params.set('tipo_reporte', this.value);
            
            // Redirigir con los nuevos parámetros
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }
    
    // Autocompletar para campo de empleado
    const empleadoInput = document.querySelector('input[name="empleado"]');
    if (empleadoInput) {
        empleadoInput.addEventListener('input', function() {
            // Aquí podrías implementar autocompletado con AJAX si lo necesitas
            // Por ahora solo dejamos el input normal
        });
    }
    
    // Formatear fecha automáticamente
    const fechaInput = document.querySelector('input[type="date"][name="fecha"]');
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            if (this.value) {
                // Validar formato de fecha
                const date = new Date(this.value);
                if (isNaN(date.getTime())) {
                    alert('Fecha inválida. Use el formato YYYY-MM-DD');
                    this.value = '';
                }
            }
        });
    }
    
    // Limpiar filtros
    const clearBtn = document.querySelector('.btn.rojo');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = window.location.pathname;
        });
    }
    
    // Alternar visibilidad de filtros avanzados (opcional)
    const toggleAdvancedBtn = document.createElement('button');
    toggleAdvancedBtn.type = 'button';
    toggleAdvancedBtn.className = 'btn btn-secondary';
    toggleAdvancedBtn.textContent = 'Filtros Avanzados ▼';
    toggleAdvancedBtn.style.marginTop = '10px';
    toggleAdvancedBtn.style.fontSize = '12px';
    
    const filtrosForm = document.querySelector('.filtros-form');
    if (filtrosForm) {
        const advancedFilters = filtrosForm.querySelectorAll('.filtro-group:nth-child(n+4)');
        
        if (advancedFilters.length > 0) {
            // Inicialmente ocultar filtros avanzados
            advancedFilters.forEach(filter => {
                filter.style.display = 'none';
            });
            
            // Insertar botón de toggle
            filtrosForm.appendChild(toggleAdvancedBtn);
            
            // Toggle de filtros avanzados
            toggleAdvancedBtn.addEventListener('click', function() {
                advancedFilters.forEach(filter => {
                    if (filter.style.display === 'none') {
                        filter.style.display = 'flex';
                    } else {
                        filter.style.display = 'none';
                    }
                });
                
                this.textContent = this.textContent.includes('▼') ? 
                    'Filtros Avanzados ▲' : 'Filtros Avanzados ▼';
            });
        }
    }
});

// Función para exportar con filtros actuales
function exportarConFiltros(tipo) {
    const params = new URLSearchParams(window.location.search);
    
    if (tipo === 'excel') {
        window.location.href = '/incapacidades/actions/export_excel.php?' + params.toString();
    } else if (tipo === 'pdf') {
        window.location.href = '/incapacidades/actions/export_pdf.php?' + params.toString();
    }
}