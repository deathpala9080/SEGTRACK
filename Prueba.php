<?php
session_start();

// Conexión
require_once __DIR__ . '/App/Core/conexion.php';
$db = new Conexion();
$conexion = $db->getConexion();

// Layout superior del dashboard
require_once __DIR__ . '/App/View/layouts/parte_superior.php';

// Obtener datos de sedes
$sql = "SELECT IdSede, TipoSede, Ciudad, Estado FROM sede";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ==========================================================
     CONTENIDO PRINCIPAL DEL DASHBOARD
========================================================== -->
<div class="container-fluid px-4 py-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-building me-2"></i>Lista de Sedes
        </h1>
    </div>

    <div class="card shadow mb-4">

        <!-- Contenido de la card -->
        <div class="card-body">

            <div class="table-responsive">

                <table id="tablaSedes" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>ID</th>
                            <th>Tipo de Sede</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                        </tr>

                        <!-- FILTROS EN EL MISMO THEAD -->
                        <tr>
                            <th><input type="text" class="form-control filtro-input" placeholder="Filtrar ID"></th>
                            <th><input type="text" class="form-control filtro-input" placeholder="Filtrar Tipo"></th>
                            <th><input type="text" class="form-control filtro-input" placeholder="Filtrar Ciudad"></th>
                            <th>
                                <select class="form-control filtro-select">
                                    <option value="">Todos</option>
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($sedes as $sede): ?>
                        <tr>
                            <td><?= htmlspecialchars($sede['IdSede']) ?></td>
                            <td><?= htmlspecialchars($sede['TipoSede']) ?></td>
                            <td><?= htmlspecialchars($sede['Ciudad']) ?></td>
                            <td><?= htmlspecialchars($sede['Estado']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

        </div>
    </div>
</div>

<?php
// Layout inferior del dashboard (scripts ya incluidos allí)
require_once __DIR__ . '/App/View/layouts/parte_inferior.php';
?>

<!-- ==========================================
     SCRIPTS EXCLUSIVOS DE ESTA VISTA
========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // INICIALIZAR DATATABLE
    var table = $('#tablaSedes').DataTable({
        orderCellsTop: true,
        fixedHeader: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        pageLength: 10,
        lengthMenu: [5, 10, 20, 50, 100]
    });

    // FILTROS (SEGUNDA FILA DEL THEAD)
    $('#tablaSedes thead tr:eq(1) th').each(function(i) {

        var input = $(this).find('input, select');

        if (input.length) {
            $(input).on('keyup change', function() {

                let valor = this.value;

                if (table.column(i).search() !== valor) {
                    table.column(i).search(valor).draw();
                }
            });
        }

    });
});
</script>

<style>
.filtro-input, .filtro-select {
    border-radius: 6px;
    padding: 4px 6px;
}

table.dataTable tbody tr:hover {
    background-color: #f0f8ff !important;
}
</style>
