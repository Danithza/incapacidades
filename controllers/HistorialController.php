<?php
class HistorialController {
    private $pdo;

    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    /**
     * OBTENER HISTORIAL CON FASES YA DECODIFICADAS
     */
    public function obtenerHistorial(){
        $sql = "SELECT * FROM historial ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$historial) return [];

        foreach($historial as &$h){

            // 1. Si no hay JSON, mandar array vacío
            if (empty($h['fases_json'])) {
                $h['fases_json'] = [];
                continue;
            }

            // 2. Intentar decodificar JSON real guardado en historial
            $decoded = json_decode($h['fases_json'], true);

            // 3. Si falla la decodificación, devolver array vacío
            if (!is_array($decoded)) {
                $h['fases_json'] = [];
            } else {
                $h['fases_json'] = $decoded;
            }
        }

        return $historial;
    }

    /**
     * NUEVO: Obtener historial con filtros
     */
    public function getFiltered($fecha = '', $empleado = '', $accion = '', $usuario = '') {
        $sql = "SELECT * FROM historial WHERE 1=1";
        $params = [];
        
        // Filtro por fecha - USAR 'creado_en' en lugar de 'fecha_accion'
        if(!empty($fecha)) {
            $sql .= " AND DATE(creado_en) = ?";
            $params[] = $fecha;
        }
        
        // Filtro por empleado (búsqueda parcial)
        if(!empty($empleado)) {
            $sql .= " AND nombre_empleado LIKE ?";
            $params[] = "%$empleado%";
        }
        
        // Filtro por acción (en tu tabla historial original no existe la columna 'accion')
        // Verificamos si la columna existe en el INSERT del método registrarAccion
        // Si no existe 'accion', buscamos en otra columna o no aplicamos filtro
        if(!empty($accion)) {
            // Primero verificar si la columna existe
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM historial LIKE 'accion'")->fetch();
            if ($checkColumn) {
                $sql .= " AND accion = ?";
                $params[] = $accion;
            }
            // Si no existe la columna, podrías buscar en otra como 'estado' o 'estado_proceso'
        }
        
        // Filtro por usuario (en tu tabla historial original no existe la columna 'usuario')
        if(!empty($usuario)) {
            // Verificar si la columna existe
            $checkColumn = $this->pdo->query("SHOW COLUMNS FROM historial LIKE 'usuario'")->fetch();
            if ($checkColumn) {
                $sql .= " AND usuario LIKE ?";
                $params[] = "%$usuario%";
            }
        }
        
        $sql .= " ORDER BY id DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Si hay error, devolver historial completo sin filtros
            $historial = $this->obtenerHistorial();
        }

        if (!$historial) return [];

        // Decodificar JSON de fases
        foreach($historial as &$h){
            if (!empty($h['fases_json'])) {
                $decoded = json_decode($h['fases_json'], true);
                $h['fases_json'] = is_array($decoded) ? $decoded : [];
            } else {
                $h['fases_json'] = [];
            }
            
            // Agregar campo 'accion' si no existe (para compatibilidad con la vista)
            if (!isset($h['accion'])) {
                $h['accion'] = 'Registro Finalizado';
            }
            
            // Agregar campo 'usuario' si no existe
            if (!isset($h['usuario'])) {
                $h['usuario'] = 'Sistema';
            }
            
            // Agregar campo 'fecha_accion' usando 'creado_en'
            if (!isset($h['fecha_accion']) && isset($h['creado_en'])) {
                $h['fecha_accion'] = $h['creado_en'];
            }
            
            // Agregar campo 'descripcion' si no existe
            if (!isset($h['descripcion'])) {
                $h['descripcion'] = 'Incapacidad movida al historial';
            }
        }

        return $historial;
    }

    /**
     * NUEVO: Obtener acciones únicas para filtros
     */
    public function getDistinctAcciones() {
        // Verificar si la columna 'accion' existe
        $checkColumn = $this->pdo->query("SHOW COLUMNS FROM historial LIKE 'accion'")->fetch();
        
        if ($checkColumn) {
            $sql = "SELECT DISTINCT accion FROM historial WHERE accion IS NOT NULL AND accion != '' ORDER BY accion";
            $result = $this->pdo->query($sql);
            if ($result) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // Si no existe la columna, devolver valor por defecto
        return [['accion' => 'Registro Finalizado']];
    }

    /**
     * NUEVO: Obtener usuarios únicos para filtros
     */
    public function getDistinctUsuarios() {
        // Verificar si la columna 'usuario' existe
        $checkColumn = $this->pdo->query("SHOW COLUMNS FROM historial LIKE 'usuario'")->fetch();
        
        if ($checkColumn) {
            $sql = "SELECT DISTINCT usuario FROM historial WHERE usuario IS NOT NULL AND usuario != '' ORDER BY usuario";
            $result = $this->pdo->query($sql);
            if ($result) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // Si no existe la columna, devolver valor por defecto
        return [['usuario' => 'Sistema']];
    }

    /**
     * NUEVO: Obtener estados únicos para filtros
     */
    public function getDistinctEstados() {
        $sql = "SELECT DISTINCT estado FROM historial WHERE estado IS NOT NULL AND estado != '' ORDER BY estado";
        $result = $this->pdo->query($sql);
        if ($result) {
            return $result->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * 🔥 NO TOCADO — LÓGICA ORIGINAL DE MOVER AL HISTORIAL
     */
    public function moverAlHistorial($incapacidadId)
    {
        // 1. Obtener la incapacidad original
        $sql = "SELECT * FROM incapacidades WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $incapacidadId]);
        $inc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inc) {
            return false;
        }

        // 2. Obtener fases asociadas
        $sqlFases = "SELECT * FROM fases WHERE incapacidad_id = :id";
        $stmtFases = $this->pdo->prepare($sqlFases);
        $stmtFases->execute(['id' => $incapacidadId]);
        $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);

        // Preparar JSON
        $fasesJson = json_encode($fases, JSON_UNESCAPED_UNICODE);

        // 3. Insertar en historial
        $sqlInsert = "
            INSERT INTO historial (
                incapacidad_id, numero_incapacidad, mes, nombre_empleado,
                cedula, area, cod_diagnostico, diagnostico, tipo_incapacidad,
                eps_arl, inicio, termina, dias_incapacidad, dias_a_cargo_entidad,
                valor, valor_aprox, estado_proceso, aplicacion_pago, observaciones,
                numero_orden, creado_en, estado, fases_json, fecha_finalizacion
            )
            VALUES (
                :incapacidad_id, :numero_incapacidad, :mes, :nombre_empleado,
                :cedula, :area, :cod_diagnostico, :diagnostico, :tipo_incapacidad,
                :eps_arl, :inicio, :termina, :dias_incapacidad, :dias_a_cargo_entidad,
                :valor, :valor_aprox, :estado_proceso, :aplicacion_pago,
                :observaciones, :numero_orden, NOW(), :estado, :fases_json, NOW()
            )
        ";

        $stmtInsert = $this->pdo->prepare($sqlInsert);

        $inc['fases_json'] = $fasesJson;

        $ok = $stmtInsert->execute([
            'incapacidad_id' => $inc['id'],
            'numero_incapacidad' => $inc['numero_incapacidad'],
            'mes' => $inc['mes'],
            'nombre_empleado' => $inc['nombre_empleado'],
            'cedula' => $inc['cedula'],
            'area' => $inc['area'],
            'cod_diagnostico' => $inc['cod_diagnostico'],
            'diagnostico' => $inc['diagnostico'],
            'tipo_incapacidad' => $inc['tipo_incapacidad'],
            'eps_arl' => $inc['eps_arl'],
            'inicio' => $inc['inicio'],
            'termina' => $inc['termina'],
            'dias_incapacidad' => $inc['dias_incapacidad'],
            'dias_a_cargo_entidad' => $inc['dias_a_cargo_entidad'],
            'valor' => $inc['valor'],
            'valor_aprox' => $inc['valor_aprox'],
            'estado_proceso' => $inc['estado_proceso'],
            'aplicacion_pago' => $inc['aplicacion_pago'],
            'observaciones' => $inc['observaciones'],
            'numero_orden' => $inc['numero_orden'],
            'estado' => 'finalizado',
            'fases_json' => $fasesJson
        ]);

        if ($ok) {
            // 4. Eliminar de incapacidades (desaparecer de seguimiento)
            $sqlDelete = "DELETE FROM incapacidades WHERE id = :id";
            $stmtDel = $this->pdo->prepare($sqlDelete);
            $stmtDel->execute(['id' => $incapacidadId]);
            return true;
        }

        return false;
    }

    /**
     * NUEVO: Método para registrar acciones en el historial (tabla separada si existe)
     */
    public function registrarAccion($incapacidad_id, $accion, $descripcion, $usuario, $detalles_cambio = null) {
        try {
            // Verificar si existe la tabla de acciones separada
            $tableExists = $this->pdo->query("SHOW TABLES LIKE 'historial_acciones'")->fetch();
            
            if ($tableExists) {
                // Usar tabla separada de historial de acciones
                $sql = "INSERT INTO historial_acciones (
                            id_incapacidad, 
                            numero_incapacidad, 
                            nombre_empleado, 
                            accion, 
                            descripcion, 
                            usuario, 
                            fecha_accion, 
                            detalles_cambio
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
                
                // Obtener información básica de la incapacidad
                $sqlInc = "SELECT numero_incapacidad, nombre_empleado FROM incapacidades WHERE id = ?";
                $stmtInc = $this->pdo->prepare($sqlInc);
                $stmtInc->execute([$incapacidad_id]);
                $inc = $stmtInc->fetch(PDO::FETCH_ASSOC);
                
                if (!$inc) return false;
                
                $stmt = $this->pdo->prepare($sql);
                
                return $stmt->execute([
                    $incapacidad_id,
                    $inc['numero_incapacidad'],
                    $inc['nombre_empleado'],
                    $accion,
                    $descripcion,
                    $usuario,
                    $detalles_cambio ? json_encode($detalles_cambio, JSON_UNESCAPED_UNICODE) : null
                ]);
            }
        } catch (Exception $e) {
            // Si no existe la tabla o hay error, simplemente retornar false
            return false;
        }
        
        return false;
    }
    
    /**
     * NUEVO: Método para obtener historial de acciones (si existe tabla separada)
     */
    public function obtenerHistorialAcciones() {
        try {
            $tableExists = $this->pdo->query("SHOW TABLES LIKE 'historial_acciones'")->fetch();
            
            if ($tableExists) {
                $sql = "SELECT * FROM historial_acciones ORDER BY fecha_accion DESC";
                $stmt = $this->pdo->query($sql);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // Si no existe la tabla, retornar array vacío
        }
        
        return [];
    }
    
    /**
     * NUEVO: Método para combinar historial principal con acciones
     */
    public function obtenerHistorialCompleto() {
        $historialPrincipal = $this->obtenerHistorial();
        $historialAcciones = $this->obtenerHistorialAcciones();
        
        // Combinar ambos arrays
        $historialCompleto = array_merge($historialPrincipal, $historialAcciones);
        
        // Ordenar por fecha (más reciente primero)
        usort($historialCompleto, function($a, $b) {
            $fechaA = $a['fecha_accion'] ?? $a['creado_en'] ?? '0000-00-00';
            $fechaB = $b['fecha_accion'] ?? $b['creado_en'] ?? '0000-00-00';
            return strtotime($fechaB) - strtotime($fechaA);
        });
        
        return $historialCompleto;
    }
}