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
        --invoice-border: #cbd5e1;
        --invoice-background: #ffffff;
        --invoice-soft: #f8fafc;
    }
    .invoice-preview-shell { background: #eef2f7; padding: 20px; }
    .invoice-document {
        max-width: 900px;
        margin: 0 auto;
        background: var(--invoice-background);
        color: var(--invoice-text);
        border: 1px solid var(--invoice-border);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        padding: 22px 26px;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.35;
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .invoice-header { display: table; width: 100%; table-layout: fixed; padding-bottom: 10px; border-bottom: 2px solid var(--invoice-border); }
    .clinic-block { display: table-cell; vertical-align: top; width: 62%; }
    .invoice-meta { display: table-cell; vertical-align: top; width: 38%; text-align: right; }
    .clinic-brand-table { display: table; width: 100%; }
    .clinic-brand-cell { display: table-cell; vertical-align: top; }
    .clinic-logo { max-height: 46px; max-width: 110px; margin-right: 10px; }
    .clinic-mark {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--invoice-primary-soft);
        color: var(--invoice-primary);
        text-align: center;
        font-size: 16px;
        font-weight: 800;
        line-height: 42px;
        margin-right: 10px;
    }
    .clinic-name { margin: 0 0 3px; color: var(--invoice-dark); font-size: 17px; font-weight: 800; line-height: 1.2; }
    .muted { color: var(--invoice-muted); font-size: 11px; line-height: 1.3; }
    .invoice-kicker { color: var(--invoice-primary); font-size: 20px; font-weight: 900; letter-spacing: 1px; line-height: 1; }
    .invoice-number { color: var(--invoice-dark); font-weight: 800; font-size: 12px; margin: 3px 0 5px; }
    .status-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .status-outstanding { background: #fef3c7; color: var(--invoice-warning); }
    .status-partially_paid { background: var(--invoice-primary-soft); color: var(--invoice-primary); }
    .status-paid { background: #dcfce7; color: var(--invoice-success); }
    .status-void { background: #fee2e2; color: var(--invoice-danger); }
    .invoice-meta dl { margin: 6px 0 0; }
    .invoice-meta dl div { margin-top: 2px; }
    .invoice-meta dt { display: inline; color: var(--invoice-muted); font-size: 11px; font-weight: 700; }
    .invoice-meta dd { display: inline; margin: 0 0 0 4px; color: var(--invoice-dark); font-size: 11px; font-weight: 700; }

    .invoice-panels-table { display: table; width: 100%; table-layout: fixed; margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .invoice-panel-cell { display: table-cell; width: 50%; vertical-align: top; }
    .invoice-panel-cell:first-child { padding-right: 6px; }
    .invoice-panel-cell:last-child { padding-left: 6px; }
    .invoice-panel { background: var(--invoice-soft); border: 1px solid var(--invoice-border); border-radius: 8px; padding: 10px 12px; box-sizing: border-box; }
    .invoice-panel h2, .invoice-section h2, .invoice-notes h2 { margin: 0 0 6px; color: var(--invoice-dark); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; font-weight: 800; }
    .primary-line { color: var(--invoice-dark); font-size: 13px; font-weight: 800; margin-bottom: 2px; }
    .mini-badge { display: inline-block; margin-top: 4px; background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 999px; font-size: 9px; font-weight: 800; }

    .detail-grid { display: table; width: 100%; }
    .detail-grid-row { display: table-row; }
    .detail-grid-label, .detail-grid-value { display: table-cell; padding: 2px 0; font-size: 11px; vertical-align: top; }
    .detail-grid-label { color: var(--invoice-muted); width: 44%; }
    .detail-grid-value { color: var(--invoice-dark); font-weight: 700; }

    .invoice-section { margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .invoice-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .invoice-table th { background: var(--invoice-soft); color: var(--invoice-muted); font-size: 10px; text-transform: uppercase; padding: 6px 8px; border-bottom: 1px solid var(--invoice-border); text-align: left; font-weight: 700; }
    .invoice-table td { padding: 6px 8px; border-bottom: 1px solid var(--invoice-border); font-size: 11px; vertical-align: top; }
    .invoice-table.compact td { padding: 5px 8px; }
    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }
    .strong { color: var(--invoice-dark); font-weight: 800; }

    .invoice-bottom-table { display: table; width: 100%; table-layout: fixed; margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .payment-history-cell { display: table-cell; width: 58%; padding-right: 8px; vertical-align: top; }
    .totals-card-cell { display: table-cell; width: 42%; vertical-align: top; }
    .totals-card { background: var(--invoice-soft); border: 1px solid var(--invoice-border); border-radius: 8px; padding: 8px 10px; }
    .summary-row { display: table; width: 100%; padding: 3px 0; border-bottom: 1px solid var(--invoice-border); font-size: 11px; }
    .summary-row span, .summary-row strong { display: table-cell; }
    .summary-row strong { text-align: right; color: var(--invoice-dark); }
    .summary-row.balance { border-bottom: 0; color: var(--invoice-primary); font-size: 13px; font-weight: 900; padding-top: 4px; }
    .summary-row.balance strong { color: var(--invoice-primary); }
    .payment-status-box { margin-top: 6px; padding: 5px 8px; border-radius: 6px; background: #fff; border: 1px solid var(--invoice-border); }
    .payment-status-box span { display: block; color: var(--invoice-muted); font-size: 10px; }
    .payment-status-box strong { color: var(--invoice-dark); font-size: 11px; }
    .empty-note { padding: 8px; border: 1px dashed var(--invoice-border); border-radius: 6px; background: var(--invoice-soft); margin: 0; font-size: 10px; }

    .invoice-notes { margin-top: 10px; padding: 8px 10px; border: 1px solid var(--invoice-border); border-radius: 8px; background: #fff; page-break-inside: avoid; break-inside: avoid; }
    .invoice-notes p { margin: 0 0 3px; font-size: 10px; }

    .invoice-footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--invoice-border); color: var(--invoice-muted); font-size: 10px; text-align: center; line-height: 1.3; page-break-inside: avoid; break-inside: avoid; }
    .invoice-footer strong { display: block; color: var(--invoice-dark); margin-bottom: 2px; font-size: 10px; }
    .generated-at { margin-top: 3px; font-size: 9px; }

    @media (max-width: 768px) {
        .invoice-preview-shell { padding: 10px; }
        .invoice-document { padding: 16px; border-radius: 10px; }
        .clinic-block, .invoice-meta, .invoice-panel-cell, .payment-history-cell, .totals-card-cell { display: block; width: 100%; text-align: left; padding: 0; }
        .invoice-meta { margin-top: 16px; }
        .invoice-panels-table, .invoice-bottom-table { display: block; margin-top: 10px; }
        .invoice-panel-cell { margin-bottom: 10px; }
        .payment-history-cell { margin-bottom: 10px; }
    }
    @media print {
        .no-print, .sidebar, .app-topbar, .topbar, .navbar, .app-toast-container { display: none !important; }
        body, .invoice-preview-shell { background: #fff !important; padding: 0 !important; }
        #page-content-wrapper { width: 100% !important; }
        .invoice-document { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; border-radius: 0 !important; padding: 4mm 6mm !important; }
        @page { size: A4 portrait; margin: 6mm; }
    }
</style>
