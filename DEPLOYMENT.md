# Deployment y Migracion

## Fuente de Verdad

El codigo publicable vive en este repositorio. La produccion actual corre en:

- codigo activo: `/var/www/lawyers`
- document root: `/var/www/lawyers/public`

## Flujo Recomendado

1. Editar y versionar cambios en este repositorio.
2. Probar localmente o en un entorno controlado.
3. Ejecutar `bin/deploy-to-prod.sh` en modo simulacion.
4. Ejecutar `bin/deploy-to-prod.sh --apply` para desplegar a produccion sin borrar archivos del destino.
5. Validar Apache y endpoints criticos.

## Script de Deploy

Simulacion:

```bash
bin/deploy-to-prod.sh
```

Aplicar cambios:

```bash
bin/deploy-to-prod.sh --apply
```

Aplicar con espejo estricto y borrado de archivos sobrantes en destino:

```bash
bin/deploy-to-prod.sh --apply-delete
```

Por defecto sincroniza:

- desde `/root/lawyers-open-source`
- hacia `/var/www/lawyers`

Por seguridad, el deploy normal excluye documentacion del repo open-source y no borra archivos existentes del destino.

Puedes sobrescribir rutas:

```bash
SOURCE_DIR=/ruta/repo TARGET_DIR=/ruta/app bin/deploy-to-prod.sh --apply
```

## Script de Respaldo Desde Produccion

Tambien puedes sacar una copia privada del arbol de produccion:

```bash
bin/backup-prod-to-private.sh
bin/backup-prod-to-private.sh --apply
```

Por defecto respalda:

- desde `/var/www/lawyers`
- hacia `/root/lawyers-prod-backups/<timestamp>`

El respaldo excluye `vendor/`, backups historicos, zips, txt operativos y otros artefactos de ruido para dejar una copia privada mas limpia.

## Lo Que No Se Despliega Desde El Repo

Esto debe vivir fuera del repositorio o generarse en el host destino:

- `vendor/`
- `.env` y cualquier secreto real
- configuracion Apache/Nginx
- configuracion PHP-FPM
- backups
- logs
- dumps de base de datos
- temporales y archivos de trabajo

## Dependencias de Produccion Que Aun Debes Replicar En Otro Host

### Servicios

- Apache
- PHP
- Composer
- MySQL

### Configuracion de Aplicacion

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `RESEND_API_KEY`
- `RESEND_FROM_EMAIL`
- `RESEND_FROM_NAME`
- `ADMIN_ALERT_EMAILS`
- `APP_DEBUG`
- `SESSION_COOKIE_SECURE`
- `SESSION_COOKIE_SAMESITE`
- `LAWYERS_ADMIN_PASS` o equivalente si mantienes login admin por password

### Web Server

El host debe apuntar el document root a:

- `/ruta/deploy/public`

Y soportar rewrite para que `public/.htaccess` funcione en Apache.

## Migracion de Host

Checklist minimo:

1. Clonar el repositorio.
2. Ejecutar `composer install --no-dev`.
3. Crear variables de entorno reales en el nuevo host.
4. Restaurar la base de datos.
5. Apuntar Apache/Nginx a `public/`.
6. Validar rutas criticas y correo.

## Pendientes Para Una Migracion Aun Mas Limpia

- extraer o documentar por completo la configuracion admin basada en `LAWYERS_ADMIN_PASS`
- documentar esquema SQL inicial completo o migraciones adicionales
- revisar si todas las vistas admin/CRM deben quedar publicas
- definir un proceso de rotacion de secretos por entorno
