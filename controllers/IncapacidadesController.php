<?php
require_once __DIR__ . '/../config/db.php';

class IncapacidadesController {

    private $pdo;

    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    // Método original para obtener todos
    public function getAll(){
        $sql = "SELECT * FROM incapacidades ORDER BY id DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // NUEVO: Método con filtros
    public function getFiltered($fecha = '', $empleado = '', $area = '', $diagnostico = '', $estado = ''){
        $sql = "SELECT * FROM incapacidades WHERE 1=1";
        $params = [];
        
        if(!empty($fecha)) {
            $sql .= " AND DATE(inicio) = ?";
            $params[] = $fecha;
        }
        
        if(!empty($empleado)) {
            $sql .= " AND nombre_empleado LIKE ?";
            $params[] = "%$empleado%";
        }
        
        if(!empty($area)) {
            $sql .= " AND area = ?";
            $params[] = $area;
        }
        
        if(!empty($diagnostico)) {
            $sql .= " AND diagnostico = ?";
            $params[] = $diagnostico;
        }
        
        if(!empty($estado)) {
            $sql .= " AND estado = ?";
            $params[] = $estado;
        }
        
        $sql .= " ORDER BY id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Métodos para obtener valores únicos para los filtros
    public function getDistinctAreas(){
        $sql = "SELECT DISTINCT area FROM incapacidades WHERE area IS NOT NULL AND area != '' ORDER BY area";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctDiagnosticos(){
        $sql = "SELECT DISTINCT diagnostico FROM incapacidades WHERE diagnostico IS NOT NULL AND diagnostico != '' ORDER BY diagnostico";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctEstados(){
        $sql = "SELECT DISTINCT estado FROM incapacidades WHERE estado IS NOT NULL AND estado != '' ORDER BY estado";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithFases() {
        $sql = "SELECT i.*, f.nombre_fase 
                FROM incapacidades i
                LEFT JOIN fases f ON f.incapacidad_id = i.id
                ORDER BY i.id DESC, f.id ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function store($data){
        $sql = "INSERT INTO incapacidades (
            numero_incapacidad, mes, nombre_empleado, cedula, area, cod_diagnostico,
            diagnostico, tipo_incapacidad, eps_arl, inicio, termina,
            dias_incapacidad, dias_a_cargo_entidad, valor, valor_aprox,
            estado_proceso, aplicacion_pago, observaciones, numero_orden
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $data['numero_incapacidad'],
            $data['mes'],
            $data['nombre_empleado'],
            $data['cedula'],
            $data['area'],
            $data['cod_diagnostico'],
            $data['diagnostico'],
            $data['tipo_incapacidad'],
            $data['eps_arl'],
            $data['inicio'],
            $data['termina'],
            $data['dias_incapacidad'],
            $data['dias_a_cargo_entidad'],
            $data['valor'],
            $data['valor_aprox'],
            $data['estado_proceso'],
            $data['aplicacion_pago'],
            $data['observaciones'],
            $data['numero_orden']
        ]);

        $id = $this->pdo->lastInsertId();

        $fases = ["RADICADO", "SIN RADICAR", "RESPUESTA EPS", "PAGO", "FINALIZADO"];

        $sqlF = "INSERT INTO fases (incapacidad_id, nombre_fase) VALUES (?,?)";
        $stmtF = $this->pdo->prepare($sqlF);

        foreach ($fases as $f)
            $stmtF->execute([$id, $f]);

        return $id;
    }

    public function find($id){
        $sql = "SELECT * FROM incapacidades WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id){
        $sql = "DELETE FROM incapacidades WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function obtenerUsuarios() {
        $stmt = $this->pdo->query("SELECT id, nombre_completo, cedula, area FROM usuarios ORDER BY nombre_completo ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDiagnosticos() {
        $stmt = $this->pdo->query("SELECT cod_diagnostico, diagnostico FROM diagnosticos ORDER BY cod_diagnostico ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $sql = "UPDATE incapacidades SET 
                    numero_incapacidad = :numero_incapacidad,
                    mes = :mes,
                    nombre_empleado = :nombre_empleado,
                    cedula = :cedula,
                    area = :area,
                    numero_orden = :numero_orden,
                    cod_diagnostico = :cod_diagnostico,
                    diagnostico = :diagnostico,
                    tipo_incapacidad = :tipo_incapacidad,
                    eps_arl = :eps_arl,
                    inicio = :inicio,
                    termina = :termina,
                    dias_incapacidad = :dias_incapacidad,
                    dias_a_cargo_entidad = :dias_a_cargo_entidad,
                    valor = :valor,
                    observaciones = :observaciones,
                    estado = :estado
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }
}