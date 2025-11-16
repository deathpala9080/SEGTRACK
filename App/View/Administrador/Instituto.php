<?php
session_start();

require_once __DIR__ . '/../layouts/parte_superior_supervisor.php';
?>

<style>
    /* Estilos para validación en tiempo real */
    .campo-valido {
        border: 2px solid #10b981 !important;
    }
    
    .campo-invalido {
        border: 2px solid #ef4444 !important;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-university me-2"></i>Registrar Institución</h1>
        <a href="InstitucionLista.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-list me-1"></i> Ver Instituciones
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Formulario de Registro</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>Recuerda estos campos son obligatorios con la regla de que verde es aprobado y rojo reprobado.
            </div>
            
            <form id="formInstituto" method="POST" action="../../Controller/ControladorInstituto.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="NombreInstitucion" class="form-label">Nombre de la Institución</label>
                        <input type="text" id="NombreInstitucion" name="NombreInstitucion" class="form-control shadow-sm" placeholder="Ej: Universidad Nacional" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="Nit_Codigo" class="form-label">NIT / Código</label>
                        <input type="text" id="Nit_Codigo" name="Nit_Codigo" class="form-control shadow-sm" placeholder="Ej: 9001234567" maxlength="10" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="TipoInstitucion" class="form-label">Tipo de Institución</label>
                        <select id="TipoInstitucion" name="TipoInstitucion" class="form-control shadow-sm" required>
                            <option value="">Seleccione tipo...</option>
                            <option value="Universidad">Universidad</option>
                            <option value="Colegio">Colegio</option>
                            <option value="Empresa">Empresa</option>
                            <option value="ONG">ONG</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="EstadoInstitucion" class="form-label">Estado</label>
                        <select id="EstadoInstitucion" name="EstadoInstitucion" class="form-control shadow-sm" required>
                            <option value="Activo" selected>Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Registrar Institución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/parte_inferior_supervisor.php'; ?>

<!-- Dependencias JS -->
<script src="../../../Public/vendor/jquery/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="../../../Public/js/javascript/js/Instituto.js"></script>