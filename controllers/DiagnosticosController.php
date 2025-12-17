<?php
class DiagnosticosController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function listar() {
        $stmt = $this->pdo->query("SELECT * FROM diagnosticos ORDER BY cod_diagnostico");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener($codigo) {
        $stmt = $this->pdo->prepare("SELECT * FROM diagnosticos WHERE cod_diagnostico = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data) {
        $sql = "INSERT INTO diagnosticos (cod_diagnostico, diagnostico)
                VALUES (:codigo, :descripcion)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function actualizar($codigo, $data) {
        $sql = "UPDATE diagnosticos SET diagnostico = :descripcion
                WHERE cod_diagnostico = :codigo";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function eliminar($codigo) {
        $stmt = $this->pdo->prepare("DELETE FROM diagnosticos WHERE cod_diagnostico = ?");
        return $stmt->execute([$codigo]);
    }
}
