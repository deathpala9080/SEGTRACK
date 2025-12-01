<?php
// =======================================================
// 🛠️ GESTIÓN DE CARPETA Y RUTAS DE DEBUG (REVISADO)
// =======================================================

// Define la ruta absoluta de la carpeta debugFunc (en el mismo directorio que el controlador)
$ruta_debug = __DIR__ . '/debugFunc';

// 1. CREACIÓN DE LA CARPETA
if (!file_exists($ruta_debug)) { 
    // Intentar crear la carpeta. Suponemos que si falla, usaremos el directorio actual.
    $creacion_exitosa = @mkdir($ruta_debug, 0777, true);
    
    // Si la creación FALLÓ (o ya existía y el @ la suprimió), 
    // y si no podemos escribir, puede haber problemas.
    if (!$creacion_exitosa && !is_writable($ruta_debug)) {
        // Fallback: Si no se pudo crear, usamos el directorio actual.
        $ruta_debug = __DIR__;
    }
}

// 2. DEFINICIÓN DE RUTAS DE LOG
$ruta_error_log = $ruta_debug . '/error_log.txt';
$ruta_debug_log = $ruta_debug . '/debug_log.txt';

// 3. CONFIGURACIÓN DE PHP INI
// Activa el reporte de todos los errores de PHP
error_reporting(E_ALL);
// Desactiva la visualización de errores
ini_set('display_errors', 0); 
// Activa el log de errores
ini_set('log_errors', 1);
// 🔥 CONFIGURA EL ARCHIVO DE LOG DE ERRORES DE PHP
ini_set('error_log', $ruta_error_log);

// 4. INICIALIZACIÓN (MÁS SEGURO)

// Inicia el buffer de salida (útil para prevenir encabezados después de la salida)
ob_start();

// Configura los encabezados
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Primera línea de log, ahora sí es seguro escribirla, 
    // ya que la configuración de la carpeta ha terminado.
    file_put_contents($ruta_debug_log, date('Y-m-d H:i:s') . " === INICIO DE PETICIÓN ===\n", FILE_APPEND);
    
    // Loguea todos los datos POST recibidos para depuración
    file_put_contents($ruta_debug_log, "POST recibido:\n" . json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

    // =======================================================
    // ⚙️ INCLUSIÓN DE ARCHIVOS Y CONEXIÓN
    // =======================================================

    // Verifica y requiere la conexión a la base de datos
    $ruta_conexion = __DIR__ . '/../Core/conexion.php';
    if (!file_exists($ruta_conexion)) {
        throw new Exception("Archivo de conexión no encontrado: $ruta_conexion");
    }
    require_once $ruta_conexion;
    file_put_contents($ruta_debug_log, "Conexión cargada\n", FILE_APPEND);

    // Crea la instancia de conexión PDO
    $conexionObj = new Conexion();
    $conexion = $conexionObj->getConexion();

    if (!isset($conexion) || !($conexion instanceof PDO)) {
        throw new Exception("Conexión PDO no válida o no inicializada");
    }
    file_put_contents($ruta_debug_log, "Conexión verificada como PDO\n", FILE_APPEND);

    // Verifica y requiere la librería QR
    $ruta_qrlib = __DIR__ . '/../Libraries/phpqrcode/qrlib.php';
    if (!file_exists($ruta_qrlib)) {
        throw new Exception("Librería phpqrcode no encontrada: $ruta_qrlib");
    }
    require_once $ruta_qrlib;
    file_put_contents($ruta_debug_log, "Librería QR cargada\n", FILE_APPEND);

    // Verifica y requiere el modelo
    $ruta_modelo = __DIR__ . "/../Model/ModeloFuncionarios.php";
    if (!file_exists($ruta_modelo)) {
        throw new Exception("Modelo no encontrado: $ruta_modelo");
    }
    require_once $ruta_modelo;
    file_put_contents($ruta_debug_log, "Modelo cargado\n", FILE_APPEND);

    // =======================================================
    // 🏛️ CLASE CONTROLADOR
    // =======================================================

    class ControladorFuncionario {
        private $modelo;
        private $ruta_log; 

        public function __construct($conexion, $ruta_log_externa) {
            $this->modelo = new ModeloFuncionario($conexion);
            $this->ruta_log = $ruta_log_externa;
        }

        // Función auxiliar para verificar si un campo está vacío o nulo
        private function campoVacio($campo): bool {
            return !isset($campo) || $campo === '' || trim($campo) === '';
        }

        // Función auxiliar para escribir logs, centralizando la lógica
        private function escribirLog(string $mensaje) {
             file_put_contents($this->ruta_log, date('Y-m-d H:i:s') . " - " . $mensaje . "\n", FILE_APPEND);
        }

        // Genera el código QR y lo guarda en la ruta pública
        private function generarQR(int $idFuncionario, string $nombre, string $documento): ?string {
            try {
                $this->escribirLog("Generando QR para funcionario ID: $idFuncionario");

                // Ruta ABSOLUTA para guardar el archivo en Public/qr/Qr_Func
                // (../../ sale de App/Controller a SEGTRAK, luego entra a Public)
                $rutaCarpeta = __DIR__ . '/../../Public/qr/Qr_Func';
                
                if (!file_exists($rutaCarpeta)) {
                    mkdir($rutaCarpeta, 0777, true);
                    $this->escribirLog("Carpeta QR creada: $rutaCarpeta");
                }

                $nombreArchivo = "QR-FUNC-" . $idFuncionario . "-" . uniqid() . ".png";
                $rutaCompleta = $rutaCarpeta . '/' . $nombreArchivo;
                $contenidoQR = "ID: $idFuncionario\nNombre: $nombre\nDocumento: $documento";

                // Genera el archivo PNG del QR
                QRcode::png($contenidoQR, $rutaCompleta, QR_ECLEVEL_H, 10);

                if (!file_exists($rutaCompleta)) {
                    throw new Exception("El archivo QR no se creó correctamente");
                }

                $this->escribirLog("QR generado exitosamente: $rutaCompleta");
                
                // Retorna la ruta RELATIVA que la VISTA (front-end) puede usar
                return 'qr/Qr_Func/' . $nombreArchivo; 

            } catch (Exception $e) {
                $this->escribirLog("ERROR al generar QR: " . $e->getMessage());
                return null;
            }
        }

        // Lógica para registrar un nuevo funcionario
        public function registrarFuncionario(array $datos): array {
            $this->escribirLog("registrarFuncionario llamado");
            
            $cargo = $datos['CargoFuncionario'] ?? null;
            $nombre = $datos['NombreFuncionario'] ?? null;
            $sede = $datos['IdSede'] ?? null;
            $telefono = $datos['TelefonoFuncionario'] ?? null;
            $documento = $datos['DocumentoFuncionario'] ?? null;
            $correo = $datos['CorreoFuncionario'] ?? null;

            // Validaciones
            if ($this->campoVacio($cargo)) {
                $this->escribirLog("ERROR: Cargo vacío");
                return ['success' => false, 'message' => 'Falta el campo: Cargo'];
            }
            if ($this->campoVacio($nombre)) {
                $this->escribirLog("ERROR: Nombre vacío");
                return ['success' => false, 'message' => 'Falta el campo: Nombre'];
            }
            if ($this->campoVacio($sede)) {
                $this->escribirLog("ERROR: Sede vacía");
                return ['success' => false, 'message' => 'Falta el campo: Sede'];
            }
            if ($this->campoVacio($documento)) {
                $this->escribirLog("ERROR: Documento vacío");
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
                        // Actualiza la BD con la ruta del QR generado
                        $this->modelo->ActualizarQrFuncionario($idFuncionario, $rutaQR);
                    }

                    return [
                        "success" => true,
                        "message" => "Funcionario registrado correctamente con ID: " . $idFuncionario,
                        "data" => ["IdFuncionario" => $idFuncionario, "QrCodigoFuncionario" => $rutaQR]
                    ];
                } else {
                    return ['success' => false, 'message' => $resultado['message'] ?? 'Error al registrar en BD'];
                }
            } catch (Exception $e) {
                $this->escribirLog("EXCEPCIÓN: " . $e->getMessage());
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        // Lógica para actualizar un funcionario existente
        public function actualizarFuncionario(int $id, array $datos): array {
            $this->escribirLog("actualizarFuncionario llamado con ID: $id");
            $this->escribirLog("Datos a actualizar: " . json_encode($datos));

            try {
                $resultado = $this->modelo->actualizar($id, $datos);
                
                if ($resultado['success']) {
                    $this->escribirLog("Funcionario actualizado exitosamente");
                    return ['success' => true, 'message' => 'Funcionario actualizado correctamente'];
                } else {
                    $this->escribirLog("Error al actualizar: " . ($resultado['error'] ?? 'desconocido'));
                    return ['success' => false, 'message' => 'Error al actualizar funcionario'];
                }
            } catch (Exception $e) {
                $this->escribirLog("EXCEPCIÓN en actualizar: " . $e->getMessage());
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }
        
    }

    // =======================================================
    // 🏃 LÓGICA DE EJECUCIÓN (ENRUTAMIENTO)
    // =======================================================

    // Instancia el controlador, pasándole la conexión y la ruta de log
    $controlador = new ControladorFuncionario($conexion, $ruta_debug_log); 
    $accion = $_POST['accion'] ?? 'registrar';

    file_put_contents($ruta_debug_log, "Acción: $accion\n", FILE_APPEND);

    // Enrutamiento de la acción
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

    // Loguea la respuesta final
    file_put_contents($ruta_debug_log, "Respuesta final: " . json_encode($resultado) . "\n", FILE_APPEND);

    // Finaliza el buffer de salida y envía la respuesta JSON
    ob_end_clean();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // =======================================================
    // 🚨 MANEJO DE ERRORES FATALES
    // =======================================================
    ob_end_clean();
    
    $error = $e->getMessage();
    // Loguea el error final en el archivo correcto
    file_put_contents($ruta_debug_log, "ERROR FATAL: $error\n", FILE_APPEND); 
    
    // Envía la respuesta de error al cliente
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $error,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
}

exit;