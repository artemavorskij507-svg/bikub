<style>
    .bikube-os-root {
        --bikube-bg: radial-gradient(circle at 0% 0%, #e0e7ff 0%, #f8fafc 42%, #eef2ff 100%);
        --bikube-card: #ffffff;
        --bikube-card-muted: #f8fafc;
        --bikube-border: #dbe4f0;
        --bikube-text: #0f172a;
        --bikube-subtext: #475569;
        --bikube-soft: #64748b;
        --bikube-primary: #2563eb;
        --bikube-primary-strong: #1d4ed8;
        --bikube-danger: #dc2626;
        --bikube-success: #059669;
        --bikube-warning: #d97706;
    }

    .bikube-os-root {
        background: var(--bikube-bg);
        border-radius: 1.5rem;
        border: 1px solid #e2e8f0;
        padding: 1.25rem;
        box-shadow: 0 12px 28px -20px rgba(15, 23, 42, 0.45);
    }

    .bikube-os-container {
        display: grid;
        gap: 1rem;
    }

    .bikube-os-hero {
        background: linear-gradient(135deg, #020617 0%, #0f172a 42%, #1e3a8a 100%);
        color: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 1rem;
        padding: 1.1rem 1.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        align-items: flex-start;
    }

    .bikube-os-hero-eyebrow {
        margin: 0;
        font-size: .7rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #93c5fd;
        font-weight: 700;
    }

    .bikube-os-hero-title {
        margin: .3rem 0 0;
        font-size: clamp(1.3rem, 2vw, 1.9rem);
        line-height: 1.2;
        color: #f8fafc;
        font-weight: 800;
    }

    .bikube-os-hero-subtitle {
        margin: .45rem 0 0;
        color: #cbd5e1;
        font-size: .92rem;
        max-width: 52rem;
    }

    .bikube-os-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .3rem .65rem;
        font-size: .72rem;
        font-weight: 700;
        background: rgba(56, 189, 248, 0.18);
        border: 1px solid rgba(56, 189, 248, 0.45);
        color: #bae6fd;
    }

    .bikube-os-status-strip {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .55rem;
    }

    .bikube-os-chip {
        display: inline-flex;
        align-items: center;
        border-radius: .7rem;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: rgba(15, 23, 42, 0.35);
        color: #dbeafe;
        font-size: .72rem;
        font-weight: 600;
        padding: .28rem .55rem;
    }

    .bikube-os-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        align-items: center;
    }

    .bikube-os-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        border-radius: .7rem;
        border: 1px solid #cbd5e1;
        padding: .52rem .85rem;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .14s ease;
        cursor: pointer;
    }

    .bikube-os-btn:hover {
        transform: translateY(-1px);
    }

    .bikube-os-btn-primary {
        color: #eff6ff;
        border-color: #1d4ed8;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 8px 18px -12px rgba(37, 99, 235, .95);
    }

    .bikube-os-btn-primary:hover { background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); }

    .bikube-os-btn-soft {
        color: #0f172a;
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .bikube-os-btn-danger {
        color: #7f1d1d;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .bikube-os-grid-3,
    .bikube-os-grid-4,
    .bikube-os-grid-5 {
        display: grid;
        gap: .8rem;
    }

    .bikube-os-grid-3 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    .bikube-os-grid-4 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .bikube-os-grid-5 { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }

    .bikube-os-card {
        background: var(--bikube-card);
        border: 1px solid var(--bikube-border);
        border-radius: 1rem;
        box-shadow: 0 10px 24px -20px rgba(15, 23, 42, 0.5);
        overflow: hidden;
    }

    .bikube-os-card-body {
        padding: .95rem 1rem;
    }

    .bikube-os-card-title {
        margin: 0;
        color: var(--bikube-text);
        font-size: .9rem;
        font-weight: 800;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .bikube-os-card-subtitle {
        margin: .25rem 0 0;
        color: var(--bikube-soft);
        font-size: .8rem;
    }

    .bikube-os-kpi {
        position: relative;
        border-left: 4px solid #64748b;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .bikube-os-kpi-value {
        margin: .5rem 0 0;
        color: var(--bikube-text);
        font-size: 1.8rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .bikube-os-kpi-hint {
        margin-top: .32rem;
        font-size: .76rem;
        color: var(--bikube-subtext);
    }

    .bikube-os-kpi-tone-blue { border-left-color: #2563eb; }
    .bikube-os-kpi-tone-emerald { border-left-color: #059669; }
    .bikube-os-kpi-tone-amber { border-left-color: #d97706; }
    .bikube-os-kpi-tone-red { border-left-color: #dc2626; }
    .bikube-os-kpi-tone-violet { border-left-color: #7c3aed; }
    .bikube-os-kpi-tone-slate { border-left-color: #64748b; }

    .bikube-os-order-card {
        border-left: 4px solid #334155;
    }

    .bikube-os-order-head {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        justify-content: space-between;
    }

    .bikube-os-order-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }

    .bikube-os-order-meta {
        margin: .3rem 0 0;
        font-size: .78rem;
        color: #64748b;
    }

    .bikube-os-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .38rem;
        align-items: flex-start;
    }

    .bikube-os-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .62rem;
        font-size: .72rem;
        font-weight: 700;
        border: 1px solid transparent;
        text-transform: lowercase;
    }

    .bikube-os-pill-status-pending { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .bikube-os-pill-status-active { background: #dbeafe; color: #1e3a8a; border-color: #93c5fd; }
    .bikube-os-pill-status-success { background: #dcfce7; color: #065f46; border-color: #86efac; }
    .bikube-os-pill-status-danger { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .bikube-os-pill-status-neutral { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
    .bikube-os-pill-status-violet { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }

    .bikube-os-info-grid {
        display: grid;
        gap: .6rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin-top: .75rem;
    }

    .bikube-os-info {
        border: 1px solid #dbe4f0;
        background: #f8fafc;
        border-radius: .75rem;
        padding: .6rem .7rem;
    }

    .bikube-os-info-label {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        font-weight: 700;
    }

    .bikube-os-info-value {
        display: block;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
        font-size: .86rem;
        font-weight: 600;
        color: #0f172a;
        margin-top: .26rem;
    }

    .bikube-os-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 1rem;
        padding: 1.1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        text-align: center;
    }

    .bikube-os-empty-title {
        font-size: .95rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .bikube-os-empty-text {
        font-size: .84rem;
        color: #64748b;
        margin: .35rem 0 0;
    }

    @media (max-width: 768px) {
        .bikube-os-root { padding: .9rem; border-radius: 1rem; }
        .bikube-os-hero { padding: .95rem; }
    }
</style>
