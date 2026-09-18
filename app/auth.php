<?php
function require_auth(array $roles = []): void
{
	if (!isset($_SESSION['user_id'])) {
		header('Location: ../login.php');
		exit;
	}

	if ($roles !== [] && !in_array($_SESSION['rol'] ?? '', $roles, true)) {
		http_response_code(403);
		exit('No tienes permisos para acceder a esta página.');
	}
}

function has_permission(string $permission): bool
{
	if (($_SESSION['rol'] ?? '') === 'director') {
		return true;
	}

	global $pdo;
	if (!isset($pdo)) {
		return false;
	}

	$stmt = $pdo->prepare(
		'SELECT otorgado FROM permisos_usuario WHERE usuario_id = :usuario_id AND permiso = :permiso'
	);
	$stmt->execute([
		'usuario_id' => $_SESSION['user_id'] ?? 0,
		'permiso' => $permission,
	]);

	return (bool) $stmt->fetchColumn();
}

function require_permission(string $permission): void
{
	if (!has_permission($permission)) {
		http_response_code(403);
		exit('No tienes permiso para realizar esta acción.');
	}
}

function require_director(): void
{
	require_auth(['director']);
}

function registrar_auditoria(string $accion, string $detalles): void
{
	global $pdo;
	if (!isset($pdo) || !isset($_SESSION['user_id'])) {
		return;
	}

	$stmt = $pdo->prepare(
		'INSERT INTO auditoria_movimientos (usuario_id, accion, detalles, ip_origen)
		 VALUES (:usuario_id, :accion, :detalles, :ip_origen)'
	);
	$stmt->execute([
		'usuario_id' => $_SESSION['user_id'],
		'accion' => $accion,
		'detalles' => $detalles,
		'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
	]);
}