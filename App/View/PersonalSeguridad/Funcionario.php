<?php 
session_start();
require_once __DIR__ . '/../layouts/parte_superior.php'; 

// Cargar sedes para el select
require_once __DIR__ . "/../../Controller/ControladorSede.php"; 
$controladorSede = new ControladorSede();
$sedes = $controladorSede->obtenerSedes();
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-tie me-2"></i>Registrar Funcionario</h1>
        <a href="./FuncionarioLista.php" class="btn btn-primary btn-sm">
            <i class="fas fa-list me-1"></i> Ver Funcionarios
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de registro</h6>
        </div>
        <div class="card-body">
            <form id="formRegistrarFuncionario">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="CargoFuncionario" class="form-label">Cargo *</label>
                        <select id="CargoFuncionario" name="CargoFuncionario" 
                                class="form-control border-primary shadow-sm">
                            <option value="">Seleccione...</option>
                            <option value="Personal Seguridad">Personal Seguridad</option>
                            <option value="Visitante">Visitante</option>
                            <option value="Funcionario Institucion">Funcionario Institución</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="NombreFuncionario" class="form-label">Nombre Completo *</label>
                        <input type="text" id="NombreFuncionario" name="NombreFuncionario" 
                               class="form-control border-primary shadow-sm" 
                               placeholder="Ej: Juan Pérez">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="IdSede" class="form-label">Sede *</label>
                        <select id="IdSede" name="IdSede" 
                                class="form-control border-primary shadow-sm">
                            <option value="">Seleccione...</option>
                            <?php if (!empty($sedes)): ?>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?= htmlspecialchars($sede['IdSede']) ?>">
                                        <?= htmlspecialchars($sede['NombreSede'] ?? $sede['TipoSede']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No hay sedes disponibles</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="TelefonoFuncionario" class="form-label">Teléfono *</label>
                        <input type="text" id="TelefonoFuncionario" name="TelefonoFuncionario" 
                               maxlength="20"
                               class="form-control border-primary shadow-sm" 
                               placeholder="Ej: 3001234567">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="DocumentoFuncionario" class="form-label">Documento *</label>
                        <input type="text" id="DocumentoFuncionario" name="DocumentoFuncionario" 
                               maxlength="20"
                               class="form-control border-primary shadow-sm" 
                               placeholder="Ej: 1002456789">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="CorreoFuncionario" class="form-label">Correo Electrónico *</label>
                    <input type="email" id="CorreoFuncionario" name="CorreoFuncionario" 
                           maxlength="100"
                           class="form-control border-primary shadow-sm" 
                           placeholder="Ej: correo@empresa.com">
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success" id="btnRegistrar">
                        <i class="fas fa-save me-1"></i> Registrar Funcionario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Información Adicional</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i> El código QR se generará automáticamente después de guardar los datos del funcionario.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/parte_inferior.php'; ?>

<!-- jQuery -->
<script src="../../../Public/vendor/jquery/jquery.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    // Validaciones en tiempo real
    $("#DocumentoFuncionario, #TelefonoFuncionario").on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Envío del formulario
    $("#formRegistrarFuncionario").on("submit", function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Validaciones básicas
        const cargo = $("#CargoFuncionario").val();
        const nombre = $("#NombreFuncionario").val().trim();
        const sede = $("#IdSede").val();
        const telefono = $("#TelefonoFuncionario").val().trim();
        const documento = $("#DocumentoFuncionario").val().trim();
        const correo = $("#CorreoFuncionario").val().trim();

        if (!cargo) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor seleccione un cargo',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        if (!nombre) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor ingrese el nombre completo',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        if (!sede) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor seleccione una sede',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        if (!documento || documento.length < 7) {
            Swal.fire({
                icon: 'warning',
                title: 'Documento inválido',
                text: 'El documento debe tener al menos 7 dígitos',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        const btn = $("#btnRegistrar");
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');
        btn.prop('disabled', true);

        const formData = $(this).serialize() + "&accion=registrar";

        console.log("Enviando datos:", formData);

        $.ajax({
            url: "../../Controller/ControladorFuncionarios.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {  
                console.log("Respuesta del servidor:", response);

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro Exitoso!',
                        text: response.message,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $("#formRegistrarFuncionario")[0].reset();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Registrar',
                        text: response.message || response.error || "Error desconocido",
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error("Error AJAX:", error);
                console.log("Estado:", status);
                console.log("Respuesta completa:", xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    html: '<p>No se pudo conectar con el servidor</p><small>Revisa la consola para más detalles</small>',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Aceptar'
                });
            },
            complete: function () {
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });

        return false;
    });
});
</script>