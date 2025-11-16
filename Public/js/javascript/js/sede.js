$(document).ready(function () {

    // ===== FUNCIONES DE VALIDACIÓN =====
    
    function marcarInvalido(campo) {
        campo.removeClass("campo-valido").addClass("campo-invalido");
    }

    function marcarValido(campo) {
        campo.removeClass("campo-invalido").addClass("campo-valido");
    }

    function marcarNeutral(campo) {
        campo.removeClass("campo-valido campo-invalido");
    }

    // ===== VALIDACIÓN EN TIEMPO REAL (VERDE/ROJO INMEDIATO) =====

    // 1. NOMBRE DE SEDE: Solo letras, espacios y tildes (mínimo 3 caracteres)
    $("#NombreSede").on("input", function () {
        let campo = $(this);
        let valor = campo.val();
        
        // Eliminar caracteres no permitidos automáticamente
        let valorLimpio = valor.replace(/[^A-Za-zÁÉÍÓÚÑáéíóúñ ]/g, "");
        campo.val(valorLimpio);

        // Validar: solo letras y espacios, mínimo 3 caracteres
        if (valorLimpio.length >= 3 && /^[A-Za-zÁÉÍÓÚÑáéíóúñ ]+$/.test(valorLimpio)) {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // 2. DIRECCIÓN: Mínimo 5 caracteres
    $("#DireccionSede").on("input", function () {
        let campo = $(this);
        let valor = campo.val().trim();

        if (valor.length >= 5) {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // 3. TELÉFONO: Exactamente 10 números
    $("#TelefonoSede").on("input", function () {
        let campo = $(this);
        let valor = campo.val();
        
        // Eliminar todo excepto números
        let valorLimpio = valor.replace(/\D/g, "");
        
        // Limitar a 10 dígitos máximo
        valorLimpio = valorLimpio.substring(0, 10);
        campo.val(valorLimpio);

        // Validar: exactamente 10 números
        if (valorLimpio.length === 10) {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // 4. ID INSTITUCIÓN: Solo números positivos
    $("#IdInstitucion").on("input", function () {
        let campo = $(this);
        let valor = campo.val();
        
        // Eliminar todo excepto números
        let valorLimpio = valor.replace(/\D/g, "");
        campo.val(valorLimpio);

        if (valorLimpio.length > 0 && parseInt(valorLimpio) > 0) {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // 5. ESTADO: Debe seleccionar una opción válida
    $("#EstadoSede").on("change", function () {
        let campo = $(this);
        
        if (campo.val() !== "") {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // ===== ENVÍO DEL FORMULARIO =====
    $("#formRegistrarSede").submit(function (e) {
        e.preventDefault();

        const nombre = $("#NombreSede");
        const direccion = $("#DireccionSede");
        const telefono = $("#TelefonoSede");
        const idInstitucion = $("#IdInstitucion");
        const estado = $("#EstadoSede");

        let errores = [];

        // VALIDACIONES FINALES
        if (nombre.val().length < 3 || !/^[A-Za-zÁÉÍÓÚÑáéíóúñ ]+$/.test(nombre.val())) {
            errores.push("• El nombre debe contener solo letras (mínimo 3 caracteres)");
            marcarInvalido(nombre);
        }

        if (direccion.val().trim().length < 5) {
            errores.push("• La dirección debe tener al menos 5 caracteres");
            marcarInvalido(direccion);
        }

        if (telefono.val().length !== 10) {
            errores.push("• El teléfono debe tener exactamente 10 números");
            marcarInvalido(telefono);
        }

        if (idInstitucion.val() === "" || parseInt(idInstitucion.val()) <= 0) {
            errores.push("• Debe ingresar un ID de institución válido");
            marcarInvalido(idInstitucion);
        }

        if (estado.val() === "") {
            errores.push("• Debe seleccionar el estado de la sede");
            marcarInvalido(estado);
        }

        // Si hay errores, mostrar alerta SweetAlert2 ROJA
        if (errores.length > 0) {
            Swal.fire({
                icon: "error",
                title: "Error de validación",
                html: "<div style='text-align: left;'>" + errores.join("<br>") + "</div>",
                confirmButtonText: "OK",
                confirmButtonColor: "#ef4444",
            });
            return;
        }

        // BOTÓN DE CARGA
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        btn.prop('disabled', true);

        // Enviar por AJAX
        $.ajax({
            url: "../Controller/Sede_institucion_funcionario_usuario/Controladorsede.php",
            type: "POST",
            data: $(this).serialize() + "&accion=registrar", 
            dataType: "json",

            success: function (resp) {
                if (resp.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Registro exitoso",
                        text: resp.message,
                        confirmButtonText: "OK",
                        confirmButtonColor: "#10b981"
                    }).then(() => {
                        $("#formRegistrarSede")[0].reset();
                        // Resetear todos los campos a neutro
                        $("#NombreSede, #DireccionSede, #TelefonoSede, #IdInstitucion, #EstadoSede").each(function() {
                            marcarNeutral($(this));
                        });
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error de validación",
                        text: resp.message,
                        confirmButtonText: "OK",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },

            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX: ", textStatus, errorThrown, jqXHR.responseText);
                Swal.fire({
                    icon: "error",
                    title: "Error de conexión",
                    text: "No se pudo conectar con el servidor. Intente nuevamente.",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#ef4444"
                });
            },

            complete: function () {
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });

});