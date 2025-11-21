class Dashboard {
    constructor() {
        this.currentPage = 1;
        this.rowsPerPage = 10;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.filteredData = [];
        
        this.init();
    }

    init() {
        this.initializeCharts();
        this.initializeTable();
        this.initializeEventListeners();
        this.updatePagination();
    }

    initializeCharts() {
        // Gráfico de incapacidades por mes
        const ctx1 = document.getElementById('incapacityChart').getContext('2d');
        this.incapacityChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Incapacidades',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de tipos de incapacidad
        const ctx2 = document.getElementById('typeChart').getContext('2d');
        this.typeChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Enfermedad General', 'Accidente Laboral', 'Licencia Maternidad'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: [
                        '#4caf50',
                        '#ff9800',
                        '#2196f3'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    initializeTable() {
        const table = document.getElementById('historialTable');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        this.filteredData = rows;

        // Ordenamiento de columnas
        table.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                this.sortTable(th.dataset.sort);
            });
        });
    }

    sortTable(column) {
        const table = document.getElementById('historialTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }

        rows.sort((a, b) => {
            let aValue = a.querySelector(`td:nth-child(${this.getColumnIndex(column) + 1})`).textContent;
            let bValue = b.querySelector(`td:nth-child(${this.getColumnIndex(column) + 1})`).textContent;

            // Convertir a números si es posible
            if (!isNaN(aValue) && !isNaN(bValue)) {
                aValue = parseFloat(aValue);
                bValue = parseFloat(bValue);
            }

            if (aValue < bValue) return this.sortDirection === 'asc' ? -1 : 1;
            if (aValue > bValue) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Limpiar y reinsertar filas ordenadas
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        this.updatePagination();
    }

    getColumnIndex(columnName) {
        const headers = document.querySelectorAll('#historialTable th[data-sort]');
        for (let i = 0; i < headers.length; i++) {
            if (headers[i].dataset.sort === columnName) {
                return i;
            }
        }
        return 0;
    }

    initializeEventListeners() {
        // Botones de exportación y actualización
        document.getElementById('btnExport').addEventListener('click', () => this.exportData());
        document.getElementById('btnRefresh').addEventListener('click', () => this.refreshData());

        // Búsqueda
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.filterTable(e.target.value);
        });

        // Paginación
        document.getElementById('prevPage').addEventListener('click', () => this.previousPage());
        document.getElementById('nextPage').addEventListener('click', () => this.nextPage());

        // Modal de detalles
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', () => this.showDetails(btn));
        });

        document.querySelector('.close-modal').addEventListener('click', () => this.hideModal());
        document.getElementById('detailModal').addEventListener('click', (e) => {
            if (e.target.id === 'detailModal') this.hideModal();
        });

        // Filtro de período del gráfico
        document.getElementById('chartPeriod').addEventListener('change', (e) => {
            this.updateChartData(e.target.value);
        });
    }

    filterTable(searchTerm) {
        const table = document.getElementById('historialTable');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
        });

        this.filteredData = Array.from(rows).filter(row => row.style.display !== 'none');
        this.currentPage = 1;
        this.updatePagination();
    }

    updatePagination() {
        const pageNumbers = document.getElementById('pageNumbers');
        const totalPages = Math.ceil(this.filteredData.length / this.rowsPerPage);
        
        pageNumbers.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-number ${i === this.currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => this.goToPage(i));
            pageNumbers.appendChild(pageBtn);
        }

        document.getElementById('prevPage').disabled = this.currentPage === 1;
        document.getElementById('nextPage').disabled = this.currentPage === totalPages;
        
        this.showCurrentPage();
    }

    showCurrentPage() {
        const start = (this.currentPage - 1) * this.rowsPerPage;
        const end = start + this.rowsPerPage;
        
        this.filteredData.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
    }

    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.showCurrentPage();
            this.updatePagination();
        }
    }

    nextPage() {
        const totalPages = Math.ceil(this.filteredData.length / this.rowsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.showCurrentPage();
            this.updatePagination();
        }
    }

    goToPage(page) {
        this.currentPage = page;
        this.showCurrentPage();
        this.updatePagination();
    }

    showDetails(button) {
        const fases = JSON.parse(button.dataset.fases || "[]");
        const modalBody = document.getElementById('modalBody');
        
        let content = '<div class="fases-container">';
        
        if (fases.length === 0) {
            content += '<p>No hay fases registradas para esta incapacidad.</p>';
        } else {
            fases.forEach((fase, index) => {
                content += `
                    <div class="fase-item ${index < fases.length - 1 ? 'fase-border' : ''}">
                        <h4>${fase.nombre_fase}</h4>
                        <p class="fase-descripcion">${fase.descripcion || 'Sin descripción'}</p>
                        <div class="fase-details">
                            ${fase.evidencia 
                                ? `<a href="/incapacidades/uploads/fases/${fase.evidencia}" target="_blank" class="evidencia-link">📎 Ver evidencia</a>`
                                : '<span class="no-evidencia">Sin evidencia</span>'
                            }
                            <span class="fase-fecha">${fase.fecha_actualizacion ? `Actualizado: ${new Date(fase.fecha_actualizacion).toLocaleDateString()}` : '---'}</span>
                        </div>
                    </div>
                `;
            });
        }
        
        content += '</div>';
        modalBody.innerHTML = content;
        
        document.getElementById('detailModal').style.display = 'flex';
    }

    hideModal() {
        document.getElementById('detailModal').style.display = 'none';
    }

    exportData() {
        // Simulación de exportación
        alert('Funcionalidad de exportación en desarrollo...');
    }

    refreshData() {
        // Simulación de actualización
        location.reload();
    }

    updateChartData(period) {
        // Simulación de actualización de datos del gráfico
        console.log('Actualizando gráfico para período:', period);
    }
}

// Inicializar el dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});

// Estilos adicionales para el modal de fases
const style = document.createElement('style');
style.textContent = `
    .fases-container {
        max-height: 400px;
        overflow-y: auto;
    }
    .fase-item {
        padding: 15px 0;
    }
    .fase-border {
        border-bottom: 1px solid #e0e0e0;
    }
    .fase-item h4 {
        color: #2e7d32;
        margin-bottom: 8px;
    }
    .fase-descripcion {
        color: #666;
        margin-bottom: 10px;
        line-height: 1.4;
    }
    .fase-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }
    .evidencia-link {
        color: #2196f3;
        text-decoration: none;
    }
    .evidencia-link:hover {
        text-decoration: underline;
    }
    .no-evidencia {
        color: #999;
        font-style: italic;
    }
    .fase-fecha {
        color: #757575;
    }
`;
document.head.appendChild(style);