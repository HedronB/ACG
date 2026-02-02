<?php
session_start();
require_once "protect.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Formulario de Ingeniería de Proceso</title>
  <link rel="icon" type="image/png" href="imagenes/loguito.png" />
  <link rel="stylesheet" href="css/acg.estilos.css" />
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
        src="imagenes/logo.png"
        alt="Logo de la Empresa"
        class="header-logo" />
      <h1>Formulario - Ingeniería de Proceso</h1>
    </div>
    <a href="registros.php" class="back-button">⬅️ Volver</a>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>

    <form id="formProceso">
      <div class="form-grid">
        <div class="form-group">
          <label for="idPieza">ID Pieza *</label>
          <input type="text" id="idPieza" name="idPieza" required />
        </div>

        <div class="form-group">
          <label for="idMolde">ID Molde *</label>
          <input type="text" id="idMolde" name="idMolde" required />
        </div>

        <div class="form-group">
          <label for="idMaquina">ID Máquina *</label>
          <input type="text" id="idMaquina" name="idMaquina" required />
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
          <label for="tempMoldeoZ1">Temp. Moldeo Z1 (°C)</label>
          <input
            type="number"
            id="tempMoldeoZ1"
            name="tempMoldeoZ1"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempMoldeoZ2">Temp. Moldeo Z2 (°C)</label>
          <input
            type="number"
            id="tempMoldeoZ2"
            name="tempMoldeoZ2"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempMoldeoZ3">Temp. Moldeo Z3 (°C)</label>
          <input
            type="number"
            id="tempMoldeoZ3"
            name="tempMoldeoZ3"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempMoldeoZ4">Temp. Moldeo Z4 (°C)</label>
          <input
            type="number"
            id="tempMoldeoZ4"
            name="tempMoldeoZ4"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tempMoldeoBoquilla">Temp. Moldeo Boquilla (°C)</label>
          <input
            type="number"
            id="tempMoldeoBoquilla"
            name="tempMoldeoBoquilla"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="presionInyeccion">Presión Inyección (MPa)</label>
          <input
            type="number"
            id="presionInyeccion"
            name="presionInyeccion"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="velocidadInyeccion">Velocidad Inyección (mm/s)</label>
          <input
            type="number"
            id="velocidadInyeccion"
            name="velocidadInyeccion"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="contrapresion">Contrapresión (MPa)</label>
          <input
            type="number"
            id="contrapresion"
            name="contrapresion"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="tiempoEnfriamiento">Tiempo Enfriamiento (s)</label>
          <input
            type="number"
            id="tiempoEnfriamiento"
            name="tiempoEnfriamiento"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="carreraApertura">Carrera Apertura (mm)</label>
          <input
            type="number"
            id="carreraApertura"
            name="carreraApertura"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="fuerzaCierre">Fuerza Cierre (kN)</label>
          <input
            type="number"
            id="fuerzaCierre"
            name="fuerzaCierre"
            step="0.1" />
        </div>

        <div class="form-group">
          <label for="observaciones">Observaciones</label>
          <textarea id="observaciones" name="observaciones"></textarea>
        </div>

        <div class="form-group">
          <label for="fechaCreacion">Fecha Creación</label>
          <input type="date" id="fechaCreacion" name="fechaCreacion" />
        </div>

        <div class="form-group">
          <label for="usuarioCreador">Usuario Creador</label>
          <input type="text" id="usuarioCreador" name="usuarioCreador" />
        </div>

        <div class="form-group">
          <label for="fechaModificacion">Fecha Modificación</label>
          <input
            type="date"
            id="fechaModificacion"
            name="fechaModificacion"
            readonly />
        </div>

        <div class="form-group">
          <label for="usuarioModificador">Usuario Modificador</label>
          <input
            type="text"
            id="usuarioModificador"
            name="usuarioModificador"
            readonly />
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
    </div>
    <div class="registros-section">
      <table class="tabla-registros" id="tablaRegistros">
        <thead>
          <tr>
            <th>ID Pieza</th>
            <th>ID Molde</th>
            <th>ID Máquina</th>
            <th>Temp. Secado</th>
            <th>Tiempo Secado</th>
            <th>Temp. Z1</th>
            <th>Temp. Z2</th>
            <th>Temp. Z3</th>
            <th>Temp. Z4</th>
            <th>Temp. Boquilla</th>
            <th>Presión Iny.</th>
            <th>Velocidad Iny.</th>
            <th>Contrapresión</th>
            <th>Tiempo Enfr.</th>
            <th>Carrera Apert.</th>
            <th>Fuerza Cierre</th>
            <th>Observaciones</th>
            <th>Fecha Creación</th>
            <th>Usuario Creador</th>
            <th>Fecha Modif.</th>
            <th>Usuario Modif.</th>
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

    window.onload = function() {
      const hoy = new Date().toISOString().split("T")[0];
      document.getElementById("fechaCreacion").value = hoy;
    };

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
      document.getElementById("formProceso").reset();
      const hoy = new Date().toISOString().split("T")[0];
      document.getElementById("fechaCreacion").value = hoy;
      registroEditando = null;
      document.querySelector(".btn-primary").textContent = "Guardar Registro";
    }

    function obtenerDatosFormulario() {
      return {
        idPieza: document.getElementById("idPieza").value,
        idMolde: document.getElementById("idMolde").value,
        idMaquina: document.getElementById("idMaquina").value,
        tempSecado: document.getElementById("tempSecado").value,
        tiempoSecado: document.getElementById("tiempoSecado").value,
        tempMoldeoZ1: document.getElementById("tempMoldeoZ1").value,
        tempMoldeoZ2: document.getElementById("tempMoldeoZ2").value,
        tempMoldeoZ3: document.getElementById("tempMoldeoZ3").value,
        tempMoldeoZ4: document.getElementById("tempMoldeoZ4").value,
        tempMoldeoBoquilla: document.getElementById("tempMoldeoBoquilla").value,
        presionInyeccion: document.getElementById("presionInyeccion").value,
        velocidadInyeccion: document.getElementById("velocidadInyeccion").value,
        contrapresion: document.getElementById("contrapresion").value,
        tiempoEnfriamiento: document.getElementById("tiempoEnfriamiento").value,
        carreraApertura: document.getElementById("carreraApertura").value,
        fuerzaCierre: document.getElementById("fuerzaCierre").value,
        observaciones: document.getElementById("observaciones").value,
        fechaCreacion: document.getElementById("fechaCreacion").value,
        usuarioCreador: document.getElementById("usuarioCreador").value,
        fechaModificacion: document.getElementById("fechaModificacion").value,
        usuarioModificador: document.getElementById("usuarioModificador").value,
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
      .getElementById("formProceso")
      .addEventListener("submit", function(e) {
        e.preventDefault();

        const datos = obtenerDatosFormulario();

        if (registroEditando !== null) {
          const hoy = new Date().toISOString().split("T")[0];
          datos.fechaModificacion = hoy;
          datos.usuarioModificador = datos.usuarioCreador;

          registros[registroEditando] = datos;
          mostrarMensaje("Registro actualizado correctamente", "exito");
          registroEditando = null;
          document.querySelector(".btn-primary").textContent =
            "Guardar Registro";
        } else {
          registros.push(datos);
          mostrarMensaje("Registro guardado correctamente", "exito");
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
        "ID Pieza",
        "ID Molde",
        "ID Máquina",
        "Temp. Secado",
        "Tiempo Secado",
        "Temp. Z1",
        "Temp. Z2",
        "Temp. Z3",
        "Temp. Z4",
        "Temp. Boquilla",
        "Presión Inyección",
        "Velocidad Inyección",
        "Contrapresión",
        "Tiempo Enfriamiento",
        "Carrera Apertura",
        "Fuerza Cierre",
        "Observaciones",
        "Fecha Creación",
        "Usuario Creador",
        "Fecha Modificación",
        "Usuario Modificador",
      ];

      const datos = registros.map((r) => Object.values(r));
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...datos]);
      XLSX.utils.book_append_sheet(wb, ws, "Datos Proceso");
      XLSX.writeFile(wb, "Datos_Proceso.xlsx");
    }
  </script>
</body>

</html>