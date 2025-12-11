<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Incapacidades</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --sidebar-width: 280px;
            --navbar-height: 70px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            transition: margin-left var(--transition-speed);
        }

        /* Navbar Superior */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0 25px;
            height: var(--navbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .navbar h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Botón Hamburguesa */
        .hamburger {
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
            transition: transform 0.3s;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
        }

        .hamburger:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }

        .hamburger-line {
            width: 20px;
            height: 2px;
            background: white;
            transition: all 0.3s;
        }

        .hamburger.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Menú Lateral */
        .sidebar {
            position: fixed;
            left: calc(-1 * var(--sidebar-width));
            top: var(--navbar-height);
            width: var(--sidebar-width);
            height: calc(100vh - var(--navbar-height));
            background: linear-gradient(180deg, var(--primary-dark), #1a472a);
            color: white;
            transition: left var(--transition-speed);
            z-index: 999;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 25px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
        }

        .sidebar-menu {
            flex: 1;
            padding: 10px 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            text-decoration: none;
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-light);
            padding-left: 30px;
        }

        .sidebar a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
        }

        .menu-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .menu-text {
            flex: 1;
        }

        .menu-badge {
            background: var(--primary-light);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-details h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-details p {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }

        /* Overlay para cerrar el menú */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-speed);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Contenido principal */
        .content {
            padding: 30px;
            margin-top: var(--navbar-height);
            transition: margin-left var(--transition-speed);
        }

        .content.expanded {
            margin-left: var(--sidebar-width);
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .content.expanded {
                margin-left: 0;
            }
            
            .navbar h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Superior -->
    <div class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <div class="logo">GI</div>
                <h1>Gestión de Incapacidades</h1>
            </div>
            <div class="hamburger" onclick="toggleSidebar()">
                <div class="hamburger-line"></div>
                <div class="hamburger-line"></div>
                <div class="hamburger-line"></div>
            </div>
        </div>
    </div>

    <!-- Menú Lateral -->
    <div id="sidebar" class="sidebar">
        <div class="sidebar-header">
        </div>
        
        <div class="sidebar-menu">
            <a href="../views/dashboard.php" class="active">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="../views/listado_incapacidades.php">
                <span class="menu-icon">📋</span>
                <span class="menu-text">Listado</span>
            </a>
            <a href="../views/seguimiento.php">
                <span class="menu-icon">📍</span>
                <span class="menu-text">Seguimiento</span>
            </a>
            <a href="../views/historial.php">
                <span class="menu-icon">📚</span>
                <span class="menu-text">Historial</span>
            </a>
            <a href="../views/crear_incapacidad.php">
                <span class="menu-icon">➕</span>
                <span class="menu-text">Nueva Incapacidad</span>
            </a>
            <a href="../views/reportes.php">
                <span class="menu-icon">📈</span>
                <span class="menu-text">Reportes</span>
            </a>
        </div>
        
    </div>

    <!-- Overlay -->
    <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>
    <br>
    <br> 
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("overlay");
            const hamburger = document.querySelector(".hamburger");
            const content = document.getElementById("content");
            
            sidebar.classList.toggle("open");
            overlay.classList.toggle("active");
            hamburger.classList.toggle("active");
            content.classList.toggle("expanded");
        }

        // Cerrar menú al hacer clic en un enlace (en dispositivos móviles)
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });

        // Cerrar menú con la tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById("sidebar");
                if (sidebar.classList.contains("open")) {
                    toggleSidebar();
                }
            }
        });
    </script>
</body>
</html>