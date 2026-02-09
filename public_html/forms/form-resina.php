<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Formulario de Resina</title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png" />
  <link rel="stylesheet" href="/css/acg.estilos.css" />
  <style>
    .header {
      justify-content: space-between;
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="header-title-group">
      <img
        src="/imagenes/logo.png"
        alt="Logo ACG"
        class="header-logo" />
      <h1>Formulario - Resina</h1>
    </div>
    <a href="/registros.php" class="back-button">⬅️ Volver</a>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>

    <form id="formResina">
      <div class="form-grid">
        <div class="form-group">
          <label for="codigoInterno">Código Interno *</label>
          <input
            type="text"
            id="codigoInterno"
            name="codigoInterno"
            required />
        </div>

        <div class="form-group">
          <label for="tipoResina">Tipo de Resina *</label>
          <input type="text" id="tipoResina" name="tipoResina" required />
        </div>

        <div class="form-group">
          <label for="grado">Grado</label>
          <input type="text" id="grado" name="grado" />
        </div>

        <div class="form-group">
          <label for="porcentajeReciclado">% de Reciclado</label>
          <input
            type="number"
            id="porcentajeReciclado"
            name="porcentajeReciclado"
            step="0.01"
            min="0"
            max="100" />
        </div>

        <div class="form-group">
          <label for="tempMasaMax">Temp. Masa Máxima (°C)</label>
          <input
            type="number"
            id="tempMasaMax"
            name="tempMasaMax"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempMasaMin">Temp. Masa Mínima (°C)</label>
          <input
            type="number"
            id="tempMasaMin"
            name="tempMasaMin"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempRefrigeracionMax">Temp. Refrigeración Máxima (°C)</label>
          <input
            type="number"
            id="tempRefrigeracionMax"
            name="tempRefrigeracionMax"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempRefrigeracionMin">Temp. Refrigeración Mínima (°C)</label>
          <input
            type="number"
            id="tempRefrigeracionMin"
            name="tempRefrigeracionMin"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempSecado">Temp. Secado (°C)</label>
          <input type="number" id="tempSecado" name="tempSecado" step="0.1" />
        </div>

        <div class="form-group">
          <label for="tiempoSecado">Tiempo Secado (Hrs)</label>
          <input
            type="number"
            id="tiempoSecado"
            name="tiempoSecado"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="densidad">Densidad (g/cm³)</label>
          <input type="number" id="densidad" name="densidad" step="0.001" />
        </div>

        <div class="form-group">
          <label for="factorCorreccion">Factor de Corrección</label>
          <input
            type="number"
            id="factorCorreccion"
            name="factorCorreccion"
            step="0.001" />
        </div>

        <div class="form-group">
          <label for="carga">Carga (%)</label>
          <input
            type="number"
            id="carga"
            name="carga"
            step="0.01"
            min="0"
            max="100" />
        </div>
      </div>

      <div class="form-actions">
        <button
          type="button"
          class="btn btn-secondary"
          onclick="limpiarFormulario()">
          Limpiar
        </button>
        <button type="submit" class="btn btn-primary">
          Guardar Registro
        </button>
      </div>
    </form>

    <h3>Registros Guardados</h3>
    <div class="form-actions">
      <button
        type="button"
        class="btn btn-secondary"
        onclick="exportarAExcel()">
        📥 Exportar a Excel
      </button>

      <button
        type="button"
        class="btn btn-primary"
        onclick="guardarTablaResinaEnBD()">
        💾 Guardar tabla en BD
      </button>
    </div>
    <div class="registros-section">
      <table class="tabla-registros" id="tablaRegistros">
        <thead>
          <tr>
            <th>Código Interno</th>
            <th>Tipo Resina</th>
            <th>Grado</th>
            <th>% Reciclado</th>
            <th>Temp. Masa Máx</th>
            <th>Temp. Masa Mín</th>
            <th>Temp. Refrig. Máx</th>
            <th>Temp. Refrig. Mín</th>
            <th>Temp. Secado</th>
            <th>Tiempo Secado</th>
            <th>Densidad</th>
            <th>Factor Corrección</th>
            <th>Carga (%)</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="cuerpoTabla"></tbody>
      </table>
    </div>
  </div>

  <footer>
    <p>Método ACG</p>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
    let registros = [];
    let registroEditando = null;

    function mostrarMensaje(texto, tipo) {
      const mensaje = document.getElementById("mensaje");
      mensaje.textContent = texto;
      mensaje.className = `mensaje ${tipo}`;
      mensaje.style.display = "block";
      setTimeout(() => {
        mensaje.style.display = "none";
      }, 3000);
    }

    function limpiarFormulario() {
      document.getElementById("formResina").reset();
      registroEditando = null;
      document.querySelector(".btn-primary").textContent = "Guardar Registro";
    }

    function obtenerDatosFormulario() {
      return {
        codigoInterno: document.getElementById("codigoInterno").value,
        tipoResina: document.getElementById("tipoResina").value,
        grado: document.getElementById("grado").value,
        porcentajeReciclado: document.getElementById("porcentajeReciclado")
          .value,
        tempMasaMax: document.getElementById("tempMasaMax").value,
        tempMasaMin: document.getElementById("tempMasaMin").value,
        tempRefrigeracionMax: document.getElementById("tempRefrigeracionMax")
          .value,
        tempRefrigeracionMin: document.getElementById("tempRefrigeracionMin")
          .value,
        tempSecado: document.getElementById("tempSecado").value,
        tiempoSecado: document.getElementById("tiempoSecado").value,
        densidad: document.getElementById("densidad").value,
        factorCorreccion: document.getElementById("factorCorreccion").value,
        carga: document.getElementById("carga").value,
      };
    }

    function cargarDatosFormulario(datos) {
      Object.keys(datos).forEach((key) => {
        const elemento = document.getElementById(key);
        if (elemento) {
          elemento.value = datos[key];
        }
      });
    }

    document
      .getElementById("formResina")
      .addEventListener("submit", function(e) {
        e.preventDefault();

        const datos = obtenerDatosFormulario();

        if (!datos.codigoInterno || !datos.tipoResina) {
          mostrarMensaje("Código interno y tipo de resina son obligatorios", "error");
          return;
        }

        if (registroEditando !== null) {
          registros[registroEditando] = datos;
          mostrarMensaje("Registro actualizado en la tabla", "exito");
          registroEditando = null;
          document.querySelector(".btn-primary").textContent = "Guardar Registro";
        } else {
          registros.push(datos);
          mostrarMensaje("Registro agregado a la tabla", "exito");
        }

        actualizarTabla();
        limpiarFormulario();
      });


    function actualizarTabla() {
      const tbody = document.getElementById("cuerpoTabla");
      tbody.innerHTML = "";

      registros.forEach((registro, index) => {
        const fila = tbody.insertRow();

        Object.values(registro).forEach((valor) => {
          const celda = fila.insertCell();
          celda.textContent = valor;
        });

        const celdaAcciones = fila.insertCell();
        celdaAcciones.innerHTML = `
            <button class="btn-editar" onclick="editarRegistro(${index})">Editar</button>
            <button class="btn-eliminar" onclick="eliminarRegistro(${index})">Eliminar</button>
          `;
      });
    }

    function editarRegistro(index) {
      registroEditando = index;
      cargarDatosFormulario(registros[index]);
      document.querySelector(".btn-primary").textContent =
        "Actualizar Registro";
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    }

    function eliminarRegistro(index) {
      if (confirm("¿Está seguro de eliminar este registro?")) {
        registros.splice(index, 1);
        actualizarTabla();
        mostrarMensaje("Registro eliminado correctamente", "exito");
      }
    }

    function exportarAExcel() {
      if (registros.length === 0) {
        alert("No hay registros para exportar");
        return;
      }

      const headers = [
        "Código Interno",
        "Tipo Resina",
        "Grado",
        "% Reciclado",
        "Temp. Masa Máxima",
        "Temp. Masa Mínima",
        "Temp. Refrigeración Máxima",
        "Temp. Refrigeración Mínima",
        "Temp. Secado",
        "Tiempo Secado",
        "Densidad",
        "Factor de Corrección",
        "Carga (%)",
      ];

      const datos = registros.map((r) => Object.values(r));
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...datos]);
      XLSX.utils.book_append_sheet(wb, ws, "Datos Resina");
      XLSX.writeFile(wb, "Datos_Resina.xlsx");
    }

    function guardarTablaResinaEnBD() {
      if (registros.length === 0) {
        mostrarMensaje("No hay registros en la tabla para guardar", "error");
        return;
      }

      if (!confirm("¿Desea guardar todos los registros de la tabla en la base de datos?")) {
        return;
      }

      fetch("/actions/guardar_resina.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            registros: registros
          }),
        })
        .then((response) => response.json())
        .then((res) => {
          if (res.ok) {
            mostrarMensaje(
              `Se guardaron ${res.insertados} registros en la base de datos`,
              "exito"
            );

            registros = [];
            actualizarTabla();
          } else {
            mostrarMensaje(res.mensaje || "Error al guardar en la BD", "error");
          }
        })
        .catch((error) => {
          console.error(error);
          mostrarMensaje("Error de comunicación con el servidor", "error");
        });
    }
  </script>
</body>

</html>