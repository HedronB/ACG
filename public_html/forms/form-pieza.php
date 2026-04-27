<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$rol       = (int)$_SESSION['rol'];
$empresaId = (int)($_SESSION['empresa'] ?? 0);
$plantaId  = isset($_SESSION['planta']) && $_SESSION['planta'] !== '' ? (int)$_SESSION['planta'] : null;

// Cargar moldes disponibles para dropdown
$sqlMo = "SELECT mo_id, mo_numero, mo_no_pieza FROM moldes WHERE mo_activo = 1";
$pMo = [];
if ($rol !== 1) { $sqlMo .= " AND mo_empresa = :empresa"; $pMo[':empresa'] = $empresaId; }
$sqlMo .= " ORDER BY mo_numero";
$stmtMo = $conn->prepare($sqlMo); $stmtMo->execute($pMo);
$moldes = $stmtMo->fetchAll(PDO::FETCH_ASSOC);

// Cargar resinas disponibles para dropdown
$sqlRe = "SELECT re_id, re_cod_int, re_tipo_resina, re_grado FROM resinas WHERE re_activo = 1";
$pRe = [];
if ($rol !== 1) { $sqlRe .= " AND re_empresa = :empresa"; $pRe[':empresa'] = $empresaId; }
$sqlRe .= " ORDER BY re_tipo_resina, re_grado";
$stmtRe = $conn->prepare($sqlRe); $stmtRe->execute($pRe);
$resinas = $stmtRe->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Formulario de Pieza</title>
  <link rel="icon" type="image/png" href="/imagenes/loguito.png" />
  <link rel="stylesheet" href="/css/acg.estilos.css" />
</head>
<body>
  <header class="header">
    <div class="header-title-group">
      <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo" />
      <h1>Formulario - Pieza</h1>
    </div>
    <div class="header-right">
      <a href="/registros.php" class="back-button">⬅️ Volver</a>
      <?= burgerBtn() ?>
    </div>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>

    <form id="formPieza">
      <div class="form-grid">

        <div class="form-group">
          <label for="codigoProducto">Código de Producto *</label>
          <input type="text" id="codigoProducto" name="codigoProducto" required />
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción</label>
          <input type="text" id="descripcion" name="descripcion" />
        </div>

        <div class="form-group">
          <label for="numeroMolde">Número de Molde *</label>
          <select id="numeroMolde" name="numeroMolde" required>
            <option value="">— Seleccionar molde —</option>
            <?php foreach ($moldes as $mo): ?>
            <option value="<?= htmlspecialchars($mo['mo_numero']) ?>">
              <?= htmlspecialchars($mo['mo_numero'] . ($mo['mo_no_pieza'] ? ' — ' . $mo['mo_no_pieza'] : '')) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <a href="/forms/form-molde.php" style="font-size:.78em;color:#0056b3;margin-top:4px;display:inline-block;">+ Registrar nuevo molde</a>
        </div>

        <div class="form-group">
          <label for="resina">Resina</label>
          <select id="resina" name="resina">
            <option value="">— Seleccionar resina —</option>
            <?php foreach ($resinas as $re): ?>
            <option value="<?= htmlspecialchars($re['re_cod_int']) ?>">
              <?= htmlspecialchars($re['re_cod_int'] . ' — ' . ($re['re_tipo_resina'] ?? '') . ($re['re_grado'] ? ' ' . $re['re_grado'] : '')) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <a href="/forms/form-resina.php" style="font-size:.78em;color:#0056b3;margin-top:4px;display:inline-block;">+ Registrar nueva resina</a>
        </div>

        <div class="form-group">
          <label for="color">Color</label>
          <input type="text" id="color" name="color" />
        </div>

        <div class="form-group">
          <label for="porcMolido">% de Molido</label>
          <input type="number" id="porcMolido" name="porcMolido" step="0.01" min="0" max="100" />
        </div>

        <div class="form-group">
          <label for="espesorPieza">Espesor de Pieza (mm)</label>
          <input type="number" id="espesorPieza" name="espesorPieza" step="0.01" />
        </div>

        <div class="form-group">
          <label for="areaProyectada">Área Proyectada (cm²)</label>
          <input type="number" id="areaProyectada" name="areaProyectada" step="0.01" />
        </div>

        <div class="form-group">
          <label for="tipoEmpaque">Tipo de Empaque</label>
          <input type="text" id="tipoEmpaque" name="tipoEmpaque" />
        </div>

        <div class="form-group">
          <label for="piezasPorCaja">Piezas por Caja</label>
          <input type="number" id="piezasPorCaja" name="piezasPorCaja" step="1" />
        </div>

        <div class="form-group">
          <label for="tamanoCaja">Tamaño de la Caja</label>
          <input type="text" id="tamanoCaja" name="tamanoCaja" />
        </div>

        <div class="form-group">
          <label for="cajasPorTarima">Cajas por Tarima</label>
          <input type="number" id="cajasPorTarima" name="cajasPorTarima" step="1" />
        </div>

      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-limpiar" onclick="limpiarFormulario()">🧹 Limpiar</button>
        <button type="submit" class="btn sqlbtn">⬇️ Pasar a Revisar</button>
      </div>
    </form>

    <h3>Registros Guardados</h3>
    <div class="form-actions">
      <button type="button" class="btn btn-excel" onclick="exportarAExcel()">📥 Exportar a Excel</button>
      <button type="button" class="btn btn-guardar" onclick="guardarTablaEnBD()">💾 Guardar tabla en BD</button>
    </div>
    <div class="registros-section">
      <table class="tabla-registros" id="tablaRegistros">
        <thead>
          <tr>
            <th>Código Producto</th>
            <th>Descripción</th>
            <th>Número Molde</th>
            <th>Resina</th>
            <th>Color</th>
            <th>% Molido</th>
            <th>Espesor</th>
            <th>Área Proy.</th>
            <th>Tipo Empaque</th>
            <th>Pzas/Caja</th>
            <th>Tam. Caja</th>
            <th>Cajas/Tarima</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="cuerpoTabla"></tbody>
      </table>
    </div>
  </div>

  <footer><p>Método ACG</p></footer>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
    let registros = [];
    let registroEditando = null;

    function mostrarMensaje(texto, tipo) {
      const m = document.getElementById("mensaje");
      m.textContent = texto;
      m.className = `mensaje ${tipo}`;
      m.style.display = "block";
      setTimeout(() => m.style.display = "none", 3000);
    }

    function limpiarFormulario() {
      document.getElementById("formPieza").reset();
      registroEditando = null;
    }

    function obtenerDatosFormulario() {
      return {
        codigoProducto: document.getElementById("codigoProducto").value,
        descripcion:    document.getElementById("descripcion").value,
        numeroMolde:    document.getElementById("numeroMolde").value,
        resina:         document.getElementById("resina").value,
        color:          document.getElementById("color").value,
        porcMolido:     document.getElementById("porcMolido").value,
        espesorPieza:   document.getElementById("espesorPieza").value,
        areaProyectada: document.getElementById("areaProyectada").value,
        tipoEmpaque:    document.getElementById("tipoEmpaque").value,
        piezasPorCaja:  document.getElementById("piezasPorCaja").value,
        tamanoCaja:     document.getElementById("tamanoCaja").value,
        cajasPorTarima: document.getElementById("cajasPorTarima").value,
      };
    }

    function cargarDatosFormulario(datos) {
      Object.keys(datos).forEach(key => {
        const el = document.getElementById(key);
        if (el) el.value = datos[key] ?? '';
      });
    }

    document.getElementById("formPieza").addEventListener("submit", function(e) {
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
      } else {
        const _now = new Date(), _p = n => String(n).padStart(2,'0');
        datos.fechaGuardado = `${_now.getFullYear()}-${_p(_now.getMonth()+1)}-${_p(_now.getDate())}T${_p(_now.getHours())}:${_p(_now.getMinutes())}:${_p(_now.getSeconds())}`;
        registros.push(datos);
        mostrarMensaje("Registro agregado a la tabla", "exito");
      }
      actualizarTabla();
      limpiarFormulario();
    });

    function actualizarTabla() {
      const tbody = document.getElementById("cuerpoTabla");
      tbody.innerHTML = "";
      const cols = ["codigoProducto","descripcion","numeroMolde","resina","color",
                    "porcMolido","espesorPieza","areaProyectada","tipoEmpaque",
                    "piezasPorCaja","tamanoCaja","cajasPorTarima"];
      registros.forEach((reg, idx) => {
        const fila = tbody.insertRow();
        cols.forEach(c => { fila.insertCell().textContent = reg[c] ?? ""; });
        const acc = fila.insertCell();
        acc.innerHTML = `<button class="btn-editar" onclick="editarRegistro(${idx})">✏️ Editar</button>
                         <button class="btn-eliminar" onclick="eliminarRegistro(${idx})">✖️ Eliminar</button>`;
      });
    }

    function editarRegistro(idx) {
      registroEditando = idx;
      cargarDatosFormulario(registros[idx]);
      window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function eliminarRegistro(idx) {
      if (confirm("¿Eliminar este registro?")) {
        registros.splice(idx, 1);
        actualizarTabla();
        mostrarMensaje("Registro eliminado", "exito");
      }
    }

    function exportarAExcel() {
      if (!registros.length) { alert("No hay registros para exportar"); return; }
      const headers = ["Código Producto","Descripción","Número Molde","Resina","Color",
                       "% Molido","Espesor","Área Proyectada","Tipo Empaque",
                       "Pzas/Caja","Tam. Caja","Cajas/Tarima"];
      const datos = registros.map(r => [r.codigoProducto,r.descripcion,r.numeroMolde,
        r.resina,r.color,r.porcMolido,r.espesorPieza,r.areaProyectada,r.tipoEmpaque,
        r.piezasPorCaja,r.tamanoCaja,r.cajasPorTarima]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([headers,...datos]), "Piezas");
      XLSX.writeFile(wb, "Datos_Pieza.xlsx");
    }

    function guardarTablaEnBD() {
      if (!registros.length) { mostrarMensaje("No hay registros para guardar", "error"); return; }
      if (!confirm("¿Guardar todos los registros en la base de datos?")) return;
      fetch("/actions/guardar_pieza.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ registros })
      }).then(r => r.json()).then(res => {
        if (res.ok) {
          mostrarMensaje(`Se guardaron ${res.insertados} registros`, "exito");
          registros = []; actualizarTabla();
        } else {
          mostrarMensaje(res.mensaje || "Error al guardar", "error");
        }
      }).catch(() => mostrarMensaje("Error de comunicación", "error"));
    }
  </script>
<?php includeSidebar(); ?>
</body>
</html>
