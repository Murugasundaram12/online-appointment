<style>
    :root {
        --quotation-primary: #0284c7;
        --quotation-primary-soft: #f0f9ff;
        --quotation-success: #15803d;
        --quotation-warning: #b45309;
        --quotation-danger: #b91c1c;
        --quotation-dark: #0f172a;
        --quotation-text: #334155;
        --quotation-muted: #64748b;
        --quotation-border: #cbd5e1;
        --quotation-background: #ffffff;
        --quotation-soft: #f8fafc;
    }
    .quotation-preview-shell { background: #eef2f7; padding: 20px; }
    .quotation-document {
        max-width: 900px;
        margin: 0 auto;
        background: var(--quotation-background);
        color: var(--quotation-text);
        border: 1px solid var(--quotation-border);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        padding: 22px 26px;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.35;
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .quotation-header { display: table; width: 100%; table-layout: fixed; padding-bottom: 10px; border-bottom: 2px solid var(--quotation-border); }
    .clinic-block { display: table-cell; vertical-align: top; width: 62%; }
    .quotation-meta { display: table-cell; vertical-align: top; width: 38%; text-align: right; }
    .clinic-brand-table { display: table; width: 100%; }
    .clinic-brand-cell { display: table-cell; vertical-align: top; }
    .clinic-logo { max-height: 46px; max-width: 110px; margin-right: 10px; }
    .clinic-mark {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--quotation-primary-soft);
        color: var(--quotation-primary);
        text-align: center;
        font-size: 16px;
        font-weight: 800;
        line-height: 42px;
        margin-right: 10px;
    }
    .clinic-name { margin: 0 0 3px; color: var(--quotation-dark); font-size: 17px; font-weight: 800; line-height: 1.2; }
    .muted { color: var(--quotation-muted); font-size: 11px; line-height: 1.3; }
    .quotation-kicker { color: var(--quotation-primary); font-size: 20px; font-weight: 900; letter-spacing: 1px; line-height: 1; }
    .quotation-number { color: var(--quotation-dark); font-weight: 800; font-size: 12px; margin: 3px 0 5px; }
    .status-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-sent { background: #e0f2fe; color: #0369a1; }
    .status-accepted { background: #dcfce7; color: var(--quotation-success); }
    .status-rejected { background: #fee2e2; color: var(--quotation-danger); }
    .status-expired { background: #fef3c7; color: var(--quotation-warning); }
    .status-converted { background: #ede9fe; color: #6d28d9; }
    .quotation-meta dl { margin: 6px 0 0; }
    .quotation-meta dl div { margin-top: 2px; }
    .quotation-meta dt { display: inline; color: var(--quotation-muted); font-size: 11px; font-weight: 700; }
    .quotation-meta dd { display: inline; margin: 0 0 0 4px; color: var(--quotation-dark); font-size: 11px; font-weight: 700; }

    .quotation-panels-table { display: table; width: 100%; table-layout: fixed; margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .quotation-panel-cell { display: table-cell; width: 50%; vertical-align: top; }
    .quotation-panel-cell:first-child { padding-right: 6px; }
    .quotation-panel-cell:last-child { padding-left: 6px; }
    .quotation-panel { background: var(--quotation-soft); border: 1px solid var(--quotation-border); border-radius: 8px; padding: 10px 12px; box-sizing: border-box; }
    .quotation-panel h2, .quotation-section h2, .quotation-notes h2 { margin: 0 0 6px; color: var(--quotation-dark); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; font-weight: 800; }
    .primary-line { color: var(--quotation-dark); font-size: 13px; font-weight: 800; margin-bottom: 2px; }
    .mini-badge { display: inline-block; margin-top: 4px; background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 999px; font-size: 9px; font-weight: 800; }

    .detail-grid { display: table; width: 100%; }
    .detail-grid-row { display: table-row; }
    .detail-grid-label, .detail-grid-value { display: table-cell; padding: 2px 0; font-size: 11px; vertical-align: top; }
    .detail-grid-label { color: var(--quotation-muted); width: 44%; }
    .detail-grid-value { color: var(--quotation-dark); font-weight: 700; }

    .quotation-section { margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .table-responsive-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .quotation-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .quotation-table thead { display: table-header-group; }
    .quotation-table tr { page-break-inside: avoid; break-inside: avoid; }
    .quotation-table th { background: var(--quotation-soft); color: var(--quotation-muted); font-size: 10px; text-transform: uppercase; padding: 6px 8px; border-bottom: 1px solid var(--quotation-border); text-align: left; font-weight: 700; }
    .quotation-table td { padding: 6px 8px; border-bottom: 1px solid var(--quotation-border); font-size: 11px; vertical-align: top; }
    .text-right { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-danger { color: var(--quotation-danger) !important; }
    .text-muted { color: var(--quotation-muted) !important; }
    .py-3 { padding-top: 10px !important; padding-bottom: 10px !important; }
    .strong { color: var(--quotation-dark); font-weight: 800; }

    .quotation-bottom-table { display: table; width: 100%; table-layout: fixed; margin-top: 10px; page-break-inside: avoid; break-inside: avoid; }
    .quotation-notes-cell { display: table-cell; width: 58%; padding-right: 8px; vertical-align: top; }
    .totals-card-cell { display: table-cell; width: 42%; vertical-align: top; }
    .totals-card { background: var(--quotation-soft); border: 1px solid var(--quotation-border); border-radius: 8px; padding: 8px 10px; }
    .summary-row { display: table; width: 100%; padding: 3px 0; border-bottom: 1px solid var(--quotation-border); font-size: 11px; }
    .summary-row span, .summary-row strong { display: table-cell; }
    .summary-row strong { text-align: right; color: var(--quotation-dark); }
    .summary-row.total-row { border-bottom: 0; color: var(--quotation-primary); font-size: 13px; font-weight: 900; padding-top: 4px; }
    .summary-row.total-row strong { color: var(--quotation-primary); }
    .quotation-status-box { margin-top: 6px; padding: 5px 8px; border-radius: 6px; background: #fff; border: 1px solid var(--quotation-border); }
    .quotation-status-box span { display: block; color: var(--quotation-muted); font-size: 10px; }
    .quotation-status-box strong { color: var(--quotation-dark); font-size: 11px; }

    .quotation-footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--quotation-border); color: var(--quotation-muted); font-size: 10px; text-align: center; line-height: 1.3; page-break-inside: avoid; break-inside: avoid; }
    .quotation-footer strong { display: block; color: var(--quotation-dark); margin-bottom: 2px; font-size: 10px; }
    .generated-at { margin-top: 3px; font-size: 9px; }

    @media (max-width: 768px) {
        .quotation-preview-shell { padding: 10px; }
        .quotation-document { padding: 16px; border-radius: 10px; }
        .clinic-block, .quotation-meta, .quotation-panel-cell, .quotation-notes-cell, .totals-card-cell { display: block; width: 100%; text-align: left; padding: 0; }
        .quotation-meta { margin-top: 16px; }
        .quotation-panels-table, .quotation-bottom-table { display: block; margin-top: 10px; }
        .quotation-panel-cell { margin-bottom: 10px; }
        .quotation-notes-cell { margin-bottom: 10px; }
    }
    @media print {
        .no-print, .sidebar, .app-topbar, .topbar, .navbar, .app-toast-container { display: none !important; }
        body, .quotation-preview-shell { background: #fff !important; padding: 0 !important; }
        #page-content-wrapper { width: 100% !important; }
        .quotation-document { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; border-radius: 0 !important; padding: 4mm 6mm !important; }
        @page { size: A4 portrait; margin: 6mm; }
    }
</style>
