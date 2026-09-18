<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_director();

$movimientos = $pdo->query(
    'SELECT a.creado_en, a.accion, a.detalles, a.ip_origen, u.nombres AS usuario
     FROM auditoria_movimientos a
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     ORDER BY a.id DESC LIMIT 100'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoría - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background:#f4f7fb;color:#172b4d; }.page-header { background:#172b4d;color:#fff; }.institution-brand { display:flex;align-items:center;gap:.6rem;white-space:nowrap; }.institution-brand img { width:42px;height:42px;object-fit:contain;border-radius:50%;background:#fff; }.institution-brand span { font-size:1.1rem;white-space:nowrap; }.header-user-photo { width:52px;height:52px;object-fit:cover;border:2px solid rgba(255,255,255,.85);border-radius:50%; } @media (max-width:768px) {.institution-brand span {font-size:.95rem;}}</style>
</head>
<body>
<?php render_app_header('panel.php', '../asistente/logout.php'); ?>
<main class="container py-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3 mb-1">Auditoría de movimientos</h1><p class="text-muted mb-0">Registro de accesos y cambios realizados en el sistema.</p></div><a href="panel.php" class="btn btn-outline-primary">Volver al panel</a></div><section class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalles</th><th>IP</th></tr></thead><tbody>
<?php foreach ($movimientos as $movimiento): ?><tr><td><?= htmlspecialchars($movimiento['creado_en']) ?></td><td><?= htmlspecialchars($movimiento['usuario'] ?? 'Usuario eliminado') ?></td><td><?= htmlspecialchars($movimiento['accion']) ?></td><td><?= htmlspecialchars($movimiento['detalles']) ?></td><td><?= htmlspecialchars($movimiento['ip_origen'] ?? '-') ?></td></tr><?php endforeach; ?>
<?php if (!$movimientos): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay movimientos registrados.</td></tr><?php endif; ?></tbody></table></div></section></main>
</body>
</html>
