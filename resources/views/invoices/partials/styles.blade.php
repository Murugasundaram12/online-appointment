<style>
    :root {
        --invoice-primary: #1d4ed8;
        --invoice-primary-soft: #eff6ff;
        --invoice-success: #15803d;
        --invoice-warning: #b45309;
        --invoice-danger: #b91c1c;
        --invoice-dark: #0f172a;
        --invoice-text: #334155;
        --invoice-muted: #64748b;
        --invoice-border: #e2e8f0;
        --invoice-background: #ffffff;
        --invoice-soft: #f8fafc;
    }
    .invoice-preview-shell { background: #eef2f7; padding: 28px; }
    .invoice-document {
        max-width: 940px;
        margin: 0 auto;
        background: var(--invoice-background);
        color: var(--invoice-text);
        border: 1px solid var(--invoice-border);
        border-radius: 18px;
        box-shadow: 0 22px 60px rgba(15, 23, 42, 0.12);
        padding: 42px;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.45;
    }
    .invoice-header { display: table; width: 100%; padding-bottom: 26px; border-bottom: 2px solid var(--invoice-border); }
    .clinic-block, .invoice-meta { display: table-cell; vertical-align: top; }
    .invoice-meta { width: 260px; text-align: right; }
    .clinic-brand-row { display: table; width: 100%; }
    .clinic-logo, .clinic-mark { display: table-cell; vertical-align: top; }
    .clinic-logo { max-height: 62px; max-width: 120px; margin-right: 14px; }
    .clinic-mark {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        background: var(--invoice-primary-soft);
        color: var(--invoice-primary);
        text-align: center;
        font-size: 20px;
        font-weight: 800;
        line-height: 58px;
        margin-right: 14px;
    }
    .clinic-name { margin: 0 0 8px; color: var(--invoice-dark); font-size: 24px; font-weight: 800; }
    .muted { color: var(--invoice-muted); font-size: 13px; }
    .invoice-kicker { color: var(--invoice-primary); font-size: 28px; font-weight: 900; letter-spacing: 1px; }
    .invoice-number { color: var(--invoice-dark); font-weight: 800; margin: 4px 0 10px; }
    .status-badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .status-outstanding { background: #fef3c7; color: var(--invoice-warning); }
    .status-partially_paid { background: var(--invoice-primary-soft); color: var(--invoice-primary); }
    .status-paid { background: #dcfce7; color: var(--invoice-success); }
    .status-void { background: #fee2e2; color: var(--invoice-danger); }
    .invoice-meta dl { margin: 14px 0 0; }
    .invoice-meta dl div { margin-top: 6px; }
    .invoice-meta dt { display: inline; color: var(--invoice-muted); font-size: 12px; font-weight: 700; }
    .invoice-meta dd { display: inline; margin: 0 0 0 6px; color: var(--invoice-dark); font-weight: 700; }
    .invoice-panels { display: table; width: 100%; border-spacing: 18px; margin: 18px -18px 6px; }
    .invoice-panel { display: table-cell; width: 50%; background: var(--invoice-soft); border: 1px solid var(--invoice-border); border-radius: 14px; padding: 18px; vertical-align: top; }
    .invoice-panel h2, .invoice-section h2, .invoice-notes h2 { margin: 0 0 12px; color: var(--invoice-dark); font-size: 13px; text-transform: uppercase; letter-spacing: .5px; }
    .primary-line { color: var(--invoice-dark); font-size: 18px; font-weight: 800; margin-bottom: 4px; }
    .mini-badge { display: inline-block; margin-top: 10px; background: #fef3c7; color: #92400e; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .detail-grid div { display: table; width: 100%; }
    .detail-grid span, .detail-grid strong { display: table-cell; padding: 4px 0; font-size: 13px; }
    .detail-grid span { color: var(--invoice-muted); width: 42%; }
    .detail-grid strong { color: var(--invoice-dark); font-weight: 700; }
    .invoice-section { margin-top: 24px; }
    .invoice-table { width: 100%; border-collapse: collapse; }
    .invoice-table th { background: var(--invoice-soft); color: var(--invoice-muted); font-size: 11px; text-transform: uppercase; padding: 12px; border-bottom: 1px solid var(--invoice-border); text-align: left; }
    .invoice-table td { padding: 14px 12px; border-bottom: 1px solid var(--invoice-border); font-size: 13px; vertical-align: top; }
    .invoice-table.compact td { padding: 10px 12px; }
    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }
    .strong { color: var(--invoice-dark); font-weight: 800; }
    .invoice-bottom-grid { display: table; width: 100%; margin-top: 24px; }
    .payment-history { display: table-cell; width: 62%; padding-right: 22px; vertical-align: top; }
    .totals-card { display: table-cell; width: 38%; background: var(--invoice-soft); border: 1px solid var(--invoice-border); border-radius: 14px; padding: 16px; vertical-align: top; }
    .summary-row { display: table; width: 100%; padding: 8px 0; border-bottom: 1px solid var(--invoice-border); }
    .summary-row span, .summary-row strong { display: table-cell; }
    .summary-row strong { text-align: right; color: var(--invoice-dark); }
    .summary-row.balance { border-bottom: 0; color: var(--invoice-primary); font-size: 18px; font-weight: 900; }
    .summary-row.balance strong { color: var(--invoice-primary); }
    .payment-status-box { margin-top: 14px; padding: 12px; border-radius: 12px; background: #fff; border: 1px solid var(--invoice-border); }
    .payment-status-box span { display: block; color: var(--invoice-muted); font-size: 12px; }
    .payment-status-box strong { color: var(--invoice-dark); }
    .empty-note { padding: 14px; border: 1px dashed var(--invoice-border); border-radius: 12px; background: var(--invoice-soft); }
    .invoice-notes { margin-top: 24px; padding: 16px; border: 1px solid var(--invoice-border); border-radius: 14px; background: #fff; }
    .invoice-notes p { margin: 0 0 8px; }
    .invoice-footer { margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--invoice-border); color: var(--invoice-muted); font-size: 12px; text-align: center; }
    .invoice-footer strong { display: block; color: var(--invoice-dark); margin-bottom: 5px; }
    .generated-at { margin-top: 6px; }
    @media (max-width: 768px) {
        .invoice-preview-shell { padding: 12px; }
        .invoice-document { padding: 24px; border-radius: 14px; }
        .clinic-block, .invoice-meta, .invoice-panel, .payment-history, .totals-card { display: block; width: 100%; text-align: left; }
        .invoice-meta { margin-top: 22px; }
        .invoice-panels { display: block; margin: 18px 0 0; }
        .invoice-panel { margin-bottom: 14px; }
        .payment-history { padding-right: 0; margin-bottom: 18px; }
    }
    @media print {
        .no-print, .sidebar, .app-topbar, .topbar, .navbar, .app-toast-container { display: none !important; }
        body, .invoice-preview-shell { background: #fff !important; padding: 0 !important; }
        #page-content-wrapper { width: 100% !important; }
        .invoice-document { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; border-radius: 0 !important; padding: 18mm !important; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
