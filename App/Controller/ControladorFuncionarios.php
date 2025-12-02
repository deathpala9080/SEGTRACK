<?php
// =======================================================
// 🛠️ CONFIGURACIÓN DE LOGS Y DEBUG (LIMPIADO Y OPTIMIZADO)
// =======================================================

// Ruta donde se guardará la carpeta debugFunc
$ruta_debug = __DIR__ . '/debugFunc';

// Crear carpeta si no existe
if (!file_exists($ruta_debug)) {
    mkdir($ruta_debug, 0777, true);
}

// Archivos de logs
$ruta_error_log = $ruta_debug . '/error_log.txt';
$ruta_debug_log = $ruta_debug . '/debug_log.txt';

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $ruta_error_log);

// Iniciar buffer de salida
ob_start();

// Establecer cabeceras
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Registrar inicio de la petición
file_put_contents($ruta_debug_log, date('Y-m-d H:i:s') . " === INICIO DE PETICIÓN ===\n", FILE_APPEND);
file_put_contents($ruta_debug_log, "POST recibido:\n" . json_encode($_POST, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);


// =======================================================
// 🛢️ CONEXIÓN A LA BASE DE DATOS
// =======================================================
try {

    $ruta_conexion = __DIR__ . '/../Core/conexion.php';

    if (!file_exists($ruta_conexion)) {
        throw new Exception("Archivo de conexión no encontrado");
    }

    require_once $ruta_conexion;

    $conexionObj = new Conexion();
    $conexion = $conexionObj->getConexion();

    if (!($conexion instanceof PDO)) {
        throw new Exception("La conexión PDO no es válida");
    }

    file_put_contents($ruta_debug_log, "Conexión establecida correctamente\n", FILE_APPEND);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}


// =======================================================
// 📦 CARGA DE LIBRERÍAS Y MODELOS
// =======================================================
try {

    // Librería QR
    $ruta_qrlib = __DIR__ . '/../Libraries/phpqrcode/qrlib.php';

    if (!file_exists($ruta_qrlib)) {
        throw new Exception("Librería QR no encontrada");
    }

    require_once $ruta_qrlib;
    file_put_contents($ruta_debug_log, "Librería QR cargada\n", FILE_APPEND);

    // Modelo
    $ruta_modelo = __DIR__ . '/../Model/ModeloFuncionarios.php';

    if (!file_exists($ruta_modelo)) {
        throw new Exception("ModeloFuncionarios no encontrado");
    }

    require_once $ruta_modelo;
    file_put_contents($ruta_debug_log, "Modelo Funcionarios cargado\n", FILE_APPEND);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}


// =======================================================
// 🏛️ CONTROLADOR DE FUNCIONARIOS (POO)
// =======================================================
class ControladorFuncionario {

    private $modelo;
    private $log;

    public function __construct($conexion, $ruta_log) {
        $this->modelo = new ModeloFuncionario($conexion);
        $this->log = $ruta_log;
    }

    private function log($msg) {
        file_put_contents($this->log, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
    }

    private function campoVacio($campo): bool {
        return !isset($campo) || trim($campo) === '';
    }

    // ----------------------------------------------------
    // 📌 GENERAR QR
    // ----------------------------------------------------
    private function generarQR($id, $nombre, $documento) {

        $this->log("Generando QR para ID $id");

        $carpeta = __DIR__ . '/../../Public/qr/Qr_Func';

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
            $this->log("Carpeta QR creada: $carpeta");
        }

        $archivo = "QR-FUNC-$id-" . uniqid() . ".png";
        $rutaCompleta = "$carpeta/$archivo";

        $contenido = "ID: $id\nNombre: $nombre\nDocumento: $documento";

        QRcode::png($contenido, $rutaCompleta, QR_ECLEVEL_H, 10);

        if (!file_exists($rutaCompleta)) {
            throw new Exception("No se pudo generar el QR");
        }

        return "qr/Qr_Func/$archivo";
    }

    // ----------------------------------------------------
    // 🟢 REGISTRAR FUNCIONARIO
    // ----------------------------------------------------
    public function registrarFuncionario($datos) {

        $this->log("registrarFuncionario iniciado");

        $cargo      = $datos["CargoFuncionario"] ?? null;
        $nombre     = $datos["NombreFuncionario"] ?? null;
        $sede       = $datos["IdSede"] ?? null;
        $telefono   = $datos["TelefonoFuncionario"] ?? null;
        $documento  = $datos["DocumentoFuncionario"] ?? null;
        $correo     = $datos["CorreoFuncionario"] ?? null;

        // Validaciones
        if ($this->campoVacio($cargo)) return ["success" => false, "message" => "Falta el Cargo"];
        if ($this->campoVacio($nombre)) return ["success" => false, "message" => "Falta el Nombre"];
        if ($this->campoVacio($sede)) return ["success" => false, "message" => "Falta la Sede"];
        if ($this->campoVacio($documento)) return ["success" => false, "message" => "Falta el Documento"];

        try {

            // Registrar BD
            $resultado = $this->modelo->RegistrarFuncionario(
                $cargo,
                $nombre,
                (int)$sede,
                (int)$telefono,
                (int)$documento,
                $correo
            );

            if (!$resultado["success"]) {
                return ["success" => false, "message" => $resultado["message"]];
            }

            $id = $resultado["id"];

            // Generar QR
            $rutaQR = $this->generarQR($id, $nombre, $documento);
            $this->modelo->ActualizarQrFuncionario($id, $rutaQR);

            return [
                "success" => true,
                "message" => "Funcionario registrado exitosamente",
                "data" => [
                    "IdFuncionario" => $id,
                    "QrCodigo" => $rutaQR
                ]
            ];

        } catch (Exception $e) {

            $this->log("ERROR registrarFuncionario: " . $e->getMessage());
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    // ----------------------------------------------------
    // 🟡 ACTUALIZAR FUNCIONARIO
    // ----------------------------------------------------
    public function actualizarFuncionario($id, $datos) {

        $this->log("actualizarFuncionario ID: $id");

        try {
            $resp = $this->modelo->actualizar($id, $datos);

            if (!$resp["success"]) {
                return ["success" => false, "message" => "Error al actualizar"];
            }

            return ["success" => true, "message" => "Funcionario actualizado"];

        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}


// =======================================================
// 🎯 PROCESAR ACCIÓN DEL POST
// =======================================================
$controlador = new ControladorFuncionario($conexion, $ruta_debug_log);

if (isset($_POST["accion"])) {
    switch ($_POST["accion"]) {

        case "registrar":
            $respuesta = $controlador->registrarFuncionario($_POST);
            break;

        case "actualizar":
            $id = (int) $_POST["IdFuncionario"];
            $respuesta = $controlador->actualizarFuncionario($id, $_POST);
            break;

        default:
            $respuesta = ["success" => false, "message" => "Acción no válida"];
    }

    echo json_encode($respuesta);
} else {
    echo json_encode(["success" => false, "message" => "No se envió acción"]);
}
