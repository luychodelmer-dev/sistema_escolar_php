<?php
$localConfigPath = __DIR__ . '/database.local.php';
$localConfig = file_exists($localConfigPath) ? require $localConfigPath : [];

$host = $localConfig['host'] ?? getenv('SISTEMA_ESCOLAR_DB_HOST') ?: 'localhost';
$dbname = $localConfig['dbname'] ?? getenv('SISTEMA_ESCOLAR_DB_NAME') ?: 'sistema_escolar';
$username = $localConfig['username'] ?? getenv('SISTEMA_ESCOLAR_DB_USER') ?: 'postgres';
$password = $localConfig['password'] ?? getenv('SISTEMA_ESCOLAR_DB_PASSWORD') ?: '';

try {
	$dsn = "pgsql:host=$host;dbname=$dbname";
	$pdo = new PDO($dsn, $username, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (PDOException $exception) {
	http_response_code(500);
	exit('No se pudo conectar con la base de datos.');
}
