<?php

require_once __DIR__ . '/../Model/ModeloSede.php';

class ControladorSede {

    private $modelo;

    public function __construct() {
        $this->modelo = new ModeloSede();
    }

    public function obtenerInstituciones() {
        return $this->modelo->obtenerInstituciones();
    }

    public function registrarSede($datos) {

        $tipoSede = trim($datos['TipoSede'] ?? '');
        $ciudad = trim($datos['Ciudad'] ?? '');
        $institucion = intval($datos['IdInstitucion'] ?? 0);

        $regexTexto = '/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{1,30}$/';

        if ($tipoSede === '' || $ciudad === '' || $institucion === 0) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
        }

        if (!preg_match($regexTexto, $tipoSede)) {
            return ['success' => false, 'message' => 'El nombre de la sede contiene caracteres inválidos'];
        }

        if (!preg_match($regexTexto, $ciudad)) {
            return ['success' => false, 'message' => 'La ciudad contiene caracteres inválidos'];
        }

        $resultado = $this->modelo->registrarSede($tipoSede, $ciudad, $institucion);

        return $resultado
            ? ['success' => true, 'message' => 'Sede registrada correctamente']
            : ['success' => false, 'message' => 'Error al registrar la sede'];
    }
}

// ===============================
// PETICIÓN AJAX
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    header('Content-Type: application/json');

    $controlador = new ControladorSede();
    $respuesta = [];

    if ($_POST['accion'] === 'registrar') {
        $respuesta = $controlador->registrarSede($_POST);
    }

    echo json_encode($respuesta);
    exit;
}
