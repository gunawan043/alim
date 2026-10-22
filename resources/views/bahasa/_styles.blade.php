@once
<style>
    .lang-hero {
        border-radius: 18px;
        background: linear-gradient(135deg, #0f5132 0%, #146c43 55%, #d1a12b 140%);
        color: #fff;
        padding: 26px 28px;
        position: relative;
        overflow: hidden;
    }
    .lang-hero:after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .12);
    }
    .lang-hero h3 { font-weight: 700; letter-spacing: -.02em; }
    .lang-hero p { color: rgba(255, 255, 255, .82); margin-bottom: 0; }
    .lang-stat {
        border: 1px solid #e3e9ef;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }
    .lang-stat .label { color: #748294; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; }
    .lang-stat .value { color: #172b3a; font-size: 26px; font-weight: 700; line-height: 1.1; margin-top: 5px; }
    .lang-stat .hint { color: #8a98a7; font-size: 11px; margin-top: 4px; }
    .lang-section-title { font-size: 14px; font-weight: 700; color: #172b3a; }
    .lang-muted { color: #7d8b99; font-size: 12px; }
    .lang-badge { border-radius: 999px; font-size: 10px; font-weight: 700; padding: 4px 9px; }
    .lang-word-ar { font-size: 18px; font-weight: 700; direction: rtl; }
    .lang-print { background: #fff; color: #111; font-family: "Times New Roman", serif; }
    .lang-print h4, .lang-print h5 { font-weight: 700; }
    .lang-print table { width: 100%; border-collapse: collapse; }
    .lang-print table th, .lang-print table td { border: 1px solid #333; padding: 6px 7px; font-size: 12px; vertical-align: top; }
    .lang-print .muted { color: #555; }
    @media print {
        .no-print { display: none !important; }
        .lang-print { padding: 0 !important; }
    }
</style>
@endonce
