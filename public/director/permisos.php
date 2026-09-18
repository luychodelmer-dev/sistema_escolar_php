<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_director();

$permisos = [
    'p_editar' => 'Editar teléfonos y direcciones',
    'p_matricular' => 'Registrar matrículas',
    'p_cobrar' => 'Registrar pagos',
];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $seleccionados = array_fill_keys(array_keys($permisos), false);
    foreach (array_keys($seleccionados) as $permiso) {
        $seleccionados[$permiso] = isset($_POST['permisos'][$permiso]);
    }

    $stmt = $pdo->prepare("SELECT id, nombres, correo, rol FROM usuarios WHERE id = :id AND rol = 'asistente'");
    $stmt->execute(['id' => $usuarioId]);
    $asistente = $stmt->fetch();

    if (!$asistente) {
        http_response_code(400);
        exit('Asistente inválido.');
    }

    $upsert = $pdo->prepare(
        'INSERT INTO permisos_usuario (usuario_id, permiso, otorgado, otorgado_por, actualizado_en)
         VALUES (:usuario_id, :permiso, :otorgado, :otorgado_por, CURRENT_TIMESTAMP)
         ON CONFLICT (usuario_id, permiso) DO UPDATE SET otorgado = EXCLUDED.otorgado,
         otorgado_por = EXCLUDED.otorgado_por, actualizado_en = CURRENT_TIMESTAMP'
    );

    foreach ($seleccionados as $permiso => $otorgado) {
        $upsert->execute([
            'usuario_id' => $usuarioId,
            'permiso' => $permiso,
            'otorgado' => $otorgado ? 'true' : 'false',
            'otorgado_por' => $_SESSION['user_id'],
        ]);
    }

    registrar_auditoria(
        'cambio_permisos',
        sprintf('Permisos actualizados para %s (%s): %s', $asistente['nombres'], $asistente['correo'], json_encode($seleccionados, JSON_UNESCAPED_UNICODE))
    );
    $mensaje = 'Permisos actualizados correctamente.';
}

$asistentes = $pdo->query("SELECT id, nombres, correo FROM usuarios WHERE rol = 'asistente' AND estado = TRUE ORDER BY nombres")->fetchAll();
$asignados = $pdo->query("SELECT usuario_id, permiso, otorgado FROM permisos_usuario")->fetchAll();
$mapa = [];
foreach ($asignados as $asignado) {
    $mapa[$asignado['usuario_id']][$asignado['permiso']] = (bool) $asignado['otorgado'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permisos - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f4f7fb; color: #172b4d; }.page-header { background: #172b4d; color: #fff; }.institution-brand { display:flex;align-items:center;gap:.6rem;white-space:nowrap; }.institution-brand img { width:42px;height:42px;object-fit:contain;border-radius:50%;background:#fff; }.institution-brand span { font-size:1.1rem;white-space:nowrap; }.header-user-photo { width:52px;height:52px;object-fit:cover;border:2px solid rgba(255,255,255,.85);border-radius:50%; } @media (max-width:768px) {.institution-brand span {font-size:.95rem;}}</style>
</head>
<body>
<?php render_app_header('panel.php', '../asistente/logout.php'); ?>
<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3 mb-1">Permisos de asistentes</h1><p class="text-muted mb-0">El director otorga o revoca permisos de operación.</p></div><a href="panel.php" class="btn btn-outline-primary">Volver al panel</a></div>
    <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <section class="card shadow-sm border-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Asistente</th><?php foreach ($permisos as $nombre): ?><th><?= htmlspecialchars($nombre) ?></th><?php endforeach; ?><th>Guardar</th></tr></thead><tbody>
    <?php foreach ($asistentes as $asistente): ?><tr><form method="POST"><input type="hidden" name="usuario_id" value="<?= (int) $asistente['id'] ?>"><td><strong><?= htmlspecialchars($asistente['nombres']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($asistente['correo']) ?></small></td><?php foreach ($permisos as $permiso => $nombre): ?><td><input class="form-check-input" type="checkbox" name="permisos[<?= htmlspecialchars($permiso) ?>]" <?= !empty($mapa[$asistente['id']][$permiso]) ? 'checked' : '' ?> aria-label="<?= htmlspecialchars($nombre) ?> para <?= htmlspecialchars($asistente['nombres']) ?>"></td><?php endforeach; ?><td><button class="btn btn-primary btn-sm" type="submit">Guardar</button></td></form></tr><?php endforeach; ?>
    <?php if (!$asistentes): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay asistentes activos.</td></tr><?php endif; ?></tbody></table></div></section>
</main>
</body>
</html>
