<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');
$E = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$mensaje = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Registrar pago y renovar
    if ($action === 'registrar_pago') {
        $eid     = $_POST['estudio_id'] ?? '';
        $monto   = (float)($_POST['monto'] ?? 0);
        $metodo  = $_POST['metodo'] ?? '';
        $dias    = (int)($_POST['dias'] ?? 30);
        $ref     = trim($_POST['referencia'] ?? '');

        if (!$eid || $monto <= 0 || !$metodo) {
            $error = 'Completa todos los campos del pago.';
        } else {
            $estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$eid]);
            // Si está vencido, cuenta desde hoy. Si está activo, suma desde la fecha actual de vencimiento
            $base = ($estudio['estado'] === 'activo' && $estudio['vence_en'] && strtotime($estudio['vence_en']) > time())
                ? $estudio['vence_en']
                : date('Y-m-d H:i:s');
            $nueva_fecha = date('Y-m-d H:i:s', strtotime($base . " +{$dias} days"));

            Database::query(
                "UPDATE estudios SET estado='activo', vence_en=? WHERE id=?",
                [$nueva_fecha, $eid]
            );
            // Guardar registro de pago en tabla (si existe) o en nota
            try {
                Database::query(
                    "INSERT INTO pagos_log (id, estudio_id, monto, metodo, referencia, dias_activados, vence_hasta) VALUES (UUID(),?,?,?,?,?,?)",
                    [$eid, $monto, $metodo, $ref, $dias, $nueva_fecha]
                );
            } catch (Exception $ex) { /* tabla opcional */ }

            $mensaje = "Pago registrado. Acceso activo hasta " . fechaEs($nueva_fecha) . ".";
        }
    }

    // Cambiar estado manualmente
    if ($action === 'cambiar_estado') {
        $eid    = $_POST['estudio_id'] ?? '';
        $estado = $_POST['estado'] ?? '';
        if ($eid && in_array($estado, ['activo','suspendido','vencido'])) {
            Database::query("UPDATE estudios SET estado=? WHERE id=?", [$estado, $eid]);
            $mensaje = 'Estado actualizado.';
        }
    }

    // Renovar sin pago
    if ($action === 'renovar_sin_pago') {
        $eid  = $_POST['estudio_id'] ?? '';
        $dias = (int)($_POST['dias'] ?? 30);
        $estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$eid]);
        $base = ($estudio['vence_en'] && strtotime($estudio['vence_en']) > time())
            ? $estudio['vence_en'] : date('Y-m-d H:i:s');
        $nueva_fecha = date('Y-m-d H:i:s', strtotime($base . " +{$dias} days"));
        Database::query("UPDATE estudios SET estado='activo', vence_en=? WHERE id=?", [$nueva_fecha, $eid]);
        $mensaje = "Renovado sin pago hasta " . fechaEs($nueva_fecha) . ".";
    }
}

// Cargar estudios con info de pagos
$estudios = Database::fetchAll(
    "SELECT e.*,
        p.nombre as plan_nombre, p.precio as plan_precio,
        DATEDIFF(e.vence_en, NOW()) as dias_rest,
        DATEDIFF(NOW(), e.created_at) as dias_desde_inicio,
        (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas
     FROM estudios e
     LEFT JOIN planes p ON e.plan_id=p.id
     ORDER BY
        CASE e.estado WHEN 'vencido' THEN 0 WHEN 'activo' THEN 1 ELSE 2 END,
        e.vence_en ASC"
);

// Si no hay tabla planes, usar fallback
foreach ($estudios as &$est) {
    if (empty($est['plan_nombre'])) {
        $est['plan_nombre'] = PLAN_NOMBRES[$est['plan']] ?? $est['plan'];
        $est['plan_precio'] = PLAN_PRECIOS[$est['plan']] ?? 0;
    }
}
unset($est);

// Historial de pagos por estudio
function getPagosLog(string $eid): array {
    try {
        return Database::fetchAll(
            "SELECT * FROM pagos_log WHERE estudio_id=? ORDER BY created_at DESC LIMIT 6",
            [$eid]
        );
    } catch (Exception $e) { return []; }
}

// Stats globales
$total_activos  = count(array_filter($estudios, fn($e) => $e['estado'] === 'activo'));
$total_vencidos = count(array_filter($estudios, fn($e) => $e['estado'] === 'vencido'));
$por_vencer_7   = count(array_filter($estudios, fn($e) => $e['estado'] === 'activo' && $e['dias_rest'] !== null && $e['dias_rest'] <= 7 && $e['dias_rest'] >= 0));
$ingreso_mes    = array_sum(array_map(fn($e) => $e['estado'] === 'activo' ? (float)($e['plan_precio'] ?? 0) : 0, $estudios));

$nav_active = 'pagos'; $user_rol = 'superadmin'; $user_nombre = 'Administrador'; $user_plan = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pagos — ContaDocs Admin</title>
<link rel="stylesheet" href="/assets/css/app.css?v=2">
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;z-index:10000;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.25);width:100%;max-width:480px;padding:28px;max-height:90vh;overflow-y:auto;transform:scale(.96) translateY(10px);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1) translateY(0)}
.estudio-card{background:#fff;border:1.5px solid var(--g200);border-radius:16px;overflow:hidden;margin-bottom:16px;transition:all .2s}
.estudio-card.vencido{border-color:#fecaca}
.estudio-card.por-vencer{border-color:#fde68a}
.card-head{display:flex;align-items:center;gap:14px;padding:18px 20px}
.card-av{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#4f6ef7,#0ea472);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;font-weight:800;flex-shrink:0}
.card-av.vencido{background:linear-gradient(135deg,#94a3b8,#64748b)}
.card-info{flex:1;min-width:0}
.card-nombre{font-size:14px;font-weight:700;color:var(--g900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.card-sub{font-size:12px;color:var(--g400);margin-top:2px}
.card-body{padding:14px 20px;border-top:1px solid var(--g100)}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--g100);font-size:13px}
.info-row:last-child{border-bottom:none;padding-bottom:0}
.info-label{color:var(--g500);display:flex;align-items:center;gap:6px}
.info-val{font-weight:600;color:var(--g900)}
.info-val.danger{color:#dc2626}
.info-val.warning{color:#d97706}
.info-val.success{color:#059669}
.dias-wrap{margin:12px 0 4px}
.dias-labels{display:flex;justify-content:space-between;font-size:11px;color:var(--g400);margin-bottom:5px}
.bar-track{height:7px;background:var(--g100);border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;transition:width .5s}
.bar-ok{background:linear-gradient(90deg,#4f6ef7,#0ea472)}
.bar-warn{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.bar-danger{background:linear-gradient(90deg,#ef4444,#f87171)}
.card-actions{display:flex;gap:8px;padding:12px 20px;border-top:1px solid var(--g100);background:var(--g50);flex-wrap:wrap}
.alerta-venc{display:flex;align-items:flex-start;gap:10px;background:#fee2e2;border:1px solid #fecaca;border-radius:9px;padding:11px 14px;font-size:12px;color:#991b1b;font-weight:500;margin-bottom:12px}
.alerta-warn{display:flex;align-items:flex-start;gap:10px;background:#fef3c7;border:1px solid #fde68a;border-radius:9px;padding:11px 14px;font-size:12px;color:#92400e;font-weight:500;margin-bottom:12px}
.timeline{padding-left:18px;border-left:2px solid var(--g200);margin-top:4px}
.tl-item{position:relative;padding:0 0 14px 16px}
.tl-item:last-child{padding-bottom:0}
.tl-dot{position:absolute;left:-23px;top:3px;width:10px;height:10px;border-radius:50%;border:2px solid}
.tl-dot-green{background:#dcfce7;border-color:#16a34a}
.tl-dot-red{background:#fee2e2;border-color:#ef4444}
.tl-dot-gray{background:var(--g100);border-color:var(--g300)}
.tl-date{font-size:11px;color:var(--g400)}
.tl-desc{font-size:13px;font-weight:600;color:var(--g900)}
.tl-monto{font-size:12px;color:#059669;font-weight:500}
.tabs{display:flex;border-bottom:2px solid var(--g100);margin-bottom:20px}
.tab-btn{padding:9px 16px;border:none;background:transparent;font-size:13px;font-weight:600;color:var(--g400);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
.tab-btn.active{color:#4f6ef7;border-color:#4f6ef7}
.tab-content{display:none}.tab-content.active{display:block}
.filtro-btns{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.filtro-btn{padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid var(--g200);background:#fff;color:var(--g600);transition:all .15s}
.filtro-btn.active{border-color:#4f6ef7;background:#eef1fe;color:#3a56e0}
@media(max-width:600px){.card-actions{flex-direction:column}}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__.'/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Control de pagos</h1>
        <p>Suscripciones, vencimientos y registros de pago</p>
      </div>
      <div class="topbar-right">
        <?php if ($por_vencer_7 > 0): ?>
        <span class="badge badge-amber badge-none" style="font-size:12px;padding:7px 14px">⚠ <?= $por_vencer_7 ?> vencen esta semana</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= $E($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= $E($error) ?></div><?php endif; ?>

      <!-- KPIs -->
      <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Activos</div><div class="metric-value"><?= $total_activos ?></div></div>
            <div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub up">Pagados al día</div>
        </div>
        <div class="metric-card <?= $total_vencidos > 0 ? 'red' : 'dark' ?>">
          <div class="metric-top">
            <div><div class="metric-label">Vencidos</div><div class="metric-value" style="color:<?= $total_vencidos > 0 ? '#dc2626' : '' ?>"><?= $total_vencidos ?></div></div>
            <div class="metric-icon <?= $total_vencidos > 0 ? 'red' : 'dark' ?>"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub <?= $total_vencidos > 0 ? 'down' : '' ?>"><?= $total_vencidos > 0 ? 'Requieren cobro' : 'Todo al día ✓' ?></div>
        </div>
        <div class="metric-card amber">
          <div class="metric-top">
            <div><div class="metric-label">Vencen en 7 días</div><div class="metric-value"><?= $por_vencer_7 ?></div></div>
            <div class="metric-icon amber"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub">Avisar por WhatsApp</div>
        </div>
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Ingreso mensual</div><div class="metric-value" style="font-size:20px">S/ <?= number_format($ingreso_mes, 2) ?></div></div>
            <div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub up">S/ <?= number_format($ingreso_mes * 12, 0) ?>/año proyectado</div>
        </div>
      </div>

      <!-- Filtros rápidos -->
      <div class="filtro-btns">
        <button class="filtro-btn active" onclick="filtrar('todos',this)">Todos (<?= count($estudios) ?>)</button>
        <button class="filtro-btn" onclick="filtrar('vencido',this)" style="<?= $total_vencidos > 0 ? 'color:#dc2626;border-color:#fecaca' : '' ?>">Vencidos (<?= $total_vencidos ?>)</button>
        <button class="filtro-btn" onclick="filtrar('por-vencer',this)" style="<?= $por_vencer_7 > 0 ? 'color:#d97706;border-color:#fde68a' : '' ?>">Por vencer (<?= $por_vencer_7 ?>)</button>
        <button class="filtro-btn" onclick="filtrar('activo',this)">Activos (<?= $total_activos ?>)</button>
      </div>

      <!-- Lista de estudios -->
      <?php foreach ($estudios as $est):
        $dr = (int)($est['dias_rest'] ?? -999);
        $estado = $est['estado'];
        $es_vencido   = $estado === 'vencido' || ($estado === 'activo' && $dr < 0);
        $es_por_vencer= $estado === 'activo' && $dr >= 0 && $dr <= 7;
        $card_clase   = $es_vencido ? 'vencido' : ($es_por_vencer ? 'por-vencer' : '');

        // Calcular % días usados
        $dias_plan = (int)($est['dias_acceso'] ?? 30);
        if ($dias_plan <= 0) $dias_plan = 30;
        $dias_usados = max(0, $dias_plan - max(0, $dr));
        $pct = min(100, round(($dias_usados / $dias_plan) * 100));
        $bar_clase = $pct >= 90 ? 'bar-danger' : ($pct >= 70 ? 'bar-warn' : 'bar-ok');

        $pagos_log = getPagosLog($est['id']);
      ?>
      <div class="estudio-card <?= $card_clase ?>" data-estado="<?= $es_vencido ? 'vencido' : ($es_por_vencer ? 'por-vencer' : 'activo') ?>">

        <div class="card-head">
          <div class="card-av <?= $es_vencido ? 'vencido' : '' ?>"><?= strtoupper(substr($est['nombre'], 0, 1)) ?></div>
          <div class="card-info">
            <div class="card-nombre"><?= $E($est['nombre']) ?></div>
            <div class="card-sub"><?= $E($est['email_admin']) ?> · <?= $E($est['plan_nombre'] ?? '—') ?> · S/ <?= number_format((float)($est['plan_precio'] ?? 0), 2) ?>/mes</div>
          </div>
          <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
            <?php if ($es_vencido): ?>
            <span class="badge badge-red">Vencido</span>
            <?php elseif ($es_por_vencer): ?>
            <span class="badge badge-amber">Vence en <?= $dr ?> días</span>
            <?php else: ?>
            <span class="badge badge-green">Activo · <?= $dr ?> días</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body">
          <?php if ($es_vencido): ?>
          <div class="alerta-venc">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Acceso bloqueado. Venció el <?= $est['vence_en'] ? fechaEs($est['vence_en']) : '—' ?>. El contador no puede ingresar.
          </div>
          <?php elseif ($es_por_vencer): ?>
          <div class="alerta-warn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Vence el <?= fechaEs($est['vence_en']) ?>. Contacta al contador para gestionar el pago.
            <a href="https://wa.me/?text=Hola+<?= urlencode($est['nombre']) ?>%2C+tu+suscripci%C3%B3n+ContaDocs+vence+el+<?= urlencode(fechaEs($est['vence_en'])) ?>.+Por+favor+realiza+tu+pago+de+S%2F+<?= $est['plan_precio'] ?>+para+continuar." target="_blank" style="color:#92400e;font-weight:700;margin-left:6px;white-space:nowrap">→ WhatsApp</a>
          </div>
          <?php endif; ?>

          <div class="tabs">
            <button class="tab-btn active" onclick="showTab('info-<?= $E($est['id']) ?>', 'tInfo-<?= $E($est['id']) ?>', event)">Info</button>
            <button class="tab-btn" onclick="showTab('hist-<?= $E($est['id']) ?>', 'tHist-<?= $E($est['id']) ?>', event)">Historial pagos</button>
          </div>

          <div id="info-<?= $E($est['id']) ?>" class="tab-content active">
            <div class="info-row">
              <span class="info-label">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Inicio del plan
              </span>
              <span class="info-val"><?= $est['created_at'] ? fechaEs($est['created_at']) : '—' ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Vence el
              </span>
              <span class="info-val <?= $es_vencido ? 'danger' : ($es_por_vencer ? 'warning' : 'success') ?>">
                <?= $est['vence_en'] ? fechaEs($est['vence_en']) : '—' ?>
                <?php if (!$es_vencido && $dr >= 0): ?> · <?= $dr ?> días<?php endif; ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Precio del plan
              </span>
              <span class="info-val success">S/ <?= number_format((float)($est['plan_precio'] ?? 0), 2) ?>/mes</span>
            </div>
            <div class="info-row">
              <span class="info-label">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Empresas
              </span>
              <span class="info-val"><?= $est['total_empresas'] ?></span>
            </div>

            <!-- Barra de días -->
            <div class="dias-wrap">
              <div class="dias-labels">
                <span>Período actual</span>
                <span><?= $dias_usados ?> / <?= $dias_plan ?> días <?= $es_vencido ? '· VENCIDO' : '' ?></span>
              </div>
              <div class="bar-track">
                <div class="<?= $bar_clase ?>" style="width:<?= $pct ?>%;height:7px;border-radius:4px;transition:width .5s"></div>
              </div>
            </div>
          </div>

          <div id="hist-<?= $E($est['id']) ?>" class="tab-content">
            <?php if (empty($pagos_log)): ?>
            <div style="text-align:center;padding:20px 0;color:var(--g400);font-size:13px">Sin registros de pago aún.</div>
            <?php else: ?>
            <div class="timeline">
              <?php foreach ($pagos_log as $pago): ?>
              <div class="tl-item">
                <div class="tl-dot tl-dot-green"></div>
                <div class="tl-date"><?= fechaEs($pago['created_at']) ?></div>
                <div class="tl-desc">Pago · <?= $E($pago['metodo']) ?></div>
                <div class="tl-monto">S/ <?= number_format($pago['monto'], 2) ?> · <?= $pago['dias_activados'] ?> días activados</div>
                <?php if ($pago['referencia']): ?>
                <div style="font-size:11px;color:var(--g400)">Ref: <?= $E($pago['referencia']) ?></div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-actions">
          <!-- Registrar pago -->
          <button class="btn btn-primary btn-sm" onclick="abrirPago('<?= $E($est['id']) ?>','<?= $E($est['nombre']) ?>','<?= number_format((float)($est['plan_precio']??0),2) ?>')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <?= $es_vencido ? 'Registrar pago y reactivar' : 'Registrar pago' ?>
          </button>

          <!-- Renovar sin pago -->
          <form method="POST" style="display:inline" onsubmit="return confirm('¿Renovar 30 días sin registrar pago?')">
            <input type="hidden" name="action" value="renovar_sin_pago">
            <input type="hidden" name="estudio_id" value="<?= $E($est['id']) ?>">
            <input type="hidden" name="dias" value="30">
            <button type="submit" class="btn btn-secondary btn-sm">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Renovar sin pago
            </button>
          </form>

          <!-- WhatsApp -->
          <a href="https://wa.me/?text=Hola+<?= urlencode($est['nombre']) ?>%2C+recordatorio+de+pago+ContaDocs+S%2F+<?= urlencode(number_format((float)($est['plan_precio']??0),2)) ?>%2Fmes.+Vence+el+<?= urlencode(fechaEs($est['vence_en']??date('Y-m-d'))) ?>" target="_blank" class="btn btn-secondary btn-sm" style="background:#dcfce7;border-color:#bbf7d0;color:#15803d">
            <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a10.08 10.08 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.112 1.524 5.84L0 24l6.335-1.524A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.733.898.914-3.643-.234-.374A9.817 9.817 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
            WhatsApp
          </a>

          <!-- Suspender / Activar -->
          <?php if ($estado !== 'suspendido'): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('¿Suspender acceso de <?= $E(addslashes($est['nombre'])) ?>?')">
            <input type="hidden" name="action" value="cambiar_estado">
            <input type="hidden" name="estudio_id" value="<?= $E($est['id']) ?>">
            <input type="hidden" name="estado" value="suspendido">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--rojo)">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              Suspender
            </button>
          </form>
          <?php else: ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="cambiar_estado">
            <input type="hidden" name="estudio_id" value="<?= $E($est['id']) ?>">
            <input type="hidden" name="estado" value="activo">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#059669">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Reactivar
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($estudios)): ?>
      <div class="empty-state">
        <div class="empty-icon">💳</div>
        <div class="empty-title">Sin estudios registrados</div>
        <div class="empty-sub">Crea el primer estudio desde la sección Estudios.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL REGISTRAR PAGO -->
<div class="modal-overlay" id="modalPago" onclick="if(event.target===this)cerrarModal('modalPago')">
  <div class="modal">
    <div class="modal-title">Registrar pago</div>
    <div class="modal-sub" id="pagoEstudioNombre" style="margin-bottom:20px"></div>

    <form method="POST">
      <input type="hidden" name="action" value="registrar_pago">
      <input type="hidden" name="estudio_id" id="pagoEstudioId">

      <div class="form-group">
        <label class="form-label">Monto recibido (S/) *</label>
        <input type="number" name="monto" id="pagoMonto" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
      </div>

      <div class="form-group">
        <label class="form-label">Método de pago *</label>
        <select name="metodo" class="form-select" required>
          <option value="">— Selecciona —</option>
          <option value="Yape">📱 Yape</option>
          <option value="Plin">📱 Plin</option>
          <option value="Transferencia bancaria">🏦 Transferencia bancaria</option>
          <option value="Efectivo">💵 Efectivo</option>
          <option value="Depósito">🏧 Depósito</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Renovar por *</label>
        <select name="dias" class="form-select" required>
          <option value="30">30 días — 1 mes</option>
          <option value="60">60 días — 2 meses</option>
          <option value="90">90 días — 3 meses</option>
          <option value="180">180 días — 6 meses</option>
          <option value="365">365 días — 1 año</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">N° de operación / voucher <span style="font-weight:400;color:var(--g400)">(opcional)</span></label>
        <input type="text" name="referencia" class="form-input" placeholder="Ej: OP-20250626-001">
        <div class="form-hint">Guarda el número para tus registros</div>
      </div>

      <div class="alert alert-info" style="margin-bottom:0">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Si el estudio está vencido, los días se cuentan desde hoy. Si está activo, se suman a la fecha actual de vencimiento.
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalPago')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Confirmar y activar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(id){document.getElementById(id).classList.add('open')}
function cerrarModal(id){document.getElementById(id).classList.remove('open')}

function abrirPago(id, nombre, precio){
  document.getElementById('pagoEstudioId').value = id;
  document.getElementById('pagoEstudioNombre').textContent = nombre;
  document.getElementById('pagoMonto').value = precio;
  abrirModal('modalPago');
}

function showTab(contentId, btnId, event){
  const card = event.target.closest('.card-body');
  card.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  card.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(contentId).classList.add('active');
  event.target.classList.add('active');
}

function filtrar(tipo, btn){
  document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.estudio-card').forEach(card => {
    if(tipo === 'todos'){card.style.display='';return}
    card.style.display = card.dataset.estado === tipo ? '' : 'none';
  });
}
</script>
</body>
</html>
