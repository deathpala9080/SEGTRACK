<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " === INICIO ===\n", FILE_APPEND);

try {

    file_put_contents(__DIR__ . '/debug_log.txt', "POST recibido:\n" . json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    // =============================
    //  CARGAR CONEXIÓN
    // =============================
    $ruta_conexion = __DIR__ . '/../Core/conexion.php';
    if (!file_exists($ruta_conexion)) {
        throw new Exception("Archivo de conexión no encontrado: $ruta_conexion");
    }
    require_once $ruta_conexion;

    $conexion = (new Conexion())->getConexion();

    if (!$conexion instanceof PDO) {
        throw new Exception("No se obtuvo una instancia válida de PDO");
    }

    // =============================
    //  CARGAR LIBRERÍA QR
    // =============================
    $ruta_qrlib = __DIR__ . '/../Libraries/phpqrcode/qrlib.php';
    if (!file_exists($ruta_qrlib)) {
        throw new Exception("Librería phpqrcode no encontrada: $ruta_qrlib");
    }
    require_once $ruta_qrlib;

    // =============================
    //  CARGAR MODELO
    // =============================
    $ruta_modelo = __DIR__ . "/../Model/ModeloFuncionarios.php";
    if (!file_exists($ruta_modelo)) {
        throw new Exception("Modelo no encontrado: $ruta_modelo");
    }
    require_once $ruta_modelo;

    // =============================
    //  CONTROLADOR
    // =============================
    class ControladorFuncionario
    {

        private $modelo;

        public function __construct($conexion)
        {
            $this->modelo = new ModeloFuncionario($conexion);
        }

        private function campoVacio($campo): bool
        {
            return !isset($campo) || trim($campo) === '';
        }

        private function generarQR($idFuncionario, $nombre, $documento)
        {

            $rutaCarpeta = realpath(__DIR__ . '/../../Public/qr/Qr_Func');

            if (!$rutaCarpeta) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR: No se encontró la carpeta Qr_Func\n", FILE_APPEND);
                return false;
            }

            $nombreArchivo = "QR-FUNC-" . $idFuncionario . "-" . time() . ".png";
            $rutaCompleta = $rutaCarpeta . '/' . $nombreArchivo;

            // 🔥 AGREGA ESTA LÍNEA JUSTO AQUÍ
            file_put_contents(__DIR__ . '/debug_log.txt', "RUTA CREADA: $rutaCompleta\n", FILE_APPEND);

            $contenidoQR = "ID: $idFuncionario\nNombre: $nombre\nDocumento: $documento";

            QRcode::png($contenidoQR, $rutaCompleta, QR_ECLEVEL_H, 10);

            return $nombreArchivo;
        }


        public function registrarFuncionario(array $datos): array
        {
            $cargo = trim($datos['CargoFuncionario']);
            $nombre = trim($datos['NombreFuncionario']);
            $sede = $datos['IdSede'];
            $telefono = $datos['TelefonoFuncionario'];
            $documento = $datos['DocumentoFuncionario'];
            $correo = trim($datos['CorreoFuncionario']);

            if ($this->campoVacio($cargo))
                return ['success' => false, 'message' => 'Campo Cargo vacío'];
            if ($this->campoVacio($nombre))
                return ['success' => false, 'message' => 'Campo Nombre vacío'];
            if ($this->campoVacio($sede))
                return ['success' => false, 'message' => 'Campo Sede vacío'];
            if ($this->campoVacio($documento))
                return ['success' => false, 'message' => 'Campo Documento vacío'];

            $resultado = $this->modelo->RegistrarFuncionario(
                $cargo,
                $nombre,
                (int) $sede,
                (int) $telefono,
                (int) $documento,
                $correo
            );

            if ($resultado['success']) {
                $id = $resultado['id'];
                $rutaQR = $this->generarQR($id, $nombre, $documento);

                if ($rutaQR) {
                    $this->modelo->ActualizarQrFuncionario($id, $rutaQR);
                }

                return [
                    'success' => true,
                    'message' => 'Funcionario registrado correctamente',
                    'data' => [
                        'IdFuncionario' => $id,
                        'QrCodigoFuncionario' => $rutaQR
                    ]
                ];
            }

            return ['success' => false, 'message' => $resultado['error'] ?? 'Error desconocido'];
        }
    }

    // =============================
    //  EJECUCIÓN DEL CONTROLADOR
    // =============================
    $controlador = new ControladorFuncionario($conexion);
    $accion = $_POST['accion'] ?? 'registrar';

    if ($accion === 'registrar') {
        $respuesta = $controlador->registrarFuncionario($_POST);
    } else {
        $respuesta = ['success' => false, 'message' => 'Acción no válida'];
    }

    ob_end_clean();
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    ob_end_clean();

    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
