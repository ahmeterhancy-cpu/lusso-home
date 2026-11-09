/* Lusso Home Concept — vitrin davranışları (kütüphanesiz). */
(function () {
  'use strict';
  var L = window.LUSSO || {};
  var token = document.querySelector('meta[name="csrf-token"]').content;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  function post(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(data || {}),
      credentials: 'same-origin'
    }).then(function (r) { return r.json().then(function (j) { j._status = r.status; return j; }); });
  }

  var toastTimer;
  function toast(msg) {
    var t = $('[data-toast]');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('shown');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('shown'); }, 2800);
  }

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  /* ---- sepet çekmecesi ---- */
  function renderCart(cart) {
    $$('[data-cart-count]').forEach(function (e) { e.textContent = cart.count; });
    $$('[data-cart-total]').forEach(function (e) { e.textContent = cart.subtotal_text; });
    var body = $('[data-drawer-body]'), foot = $('[data-drawer-foot]');
    if (!body) return;
    if (!cart.items.length) {
      body.innerHTML = '<p class="drawer-empty">Sepetinizde ürün yok.</p>';
      foot.style.display = 'none';
      return;
    }
    foot.style.display = '';
    $('[data-drawer-total]').textContent = cart.subtotal_text;
    body.innerHTML = cart.items.map(function (i) {
      return '<div class="mini-item">' +
        (i.image ? '<img src="' + esc(i.image) + '" alt="">' : '') +
        '<div class="info"><a href="' + esc(i.url) + '">' + esc(i.name) + '</a><span class="q">' + i.qty + ' × <span>' + esc(i.price_text) + '</span></span></div>' +
        '<button class="rm" type="button" data-remove="' + i.id + '" aria-label="Kaldır">×</button></div>';
    }).join('');
  }
  function openDrawer() { document.body.classList.add('drawer-open'); }
  function closeDrawer() { document.body.classList.remove('drawer-open', 'menu-open'); }

  document.addEventListener('click', function (e) {
    var t;
    if ((t = e.target.closest('[data-open-cart]'))) {
      if (window.innerWidth < 768) return; // telefonda doğrudan sepet sayfası
      e.preventDefault();
      fetch(L.cartSummary, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(function (c) { renderCart(c); openDrawer(); });
    } else if (e.target.closest('[data-close-drawer]')) {
      closeDrawer();
    } else if ((t = e.target.closest('[data-remove]'))) {
      post(L.cartRemove + '/' + t.getAttribute('data-remove')).then(function (j) { if (j.cart) renderCart(j.cart); });
    } else if ((t = e.target.closest('[data-add-to-cart]'))) {
      e.preventDefault();
      t.classList.add('loading');
      post(L.cartAdd, { product_id: +t.getAttribute('data-add-to-cart'), qty: 1 }).then(function (j) {
        t.classList.remove('loading');
        if (j.ok) { renderCart(j.cart); openDrawer(); } else { toast(j.message || 'Ürün sepete eklenemedi.'); }
      }).catch(function () { t.classList.remove('loading'); toast('Bağlantı hatası, tekrar deneyin.'); });
    } else if ((t = e.target.closest('[data-wishlist]'))) {
      e.preventDefault();
      post(L.wishlistToggle, { product_id: +t.getAttribute('data-wishlist') }).then(function (j) {
        if (!j.ok) return;
        $$('[data-wishlist="' + t.getAttribute('data-wishlist') + '"]').forEach(function (b) {
          b.classList.toggle('in-list', j.added);
          var s = b.querySelector('span');
          if (s) s.textContent = j.added ? 'Beğendiklerimde' : 'Beğendiklerime ekle';
        });
        $$('[data-wishlist-count]').forEach(function (c) { c.textContent = j.count; c.toggleAttribute('data-zero', !j.count); });
        toast(j.added ? 'Beğendiklerinize eklendi.' : 'Beğendiklerinizden çıkarıldı.');
      });
    } else if ((t = e.target.closest('[data-qty]'))) {
      var input = t.parentNode.querySelector('input');
      var v = (parseInt(input.value, 10) || 0) + parseInt(t.getAttribute('data-qty'), 10);
      var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '99', 10);
      input.value = Math.max(min, Math.min(max, v));
    } else if (e.target.closest('[data-open-search]')) {
      $('[data-search]').classList.add('open');
      setTimeout(function () { $('[data-search] input').focus(); }, 100);
    } else if (e.target.closest('[data-close-search]')) {
      $('[data-search]').classList.remove('open');
    } else if (e.target.closest('[data-open-menu]')) {
      document.body.classList.add('menu-open');
    } else if (e.target.closest('[data-scroll-top]')) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeDrawer(); var s = $('[data-search]'); if (s) s.classList.remove('open'); closeLightbox(); }
  });

  /* ürün sayfası: sepete ekle formu AJAX (JS kapalıyken normal gönderilir) */
  var addForm = $('[data-add-form]');
  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = addForm.querySelector('[type=submit]');
      btn.disabled = true;
      post(L.cartAdd, { product_id: +addForm.product_id.value, qty: +addForm.qty.value || 1 }).then(function (j) {
        btn.disabled = false;
        if (j.ok) { renderCart(j.cart); openDrawer(); } else { toast(j.message || 'Ürün sepete eklenemedi.'); }
      }).catch(function () { btn.disabled = false; addForm.submit(); });
    });
  }

  /* ---- ana sayfa slaytı: ilerleme çizgisi dolunca sonraki görsele geçer ---- */
  $$('[data-hero]').forEach(function (hs) {
    var slides = $$('[data-hs-slide]', hs), bars = $$('[data-hs-go]', hs), cur = $('[data-hs-cur]', hs);
    if (slides.length < 2) return;
    var i = 0;
    function go(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) {
        var on = k === i;
        s.classList.toggle('active', on);
        if (on) { s.removeAttribute('aria-hidden'); s.removeAttribute('tabindex'); }
        else { s.setAttribute('aria-hidden', 'true'); s.setAttribute('tabindex', '-1'); }
      });
      bars.forEach(function (b, k) {
        b.classList.remove('active');
        b.classList.toggle('done', k < i);
      });
      void bars[i].offsetWidth; // dolma animasyonu baştan başlasın
      bars[i].classList.add('active');
      if (cur) cur.textContent = (i < 9 ? '0' : '') + (i + 1);
    }
    // otomatik geçiş: etkin çizginin CSS animasyonu bitince (hareket azaltılmışsa animasyon yok → durur)
    bars.forEach(function (b, k) {
      b.addEventListener('click', function () { go(k); });
      $('i', b).addEventListener('animationend', function () { if (k === i) go(i + 1); });
    });
    $$('[data-hs-step]', hs).forEach(function (b) {
      b.addEventListener('click', function () { go(i + Number(b.dataset.hsStep)); });
    });
    hs.addEventListener('mouseenter', function () { hs.classList.add('paused'); });
    hs.addEventListener('mouseleave', function () { hs.classList.remove('paused'); });
    hs.addEventListener('focusin', function () { hs.classList.add('paused'); });
    hs.addEventListener('focusout', function () { hs.classList.remove('paused'); });
    // parmakla kaydırma
    var stage = $('[data-hs-stage]', hs), x0 = null, moved = false;
    stage.addEventListener('pointerdown', function (e) { x0 = e.clientX; moved = false; });
    stage.addEventListener('pointerup', function (e) {
      if (x0 === null) return;
      var dx = e.clientX - x0; x0 = null;
      if (Math.abs(dx) > 45) { moved = true; go(i + (dx < 0 ? 1 : -1)); }
    });
    stage.addEventListener('click', function (e) { if (moved) { e.preventDefault(); moved = false; } }, true);
    stage.addEventListener('dragstart', function (e) { e.preventDefault(); });
  });

  /* ---- kaydırmalı raylar ---- */
  $$('[data-carousel]').forEach(function (c) {
    var track = $('.carousel-track', c), prev = $('[data-prev]', c), next = $('[data-next]', c), dotsWrap = $('[data-dots]', c);
    var page = function () { return track.clientWidth + (parseFloat(getComputedStyle(track).columnGap) || 0); };
    if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -page() }); });
    if (next) next.addEventListener('click', function () { track.scrollBy({ left: page() }); });
    if (dotsWrap) {
      var build = function () {
        var n = Math.max(1, Math.ceil((track.scrollWidth - 5) / page()));
        dotsWrap.innerHTML = n > 1 ? new Array(n).fill(0).map(function (_, i) { return '<button type="button" aria-label="Sayfa ' + (i + 1) + '"></button>'; }).join('') : '';
        $$('button', dotsWrap).forEach(function (b, i) { b.addEventListener('click', function () { track.scrollTo({ left: i * page() }); }); });
        sync();
      };
      var sync = function () {
        var i = Math.round(track.scrollLeft / page());
        $$('button', dotsWrap).forEach(function (b, j) { b.classList.toggle('active', i === j); });
      };
      track.addEventListener('scroll', function () { window.requestAnimationFrame(sync); });
      window.addEventListener('resize', build);
      build();
    }
  });

  /* ---- ürün galerisi (kaydırmalı) + büyütme ---- */
  var gallery = $('[data-gallery]'), lb = $('[data-lightbox]'), lbImgs = [], lbIdx = 0;
  function closeLightbox() { if (lb) lb.classList.remove('open'); }
  if (gallery) {
    var track = $('[data-track]', gallery);
    var slidesEls = track ? $$('.pd-slide', track) : [];
    var thumbs = $$('[data-thumb]', gallery), dotsEls = $$('.pd-dots span', gallery);
    lbImgs = slidesEls.map(function (s) { return $('img', s).src; });
    var current = function () { return track ? Math.round(track.scrollLeft / Math.max(1, track.clientWidth)) : 0; };
    var show = function (i) {
      if (!track) return;
      i = (i + slidesEls.length) % slidesEls.length;
      track.scrollTo({ left: i * track.clientWidth });
    };
    var mark = function () {
      var i = current(); lbIdx = i;
      thumbs.forEach(function (t, j) { t.classList.toggle('active', i === j); });
      dotsEls.forEach(function (d, j) { d.classList.toggle('active', i === j); });
    };
    if (track) track.addEventListener('scroll', function () { window.requestAnimationFrame(mark); }, { passive: true });
    thumbs.forEach(function (t, i) { t.addEventListener('click', function () { show(i); }); });
    $$('[data-step]', gallery).forEach(function (b) {
      b.addEventListener('click', function () { show(current() + parseInt(b.getAttribute('data-step'), 10)); });
    });
    var openLb = function () { if (!lb || !lbImgs.length) return; lbIdx = current(); $('img', lb).src = lbImgs[lbIdx]; lb.classList.add('open'); };
    slidesEls.forEach(function (s) { s.addEventListener('click', openLb); });
    var z = $('[data-zoom]', gallery); if (z) z.addEventListener('click', openLb);
    if (lb) {
      lb.addEventListener('click', function (e) {
        if (e.target.closest('[data-lb-prev]')) { lbIdx = (lbIdx - 1 + lbImgs.length) % lbImgs.length; $('img', lb).src = lbImgs[lbIdx]; }
        else if (e.target.closest('[data-lb-next]')) { lbIdx = (lbIdx + 1) % lbImgs.length; $('img', lb).src = lbImgs[lbIdx]; }
        else if (e.target.closest('[data-lb-close]') || e.target === lb) closeLightbox();
      });
    }
  }

  /* ---- ürün sayfası: asıl düğme görünmeyince alttan satın alma çubuğu ---- */
  var bar = $('[data-pd-bar]'), buyForm = $('[data-add-form]');
  if (bar && buyForm) {
    var syncBar = function () { bar.classList.toggle('shown', buyForm.getBoundingClientRect().bottom < 0); };
    window.addEventListener('scroll', syncBar, { passive: true });
    syncBar();
  }

  /* ---- sekmeler ---- */
  $$('[data-tabs]').forEach(function (w) {
    $$('[data-tab]', w).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-tab]', w).forEach(function (x) { x.classList.toggle('active', x === b); });
        $$('[data-panel]', w).forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === b.getAttribute('data-tab')); });
      });
    });
  });

  /* ---- yapışkan başlık + yukarı düğmesi ---- */
  var sticky = $('[data-sticky]'), up = $('[data-scroll-top]');
  window.addEventListener('scroll', function () {
    var y = window.pageYOffset;
    if (sticky) sticky.classList.toggle('shown', y > 260);
    if (up) up.classList.toggle('shown', y > 600);
  }, { passive: true });

  /* ---- kart numarası biçimi ---- */
  var cn = $('[data-card-number]');
  if (cn) {
    cn.addEventListener('input', function () {
      cn.value = cn.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
    });
    // Tiko kart numarasını boşluksuz bekler.
    cn.form.addEventListener('submit', function () { cn.value = cn.value.replace(/\s/g, ''); });
  }

  /* ---- kasa: şehre göre kargo ---- */
  var citySel = $('[data-city-select]');
  if (citySel) {
    var fmt = function (v) {
      return (Math.round(v * 100) % 100 === 0 ? String(Math.round(v)) : v.toFixed(2).replace('.', ',')) + ' ₺';
    };
    var updateShip = function () {
      var opt = citySel.options[citySel.selectedIndex];
      if (!opt || !opt.value) return;
      var ship = parseFloat(opt.getAttribute('data-ship') || '0');
      var base = parseFloat(citySel.getAttribute('data-base') || '0');
      $$('[data-ship-text]').forEach(function (e) { e.textContent = ship > 0 ? fmt(ship) : 'Ücretsiz'; });
      $$('[data-total-text]').forEach(function (e) { e.textContent = fmt(base + ship); });
    };
    citySel.addEventListener('change', updateShip);
    updateShip();
  }

  /* ---- çerez çubuğu ---- */
  var cookie = $('[data-cookie]');
  try {
    if (cookie && !localStorage.getItem('lusso-cookie')) cookie.hidden = false;
  } catch (err) { /* depolama kapalı */ }
  if (cookie) $('[data-cookie-ok]', cookie).addEventListener('click', function () {
    cookie.hidden = true;
    try { localStorage.setItem('lusso-cookie', '1'); } catch (err) { }
  });

  /* ---- alt bilgi: telefonda açılır kapanır, masaüstünde hep açık ---- */
  var fcols = $$('[data-fcol]'), phone = window.matchMedia('(max-width: 767px)');
  function syncFooter() { fcols.forEach(function (d) { d.open = !phone.matches; }); }
  syncFooter();
  if (phone.addEventListener) phone.addEventListener('change', syncFooter);
  fcols.forEach(function (d) {
    d.addEventListener('toggle', function () { // biri açılınca diğerleri kapansın
      if (phone.matches && d.open) fcols.forEach(function (o) { if (o !== d) o.open = false; });
    });
  });
})();
