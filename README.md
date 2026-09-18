# Sistema Escolar PHP

## Requisitos

- PHP con `pdo_pgsql` habilitado.
- PostgreSQL.

## Configuración

1. Copia `config/database.local.php.example` como `config/database.local.php`.
2. Completa las credenciales de PostgreSQL.
3. Ejecuta el instalador de permisos:

```powershell
php database/install_permissions.php
```

## Ejecutar localmente

Desde la carpeta del proyecto:

```powershell
php -S localhost:8000 -t public
```

Abre `http://localhost:8000` en el navegador.

La estructura del proyecto está documentada en [ARQUITECTURA.md](ARQUITECTURA.md).

El panel del asistente incluye gestión de estudiantes, apoderados, matrículas y pagos.