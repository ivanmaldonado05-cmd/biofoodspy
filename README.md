# Biofoods Paraguay — Sitio web (rediseño 2026)

Rediseño completo del e-commerce de **Biofoods Paraguay** — alimentos naturales, frutos secos y superfoods.
Sitio estático (HTML/CSS/JS vanilla, sin frameworks) pensado para GitHub Pages.

## Estructura

- `index.html` — Inicio (hero, categorías, destacados, combos, beneficios).
- `catalogo.html` — Catálogo completo con filtros por categoría, buscador en vivo y orden.
- `producto.html` — Detalle de producto (`?p=<handle>`): galería, variantes por tamaño, cantidad.
- `contacto.html` — Contacto, mapa, datos de depósito.
- `css/styles.css` — Sistema de diseño (paleta verde bosque + crema + ámbar; Fraunces + Manrope).
- `js/catalog-data.js` — Catálogo (56 productos) embebido como `window.CATALOG`.
- `js/app.js` — Header/footer compartidos, carrito (localStorage) y checkout por WhatsApp.
- `assets/img/` — Imágenes de producto y logo.

## Pedido / pago

No hay pasarela de pago online (igual que el sitio original en Shopify): el checkout arma un
pedido y se coordina por **WhatsApp** con **pago por depósito/transferencia bancaria**
(Banco Sudameris, Cta. Cte. 5632542, CENIN EAS).

## Desarrollo local

Cualquier servidor estático sirve. Por ejemplo:

```bash
npx serve .
```

## Crédito

Desarrollado por [@ivma.dv](https://www.instagram.com/ivma.dv/).
