class Dashboard {
    constructor() {
        this.init();
    }

    init() {
        this.initializeCharts();
        this.initializeEventListeners();
    }

    initializeCharts() {
        // Gráfico de incapacidades por mes
        if (document.getElementById('incapacityChart')) {
            const ctx1 = document.getElementById('incapacityChart').getContext('2d');
            
            // Formatear nombres de meses
            const mesesLabels = window.chartData.meses.map(mes => {
                const [year, month] = mes.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleDateString('es-ES', { month: 'short' });
            });

            this.incapacityChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: mesesLabels,
                    datasets: [{
                        label: 'Incapacidades',
                        data: window.chartData.incapacidadesPorMes,
                        backgroundColor: '#4caf50',
                        borderColor: '#2e7d32',
                        borderWidth: 1
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
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de tipos de incapacidad
        if (document.getElementById('typeChart') && window.chartData.tiposIncapacidad && Object.keys(window.chartData.tiposIncapacidad).length > 0) {
            const ctx2 = document.getElementById('typeChart').getContext('2d');
            
            const tipos = Object.keys(window.chartData.tiposIncapacidad);
            const valores = Object.values(window.chartData.tiposIncapacidad);
            
            // Colores dinámicos basados en el número de tipos
            const colores = tipos.map((_, index) => {
                const hue = (index * 120) % 360;
                return `hsl(${hue}, 70%, 60%)`;
            });

            this.typeChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: tipos,
                    datasets: [{
                        data: valores,
                        backgroundColor: colores,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = Math.round((value / total) * 100);
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    initializeEventListeners() {
        // Botón de exportación
        const btnExport = document.getElementById('btnExport');
        if (btnExport) {
            btnExport.addEventListener('click', () => this.exportData());
        }
    }

    exportData() {
        try {
            // Crear datos para exportar usando los datos disponibles
            const data = {
                totalIncapacidades: parseInt(document.querySelector('.card-primary h3')?.textContent || '0'),
                incapacidadesActivas: parseInt(document.querySelector('.card-warning h3')?.textContent || '0'),
                valorTotal: parseFloat(document.querySelector('.card-success h3')?.textContent?.replace(/[^0-9]/g, '') || '0'),
                promedioDias: parseFloat(document.querySelector('.card-info h3')?.textContent?.replace(',', '.') || '0'),
                fechaExportacion: new Date().toLocaleDateString('es-ES'),
                resumenTipos: window.chartData?.tiposIncapacidad || {}
            };

            // Crear contenido del archivo
            let content = 'RESUMEN DE INCAPACIDADES\n';
            content += '========================\n\n';
            content += `Fecha de exportación: ${data.fechaExportacion}\n\n`;
            content += `Total de incapacidades: ${data.totalIncapacidades}\n`;
            content += `Incapacidades activas: ${data.incapacidadesActivas}\n`;
            content += `Valor total: $${data.valorTotal.toLocaleString('es-ES')}\n`;
            content += `Promedio de días: ${data.promedioDias.toFixed(1)}\n\n`;
            
            if (Object.keys(data.resumenTipos).length > 0) {
                content += 'DISTRIBUCIÓN POR TIPO:\n';
                Object.entries(data.resumenTipos).forEach(([tipo, cantidad]) => {
                    content += `  ${tipo}: ${cantidad}\n`;
                });
            } else {
                content += 'No hay datos de distribución por tipo.\n';
            }

            // Añadir información de áreas
            const areaItems = document.querySelectorAll('.area-item');
            if (areaItems.length > 0) {
                content += '\nTOP ÁREAS:\n';
                areaItems.forEach(item => {
                    const areaName = item.querySelector('.area-name')?.textContent || '';
                    const areaCount = item.querySelector('.area-count')?.textContent || '';
                    content += `  ${areaName}: ${areaCount}\n`;
                });
            }

            // Añadir información de estados
            const statusItems = document.querySelectorAll('.status-item');
            if (statusItems.length > 0) {
                content += '\nESTADOS DE PROCESO:\n';
                statusItems.forEach(item => {
                    const status = item.querySelector('.status-badge')?.textContent || '';
                    const count = item.querySelector('.status-count')?.textContent || '';
                    content += `  ${status}: ${count}\n`;
                });
            }

            // Descargar archivo
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte-incapacidades-${new Date().toISOString().split('T')[0]}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            // Mostrar confirmación
            this.showNotification('Reporte exportado exitosamente', 'success');
        } catch (error) {
            console.error('Error al exportar datos:', error);
            this.showNotification('Error al exportar el reporte', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Estilos para la notificación
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        `;

        // Botón para cerrar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            margin-left: 15px;
            padding: 0;
            line-height: 1;
        `;

        closeBtn.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        });

        document.body.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);

        // Animaciones CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Inicializar el dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar que los datos del gráfico estén disponibles
    if (!window.chartData) {
        console.warn('chartData no está definido. Usando datos por defecto.');
        window.chartData = {
            meses: [],
            incapacidadesPorMes: [],
            tiposIncapacidad: {}
        };
    }
    
    new Dashboard();
});