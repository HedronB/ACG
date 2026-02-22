<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

// Cargar empresas con sus plantas
$empresas = $conn->query("
    SELECT e.em_id, e.em_nombre,
           COUNT(p.pl_id) AS total_plantas
    FROM empresas e
    LEFT JOIN plantas p ON p.pl_empresa = e.em_id AND p.pl_activo = 1
    GROUP BY e.em_id, e.em_nombre
    ORDER BY e.em_nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Cargar todas las plantas activas
$plantas = $conn->query("
    SELECT pl.pl_id, pl.pl_nombre, pl.pl_empresa, pl.pl_activo, e.em_nombre
    FROM plantas pl
    INNER JOIN empresas e ON pl.pl_empresa = e.em_id
    WHERE pl.pl_activo = 1
    ORDER BY e.em_nombre ASC, pl.pl_nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas y Plantas</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
</head>
<body>

<header class="header">
    <div class="header-title-group">
        <a href="/admin/menu_admin.php">
            <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
        </a>
        <h1>Empresas y Plantas</h1>
    </div>
    <div class="header-actions">
        <button type="button" class="btn btn-primary" id="btnNuevaEmpresa">+ Nueva empresa</button>
        <button type="button" class="btn btn-secondary" id="btnNuevaPlanta">+ Nueva planta</button>
        <a href="/admin/menu_admin.php" class="back-button">⬅️ Volver</a>
    </div>
</header>

<main class="main-container">
    <div class="form-section wide">

        <div id="mensaje" class="mensaje" style="display:none;"></div>

        <!-- Empresas -->
        <h2 style="color:#0056b3; margin-top:0;">Empresas registradas</h2>
        <div class="registros-section">
            <div class="tabla-container-scroll">
                <table class="tabla-registros" id="tablaEmpresas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Plantas activas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $e): ?>
                        <tr data-id="<?= (int)$e['em_id'] ?>" data-empresa='<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT) ?>'>
                            <td><?= (int)$e['em_id'] ?></td>
                            <td><?= htmlspecialchars($e['em_nombre']) ?></td>
                            <td><?= (int)$e['total_plantas'] ?></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-edit-empresa" style="font-size:0.8em;" data-id="<?= (int)$e['em_id'] ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-delete-empresa" style="font-size:0.8em;" data-id="<?= (int)$e['em_id'] ?>" data-plantas="<?= (int)$e['total_plantas'] ?>">Eliminar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($empresas)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#888;">No hay empresas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <hr style="margin: 30px 0; border-color: #e2e8f0;">

        <!-- Plantas -->
        <h2 style="color:#0056b3;">Plantas registradas</h2>
        <div class="registros-section">
            <div class="tabla-container-scroll">
                <table class="tabla-registros" id="tablaActuales">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre planta</th>
                            <th>Empresa</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plantas as $p): ?>
                        <tr data-id="<?= (int)$p['pl_id'] ?>" data-planta='<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT) ?>'>
                            <td><?= (int)$p['pl_id'] ?></td>
                            <td><?= htmlspecialchars($p['pl_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['em_nombre']) ?></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-edit-planta" style="font-size:0.8em;" data-id="<?= (int)$p['pl_id'] ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-delete-planta" style="font-size:0.8em;" data-id="<?= (int)$p['pl_id'] ?>">Eliminar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($plantas)): ?>
                        <tr><td colspan="4" style="text-align:center; color:#888;">No hay plantas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<footer>
    <p>Método ACG</p>
</footer>

<!-- ── Modal: Nueva / Editar Empresa ── -->
<div class="modal-backdrop" id="modalEmpresa">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2 id="tituloModalEmpresa">Nueva empresa</h2>
            <button type="button" class="modal-close" data-close="modalEmpresa">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_em_id">
            <div class="input-group">
                <label>Nombre de la empresa</label>
                <input type="text" id="edit_em_nombre" placeholder="Nombre de la empresa">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEmpresa">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarEmpresa">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar eliminar empresa ── -->
<div class="modal-backdrop" id="modalEliminarEmpresa">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>Eliminar empresa</h2>
            <button type="button" class="modal-close" data-close="modalEliminarEmpresa">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_em_id">
            <p id="msgEliminarEmpresa">¿Seguro que deseas eliminar esta empresa?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEliminarEmpresa">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminarEmpresa">Eliminar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Nueva / Editar Planta ── -->
<div class="modal-backdrop" id="modalPlanta">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2 id="tituloModalPlanta">Nueva planta</h2>
            <button type="button" class="modal-close" data-close="modalPlanta">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_pl_id">
            <div class="input-group" style="margin-bottom:12px;">
                <label>Nombre de la planta</label>
                <input type="text" id="edit_pl_nombre" placeholder="Nombre de la planta">
            </div>
            <div class="input-group">
                <label>Empresa</label>
                <select id="edit_pl_empresa">
                    <option value="">-- Seleccionar empresa --</option>
                    <?php foreach ($empresas as $e): ?>
                    <option value="<?= (int)$e['em_id'] ?>"><?= htmlspecialchars($e['em_nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalPlanta">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarPlanta">Guardar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar eliminar planta ── -->
<div class="modal-backdrop" id="modalEliminarPlanta">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>Eliminar planta</h2>
            <button type="button" class="modal-close" data-close="modalEliminarPlanta">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_pl_id">
            <p>¿Seguro que deseas eliminar esta planta?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEliminarPlanta">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminarPlanta">Eliminar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const body = document.body;

    function openModal(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.add('active'); body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.remove('active'); body.style.overflow = ''; }
    }

    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', function () { closeModal(this.getAttribute('data-close')); });
    });
    document.querySelectorAll('.modal-backdrop').forEach(b => {
        b.addEventListener('click', function (e) {
            if (e.target === this) { this.classList.remove('active'); body.style.overflow = ''; }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
            body.style.overflow = '';
        }
    });

    function showMsg(msg, tipo) {
        const el = document.getElementById('mensaje');
        el.textContent = msg;
        el.className = 'mensaje ' + (tipo === 'ok' ? 'exito' : 'error');
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }

    function apiCall(url, payload, onOk) {
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(res => {
                if (res.ok) { onOk(res); }
                else { showMsg(res.mensaje || 'Error', 'error'); }
            })
            .catch(() => showMsg('Error de comunicación', 'error'));
    }

    // ── EMPRESAS ─────────────────────────────────────
    document.getElementById('btnNuevaEmpresa').addEventListener('click', () => {
        document.getElementById('edit_em_id').value = '';
        document.getElementById('edit_em_nombre').value = '';
        document.getElementById('tituloModalEmpresa').textContent = 'Nueva empresa';
        openModal('modalEmpresa');
    });

    document.querySelectorAll('.btn-edit-empresa').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = document.querySelector(`#tablaEmpresas tr[data-id="${this.dataset.id}"]`);
            if (!row) return;
            let d; try { d = JSON.parse(row.getAttribute('data-empresa')); } catch (e) { return; }
            document.getElementById('edit_em_id').value = d.em_id;
            document.getElementById('edit_em_nombre').value = d.em_nombre;
            document.getElementById('tituloModalEmpresa').textContent = 'Editar empresa';
            openModal('modalEmpresa');
        });
    });

    document.getElementById('btnGuardarEmpresa').addEventListener('click', () => {
        const id = document.getElementById('edit_em_id').value;
        const nombre = document.getElementById('edit_em_nombre').value.trim();
        if (!nombre) { showMsg('El nombre de la empresa es obligatorio', 'error'); return; }
        const url = id ? '/actions/update_empresa.php' : '/actions/create_empresa.php';
        apiCall(url, { em_id: id, em_nombre: nombre }, res => {
            closeModal('modalEmpresa');
            showMsg(res.mensaje, 'ok');
            setTimeout(() => location.reload(), 800);
        });
    });

    document.querySelectorAll('.btn-delete-empresa').forEach(btn => {
        btn.addEventListener('click', function () {
            const plantas = parseInt(this.dataset.plantas);
            const msg = document.getElementById('msgEliminarEmpresa');
            if (plantas > 0) {
                msg.textContent = `Esta empresa tiene ${plantas} planta(s) activa(s). Al eliminarla, también se darán de baja. ¿Deseas continuar?`;
            } else {
                msg.textContent = '¿Seguro que deseas eliminar esta empresa?';
            }
            document.getElementById('delete_em_id').value = this.dataset.id;
            openModal('modalEliminarEmpresa');
        });
    });

    document.getElementById('btnConfirmarEliminarEmpresa').addEventListener('click', () => {
        const id = document.getElementById('delete_em_id').value;
        apiCall('/actions/delete_empresa.php', { em_id: id }, res => {
            closeModal('modalEliminarEmpresa');
            showMsg(res.mensaje, 'ok');
            setTimeout(() => location.reload(), 800);
        });
    });

    // ── PLANTAS ──────────────────────────────────────
    document.getElementById('btnNuevaPlanta').addEventListener('click', () => {
        document.getElementById('edit_pl_id').value = '';
        document.getElementById('edit_pl_nombre').value = '';
        document.getElementById('edit_pl_empresa').value = '';
        document.getElementById('tituloModalPlanta').textContent = 'Nueva planta';
        openModal('modalPlanta');
    });

    document.querySelectorAll('.btn-edit-planta').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = document.querySelector(`#tablaActuales tr[data-id="${this.dataset.id}"]`);
            if (!row) return;
            let d; try { d = JSON.parse(row.getAttribute('data-planta')); } catch (e) { return; }
            document.getElementById('edit_pl_id').value = d.pl_id;
            document.getElementById('edit_pl_nombre').value = d.pl_nombre;
            document.getElementById('edit_pl_empresa').value = d.pl_empresa;
            document.getElementById('tituloModalPlanta').textContent = 'Editar planta';
            openModal('modalPlanta');
        });
    });

    document.getElementById('btnGuardarPlanta').addEventListener('click', () => {
        const id      = document.getElementById('edit_pl_id').value;
        const nombre  = document.getElementById('edit_pl_nombre').value.trim();
        const empresa = document.getElementById('edit_pl_empresa').value;
        if (!nombre)  { showMsg('El nombre de la planta es obligatorio', 'error'); return; }
        if (!empresa) { showMsg('Debes seleccionar una empresa', 'error'); return; }
        const url = id ? '/actions/update_planta.php' : '/actions/create_planta.php';
        apiCall(url, { pl_id: id, pl_nombre: nombre, pl_empresa: empresa }, res => {
            closeModal('modalPlanta');
            showMsg(res.mensaje, 'ok');
            setTimeout(() => location.reload(), 800);
        });
    });

    document.querySelectorAll('.btn-delete-planta').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('delete_pl_id').value = this.dataset.id;
            openModal('modalEliminarPlanta');
        });
    });

    document.getElementById('btnConfirmarEliminarPlanta').addEventListener('click', () => {
        const id = document.getElementById('delete_pl_id').value;
        apiCall('/actions/delete_planta.php', { pl_id: id }, res => {
            closeModal('modalEliminarPlanta');
            showMsg(res.mensaje, 'ok');
            setTimeout(() => location.reload(), 800);
        });
    });

})();
</script>

</body>
</html>
