<?php
session_start();
require_once "protect.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Formulario de Pieza</title>
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
      <h1>Formulario - Pieza</h1>
    </div>
    <a href="registros.php" class="back-button">⬅️ Volver</a>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>

    <form id="formPieza">
      <div class="form-grid">
        <div class="form-group">
          <label for="codigoProducto">Código de Producto *</label>
          <input
            type="text"
            id="codigoProducto"
            name="codigoProducto"
            required />
        </div>

        <div class="form-group">
          <label for="numeroMolde">Número de Molde *</label>
          <input type="text" id="numeroMolde" name="numeroMolde" required />
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción</label>
          <input type="text" id="descripcion" name="descripcion" />
        </div>

        <div class="form-group">
          <label for="resina">Resina</label>
          <input type="text" id="resina" name="resina" />
        </div>

        <div class="form-group">
          <label for="espesorPieza">Espesor de Pieza</label>
          <input
            type="number"
            id="espesorPieza"
            name="espesorPieza"
            step="0.01" />
        </div>

        <div class="form-group">
          <label for="areaProyectada">Área Proyectada</label>
          <input
            type="number"
            id="areaProyectada"
            name="areaProyectada"
            step="0.01" />
        </div>

        <div class="form-group">
          <label for="color">Color</label>
          <input type="text" id="color" name="color" />
        </div>

        <div class="form-group">
          <label for="tipoEmpaque">Tipo de Empaque</label>
          <input type="text" id="tipoEmpaque" name="tipoEmpaque" />
        </div>

        <div class="form-group">
          <label for="piezas">Piezas</label>
          <input type="number" id="piezas" name="piezas" step="1" />
        </div>

        <div class="form-group">
          <label for="piezasPorCaja">Piezas por Caja</label>
          <input
            type="number"
            id="piezasPorCaja"
            name="piezasPorCaja"
            step="1" />
        </div>

        <div class="form-group">
          <label for="tamanoCaja">Tamaño de la Caja</label>
          <input type="text" id="tamanoCaja" name="tamanoCaja" />
        </div>

        <div class="form-group">
          <label for="cajasPorTarima">Cajas por Tarima</label>
          <input
            type="number"
            id="cajasPorTarima"
            name="cajasPorTarima"
            step="1" />
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
        class="btn sqlbtn"
        onclick="guardarTablaEnBD()">
        💾 Guardar tabla en BD
      </button>
    </div>
    <div class="registros-section">
      <table class="tabla-registros" id="tablaRegistros">
        <thead>
          <tr>
            <th>Código Producto</th>
            <th>Número Molde</th>
            <th>Descripción</th>
            <th>Resina</th>
            <th>Espesor Pieza</th>
            <th>Área Proyectada</th>
            <th>Color</th>
            <th>Tipo Empaque</th>
            <th>Piezas</th>
            <th>Piezas por Caja</th>
            <th>Tamaño Caja</th>
            <th>Cajas por Tarima</th>
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
      document.getElementById("formPieza").reset();
      registroEditando = null;
      document.querySelector(".btn-primary").textContent = "Guardar Registro";
    }

    function obtenerDatosFormulario() {
      return {
        codigoProducto: document.getElementById("codigoProducto").value,
        numeroMolde: document.getElementById("numeroMolde").value,
        descripcion: document.getElementById("descripcion").value,
        resina: document.getElementById("resina").value,
        espesorPieza: document.getElementById("espesorPieza").value,
        areaProyectada: document.getElementById("areaProyectada").value,
        color: document.getElementById("color").value,
        tipoEmpaque: document.getElementById("tipoEmpaque").value,
        piezas: document.getElementById("piezas").value,
        piezasPorCaja: document.getElementById("piezasPorCaja").value,
        tamanoCaja: document.getElementById("tamanoCaja").value,
        cajasPorTarima: document.getElementById("cajasPorTarima").value,
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
      .getElementById("formPieza")
      .addEventListener("submit", function(e) {
        e.preventDefault();

        const datos = obtenerDatosFormulario();

        if (!datos.codigoProducto || !datos.numeroMolde) {
          mostrarMensaje("Código de producto y número de molde son obligatorios", "error");
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

        const ordenColumnas = [
          "codigoProducto",
          "numeroMolde",
          "descripcion",
          "resina",
          "espesorPieza",
          "areaProyectada",
          "color",
          "tipoEmpaque",
          "piezas",
          "piezasPorCaja",
          "tamanoCaja",
          "cajasPorTarima"
        ];

        ordenColumnas.forEach(campo => {
          const celda = fila.insertCell();
          celda.textContent = registro[campo] ?? "";
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
        "Código Producto",
        "Número Molde",
        "Descripción",
        "Resina",
        "Espesor Pieza",
        "Área Proyectada",
        "Color",
        "Tipo Empaque",
        "Piezas",
        "Piezas por Caja",
        "Tamaño Caja",
        "Cajas por Tarima",
      ];

      const datos = registros.map((r) => Object.values(r));
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...datos]);
      XLSX.utils.book_append_sheet(wb, ws, "Datos Pieza");
      XLSX.writeFile(wb, "Datos_Pieza.xlsx");
    }

    function guardarTablaEnBD() {
      if (registros.length === 0) {
        mostrarMensaje("No hay registros en la tabla para guardar", "error");
        return;
      }

      if (!confirm("¿Desea guardar todos los registros de la tabla en la base de datos?")) {
        return;
      }

      fetch("guardar_pieza.php", {
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