<?php
require_once __DIR__ . "/../Core/Conexion.php";

class ModeloSede {

    private $conexion;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->conexion = $conexionObj->getConexion();
    }

    // ======================================================
    // 🔹 REGISTRAR SEDE
    // ======================================================
    public function registrarSede($tipoSede, $ciudad, $idInstitucion) {
        try {
            $sql = "INSERT INTO sede (TipoSede, Ciudad, IdInstitucion)
                    VALUES (:tipo, :ciudad, :institucion)";
            $stmt = $this->conexion->prepare($sql);

            $stmt->bindParam(":tipo", $tipoSede);
            $stmt->bindParam(":ciudad", $ciudad);
            $stmt->bindParam(":institucion", $idInstitucion);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    // ======================================================
    // 🔹 OBTENER TODAS LAS INSTITUCIONES (PREPARED)
    // ======================================================
    public function obtenerInstituciones() {
        try {
            $sql = "SELECT IdInstitucion, NombreInstitucion FROM institucion";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    // ======================================================
    // 🔹 OBTENER TODAS LAS SEDES
    // ======================================================
    public function obtenerSedes() {
        try {
            $sql = "SELECT s.IdSede, s.TipoSede, s.Ciudad, i.NombreInstitucion
                    FROM sede s
                    INNER JOIN institucion i ON s.IdInstitucion = i.IdInstitucion";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    // ======================================================
    // 🔹 OBTENER SEDE POR ID
    // ======================================================
    public function obtenerSedePorId($idSede) {
        try {
            $sql = "SELECT * FROM sede WHERE IdSede = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(":id", $idSede, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    // ======================================================
    // 🔹 ACTUALIZAR SEDE
    // ======================================================
    public function actualizarSede($idSede, $tipoSede, $ciudad, $idInstitucion) {
        try {
            $sql = "UPDATE sede
                    SET TipoSede = :tipo,
                        Ciudad = :ciudad,
                        IdInstitucion = :institucion
                    WHERE IdSede = :id";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bindParam(":tipo", $tipoSede);
            $stmt->bindParam(":ciudad", $ciudad);
            $stmt->bindParam(":institucion", $idInstitucion);
            $stmt->bindParam(":id", $idSede, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    // ======================================================
    // 🔹 ELIMINAR SEDE
    // ======================================================
    public function eliminarSede($idSede) {
        try {
            $sql = "DELETE FROM sede WHERE IdSede = :id";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(":id", $idSede, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }
}
