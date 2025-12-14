<?php
// controllers/SeguimientoController.php
class SeguimientoController {
    private $pdo;
    private $fasesDefinidas = ["RADICADO", "SIN RADICAR", "RESPUESTA EPS", "PAGO", "FINALIZADO"];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Devuelve todas las incapacidades pendientes con un array 'fases' conteniendo las fases definidas
    public function index() {
        // 1) traer solo incapacidades pendientes
        $sql = "SELECT id, numero_incapacidad, mes, nombre_empleado, cedula, area, cod_diagnostico, diagnostico,
                       tipo_incapacidad, eps_arl, inicio, termina, estado_proceso, observaciones, numero_orden,
                       estado
                FROM incapacidades
                WHERE estado = 0   -- solo pendientes
                ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        $incapacidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$incapacidades) return [];

        // 2) traer todas las fases de las incapacidades listadas
        $ids = array_column($incapacidades, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql2 = "SELECT * FROM fases WHERE incapacidad_id IN ($placeholders)";
        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute($ids);
        $fasesRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 3) indexar fases por incapacidad y por nombre_fase (uppercase)
        $fasesIndex = [];
        foreach ($fasesRows as $f) {
            $fasesIndex[$f['incapacidad_id']][strtoupper($f['nombre_fase'])] = $f;
        }

        // 4) construir salida asegurando las fases definidas aunque no existan en BD
        $result = [];
        foreach ($incapacidades as $inc) {
            $incId = $inc['id'];
            $inc['fases'] = [];

            foreach ($this->fasesDefinidas as $faseName) {
                if (isset($fasesIndex[$incId][$faseName])) {
                    $inc['fases'][$faseName] = $fasesIndex[$incId][$faseName];
                } else {
                    // Si no existe, devolvemos estructura vacía
                    $inc['fases'][$faseName] = [
                        'id' => null,
                        'incapacidad_id' => $incId,
                        'nombre_fase' => $faseName,
                        'descripcion' => null,
                        'evidencia' => null,
                        'fecha_actualizacion' => null,
                        'estado' => null
                    ];
                }
            }

            $result[] = $inc;
        }

        return $result;
    }

    /**
     * NUEVO: Obtener seguimiento con filtros
     */
    public function getFiltered($fecha_inicio = '', $empleado = '', $area = '', $estado_proceso = '') {
        // 1) traer incapacidades pendientes con filtros
        $sql = "SELECT id, numero_incapacidad, mes, nombre_empleado, cedula, area, cod_diagnostico, diagnostico,
                       tipo_incapacidad, eps_arl, inicio, termina, estado_proceso, observaciones, numero_orden,
                       estado
                FROM incapacidades
                WHERE estado = 0";  // solo pendientes
                
        $params = [];
        
        // Filtro por fecha de inicio
        if(!empty($fecha_inicio)) {
            $sql .= " AND DATE(inicio) = ?";
            $params[] = $fecha_inicio;
        }
        
        // Filtro por empleado (búsqueda parcial)
        if(!empty($empleado)) {
            $sql .= " AND nombre_empleado LIKE ?";
            $params[] = "%$empleado%";
        }
        
        // Filtro por área
        if(!empty($area)) {
            $sql .= " AND area = ?";
            $params[] = $area;
        }
        
        // Filtro por estado del proceso
        if(!empty($estado_proceso)) {
            $sql .= " AND estado_proceso = ?";
            $params[] = $estado_proceso;
        }
        
        $sql .= " ORDER BY id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $incapacidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$incapacidades) return [];

        // 2) traer fases de las incapacidades filtradas
        $ids = array_column($incapacidades, 'id');
        
        if (empty($ids)) return [];
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql2 = "SELECT * FROM fases WHERE incapacidad_id IN ($placeholders)";
        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute($ids);
        $fasesRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 3) indexar fases por incapacidad y por nombre_fase (uppercase)
        $fasesIndex = [];
        foreach ($fasesRows as $f) {
            $fasesIndex[$f['incapacidad_id']][strtoupper($f['nombre_fase'])] = $f;
        }

        // 4) construir salida con fases definidas
        $result = [];
        foreach ($incapacidades as $inc) {
            $incId = $inc['id'];
            $inc['fases'] = [];

            foreach ($this->fasesDefinidas as $faseName) {
                if (isset($fasesIndex[$incId][$faseName])) {
                    $inc['fases'][$faseName] = $fasesIndex[$incId][$faseName];
                } else {
                    // Si no existe, estructura vacía
                    $inc['fases'][$faseName] = [
                        'id' => null,
                        'incapacidad_id' => $incId,
                        'nombre_fase' => $faseName,
                        'descripcion' => null,
                        'evidencia' => null,
                        'fecha_actualizacion' => null,
                        'estado' => null
                    ];
                }
            }

            $result[] = $inc;
        }

        return $result;
    }

    /**
     * NUEVO: Obtener áreas únicas para filtros
     */
    public function getDistinctAreas() {
        $sql = "SELECT DISTINCT area FROM incapacidades 
                WHERE estado = 0 AND area IS NOT NULL AND area != '' 
                ORDER BY area";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NUEVO: Obtener estados de proceso únicos para filtros
     */
    public function getDistinctEstadosProceso() {
        $sql = "SELECT DISTINCT estado_proceso FROM incapacidades 
                WHERE estado = 0 AND estado_proceso IS NOT NULL AND estado_proceso != '' 
                ORDER BY estado_proceso";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NUEVO: Obtener EPS/ARL únicas para filtros
     */
    public function getDistinctEPS() {
        $sql = "SELECT DISTINCT eps_arl FROM incapacidades 
                WHERE estado = 0 AND eps_arl IS NOT NULL AND eps_arl != '' 
                ORDER BY eps_arl";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // util: devuelve fases definidas (para la vista)
    public function fasesDefinidas() {
        return $this->fasesDefinidas;
    }

    public function getIncapacidadId($fase_id) {
        $sql = "SELECT incapacidad_id FROM fases WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$fase_id]);
        return $stmt->fetchColumn();
    }

    /**
     * NUEVO: Obtener incapacidad específica con sus fases
     */
    public function getIncapacidadWithFases($id) {
        // Obtener incapacidad
        $sql = "SELECT * FROM incapacidades WHERE id = ? AND estado = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $inc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inc) return null;
        
        // Obtener fases
        $sql2 = "SELECT * FROM fases WHERE incapacidad_id = ?";
        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute([$id]);
        $fasesRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Indexar fases
        $fasesIndex = [];
        foreach ($fasesRows as $f) {
            $fasesIndex[strtoupper($f['nombre_fase'])] = $f;
        }
        
        // Construir array de fases con todas las definidas
        $inc['fases'] = [];
        foreach ($this->fasesDefinidas as $faseName) {
            if (isset($fasesIndex[$faseName])) {
                $inc['fases'][$faseName] = $fasesIndex[$faseName];
            } else {
                $inc['fases'][$faseName] = [
                    'id' => null,
                    'incapacidad_id' => $id,
                    'nombre_fase' => $faseName,
                    'descripcion' => null,
                    'evidencia' => null,
                    'fecha_actualizacion' => null,
                    'estado' => null
                ];
            }
        }
        
        return $inc;
    }
}