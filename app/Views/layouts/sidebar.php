<?php
$nav_admin = [
    ['href'=>'/admin/dashboard.php','label'=>'Dashboard','key'=>'dashboard','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['href'=>'/admin/estudios.php','label'=>'Estudios','key'=>'estudios','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ['href'=>'/admin/planes.php','label'=>'Planes','key'=>'planes','icon'=>'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
    ['href'=>'/admin/pagos.php','label'=>'Pagos','key'=>'pagos','icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
];
$nav_contador = [
    ['href'=>'/contador/clientes.php',   'label'=>'Mis clientes',  'key'=>'clientes',   'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
    ['href'=>'/contador/documentos.php', 'label'=>'Documentos',    'key'=>'documentos', 'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ['href'=>'/contador/subir.php',      'label'=>'Subir docs',    'key'=>'subir',      'icon'=>'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
    ['href'=>'/contador/categorias.php', 'label'=>'Categorías',    'key'=>'categorias', 'icon'=>'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
    ['href'=>'/contador/descargas.php',  'label'=>'Descargas',     'key'=>'descargas',  'icon'=>'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ['href'=>'/contador/cuenta.php',     'label'=>'Mi cuenta',     'key'=>'cuenta',     'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
];
$nav_cliente = [
    ['href'=>'/cliente/documentos.php','label'=>'Mis documentos','key'=>'documentos','icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ['href'=>'/cliente/historial.php', 'label'=>'Historial',     'key'=>'historial', 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
];
$nav_items  = match($user_rol??'') { 'superadmin'=>$nav_admin, 'contador'=>$nav_contador, default=>$nav_cliente };
$sec_labels = ['superadmin'=>'Administración','contador'=>'Gestión','cliente'=>'Mi portal'];
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
    </div>
    <span class="sidebar-logo-text">Conta<span>Docs</span></span>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label"><?= $sec_labels[$user_rol??''] ?? 'Menú' ?></div>
    <?php foreach ($nav_items as $item): ?>
    <a href="<?= $item['href'] ?>" class="nav-item <?= ($nav_active??'')===$item['key']?'active':'' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
      </svg>
      <?= e($item['label']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-footer">
    <?php if (!empty($user_nombre)): ?>
    <div style="padding:8px 8px 10px">
      <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--gris-50);border-radius:10px;border:1px solid var(--gris-200)">
        <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--azul),var(--verde));display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0">
          <?= strtoupper(substr($user_nombre,0,1)) ?>
        </div>
        <div style="min-width:0">
          <div style="font-size:12px;font-weight:600;color:var(--gris-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($user_nombre) ?></div>
          <?php if (!empty($user_plan)): ?>
          <?php
            try {
              $planes_all = Database::fetchAll("SELECT * FROM planes WHERE activo=1");
              $plan_data  = null;
              foreach ($planes_all as $p) {
                if ($p['id']===$user_plan || $p['nombre']===$user_plan) { $plan_data=$p; break; }
              }
            } catch(Exception $e) { $plan_data = null; }
          ?>
          <div style="font-size:11px;color:var(--gris-400);margin-top:1px"><?= $plan_data ? e($plan_data['nombre']) : e($user_plan) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <a href="/logout.php" class="nav-item" style="color:var(--gris-400);margin:0 0 4px">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Cerrar sesión
    </a>
  </div>
</aside>
