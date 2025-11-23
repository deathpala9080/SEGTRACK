<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " === INICIO ===\n", FILE_APPEND);

try {
    file_put_contents(__DIR__ . '/debug_log.txt', "POST recibido:\n" . json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    $ruta_conexion = __DIR__ . '/../Core/conexion.php';
    if (!file_exists($ruta_conexion)) {
        throw new Exception("Archivo de conexión no encontrado: $ruta_conexion");
    }

    require_once $ruta_conexion;
    file_put_contents(__DIR__ . '/debug_log.txt', "Conexión cargada\n", FILE_APPEND);

    // ⭐ Crear instancia de la clase Conexion y obtener el objeto PDO
    $conexionObj = new Conexion();
    $conexion = $conexionObj->getConexion();

    if (!isset($conexion)) {
        throw new Exception("Variable \$conexion no inicializada");
    }

    if (!($conexion instanceof PDO)) {
        throw new Exception("La conexión no es una instancia de PDO");
    }

    file_put_contents(__DIR__ . '/debug_log.txt', "Conexión verificada como PDO\n", FILE_APPEND);

    $ruta_qrlib = __DIR__ . '/../Libraries/phpqrcode/qrlib.php';
    if (!file_exists($ruta_qrlib)) {
        throw new Exception("Librería phpqrcode no encontrada: $ruta_qrlib");
    }
    require_once $ruta_qrlib;
    file_put_contents(__DIR__ . '/debug_log.txt', "Librería QR cargada\n", FILE_APPEND);

    $ruta_modelo = __DIR__ . "/../Model/ModeloFuncionarios.php";
    if (!file_exists($ruta_modelo)) {
        throw new Exception("Modelo no encontrado: $ruta_modelo");
    }
    require_once $ruta_modelo;
    file_put_contents(__DIR__ . '/debug_log.txt', "Modelo cargado\n", FILE_APPEND);

    class ControladorFuncionario {
        private $modelo;

        public function __construct($conexion) {
            $this->modelo = new ModeloFuncionario($conexion);
        }

        private function campoVacio($campo): bool {
            return !isset($campo) || $campo === '' || trim($campo) === '';
        }

        private function generarQR(int $idFuncionario, string $nombre, string $documento): ?string {
            try {
                file_put_contents(__DIR__ . '/debug_log.txt', "Generando QR para funcionario ID: $idFuncionario\n", FILE_APPEND);

                // 🔥 RUTA CORREGIDA: Apunta a Public/qr/Qr_Func/
                $rutaCarpeta = __DIR__ . '/../../Public/qr/Qr_Func';
                
                if (!file_exists($rutaCarpeta)) {
                    mkdir($rutaCarpeta, 0777, true);
                    file_put_contents(__DIR__ . '/debug_log.txt', "Carpeta QR creada: $rutaCarpeta\n", FILE_APPEND);
                }

                $nombreArchivo = "QR-FUNC-" . $idFuncionario . "-" . uniqid() . ".png";
                $rutaCompleta = $rutaCarpeta . '/' . $nombreArchivo;
                $contenidoQR = "ID: $idFuncionario\nNombre: $nombre\nDocumento: $documento";

                QRcode::png($contenidoQR, $rutaCompleta, QR_ECLEVEL_H, 10);

                if (!file_exists($rutaCompleta)) {
                    throw new Exception("El archivo QR no se creó correctamente");
                }

                file_put_contents(__DIR__ . '/debug_log.txt', "QR generado exitosamente: $rutaCompleta\n", FILE_APPEND);
                
                // ⭐ RETORNA LA RUTA RELATIVA CORRECTA: qr/Qr_Func/archivo.png
                return 'qr/Qr_Func/' . $nombreArchivo;

            } catch (Exception $e) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR al generar QR: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        }

        public function registrarFuncionario(array $datos): array {
            file_put_contents(__DIR__ . '/debug_log.txt', "registrarFuncionario llamado\n", FILE_APPEND);

            $cargo = $datos['CargoFuncionario'] ?? null;
            $nombre = $datos['NombreFuncionario'] ?? null;
            $sede = $datos['IdSede'] ?? null;
            $telefono = $datos['TelefonoFuncionario'] ?? null;
            $documento = $datos['DocumentoFuncionario'] ?? null;
            $correo = $datos['CorreoFuncionario'] ?? null;

            if ($this->campoVacio($cargo)) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR: Cargo vacío\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Falta el campo: Cargo'];
            }

            if ($this->campoVacio($nombre)) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR: Nombre vacío\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Falta el campo: Nombre'];
            }

            if ($this->campoVacio($sede)) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR: Sede vacía\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Falta el campo: Sede'];
            }

            if ($this->campoVacio($documento)) {
                file_put_contents(__DIR__ . '/debug_log.txt', "ERROR: Documento vacío\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Falta el campo: Documento'];
            }

            try {
                $resultado = $this->modelo->RegistrarFuncionario(
                    $cargo, 
                    $nombre, 
                    (int)$sede, 
                    (int)$telefono, 
                    (int)$documento, 
                    $correo
                );

                if ($resultado['success']) {
                    $idFuncionario = $resultado['id'];
                    $rutaQR = $this->generarQR($idFuncionario, $nombre, $documento);

                    if ($rutaQR) {
                        $this->modelo->ActualizarQrFuncionario($idFuncionario, $rutaQR);
                    }

                    return [
                        "success" => true,
                        "message" => "Funcionario registrado correctamente con ID: " . $idFuncionario,
                        "data" => ["IdFuncionario" => $idFuncionario, "QrCodigoFuncionario" => $rutaQR]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Error al registrar en BD'];
                }
            } catch (Exception $e) {
                file_put_contents(__DIR__ . '/debug_log.txt', "EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        public function actualizarFuncionario(int $id, array $datos): array {
            file_put_contents(__DIR__ . '/debug_log.txt', "actualizarFuncionario llamado con ID: $id\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/debug_log.txt', "Datos a actualizar: " . json_encode($datos) . "\n", FILE_APPEND);

            try {
                $resultado = $this->modelo->actualizar($id, $datos);
                
                if ($resultado['success']) {
                    file_put_contents(__DIR__ . '/debug_log.txt', "Funcionario actualizado exitosamente\n", FILE_APPEND);
                    return ['success' => true, 'message' => 'Funcionario actualizado correctamente'];
                } else {
                    file_put_contents(__DIR__ . '/debug_log.txt', "Error al actualizar: " . ($resultado['error'] ?? 'desconocido') . "\n", FILE_APPEND);
                    return ['success' => false, 'message' => 'Error al actualizar funcionario'];
                }
            } catch (Exception $e) {
                file_put_contents(__DIR__ . '/debug_log.txt', "EXCEPCIÓN en actualizar: " . $e->getMessage() . "\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }
    }

    $controlador = new ControladorFuncionario($conexion);
    $accion = $_POST['accion'] ?? 'registrar';

    file_put_contents(__DIR__ . '/debug_log.txt', "Acción: $accion\n", FILE_APPEND);

    if ($accion === 'registrar') {
        $resultado = $controlador->registrarFuncionario($_POST);
    } elseif ($accion === 'actualizar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $datos = [
                'CargoFuncionario' => $_POST['cargo'] ?? null,
                'NombreFuncionario' => $_POST['nombre'] ?? null,
                'IdSede' => !empty($_POST['sede']) ? (int)$_POST['sede'] : null,
                'TelefonoFuncionario' => !empty($_POST['telefono']) ? (int)$_POST['telefono'] : null,
                'DocumentoFuncionario' => !empty($_POST['documento']) ? (int)$_POST['documento'] : null,
                'CorreoFuncionario' => $_POST['correo'] ?? null
            ];
            $resultado = $controlador->actualizarFuncionario($id, $datos);
        } else {
            $resultado = ['success' => false, 'message' => 'ID de funcionario no válido'];
        }
    } else {
        $resultado = ['success' => false, 'message' => 'Acción no reconocida'];
    }

    file_put_contents(__DIR__ . '/debug_log.txt', "Respuesta final: " . json_encode($resultado) . "\n", FILE_APPEND);

    ob_end_clean();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    
    $error = $e->getMessage();
    file_put_contents(__DIR__ . '/debug_log.txt', "ERROR FINAL: $error\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $error,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
}

exit;