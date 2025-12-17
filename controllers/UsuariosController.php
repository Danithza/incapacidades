<?php

class UsuariosController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ==========================
       LISTAR (con filtro opcional)
    ========================== */
    public function listar($filtro = null) {
        if ($filtro) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM usuarios
                WHERE nombre_completo LIKE :filtro
                   OR cedula LIKE :filtro
                   OR area LIKE :filtro
                ORDER BY nombre_completo
            ");
            $stmt->execute(['filtro' => "%$filtro%"]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM usuarios ORDER BY nombre_completo");
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ==========================
       CREAR (🔥 CORREGIDO)
    ========================== */
    public function crear($data) {
        $sql = "INSERT INTO usuarios (cedula, nombre_completo, area)
                VALUES (:cedula, :nombre_completo, :area)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'cedula' => $data['cedula'],
            'nombre_completo' => $data['nombre_completo'],
            'area' => $data['area']
        ]);
    }

    /* ==========================
       ACTUALIZAR (🔥 CORREGIDO)
    ========================== */
    public function actualizar($id, $data) {
        $sql = "UPDATE usuarios SET
                    cedula = :cedula,
                    nombre_completo = :nombre_completo,
                    area = :area
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'cedula' => $data['cedula'],
            'nombre_completo' => $data['nombre_completo'],
            'area' => $data['area'],
            'id' => $id
        ]);
    }

    public function eliminar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function obtenerAreas() {
        $stmt = $this->pdo->query("SELECT DISTINCT area FROM usuarios ORDER BY area");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
