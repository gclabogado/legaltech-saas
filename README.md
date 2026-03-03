# Tu Estudio Juridico

Plantilla de aplicacion legal construida con PHP, Slim 4 y vistas server-rendered. Esta copia fue sanitizada para distribucion open source y no incluye secretos, configuracion privada de servidor ni artefactos operativos.

## Caracteristicas

- Frontend server-rendered con plantillas PHP
- Flujo publico, onboarding, panel profesional y vistas administrativas
- Integracion por variables de entorno para Google OAuth, correo y base de datos
- Estructura simple para desplegar sobre Apache con `public/` como document root

## Stack

- PHP 8+
- Slim 4
- MySQL
- Google OAuth
- Resend Email API

## Estructura

- `public/`: web root y entrypoint principal
- `src/`: controladores y servicios
- `templates/`: vistas PHP
- `bin/`: scripts de mantenimiento
- `app/`: utilidades auxiliares

## Requisitos

- PHP con PDO MySQL
- Composer
- Apache u otro servidor apuntando a `public/`
- Base de datos MySQL

## Instalacion

```bash
composer install
cp .env.example .env
```

Configura estas variables en tu entorno o servidor:

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

## Desarrollo Local

Apunta el document root de tu servidor local a `public/`.

Si usas Apache:

- habilita `mod_rewrite`
- conserva el archivo `public/.htaccess`

## Deploy

Este repositorio incluye un script de sincronizacion hacia produccion:

```bash
bin/deploy-to-prod.sh
bin/deploy-to-prod.sh --apply
```

Guia completa:

- [`DEPLOYMENT.md`](DEPLOYMENT.md)

## Notas

- Esta version usa branding generico: `Tu Estudio Juridico`
- Las referencias de dominio publico fueron reemplazadas por `example.com`
- `vendor/` no se incluye; debe regenerarse con Composer

## Seguridad

No publiques:

- credenciales reales
- `.env`
- backups
- logs
- dumps de base de datos
- configuracion privada del servidor

## Licencia

MIT. Ver [`LICENSE`](LICENSE).
