<?php
// controllers/SeguimientoController.php
class SeguimientoController {
    private $pdo;
    private $fasesDefinidas = ["RADICADO","SIN RADICAR","RESPUESTA EPS","PAGO","FINALIZADO"];

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
}
