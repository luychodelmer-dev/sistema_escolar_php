# Arquitectura del proyecto

El proyecto usa una organización por responsabilidades. `public/` es la única carpeta que debe exponerse al navegador:

```text
Sistema_escolar/
├── app/                  # Núcleo de la aplicación PHP
│   ├── auth.php          # Autenticación, permisos y auditoría
│   ├── bootstrap.php     # Sesión y carga de configuración
│   └── header.php        # Encabezado institucional compartido
├── config/               # Configuración y conexión a PostgreSQL
│   ├── database.php      # Creación centralizada de PDO
│   └── database.local.php.example # Plantilla de configuración local
├── database/             # Instaladores y esquema de base de datos
│   └── install_permissions.php # Crea la tabla de permisos
├── frontend/             # Guía de las pantallas
├── public/               # Archivos visibles para el navegador
│   ├── login.php         # Entrada pública y autenticación
│   ├── asistente/        # Páginas protegidas del asistente
│   │   ├── estudiantes.php # Registro y consulta de estudiantes
│   │   ├── matriculas.php # Registro y consulta de matrículas
│   │   └── pagos.php      # Registro y consulta de pagos
│   └── assets/images/    # Imágenes públicas
├── login.php             # Puente de compatibilidad para el inicio
├── conexion.php          # Puente de compatibilidad para la conexión
└── .env.example
```

Las páginas protegidas están agrupadas por rol dentro de `public/`: `asistente/` para la operación diaria y `director/` para la administración.

## Configuración local

Copiar `config/database.local.php.example` como `config/database.local.php` y completar la contraseña de PostgreSQL, o definir las variables de `.env.example` en el entorno del servidor. El archivo local está excluido de Git. La conexión se centraliza en `config/database.php`.

## Próximos pasos recomendados

1. Completar `database/` con el esquema de las tablas restantes y ejecutarlo mediante una migración, en lugar de crear tablas durante una petición web.
2. Extraer la lógica de `public/asistente/padres.php`, `matriculas.php` y `pagos.php` a servicios/repositorios.
3. Mover los estilos y scripts embebidos a `public/assets/` cuando haya varias páginas que los compartan.
4. Añadir autorización por módulo más fina si aparecen nuevos roles o módulos.

## Permisos y auditoría

El usuario con rol `director` es quien otorga o revoca permisos desde `public/director/permisos.php`. Los permisos actuales son `p_editar`, `p_matricular` y `p_cobrar`.

La tabla `auditoria_movimientos` ya existía en PostgreSQL. Ahora registra inicios de sesión, cambios de permisos, ediciones de apoderados, matrículas y pagos. El director puede consultar los movimientos desde `/director/auditoria`.

## Alumnos, padres y apoderados

La relación familiar se modela separando a la persona responsable del vínculo con el escolar:

```text
alumnos
	└── alumno_responsable
				├── responsable: padre, parentesco "Padre"
				├── responsable: madre, parentesco "Madre"
				└── responsable: tercero, parentesco "Abuela", "Tío", etc.
```

Un alumno puede tener ambos padres registrados y exactamente un responsable marcado como apoderado. El apoderado puede ser el padre, la madre o un tercero. La restricción de un solo apoderado se aplica en PostgreSQL mediante un índice único parcial.

La relación familiar actual se mantiene en la tabla `apoderados`. Las pantallas correspondientes están en `public/asistente/`.
