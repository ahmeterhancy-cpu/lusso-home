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

    /* Yan menü */
    .fi-sidebar { background: var(--wp-menu) !important; border: 0 !important; box-shadow: none !important; }
    .fi-sidebar-header { background: var(--wp-menu) !important; border-bottom: 1px solid #2c3338 !important; box-shadow: none !important; height: 46px !important; }
    .fi-sidebar-nav { padding: 6px 0 20px !important; gap: 0 !important; }
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

    .lusso-thumb { width: 44px; height: 44px; object-fit: contain; background: #fff; border: 1px solid #dcdcde; border-radius: 2px; }
</style>
