/* =========================================================
   Biofoods Paraguay — App logic
   Shared UI, cart (localStorage) + WhatsApp checkout
   ========================================================= */
(function () {
  "use strict";

  /* ---------- Config ---------- */
  var WA_NUMBER = "595986924545";
  var EMAIL = "info@biofoodspy.com";
  var ADDRESS = "Herminio Giménez 1919 e/ Gral. Aquino y Gral. Bruguez, Bº Ciudad Nueva, Asunción";
  var IG = "https://www.instagram.com/biofoods.py/";
  var FB = "https://www.facebook.com/Biofoods-Paraguay-100071634172903/";
  var BANK = { banco: "Banco Sudameris", cuenta: "Cta. Cte. 5632542", titular: "CENIN EAS", ci: "C.I. 80142067-9", alias: "801420679" };

  var CATS = [
    { slug: "frutos-secos", label: "Frutos Secos", emoji: "🥜" },
    { slug: "mixes", label: "Mixes", emoji: "🥣" },
    { slug: "deshidratadas", label: "Deshidratadas", emoji: "🍇" },
    { slug: "harinas", label: "Harinas", emoji: "🌾" },
    { slug: "semillas", label: "Semillas", emoji: "🌱" },
    { slug: "superfoods", label: "Superfoods", emoji: "✨" },
    { slug: "chocolates", label: "Chocolates", emoji: "🍫" },
    { slug: "leche-endulzantes", label: "Leche y Endulzantes", emoji: "🥥" },
    { slug: "combos", label: "Combos", emoji: "🎁" }
  ];
  function catLabel(slug){ var c = CATS.find(function(x){return x.slug===slug;}); return c?c.label:slug; }

  var CATALOG = window.CATALOG || [];

  /* ---------- Helpers ---------- */
  function $(s, c){ return (c||document).querySelector(s); }
  function $$(s, c){ return Array.prototype.slice.call((c||document).querySelectorAll(s)); }
  function el(html){ var t=document.createElement("template"); t.innerHTML=html.trim(); return t.content.firstChild; }
  function money(n){ if(!n||n<=0) return "A consultar"; return "Gs. " + Number(n).toLocaleString("de-DE"); }
  function minPrice(p){ var ps=p.variants.map(function(v){return v.price;}).filter(function(x){return x>0;}); return ps.length?Math.min.apply(null,ps):0; }
  function multiPrice(p){ return p.variants.length>1; }
  function byHandle(h){ return CATALOG.find(function(p){return p.handle===h;}); }
  function esc(s){ return (s||"").replace(/[&<>"]/g,function(c){return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c];}); }

  /* ---------- SVG icons ---------- */
  var IC = {
    cart:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
    menu:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    close:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    arrow:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
    search:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    wa:'<svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.8 14.13c-.25.7-1.44 1.33-1.99 1.41-.51.08-1.15.11-1.86-.12-.43-.14-.98-.32-1.68-.62-2.96-1.28-4.9-4.26-5.05-4.46-.15-.2-1.2-1.6-1.2-3.06s.76-2.16 1.03-2.46c.27-.3.59-.37.79-.37.2 0 .39 0 .56.01.18.01.42-.07.66.5.25.6.83 2.06.9 2.21.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.45.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.39-.25.66-.15.27.1 1.72.81 2.01.96.3.15.5.22.57.35.07.12.07.72-.18 1.42z"/></svg>',
    pin:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    phone:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    mail:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>',
    check:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    truck:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    trash:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
    ig:'<svg viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 1.62c-3.15 0-3.5.01-4.74.07-.85.04-1.31.18-1.62.3-.41.16-.7.35-1 .66-.31.3-.5.59-.66 1-.12.31-.26.77-.3 1.62-.06 1.24-.07 1.59-.07 4.74s.01 3.5.07 4.74c.04.85.18 1.31.3 1.62.16.41.35.7.66 1 .3.31.59.5 1 .66.31.12.77.26 1.62.3 1.24.06 1.59.07 4.74.07s3.5-.01 4.74-.07c.85-.04 1.31-.18 1.62-.3.41-.16.7-.35 1-.66.31-.3.5-.59.66-1 .12-.31.26-.77.3-1.62.06-1.24.07-1.59.07-4.74s-.01-3.5-.07-4.74c-.04-.85-.18-1.31-.3-1.62a2.7 2.7 0 0 0-.66-1 2.7 2.7 0 0 0-1-.66c-.31-.12-.77-.26-1.62-.3-1.24-.06-1.59-.07-4.74-.07zM12 6.87A5.13 5.13 0 1 0 12 17.13 5.13 5.13 0 0 0 12 6.87zm0 8.46A3.33 3.33 0 1 1 12 8.67a3.33 3.33 0 0 1 0 6.66zm6.54-8.66a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0z"/></svg>',
    fb:'<svg viewBox="0 0 24 24"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/></svg>'
  };

  /* ================= CART ================= */
  var CART_KEY = "biofoods_cart_v1";
  function loadCart(){ try{ return JSON.parse(localStorage.getItem(CART_KEY))||[]; }catch(e){ return []; } }
  function saveCart(c){ try{ localStorage.setItem(CART_KEY, JSON.stringify(c)); }catch(e){} }
  var cart = loadCart();

  function cartCount(){ return cart.reduce(function(s,i){return s+i.qty;},0); }
  function cartTotal(){ return cart.reduce(function(s,i){return s+i.price*i.qty;},0); }
  function findLine(h,s){ return cart.find(function(i){return i.handle===h && i.size===s;}); }

  function addToCart(handle, size, price, qty){
    qty = qty||1;
    var line = findLine(handle,size);
    if(line){ line.qty += qty; } else { cart.push({handle:handle, size:size, price:price, qty:qty}); }
    saveCart(cart); syncCart(); openDrawer();
  }
  function setQty(handle,size,qty){
    var line = findLine(handle,size);
    if(!line) return;
    line.qty = qty;
    if(line.qty<=0){ cart = cart.filter(function(i){return !(i.handle===handle&&i.size===size);}); }
    saveCart(cart); syncCart();
  }
  function removeLine(handle,size){ cart = cart.filter(function(i){return !(i.handle===handle&&i.size===size);}); saveCart(cart); syncCart(); }
  function clearCart(){ cart = []; saveCart(cart); syncCart(); }

  function syncCart(){
    var count = cartCount();
    $$(".cart-count").forEach(function(b){ b.textContent=count; b.classList.toggle("show", count>0); });
    renderDrawer();
  }

  /* ================= SHARED UI ================= */
  function navLinksHTML(active){
    var links = [{h:"index.html",t:"Inicio",p:"home"},{h:"catalogo.html",t:"Catálogo",p:"catalog"}]
      .concat(CATS.slice(0,6).map(function(c){return {h:"catalogo.html?cat="+c.slug,t:c.label,p:"cat-"+c.slug};}))
      .concat([{h:"contacto.html",t:"Contacto",p:"contact"}]);
    return links.map(function(l){
      var a = (active===l.p)?" class=\"active\"":"";
      return '<li><a href="'+l.h+'"'+a+'>'+l.t+'</a></li>';
    }).join("");
  }

  function buildHeader(){
    var active = document.body.getAttribute("data-page")||"";
    var root = $("#header-root"); if(!root) return;
    root.innerHTML =
      '<div class="announce">🥜 <span>Bienvenido a Biofoods — alimentos naturales, frutos secos y superfoods</span></div>'+
      '<header class="site-header" id="siteHeader"><div class="container header-bar">'+
        '<a class="brand" href="index.html" aria-label="Biofoods Paraguay inicio">'+
          '<img src="assets/img/logo-black.png" alt="Biofoods Paraguay">'+
          '<span class="brand__text"><span class="brand__name">Biofoods</span><span class="brand__tag">Paraguay</span></span>'+
        '</a>'+
        '<nav class="main-nav" aria-label="Principal"><ul>'+ navLinksHTML(active) +'</ul></nav>'+
        '<div class="header-actions">'+
          '<button class="icon-btn" id="cartBtn" aria-label="Abrir carrito">'+IC.cart+'<span class="cart-count" aria-hidden="true">0</span></button>'+
          '<button class="icon-btn nav-toggle" id="navToggle" aria-label="Abrir menú">'+IC.menu+'</button>'+
        '</div>'+
      '</div></header>';

    // mobile nav
    var mnav = el(
      '<div class="mobile-nav" id="mobileNav" aria-hidden="true">'+
        '<div class="mobile-nav__top"><a class="brand" href="index.html"><img src="assets/img/logo-black.png" alt="Biofoods"><span class="brand__text"><span class="brand__name">Biofoods</span><span class="brand__tag">Paraguay</span></span></a>'+
        '<button class="icon-btn" id="mnavClose" aria-label="Cerrar menú">'+IC.close+'</button></div>'+
        '<ul>'+ navLinksHTML(active) +'</ul>'+
        '<div class="mobile-nav__footer"><a class="btn btn--wa btn--block" href="'+waLink("Hola Biofoods! Quiero hacer una consulta 🥜")+'" target="_blank" rel="noopener">'+IC.wa+' Escribir por WhatsApp</a></div>'+
      '</div>');
    document.body.appendChild(mnav);

    // scroll shrink
    var header = $("#siteHeader");
    var onScroll = function(){ header.classList.toggle("scrolled", window.scrollY>10); };
    window.addEventListener("scroll", onScroll, {passive:true}); onScroll();

    $("#cartBtn").addEventListener("click", openDrawer);
    $("#navToggle").addEventListener("click", function(){ mnav.classList.add("open"); document.body.classList.add("no-scroll"); });
    $("#mnavClose").addEventListener("click", function(){ mnav.classList.remove("open"); document.body.classList.remove("no-scroll"); });
    $$("#mobileNav a").forEach(function(a){ a.addEventListener("click", function(){ mnav.classList.remove("open"); document.body.classList.remove("no-scroll"); }); });
  }

  function buildFooter(){
    var root = $("#footer-root"); if(!root) return;
    root.innerHTML =
      '<footer class="site-footer"><div class="container">'+
        '<div class="footer-grid">'+
          '<div class="footer-brand">'+
            '<img src="assets/img/logo-black.png" alt="Biofoods Paraguay">'+
            '<p>Venta mayorista y minorista de alimentos saludables, frutos secos y superfoods en Asunción, Paraguay.</p>'+
            '<div class="footer-social"><a href="'+IG+'" target="_blank" rel="noopener" aria-label="Instagram">'+IC.ig+'</a><a href="'+FB+'" target="_blank" rel="noopener" aria-label="Facebook">'+IC.fb+'</a></div>'+
          '</div>'+
          '<div class="footer-col"><h4>Tienda</h4><ul>'+
            '<li><a href="catalogo.html">Ver catálogo</a></li>'+
            CATS.map(function(c){return '<li><a href="catalogo.html?cat='+c.slug+'">'+c.label+'</a></li>';}).join("")+
          '</ul></div>'+
          '<div class="footer-col"><h4>Información</h4><ul>'+
            '<li><a href="contacto.html">Contacto</a></li>'+
            '<li><a href="https://biofoodspy.com/policies/shipping-policy" target="_blank" rel="noopener">Política de Envíos</a></li>'+
            '<li><a href="https://biofoodspy.com/policies/refund-policy" target="_blank" rel="noopener">Política de Reembolsos</a></li>'+
            '<li><a href="https://biofoodspy.com/policies/privacy-policy" target="_blank" rel="noopener">Privacidad</a></li>'+
            '<li><a href="https://biofoodspy.com/policies/terms-of-service" target="_blank" rel="noopener">Términos</a></li>'+
          '</ul></div>'+
          '<div class="footer-col"><h4>Contacto</h4><ul class="footer-contact">'+
            '<li>'+IC.pin+'<span>'+ADDRESS+'</span></li>'+
            '<li>'+IC.phone+'<a href="'+waLink("Hola Biofoods!")+'" target="_blank" rel="noopener">+595 986 924545</a></li>'+
            '<li>'+IC.mail+'<a href="mailto:'+EMAIL+'">'+EMAIL+'</a></li>'+
          '</ul></div>'+
        '</div>'+
        '<div class="footer-bottom"><span>© '+(new Date().getFullYear())+' Biofoods Paraguay. Todos los derechos reservados.</span>'+
        '<span>Desarrollado por <a href="https://www.instagram.com/ivma.dv/" target="_blank" rel="noopener" style="color:var(--amber);font-weight:700">@ivma.dv</a></span></div>'+
      '</div></footer>';
  }

  function buildDrawer(){
    var overlay = el('<div class="overlay" id="overlay"></div>');
    var drawer = el(
      '<aside class="drawer" id="drawer" aria-label="Carrito">'+
        '<div class="drawer__head"><h3>Tu pedido</h3><button class="icon-btn" id="drawerClose" aria-label="Cerrar">'+IC.close+'</button></div>'+
        '<div class="drawer__body" id="drawerBody"></div>'+
        '<div class="drawer__foot" id="drawerFoot"></div>'+
      '</aside>');
    document.body.appendChild(overlay); document.body.appendChild(drawer);
    overlay.addEventListener("click", closeDrawer);
    $("#drawerClose").addEventListener("click", closeDrawer);
  }

  function renderDrawer(){
    var body = $("#drawerBody"), foot = $("#drawerFoot");
    if(!body) return;
    if(!cart.length){
      body.innerHTML = '<div class="cart-empty"><span>🛒</span><p>Tu carrito está vacío.</p><p style="margin-top:.4rem">Explorá nuestros productos naturales.</p></div>';
      foot.innerHTML = '<a class="btn btn--block" href="catalogo.html">Ver catálogo</a>';
      return;
    }
    body.innerHTML = cart.map(function(i){
      var p = byHandle(i.handle)||{title:i.handle,images:[]};
      var img = (p.images&&p.images[0])||"assets/img/logo-black.png";
      return '<div class="cart-item">'+
        '<img src="'+img+'" alt="'+esc(p.title)+'">'+
        '<div><div class="cart-item__t">'+esc(p.title)+'</div><div class="cart-item__s">'+esc(i.size)+'</div>'+
          '<div class="cart-item__ctrl"><div class="miniqty"><button data-act="dec" data-h="'+i.handle+'" data-s="'+esc(i.size)+'" aria-label="Menos">−</button><span>'+i.qty+'</span><button data-act="inc" data-h="'+i.handle+'" data-s="'+esc(i.size)+'" aria-label="Más">+</button></div></div>'+
        '</div>'+
        '<div><div class="cart-item__price">'+money(i.price*i.qty)+'</div><button class="cart-item__rm" data-act="rm" data-h="'+i.handle+'" data-s="'+esc(i.size)+'">Quitar</button></div>'+
      '</div>';
    }).join("");
    foot.innerHTML =
      '<div class="drawer__total"><span>Total</span><b>'+money(cartTotal())+'</b></div>'+
      '<button class="btn btn--wa btn--block btn--lg" id="checkoutBtn">'+IC.wa+' Finalizar por WhatsApp</button>'+
      '<button class="drawer__clear" id="clearCartBtn">'+IC.trash+' Vaciar carrito</button>'+
      '<p class="drawer__note">Coordinás el pago por depósito bancario al confirmar.</p>';
    // bind
    $$("#drawerBody [data-act]").forEach(function(b){
      b.addEventListener("click", function(){
        var h=b.getAttribute("data-h"), s=b.getAttribute("data-s"), act=b.getAttribute("data-act");
        var line=findLine(h,s); if(!line && act!=="rm") return;
        if(act==="inc") setQty(h,s,line.qty+1);
        else if(act==="dec") setQty(h,s,line.qty-1);
        else if(act==="rm") removeLine(h,s);
      });
    });
    $("#checkoutBtn").addEventListener("click", openCheckout);
    $("#clearCartBtn").addEventListener("click", function(){ clearCart(); });
  }

  function openDrawer(){ $("#drawer").classList.add("open"); $("#overlay").classList.add("open"); document.body.classList.add("no-scroll"); }
  function closeDrawer(){ $("#drawer").classList.remove("open"); $("#overlay").classList.remove("open"); document.body.classList.remove("no-scroll"); }

  /* ================= CHECKOUT MODAL ================= */
  function buildModal(){
    var modal = el(
      '<div class="modal" id="checkoutModal" role="dialog" aria-modal="true" aria-label="Finalizar pedido">'+
        '<div class="modal__box" style="position:relative">'+
          '<button class="icon-btn modal__close" id="modalClose" aria-label="Cerrar">'+IC.close+'</button>'+
          '<h3>Finalizá tu pedido</h3>'+
          '<p class="sub">Completá tus datos y te enviamos a WhatsApp con el resumen listo.</p>'+
          '<div class="field"><label>¿Cómo lo recibís?</label>'+
            '<div class="toggle-group"><button type="button" class="toggle-opt active" data-deliv="Envío a domicilio">Envío<small>1 a 7 días hábiles</small></button>'+
            '<button type="button" class="toggle-opt" data-deliv="Retiro en local">Retiro<small>En nuestro local</small></button></div></div>'+
          '<div class="field"><label for="ckName">Nombre y apellido</label><input id="ckName" type="text" placeholder="Tu nombre" autocomplete="name"></div>'+
          '<div class="field" id="addrField"><label for="ckAddr">Dirección de envío / ciudad</label><input id="ckAddr" type="text" placeholder="Calle, barrio, ciudad" autocomplete="street-address"></div>'+
          '<div class="field"><label for="ckNote">Nota (opcional)</label><input id="ckNote" type="text" placeholder="Alguna aclaración"></div>'+
          '<div class="deposit-box"><b>Pago por depósito / transferencia bancaria</b>'+
            '<div class="row"><span>'+BANK.banco+'</span><span>'+BANK.cuenta+'</span></div>'+
            '<div class="row"><span>Titular</span><span>'+BANK.titular+'</span></div>'+
            '<div class="row"><span>'+BANK.ci+'</span><span>Alias: '+BANK.alias+'</span></div>'+
          '</div>'+
          '<button class="btn btn--wa btn--block btn--lg" id="sendWa">'+IC.wa+' Enviar pedido por WhatsApp</button>'+
        '</div>'+
      '</div>');
    document.body.appendChild(modal);
    var deliv = "Envío a domicilio";
    $$("#checkoutModal .toggle-opt").forEach(function(b){
      b.addEventListener("click", function(){
        $$("#checkoutModal .toggle-opt").forEach(function(x){x.classList.remove("active");});
        b.classList.add("active"); deliv=b.getAttribute("data-deliv");
        $("#addrField").style.display = /Retiro/.test(deliv)?"none":"block";
      });
    });
    $("#modalClose").addEventListener("click", closeCheckout);
    $("#checkoutModal").addEventListener("click", function(e){ if(e.target.id==="checkoutModal") closeCheckout(); });
    $("#sendWa").addEventListener("click", function(){
      var name = $("#ckName").value.trim();
      var addr = $("#ckAddr").value.trim();
      var note = $("#ckNote").value.trim();
      sendWhatsAppOrder(deliv, name, addr, note);
    });
    window.__getDeliv = function(){ return deliv; };
  }

  function openCheckout(){ if(!cart.length) return; closeDrawer(); $("#checkoutModal").classList.add("open"); document.body.classList.add("no-scroll"); }
  function closeCheckout(){ $("#checkoutModal").classList.remove("open"); document.body.classList.remove("no-scroll"); }

  function waLink(text){ return "https://wa.me/"+WA_NUMBER+"?text="+encodeURIComponent(text); }

  function sendWhatsAppOrder(deliv, name, addr, note){
    var lines = ["*Nuevo pedido — Biofoods* 🥜",""];
    if(name) lines.push("👤 *Cliente:* "+name);
    lines.push("📦 *Entrega:* "+deliv);
    if(!/Retiro/.test(deliv) && addr) lines.push("📍 *Dirección:* "+addr);
    if(note) lines.push("📝 *Nota:* "+note);
    lines.push("","*Detalle del pedido:*");
    cart.forEach(function(i){
      var p = byHandle(i.handle)||{title:i.handle};
      lines.push("• "+i.qty+"× "+p.title+" ("+i.size+") — "+money(i.price*i.qty));
    });
    lines.push("","💰 *Total: "+money(cartTotal())+"*");
    lines.push("","Pago por depósito: "+BANK.banco+" "+BANK.cuenta+" ("+BANK.titular+")");
    window.open(waLink(lines.join("\n")), "_blank");
  }

  /* ================= WhatsApp float + toast ================= */
  function buildFloat(){
    var f = el('<a class="wa-float" href="'+waLink("Hola Biofoods! Te escribo desde la tienda online 🥜")+'" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">'+IC.wa+'</a>');
    document.body.appendChild(f);
    var toast = el('<div class="toast" id="toast"></div>'); document.body.appendChild(toast);
  }
  var toastTimer;
  function showToast(msg){
    var t=$("#toast"); if(!t) return;
    t.innerHTML = IC.check+"<span>"+esc(msg)+"</span>";
    t.classList.add("show"); clearTimeout(toastTimer);
    toastTimer=setTimeout(function(){t.classList.remove("show");},2200);
  }

  /* ================= REVEAL ================= */
  function initReveal(){
    var els = $$("[data-reveal]");
    if(!("IntersectionObserver" in window)){ els.forEach(function(e){e.classList.add("in");}); return; }
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if(en.isIntersecting){ en.target.classList.add("in"); io.unobserve(en.target); } });
    },{threshold:.12, rootMargin:"0px 0px -8% 0px"});
    els.forEach(function(e){ io.observe(e); });
  }

  /* ================= CARD TEMPLATE ================= */
  function cardHTML(p){
    var mp = minPrice(p);
    var img = (p.images&&p.images[0])||"assets/img/logo-black.png";
    var priceLbl = multiPrice(p) ? "Desde" : "Precio";
    return '<article class="card" data-reveal>'+
      '<a class="card__media" href="producto.html?p='+p.handle+'">'+
        '<span class="card__cat">'+catLabel(p.category)+'</span>'+
        '<img loading="lazy" src="'+img+'" alt="'+esc(p.title)+'">'+
      '</a>'+
      '<div class="card__body">'+
        '<a href="producto.html?p='+p.handle+'"><h3 class="card__title">'+esc(p.title)+'</h3></a>'+
        (p.description?'<p class="card__desc">'+esc(p.description)+'</p>':'<p class="card__desc">'+catLabel(p.category)+' natural de primera calidad.</p>')+
        '<div class="card__foot">'+
          '<div class="card__price"><small>'+priceLbl+'</small><b>'+money(mp)+'</b></div>'+
          '<a class="card__add" href="producto.html?p='+p.handle+'" aria-label="Ver '+esc(p.title)+'">'+IC.arrow+'</a>'+
        '</div>'+
      '</div>'+
    '</article>';
  }

  /* ================= PAGE: HOME ================= */
  function initHome(){
    // categories
    var catRoot = $("#homeCats");
    if(catRoot){
      catRoot.innerHTML = CATS.map(function(c,idx){
        var sample = CATALOG.find(function(p){return p.category===c.slug;});
        var img = sample?sample.images[0]:"assets/img/logo-black.png";
        var count = CATALOG.filter(function(p){return p.category===c.slug;}).length;
        return '<a class="cat-tile" href="catalogo.html?cat='+c.slug+'" data-reveal data-delay="'+(idx%4)+'">'+
          '<img loading="lazy" src="'+img+'" alt="'+c.label+'">'+
          '<span class="arrow">'+IC.arrow+'</span>'+
          '<b>'+c.label+'</b><small>'+count+' productos</small></a>';
      }).join("");
    }
    // featured (a curated selection)
    var featRoot = $("#homeFeatured");
    if(featRoot){
      var picks = ["mix-keto","almendras-banadas-en-chocolate-sin-azucar","castanas-de-caju","datiles-con-carozo","spirulina-100-puro","nuez-mariposa","arandanos-rojos","harina-de-almendras-sin-piel"];
      var feat = picks.map(byHandle).filter(Boolean);
      featRoot.innerHTML = feat.map(cardHTML).join("");
    }
    // combos
    var comboRoot = $("#homeCombos");
    if(comboRoot){
      var combos = CATALOG.filter(function(p){return p.category==="combos";}).slice(0,4);
      comboRoot.innerHTML = combos.map(cardHTML).join("");
    }
    initReveal();
  }

  /* ================= PAGE: CATALOG ================= */
  function initCatalog(){
    var grid = $("#catalogGrid"); if(!grid) return;
    var params = new URLSearchParams(location.search);
    var state = { cat: params.get("cat")||"all", q:"", sort:"featured" };

    var chipsRoot = $("#catChips");
    chipsRoot.innerHTML = '<button class="chip" data-cat="all">Todos <span class="n">'+CATALOG.length+'</span></button>'+
      CATS.map(function(c){ var n=CATALOG.filter(function(p){return p.category===c.slug;}).length;
        return '<button class="chip" data-cat="'+c.slug+'">'+c.label+' <span class="n">'+n+'</span></button>'; }).join("");

    var searchInput = $("#catSearch");
    var sortSel = $("#catSort");

    function apply(){
      $$("#catChips .chip").forEach(function(ch){ ch.classList.toggle("active", ch.getAttribute("data-cat")===state.cat); });
      var list = CATALOG.slice();
      if(state.cat!=="all") list = list.filter(function(p){return p.category===state.cat;});
      if(state.q){ var q=state.q.toLowerCase(); list = list.filter(function(p){ return (p.title+" "+p.description+" "+catLabel(p.category)).toLowerCase().indexOf(q)>-1; }); }
      if(state.sort==="price-asc") list.sort(function(a,b){return minPrice(a)-minPrice(b);});
      else if(state.sort==="price-desc") list.sort(function(a,b){return minPrice(b)-minPrice(a);});
      else if(state.sort==="az") list.sort(function(a,b){return a.title.localeCompare(b.title);});

      var title = state.cat==="all"?"Todo el catálogo":catLabel(state.cat);
      $("#catTitle").textContent = title;
      $("#catResult").textContent = list.length+" producto"+(list.length!==1?"s":"")+(state.q?' para "'+state.q+'"':"");

      if(!list.length){ grid.innerHTML='<div class="empty-state"><span>🔍</span><p>No encontramos productos con esos filtros.</p></div>'; return; }
      grid.innerHTML = list.map(cardHTML).join("");
      initReveal();
    }

    $$("#catChips .chip").forEach(function(ch){
      ch.addEventListener("click", function(){
        state.cat = ch.getAttribute("data-cat");
        var url = new URL(location); if(state.cat==="all") url.searchParams.delete("cat"); else url.searchParams.set("cat",state.cat);
        history.replaceState(null,"",url); apply();
      });
    });
    var t;
    searchInput.addEventListener("input", function(){ clearTimeout(t); t=setTimeout(function(){ state.q=searchInput.value.trim(); apply(); },160); });
    sortSel.addEventListener("change", function(){ state.sort=sortSel.value; apply(); });
    apply();
  }

  /* ================= PAGE: PRODUCT ================= */
  function initProduct(){
    var root = $("#productRoot"); if(!root) return;
    var handle = new URLSearchParams(location.search).get("p");
    var p = byHandle(handle);
    if(!p){ root.innerHTML='<div class="container section empty-state"><span>😕</span><p>Producto no encontrado.</p><a class="btn" href="catalogo.html" style="margin-top:1rem">Volver al catálogo</a></div>'; initReveal(); return; }
    document.title = p.title + " — Biofoods Paraguay";

    var selVar = p.variants[0];
    var imgs = p.images.length?p.images:["assets/img/logo-black.png"];

    root.innerHTML =
      '<div class="container section"><div class="pd-grid">'+
        '<div class="pd-gallery" data-reveal>'+
          '<div class="pd-main"><img id="pdMainImg" src="'+imgs[0]+'" alt="'+esc(p.title)+'"></div>'+
          (imgs.length>1?'<div class="pd-thumbs">'+imgs.map(function(im,i){return '<button class="'+(i===0?"active":"")+'" data-img="'+im+'"><img src="'+im+'" alt="'+esc(p.title)+' '+(i+1)+'"></button>';}).join("")+'</div>':'')+
        '</div>'+
        '<div class="pd-info" data-reveal data-delay="1">'+
          '<div class="breadcrumb"><a href="index.html">Inicio</a> / <a href="catalogo.html">Catálogo</a> / <a href="catalogo.html?cat='+p.category+'">'+catLabel(p.category)+'</a></div>'+
          '<span class="pd-cat-tag">'+catLabel(p.category)+'</span>'+
          '<h1>'+esc(p.title)+'</h1>'+
          (p.description?'<p class="pd-desc">'+esc(p.description)+'</p>':'')+
          '<div class="pd-price" id="pdPrice">'+money(selVar.price)+'</div>'+
          (p.variants.length>1?'<div class="pd-section-label">'+esc(p.sizeName||"Tamaño")+'</div><div class="size-opts" id="pdSizes">'+
            p.variants.map(function(v,i){return '<button class="size-opt '+(i===0?"active":"")+'" data-i="'+i+'">'+esc(v.size)+'<small>'+money(v.price)+'</small></button>';}).join("")+'</div>':'')+
          '<div class="pd-section-label">Cantidad</div>'+
          '<div class="pd-buy">'+
            '<div class="qty"><button id="qtyMinus" aria-label="Menos">−</button><input id="qtyInput" type="text" value="1" inputmode="numeric" aria-label="Cantidad"><button id="qtyPlus" aria-label="Más">+</button></div>'+
            '<button class="btn btn--lg" id="pdAdd" style="flex:1">'+IC.cart+' Agregar al pedido</button>'+
          '</div>'+
          '<div class="pd-meta">'+
            '<div>'+IC.truck+'<span>Envíos a todo el país (1 a 7 días hábiles) o retiro en local.</span></div>'+
            '<div>'+IC.check+'<span>Producto natural, seleccionado y fraccionado con cuidado.</span></div>'+
            '<div>'+IC.wa+'<span>¿Dudas? <a href="'+waLink("Hola! Quiero consultar por "+p.title)+'" target="_blank" rel="noopener" style="color:var(--forest);font-weight:700">Consultanos por WhatsApp</a></span></div>'+
          '</div>'+
        '</div>'+
      '</div></div>';

    // thumbs
    $$("#productRoot .pd-thumbs button").forEach(function(b){
      b.addEventListener("click", function(){
        $$("#productRoot .pd-thumbs button").forEach(function(x){x.classList.remove("active");});
        b.classList.add("active"); $("#pdMainImg").src=b.getAttribute("data-img");
      });
    });
    // sizes
    $$("#pdSizes .size-opt").forEach(function(b){
      b.addEventListener("click", function(){
        $$("#pdSizes .size-opt").forEach(function(x){x.classList.remove("active");});
        b.classList.add("active"); selVar=p.variants[+b.getAttribute("data-i")];
        $("#pdPrice").textContent=money(selVar.price);
      });
    });
    // qty
    var qi=$("#qtyInput");
    function getQty(){ var n=parseInt(qi.value,10); return (isNaN(n)||n<1)?1:n; }
    $("#qtyMinus").addEventListener("click",function(){ qi.value=Math.max(1,getQty()-1); });
    $("#qtyPlus").addEventListener("click",function(){ qi.value=getQty()+1; });
    qi.addEventListener("blur",function(){ qi.value=getQty(); });
    // add
    $("#pdAdd").addEventListener("click",function(){
      if(!selVar.price){ window.open(waLink("Hola! Quiero consultar por "+p.title),"_blank"); return; }
      addToCart(p.handle, selVar.size, selVar.price, getQty());
      showToast("Agregado al pedido");
    });

    // related
    var relRoot = $("#relatedGrid");
    if(relRoot){
      var rel = CATALOG.filter(function(x){return x.category===p.category && x.handle!==p.handle;}).slice(0,4);
      if(rel.length<4){ rel=rel.concat(CATALOG.filter(function(x){return x.handle!==p.handle && rel.indexOf(x)===-1;}).slice(0,4-rel.length)); }
      relRoot.innerHTML=rel.map(cardHTML).join("");
    }
    initReveal();
  }

  /* ================= INIT ================= */
  function init(){
    buildHeader(); buildFooter(); buildDrawer(); buildModal(); buildFloat();
    syncCart();
    var page = document.body.getAttribute("data-page");
    if(page==="home") initHome();
    else if(page==="catalog") initCatalog();
    else if(page==="product") initProduct();
    else initReveal();
  }
  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded", init); else init();

  // expose a couple helpers for inline use
  window.Biofoods = { addToCart:addToCart, waLink:waLink };
})();
