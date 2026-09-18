<?php
function render_app_header(string $homePath, string $logoutPath): void
{
	$role = $_SESSION['rol'] ?? '';
	$roleLabel = $role === 'director' ? 'Director' : 'Asistente';
	?>
	<header class="page-header py-3">
		<div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
			<a class="institution-brand text-white text-decoration-none fw-semibold" href="<?= htmlspecialchars($homePath) ?>">
				<img src="../assets/images/logo-institucion.jpg.jpg" alt="Logo de la Institución Educativa Divino Maestro">
				<span>Institución Educativa &quot;Divino Maestro&quot; de Mollepampa-Cajamarca</span>
			</a>
			<div class="header-user d-flex align-items-center gap-3">
				<img class="header-user-photo" src="../assets/images/imagen-directora.jpg.png" alt="Imagen institucional">
				<span class="badge bg-secondary"><?= htmlspecialchars($roleLabel) ?></span>
				<a href="<?= htmlspecialchars($logoutPath) ?>" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
			</div>
		</div>
	</header>
	<?php
}
