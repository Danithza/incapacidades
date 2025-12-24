// Variables globales
let chartMensual = null;
let chartTipos = null;
let currentChartType = 'bar';
let currentPieType = 'pie';

// Inicializar gráficos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard inicializado');
    
    // Inicializar gráficos
    initChartMensual();
    if (window.dashboardData.tiposData.length > 0) {
        initChartTipos();
    }
    
    // Configurar event listeners
    setupEventListeners();
    
    // Iniciar animaciones
    startAnimations();
});

// Configurar event listeners
function setupEventListeners() {
    // Botones de cambio de vista de gráficos
    document.querySelectorAll('.btn-chart').forEach(btn => {
        btn.addEventListener('click', function() {
            const chartType = this.dataset.chart;
            const viewType = this.dataset.type;
            cambiarVistaGrafico(chartType, viewType, this);
        });
    });
    
    // Selector de período
    document.getElementById('periodo').addEventListener('change', function() {
        console.log('Cambiando período a:', this.value);
        // Aquí podrías implementar filtrado de datos
        mostrarNotificacion(`Mostrando datos del ${this.options[this.selectedIndex].text.toLowerCase()}`);
    });
    
    // Botones de ver más
    document.getElementById('btnVerAreas')?.addEventListener('click', verTodasAreas);
    document.getElementById('btnVerEstados')?.addEventListener('click', verDetalleEstados);
    document.getElementById('btnVerIncapacidades')?.addEventListener('click', verTodasIncapacidades);
    
    // Botón de actualizar
    document.querySelector('.btn-refresh').addEventListener('click', actualizarDashboard);
}

// Inicializar gráfico mensual
function initChartMensual() {
    const ctx = document.getElementById('chartMensual');
    if (!ctx) {
        console.error('Elemento chartMensual no encontrado');
        return;
    }
    
    // Destruir gráfico anterior si existe
    if (chartMensual) {
        chartMensual.destroy();
    }
    
    chartMensual = new Chart(ctx, {
        type: currentChartType,
        data: {
            labels: window.dashboardData.mesesLabels,
            datasets: [{
                label: 'Incapacidades',
                data: window.dashboardData.datosMensuales,
                backgroundColor: currentChartType === 'bar' ? 
                    window.dashboardData.chartColors.blue + '20' : 
                    window.dashboardData.chartColors.blue + '80',
                borderColor: window.dashboardData.chartColors.blue,
                borderWidth: currentChartType === 'bar' ? 1 : 2,
                borderRadius: currentChartType === 'bar' ? 4 : 0,
                borderSkipped: false,
                tension: currentChartType === 'line' ? 0.4 : 0,
                fill: currentChartType === 'line'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 10,
                    cornerRadius: 4,
                    titleFont: {
                        size: 12
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Inicializar gráfico de tipos
function initChartTipos() {
    const ctx = document.getElementById('chartTipos');
    if (!ctx) {
        console.error('Elemento chartTipos no encontrado');
        return;
    }
    
    // Destruir gráfico anterior si existe
    if (chartTipos) {
        chartTipos.destroy();
    }
    
    chartTipos = new Chart(ctx, {
        type: currentPieType,
        data: {
            labels: window.dashboardData.tiposLabels,
            datasets: [{
                data: window.dashboardData.tiposData,
                backgroundColor: [
                    window.dashboardData.chartColors.blue,
                    window.dashboardData.chartColors.green,
                    window.dashboardData.chartColors.red,
                    window.dashboardData.chartColors.yellow,
                    window.dashboardData.chartColors.purple
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: currentPieType === 'doughnut' ? '50%' : 0
        }
    });
}

// Cambiar vista de gráfico
function cambiarVistaGrafico(chartType, viewType, button) {
    if (chartType === 'mes') {
        currentChartType = viewType;
        initChartMensual();
    } else if (chartType === 'tipo') {
        currentPieType = viewType;
        if (window.dashboardData.tiposData.length > 0) {
            initChartTipos();
        }
    }
    
    // Actualizar estado de botones
    document.querySelectorAll(`.btn-chart[data-chart="${chartType}"]`).forEach(btn => {
        btn.classList.remove('active');
    });
    button.classList.add('active');
}

// Actualizar dashboard
function actualizarDashboard() {
    const btn = document.querySelector('.btn-refresh');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<span>⏳</span> Actualizando...';
    btn.disabled = true;
    
    // Simular actualización
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Ver todas las áreas
function verTodasAreas() {
    mostrarNotificacion('Redirigiendo a vista completa de áreas');
    // window.location.href = 'areas.php';
}

// Ver detalle de estados
function verDetalleEstados() {
    mostrarNotificacion('Mostrando detalle de estados de proceso');
    // window.location.href = 'estados.php';
}

// Ver todas las incapacidades
function verTodasIncapacidades() {
    mostrarNotificacion('Redirigiendo a lista completa de incapacidades');
    // window.location.href = 'incapacidades.php';
}

// Mostrar notificación temporal
function mostrarNotificacion(mensaje) {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = 'notificacion';
    notificacion.textContent = mensaje;
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notificacion);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notificacion.parentNode) {
                document.body.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Animaciones iniciales
function startAnimations() {
    // Animación para tarjetas KPI
    document.querySelectorAll('.kpi-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animación para gráficos
    setTimeout(() => {
        document.querySelectorAll('.chart-container').forEach((container, index) => {
            container.style.opacity = '0';
            container.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'scale(1)';
            }, index * 150);
        });
    }, 500);
}

// Agregar estilos CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Actualización automática cada 5 minutos (opcional)
// setInterval(() => {
//     console.log('Actualizando datos automáticamente...');
// }, 300000);