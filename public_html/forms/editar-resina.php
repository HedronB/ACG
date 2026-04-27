<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$reId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reId) { header('Location: /lists/list-resina.php'); exit(); }

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];

$stmt = $conn->prepare("SELECT * FROM resinas WHERE re_id = ? AND re_activo = 1");
$stmt->execute([$reId]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reg) { header('Location: /lists/list-resina.php?error=no_encontrado'); exit(); }
if ($rol === 2 && (int)$reg['re_empresa'] !== $empresaSesion) { header('Location: /lists/list-resina.php?error=sin_permiso'); exit(); }
if ($rol === 3 && (int)$reg['re_usuario'] !== $usuarioSesion)  { header('Location: /lists/list-resina.php?error=sin_permiso'); exit(); }

$datosReg = [
    'codigoInterno'        => $reg['re_cod_int'],
    'tipoResina'           => $reg['re_tipo_resina'],
    'grado'                => $reg['re_grado'],
    'porcentajeReciclado'  => $reg['re_porc_reciclado'],
    'tempMasaMax'          => $reg['re_temp_masa_max'],
    'tempMasaMin'          => $reg['re_temp_masa_min'],
    'tempRefrigeracionMax' => $reg['re_temp_ref_max'],
    'tempRefrigeracionMin' => $reg['re_temp_ref_min'],
    'tempSecado'           => $reg['re_sec_temp'],
    'tiempoSecado'         => $reg['re_sec_tiempo'],
    'densidad'             => $reg['re_densidad'],
    'factorCorreccion'     => $reg['re_factor_correccion'],
    'carga'                => $reg['re_carga'],
];

$mapaDB = [
    'codigoInterno'=>'re_cod_int','tipoResina'=>'re_tipo_resina','grado'=>'re_grado',
    'porcentajeReciclado'=>'re_porc_reciclado','tempMasaMax'=>'re_temp_masa_max',
    'tempMasaMin'=>'re_temp_masa_min','tempRefrigeracionMax'=>'re_temp_ref_max',
    'tempRefrigeracionMin'=>'re_temp_ref_min','tempSecado'=>'re_sec_temp',
    'tiempoSecado'=>'re_sec_tiempo','densidad'=>'re_densidad',
    'factorCorreccion'=>'re_factor_correccion','carga'=>'re_carga',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Resina</title>
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
      <h1>Editar Resina</h1>
    </div>
    <div class="header-right">
        <a href="/lists/list-resina.php" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
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
          class="btn btn-limpiar" onclick="limpiarFormulario()">🧹 Limpiar
        </button>
        <button type="submit" class="btn sqlbtn">
          💾 Guardar Cambios
        </button>
      </div>
    </form>


  <script>
  const REGISTRO_ID = <?= $reId ?>;
  const DATOS_REG   = <?= json_encode($datosReg, JSON_UNESCAPED_UNICODE) ?>;
  const MAPA_DB     = <?= json_encode($mapaDB) ?>;

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
      .addEventListener("submit", async function(e) {
        e.preventDefault();
        const datos = obtenerDatosFormulario();
        if (!datos.codigoInterno || !datos.tipoResina) {
          mostrarMensaje("Código interno y tipo de resina son obligatorios", "error"); return;
        }
        const payload = { re_id: REGISTRO_ID };
        Object.keys(datos).forEach(k => { if (MAPA_DB[k]) payload[MAPA_DB[k]] = datos[k] || null; });
        try {
          const res = await fetch('/actions/update_resina.php', {
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
        const fila = tbody.insertRow();

        Object.values(registro).forEach((valor) => {
          const celda = fila.insertCell();
          celda.textContent = valor;
        });

        const celdaAcciones = fila.insertCell();
        celdaAcciones.innerHTML = `
            <button class="btn-editar" onclick="editarRegistro(${index})">✏️ Editar</button>
            <button class="btn-eliminar" onclick="eliminarRegistro(${index})">✖️ Eliminar</button>
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
    // Prellenar
    (function precargar() {
      const d = DATOS_REG;
      const campos = ["codigoInterno","tipoResina","grado","porcentajeReciclado",
        "tempMasaMax","tempMasaMin","tempRefrigeracionMax","tempRefrigeracionMin",
        "tempSecado","tiempoSecado","densidad","factorCorreccion","carga"];
      campos.forEach(k => { const el=document.getElementById(k); if(el&&d[k]!=null) el.value=d[k]; });

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