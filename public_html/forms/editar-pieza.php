<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$piId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$piId) { header('Location: /lists/list-pieza.php'); exit(); }

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];

$stmt = $conn->prepare("SELECT * FROM piezas WHERE pi_id = ? AND pi_activo = 1");
$stmt->execute([$piId]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reg) { header('Location: /lists/list-pieza.php?error=no_encontrado'); exit(); }
if ($rol === 2 && (int)$reg['pi_empresa'] !== $empresaSesion) { header('Location: /lists/list-pieza.php?error=sin_permiso'); exit(); }
if ($rol === 3 && (int)$reg['pi_usuario'] !== $usuarioSesion)  { header('Location: /lists/list-pieza.php?error=sin_permiso'); exit(); }

$datosReg = [
    'codigoProducto' => $reg['pi_cod_prod'],
    'numeroMolde'    => $reg['pi_molde'],
    'descripcion'    => $reg['pi_descripcion'],
    'resina'         => $reg['pi_resina'],
    'espesorPieza'   => $reg['pi_espesor'],
    'areaProyectada' => $reg['pi_area_proy'],
    'color'          => $reg['pi_color'],
    'tipoEmpaque'    => $reg['pi_tipo_empaque'],
    'piezas'         => $reg['pi_piezas'],
    'piezasPorCaja'  => $reg['pi_caja_no_pzs'],
    'tamanoCaja'     => $reg['pi_caja_tamano'],
    'cajasPorTarima' => $reg['pi_tarima_no_cajas'],
];

$mapaDB = [
    'codigoProducto'=>'pi_cod_prod','numeroMolde'=>'pi_molde',
    'descripcion'=>'pi_descripcion','resina'=>'pi_resina',
    'espesorPieza'=>'pi_espesor','areaProyectada'=>'pi_area_proy',
    'color'=>'pi_color','tipoEmpaque'=>'pi_tipo_empaque',
    'piezas'=>'pi_piezas','piezasPorCaja'=>'pi_caja_no_pzs',
    'tamanoCaja'=>'pi_caja_tamano','cajasPorTarima'=>'pi_tarima_no_cajas',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Pieza</title>
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
      <h1>Editar Pieza</h1>
    </div>
    <div class="header-right">
        <a href="/lists/list-pieza.php" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
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
          class="btn btn-limpiar" onclick="limpiarFormulario()">🧹 Limpiar
        </button>
        <button type="submit" class="btn sqlbtn">
          💾 Guardar Cambios
        </button>
      </div>
    </form>


  <script>
  const REGISTRO_ID = <?= $piId ?>;
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
      .addEventListener("submit", async function(e) {
        e.preventDefault();
        const datos = obtenerDatosFormulario();
        if (!datos.codigoProducto || !datos.numeroMolde) {
          mostrarMensaje("Código de producto y molde son obligatorios", "error"); return;
        }
        const payload = { pi_id: REGISTRO_ID };
        Object.keys(datos).forEach(k => { if (MAPA_DB[k]) payload[MAPA_DB[k]] = datos[k] || null; });
        try {
          const res = await fetch('/actions/update_pieza.php', {
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

      fetch("/actions/guardar_pieza.php", {
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
      const campos = ["codigoProducto","numeroMolde","descripcion","resina",
        "espesorPieza","areaProyectada","color","tipoEmpaque","piezas",
        "piezasPorCaja","tamanoCaja","cajasPorTarima"];
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