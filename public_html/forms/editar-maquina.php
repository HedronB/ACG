<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';
require_once BASE_PATH . '/app/helpers/LayoutHelper.php';

$maId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$maId) { header('Location: /lists/list-maquina.php'); exit(); }

$usuarioSesion = (int)$_SESSION['id'];
$empresaSesion = (int)$_SESSION['empresa'];
$rol           = (int)$_SESSION['rol'];

$stmt = $conn->prepare("SELECT * FROM maquinas WHERE ma_id = ? AND ma_activo = 1");
$stmt->execute([$maId]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reg) { header('Location: /lists/list-maquina.php?error=no_encontrado'); exit(); }
if ($rol === 2 && (int)$reg['ma_empresa'] !== $empresaSesion) { header('Location: /lists/list-maquina.php?error=sin_permiso'); exit(); }
if ($rol === 3 && (int)$reg['ma_usuario'] !== $usuarioSesion) { header('Location: /lists/list-maquina.php?error=sin_permiso'); exit(); }

// Mapeo columnas DB → id de campo en el formulario
$datosReg = [
    'marca'                 => $reg['ma_marca'],
    'modelo'                => $reg['ma_modelo'],
    'fechaFabricacion'      => $reg['ma_fecha_fabr'] ? date('Y-m-d', strtotime($reg['ma_fecha_fabr'])) : '',
    'ubicacion'             => $reg['ma_ubicacion'],
    'tipoMaquina'           => $reg['ma_tipo'],
    'dimAncho'              => $reg['ma_ancho'],
    'dimLargo'              => $reg['ma_largo'],
    'dimAlto'               => $reg['ma_alto'],
    'peso'                  => $reg['ma_peso'],
    'tamanoTanqueAceite'    => $reg['ma_vol_tanq_aceite'],
    'tonelaje'              => $reg['ma_tonelaje'],
    'distanciaBarras'       => $reg['ma_dist_barras'],
    'tamanoPlatina'         => $reg['ma_tam_platina'],
    'anilloCentrador'       => $reg['ma_anillo_centr'],
    'alturaMaxMolde'        => $reg['ma_alt_max_molde'],
    'aperturaMax'           => $reg['ma_apert_max'],
    'alturaMinMolde'        => $reg['ma_alt_min_molde'],
    'tipoSujecion'          => $reg['ma_tipo_sujecion'],
    'moldeChico'            => $reg['ma_molde_chico'],
    'patronBotado'          => $reg['ma_botado_patron'],
    'fuerzaBotado'          => $reg['ma_botado_fuerza'],
    'carreraBotado'         => $reg['ma_botado_carrera'],
    'tamanoUnidadInyeccion' => $reg['ma_tam_unid_inyec'],
    'volumenInyeccion'      => $reg['ma_vol_inyec'],
    'diametroHusillo'       => $reg['ma_diam_husillo'],
    'cargaMax'              => $reg['ma_carga_max'],
    'ld'                    => $reg['ma_ld'],
    'tipoHusillo'           => $reg['ma_tipo_husillo'],
    'maxPresionInyeccion'   => $reg['ma_max_pres_inyec'],
    'maxContrapresion'      => $reg['ma_max_contrapres'],
    'maxRevoluciones'       => $reg['ma_max_revol'],
    'maxVelocidadInyeccion' => $reg['ma_max_vel_inyec'],
    'valvulaShutOff'        => $reg['ma_valv_shut_off'],
    'cargaVuelo'            => $reg['ma_carga_vuelo'],
    'fuerzaApoyo'           => $reg['ma_fuerza_apoyo'],
    'noyos'                 => $reg['ma_noyos'],
    'numValvulasAire'       => $reg['ma_no_valv_aire'],
    'tipoValvulasAire'      => $reg['ma_tipo_valv_aire'],
    'secador'               => $reg['ma_secador'],
    'termoreguladores'      => $reg['ma_termoreguladores'],
    'cargador'              => $reg['ma_cargador'],
    'canalCaliente'         => $reg['ma_canal_caliente'],
    'robot'                 => $reg['ma_robot'],
    'acumuladorHidraulico'  => $reg['ma_acumul_hidr'],
    'voltaje'               => $reg['ma_voltaje'],
    'calentamiento'         => $reg['ma_calentamiento'],
    'tamanoMotor1'          => $reg['ma_tam_motor_1'],
    'tamanoMotor2'          => $reg['ma_tam_motor_2'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar Máquina</title>
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
      <h1>Editar Máquina</h1>
    </div>
    <div class="header-right">
        <a href="/lists/list-maquina.php" class="back-button">⬅️ Volver</a>
        <?= burgerBtn() ?>
    </div>
  </header>

  <div class="form-container">
    <div id="mensaje" class="mensaje"></div>
    <form id="formMaquina">
      <div class="form-section-title section-header">Información General</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="noMaq">No. Máquina</label>
            <input type="text" id="noMaq" name="noMaq" required />
          </div>
          <div class="form-group">
            <label for="marca">Marca *</label>
            <input type="text" id="marca" name="marca" required />
          </div>
          <div class="form-group">
            <label for="modelo">Modelo *</label>
            <input type="text" id="modelo" name="modelo" required />
          </div>
          <div class="form-group">
            <label for="fechaFabricacion">Fecha Fabricación</label>
            <input type="date" id="fechaFabricacion" name="fechaFabricacion" />
          </div>
          <div class="form-group">
            <label for="ubicacion">Ubicación</label>
            <input type="text" id="ubicacion" name="ubicacion" />
          </div>
          <div class="form-group">
            <label for="tipoMaquina">Tipo de Máquina</label>
            <div class="radio-group">
              <label><input type="radio" name="tipoMaquina" value="Hidráulica">Hidráulica</label>
              <label><input type="radio" name="tipoMaquina" value="Eléctrica">Eléctrica</label>
              </div>
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Dimensiones y Peso</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="dimAncho">Ancho (mm)</label>
            <input type="number" id="dimAncho" name="dimAncho" step="0.01" />
          </div>
          <div class="form-group">
            <label for="dimLargo">Largo (mm)</label>
            <input type="number" id="dimLargo" name="dimLargo" step="0.01" />
          </div>
          <div class="form-group">
            <label for="dimAlto">Alto (mm)</label>
            <input type="number" id="dimAlto" name="dimAlto" step="0.01" />
          </div>
          <div class="form-group">
            <label for="peso">Peso (kg)</label>
            <input type="number" id="peso" name="peso" step="0.01" />
          </div>
          <div class="form-group">
            <label for="tamanoTanqueAceite">Tamaño Tanque Aceite (L)</label>
            <input
              type="number"
              id="tamanoTanqueAceite"
              name="tamanoTanqueAceite"
              step="0.01" />
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Prensa</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="tonelaje">Tonelaje (ton)</label>
            <input type="number" id="tonelaje" name="tonelaje" step="0.01" />
          </div>
          <div class="form-group">
            <label for="distanciaBarras">Distancia Entre Barras (mm)</label>
            <input
              type="number"
              id="distanciaBarras"
              name="distanciaBarras"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="tamanoPlatina">Tamaño de la Platina (mm)</label>
            <input type="text" id="tamanoPlatina" name="tamanoPlatina" />
          </div>
          <div class="form-group">
            <label for="anilloCentrador">Anillo Centrador</label>
            <input type="text" id="anilloCentrador" name="anilloCentrador" />
          </div>
          <div class="form-group">
            <label for="alturaMaxMolde">Altura Máxima Molde (mm)</label>
            <input
              type="number"
              id="alturaMaxMolde"
              name="alturaMaxMolde"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="alturaMinMolde">Altura Mínima Molde (mm)</label>
            <input
              type="number"
              id="alturaMinMolde"
              name="alturaMinMolde"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="aperturaMax">Apertura Máxima (mm)</label>
            <input
              type="number"
              id="aperturaMax"
              name="aperturaMax"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="tipoSujecion">Tipo de Sujeción</label>
            <div class="radio-group">
              <label><input type="radio" name="tipoSujecion" value="Tornillo">Tornillo</label>
              <label><input type="radio" name="tipoSujecion" value="Ranura">Ranura</label>
            </div>
            </div>
          <!-- <div class="form-group">
            <label for="moldeChico">Molde Más Chico</label>
            <input type="text" id="moldeChico" name="moldeChico" />
          </div> -->
        </div>
      </div>
      
      <div class="form-section-title section-header">Sistema de Botado</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="patronBotado">Patrón de Botado</label>
            <input type="text" id="patronBotado" name="patronBotado" />
          </div>
          <div class="form-group">
            <label for="fuerzaBotado">Fuerza de Botado (kN)</label>
            <input
              type="number"
              id="fuerzaBotado"
              name="fuerzaBotado"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="carreraBotado">Carrera de Botado (mm)</label>
            <input
              type="number"
              id="carreraBotado"
              name="carreraBotado"
              step="0.01" />
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Unidad de Inyección</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="tamanoUnidadInyeccion">Tamaño Unidad Inyección</label>
            <input
              type="text"
              id="tamanoUnidadInyeccion"
              name="tamanoUnidadInyeccion" />
          </div>
          <div class="form-group">
            <label for="volumenInyeccion">Vol. de Inyección (cm³) (Calculado)</label>
            <input type="number" id="volumenInyeccion" name="volumenInyeccion" step="0.01" readonly />
          </div>
          <div class="form-group">
            <label for="diametroHusillo">Diámetro Husillo (mm)</label>
            <input
              type="number"
              id="diametroHusillo"
              name="diametroHusillo"
              step="1" />
          </div>
          <div class="form-group">
            <label for="cargaMax">Carga Máxima (mm)</label>
            <input type="number" id="cargaMax" name="cargaMax" step="1" />
          </div>
          <div class="form-group">
            <label for="recorrido">Recorrido</label>
            <input
              type="number"
              id="recorrido"
              name="recorrido"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="ld">L/D</label>
            <input type="text" id="ld" name="ld" />
          </div>
          <div class="form-group">
            <label for="tipoHusillo">Tipo de Husillo</label>
            <select id="tipoHusillo" name="tipoHusillo">
              <option value="Éstandar">Éstandar</option>
              <option value="Tratado">Tratado</option>
              <option value="Bimetálico">Bimetálico</option>
            </select>
          </div>
          <div class="form-group">
            <label for="maxPresionInyeccion">Máx. Presión Inyección</label>
            <input
              type="number"
              id="maxPresionInyeccion"
              name="maxPresionInyeccion"
              step="0.01" />
          </div>


          <!-- Añadir el select con la unidad de medida (BAR / MPa) -->


          <div class="form-group">
            <label for="maxContrapresion">Máx. Contrapresión</label>
            <input
              type="number"
              id="maxContrapresion"
              name="maxContrapresion"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="maxRevoluciones">Capacidad de carga (kg/hr de PS)</label>
            <input
              type="number"
              id="maxRevoluciones"
              name="maxRevoluciones"
              step="1" />
          </div>
          <div class="form-group">
            <label for="maxVelocidadInyeccion">Máx. Velocidad Inyección (mm/s)</label>
            <input
              type="number"
              id="maxVelocidadInyeccion"
              name="maxVelocidadInyeccion"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="valvulaShutOff">Válvula Shut-Off</label>
            <div class="radio-group">
              <label><input type="radio" name="valvulaShutOff" value="Si">Si</label>
              <label><input type="radio" name="valvulaShutOff" value="No">No</label>
            </div>
          </div>
          <div class="form-group">
            <label for="cargaVuelo">Carga al Vuelo</label>
            <div class="radio-group">
              <label><input type="radio" name="cargaVuelo" value="Si">Si</label>
              <label><input type="radio" name="cargaVuelo" value="No">No</label>
            </div>
          </div>
          <div class="form-group">
            <label for="fuerzaApoyo">Fuerza de Apoyo (kN)</label>
            <input
              type="number"
              id="fuerzaApoyo"
              name="fuerzaApoyo"
              step="0.01" />
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Equipamiento Adicional</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="noyos">Noyos (0-20)</label>
            <input type="number" id="noyos" name="noyos" step="1" min="0" max="20">
          </div>
          <div class="form-group">
            <label for="numValvulasAire">Número Válvulas Aire</label>
            <input
              type="number"
              id="numValvulasAire"
              name="numValvulasAire"
              step="1" min="0" max="5">
          </div>
          <!-- <div class="form-group">
            <label for="tipoValvulasAire">Tipo Válvulas Aire</label>
            <input type="text" id="tipoValvulasAire" name="tipoValvulasAire" />
          </div> -->
          <div class="form-group">
            <label for="secador">Secador</label>
            <select id="secador" name="secador">
              <option value="No">No</option>
              <option value="Secador">Secador</option>
              <option value="Dehumificador">Dehumificador</option>
            </select>
          </div>
          <div class="form-group">
            <label for="termoreguladores">Termoreguladores (0-20)</label>
            <input type="number" id="termoreguladores" name="termoreguladores" step="1" min="0" max="20">
          </div>
          <div class="form-group">
            <label for="cargador">Cargador</label>
            <div class="radio-group">
              <label><input type="radio" name="cargador" value="Si">Si</label>
              <label><input type="radio" name="cargador" value="No">No</label>
            </div>
          </div>
          <div class="form-group">
            <label for="canalCaliente">Canal Caliente (#zonas)</label>
            <input type="number" id="canalCaliente" name="canalCaliente" step="1">
          </div>
          <div class="form-group">
            <label for="robot">Robot</label>
            <select id="robot" name="robot" />
              <option value="No">No</option>
              <option value="Cartesiano">Cartesiano</option>
              <option value="Brazo Libre">Brazo Libre</option>
            </select>
          </div>
          <div class="form-group">
            <label for="acumuladorHidraulico">Acumulador Hidráulico</label>
            <div class="radio-group">
              <label><input type="radio" name="acumuladorHidraulico" value="Si">Si</label>
              <label><input type="radio" name="acumuladorHidraulico" value="No">No</label>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section-title section-header">Especificaciones Eléctricas</div>
      <div class="section-content">
        <div class="form-grid">
          <div class="form-group">
            <label for="voltaje">Voltaje (V)</label>
            <input type="text" id="voltaje" name="voltaje" />
          </div>
          <div class="form-group">
            <label for="calentamiento">Calentamiento (kW)</label>
            <input
              type="number"
              id="calentamiento"
              name="calentamiento"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="tamanoMotor1">Tamaño Motor 1 (kW)</label>
            <input
              type="number"
              id="tamanoMotor1"
              name="tamanoMotor1"
              step="0.01" />
          </div>
          <div class="form-group">
            <label for="tamanoMotor2">Tamaño Motor 2 (kW)</label>
            <input
              type="number"
              id="tamanoMotor2"
              name="tamanoMotor2"
              step="0.01" />
          </div>
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
  // Datos del registro a editar
  const REGISTRO_ID = <?= $maId ?>;
  const DATOS_REG   = <?= json_encode($datosReg, JSON_UNESCAPED_UNICODE) ?>;

  // Mapeo campo JS → columna DB para el update
  const MAPA_DB = {
    noMaq:'ma_no', marca:'ma_marca', modelo:'ma_modelo', fechaFabricacion:'ma_fecha_fabr',
    ubicacion:'ma_ubicacion', tipoMaquina:'ma_tipo',
    dimAncho:'ma_ancho', dimLargo:'ma_largo', dimAlto:'ma_alto',
    peso:'ma_peso', tamanoTanqueAceite:'ma_vol_tanq_aceite',
    tonelaje:'ma_tonelaje', distanciaBarras:'ma_dist_barras',
    tamanoPlatina:'ma_tam_platina', anilloCentrador:'ma_anillo_centr',
    alturaMaxMolde:'ma_alt_max_molde', aperturaMax:'ma_apert_max',
    alturaMinMolde:'ma_alt_min_molde', tipoSujecion:'ma_tipo_sujecion',
    moldeChico:'ma_molde_chico', patronBotado:'ma_botado_patron',
    fuerzaBotado:'ma_botado_fuerza', carreraBotado:'ma_botado_carrera',
    tamanoUnidadInyeccion:'ma_tam_unid_inyec', volumenInyeccion:'ma_vol_inyec',
    diametroHusillo:'ma_diam_husillo', cargaMax:'ma_carga_max',
    ld:'ma_ld', tipoHusillo:'ma_tipo_husillo',
    maxPresionInyeccion:'ma_max_pres_inyec', maxContrapresion:'ma_max_contrapres',
    maxRevoluciones:'ma_max_revol', maxVelocidadInyeccion:'ma_max_vel_inyec',
    valvulaShutOff:'ma_valv_shut_off', cargaVuelo:'ma_carga_vuelo',
    fuerzaApoyo:'ma_fuerza_apoyo', noyos:'ma_noyos',
    numValvulasAire:'ma_no_valv_aire', tipoValvulasAire:'ma_tipo_valv_aire',
    secador:'ma_secador', termoreguladores:'ma_termoreguladores',
    cargador:'ma_cargador', canalCaliente:'ma_canal_caliente',
    robot:'ma_robot', acumuladorHidraulico:'ma_acumul_hidr',
    voltaje:'ma_voltaje', calentamiento:'ma_calentamiento',
    tamanoMotor1:'ma_tam_motor_1', tamanoMotor2:'ma_tam_motor_2',
  };

    let registros = [];
    let registroEditando = null;

    function calcularVolumenInyeccion() {
      const diametro = parseFloat(document.getElementById("diametroHusillo").value) || 0;
      const carga = parseFloat(document.getElementById("cargaMax").value) || 0;

      if (diametro > 0 && carga > 0) {
        const volumen = (diametro * diametro * 0.7854 * carga) / 1000;
        document.getElementById("volumenInyeccion").value = volumen.toFixed(2);
      } else {
        document.getElementById("volumenInyeccion").value = "";
      }
    }

    ["diametroHusillo", "cargaMax"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener("input", calcularVolumenInyeccion);
    });

    function mostrarMensaje(texto, tipo) {
      const mensaje = document.getElementById("mensaje");
      mensaje.textContent = texto;
      mensaje.className = `mensaje ${tipo}`;
      mensaje.style.display = "block";
      setTimeout(() => (mensaje.style.display = "none"), 3000);
    }

    function limpiarFormulario() {
      document.getElementById("formMaquina").reset();
      registroEditando = null;
      document.querySelector(".btn-primary").textContent = "Guardar Registro";
    }

    function obtenerDatosFormulario() {

      const datos = {};

      const camposNormales = [
        "noMaq", "marca", "modelo", "fechaFabricacion", "ubicacion", "dimAncho", "dimLargo",
        "dimAlto", "peso", "tamanoTanqueAceite", "tonelaje", "distanciaBarras",
        "tamanoPlatina", "anilloCentrador", "alturaMaxMolde", "aperturaMax",
        "alturaMinMolde", "tamanoUnidadInyeccion", "volumenInyeccion",
        "diametroHusillo", "ld", "maxPresionInyeccion", "maxContrapresion",
        "maxRevoluciones", "maxVelocidadInyeccion", "fuerzaApoyo", "noyos",
        "numValvulasAire", "secador", "termoreguladores", "canalCaliente",
        "robot", "voltaje", "calentamiento", "tamanoMotor1", "tamanoMotor2",
        "patronBotado", "fuerzaBotado", "carreraBotado"
      ];

      camposNormales.forEach(campo => {
        const elemento = document.getElementById(campo);
        datos[campo] = elemento ? elemento.value : "";
      });

      const radios = [
        "tipoMaquina",
        "tipoSujecion",
        "valvulaShutOff",
        "cargaVuelo",
        "cargador",
        "acumuladorHidraulico"
      ];

      radios.forEach(name => {
        const seleccionado = document.querySelector(`input[name="${name}"]:checked`);
        datos[name] = seleccionado ? seleccionado.value : "";
      });

      return datos;
    }

    function cargarDatosFormulario(datos) {

      Object.keys(datos).forEach(key => {
        const elemento = document.getElementById(key);
        if (elemento && elemento.type !== "radio") {
          elemento.value = datos[key];
        }
      });

      const radios = [
        "tipoMaquina",
        "tipoSujecion",
        "valvulaShutOff",
        "cargaVuelo",
        "cargador",
        "acumuladorHidraulico"
      ];

      radios.forEach(name => {
        if (datos[name]) {
          const radio = document.querySelector(`input[name="${name}"][value="${datos[name]}"]`);
          if (radio) radio.checked = true;
        }
      });
    }

    document
      .getElementById("formMaquina")
      .addEventListener("submit", async function (e) {
        e.preventDefault();
        const datos = obtenerDatosFormulario();

        if (!datos.marca || !datos.modelo) {
          mostrarMensaje("Marca y modelo son obligatorios", "error");
          return;
        }

        // Construir payload con nombres de columnas DB
        const payload = { ma_id: REGISTRO_ID };
        Object.keys(datos).forEach(k => {
          if (MAPA_DB[k]) payload[MAPA_DB[k]] = datos[k] || null;
        });

        try {
          const res = await fetch('/actions/update_maquina.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (data.ok || data.success) {
            mostrarMensaje("Cambios guardados correctamente", "exito");
            setTimeout(() => window.close(), 1500);
          } else {
            mostrarMensaje(data.error || data.mensaje || "Error al guardar", "error");
          }
        } catch(err) {
          mostrarMensaje("Error de conexión", "error");
        }
      });

    function actualizarTabla() {
      const tbody = document.getElementById("cuerpoTabla");
      tbody.innerHTML = "";

      registros.forEach((registro, index) => {
        const fila = document.createElement("tr");

        const ordenColumnas = [
          "noMaq",
          "marca",
          "modelo",
          "fechaFabricacion",
          "ubicacion",
          "tipoMaquina",
          "dimAncho",
          "dimLargo",
          "dimAlto",
          "peso",
          "tamanoTanqueAceite",
          "tonelaje",
          "distanciaBarras",
          "tamanoPlatina",
          "anilloCentrador",
          "alturaMaxMolde",
          "aperturaMax",
          "alturaMinMolde",
          "tipoSujecion",
          "moldeChico",
          "patronBotado",
          "fuerzaBotado",
          "carreraBotado",
          "tamanoUnidadInyeccion",
          "volumenInyeccion",
          "diametroHusillo",
          "cargaMax",
          "ld",
          "tipoHusillo",
          "maxPresionInyeccion",
          "maxContrapresion",
          "maxRevoluciones",
          "maxVelocidadInyeccion",
          "valvulaShutOff",
          "cargaVuelo",
          "fuerzaApoyo",
          "noyos",
          "numValvulasAire",
          "tipoValvulasAire",
          "secador",
          "termoreguladores",
          "cargador",
          "canalCaliente",
          "robot",
          "acumuladorHidraulico",
          "voltaje",
          "calentamiento",
          "tamanoMotor1",
          "tamanoMotor2",
        ];

        ordenColumnas.forEach((campo) => {
          const celda = document.createElement("td");
          celda.textContent = registro[campo] || "";
          fila.appendChild(celda);
        });

        const celdaAcciones = document.createElement("td");
        celdaAcciones.innerHTML = `
          <button class="btn-editar" onclick="editarRegistro(${index})">✏️ Editar</button>
          <button class="btn-eliminar" onclick="eliminarRegistro(${index})">✖️ Eliminar</button>
        `;
        fila.appendChild(celdaAcciones);

        tbody.appendChild(fila);
      });
    }

    function editarRegistro(index) {
      registroEditando = index;
      cargarDatosFormulario(registros[index]);
      document.querySelector(".btn-primary").textContent = "Actualizar Registro";
      window.scrollTo({
        top: 0,
        behavior: "smooth",
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
        "Marca",
        "Modelo",
        "Fecha Fabricación",
        "Ubicación",
        "Tipo Máquina",
        "Ancho",
        "Largo",
        "Alto",
        "Peso",
        "Tamaño Tanque Aceite",
        "Tonelaje",
        "Distancia Barras",
        "Tamaño Platina",
        "Anillo Centrador",
        "Altura Máx Molde",
        "Apertura Máx",
        "Altura Mín Molde",
        "Tipo Sujeción",
        "Molde Chico",
        "Patrón Botado",
        "Fuerza Botado",
        "Carrera Botado",
        "Tamaño Unidad Inyección",
        "Volumen Inyección",
        "Diámetro Husillo",
        "Carga Máx",
        "L/D",
        "Tipo Husillo",
        "Máx Presión Inyección",
        "Máx Contrapresión",
        "Máx Revoluciones",
        "Máx Velocidad Inyección",
        "Válvula Shut-Off",
        "Carga al Vuelo",
        "Fuerza Apoyo",
        "Noyos",
        "Núm Válvulas Aire",
        "Tipo Válvulas Aire",
        "Secador",
        "Termoreguladores",
        "Cargador",
        "Canal Caliente",
        "Robot",
        "Acumulador Hidráulico",
        "Voltaje",
        "Calentamiento",
        "Tamaño Motor 1",
        "Tamaño Motor 2",
      ];

      const datos = registros.map((r) =>
        headers.map((_, i) => Object.values(r)[i])
      );

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...datos]);
      XLSX.utils.book_append_sheet(wb, ws, "Datos Maquina");
      XLSX.writeFile(wb, "Datos_Maquina.xlsx");
    }

    function guardarTablaMaquinaEnBD() {
      if (registros.length === 0) {
        mostrarMensaje("No hay registros en la tabla para guardar", "error");
        return;
      }

      if (!confirm("¿Desea guardar todos los registros de la tabla en la base de datos?")) {
        return;
      }

      fetch("/actions/guardar_maquina.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ registros: registros }),
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
    document.addEventListener("DOMContentLoaded", function () {
        const headers = document.querySelectorAll(".section-header");

        headers.forEach((header) => {
            const content = header.nextElementSibling;

            if (!content || !content.classList.contains("section-content")) return;

            content.style.maxHeight = "0px";

            header.addEventListener("click", function () {
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

    // Prellenar formulario con datos del registro
    (function precargar() {
      const d = DATOS_REG;
      const campos = [
        "marca","modelo","fechaFabricacion","ubicacion","dimAncho","dimLargo",
        "dimAlto","peso","tamanoTanqueAceite","tonelaje","distanciaBarras",
        "tamanoPlatina","anilloCentrador","alturaMaxMolde","aperturaMax",
        "alturaMinMolde","tamanoUnidadInyeccion","volumenInyeccion",
        "diametroHusillo","ld","maxPresionInyeccion","maxContrapresion",
        "maxRevoluciones","maxVelocidadInyeccion","fuerzaApoyo","noyos",
        "numValvulasAire","secador","termoreguladores","canalCaliente",
        "robot","voltaje","calentamiento","tamanoMotor1","tamanoMotor2",
        "patronBotado","fuerzaBotado","carreraBotado","tipoValvulasAire"
      ];
      campos.forEach(k => {
        const el = document.getElementById(k);
        if (el && d[k] != null) el.value = d[k];
      });
      const radios = ["tipoMaquina","tipoSujecion","valvulaShutOff","cargaVuelo","cargador","acumuladorHidraulico"];
      radios.forEach(name => {
        if (d[name]) {
          const r = document.querySelector(`input[name="${name}"][value="${d[name]}"]`);
          if (r) r.checked = true;
        }
      });
      // Abrir secciones para que los campos sean visibles
      document.querySelectorAll('.section-header').forEach(h => {
        h.classList.add('active');
        const content = h.nextElementSibling;
        if (content) content.style.maxHeight = content.scrollHeight + 'px';
      });
    })();
  </script>

<?php includeSidebar(); ?>
</body>

</html>