<?php
require_once __DIR__ . '/../config/db.php';


class IncapacidadesController {

    private $pdo;

    public function __construct($pdo){
        $this->pdo = $pdo;
    }

    // LISTADO
    public function getAll(){
        $sql = "SELECT * FROM incapacidades ORDER BY id DESC";
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

    // GUARDAR → TABLA incapacidades + TABLA fases (5 por defecto)
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

        // CREAR LAS 5 FASES
        $fases = ["RADICADO", "SIN RADICAR", "RESPUESTA EPS", "PAGO", "FINALIZADO"];

        $sqlF = "INSERT INTO fases (incapacidad_id, nombre_fase) VALUES (?,?)";
        $stmtF = $this->pdo->prepare($sqlF);

        foreach ($fases as $f)
            $stmtF->execute([$id, $f]);

        return $id;
    }

    // OBTENER UNA INCAPACIDAD
    public function find($id){
        $sql = "SELECT * FROM incapacidades WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ELIMINAR
    public function delete($id){
        $sql = "DELETE FROM incapacidades WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function obtenerUsuarios() {
    $stmt = $this->pdo->query("SELECT id, nombre_completo, cedula, area FROM usuarios ORDER BY nombre_completo ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

