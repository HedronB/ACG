<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$moId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$moId) { header('Location: /lists/list-molde.php'); exit(); }

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];

$stmt = $conn->prepare("SELECT * FROM moldes WHERE mo_id = ? AND mo_activo = 1");
$stmt->execute([$moId]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reg) { header('Location: /lists/list-molde.php?error=no_encontrado'); exit(); }
if ($rol === 2 && (int)$reg['mo_empresa'] !== $empresaSesion) { header('Location: /lists/list-molde.php?error=sin_permiso'); exit(); }
if ($rol === 3 && (int)$reg['mo_usuario'] !== $usuarioSesion)  { header('Location: /lists/list-molde.php?error=sin_permiso'); exit(); }

$datosReg = [
    'numeroPieza'       => $reg['mo_no_pieza'],
    'numeroMolde'       => $reg['mo_numero'],
    'ancho'             => $reg['mo_ancho'],
    'alto'              => $reg['mo_alto'],
    'largo'             => $reg['mo_largo'],
    'placasVoladas'     => $reg['mo_placas_voladas'],
    'anilloCentrador'   => $reg['mo_anillo_centrador'],
    'circuitosAgua'     => $reg['mo_no_circ_agua'],
    'peso'              => $reg['mo_peso'],
    'aperturaMinima'    => $reg['mo_apert_min'],
    'moldeAbierto'      => $reg['mo_abierto'],
    'tipoColada'        => $reg['mo_tipo_colada'],
    'numeroZonas'       => $reg['mo_no_zonas'],
    'numeroCavidades'   => $reg['mo_no_cavidades'],
    'pesoPieza'         => $reg['mo_peso_pieza'],
    'puertosCavidad'    => $reg['mo_puert_cavidad'],
    'numeroColadas'     => $reg['mo_no_coladas'],
    'pesoColada'        => $reg['mo_peso_colada'],
    'pesoDisparo'       => $reg['mo_peso_disparo'],
    'noyos'             => $reg['mo_noyos'],
    'entradasAire'      => $reg['mo_entr_aire'],
    'thermoreguladores' => $reg['mo_thermoreguladores'],
    'valveGates'        => $reg['mo_valve_gates'],
    'tiempoCiclo'       => $reg['mo_tiempo_ciclo'],
    'cavidadesActivas'  => $reg['mo_cavidades_activas'],
];

$mapaDB = [
    'numeroPieza'=>'mo_no_pieza','numeroMolde'=>'mo_numero',
    'ancho'=>'mo_ancho','alto'=>'mo_alto','largo'=>'mo_largo',
    'placasVoladas'=>'mo_placas_voladas','anilloCentrador'=>'mo_anillo_centrador',
    'circuitosAgua'=>'mo_no_circ_agua','peso'=>'mo_peso',
    'aperturaMinima'=>'mo_apert_min','moldeAbierto'=>'mo_abierto',
    'tipoColada'=>'mo_tipo_colada','numeroZonas'=>'mo_no_zonas',
    'numeroCavidades'=>'mo_no_cavidades','pesoPieza'=>'mo_peso_pieza',
    'puertosCavidad'=>'mo_puert_cavidad','numeroColadas'=>'mo_no_coladas',
    'pesoColada'=>'mo_peso_colada','pesoDisparo'=>'mo_peso_disparo',
    'noyos'=>'mo_noyos','entradasAire'=>'mo_entr_aire',
    'thermoreguladores'=>'mo_thermoreguladores','valveGates'=>'mo_valve_gates',
    'tiempoCiclo'=>'mo_tiempo_ciclo','cavidadesActivas'=>'mo_cavidades_activas',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Molde</title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png" />
  <link rel="stylesheet" href="/css/acg.estilos.css" />
</head>

<body>
  <header class="header">
    <div class="header-title-group">
      <img
        src="/imagenes/logo.png"
        alt="Logo ACG"
        class="header-logo" />
      <h1>Editar Molde</h1>
    </div>
    <div class="header-right">
        <a href="/lists/list-molde.php" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>

    <form id="formMolde">
      <div class="form-section-title section-header">Información General</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="numeroPieza">Número de Pieza *</label>
            <input type="text" id="numeroPieza" name="numeroPieza" required />
          </div>

          <div class="form-group">
            <label for="numeroMolde">Número de Molde *</label>
            <input type="text" id="numeroMolde" name="numeroMolde" required />
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Medidas de Molde</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="ancho">Ancho</label>
            <input type="number" id="ancho" name="ancho" step="1" />
          </div>

          <div class="form-group">
            <label for="alto">Alto</label>
            <input type="number" id="alto" name="alto" step="1" />
          </div>

          <div class="form-group">
            <label for="largo">Largo</label>
            <input type="number" id="largo" name="largo" step="1" />
          </div>

          <div class="form-group">
            <label for="placasVoladas">Placas Voladas</label>
            <input type="text" id="placasVoladas" name="placasVoladas" />
          </div>

          <div class="form-group">
            <label for="anilloCentrador">Anillo Centrador</label>
            <input type="text" id="anilloCentrador" name="anilloCentrador" />
          </div>

          <div class="form-group">
            <label for="aperturaMinima">Apertura Mínima Requerida</label>
            <input
              type="number"
              id="aperturaMinima"
              name="aperturaMinima"
              step="0.01" />
          </div>

          <div class="form-group">
            <label for="moldeAbierto">Molde Abierto (Calculado)</label>
            <input
              type="number"
              id="moldeAbierto"
              name="moldeAbierto"
              step="0.01"
              readonly />
          </div>

          <div class="form-group">
            <label for="peso">Peso (Calculado)</label>
            <input type="number" id="peso" name="peso" step="0.01" readonly />
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Refrigeración</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="circuitosAgua">Número de Circuitos de Agua</label>
            <input
              type="number"
              id="circuitosAgua"
              name="circuitosAgua"
              step="1" />
          </div>

          <div class="form-group">
            <label for="thermoreguladores">Thermoreguladores (0-20)</label>
            <input type="number" id="termoreguladores" name="termoreguladores" step="1" min="0" max="20">
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Inyección</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="tipoColada">Tipo de Colada</label>
            <select id="tipoColada" name="tipoColada">
              <option value="Fría">Fría</option>
              <option value="Caliente">Caliente</option>
              <option value="Híbrida">Híbrida</option>
            </select>
          </div>

          <div class="form-group" id="grupoNumeroZonas">
            <label for="numeroZonas">Número de Zonas</label>
            <input type="number" id="numeroZonas" name="numeroZonas" step="1" />
          </div>

          <div class="form-group">
            <label for="numeroCavidades">Número de Cavidades Totales</label>
            <input
              type="number"
              id="numeroCavidades"
              name="numeroCavidades"
              step="1" />
          </div>

          <div class="form-group">
            <label for="cavidadesActivas">Número de Cavidades Activas</label>
            <input
              type="number"
              id="cavidadesActivas"
              name="cavidadesActivas"
              step="1" />
          </div>

          <div class="form-group">
            <label for="pesoPieza">Peso Pieza</label>
            <input type="number" id="pesoPieza" name="pesoPieza" step="0.01" />
          </div>

          <div class="form-group">
            <label for="puertosCavidad">Puertos X Cavidad</label>
            <input
              type="number"
              id="puertosCavidad"
              name="puertosCavidad"
              step="1" />
          </div>

          <div class="form-group">
            <label for="numeroColadas">Número de Coladas</label>
            <input
              type="number"
              id="numeroColadas"
              name="numeroColadas"
              step="1" />
          </div>

          <div class="form-group">
            <label for="pesoColada">Peso Colada</label>
            <input
              type="number"
              id="pesoColada"
              name="pesoColada"
              step="0.01" />
          </div>

          <div class="form-group">
            <label for="pesoDisparo">Peso del Disparo (Calculado)</label>
            <input
              type="number"
              id="pesoDisparo"
              name="pesoDisparo"
              step="0.01"
              readonly />
          </div>
          <div class="form-group" id="grupoValveGates">
            <label for="valveGates">*cambiar titulo*</label>
            <div class="radio-group">
              <label><input type="radio" name="valveGates" value="Éstandar">Éstandar</label>
              <label><input type="radio" name="valveGates" value="Valve Gates">Valve Gates</label>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Otros</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="noyos">Noyos (0-5)</label>
            <input type="number" id="noyos" name="noyos" step="1" min="0" max="5">
          </div>

          <div class="form-group">
            <label for="entradasAire">Entradas de Aire (0-5)</label>
            <input type="number" id="entradasAire" name="entradasAire" step="1" min="0" max="5">
          </div>

          <div class="form-group">
            <label for="tiempoCiclo">Tiempo de Ciclo</label>
            <input
              type="number"
              id="tiempoCiclo"
              name="tiempoCiclo"
              step="0.01" />
          </div>
        </div>
      </div>
      <!-- </div> -->

      <div class="form-actions">
        <button
          type="button"
          class="btn btn-limpiar" onclick="limpiarFormulario()">🧹 Limpiar
        </button>
        <button type="submit" class="btn sqlbtn">
          💾 Guardar Cambios
        </button>
      </div>
    </form>


  <script>
  const REGISTRO_ID = <?= $moId ?>;
  const DATOS_REG   = <?= json_encode($datosReg, JSON_UNESCAPED_UNICODE) ?>;
  const MAPA_DB     = <?= json_encode($mapaDB) ?>;

    let registros = [];
    let registroEditando = null;

    function calcularCampos() {
      const pesoPieza =
        parseFloat(document.getElementById("pesoPieza").value) || 0;
      const numeroCavidades =
        parseFloat(document.getElementById("numeroCavidades").value) || 0;
      const pesoColada =
        parseFloat(document.getElementById("pesoColada").value) || 0;

      const pesoDisparo = pesoPieza * numeroCavidades + pesoColada;
      document.getElementById("pesoDisparo").value =
        pesoDisparo > 0 ? pesoDisparo.toFixed(2) : "";

      const alto = parseFloat(document.getElementById("alto").value) || 0;
      const aperturaMinima =
        parseFloat(document.getElementById("aperturaMinima").value) || 0;
      const moldeAbierto = alto + aperturaMinima;
      document.getElementById("moldeAbierto").value =
        moldeAbierto > 0 ? moldeAbierto.toFixed(2) : "";

      const ancho = parseFloat(document.getElementById("ancho").value) || 0;
      const largo = parseFloat(document.getElementById("largo").value) || 0;
      if (ancho > 0 && alto > 0 && largo > 0) {
        const peso = (ancho * alto * largo) / 1000;
        document.getElementById("peso").value = peso.toFixed(2);
      } else {
        document.getElementById("peso").value = "";
      }
    }

    [
      "pesoPieza",
      "numeroCavidades",
      "pesoColada",
      "alto",
      "aperturaMinima",
      "ancho",
      "largo",
    ].forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        element.addEventListener("input", calcularCampos);
      }
    });

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
      document.getElementById("formMolde").reset();
      registroEditando = null;
      document.querySelector(".btn-primary").textContent = "Guardar Registro";
    }

    function obtenerDatosFormulario() {
      return {
        numeroPieza: document.getElementById("numeroPieza").value,
        numeroMolde: document.getElementById("numeroMolde").value,
        ancho: document.getElementById("ancho").value,
        alto: document.getElementById("alto").value,
        largo: document.getElementById("largo").value,
        placasVoladas: document.getElementById("placasVoladas").value,
        anilloCentrador: document.getElementById("anilloCentrador").value,
        circuitosAgua: document.getElementById("circuitosAgua").value,
        peso: document.getElementById("peso").value,
        aperturaMinima: document.getElementById("aperturaMinima").value,
        moldeAbierto: document.getElementById("moldeAbierto").value,
        tipoColada: document.getElementById("tipoColada").value,
        numeroZonas: document.getElementById("numeroZonas").value,
        numeroCavidades: document.getElementById("numeroCavidades").value,
        pesoPieza: document.getElementById("pesoPieza").value,
        puertosCavidad: document.getElementById("puertosCavidad").value,
        numeroColadas: document.getElementById("numeroColadas").value,
        pesoColada: document.getElementById("pesoColada").value,
        pesoDisparo: document.getElementById("pesoDisparo").value,
        noyos: document.getElementById("noyos").value,
        entradasAire: document.getElementById("entradasAire").value,
        thermoreguladores: document.getElementById("termoreguladores").value,
        valveGates: document.querySelector("input[name='valveGates']:checked")?.value || "",
        tiempoCiclo: document.getElementById("tiempoCiclo").value,
        cavidadesActivas: document.getElementById("cavidadesActivas").value,
      };
    }

    function cargarDatosFormulario(datos) {
      document.getElementById("numeroPieza").value = datos.numeroPieza || "";
      document.getElementById("numeroMolde").value = datos.numeroMolde || "";
      document.getElementById("ancho").value = datos.ancho || "";
      document.getElementById("alto").value = datos.alto || "";
      document.getElementById("largo").value = datos.largo || "";
      document.getElementById("placasVoladas").value =
        datos.placasVoladas || "";
      document.getElementById("anilloCentrador").value =
        datos.anilloCentrador || "";
      document.getElementById("circuitosAgua").value =
        datos.circuitosAgua || "";
      document.getElementById("peso").value = datos.peso || "";
      document.getElementById("aperturaMinima").value =
        datos.aperturaMinima || "";
      document.getElementById("moldeAbierto").value =
        datos.moldeAbierto || "";
      document.getElementById("tipoColada").value = datos.tipoColada || "";
      document.getElementById("numeroZonas").value = datos.numeroZonas || "";
      document.getElementById("numeroCavidades").value =
        datos.numeroCavidades || "";
      document.getElementById("pesoPieza").value = datos.pesoPieza || "";
      document.getElementById("puertosCavidad").value =
        datos.puertosCavidad || "";
      document.getElementById("numeroColadas").value =
        datos.numeroColadas || "";
      document.getElementById("pesoColada").value = datos.pesoColada || "";
      document.getElementById("pesoDisparo").value = datos.pesoDisparo || "";
      document.getElementById("noyos").value = datos.noyos || "";
      document.getElementById("entradasAire").value =
        datos.entradasAire || "";
      document.getElementById("termoreguladores").value = datos.thermoreguladores || "";
      document.getElementById("valveGates").value = datos.valveGates || "";
      document.getElementById("tiempoCiclo").value = datos.tiempoCiclo || "";
      document.getElementById("cavidadesActivas").value =
        datos.cavidadesActivas || "";
    }

    document
      .getElementById("formMolde")
      .addEventListener("submit", async function(e) {
        e.preventDefault();
        const datos = obtenerDatosFormulario();
        if (!datos.numeroPieza || !datos.numeroMolde) {
          mostrarMensaje("Número de pieza y número de molde son obligatorios", "error"); return;
        }
        const payload = { mo_id: REGISTRO_ID };
        Object.keys(datos).forEach(k => { if (MAPA_DB[k]) payload[MAPA_DB[k]] = datos[k] || null; });
        try {
          const res = await fetch('/actions/update_molde.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (data.ok || data.success) {
            mostrarMensaje("Cambios guardados correctamente", "exito");
            setTimeout(() => window.close(), 1500);
          } else { mostrarMensaje(data.error || data.mensaje || "Error al guardar", "error"); }
        } catch(err) { mostrarMensaje("Error de conexión", "error"); }
      });

    function actualizarTabla() {
      const tbody = document.getElementById("cuerpoTabla");
      tbody.innerHTML = "";

      registros.forEach((registro, index) => {
        const fila = document.createElement("tr");

        const celdas = [
          registro.numeroPieza || "",
          registro.numeroMolde || "",
          registro.ancho || "",
          registro.alto || "",
          registro.largo || "",
          registro.placasVoladas || "",
          registro.anilloCentrador || "",
          registro.circuitosAgua || "",
          registro.peso || "",
          registro.aperturaMinima || "",
          registro.moldeAbierto || "",
          registro.tipoColada || "",
          registro.numeroZonas || "",
          registro.numeroCavidades || "",
          registro.pesoPieza || "",
          registro.puertosCavidad || "",
          registro.numeroColadas || "",
          registro.pesoColada || "",
          registro.pesoDisparo || "",
          registro.noyos || "",
          registro.entradasAire || "",
          registro.thermoreguladores || "",
          registro.valveGates || "",
          registro.tiempoCiclo || "",
          registro.cavidadesActivas || "",
        ];

        celdas.forEach((valor) => {
          const td = document.createElement("td");
          td.textContent = valor;
          fila.appendChild(td);
        });

        const tdAcciones = document.createElement("td");
        tdAcciones.innerHTML = `
      <button class="btn-editar" onclick="editarRegistro(${index})">✏️ Editar</button>
      <button class="btn-eliminar" onclick="eliminarRegistro(${index})">✖️ Eliminar</button>
    `;
        fila.appendChild(tdAcciones);

        tbody.appendChild(fila);
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
        "Número Pieza",
        "Número Molde",
        "Ancho",
        "Alto",
        "Largo",
        "Placas Voladas",
        "Anillo Centrador",
        "Circuitos Agua",
        "Peso",
        "Apertura Mínima",
        "Molde Abierto",
        "Tipo Colada",
        "Número Zonas",
        "Número Cavidades",
        "Peso Pieza",
        "Puertos X Cavidad",
        "Número Coladas",
        "Peso Colada",
        "Peso Disparo",
        "Noyos",
        "Entradas Aire",
        "Thermoreguladores",
        "Valve Gates",
        "Tiempo Ciclo",
        "Cavidades Activas",
      ];

      const datos = registros.map((r) => [
        r.numeroPieza,
        r.numeroMolde,
        r.ancho,
        r.alto,
        r.largo,
        r.placasVoladas,
        r.anilloCentrador,
        r.circuitosAgua,
        r.peso,
        r.aperturaMinima,
        r.moldeAbierto,
        r.tipoColada,
        r.numeroZonas,
        r.numeroCavidades,
        r.pesoPieza,
        r.puertosCavidad,
        r.numeroColadas,
        r.pesoColada,
        r.pesoDisparo,
        r.noyos,
        r.entradasAire,
        r.thermoreguladores,
        r.valveGates,
        r.tiempoCiclo,
        r.cavidadesActivas,
      ]);

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...datos]);
      XLSX.utils.book_append_sheet(wb, ws, "Datos Molde");
      XLSX.writeFile(wb, "Datos_Molde.xlsx");
    }

    function guardarTablaMoldeEnBD() {
      if (registros.length === 0) {
        mostrarMensaje("No hay registros en la tabla para guardar", "error");
        return;
      }

      if (!confirm("¿Desea guardar todos los registros de la tabla en la base de datos?")) {
        return;
      }

      fetch("/actions/guardar_molde.php", {
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
    document.addEventListener("DOMContentLoaded", function() {
      const tipoColada = document.getElementById("tipoColada");
      const grupoZonas = document.getElementById("grupoNumeroZonas");
      const numeroZonas = document.getElementById("numeroZonas");
      const grupoValveGates = document.getElementById("grupoValveGates");

      function actualizarVisibilidadCampos() {
        const valor = tipoColada.value;

        if (valor === "Fría") {
          grupoZonas.style.display = "none";
          numeroZonas.value = 0;
        } else {
          grupoZonas.style.display = "block";
        }

        if (valor === "Caliente") {
          grupoValveGates.style.display = "block";
        } else {
          grupoValveGates.style.display = "none";

          const radios = document.querySelectorAll("input[name='valveGates']");
          radios.forEach(r => r.checked = false);
        }
      }

      tipoColada.addEventListener("change", actualizarVisibilidadCampos);

      actualizarVisibilidadCampos();
    });
    document.addEventListener("DOMContentLoaded", function() {
      const tipoColada = document.getElementById("tipoColada");
      const grupoNumeroColadas = document.querySelector("#numeroColadas").parentElement;
      const numeroColadas = document.getElementById("numeroColadas");

      function actualizarNumeroColadas() {
        if (tipoColada.value === "Caliente") {
          grupoNumeroColadas.style.display = "none";
          numeroColadas.value = 0;
        } else {
          grupoNumeroColadas.style.display = "block";
        }
      }

      tipoColada.addEventListener("change", actualizarNumeroColadas);

      actualizarNumeroColadas();
    });
    document.addEventListener("DOMContentLoaded", function() {
      const headers = document.querySelectorAll(".section-header");

      headers.forEach((header) => {
        const content = header.nextElementSibling;

        if (!content || !content.classList.contains("section-content")) return;

        content.style.maxHeight = "0px";

        header.addEventListener("click", function() {
          const isOpen = header.classList.contains("active");

          if (isOpen) {
            header.classList.remove("active");
            content.style.maxHeight = "0px";
          } else {
            header.classList.add("active");
            content.style.maxHeight = content.scrollHeight + "px";
          }
        });
      });
    });
    // Prellenar
    (function precargar() {
      const d = DATOS_REG;
      const campos = [
        "numeroPieza","numeroMolde","ancho","alto","largo","placasVoladas",
        "anilloCentrador","circuitosAgua","peso","aperturaMinima","moldeAbierto",
        "numeroZonas","numeroCavidades","pesoPieza","puertosCavidad",
        "numeroColadas","pesoColada","pesoDisparo","noyos","entradasAire",
        "thermoreguladores","valveGates","tiempoCiclo","cavidadesActivas"
      ];
      campos.forEach(k => { const el=document.getElementById(k); if(el&&d[k]!=null) el.value=d[k]; });
      // Select tipoColada
      if(d.tipoColada){ const s=document.getElementById('tipoColada'); if(s) s.value=d.tipoColada; }

      // Abrir secciones para que los campos sean visibles
      document.querySelectorAll('.section-header').forEach(h => {
        h.classList.add('active');
        const content = h.nextElementSibling;
        if (content && content.classList.contains('section-content')) {
          content.style.maxHeight = content.scrollHeight + 'px';
        }
      });
    })();
  </script>
<?php includeSidebar(); ?>
</body>

</html>