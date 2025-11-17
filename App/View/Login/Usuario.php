<?php
// Incluye la parte superior de la plantilla y la conexión a la base de datos
require_once __DIR__ . '/../layouts/parte_superior_supervisor.php';
require_once __DIR__ . '/../../Core/conexion.php';

// Inicialización de la conexión PDO
try {
    $db = new Conexion();
    $pdo = $db->getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("<div style='color: red; text-align: center; padding: 20px; border: 1px solid red;'>Error de Conexión a la Base de Datos: " . $e->getMessage() . "</div>");
}

// --- Lógica para obtener Funcionarios ---
$funcionarios = [];
$sql_funcionarios = "SELECT IdFuncionario, NombreFuncionario FROM Funcionario ORDER BY NombreFuncionario";
try {
    $stmt_func = $pdo->prepare($sql_funcionarios);
    $stmt_func->execute();
    $funcionarios = $stmt_func->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div style='color: red; text-align: center;'>Error al cargar los funcionarios: " . $e->getMessage() . "</div>";
}

// Roles permitidos (deben coincidir con el ENUM de la base de datos)
$roles_permitidos = ['Supervisor', 'Personal Seguridad', 'Administrador'];
?>
<style>
.input-valid {
    border: 2px solid #28a745 !important;
    background: #eaffea !important;
}

.input-invalid {
    border: 2px solid #dc3545 !important;
    background: #ffeaea !important;
}

.label-valid {
    color: #28a745 !important;
    font-weight: bold;
}

.label-invalid {
    color: #dc3545 !important;
    font-weight: bold;
}
</style>
<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-circle me-2"></i>Registrar Usuario</h1>
        <a href="UsuarioLista.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-list me-1"></i> Ver Usuarios
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Registro</h6>
        </div>
        <div class="card-body">
            <form id="formUsuario" action="../../Controller/ControladorusuarioADM.php" method="POST">
                <div class="row">
                    
                    <!-- Tipo de Rol -->
                    <div class="col-md-6 mb-3">
                        <label for="tipo_rol" class="form-label" id="label_tipo_rol">Tipo de Rol</label>
                        <select id="tipo_rol" name="tipo_rol" class="form-control border-primary shadow-sm" required>
                            <option value="">Seleccione un Rol</option>
                            <?php foreach ($roles_permitidos as $rol): ?>
                                <option value="<?php echo htmlspecialchars($rol); ?>">
                                    <?php echo htmlspecialchars($rol); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6 mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <div style="position: relative;">
                            <input type="password" id="contrasena" name="contrasena"
                                class="form-control border-primary shadow-sm"
                                placeholder="Ingresa una contraseña segura" minlength="6" required>
                            <button type="button" onclick="togglePassword()"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6c757d;">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Funcionario Asignado -->
                    <div class="col-md-12 mb-3">
                        <label for="id_funcionario" class="form-label">Funcionario Asignado</label>
                        <select id="id_funcionario" name="id_funcionario" class="form-control border-primary shadow-sm"
                            required>
                            <option value="">Selecciona un Funcionario</option>
                            <?php foreach ($funcionarios as $func): ?>
                                <option value="<?php echo $func['IdFuncionario']; ?>">
                                    <?php echo htmlspecialchars($func['NombreFuncionario']) . " (ID: " . $func['IdFuncionario'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Registrar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/parte_inferior_supervisor.php'; ?>
<!-- Dependencias JS -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ===============================
// VALIDACIONES
// ===============================

function validarMin7(texto) {
    return texto.trim().length >= 7;
}

function marcarCampo(input, valido) {
    const label = input.closest(".mb-3").querySelector("label");

    input.classList.remove("input-valid", "input-invalid");
    label.classList.remove("label-valid", "label-invalid");

    if (valido) {
        input.classList.add("input-valid");
        label.classList.add("label-valid");
    } else {
        input.classList.add("input-invalid");
        label.classList.add("label-invalid");
    }
}

// ===============================
// VALIDACIÓN TIEMPO REAL
// ===============================

document.getElementById("tipo_rol").addEventListener("change", function () {
    marcarCampo(this, this.value !== "");
});

document.getElementById("contrasena").addEventListener("input", function () {
    marcarCampo(this, validarMin7(this.value));
});

document.getElementById("id_funcionario").addEventListener("change", function () {
    marcarCampo(this, this.value !== "");
});

// ===============================
// ENVÍO DEL FORMULARIO VIA AJAX
// ===============================

document.getElementById("formUsuario").addEventListener("submit", function(e) {
    e.preventDefault(); // NO RECARGA PÁGINA

    const rol = document.getElementById('tipo_rol');
    const contrasena = document.getElementById('contrasena');
    const funcionario = document.getElementById('id_funcionario');

    let valido = true;

    if (rol.value === "") { marcarCampo(rol, false); valido = false; }
    if (!validarMin7(contrasena.value)) { marcarCampo(contrasena, false); valido = false; }
    if (funcionario.value === "") { marcarCampo(funcionario, false); valido = false; }

    if (!valido) {
        Swal.fire({
            icon: "error",
            title: "Campos inválidos",
            text: "Revisa los campos marcados en rojo"
        });
        return;
    }

    // =================================
    //  ENVÍO AJAX F A L I N A L
    // =================================
    let datos = new FormData(this);

    fetch("../../Controller/ControladorusuarioADM.php", {
        method: "POST",
        body: datos
    })
    .then(resp => resp.json())
    .then(data => {

        if (data.ok) {
            Swal.fire({
                icon: "success",
                title: "Usuario Registrado",
                text: data.mensaje,
                confirmButtonText: "Aceptar"
            }).then(() => {
                document.getElementById("formUsuario").reset();

                [rol, contrasena, funcionario].forEach(i => {
                    i.classList.remove("input-valid", "input-invalid");
                });
            });

        } else {
            Swal.fire({
                icon: "error",
                title: "Error al registrar",
                text: data.mensaje
            });
        }

    })
    .catch(() => {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo contactar con el servidor"
        });
    });

});

</script>
