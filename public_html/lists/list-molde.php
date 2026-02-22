<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

$sql = "SELECT 
            m.mo_id,
            m.mo_fecha,
            m.mo_no_pieza,
            m.mo_numero,
            m.mo_ancho,
            m.mo_alto,
            m.mo_largo,
            m.mo_placas_voladas,
            m.mo_anillo_centrador,
            m.mo_no_circ_agua,
            m.mo_peso,
            m.mo_apert_min,
            m.mo_abierto,
            m.mo_tipo_colada,
            m.mo_no_zonas,
            m.mo_no_cavidades,
            m.mo_peso_pieza,
            m.mo_puert_cavidad,
            m.mo_no_coladas,
            m.mo_peso_colada,
            m.mo_peso_disparo,
            m.mo_noyos,
            m.mo_entr_aire,
            m.mo_thermoreguladores,
            m.mo_valve_gates,
            m.mo_tiempo_ciclo,
            m.mo_cavidades_activas,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM moldes m
        INNER JOIN usuarios u ON m.mo_usuario = u.us_id
        INNER JOIN empresas e ON m.mo_empresa = e.em_id";

$where  = " WHERE m.mo_activo = 1";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where .= " AND m.mo_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where .= " AND m.mo_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY m.mo_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$moldes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$puedeEditarEliminar = ($rol == 1 || $rol == 2);
$menu_retorno = "/";

switch ($_SESSION['rol']) {
    case 1:
        $menu_retorno = "/admin/menu_admin.php";
        break;

    case 2:
        $menu_retorno = "/user/menu_user.php";
        break;

    case 3:
        $menu_retorno = "/user/menu_user.php";
        break;

    default:
        $menu_retorno = "/index.php";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de moldes</title>
    <link rel="icon" type="image/png" href="/imagenes/loguito.png">
    <link rel="stylesheet" href="/css/acg.estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <header class="header">
        <div class="header-title-group">
            <a href="/registros.php">
                <img src="/imagenes/logo.png" alt="Logo ACG" class="header-logo">
            </a>
            <a href="/registros.php">
                <h1>Listado de moldes</h1>
            </a>
        </div>

        <div>
            <a href="<?= $menu_retorno ?>" class="back-button">⬅️ Volver</a>
        </div>
    </header>

    <main class="main-container">
        <div class="form-section">

            <div class="filtros-container">
                <label>
                    🔍 Buscar:
                    <input type="text" id="filtroGlobal" placeholder="Escribe para filtrar...">
                </label>

                <label>
                    Campo:
                    <select id="campoFiltro">
                        <option value="all">Todos los campos</option>
                        <option value="0">Fecha registro</option>
                        <option value="1">No. pieza</option>
                        <option value="2">No. molde</option>
                        <option value="3">Ancho</option>
                        <option value="4">Alto</option>
                        <option value="5">Largo</option>
                        <option value="6">Placas voladas</option>
                        <option value="7">Anillo centrador</option>
                        <option value="8">No. circ. agua</option>
                        <option value="9">Peso</option>
                        <option value="10">Apertura mín.</option>
                        <option value="11">Abierto</option>
                        <option value="12">Tipo colada</option>
                        <option value="13">No. zonas</option>
                        <option value="14">No. cavidades</option>
                        <option value="15">Peso pieza</option>
                        <option value="16">Puer. por cavidad</option>
                        <option value="17">No. coladas</option>
                        <option value="18">Peso colada</option>
                        <option value="19">Peso disparo</option>
                        <option value="20">Noyos</option>
                        <option value="21">Entrada aire</option>
                        <option value="22">Thermoreguladores</option>
                        <option value="23">Valve gates</option>
                        <option value="24">Tiempo ciclo</option>
                        <option value="25">Cavidades activas</option>
                        <option value="26">Usuario</option>
                        <option value="27">Empresa</option>
                    </select>
                </label>

                <label>
                    Registros por página:
                    <select id="pageSize" class="page-size-select">
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </label>

                <div class="export-buttons">
                    <button type="button" class="btn-export" id="btnExportCSV">⬇️ Exportar Excel (CSV)</button>
                    <button type="button" class="btn-export" id="btnExportPDF">⬇️ Exportar PDF</button>
                </div>
            </div>

            <div class="registros-section">
                <?php if (empty($moldes)): ?>
                    <p>No hay moldes registrados para los criterios de búsqueda.</p>
                <?php else: ?>
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros" id="tablaMoldes">
                            <thead>
                                <tr>
                                    <th>Fecha registro</th>
                                    <th>No. pieza</th>
                                    <th>No. molde</th>
                                    <th>Ancho</th>
                                    <th>Alto</th>
                                    <th>Largo</th>
                                    <th>Placas voladas</th>
                                    <th>Anillo centrador</th>
                                    <th>No. circ. agua</th>
                                    <th>Peso</th>
                                    <th>Apertura mín.</th>
                                    <th>Abierto</th>
                                    <th>Tipo colada</th>
                                    <th>No. zonas</th>
                                    <th>No. cavidades</th>
                                    <th>Peso pieza</th>
                                    <th>Puer. por cavidad</th>
                                    <th>No. coladas</th>
                                    <th>Peso colada</th>
                                    <th>Peso disparo</th>
                                    <th>Noyos</th>
                                    <th>Entrada aire</th>
                                    <th>Thermoreguladores</th>
                                    <th>Valve gates</th>
                                    <th>Tiempo ciclo</th>
                                    <th>Cavidades activas</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moldes as $m): ?>
                                    <tr data-id="<?= (int)$m['mo_id'] ?>" data-molde='<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>
                                        <td><?= htmlspecialchars($m['mo_fecha']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_pieza']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_numero']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_ancho']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_alto']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_largo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_placas_voladas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_anillo_centrador']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_circ_agua']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_apert_min']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_abierto']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_tipo_colada']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_zonas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_cavidades']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_pieza']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_puert_cavidad']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_no_coladas']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_colada']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_peso_disparo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_noyos']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_entr_aire']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_thermoreguladores']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_valve_gates']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_tiempo_ciclo']) ?></td>
                                        <td><?= htmlspecialchars($m['mo_cavidades_activas']) ?></td>
                                        <td><?= htmlspecialchars($m['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($m['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-edit" style="font-size:0.75em;" data-id="<?= (int)$m['mo_id'] ?>">Editar</button>
                                                <button type="button" class="btn btn-danger btn-delete" style="font-size:0.75em;" data-id="<?= (int)$m['mo_id'] ?>">Eliminar</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-container">
                        <div class="pagination-info" id="paginationInfo"></div>
                        <div class="pagination-buttons">
                            <button type="button" id="prevPage">&laquo; Anterior</button>
                            <button type="button" id="nextPage">Siguiente &raquo;</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <footer>
        <p>Método ACG</p>
    </footer>

<?php if ($puedeEditarEliminar): ?>
<div class="modal-backdrop" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar molde</h2>
            <button type="button" class="modal-close" data-close="modalEditar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_mo_id">
            <div class="form-grid">
                <div class="input-group"><label>No. Pieza</label><input type="number step="0.01"" id="edit_mo_no_pieza"></div>
                <div class="input-group"><label>Número de molde</label><input type="number" id="edit_mo_numero"></div>
                <div class="input-group"><label>Ancho (mm)</label><input type="number step="0.01"" id="edit_mo_ancho"></div>
                <div class="input-group"><label>Alto (mm)</label><input type="number step="0.01"" id="edit_mo_alto"></div>
                <div class="input-group"><label>Largo (mm)</label><input type="number step="0.01"" id="edit_mo_largo"></div>
                <div class="input-group"><label>Placas voladas</label><input type="number step="0.01"" id="edit_mo_placas_voladas"></div>
                <div class="input-group"><label>Anillo centrador</label><input type="number step="0.01"" id="edit_mo_anillo_centrador"></div>
                <div class="input-group"><label>No. circuitos agua</label><input type="number step="0.01"" id="edit_mo_no_circ_agua"></div>
                <div class="input-group"><label>Peso (kg)</label><input type="number step="0.01"" id="edit_mo_peso"></div>
                <div class="input-group"><label>Apertura mínima</label><input type="number step="0.01"" id="edit_mo_apert_min"></div>
                <div class="input-group"><label>Abierto</label><input type="number step="0.01"" id="edit_mo_abierto"></div>
                <div class="input-group"><label>Tipo colada</label><input type="number step="0.01"" id="edit_mo_tipo_colada"></div>
                <div class="input-group"><label>No. zonas</label><input type="number" id="edit_mo_no_zonas"></div>
                <div class="input-group"><label>No. cavidades</label><input type="number" id="edit_mo_no_cavidades"></div>
                <div class="input-group"><label>Peso pieza</label><input type="number step="0.01"" id="edit_mo_peso_pieza"></div>
                <div class="input-group"><label>Puerta/cavidad</label><input type="number" id="edit_mo_puert_cavidad"></div>
                <div class="input-group"><label>No. coladas</label><input type="number step="0.01"" id="edit_mo_no_coladas"></div>
                <div class="input-group"><label>Peso colada</label><input type="number step="0.01"" id="edit_mo_peso_colada"></div>
                <div class="input-group"><label>Peso disparo</label><input type="number step="0.01"" id="edit_mo_peso_disparo"></div>
                <div class="input-group"><label>Noyos</label><input type="text" id="edit_mo_noyos"></div>
                <div class="input-group"><label>Entrada aire</label><input type="text" id="edit_mo_entr_aire"></div>
                <div class="input-group"><label>Termoreguladores</label><input type="text" id="edit_mo_thermoreguladores"></div>
                <div class="input-group"><label>Valve gates</label><input type="text" id="edit_mo_valve_gates"></div>
                <div class="input-group"><label>Tiempo ciclo</label><input type="text" id="edit_mo_tiempo_ciclo"></div>
                <div class="input-group"><label>Cavidades activas</label><input type="number" id="edit_mo_cavidades_activas"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEditar">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarEdicion">Guardar cambios</button>
        </div>
    </div>
</div>
<div class="modal-backdrop" id="modalEliminar">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>Eliminar molde</h2>
            <button type="button" class="modal-close" data-close="modalEliminar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_mo_id">
            <p>¿Seguro que deseas eliminar este molde?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="back-button" data-close="modalEliminar">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    const table=document.getElementById('tablaMoldes'); if(!table) return;
    const tbody=table.querySelector('tbody'), rows=Array.from(tbody.querySelectorAll('tr'));
    const fG=document.getElementById('filtroGlobal'), cF=document.getElementById('campoFiltro');
    const pS=document.getElementById('pageSize'), pB=document.getElementById('prevPage'), nB=document.getElementById('nextPage'), info=document.getElementById('paginationInfo');
    let fr=rows.slice(), cp=1, ps=parseInt(pS.value,10);
    function filter(){
        const t=fG.value.toLowerCase().trim(), c=cF.value;
        fr=!t?rows.slice():rows.filter(r=>{const cs=Array.from(r.cells); if(c==='all') return cs.map(td=>td.innerText.toLowerCase()).join(' ').includes(t); const i=parseInt(c,10); return(i>=0&&i<cs.length)?cs[i].innerText.toLowerCase().includes(t):false;}); cp=1; render();
    }
    function render(){
        while(tbody.firstChild) tbody.removeChild(tbody.firstChild);
        const tot=fr.length, tp=Math.max(1,Math.ceil(tot/ps)); if(cp>tp) cp=tp;
        const s=(cp-1)*ps, e=s+ps; fr.slice(s,e).forEach(r=>tbody.appendChild(r));
        info.textContent=`Mostrando ${tot===0?0:s+1}–${Math.min(e,tot)} de ${tot} registros (pág. ${cp} de ${tp})`;
        pB.disabled=cp<=1; nB.disabled=cp>=tp||tot===0;
    }
    fG.addEventListener('input',filter); cF.addEventListener('change',filter);
    pS.addEventListener('change',()=>{ps=parseInt(pS.value,10);cp=1;render();});
    pB.addEventListener('click',()=>{if(cp>1){cp--;render();}});
    nB.addEventListener('click',()=>{if(cp<Math.max(1,Math.ceil(fr.length/ps))){cp++;render();}});
    document.getElementById('btnExportCSV').addEventListener('click',()=>{
        const hdrs=Array.from(table.querySelectorAll('thead th')).map(th=>th.innerText.trim());
        const dRows=fr.map(row=>Array.from(row.querySelectorAll('td')).map(td=>td.innerText.replace(/\s+/g,' ').trim()));
        const wb=XLSX.utils.book_new();
        const ws=XLSX.utils.aoa_to_sheet([hdrs,...dRows]);
        ws['!cols']=hdrs.map((h,i)=>({wch:Math.min(40,Math.max(h.length,...dRows.map(r=>(r[i]||'').length)))}));
        XLSX.utils.book_append_sheet(wb,ws,'Datos');
        XLSX.writeFile(wb,'moldes.xlsx');
    });
    document.getElementById('btnExportPDF').addEventListener('click',()=>{
        const hdrs=Array.from(table.querySelectorAll('thead th')).map(th=>th.innerText.trim());
        const dRows=fr.map(row=>Array.from(row.querySelectorAll('td')).map(td=>td.innerText.replace(/\s+/g,' ').trim()));
        const {jsPDF}=window.jspdf;
        const doc=new jsPDF({orientation:'landscape',unit:'mm',format:'letter'});
        const pW=doc.internal.pageSize.getWidth(), mg=10, uW=pW-mg*2;
        const cW=hdrs.map((h,i)=>Math.min(40,Math.max(8,Math.max(h.length,...dRows.slice(0,50).map(r=>(r[i]||'').length))*1.8)));
        const groups=[]; let grp=[],gW=0;
        for(let i=0;i<hdrs.length;i++){
            if(gW+cW[i]>uW&&grp.length>0){groups.push(grp);grp=[i];gW=cW[i];}
            else{grp.push(i);gW+=cW[i];}
        }
        if(grp.length>0) groups.push(grp);
        let first=true;
        groups.forEach(cols=>{
            if(!first) doc.addPage(); first=false;
            const gH=cols.map(i=>hdrs[i]), gD=dRows.map(r=>cols.map(i=>r[i]||''));
            const gW2=cols.map(i=>cW[i]), sc=uW/gW2.reduce((a,b)=>a+b,0), fW=gW2.map(w=>w*sc);
            doc.setFontSize(10); doc.text('Listado de Moldes',mg,mg-2);
            doc.autoTable({head:[gH],body:gD,startY:mg+2,margin:{left:mg,right:mg},tableWidth:uW,
                columnStyles:Object.fromEntries(fW.map((w,i)=>[i,{cellWidth:w}])),
                styles:{fontSize:7,cellPadding:1.5,overflow:'linebreak',valign:'middle'},
                headStyles:{fillColor:[0,0,0],textColor:255,fontStyle:'bold',fontSize:7},
                alternateRowStyles:{fillColor:[245,245,245]},
                didDrawPage:function(d){
                    doc.setFontSize(7);
                    doc.text(`Pág. ${doc.internal.getCurrentPageInfo().pageNumber}`,pW-mg-20,doc.internal.pageSize.getHeight()-5);
                }
            });
        });
        doc.save('moldes.pdf');
    });
    render();
    <?php if($puedeEditarEliminar): ?>
    const body=document.body;
    const CAMPOS=['mo_no_pieza', 'mo_numero', 'mo_ancho', 'mo_alto', 'mo_largo', 'mo_placas_voladas', 'mo_anillo_centrador', 'mo_no_circ_agua', 'mo_peso', 'mo_apert_min', 'mo_abierto', 'mo_tipo_colada', 'mo_no_zonas', 'mo_no_cavidades', 'mo_peso_pieza', 'mo_puert_cavidad', 'mo_no_coladas', 'mo_peso_colada', 'mo_peso_disparo', 'mo_noyos', 'mo_entr_aire', 'mo_thermoreguladores', 'mo_valve_gates', 'mo_tiempo_ciclo', 'mo_cavidades_activas'];
    function oM(id){const el=document.getElementById(id);if(el){el.classList.add('active');body.style.overflow='hidden';}}
    function cM(id){const el=document.getElementById(id);if(el){el.classList.remove('active');body.style.overflow='';}}
    document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',function(){cM(this.getAttribute('data-close'));}));
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.addEventListener('click',function(e){if(e.target===this){this.classList.remove('active');body.style.overflow='';}}));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('.modal-backdrop.active').forEach(m=>m.classList.remove('active'));body.style.overflow='';}});
    document.querySelectorAll('.btn-edit').forEach(btn=>{
        btn.addEventListener('click',function(){
            const row=table.querySelector(`tr[data-id="${this.dataset.id}"]`); if(!row) return;
            let d; try{d=JSON.parse(row.getAttribute('data-molde'));}catch(e){return;}
            document.getElementById('edit_mo_id').value=d.mo_id||'';
            CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)el.value=d[c]??'';});
            oM('modalEditar');
        });
    });
    document.getElementById('btnGuardarEdicion').addEventListener('click',function(){
        const id=document.getElementById('edit_mo_id').value; if(!id) return;
        const payload={mo_id:id}; CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)payload[c]=el.value;});
        fetch('/actions/update_molde.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEditar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    document.querySelectorAll('.btn-delete').forEach(btn=>{
        btn.addEventListener('click',function(){document.getElementById('delete_mo_id').value=this.dataset.id;oM('modalEliminar');});
    });
    document.getElementById('btnConfirmarEliminar').addEventListener('click',function(){
        const id=document.getElementById('delete_mo_id').value; if(!id) return;
        fetch('/actions/delete_molde.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mo_id:id})})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEliminar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
