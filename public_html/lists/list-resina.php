<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

$sql = "SELECT 
            r.re_id,
            r.re_fecha,
            r.re_cod_int,
            r.re_tipo_resina,
            r.re_grado,
            r.re_porc_reciclado,
            r.re_temp_masa_max,
            r.re_temp_masa_min,
            r.re_temp_ref_max,
            r.re_temp_ref_min,
            r.re_sec_temp,
            r.re_sec_tiempo,
            r.re_densidad,
            r.re_factor_correccion,
            r.re_carga,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM resinas r
        INNER JOIN usuarios u ON r.re_usuario = u.us_id
        INNER JOIN empresas e ON r.re_empresa = e.em_id";

$where  = " WHERE r.re_activo = 1";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where .= " AND r.re_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where .= " AND r.re_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY r.re_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Listado de resinas</title>
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
                <h1>Listado de resinas</h1>
            </a>
        </div>

        <div>
            <!-- <a href="form-maquina.php" class="back-button">➕ Nueva máquina</a> -->
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
                        <option value="1">Código interno</option>
                        <option value="2">Tipo resina</option>
                        <option value="3">Grado</option>
                        <option value="4">% reciclado</option>
                        <option value="5">Temp. masa máx.</option>
                        <option value="6">Temp. masa mín.</option>
                        <option value="7">Temp. ref. máx.</option>
                        <option value="8">Temp. ref. mín.</option>
                        <option value="9">Secado temp.</option>
                        <option value="10">Secado tiempo</option>
                        <option value="11">Densidad</option>
                        <option value="12">Factor corrección</option>
                        <option value="13">Carga</option>
                        <option value="14">Usuario</option>
                        <option value="15">Empresa</option>
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
                <?php if (empty($resinas)): ?>
                    <p>No hay resinas registradas para los criterios de búsqueda.</p>
                <?php else: ?>
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros" id="tablaResinas">
                            <thead>
                                <tr>
                                    <th>Fecha registro</th>
                                    <th>Código interno</th>
                                    <th>Tipo resina</th>
                                    <th>Grado</th>
                                    <th>% reciclado</th>
                                    <th>Temp. masa máx.</th>
                                    <th>Temp. masa mín.</th>
                                    <th>Temp. ref. máx.</th>
                                    <th>Temp. ref. mín.</th>
                                    <th>Secado temp.</th>
                                    <th>Secado tiempo</th>
                                    <th>Densidad</th>
                                    <th>Factor corrección</th>
                                    <th>Carga</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resinas as $r): ?>
                                    <tr data-id="<?= (int)$r['re_id'] ?>" data-resina='<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>
                                        <td><?= htmlspecialchars($r['re_fecha']) ?></td>
                                        <td><?= htmlspecialchars($r['re_cod_int']) ?></td>
                                        <td><?= htmlspecialchars($r['re_tipo_resina']) ?></td>
                                        <td><?= htmlspecialchars($r['re_grado']) ?></td>
                                        <td><?= htmlspecialchars($r['re_porc_reciclado']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_masa_max']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_masa_min']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_ref_max']) ?></td>
                                        <td><?= htmlspecialchars($r['re_temp_ref_min']) ?></td>
                                        <td><?= htmlspecialchars($r['re_sec_temp']) ?></td>
                                        <td><?= htmlspecialchars($r['re_sec_tiempo']) ?></td>
                                        <td><?= htmlspecialchars($r['re_densidad']) ?></td>
                                        <td><?= htmlspecialchars($r['re_factor_correccion']) ?></td>
                                        <td><?= htmlspecialchars($r['re_carga']) ?></td>
                                        <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($r['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-edit" style="font-size:0.75em;" data-id="<?= (int)$r['re_id'] ?>">Editar</button>
                                                <button type="button" class="btn btn-danger btn-delete" style="font-size:0.75em;" data-id="<?= (int)$r['re_id'] ?>">Eliminar</button>
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
            <h2>Editar resina</h2>
            <button type="button" class="modal-close" data-close="modalEditar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_re_id">
            <div class="form-grid">
                <div class="input-group"><label>Código interno</label><input type="text" id="edit_re_cod_int"></div>
                <div class="input-group"><label>Tipo de resina</label><input type="text" id="edit_re_tipo_resina"></div>
                <div class="input-group"><label>Grado</label><input type="text" id="edit_re_grado"></div>
                <div class="input-group"><label>% Reciclado</label><input type="number step="0.01"" id="edit_re_porc_reciclado"></div>
                <div class="input-group"><label>Temp. masa máx.</label><input type="number step="0.01"" id="edit_re_temp_masa_max"></div>
                <div class="input-group"><label>Temp. masa mín.</label><input type="number step="0.01"" id="edit_re_temp_masa_min"></div>
                <div class="input-group"><label>Temp. ref. máx.</label><input type="number step="0.01"" id="edit_re_temp_ref_max"></div>
                <div class="input-group"><label>Temp. ref. mín.</label><input type="number step="0.01"" id="edit_re_temp_ref_min"></div>
                <div class="input-group"><label>Secado temp.</label><input type="number step="0.01"" id="edit_re_sec_temp"></div>
                <div class="input-group"><label>Secado tiempo (hr)</label><input type="text" id="edit_re_sec_tiempo"></div>
                <div class="input-group"><label>Densidad</label><input type="number step="0.01"" id="edit_re_densidad"></div>
                <div class="input-group"><label>Factor corrección</label><input type="number step="0.01"" id="edit_re_factor_correccion"></div>
                <div class="input-group"><label>Carga</label><input type="number step="0.01"" id="edit_re_carga"></div>
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
            <h2>Eliminar resina</h2>
            <button type="button" class="modal-close" data-close="modalEliminar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_re_id">
            <p>¿Seguro que deseas eliminar este resina?</p>
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
    const table=document.getElementById('tablaResinas'); if(!table) return;
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
        XLSX.writeFile(wb,'resinas.xlsx');
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
            doc.setFontSize(10); doc.text('Listado de Resinas',mg,mg-2);
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
        doc.save('resinas.pdf');
    });
    render();
    <?php if($puedeEditarEliminar): ?>
    const body=document.body;
    const CAMPOS=['re_cod_int', 're_tipo_resina', 're_grado', 're_porc_reciclado', 're_temp_masa_max', 're_temp_masa_min', 're_temp_ref_max', 're_temp_ref_min', 're_sec_temp', 're_sec_tiempo', 're_densidad', 're_factor_correccion', 're_carga'];
    function oM(id){const el=document.getElementById(id);if(el){el.classList.add('active');body.style.overflow='hidden';}}
    function cM(id){const el=document.getElementById(id);if(el){el.classList.remove('active');body.style.overflow='';}}
    document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',function(){cM(this.getAttribute('data-close'));}));
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.addEventListener('click',function(e){if(e.target===this){this.classList.remove('active');body.style.overflow='';}}));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('.modal-backdrop.active').forEach(m=>m.classList.remove('active'));body.style.overflow='';}});
    document.querySelectorAll('.btn-edit').forEach(btn=>{
        btn.addEventListener('click',function(){
            const row=table.querySelector(`tr[data-id="${this.dataset.id}"]`); if(!row) return;
            let d; try{d=JSON.parse(row.getAttribute('data-resina'));}catch(e){return;}
            document.getElementById('edit_re_id').value=d.re_id||'';
            CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)el.value=d[c]??'';});
            oM('modalEditar');
        });
    });
    document.getElementById('btnGuardarEdicion').addEventListener('click',function(){
        const id=document.getElementById('edit_re_id').value; if(!id) return;
        const payload={re_id:id}; CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)payload[c]=el.value;});
        fetch('/actions/update_resina.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEditar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    document.querySelectorAll('.btn-delete').forEach(btn=>{
        btn.addEventListener('click',function(){document.getElementById('delete_re_id').value=this.dataset.id;oM('modalEliminar');});
    });
    document.getElementById('btnConfirmarEliminar').addEventListener('click',function(){
        const id=document.getElementById('delete_re_id').value; if(!id) return;
        fetch('/actions/delete_resina.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({re_id:id})})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEliminar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
