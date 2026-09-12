# Backend de producción — Biofoods (Hostinger · PHP + MySQL)

Este `server/` contiene el backend para la versión en vivo del sitio (Hostinger). El sitio
público (HTML/CSS/JS) sigue siendo el mismo; estos archivos agregan: **guardar pedidos +
comprobante**, **emails de confirmación**, **panel de administración**, **reporte semanal** y
**pago con tarjeta (Bancard)**.

> ⚠️ Este código **todavía no fue probado en un servidor** (se escribió sin PHP local). Hay que
> desplegarlo en un **staging** de Hostinger y probarlo antes de salir en vivo.

## Estructura
```
server/
  config.sample.php   → copiar a config.php y completar (DB, correos, SMTP, admin, Bancard)
  schema.sql          → tabla de pedidos (se crea sola en el primer pedido)
  db.php              → conexión MySQL (PDO)
  save_order.php      → recibe el checkout (datos + comprobante), guarda y envía emails
  lib/helpers.php     → utilidades
  lib/mailer.php      → envío de email (SMTP con PHPMailer, o mail() de respaldo)
  admin/index.php     → panel de administración (login + pedidos + estados + CSV + comprobante)
  cron/weekly_report.php → reporte semanal por email
  bancard/create.php  → crea la operación de pago (devuelve process_id)
  bancard/confirm.php → webhook de confirmación de Bancard
  uploads/            → comprobantes (privado, .htaccess deniega acceso directo)
  analytics-snippet.html → GA4 + Meta Pixel para pegar en el <head>
```

## Pasos de despliegue
1. **Subir** la carpeta `server/` al hosting (dentro de `public_html`).
2. **Base de datos:** en hPanel → *Bases de datos MySQL*, crear una. Importar `schema.sql` (o se
   crea sola). Anotar host/nombre/usuario/contraseña.
3. **config:** copiar `config.sample.php` a `config.php` y completar DB, `business_email`,
   `report_recipients`, SMTP (casilla del dominio), y el hash del admin:
   ```
   php -r "echo password_hash('TU_CLAVE', PASSWORD_DEFAULT);"
   ```
4. **Emails:** crear la casilla `pedidos@biofoodspy.com` en Hostinger y cargar sus datos SMTP.
   (Opcional pero recomendado: subir **PHPMailer** a `server/lib/PHPMailer/` para envío por SMTP.)
5. **Frontend → backend:** en las páginas del sitio, definir la URL del backend antes de `app.js`:
   ```html
   <script>window.BIOFOODS_API = "/server";</script>
   <script src="js/app.js"></script>
   ```
   Con eso el checkout envía el pedido + comprobante a `save_order.php`. Sin esa línea, el sitio
   funciona en modo demo (confirmación en pantalla).
6. **Analítica:** pegar `analytics-snippet.html` en el `<head>` de todas las páginas y reemplazar
   el ID de GA4 y el de Meta Pixel.
7. **Reporte semanal:** hPanel → *Cron Jobs*, agregar (lunes 08:00):
   ```
   php /home/USUARIO/domains/biofoodspy.com/public_html/server/cron/weekly_report.php
   ```
8. **Bancard (tarjeta):** completar `bancard` en `config.php` con las claves. Probar primero en
   **staging** con tarjetas de prueba. Verificar campos/URLs contra la documentación de Bancard.
   El frontend debe: guardar el pedido → llamar a `bancard/create.php` → mostrar el iframe de
   Bancard con el `process_id` → Bancard confirma en `bancard/confirm.php`.

## Seguridad
- `config.php`, `uploads/` y `PHPMailer/` están en `.gitignore` (no se versionan).
- Los comprobantes solo se descargan desde el panel (con sesión).
- Las claves (Bancard, DB, SMTP) van **solo** en `config.php` del servidor.
