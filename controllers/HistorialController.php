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
}
