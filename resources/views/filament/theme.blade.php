{{-- Brand layer on top of Filament (no build step needed — works on shared hosting). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Noto+Sans+Ethiopic:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{
  --ybd-green-900:#06301f;--ybd-green-800:#0b3d2a;--ybd-green-700:#11553a;--ybd-green-500:#3a8a55;
  --ybd-gold:#c9a45c;--ybd-gold-soft:#efe3c8;
  --ybd-pattern:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1' opacity='.06'%3E%3Cpath d='M40 8l9.4 22.6L72 40l-22.6 9.4L40 72l-9.4-22.6L8 40l22.6-9.4z'/%3E%3Crect x='20' y='20' width='40' height='40' transform='rotate(45 40 40)'/%3E%3Crect x='20' y='20' width='40' height='40'/%3E%3Ccircle cx='40' cy='40' r='8'/%3E%3C/g%3E%3C/svg%3E");
}
body,.fi-body{font-family:'Inter','Noto Sans Ethiopic',ui-sans-serif,system-ui,sans-serif}

/* Brand */
.ybd-brand{display:flex;align-items:center;gap:.6rem;height:100%}
.ybd-brand img{height:2.5rem;width:auto;filter:drop-shadow(0 4px 6px rgba(6,48,31,.25))}
.ybd-brand-text{display:flex;flex-direction:column;line-height:1.05}
.ybd-brand-text .am{font-family:'Noto Sans Ethiopic',sans-serif;font-size:.72rem;font-weight:700;color:var(--ybd-green-500)}
.ybd-brand-text .en{font-family:'Cormorant Garamond',Georgia,serif;font-size:1.15rem;font-weight:700;letter-spacing:.05em;color:var(--ybd-green-800)}
.dark .ybd-brand-text .en{color:#fff}
.dark .ybd-brand-text .am{color:var(--ybd-gold)}

/* Sidebar — deep green with the 8-point star pattern */
.fi-sidebar{background:var(--ybd-pattern),linear-gradient(180deg,var(--ybd-green-800),var(--ybd-green-900)) !important;background-size:80px 80px,auto !important}
.fi-sidebar-header{background:transparent !important;box-shadow:none !important;border:0 !important}
.fi-sidebar .ybd-brand-text .en{color:#fff}
.fi-sidebar .ybd-brand-text .am{color:var(--ybd-gold)}
.fi-sidebar-group-label,.fi-sidebar-group-btn .fi-sidebar-group-label{color:rgba(255,255,255,.55) !important;letter-spacing:.12em;text-transform:uppercase;font-size:.68rem !important}
.fi-sidebar-group-btn svg,.fi-sidebar-group-collapse-btn svg{color:rgba(255,255,255,.45) !important}
.fi-sidebar-item-btn,.fi-sidebar-item-label{color:rgba(255,255,255,.82) !important}
.fi-sidebar-item-icon{color:rgba(255,255,255,.55) !important}
.fi-sidebar-item-btn:hover{background:rgba(255,255,255,.07) !important}
.fi-sidebar-item.fi-active .fi-sidebar-item-btn,.fi-sidebar-item-active .fi-sidebar-item-btn{background:linear-gradient(90deg,rgba(201,164,92,.22),rgba(201,164,92,.06)) !important;box-shadow:inset 3px 0 0 var(--ybd-gold)}
.fi-sidebar-item.fi-active .fi-sidebar-item-label,.fi-sidebar-item-active .fi-sidebar-item-label{color:#fff !important;font-weight:600}
.fi-sidebar-item.fi-active .fi-sidebar-item-icon,.fi-sidebar-item-active .fi-sidebar-item-icon{color:var(--ybd-gold) !important}
.fi-sidebar-item-grouped-border-part,.fi-sidebar-item-grouped-border-part-not-first,.fi-sidebar-item-grouped-border-part-not-last{background:rgba(255,255,255,.18) !important}
.fi-sidebar .fi-badge{background:var(--ybd-gold) !important;color:var(--ybd-green-900) !important;--tw-ring-color:transparent !important}
.fi-sidebar-nav{scrollbar-color:rgba(255,255,255,.2) transparent}
.fi-sidebar .fi-icon-btn{color:rgba(255,255,255,.7) !important}

/* Topbar */
.fi-topbar nav,.fi-topbar{background:rgba(255,255,255,.85) !important;backdrop-filter:blur(12px)}
.dark .fi-topbar nav,.dark .fi-topbar{background:rgba(9,9,11,.8) !important}
.ybd-visit{font-size:.8rem;font-weight:600;color:var(--ybd-green-700);padding:.4rem .8rem;border-radius:999px;border:1px solid rgba(11,61,42,.15);margin-right:.5rem;white-space:nowrap}
.ybd-visit:hover{background:var(--ybd-green-800);color:#fff}
.dark .ybd-visit{color:var(--ybd-gold);border-color:rgba(201,164,92,.35)}

/* Headings */
.fi-header-heading{font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;letter-spacing:.01em;font-size:2rem !important;color:var(--ybd-green-900)}
.dark .fi-header-heading{color:#fff}

/* Cards / sections */
.fi-section,.fi-ta-ctn,.fi-wi-stats-overview-stat,.fi-wi-chart .fi-section{border-radius:1.1rem !important}
.fi-wi-stats-overview-stat{position:relative;overflow:hidden}
.fi-wi-stats-overview-stat::after{content:"";position:absolute;right:-28px;top:-28px;width:90px;height:90px;border-radius:50%;border:1px solid rgba(201,164,92,.25)}
.fi-wi-stats-overview-stat-value{font-family:'Cormorant Garamond',Georgia,serif;font-size:2.2rem !important;color:var(--ybd-green-800)}
.dark .fi-wi-stats-overview-stat-value{color:#fff}

/* Login / simple pages */
.fi-simple-layout{background:var(--ybd-pattern),radial-gradient(120% 90% at 50% 0%,var(--ybd-green-700),var(--ybd-green-900)) !important;background-size:80px 80px,auto !important}
.fi-simple-main{border-radius:1.6rem !important;box-shadow:0 40px 80px -30px rgba(0,0,0,.55) !important;border-top:4px solid var(--ybd-gold)}
.fi-simple-header .ybd-brand{justify-content:center}
.fi-simple-header .ybd-brand img{height:3.6rem}
.fi-simple-header-heading{font-family:'Cormorant Garamond',Georgia,serif;font-size:1.9rem !important}

/* Buttons */
.fi-btn.fi-color-primary,.fi-btn-color-primary{box-shadow:0 8px 18px -10px rgba(11,61,42,.8)}

/* Receipt preview */
.ybd-receipt{max-height:70vh;width:auto;margin:auto;border-radius:.75rem;box-shadow:0 20px 40px -20px rgba(0,0,0,.4)}
.ybd-kpi-note{font-size:.75rem;color:#6b7280}
</style>
<style>
/* ===== People cards (registrations / students / members) ===== */
.ybd-card{display:flex;flex-direction:column;gap:12px;width:100%;min-width:0}
.ybd-card-top{display:flex;align-items:center;gap:12px;min-width:0}
.ybd-avatar{width:44px;height:44px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;object-fit:cover;
  background:linear-gradient(135deg,#3a8a55,#0b3d2a);color:#fff;font-weight:700;font-size:.92rem;letter-spacing:.02em;
  box-shadow:0 0 0 3px rgba(201,164,92,.25)}
.ybd-avatar-gold{background:linear-gradient(135deg,#d8b874,#a8823a)}
.ybd-id{flex:1;min-width:0}
.ybd-name{display:block;font-weight:650;font-size:1rem;line-height:1.3;color:#16231c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ybd-name:hover{color:#3a8a55}
.ybd-sub{font-size:.8rem;color:#6b7c72;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ybd-status{flex-shrink:0;font-size:.72rem;font-weight:700;padding:5px 10px;border-radius:999px;background:#f1f5f3;color:#3b4a42;white-space:nowrap}
.ybd-s-pending{background:#fff4e0;color:#9a6200}
.ybd-s-interview{background:#e6f0ff;color:#2457b8}
.ybd-s-approved,.ybd-st-active{background:#e3f5ea;color:#1d7a43}
.ybd-s-waitlisted{background:#eef2f7;color:#4b5b70}
.ybd-s-rejected,.ybd-st-blocked{background:#fde8ea;color:#b4232c}
.ybd-st-graduated{background:#f6eedb;color:#8a6a2c}
.ybd-st-inactive{background:#eef2f7;color:#4b5b70}
.ybd-team{background:#0b3d2a;color:#fff}
.ybd-row{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;padding-left:56px}
.ybd-i{width:15px;height:15px;flex-shrink:0}
.ybd-phone{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:999px;background:#e8f2ec;color:#0b3d2a;font-weight:650;font-size:.84rem;white-space:nowrap}
.ybd-phone:hover{background:#0b3d2a;color:#fff}
.ybd-meta{display:inline-flex;align-items:center;gap:5px;font-size:.8rem;color:#3b4a42;min-width:0}
.ybd-muted{color:#8a978f}
.ybd-ok{color:#1d7a43}
.ybd-chip{display:inline-flex;align-items:center;gap:5px;font-size:.74rem;font-weight:600;padding:4px 9px;border-radius:999px;background:#f1f5f3;color:#4b5b70;white-space:nowrap}
.ybd-chip-warn{background:#fff4e0;color:#9a6200}
.ybd-chip-info{background:#e6f0ff;color:#2457b8}
.ybd-chip-gold{background:#f6eedb;color:#8a6a2c}
@media (max-width:480px){.ybd-row{padding-left:0}}
/* Card grid: tidy record boxes + action bar */
.fi-ta-content-grid .fi-ta-record{border-radius:18px}
.fi-ta-content-grid .fi-ta-record .fi-ta-actions{padding-top:4px}
/* Dark mode */
.dark .ybd-name{color:#f3f6f4}.dark .ybd-sub,.dark .ybd-muted{color:#9aa8a0}.dark .ybd-meta{color:#cfd8d3}
.dark .ybd-phone{background:rgba(58,138,85,.2);color:#cfe9d8}
.dark .ybd-status,.dark .ybd-chip{background:rgba(255,255,255,.08);color:#dfe6e2}
</style>
<style>
/* Let the card shrink inside the record box (status badge must never overflow) */
.fi-ta-content-grid .fi-ta-record-content-ctn,.fi-ta-content-grid .fi-ta-record-content,.fi-ta-content-grid .fi-ta-record-content>*{min-width:0;max-width:100%;flex:1 1 auto}
.fi-ta-content-grid .fi-ta-record{overflow:hidden}
@media (max-width:640px){
  /* Phones: no selection boxes on cards — more room for the person */
  .fi-ta-content-grid .fi-ta-record-checkbox{display:none}
  .fi-ta-content-grid .fi-ta-record{padding-left:0}
}
</style>
<style>
/* =====================================================================
   EVERY admin table on phones → compact cards (desktop table untouched).
   Title = the bold main column (full width); other values = 2-per-row
   with tiny labels; checkbox top-right; action buttons in a footer row.
   ===================================================================== */
@media (max-width:639.98px){
  .fi-ta-table-stacked-on-mobile{display:block!important;width:100%;background:transparent}
  .fi-ta-table-stacked-on-mobile>thead{display:none!important}
  .fi-ta-table-stacked-on-mobile>tbody{display:flex!important;flex-direction:column;gap:10px;padding:10px}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row{display:grid!important;grid-template-columns:1fr 1fr;gap:10px 14px;align-items:start;
    position:relative;padding:14px 14px 10px;border:1px solid #e3ebe6;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(6,48,31,.05)}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td{display:block!important;padding:0!important;border:0!important;min-width:0;width:auto!important}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td :is(.fi-ta-col,.fi-ta-text,.fi-ta-icon,.fi-ta-image,.fi-ta-toggle,.fi-ta-select,.fi-ta-color,.fi-ta-text-input,.fi-ta-checkbox){padding:0!important}
  .fi-ta-table-stacked-on-mobile .fi-ta-cell-label{display:block;font-size:.64rem;line-height:1.2;text-transform:uppercase;letter-spacing:.07em;font-weight:600;color:#8a978f;margin:0 0 4px}
  .fi-ta-table-stacked-on-mobile .fi-ta-cell-label:empty{display:none}
  .fi-ta-table-stacked-on-mobile .fi-ta-text-item{font-size:.86rem}
  .fi-ta-table-stacked-on-mobile .fi-ta-text-description{font-size:.76rem}

  /* Title: the bold main column spans the card and comes first, without a label */
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-font-semibold,.fi-font-bold){grid-column:1/-1;order:-2;padding-right:34px!important}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-font-semibold,.fi-font-bold) .fi-ta-cell-label{display:none}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-font-semibold,.fi-font-bold) .fi-ta-text-item{font-size:1rem;line-height:1.3}

  /* Pictures (photos / covers): small thumbnail in the title row */
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-ta-image){order:-3;grid-column:1/-1}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-ta-image) img{max-height:44px;width:auto;border-radius:10px}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-ta-image):not(:has(img)){display:none!important}

  /* Bulk-select checkbox: top-right corner */
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(.fi-ta-record-checkbox){position:absolute;top:14px;right:14px;order:-4}

  /* Buttons: footer row */
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(>.fi-ta-actions){grid-column:1/-1;order:99;border-top:1px solid #eef3f0!important;padding-top:8px!important;margin-top:2px}
  .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td>.fi-ta-actions{justify-content:flex-end;flex-wrap:wrap;gap:6px 14px}

  /* Dark mode */
  .dark .fi-ta-table-stacked-on-mobile tr.fi-ta-row{background:rgba(255,255,255,.03);border-color:rgba(255,255,255,.09)}
  .dark .fi-ta-table-stacked-on-mobile tr.fi-ta-row>td:has(>.fi-ta-actions){border-color:rgba(255,255,255,.08)!important}
}
</style>
