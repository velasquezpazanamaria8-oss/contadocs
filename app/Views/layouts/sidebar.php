<?php
$nav_admin=[
  ['href'=>'/admin/dashboard.php','label'=>'Dashboard','key'=>'dashboard','d'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
  ['href'=>'/admin/estudios.php','label'=>'Estudios','key'=>'estudios','d'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
  ['href'=>'/admin/planes.php','label'=>'Planes','key'=>'planes','d'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
  ['href'=>'/admin/pagos.php','label'=>'Pagos','key'=>'pagos','d'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
];
$nav_contador=[
  ['href'=>'/contador/clientes.php','label'=>'Mis clientes','key'=>'clientes','d'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
  ['href'=>'/contador/documentos.php','label'=>'Documentos','key'=>'documentos','d'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
  ['href'=>'/contador/subir.php','label'=>'Subir docs','key'=>'subir','d'=>'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
  ['href'=>'/contador/categorias.php','label'=>'Categorías','key'=>'categorias','d'=>'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
  ['href'=>'/contador/descargas.php','label'=>'Descargas','key'=>'descargas','d'=>'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
  ['href'=>'/contador/cuenta.php','label'=>'Mi cuenta','key'=>'cuenta','d'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
$nav_cliente=[
  ['href'=>'/cliente/documentos.php','label'=>'Mis documentos','key'=>'documentos','d'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
  ['href'=>'/cliente/historial.php','label'=>'Historial','key'=>'historial','d'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
];
$nav=match($user_rol??''){'superadmin'=>$nav_admin,'contador'=>$nav_contador,default=>$nav_cliente};
$secs=['superadmin'=>'Administración','contador'=>'Gestión','cliente'=>'Mi portal'];
?>
<button id="btnMenu" onclick="abrirMenu()">
  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>
<div id="menuOverlay" onclick="cerrarMenu()"></div>
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <span class="logo-text">Conta<span>Docs</span></span>
    <button onclick="cerrarMenu()" id="btnX" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--g400);display:none">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="sidebar-nav">
    <div class="nav-sec"><?= $secs[$user_rol??'']??'Menú' ?></div>
    <?php foreach($nav as $item): ?>
    <a href="<?= $item['href'] ?>" class="nav-item <?= ($nav_active??'')===$item['key']?'active':'' ?>" onclick="if(window.innerWidth<=768)cerrarMenu()">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['d'] ?>"/></svg>
      <?= e($item['label']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-footer">
    <?php if(!empty($user_nombre)): ?>
    <div class="user-card">
      <div class="user-av"><?= strtoupper(substr($user_nombre,0,1)) ?></div>
      <div style="min-width:0">
        <div class="user-name"><?= e($user_nombre) ?></div>
        <?php if(!empty($user_plan)): ?>
        <div class="user-plan"><?php
          try{$pp=Database::fetchAll("SELECT * FROM planes WHERE activo=1");$pd=null;
            foreach($pp as $p)if($p['id']===$user_plan||$p['nombre']===$user_plan){$pd=$p;break;}
          }catch(Exception $ex){$pd=null;}
          echo $pd?e($pd['nombre']):e($user_plan);
        ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <a href="/logout.php" class="nav-item" style="color:var(--g400)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Cerrar sesión
    </a>
  </div>
</aside>
<style>@media(max-width:768px){#btnX{display:block!important}#menuOverlay.open{display:block}}</style>
<script>
function abrirMenu(){document.getElementById('mainSidebar').classList.add('open');document.getElementById('menuOverlay').classList.add('open');document.body.style.overflow='hidden'}
function cerrarMenu(){document.getElementById('mainSidebar').classList.remove('open');document.getElementById('menuOverlay').classList.remove('open');document.body.style.overflow=''}
</script>
