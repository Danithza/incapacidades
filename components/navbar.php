<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
    }

    .navbar {
        background: #004080;
        color: white;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .navbar h1 {
        margin: 0;
        font-size: 20px;
    }

    /* Hamburguesa */
    .hamburger {
        cursor: pointer;
        font-size: 26px;
        user-select: none;
    }

    /* Menú lateral */
    .sidebar {
        position: fixed;
        left: -260px;
        top: 0;
        width: 250px;
        height: 100%;
        background: #00284d;
        color: white;
        padding-top: 40px;
        transition: 0.3s;
    }

    .sidebar a {
        display: block;
        padding: 12px 20px;
        text-decoration: none;
        color: white;
        font-size: 16px;
    }

    .sidebar a:hover {
        background: #003d73;
    }

    /* Cuando se abra */
    .sidebar.open {
        left: 0;
    }
</style>

<div class="navbar">
    <h1>Gestión de Incapacidades</h1>
    <span class="hamburger" onclick="toggleSidebar()">☰</span>
</div>

<div id="sidebar" class="sidebar">
    <a href="../views/dashboard.php">🏠 Dashboard</a>
    <a href="../views/listado_incapacidades.php">📋 Listado</a>
    <a href="../views/seguimiento.php">📝 Seguimiento</a>
    <a href="../views/historial.php">📚 Historial</a>
    <a href="../views/crear_incapacidad.php">➕ Nueva incapacidad</a>
</div>

<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("open");
    }
</script>
