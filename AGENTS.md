# Instrucciones para el Agente

## REGLA IMPORTANTE: Idioma del Proyecto

**Todo el código, interfaces, textos visibles al usuario, mensajes, labels, botones, alertas, placeholders, y cualquier contenido textual del proyecto DEBE estar en Portugués Brasileiro (pt-BR).** El usuario se comunica en español, pero el producto final es para el mercado brasileño. Siempre traducir todo al portugués brasileño al escribir o modificar archivos del proyecto.

## REGLA IMPORTANTE: CSS del Dashboard

**NUNCA colocar código CSS inline dentro de archivos PHP del dashboard (ni en `<style>` dentro de los PHP).** Todo el CSS del dashboard DEBE estar en el archivo `dashboard/css/style.css`. Al crear o modificar estilos, siempre agregarlos al final de `style.css` y referenciarlo desde el PHP con `<link rel="stylesheet" href="css/style.css">`.

## Configuración del Proyecto

### Estructura de carpetas

- **Repositorio original**: `F:\Projeto-Web\calebitotransporte`
- **Copia local XAMPP**: `C:\xampp\htdocs\calebitotransporte`

### Servidor de producción (HostGator)

- **Config**: `/home/calebito/config.php` (ruta externa fuera del web root)
- **Usuario BD**: `calebito_admin`
- **Contraseña BD**: `ProdFeb10**`
- **Rutas de imágenes en dashboard**: `../../img/` (funciona en HostGator porque la estructura de carpetas es más profunda)
- **Archivos PHP que usan require de config**: `login.php`, `forgot.php`, `reset.php`, `process_register.php` — todos referencian `require '/home/calebito/config.php'`

### Configuración local XAMPP

- **Config**: `config.php` en la raíz del proyecto
- **Usuario BD**: `root`
- **Contraseña BD**: (vacía)
- **Rutas de imágenes en dashboard**: `../img/` (desde `dashboard/` solo se sube un nivel)
- **Ruta config PHP**: `require __DIR__ . '/../config.php'`
- **Puerto Apache**: 80
- **Puerto MySQL**: 3306

### REGLA IMPORTANTE: Rutas de imágenes

**NUNCA modificar los archivos originales del repositorio** para arreglar rutas de imágenes. Las diferencias son:

| Archivo | Repositorio (HostGator) | XAMPP (local) |
|---------|------------------------|---------------|
| `dashboard/index.php` | `../../img/` | `../img/` |
| `dashboard/users.php` | `../../img/` | `../img/` |
| `dashboard/register.php` | `../../img/` | `../img/` |
| `dashboard/users_consultar.php` | `../../img/` | `../img/` |
| Cualquier archivo futuro en `dashboard/` | `../../img/` | `../img/` |

**Cuando se cree un archivo nuevo en `dashboard/`**:
1. En el repositorio original, usar `../../img/` para favicons e imágenes
2. En la copia de XAMPP, ajustar a `../img/`
3. NUNCA editar el original para que funcione en local

**IMPORTANTE: Para probar en el servidor local XAMPP**:
Siempre copiar los archivos modificados del repositorio a la carpeta de XAMPP:
```powershell
Copy-Item "F:\Projeto-Web\calebitotransporte\ruta\archivo.php" "C:\xampp\htdocs\calebitotransporte\ruta\archivo.php" -Force
```
Después de copiar, ajustar las rutas de imágenes de `../../img/` a `../img/` solo en la copia de XAMPP, NUNCA en el original.

### REGLA IMPORTANTE: Require de config.php

**NUNCA modificar los archivos originales del repositorio** para cambiar el require de config. Las diferencias son:

| Archivo | Repositorio (HostGator) | XAMPP (local) |
|---------|------------------------|---------------|
| `auth/login.php` | `require '/home/calebito/config.php'` | `require __DIR__ . '/../config.php'` |
| `auth/forgot.php` | `require '/home/calebito/config.php'` | `require __DIR__ . '/../config.php'` |
| `auth/reset.php` | `require '/home/calebito/config.php'` | `require __DIR__ . '/../config.php'` |
| `dashboard/process_register.php` | `require '/home/calebito/config.php'` | `require __DIR__ . '/../config.php'` |
| Cualquier archivo futuro con config | `require '/home/calebito/config.php'` | `require __DIR__ . '/../config.php'` |

**Cuando se copie un archivo con require de config a XAMPP**:
1. El original DEBE mantener `require '/home/calebito/config.php'` para HostGator
2. En la copia de XAMPP, cambiar a `require __DIR__ . '/../config.php'`
3. NUNCA editar el original para que funcione en local

### Servicios XAMPP

- Apache se inicia con: `Start-Process "C:\xampp\apache\bin\httpd.exe"`
- MySQL se inicia con: `Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList @("--defaults-file=C:\xampp\mysql\bin\my.ini", "mysql")`
- MySQL importar BD: `Get-Content "archivo.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root nombre_bd`
- PHP en XAMPP: `C:\xampp\php\php.exe`
- MySQL en XAMPP: `C:\xampp\mysql\bin\mysql.exe`

### Base de datos

- **Nombre**: `calebito_transporte_db`
- **Tablas**: destinations, maintenance_alerts, maintenance_items, notifications, route_assignments, routes, stores, task_assignments, tasks, users, vehicle_maintenances, vehicle_usages, vehicles
- **Usuario de prueba local**: `admin@test.com` / `password`

### Usuario de prueba (local XAMPP)

- Email: `admin@test.com`
- Contraseña: `password`
- Rol: `gestor_logistica`
- IMPORTANTE: Usar PHP para generar el hash de contraseña, PowerShell corrompe los `$` en el hash:
  ```php
  & "C:\xampp\php\php.exe" -r "echo password_hash('password', PASSWORD_DEFAULT);"
  ```
  Luego insertar con PHP:
  ```php
  $conn = new mysqli('localhost', 'root', '', 'calebitotransporte_db');
  $stmt = $conn->prepare('UPDATE users SET password_hash=? WHERE id=10');
  $stmt->bind_param('s', $hash);
  $stmt->execute();
  ```

### GitHub

- **Repositorio**: https://github.com/orlandomartinezdeveloper/logistica-db.git
- **Rama principal**: main

### REGLA IMPORTANTE: Fotos subidas por usuarios

Las fotos se guardan en `img/users/` (o futuras carpetas como `img/vehicles/`, `img/stores/`, etc.).

**En XAMPP** las fotos se guardan en:
`C:\xampp\htdocs\calebitotransporte\img\users\`

**En el repositorio** están en:
`F:\Projeto-Web\calebitotransporte\img\users\`

XAMPP ejecuta PHP desde `C:\xampp\htdocs\`, NO desde `F:\Projeto-Web\`. Por eso las fotos subidas localmente NO aparecen automáticamente en el repositorio.

**Cuando el usuario suba una foto y la necesite en el repositorio** (para subir a HostGator), copiar de XAMPP al repo:
```powershell
Copy-Item "C:\xampp\htdocs\calebitotransporte\img\users\*" "F:\Projeto-Web\calebitotransporte\img\users\" -Force
```

**En HostGator** no hay problema: el código corre desde la raíz del proyecto, así que las fotos se guardan directamente en `img/users/` del servidor.
