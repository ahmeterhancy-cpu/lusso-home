{{-- Panel görünümü: WordPress yönetim paneline benzer (koyu dar menü, açık gri zemin, düz beyaz kutular). --}}
<style>
    :root {
        --wp-bg: #f0f0f1;          /* içerik zemini */
        --wp-menu: #1d2327;        /* yan menü + üst bar */
        --wp-menu-hover: #2c3338;
        --wp-menu-text: #f0f0f1;
        --wp-menu-muted: #a7aaad;
        --wp-menu-hover-text: #72aee6;
        --wp-active: #2271b1;      /* seçili menü */
        --wp-border: #c3c4c7;
        --lusso-gold: #dca344;
    }

    /* Zemin */
    .fi-body, .fi-layout, .fi-main-ctn { background: var(--wp-bg) !important; }

    /* Üst bar: WordPress yönetim çubuğu gibi koyu ve ince */
    .fi-topbar, .fi-topbar > nav { background: var(--wp-menu) !important; box-shadow: none !important; border: 0 !important; }
    .fi-topbar { min-height: 46px; }
    .fi-topbar .fi-icon-btn, .fi-topbar .fi-icon-btn svg { color: var(--wp-menu-text) !important; }
    .fi-topbar .fi-global-search-field .fi-input-wrp { background: #2c3338 !important; border-color: #3c434a !important; box-shadow: none !important; }
    .fi-topbar .fi-global-search-field input { color: #f0f0f1 !important; }

    /* Yan menü — üst bar 46px olduğu için menü de 46px'ten başlar (Filament varsayılanı 64px boşluk bırakıyordu) */
    .fi-sidebar { background: var(--wp-menu) !important; border: 0 !important; box-shadow: none !important; }
    @media (min-width: 1024px) {
        .fi-main-sidebar { top: 46px !important; height: calc(100dvh - 46px) !important; }
    }
    .fi-sidebar-nav { padding-top: 0 !important; }
    .fi-sidebar-header { background: var(--wp-menu) !important; border-bottom: 1px solid #2c3338 !important; box-shadow: none !important; height: 46px !important; }
    .fi-sidebar-nav { padding-bottom: 20px !important; gap: 0 !important; }
    .fi-sidebar-nav-groups { gap: 2px !important; }
    .fi-sidebar-group { gap: 0 !important; }
    .fi-sidebar-group-btn { padding: 12px 14px 4px !important; }
    .fi-sidebar-group-label { color: #8c8f94 !important; font-size: 11px !important; letter-spacing: .06em; text-transform: uppercase; font-weight: 600 !important; }
    .fi-sidebar-group-collapse-btn, .fi-sidebar-group-collapse-btn svg { color: #8c8f94 !important; }
    .fi-sidebar-group-items { gap: 0 !important; }
    .fi-sidebar-item-btn { border-radius: 0 !important; padding: 7px 14px 7px 16px !important; gap: 10px !important; min-height: 34px; }
    .fi-sidebar-nav, .fi-sidebar-group-items, .fi-sidebar-group { padding-inline: 0 !important; margin-inline: 0 !important; }
    .fi-sidebar-item-btn .fi-sidebar-item-label, .fi-sidebar-item-label { color: var(--wp-menu-text) !important; font-size: 14px !important; font-weight: 400 !important; }
    .fi-sidebar-item-btn .fi-sidebar-item-icon { color: var(--wp-menu-muted) !important; width: 20px !important; height: 20px !important; }
    .fi-sidebar-item-btn:hover { background: var(--wp-menu-hover) !important; }
    .fi-sidebar-item-btn:hover .fi-sidebar-item-label, .fi-sidebar-item-btn:hover .fi-sidebar-item-icon { color: var(--wp-menu-hover-text) !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn { background: var(--wp-active) !important; box-shadow: none !important; position: relative; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon { color: #fff !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn::after { content: ""; position: absolute; right: 0; top: 50%; margin-top: -8px; border: 8px solid transparent; border-right-color: var(--wp-bg); }
    .fi-sidebar-item .fi-badge { box-shadow: none !important; }
    .fi-sidebar-item:not(.fi-active) .fi-badge { background: #d63638 !important; color: #fff !important; border-radius: 9px !important; }
    .fi-sidebar-close-overlay { background: rgba(0,0,0,.5); }

    /* Sayfa başlığı */
    .fi-header-heading { font-size: 23px !important; font-weight: 400 !important; color: #1d2327 !important; }
    .fi-breadcrumbs { font-size: 12px; }

    /* Kutular: WordPress "postbox" gibi düz beyaz, ince gri kenar, köşesiz */
    .fi-section:not(.fi-section-not-contained), .fi-ta-ctn, .fi-wi-stats-overview-stat, .fi-wi-chart .fi-section,
    .fi-fo-repeater-item, .fi-tabs:not(.fi-contained), .fi-sc-tabs.fi-contained, .fi-modal-window {
        border-radius: 2px !important; box-shadow: none !important; border: 1px solid var(--wp-border) !important;
    }
    .fi-section-header { border-bottom: 1px solid #dcdcde; }
    .fi-section-header-heading { font-size: 14px !important; font-weight: 600 !important; }
    .fi-wi-stats-overview-stat { border-top: 3px solid var(--lusso-gold) !important; }
    .fi-ta-header-cell { background: #fff; }
    .fi-ta-row:hover { background: #f6f7f7 !important; }

    /* Form alanları: WordPress girdi kutuları */
    .fi-input-wrp { border-radius: 4px !important; box-shadow: 0 0 0 1px #8c8f94 !important; }
    .fi-input-wrp:focus-within { box-shadow: 0 0 0 1px var(--wp-active), 0 0 0 2px rgba(34,113,177,.25) !important; }

    /* Düğmeler hafif köşeli */
    .fi-btn { border-radius: 3px !important; }
    .fi-tabs-item { border-radius: 2px !important; }

    /* Giriş ekranı */
    .fi-simple-layout { background: var(--wp-bg) !important; }
    .fi-simple-main { border-radius: 2px !important; box-shadow: 0 1px 3px rgba(0,0,0,.04) !important; border: 1px solid var(--wp-border) !important; border-top: 4px solid var(--lusso-gold) !important; }

    /* Modern giriş: solda marka paneli (auth-side), sağda sade form */
    .fi-simple-layout:has(.lusso-auth-side) { display: grid !important; grid-template-columns: minmax(0, 1.15fr) minmax(440px, 1fr); min-height: 100vh; padding: 0 !important; background: #fff !important; }
    .lusso-auth-side { position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 32px; min-height: 100vh; padding: 44px 52px; color: rgba(255,255,255,.8); background: linear-gradient(180deg, #37383c 0%, #37383c 22%, rgba(55,56,60,.9) 48%, rgba(40,40,44,.62) 100%), var(--auth-img) center / cover no-repeat, #37383c; }
    .lusso-auth-logo { width: 190px; height: auto; margin-left: -8px; }
    .lusso-auth-eyebrow { margin: 0 0 12px; font-size: 12px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--lusso-gold); }
    .lusso-auth-copy h2 { margin: 0 0 22px; font-size: clamp(30px, 3vw, 44px); line-height: 1.1; font-weight: 700; color: #fff; letter-spacing: -.01em; }
    .lusso-auth-copy ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .lusso-auth-copy li { display: flex; align-items: center; gap: 10px; font-size: 15px; }
    .lusso-auth-copy li::before { content: ""; flex: none; width: 8px; height: 8px; border-radius: 50%; background: var(--lusso-gold); box-shadow: 0 0 0 4px rgba(220,163,68,.2); }
    .lusso-auth-back { align-self: flex-start; font-size: 13px; font-weight: 700; color: rgba(255,255,255,.75); padding: 8px 14px; border-radius: 999px; background: rgba(255,255,255,.1); transition: background-color .2s, color .2s; }
    .lusso-auth-back:hover { background: rgba(255,255,255,.18); color: #fff; }

    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-main-ctn { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 32px; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-main { width: 100%; max-width: 410px !important; margin: 0 !important; padding: 0 !important; border: 0 !important; border-radius: 0 !important; box-shadow: none !important; background: transparent !important; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-header { align-items: flex-start !important; text-align: left !important; margin-bottom: 8px; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-header .fi-logo { display: none !important; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-header-heading { font-size: 30px !important; line-height: 1.15 !important; font-weight: 700 !important; letter-spacing: -.01em; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-simple-header-subheading { text-align: left !important; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-input-wrp { border-radius: 10px !important; min-height: 48px; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-input { min-height: 46px; font-size: 15px !important; }
    .fi-simple-layout:has(.lusso-auth-side) .fi-btn { border-radius: 10px !important; min-height: 48px; font-size: 15px !important; }
    .fi-simple-layout:has(.lusso-auth-side) button[type=submit].fi-btn { background: #212121 !important; color: #fff !important; }
    .fi-simple-layout:has(.lusso-auth-side) button[type=submit].fi-btn:hover { background: #a86400 !important; }
    @media (max-width: 1023px) {
        .fi-simple-layout:has(.lusso-auth-side) { grid-template-columns: 1fr; grid-template-rows: auto auto; align-content: start; }
        .lusso-auth-side { min-height: 0; padding: 24px 22px 26px; gap: 14px; background: #37383c; }
        .lusso-auth-logo { width: 140px; }
        .lusso-auth-copy h2 { font-size: 24px; margin: 0; }
        .lusso-auth-copy ul, .lusso-auth-back { display: none; }
        .fi-simple-layout:has(.lusso-auth-side) .fi-simple-main-ctn { min-height: 0; align-items: flex-start; padding: 32px 22px 48px; }
    }

    .lusso-thumb { width: 44px; height: 44px; object-fit: contain; background: #fff; border: 1px solid #dcdcde; border-radius: 2px; }
</style>
