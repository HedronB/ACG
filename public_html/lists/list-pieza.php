<?php
require_once __DIR__ . '/../../app/bootstrap.php';

require_once BASE_PATH . '/app/auth/protect.php';
require_once BASE_PATH . '/app/config/db.php';

$usuarioId = $_SESSION['id'];
$rol       = $_SESSION['rol'];
$empresaId = $_SESSION['empresa'];

$sql = "SELECT 
            p.pi_id,
            p.pi_fecha,
            p.pi_cod_prod,
            p.pi_molde,
            p.pi_descripcion,
            p.pi_resina,
            p.pi_espesor,
            p.pi_area_proy,
            p.pi_color,
            p.pi_tipo_empaque,
            p.pi_piezas,
            p.pi_caja_no_pzs,
            p.pi_caja_tamano,
            p.pi_bolsa1,
            p.pi_bolsa2,
            p.pi_tarima_no_cajas,
            u.us_nombre AS nombre_usuario,
            e.em_nombre AS nombre_empresa
        FROM piezas p
        INNER JOIN usuarios u ON p.pi_usuario = u.us_id
        INNER JOIN empresas e ON p.pi_empresa = e.em_id";

$where  = " WHERE p.pi_activo = 1";
$params = [];

switch ($rol) {
    case 1:
        break;
    case 2:
        $where .= " AND p.pi_empresa = :empresa";
        $params[':empresa'] = $empresaId;
        break;
    case 3:
        $where .= " AND p.pi_usuario = :usuario";
        $params[':usuario'] = $usuarioId;
        break;
    default:
        header("Location: index.php?error=Rol no autorizado");
        exit();
}

$sql .= $where . " ORDER BY p.pi_fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$piezas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Listado de piezas</title>
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
                <h1>Listado de piezas</h1>
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
                        <option value="1">Código producto</option>
                        <option value="2">Molde</option>
                        <option value="3">Descripción</option>
                        <option value="4">Resina</option>
                        <option value="5">Espesor</option>
                        <option value="6">Área proyectada</option>
                        <option value="7">Color</option>
                        <option value="8">Tipo empaque</option>
                        <option value="9">Piezas</option>
                        <option value="10">Caja no. pzs</option>
                        <option value="11">Tamaño caja</option>
                        <option value="12">Bolsa 1</option>
                        <option value="13">Bolsa 2</option>
                        <option value="14">Tarima no. cajas</option>
                        <option value="15">Usuario</option>
                        <option value="16">Empresa</option>
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
                <?php if (empty($piezas)): ?>
                    <p>No hay piezas registradas para los criterios de búsqueda.</p>
                <?php else: ?>
                    <div class="tabla-container-scroll">
                        <table class="tabla-registros" id="tablaPiezas">
                            <thead>
                                <tr>
                                    <th>Fecha registro</th>
                                    <th>Código producto</th>
                                    <th>Molde</th>
                                    <th>Descripción</th>
                                    <th>Resina</th>
                                    <th>Espesor</th>
                                    <th>Área proyectada</th>
                                    <th>Color</th>
                                    <th>Tipo empaque</th>
                                    <th>Piezas</th>
                                    <th>Caja no. pzs</th>
                                    <th>Tamaño caja</th>
                                    <th>Bolsa 1</th>
                                    <th>Bolsa 2</th>
                                    <th>Tarima no. cajas</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <?php if ($puedeEditarEliminar): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($piezas as $p): ?>
                                    <tr data-id="<?= (int)$p['pi_id'] ?>" data-pieza='<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>
                                        <td><?= htmlspecialchars($p['pi_fecha']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_cod_prod']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_molde']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_descripcion']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_resina']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_espesor']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_area_proy']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_color']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_tipo_empaque']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_piezas']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_caja_no_pzs']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_caja_tamano']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_bolsa1']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_bolsa2']) ?></td>
                                        <td><?= htmlspecialchars($p['pi_tarima_no_cajas']) ?></td>
                                        <td><?= htmlspecialchars($p['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($p['nombre_empresa']) ?></td>
                                        <?php if ($puedeEditarEliminar): ?>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-edit" style="font-size:0.75em;" data-id="<?= (int)$p['pi_id'] ?>">Editar</button>
                                                <button type="button" class="btn btn-danger btn-delete" style="font-size:0.75em;" data-id="<?= (int)$p['pi_id'] ?>">Eliminar</button>
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
            <h2>Editar pieza</h2>
            <button type="button" class="modal-close" data-close="modalEditar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_pi_id">
            <div class="form-grid">
                <div class="input-group"><label>Código producto</label><input type="text" id="edit_pi_cod_prod"></div>
                <div class="input-group"><label>Molde</label><input type="text" id="edit_pi_molde"></div>
                <div class="input-group"><label>Descripción</label><input type="text" id="edit_pi_descripcion"></div>
                <div class="input-group"><label>Resina</label><input type="text" id="edit_pi_resina"></div>
                <div class="input-group"><label>Espesor</label><input type="number step="0.01"" id="edit_pi_espesor"></div>
                <div class="input-group"><label>Área proyectada</label><input type="text" id="edit_pi_area_proy"></div>
                <div class="input-group"><label>Color</label><input type="text" id="edit_pi_color"></div>
                <div class="input-group"><label>Tipo empaque</label><input type="text" id="edit_pi_tipo_empaque"></div>
                <div class="input-group"><label>Piezas</label><input type="number step="0.01"" id="edit_pi_piezas"></div>
                <div class="input-group"><label>Piezas por caja</label><input type="number" id="edit_pi_caja_no_pzs"></div>
                <div class="input-group"><label>Tamaño caja</label><input type="number step="0.01"" id="edit_pi_caja_tamaño"></div>
                <div class="input-group"><label>Bolsa 1</label><input type="text" id="edit_pi_bolsa1"></div>
                <div class="input-group"><label>Bolsa 2</label><input type="text" id="edit_pi_bolsa2"></div>
                <div class="input-group"><label>Cajas por tarima</label><input type="number" id="edit_pi_tarima_no_cajas"></div>
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
            <h2>Eliminar pieza</h2>
            <button type="button" class="modal-close" data-close="modalEliminar">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_pi_id">
            <p>¿Seguro que deseas eliminar este pieza?</p>
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
    const table=document.getElementById('tablaPiezas'); if(!table) return;
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
        XLSX.writeFile(wb,'piezas.xlsx');
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
            doc.setFontSize(10); doc.text('Listado de Piezas',mg,mg-2);
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
        doc.save('piezas.pdf');
    });
    render();
    <?php if($puedeEditarEliminar): ?>
    const body=document.body;
    const CAMPOS=['pi_cod_prod', 'pi_molde', 'pi_descripcion', 'pi_resina', 'pi_espesor', 'pi_area_proy', 'pi_color', 'pi_tipo_empaque', 'pi_piezas', 'pi_caja_no_pzs', 'pi_caja_tamaño', 'pi_bolsa1', 'pi_bolsa2', 'pi_tarima_no_cajas'];
    function oM(id){const el=document.getElementById(id);if(el){el.classList.add('active');body.style.overflow='hidden';}}
    function cM(id){const el=document.getElementById(id);if(el){el.classList.remove('active');body.style.overflow='';}}
    document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',function(){cM(this.getAttribute('data-close'));}));
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.addEventListener('click',function(e){if(e.target===this){this.classList.remove('active');body.style.overflow='';}}));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('.modal-backdrop.active').forEach(m=>m.classList.remove('active'));body.style.overflow='';}});
    document.querySelectorAll('.btn-edit').forEach(btn=>{
        btn.addEventListener('click',function(){
            const row=table.querySelector(`tr[data-id="${this.dataset.id}"]`); if(!row) return;
            let d; try{d=JSON.parse(row.getAttribute('data-pieza'));}catch(e){return;}
            document.getElementById('edit_pi_id').value=d.pi_id||'';
            CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)el.value=d[c]??'';});
            oM('modalEditar');
        });
    });
    document.getElementById('btnGuardarEdicion').addEventListener('click',function(){
        const id=document.getElementById('edit_pi_id').value; if(!id) return;
        const payload={pi_id:id}; CAMPOS.forEach(c=>{const el=document.getElementById('edit_'+c);if(el)payload[c]=el.value;});
        fetch('/actions/update_pieza.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEditar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    document.querySelectorAll('.btn-delete').forEach(btn=>{
        btn.addEventListener('click',function(){document.getElementById('delete_pi_id').value=this.dataset.id;oM('modalEliminar');});
    });
    document.getElementById('btnConfirmarEliminar').addEventListener('click',function(){
        const id=document.getElementById('delete_pi_id').value; if(!id) return;
        fetch('/actions/delete_pieza.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({pi_id:id})})
        .then(r=>r.json()).then(res=>{if(res.ok){cM('modalEliminar');location.reload();}else{alert(res.mensaje||'Error');}})
        .catch(()=>alert('Error de comunicación'));
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
