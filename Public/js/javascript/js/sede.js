// ===============================
// VALIDACIONES PERSONALIZADAS
// ===============================
function validarTexto(texto) {
    const regex = /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{1,30}$/;
    return regex.test(texto.trim());
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
// VALIDACIÓN EN TIEMPO REAL
// ===============================
document.getElementById("TipoSede").addEventListener("input", function () {
    marcarCampo(this, validarTexto(this.value));
});

document.getElementById("Ciudad").addEventListener("input", function () {
    marcarCampo(this, validarTexto(this.value));
});

document.getElementById("IdInstitucion").addEventListener("change", function () {
    marcarCampo(this, this.value !== "");
});

// ===============================
// ENVÍO AJAX
// ===============================
document.getElementById("formRegistrarSede").addEventListener("submit", function(e) {
    e.preventDefault();

    const tipoSede = document.getElementById("TipoSede");
    const ciudad = document.getElementById("Ciudad");
    const institucion = document.getElementById("IdInstitucion");

    let valido = true;

    if (!validarTexto(tipoSede.value)) { marcarCampo(tipoSede, false); valido = false; }
    if (!validarTexto(ciudad.value)) { marcarCampo(ciudad, false); valido = false; }
    if (institucion.value === "") { marcarCampo(institucion, false); valido = false; }

    if (!valido) {
        Swal.fire({
            icon: "error",
            title: "Datos inválidos",
            text: "Corrige los campos marcados en rojo."
        });
        return;
    }

    let formData = new FormData();
    formData.append("accion", "registrar");
    formData.append("TipoSede", tipoSede.value);
    formData.append("Ciudad", ciudad.value);
    formData.append("IdInstitucion", institucion.value);

    fetch("../../Controller/ControladorSede.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Sede Registrada",
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });

            this.reset();

            [tipoSede, ciudad, institucion].forEach(c => {
                c.classList.remove("input-valid", "input-invalid");
                const label = c.closest(".mb-3").querySelector("label");
                label.classList.remove("label-valid", "label-invalid");
            });

        } else {
            Swal.fire({ icon: "error", title: "Error", text: data.message });
        }
    });
});