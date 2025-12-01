<?php require_once __DIR__ . '/../layouts/parte_superior.php'; ?>

<div class="container-fluid px-4 py-4">

    <!-- ENCABEZADO -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-building me-2"></i>Lista de Sedes
        </h1>
        <a href="./Sede.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Nueva Sede
        </a>
    </div>

    <!-- ==============================
         📌 CARGAR DATOS
    =============================== -->
    <?php
    require_once __DIR__ . "../../../Core/conexion.php";
    require_once __DIR__ . "../../../Controller/ControladorSede.php";

    $conexionObj = new Conexion();
    $conn = $conexionObj->getConexion();

    $controlador = new ControladorSede();

    // FILTROS DINÁMICOS
    $filtros = [];
    $params = [];

    if (!empty($_GET['tipo'])) {
        $filtros[] = "TipoSede LIKE :tipo";
        $params[':tipo'] = '%' . $_GET['tipo'] . '%';
    }

    if (!empty($_GET['ciudad'])) {
        $filtros[] = "Ciudad LIKE :ciudad";
        $params[':ciudad'] = '%' . $_GET['ciudad'] . '%';
    }

    if (!empty($_GET['institucion'])) {
        $filtros[] = "IdInstitucion = :institucion";
        $params[':institucion'] = intval($_GET['institucion']);
    }

    $where = "";
    if (count($filtros) > 0) {
        $where = "WHERE " . implode(" AND ", $filtros);
    }

    $sql = "SELECT sede.IdSede, sede.TipoSede, sede.Ciudad, sede.IdInstitucion,
            institucion.NombreInstitucion
            FROM sede 
            LEFT JOIN institucion ON sede.IdInstitucion = institucion.IdInstitucion
            $where
            ORDER BY sede.IdSede DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $listaInstituciones = $controlador->obtenerInstituciones();
    ?>

    <!-- ==============================
         🎯 FILTROS
    =============================== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Sedes</h6>
        </div>

        <div class="card-body">
            <form method="get" class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Tipo de Sede</label>
                    <input type="text" name="tipo" class="form-control"
                        value="<?= $_GET['tipo'] ?? '' ?>" placeholder="Ej: Principal">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="ciudad" class="form-control"
                        value="<?= $_GET['ciudad'] ?? '' ?>" placeholder="Ej: Medellín">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Institución</label>
                    <select name="institucion" class="form-control">
                        <option value="">-- Todas --</option>
                        <?php foreach ($listaInstituciones as $inst): ?>
                            <option value="<?= $inst['IdInstitucion']; ?>"
                                <?= (($_GET['institucion'] ?? '') == $inst['IdInstitucion']) ? 'selected' : '' ?>>
                                <?= $inst['NombreInstitucion']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2" type="submit">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="SedeLista.php" class="btn btn-secondary">
                        <i class="fas fa-broom me-1"></i> Limpiar
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- ==============================
         📋 TABLA DE SEDES
    =============================== -->
    <div class="card shadow mb-4">

        <div class="card-header bg-light py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Sedes Registradas</h6>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover">

                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Ciudad</th>
                            <th>Institución</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result): ?>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?= $row['IdSede']; ?></td>
                                    <td><?= htmlspecialchars($row['TipoSede']); ?></td>
                                    <td><?= htmlspecialchars($row['Ciudad']); ?></td>
                                    <td><?= $row['NombreInstitucion'] ?? 'Sin institución'; ?></td>

                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary"
                                            data-toggle="modal"
                                            data-target="#modalEditar"
                                            onclick="cargarEdicion(
                                                <?= $row['IdSede']; ?>,
                                                '<?= htmlspecialchars($row['TipoSede']); ?>',
                                                '<?= htmlspecialchars($row['Ciudad']); ?>',
                                                <?= $row['IdInstitucion']; ?>
                                            )">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2 text-muted"></i>
                                    <p class="text-muted">No se encontraron sedes registradas.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<!-- ==============================
     ✨ MODAL EDITAR SEDE
============================== -->
<div class="modal fade" id="modalEditar" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Editar Sede</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="formEditar">

                    <input type="hidden" id="editId">

                    <div class="mb-3">
                        <label class="form-label">Tipo de Sede</label>
                        <input type="text" id="editTipo" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ciudad</label>
                        <input type="text" id="editCiudad" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Institución</label>
                        <select id="editInstitucion" class="form-control">
                            <?php foreach ($listaInstituciones as $inst): ?>
                                <option value="<?= $inst['IdInstitucion']; ?>">
                                    <?= $inst['NombreInstitucion']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="btnGuardar">Guardar Cambios</button>
            </div>

        </div>
    </div>

</div>

<script>
// =========================
//  📝 Cargar datos en modal
// =========================
function cargarEdicion(id, tipo, ciudad, institucion) {
    $('#editId').val(id);
    $('#editTipo').val(tipo);
    $('#editCiudad').val(ciudad);
    $('#editInstitucion').val(institucion);
}

// =========================
//  💾 Guardar cambios AJAX
// =========================
$('#btnGuardar').click(function () {

    const data = {
        accion: 'editar',
        IdSede: $('#editId').val(),
        TipoSede: $('#editTipo').val(),
        Ciudad: $('#editCiudad').val(),
        IdInstitucion: $('#editInstitucion').val()
    };

    $.ajax({
        url: '../../Controller/ControladorSede.php',
        type: 'POST',
        data: data,
        dataType: 'json',

        success: function (response) {
            $('#modalEditar').modal('hide');
            if (response.success) {
                alert('Sede actualizada correctamente');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },

        error: function () {
            $('#modalEditar').modal('hide');
            alert('Error en la solicitud');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/parte_inferior.php'; ?>
