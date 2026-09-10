# Biofoods Paraguay — Sitio web (rediseño 2026)

Rediseño completo del e-commerce de **Biofoods Paraguay** — alimentos naturales, frutos secos y superfoods.
Sitio estático (HTML/CSS/JS vanilla, sin frameworks) pensado para GitHub Pages.

## Estructura

- `index.html` — Inicio (hero, categorías, destacados, combos, beneficios).
- `catalogo.html` — Catálogo completo con filtros por categoría, buscador en vivo y orden.
- `producto.html` — Detalle de producto (`?p=<handle>`): galería, variantes por tamaño, cantidad.
- `contacto.html` — Contacto, mapa, datos de depósito.
- `admin.html` — Panel de administración de pedidos (mockup/demo: login + tablero + pedidos).
- `css/styles.css` — Sistema de diseño (paleta verde bosque + crema + ámbar; Fraunces + Manrope).
- `js/catalog-data.js` — Catálogo (56 productos) embebido como `window.CATALOG`.
- `js/app.js` — Header/footer compartidos, carrito (localStorage) y checkout.
- `assets/img/` — Imágenes de producto y logo.

## Pedido / pago

Todo el pedido se hace **dentro del sitio** (sin salir a WhatsApp). El botón **Finalizar
pedido** del carrito lleva a `checkout.html`, donde el cliente carga sus datos, elige **Envío**
(con costo por ciudad + mapa para marcar la ubicación) o **Retiro** (gratis en el local),
ve el **total** y los datos de **depósito/transferencia** (Banco Sudameris, Cta. Cte. 5632542,
CENIN EAS), y **adjunta su comprobante de transferencia (obligatorio)**. Al confirmar se envía
**un email de confirmación al cliente** y el pedido queda en estado *Pendiente de verificación*.

> Demo vs producción: en el demo estático el email requiere claves de EmailJS y el comprobante
> se envía al negocio recién en producción (Hostinger, PHP). El pago con tarjeta (Bancard) y el
> registro de ventas en base de datos también son de la versión de producción.

### Configurar el email (EmailJS — gratis)

El envío de email usa [EmailJS](https://www.emailjs.com) (los sitios estáticos no pueden mandar
mails solos). En `js/app.js`, completar el objeto `EMAILJS` con:

1. **SERVICE_ID** — al agregar un "Email Service" (ej. el Gmail de info@biofoodspy.com).
2. **TEMPLATE_ID** — un "Email Template" que use las variables `to_email`, `customer_name`,
   `order_summary`, `delivery`, `shipping_cost`, `total`, `bank_info` (poner *To Email* = `{{to_email}}`).
3. **PUBLIC_KEY** — desde Account.

Mientras estén vacíos, el pedido igual se completa (confirmación en pantalla), solo que no se envía el email.

### Zonas de envío (editables en `js/app.js` → `SHIPPING`)

Asunción 20.000 · Fernando de la Mora 25.000 · Lambaré 25.000 · San Lorenzo 30.000 ·
Interior/Transportadora 30.000 · Luque 35.000 · Mariano R. Alonso 35.000 · Retiro: gratis.

## Desarrollo local

Cualquier servidor estático sirve. Por ejemplo:

```bash
npx serve .
```

## Crédito

Desarrollado por [@ivma.dv](https://www.instagram.com/ivma.dv/).
