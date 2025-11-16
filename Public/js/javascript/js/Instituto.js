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

    // 1. NOMBRE DE INSTITUCIÓN: Solo letras, espacios y tildes (mínimo 3 caracteres)
    $("#NombreInstitucion").on("input", function () {
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

    // 2. NIT/CÓDIGO: Exactamente 10 números
    $("#Nit_Codigo").on("input", function () {
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

    // 3. TIPO DE INSTITUCIÓN: Debe seleccionar una opción válida
    $("#TipoInstitucion").on("change", function () {
        let campo = $(this);
        
        if (campo.val() !== "") {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // 4. ESTADO: Debe seleccionar una opción válida
    $("#EstadoInstitucion").on("change", function () {
        let campo = $(this);
        
        if (campo.val() !== "") {
            marcarValido(campo); // ✅ VERDE
        } else {
            marcarInvalido(campo); // ❌ ROJO
        }
    });

    // ===== ENVÍO DEL FORMULARIO =====
    $("#formInstituto").submit(function (e) {
        e.preventDefault();

        const nombre = $("#NombreInstitucion");
        const nit = $("#Nit_Codigo");
        const tipo = $("#TipoInstitucion");
        const estado = $("#EstadoInstitucion");

        let errores = [];

        // VALIDACIONES FINALES
        if (nombre.val().length < 3 || !/^[A-Za-zÁÉÍÓÚÑáéíóúñ ]+$/.test(nombre.val())) {
            errores.push("• El nombre debe contener solo letras (mínimo 3 caracteres)");
            marcarInvalido(nombre);
        }

        if (nit.val().length !== 10) {
            errores.push("• El NIT debe tener exactamente 10 números");
            marcarInvalido(nit);
        }

        if (tipo.val() === "") {
            errores.push("• Debe seleccionar un tipo de institución");
            marcarInvalido(tipo);
        }

        if (estado.val() === "") {
            errores.push("• Debe seleccionar el estado de la institución");
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
                customClass: {
                    popup: 'swal-error-popup'
                }
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
            url: $(this).attr('action'),
            type: "POST",
            data: $(this).serialize(),
            
            success: function (data) {
                console.log("Respuesta del servidor:", data);

                if (data.includes("✅") || data.includes("correctamente") || data.includes("éxito")) {
                    Swal.fire({
                        icon: "success",
                        title: "Registro exitoso",
                        text: 'La institución ha sido registrada correctamente.', 
                        confirmButtonText: "OK",
                        confirmButtonColor: "#10b981"
                    }).then(() => {
                        $("#formInstituto")[0].reset();
                        // Resetear todos los campos a neutro
                        $("#NombreInstitucion, #Nit_Codigo, #TipoInstitucion, #EstadoInstitucion").each(function() {
                            marcarNeutral($(this));
                        });
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error en el registro",
                        text: data, 
                        confirmButtonText: "OK",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error de conexión",
                    text: "No se pudo contactar con el servidor. Intente nuevamente.",
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