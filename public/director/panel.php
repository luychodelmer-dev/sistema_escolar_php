<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/header.php';

require_auth(['director']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel del Director - Sistema Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; color: #172b4d; }
        .page-header { background: #172b4d; color: #fff; }
        .institution-brand { display: flex; align-items: center; gap: .6rem; white-space: nowrap; }
        .institution-brand img { width: 42px; height: 42px; object-fit: contain; border-radius: 50%; background: #fff; }
        .institution-brand span { font-size: 1.1rem; white-space: nowrap; }
        .header-user-photo { width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(255, 255, 255, .85); border-radius: 50%; }
        @media (max-width: 768px) { .institution-brand span { font-size: .95rem; } }
        .module-card { height: 100%; border: 0; border-radius: 12px; transition: transform .2s ease, box-shadow .2s ease; }
        .module-card:hover { transform: translateY(-3px); box-shadow: 0 .75rem 1.5rem rgba(23, 43, 77, .12) !important; }
    </style>
</head>
<body>
<?php render_app_header('panel.php', '../asistente/logout.php'); ?>
<main class="container py-5">
    <div class="mb-4"><h1 class="h3 mb-1">Panel del Director</h1><p class="text-muted mb-0">Supervisa y gestiona la información principal de la institución.</p></div>
    <div class="row g-4">
        <div class="col-md-4"><div class="card module-card shadow-sm"><div class="card-body p-4 d-flex flex-column"><h2 class="h5 text-primary">Padres y apoderados</h2><p class="text-muted flex-grow-1">Consulta y registra la información de los responsables.</p><a href="../asistente/padres.php" class="btn btn-primary">Gestionar padres</a></div></div></div>
        <div class="col-md-4"><div class="card module-card shadow-sm"><div class="card-body p-4 d-flex flex-column"><h2 class="h5 text-success">Alumnos y matrículas</h2><p class="text-muted flex-grow-1">Revisa las matrículas por nivel, grado y sección.</p><a href="../asistente/matriculas.php" class="btn btn-success">Gestionar matrículas</a></div></div></div>
        <div class="col-md-4"><div class="card module-card shadow-sm"><div class="card-body p-4 d-flex flex-column"><h2 class="h5 text-warning">Control de pagos</h2><p class="text-muted flex-grow-1">Consulta y registra cuotas y otros pagos escolares.</p><a href="../asistente/pagos.php" class="btn btn-warning">Gestionar pagos</a></div></div></div>
        <div class="col-md-4"><div class="card module-card shadow-sm"><div class="card-body p-4 d-flex flex-column"><h2 class="h5 text-dark">Permisos</h2><p class="text-muted flex-grow-1">Otorga o revoca permisos de los asistentes.</p><a href="permisos.php" class="btn btn-dark">Gestionar permisos</a></div></div></div>
        <div class="col-md-4"><div class="card module-card shadow-sm"><div class="card-body p-4 d-flex flex-column"><h2 class="h5 text-secondary">Auditoría</h2><p class="text-muted flex-grow-1">Consulta accesos y cambios realizados.</p><a href="auditoria.php" class="btn btn-secondary">Ver auditoría</a></div></div></div>
    </div>
</main>
</body>
</html>
