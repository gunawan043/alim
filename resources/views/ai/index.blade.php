@extends('layouts.master')

@section('title', 'ALIM AI')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body.ai-page { overflow: hidden; }
        body.ai-page .page-content {
            min-height: 0;
            padding-top: 90px;
            padding-bottom: 0;
        }
        body.ai-page .footer { display: none; }

        .ai-shell {
            --ai-ink: #001e2e;
            --ai-muted: #5b6b78;
            --ai-line: #dbe4ea;
            --ai-blue: #005981;
            --ai-blue-2: #0a7aa8;
            --ai-gold: #ffae01;
            --ai-surface: #ffffff;
            --ai-canvas: #f6fafc;
            position: relative;
            display: flex;
            height: calc(100vh - 92px);
            min-height: 0;
            overflow: hidden;
            border: 1px solid var(--ai-line);
            border-radius: 22px;
            background:
                radial-gradient(80% 70% at 100% 0%, rgba(0, 89, 129, .08) 0%, transparent 55%),
                radial-gradient(60% 60% at 0% 100%, rgba(255, 174, 1, .07) 0%, transparent 55%),
                var(--ai-canvas);
            color: var(--ai-ink);
            font-family: 'IBM Plex Sans', 'Segoe UI', system-ui, sans-serif;
        }
        .ai-shell * { box-sizing: border-box; }

        .ai-history {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            width: 272px;
            flex: 0 0 272px;
            min-height: 0;
            border-right: 1px solid var(--ai-line);
            background: rgba(255, 255, 255, .72);
            backdrop-filter: blur(12px);
        }
        .ai-history-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 14px 12px;
        }
        .ai-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .ai-brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(145deg, var(--ai-blue), #0a7aa8 70%, var(--ai-gold));
            box-shadow: 0 8px 18px rgba(0, 89, 129, .24);
            font-size: 19px;
        }
        .ai-brand-name { font-weight: 700; font-size: 14px; line-height: 1.1; letter-spacing: .01em; }
        .ai-brand-sub { color: var(--ai-muted); font-size: 10px; margin-top: 2px; }
        .ai-new-chat {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--ai-line);
            border-radius: 10px;
            background: #fff;
            color: var(--ai-blue);
            transition: .18s ease;
        }
        .ai-new-chat:hover { background: var(--ai-blue); color: #fff; border-color: var(--ai-blue); }

        .ai-history-search { padding: 0 14px 10px; }
        .ai-history-search input {
            width: 100%;
            height: 36px;
            border: 1px solid var(--ai-line);
            border-radius: 10px;
            background: #fff;
            padding: 0 11px;
            font-size: 12px;
            color: var(--ai-ink);
            outline: none;
        }
        .ai-history-search input:focus { border-color: var(--ai-blue-2); box-shadow: 0 0 0 3px rgba(10, 122, 168, .10); }

        .ai-history-list { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 2px 8px 14px; }
        .ai-history-label { padding: 8px 8px 6px; font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: #8b9aa5; }
        .ai-history-row {
            display: flex;
            align-items: center;
            gap: 2px;
            border-radius: 10px;
        }
        .ai-history-row:hover { background: rgba(0, 89, 129, .08); }
        .ai-history-item {
            display: block;
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            border-radius: 10px;
            background: transparent;
            padding: 9px 10px;
            text-align: left;
            color: #31424f;
            font-size: 12px;
            line-height: 1.35;
            transition: .16s ease;
        }
        .ai-history-item:hover, .ai-history-item.is-active { background: rgba(0, 89, 129, .08); color: var(--ai-blue); }
        .ai-history-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            margin-right: 3px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #8b9aa5;
            opacity: 0;
            transition: .16s ease;
        }
        .ai-history-row:hover .ai-history-delete,
        .ai-history-delete:focus-visible { opacity: 1; }
        .ai-history-delete:hover { background: rgba(217, 83, 79, .14); color: #d9534f; }
        @media (hover: none) {
            .ai-history-delete { opacity: 1; }
        }
        .ai-history-item .title { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
        .ai-history-item .meta { display: flex; align-items: center; gap: 6px; margin-top: 3px; color: #8b9aa5; font-size: 10px; }
        .ai-history-empty { padding: 18px 12px; color: var(--ai-muted); font-size: 12px; line-height: 1.5; }
        .ai-history-foot { border-top: 1px solid var(--ai-line); padding: 12px 14px; font-size: 11px; color: var(--ai-muted); }
        .ai-history-foot strong { color: var(--ai-ink); font-weight: 600; }

        .ai-main { position: relative; z-index: 2; display: flex; flex-direction: column; min-width: 0; min-height: 0; flex: 1 1 auto; }
        .ai-topbar {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 58px;
            padding: 10px 18px;
            border-bottom: 1px solid var(--ai-line);
            background: rgba(255, 255, 255, .68);
            backdrop-filter: blur(10px);
        }
        .ai-topbar-title { min-width: 0; }
        .ai-topbar-title h1 { margin: 0; font-size: 14px; font-weight: 700; letter-spacing: .01em; }
        .ai-topbar-title p { margin: 2px 0 0; font-size: 11px; color: var(--ai-muted); }
        .ai-topbar-spacer { flex: 1 1 auto; }
        .ai-quality-select {
            height: 34px;
            border: 1px solid var(--ai-line);
            border-radius: 9px;
            background: #fff;
            color: var(--ai-ink);
            font-size: 11px;
            font-weight: 600;
            padding: 0 9px;
            outline: none;
        }
        .ai-provider-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: 600;
            background: rgba(0, 89, 129, .08);
            color: var(--ai-blue);
            white-space: nowrap;
        }
        .ai-provider-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: #0f9f6e; box-shadow: 0 0 0 3px rgba(15, 159, 110, .12); }
        .ai-provider-pill.is-off .dot { background: #d9534f; box-shadow: 0 0 0 3px rgba(217, 83, 79, .12); }
        .ai-history-toggle { display: none; }

        .ai-stage { flex: 1 1 auto; min-height: 0; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #c7d4dd transparent; }
        .ai-landing { min-height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 34px 22px 18px; }
        .ai-hero { width: 100%; max-width: 860px; text-align: center; }
        .ai-hero-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            margin-bottom: 16px;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(145deg, var(--ai-blue), #0a7aa8 65%, var(--ai-gold));
            box-shadow: 0 16px 30px rgba(0, 89, 129, .22);
            font-size: 27px;
        }
        .ai-hero h2 { margin: 0; font-size: clamp(24px, 3.1vw, 36px); line-height: 1.1; letter-spacing: -.03em; font-weight: 700; }
        .ai-hero p { max-width: 620px; margin: 10px auto 0; color: var(--ai-muted); font-size: 13px; line-height: 1.6; }
        .ai-badges { display: flex; flex-wrap: wrap; justify-content: center; gap: 7px; margin-top: 14px; }
        .ai-badge { border: 1px solid var(--ai-line); border-radius: 999px; padding: 5px 9px; background: rgba(255, 255, 255, .75); color: #4f6270; font-size: 10px; font-weight: 600; }
        .ai-badge i { color: var(--ai-blue); }

        .ai-suggestions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; width: 100%; max-width: 860px; margin-top: 22px; }
        .ai-suggestion {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            min-height: 58px;
            padding: 11px 12px;
            border: 1px solid var(--ai-line);
            border-radius: 13px;
            background: rgba(255, 255, 255, .82);
            color: #344755;
            text-align: left;
            font-size: 12px;
            line-height: 1.45;
            transition: .18s ease;
        }
        .ai-suggestion:hover { border-color: rgba(0, 89, 129, .35); background: #fff; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0, 30, 46, .06); }
        .ai-suggestion i { flex: 0 0 auto; color: var(--ai-blue); font-size: 16px; margin-top: 1px; }
        .ai-capabilities { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px; width: 100%; max-width: 860px; margin-top: 18px; }
        .ai-capability { border: 1px solid var(--ai-line); border-radius: 12px; background: rgba(255,255,255,.62); padding: 10px; text-align: center; }
        .ai-capability i { display: block; color: var(--ai-blue); font-size: 18px; margin-bottom: 4px; }
        .ai-capability span { display: block; font-size: 10px; font-weight: 600; color: #526573; }

        .ai-chat-view { display: none; min-height: 100%; padding: 18px 22px 8px; }
        .ai-chat-view.is-visible { display: block; }
        .ai-landing.is-hidden { display: none; }
        .ai-messages { width: 100%; max-width: 900px; margin: 0 auto; }
        .ai-message { display: flex; gap: 11px; padding: 14px 0; }
        .ai-message + .ai-message { border-top: 1px solid rgba(219, 228, 234, .65); }
        .ai-message.user { flex-direction: row-reverse; }
        .ai-message.user .ai-message-body { display: flex; flex-direction: column; align-items: flex-end; }
        .ai-message.user .ai-message-role { justify-content: flex-end; }
        .ai-message.user .ai-content { display: flex; justify-content: flex-end; width: 100%; }
        .ai-message-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
        }
        .ai-message.user .ai-message-avatar { background: rgba(0, 89, 129, .10); color: var(--ai-blue); }
        .ai-message.assistant .ai-message-avatar { background: linear-gradient(145deg, var(--ai-blue), #0a7aa8 70%, var(--ai-gold)); color: #fff; }
        .ai-message-body { min-width: 0; flex: 1 1 auto; }
        .ai-message-role { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 11px; font-weight: 700; color: #445866; }
        .ai-message-role small { font-weight: 500; color: #8b9aa5; }
        .ai-text { font-size: 13px; line-height: 1.68; color: #263a47; white-space: pre-wrap; overflow-wrap: anywhere; }
        .ai-message.user .ai-text { max-width: min(78%, 720px); padding: 10px 14px; border-radius: 16px 4px 16px 16px; background: linear-gradient(145deg, var(--ai-blue), #0a7aa8); color: #fff; }
        .ai-message.assistant .ai-text { max-width: 100%; color: #263a47; }
        .ai-status { display: flex; align-items: center; gap: 7px; color: var(--ai-muted); font-size: 12px; }
        .ai-status .spinner { width: 13px; height: 13px; border: 2px solid rgba(0, 89, 129, .2); border-top-color: var(--ai-blue); border-radius: 50%; animation: ai-spin .7s linear infinite; }
        @keyframes ai-spin { to { transform: rotate(360deg); } }
        .ai-error { border: 1px solid rgba(217, 83, 79, .25); background: rgba(217, 83, 79, .07); color: #9d2f2a; border-radius: 10px; padding: 9px 11px; font-size: 12px; }
        .ai-usage { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 7px; color: #8b9aa5; font-size: 10px; }
        .ai-message-actions { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
        .ai-message-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-height: 27px;
            padding: 3px 8px;
            border: 1px solid var(--ai-line);
            border-radius: 7px;
            background: #fff;
            color: #6b7d89;
            font-size: 10px;
            font-weight: 500;
            transition: .15s ease;
        }
        .ai-message-action:hover { color: var(--ai-blue); border-color: rgba(0, 89, 129, .35); background: #f6fafc; }
        .ai-message-action.is-active { color: var(--ai-blue); border-color: var(--ai-blue); background: rgba(0, 89, 129, .08); }
        .ai-message-action.is-down.is-active { color: #d9534f; border-color: rgba(217, 83, 79, .45); background: rgba(217, 83, 79, .08); }
        .ai-mic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--ai-line);
            border-radius: 10px;
            background: #fff;
            color: var(--ai-muted);
            font-size: 16px;
            transition: .16s ease;
        }
        .ai-mic:hover, .ai-mic.is-listening { color: #d9534f; border-color: rgba(217, 83, 79, .4); background: rgba(217, 83, 79, .08); }

        .ai-block { margin-top: 10px; }
        .ai-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; }
        .ai-metric { border: 1px solid var(--ai-line); border-radius: 12px; background: #fff; padding: 11px 12px; }
        .ai-metric-label { color: var(--ai-muted); font-size: 10px; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
        .ai-metric-value { margin-top: 3px; font-size: 22px; line-height: 1.05; font-weight: 700; color: var(--ai-ink); }
        .ai-metric-unit { color: var(--ai-muted); font-size: 11px; font-weight: 500; }
        .ai-metric-delta { display: inline-block; margin-top: 5px; font-size: 10px; font-weight: 600; color: var(--ai-blue); }
        .ai-visual-card { border: 1px solid var(--ai-line); border-radius: 13px; background: #fff; overflow: hidden; }
        .ai-visual-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 12px; border-bottom: 1px solid rgba(219, 228, 234, .7); }
        .ai-visual-head h4 { margin: 0; font-size: 12px; font-weight: 700; color: #2d4250; }
        .ai-visual-head span { color: #8b9aa5; font-size: 10px; }
        .ai-table-wrap { overflow-x: auto; }
        .ai-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        .ai-table th, .ai-table td { padding: 8px 11px; border-bottom: 1px solid rgba(219, 228, 234, .65); text-align: left; vertical-align: top; }
        .ai-table th { background: #f5f9fb; color: #526573; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
        .ai-table tr:last-child td { border-bottom: 0; }
        .ai-chart { min-height: 220px; padding: 8px 10px 2px; }
        .ai-sources { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .ai-source { display: inline-flex; align-items: center; gap: 5px; border: 1px solid var(--ai-line); border-radius: 999px; background: #fff; padding: 4px 8px; color: #526573; font-size: 10px; text-decoration: none; }
        .ai-source:hover { border-color: var(--ai-blue); color: var(--ai-blue); }
        .ai-source i { color: var(--ai-blue); }

        .ai-composer-wrap { position: sticky; bottom: 0; z-index: 5; padding: 8px 22px 16px; background: linear-gradient(to top, var(--ai-canvas) 70%, rgba(246, 250, 252, 0)); }
        .ai-composer { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #cbd9e2; border-radius: 17px; background: #fff; box-shadow: 0 10px 30px rgba(0, 30, 46, .08); transition: .18s ease; }
        .ai-composer:focus-within { border-color: var(--ai-blue-2); box-shadow: 0 10px 32px rgba(0, 89, 129, .12), 0 0 0 3px rgba(10, 122, 168, .08); }
        .ai-composer textarea { display: block; width: 100%; min-height: 54px; max-height: 180px; resize: none; border: 0; outline: none; background: transparent; padding: 15px 15px 4px; color: var(--ai-ink); font-size: 13px; line-height: 1.5; font-family: inherit; }
        .ai-composer textarea::placeholder { color: #93a3ae; }
        .ai-composer-foot { display: flex; align-items: center; gap: 8px; padding: 5px 10px 9px 13px; }
        .ai-composer-hint { flex: 1 1 auto; color: #8b9aa5; font-size: 10px; }
        .ai-send { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border: 0; border-radius: 10px; color: #fff; background: linear-gradient(145deg, var(--ai-blue), #0a7aa8); font-size: 16px; transition: .18s ease; }
        .ai-send:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .ai-send:disabled { opacity: .45; transform: none; cursor: not-allowed; }
        .ai-disclaimer { max-width: 900px; margin: 7px auto 0; text-align: center; color: #8b9aa5; font-size: 10px; }

        .ai-alert { max-width: 900px; margin: 12px auto 0; border: 1px solid rgba(255, 174, 1, .35); border-radius: 12px; background: rgba(255, 174, 1, .09); color: #7a5600; padding: 9px 12px; font-size: 12px; }

        /* ── Mode gelap halaman AI ─────────────────────────────── */
        [data-bs-theme="dark"] .ai-shell {
            --ai-ink: #e8f0f5;
            --ai-muted: #9fb2bf;
            --ai-line: #28414f;
            --ai-blue: #4cc3f0;
            --ai-blue-2: #7dd3fc;
            --ai-gold: #ffbd33;
            --ai-surface: #102631;
            --ai-canvas: #071720;
            background:
                radial-gradient(80% 70% at 100% 0%, rgba(76, 195, 240, .10) 0%, transparent 55%),
                radial-gradient(60% 60% at 0% 100%, rgba(255, 189, 51, .07) 0%, transparent 55%),
                var(--ai-canvas);
        }
        [data-bs-theme="dark"] .ai-shell .ai-history,
        [data-bs-theme="dark"] .ai-shell .ai-topbar {
            background: rgba(8, 24, 33, .86);
            border-color: var(--ai-line);
        }
        [data-bs-theme="dark"] .ai-shell .ai-history-search input,
        [data-bs-theme="dark"] .ai-shell .ai-new-chat,
        [data-bs-theme="dark"] .ai-shell .ai-quality-select,
        [data-bs-theme="dark"] .ai-shell .ai-composer,
        [data-bs-theme="dark"] .ai-shell .ai-metric,
        [data-bs-theme="dark"] .ai-shell .ai-visual-card,
        [data-bs-theme="dark"] .ai-shell .ai-source,
        [data-bs-theme="dark"] .ai-shell .ai-badge,
        [data-bs-theme="dark"] .ai-shell .ai-suggestion,
        [data-bs-theme="dark"] .ai-shell .ai-capability {
            background: #102631;
            border-color: var(--ai-line);
            color: var(--ai-ink);
        }
        [data-bs-theme="dark"] .ai-shell .ai-suggestion:hover,
        [data-bs-theme="dark"] .ai-shell .ai-history-item:hover,
        [data-bs-theme="dark"] .ai-shell .ai-history-item.is-active,
        [data-bs-theme="dark"] .ai-shell .ai-history-row:hover {
            background: #163644;
            border-color: #3b5968;
            color: var(--ai-blue);
        }
        [data-bs-theme="dark"] .ai-shell .ai-history-delete { color: #8fa6b3; }
        [data-bs-theme="dark"] .ai-shell .ai-message-action,
        [data-bs-theme="dark"] .ai-shell .ai-mic {
            background: #102631;
            border-color: var(--ai-line);
            color: #a8bcc8;
        }
        [data-bs-theme="dark"] .ai-shell .ai-message-action:hover,
        [data-bs-theme="dark"] .ai-shell .ai-mic:hover {
            background: #163644;
            border-color: #3b5968;
            color: var(--ai-blue);
        }
        [data-bs-theme="dark"] .ai-shell .ai-message-action.is-active { color: var(--ai-blue); border-color: var(--ai-blue); }
        [data-bs-theme="dark"] .ai-shell .ai-message-action.is-down.is-active { color: #ffb4b1; border-color: rgba(217, 83, 79, .55); }
        [data-bs-theme="dark"] .ai-shell .ai-history-item { color: #c4d5df; }
        [data-bs-theme="dark"] .ai-shell .ai-history-item .meta,
        [data-bs-theme="dark"] .ai-shell .ai-history-foot,
        [data-bs-theme="dark"] .ai-shell .ai-history-empty,
        [data-bs-theme="dark"] .ai-shell .ai-topbar-title p,
        [data-bs-theme="dark"] .ai-shell .ai-hero p,
        [data-bs-theme="dark"] .ai-shell .ai-badge,
        [data-bs-theme="dark"] .ai-shell .ai-capability span,
        [data-bs-theme="dark"] .ai-shell .ai-composer-hint,
        [data-bs-theme="dark"] .ai-shell .ai-disclaimer,
        [data-bs-theme="dark"] .ai-shell .ai-status,
        [data-bs-theme="dark"] .ai-shell .ai-usage,
        [data-bs-theme="dark"] .ai-shell .ai-metric-label,
        [data-bs-theme="dark"] .ai-shell .ai-visual-head span,
        [data-bs-theme="dark"] .ai-shell .ai-source,
        [data-bs-theme="dark"] .ai-shell .ai-table th,
        [data-bs-theme="dark"] .ai-shell .ai-message-role small {
            color: var(--ai-muted);
        }
        [data-bs-theme="dark"] .ai-shell .ai-brand-name,
        [data-bs-theme="dark"] .ai-shell .ai-topbar-title h1,
        [data-bs-theme="dark"] .ai-shell .ai-hero h2,
        [data-bs-theme="dark"] .ai-shell .ai-metric-value,
        [data-bs-theme="dark"] .ai-shell .ai-visual-head h4,
        [data-bs-theme="dark"] .ai-shell .ai-composer textarea {
            color: var(--ai-ink);
        }
        [data-bs-theme="dark"] .ai-shell .ai-composer textarea::placeholder { color: #78909c; }
        [data-bs-theme="dark"] .ai-shell .ai-composer-wrap {
            background: linear-gradient(to top, #071720 70%, rgba(7, 23, 32, 0));
        }
        [data-bs-theme="dark"] .ai-shell .ai-composer { box-shadow: 0 10px 30px rgba(0, 0, 0, .28); }
        [data-bs-theme="dark"] .ai-shell .ai-text,
        [data-bs-theme="dark"] .ai-shell .ai-message.assistant .ai-text,
        [data-bs-theme="dark"] .ai-shell .ai-message-role,
        [data-bs-theme="dark"] .ai-shell .ai-table td,
        [data-bs-theme="dark"] .ai-shell .ai-source,
        [data-bs-theme="dark"] .ai-shell .ai-history-foot strong {
            color: #d7e5ed;
        }
        [data-bs-theme="dark"] .ai-shell .ai-message.user .ai-text { color: #fff; }
        [data-bs-theme="dark"] .ai-shell .ai-message + .ai-message,
        [data-bs-theme="dark"] .ai-shell .ai-visual-head,
        [data-bs-theme="dark"] .ai-shell .ai-table th,
        [data-bs-theme="dark"] .ai-shell .ai-table td,
        [data-bs-theme="dark"] .ai-shell .ai-history-foot {
            border-color: var(--ai-line);
        }
        [data-bs-theme="dark"] .ai-shell .ai-table th { background: #0b202b; }
        [data-bs-theme="dark"] .ai-shell .ai-table tbody tr:hover { background: #0f2a36; }
        [data-bs-theme="dark"] .ai-shell .ai-alert {
            background: rgba(255, 189, 51, .10);
            border-color: rgba(255, 189, 51, .35);
            color: #ffd77a;
        }
        [data-bs-theme="dark"] .ai-shell .ai-error {
            background: rgba(217, 83, 79, .12);
            border-color: rgba(217, 83, 79, .35);
            color: #ffb4b1;
        }

        @media (max-width: 1100px) {
            .ai-history { position: absolute; inset: 0 auto 0 0; z-index: 20; width: 280px; transform: translateX(-105%); transition: transform .2s ease; box-shadow: 20px 0 40px rgba(0,30,46,.12); }
            .ai-history.is-open { transform: translateX(0); }
            .ai-history-toggle { display: inline-flex; }
            .ai-capabilities { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .ai-shell { height: calc(100dvh - 92px); min-height: 0; border-radius: 16px; }
            .ai-suggestions { grid-template-columns: 1fr; }
            .ai-capabilities { display: none; }
            .ai-landing { padding: 24px 14px 12px; }
            .ai-chat-view { padding: 12px 12px 4px; }
            .ai-composer-wrap { padding: 6px 12px 12px; }
            .ai-topbar { padding: 9px 12px; }
            .ai-topbar-title p { display: none; }
            .ai-provider-pill { display: none; }
        }
    </style>
@endsection

@section('content')
    @php
        $userId = $userId ?? auth()->id();
    @endphp

    <div class="ai-shell" id="aiApp"
         data-stream-url="{{ route('user.ai.stream', ['userId' => $userId]) }}"
         data-fallback-url="{{ route('user.ai-chat.send', ['userId' => $userId]) }}"
         data-show-url="{{ route('user.ai-chat.show', ['userId' => $userId, 'conversation' => '00000000-0000-0000-0000-000000000000']) }}"
         data-delete-url="{{ route('user.ai-chat.destroy', ['userId' => $userId, 'conversation' => '00000000-0000-0000-0000-000000000000']) }}"
         data-feedback-url="{{ route('user.ai-chat.feedback', ['userId' => $userId, 'conversation' => '00000000-0000-0000-0000-000000000000']) }}"
         data-conversations-url="{{ route('user.ai-chat.conversations', ['userId' => $userId]) }}"
         data-provider-ready="{{ $providerReady ? '1' : '0' }}"
         data-models="{{ json_encode($models) }}">

        {{-- RIWAYAT --}}
        <aside class="ai-history" id="aiHistory">
            <div class="ai-history-head">
                <div class="ai-brand">
                    <span class="ai-brand-mark"><i class="ri-sparkling-2-fill"></i></span>
                    <div>
                        <div class="ai-brand-name">ALIM AI</div>
                        <div class="ai-brand-sub">Analis data pesantren</div>
                    </div>
                </div>
                <button type="button" class="ai-new-chat" id="aiNewChat" title="Percakapan baru"><i class="ri-add-line"></i></button>
            </div>
            <div class="ai-history-search">
                <input type="search" id="aiHistorySearch" placeholder="Cari riwayat..." autocomplete="off">
            </div>
            <div class="ai-history-list" id="aiHistoryList">
                <div class="ai-history-label">Riwayat terbaru</div>
                @forelse($conversations as $conversation)
                    <div class="ai-history-row" data-history-row="{{ $conversation->id }}">
                        <button type="button" class="ai-history-item" data-conversation-id="{{ $conversation->id }}">
                            <span class="title">{{ $conversation->title ?: 'Percakapan' }}</span>
                            <span class="meta">
                                <i class="ri-time-line"></i>
                                {{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at?->diffForHumans() }}
                                @if($conversation->model) · {{ $conversation->model }} @endif
                            </span>
                        </button>
                        <button type="button" class="ai-history-delete" data-delete-conversation="{{ $conversation->id }}" title="Hapus riwayat" aria-label="Hapus riwayat">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                @empty
                    <div class="ai-history-empty" id="aiHistoryEmpty">Belum ada riwayat. Mulai pertanyaan pertama Anda.</div>
                @endforelse
            </div>
            <div class="ai-history-foot">
                <div><i class="ri-shield-check-line me-1 text-success"></i>Cakupan: <strong>{{ $scopeLabel }}</strong></div>
                <div class="mt-1"><i class="ri-lock-line me-1"></i>NIK/KK, payroll, dan data sensitif dibatasi.</div>
            </div>
        </aside>

        {{-- AREA UTAMA --}}
        <main class="ai-main">
            <header class="ai-topbar">
                <button type="button" class="ai-new-chat ai-history-toggle" id="aiHistoryToggle" title="Riwayat"><i class="ri-menu-2-line"></i></button>
                <div class="ai-topbar-title">
                    <h1>ALIM AI</h1>
                    <p>{{ $scopeLabel }} · jawaban otomatis dari data aplikasi</p>
                </div>
                <div class="ai-topbar-spacer"></div>
                <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode" title="Mode gelap / terang" aria-label="Mode gelap / terang">
                    <i class="bx bx-moon fs-18"></i>
                </button>
                <span class="ai-provider-pill {{ $providerReady ? '' : 'is-off' }}" id="aiProviderPill">
                    <span class="dot"></span>
                    {{ $providerReady ? ($providerLabel ?: 'Provider aktif') : 'Provider belum aktif' }}
                    @if($providerReady && !empty($providerModel))
                        <span class="text-muted">· {{ $providerModel }}</span>
                    @endif
                </span>
                <select class="ai-quality-select" id="aiQuality" aria-label="Kualitas jawaban">
                    <option value="flash">Cepat</option>
                    <option value="pro">Analisis berat</option>
                </select>
            </header>

            @if(! $providerReady)
                <div class="ai-alert mx-3">
                    <i class="ri-error-warning-line me-1"></i>
                    Provider AI belum aktif. Super Admin perlu mengisi API key DeepSeek dan mengaktifkan provider di Pengaturan AI.
                </div>
            @endif

            <div class="ai-stage" id="aiStage">
                {{-- LANDING --}}
                <section class="ai-landing" id="aiLanding">
                    <div class="ai-hero">
                        <span class="ai-hero-mark"><i class="ri-sparkling-2-fill"></i></span>
                        <h2>Ada yang bisa saya bantu?</h2>
                        <p>Tanyakan data santri, presensi, nilai, tahfidz, LJK, atau cari informasi terbaru dari internet. Saya akan mengambil data sesuai izin Anda dan menyajikannya dalam tabel, grafik, atau ringkasan dengan sumber.</p>
                        <div class="ai-badges">
                            <span class="ai-badge"><i class="ri-shield-check-line me-1"></i>Akses sesuai peran</span>
                            <span class="ai-badge"><i class="ri-bar-chart-2-line me-1"></i>Visual otomatis</span>
                            <span class="ai-badge"><i class="ri-global-line me-1"></i>Web real-time</span>
                            <span class="ai-badge"><i class="ri-database-2-line me-1"></i>Sumber data ditampilkan</span>
                        </div>
                    </div>

                    <div class="ai-suggestions">
                        @foreach($suggestions as $index => $suggestion)
                            <button type="button" class="ai-suggestion" data-suggestion="{{ $suggestion }}">
                                <i class="{{ ['ri-line-chart-line', 'ri-table-line', 'ri-team-line', 'ri-file-chart-line', 'ri-book-open-line'][$index % 5] }}"></i>
                                <span>{{ $suggestion }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="ai-capabilities">
                        <div class="ai-capability"><i class="ri-team-line"></i><span>Santri</span></div>
                        <div class="ai-capability"><i class="ri-calendar-check-line"></i><span>Presensi</span></div>
                        <div class="ai-capability"><i class="ri-file-chart-line"></i><span>Nilai & Rapor</span></div>
                        <div class="ai-capability"><i class="ri-book-read-line"></i><span>Tahfidz</span></div>
                        <div class="ai-capability"><i class="ri-scan-2-line"></i><span>LJK</span></div>
                        <div class="ai-capability"><i class="ri-global-line"></i><span>Web</span></div>
                    </div>
                </section>

                {{-- CHAT --}}
                <section class="ai-chat-view" id="aiChatView">
                    <div class="ai-messages" id="aiMessages"></div>
                </section>
            </div>

            <div class="ai-composer-wrap">
                <form class="ai-composer" id="aiComposer">
                    <textarea id="aiInput" rows="1" placeholder="Tanya data aplikasi atau cari informasi dari internet..." autocomplete="off"></textarea>
                    <div class="ai-composer-foot">
                        <span class="ai-composer-hint"><i class="ri-enter-line me-1"></i>Enter untuk kirim · Shift+Enter baris baru</span>
                        <button type="button" class="ai-mic" id="aiMic" title="Dikte suara" aria-label="Dikte suara"><i class="ri-mic-line"></i></button>
                        <button type="submit" class="ai-send" id="aiSend" title="Kirim"><i class="ri-arrow-up-line"></i></button>
                    </div>
                </form>
                <p class="ai-disclaimer">ALIM AI dapat membuat kesalahan. Periksa kembali data penting dan sumber yang ditampilkan.</p>
            </div>
        </main>
    </div>

    {{-- MODAL: HAPUS RIWAYAT --}}
    <div class="modal fade" id="deleteHistoryModal" tabindex="-1" aria-labelledby="deleteHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="deleteHistoryForm" action="#">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-body text-center p-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h4 class="mb-2" id="deleteHistoryModalLabel">Hapus Riwayat?</h4>
                        <p class="text-muted mb-4">Percakapan <strong id="deleteHistoryTitle">ini</strong> akan dihapus permanen.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i>Hapus</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('aiApp');
    if (!app) return;

    document.body.classList.add('ai-page');

    const streamUrl = app.dataset.streamUrl;
    const fallbackUrl = app.dataset.fallbackUrl;
    const showUrlTemplate = app.dataset.showUrl;
    const deleteUrlTemplate = app.dataset.deleteUrl;
    const feedbackUrlTemplate = app.dataset.feedbackUrl;
    const conversationsUrl = app.dataset.conversationsUrl;
    const providerReady = app.dataset.providerReady === '1';
    const models = JSON.parse(app.dataset.models || '{}');

    const history = document.getElementById('aiHistory');
    const historyList = document.getElementById('aiHistoryList');
    const historySearch = document.getElementById('aiHistorySearch');
    const landing = document.getElementById('aiLanding');
    const chatView = document.getElementById('aiChatView');
    const messages = document.getElementById('aiMessages');
    const composer = document.getElementById('aiComposer');
    const input = document.getElementById('aiInput');
    const sendButton = document.getElementById('aiSend');
    const quality = document.getElementById('aiQuality');
    const stage = document.getElementById('aiStage');

    let conversationId = null;
    let streaming = false;
    let chartCounter = 0;
    let pendingCharts = [];

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    async function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        const area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    }

    function messageText(article) {
        return article?.querySelector('.ai-content')?.innerText?.trim() || '';
    }

    function addMessageActions(article, messageId, rating) {
        if (!article || article.classList.contains('user')) return;

        let actions = article.querySelector('.ai-message-actions');
        if (actions) {
            if (messageId) actions.dataset.messageId = messageId;
            if (rating === 'up' || rating === 'down') {
                actions.querySelector('[data-action="' + rating + '"]')?.classList.add('is-active');
            }
            return;
        }

        const body = article.querySelector('.ai-message-body');
        if (!body) return;

        actions = document.createElement('div');
        actions.className = 'ai-message-actions';
        actions.dataset.messageId = messageId || '';

        [
            {action: 'copy', icon: 'ri-file-copy-line', label: 'Salin'},
            {action: 'retry', icon: 'ri-refresh-line', label: 'Ulangi'},
            {action: 'up', icon: 'ri-thumb-up-line', label: 'Membantu'},
            {action: 'down', icon: 'ri-thumb-down-line', label: 'Kurang'},
            {action: 'share', icon: 'ri-share-line', label: 'Bagikan'},
        ].forEach(function (item) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ai-message-action' + (item.action === 'down' ? ' is-down' : '');
            button.dataset.action = item.action;
            button.title = item.label;
            button.innerHTML = '<i class="' + item.icon + '"></i><span>' + item.label + '</span>';
            if ((item.action === 'up' || item.action === 'down') && !messageId) {
                button.disabled = true;
            }
            actions.appendChild(button);
        });

        actions.addEventListener('click', function (event) {
            const button = event.target.closest('.ai-message-action');
            if (!button || button.disabled) return;

            const action = button.dataset.action;
            const id = actions.dataset.messageId || messageId;

            if (action === 'copy') {
                copyToClipboard(messageText(article)).then(function () {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="ri-check-line"></i><span>Tersalin</span>';
                    setTimeout(function () { button.innerHTML = original; }, 1200);
                }).catch(function () { alert('Gagal menyalin jawaban.'); });
            }

            if (action === 'retry') {
                regenerateMessage(article);
            }

            if (action === 'up' || action === 'down') {
                actions.querySelectorAll('[data-action="up"], [data-action="down"]').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });

                if (id && conversationId && feedbackUrlTemplate) {
                    const url = feedbackUrlTemplate.replace('00000000-0000-0000-0000-000000000000', conversationId);
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({message_id: id, rating: action}),
                    }).catch(function () {});
                }
            }

            if (action === 'share') {
                shareMessage(id);
            }
        });

        body.appendChild(actions);

        if (rating === 'up' || rating === 'down') {
            actions.querySelector('[data-action="' + rating + '"]')?.classList.add('is-active');
        }
    }

    function regenerateMessage(article) {
        let previous = article.previousElementSibling;
        while (previous && !previous.classList.contains('user')) {
            previous = previous.previousElementSibling;
        }

        const prompt = previous?.querySelector('.ai-content')?.innerText?.trim() || '';
        if (!prompt) return;

        newChat();
        sendMessage(prompt);
    }

    function shareMessage(messageId) {
        const url = new URL(window.location.href);
        if (conversationId) {
            url.searchParams.set('conversation', conversationId);
        }

        if (navigator.share) {
            navigator.share({
                title: 'ALIM AI',
                text: 'Jawaban ALIM AI',
                url: url.toString(),
            }).catch(function () {});
            return;
        }

        copyToClipboard(url.toString())
            .then(function () { alert('Tautan percakapan disalin.'); })
            .catch(function () { alert('Gagal membagikan tautan.'); });
    }

    function setMode(mode) {
        const isChat = mode === 'chat';
        landing.classList.toggle('is-hidden', isChat);
        chatView.classList.toggle('is-visible', isChat);
        if (isChat) {
            stage.scrollTop = stage.scrollHeight;
        }
    }

    function setBusy(busy) {
        streaming = busy;
        sendButton.disabled = busy || !providerReady;
        input.disabled = busy || !providerReady;
    }

    function scrollBottom() {
        stage.scrollTop = stage.scrollHeight;
    }

    function formatTokens(value) {
        const number = Number(value || 0);
        return number >= 1000 ? (number / 1000).toFixed(1).replace('.0', '') + 'k' : String(number);
    }

    function formatCost(value) {
        const number = Number(value || 0);
        return number > 0 ? '$' + number.toFixed(5) : 'gratis';
    }

    function usageHtml(usage, cost) {
        const prompt = formatTokens(usage?.prompt_tokens);
        const completion = formatTokens(usage?.completion_tokens);
        const total = formatTokens(usage?.total_tokens);

        return '<span title="Prompt: ' + prompt + ' token · Output: ' + completion + ' token"><i class="ri-flashlight-line me-1"></i>' + total + ' token</span>'
            + '<span class="text-muted">input ' + prompt + ' · output ' + completion + '</span>'
            + '<span><i class="ri-wallet-3-line me-1"></i>' + formatCost(cost) + '</span>';
    }

    function createClientMessageId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
            const random = Math.random() * 16 | 0;
            const value = character === 'x' ? random : (random & 0x3 | 0x8);

            return value.toString(16);
        });
    }

    function createMessage(role, content, blocks, meta) {
        const article = document.createElement('article');
        article.className = 'ai-message ' + role;

        const avatar = document.createElement('div');
        avatar.className = 'ai-message-avatar';
        avatar.innerHTML = role === 'assistant' ? '<i class="ri-sparkling-2-fill"></i>' : '<i class="ri-user-line"></i>';

        const body = document.createElement('div');
        body.className = 'ai-message-body';

        const roleLine = document.createElement('div');
        roleLine.className = 'ai-message-role';
        roleLine.innerHTML = role === 'assistant'
            ? 'ALIM AI <small>' + escapeHtml(meta?.model || '') + '</small>'
            : 'Anda <small>' + new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'}) + '</small>';

        const contentNode = document.createElement('div');
        contentNode.className = 'ai-content';

        body.appendChild(roleLine);
        body.appendChild(contentNode);
        article.appendChild(avatar);
        article.appendChild(body);

        if (role === 'assistant' && Array.isArray(blocks) && blocks.length) {
            renderBlocks(contentNode, blocks);
            if (meta?.usage || meta?.cost) {
                const usage = document.createElement('div');
                usage.className = 'ai-usage';
                usage.innerHTML = usageHtml(meta?.usage, meta?.cost);
                body.appendChild(usage);
            }
        } else if (content) {
            const text = document.createElement('div');
            text.className = 'ai-text';
            text.textContent = content;
            contentNode.appendChild(text);
        }

        messages.appendChild(article);
        flushCharts();
        scrollBottom();

        if (role === 'assistant' && meta?.messageId) {
            addMessageActions(article, meta.messageId, meta.rating);
            scrollBottom();
        }

        return { article, contentNode, body };
    }

    function renderBlocks(container, blocks) {
        container.innerHTML = '';

        (blocks || []).forEach(function (block) {
            if (!block || !block.type) return;

            if (block.type === 'text') {
                const text = document.createElement('div');
                text.className = 'ai-text';
                text.textContent = block.text || '';
                container.appendChild(text);
                return;
            }

            if (block.type === 'metric') {
                const wrap = document.createElement('div');
                wrap.className = 'ai-block ai-metrics';
                wrap.innerHTML = '<div class="ai-metric"><div class="ai-metric-label">' + escapeHtml(block.label) + '</div><div class="ai-metric-value">' + escapeHtml(block.value) + (block.unit ? ' <span class="ai-metric-unit">' + escapeHtml(block.unit) + '</span>' : '') + '</div>' + (block.delta ? '<span class="ai-metric-delta">' + escapeHtml(block.delta) + '</span>' : '') + '</div>';
                container.appendChild(wrap);
                return;
            }

            if (block.type === 'table') {
                const card = document.createElement('div');
                card.className = 'ai-block ai-visual-card';
                const columns = (block.columns || []).map(function (column) {
                    return '<th>' + escapeHtml(column) + '</th>';
                }).join('');
                const rows = (block.rows || []).map(function (row) {
                    return '<tr>' + (Array.isArray(row) ? row : [row]).map(function (cell) {
                        return '<td>' + escapeHtml(cell) + '</td>';
                    }).join('') + '</tr>';
                }).join('');

                card.innerHTML = (block.title ? '<div class="ai-visual-head"><h4>' + escapeHtml(block.title) + '</h4><span>' + (block.rows || []).length + ' baris</span></div>' : '') +
                    '<div class="ai-table-wrap"><table class="ai-table"><thead><tr>' + columns + '</tr></thead><tbody>' + rows + '</tbody></table></div>';
                container.appendChild(card);
                return;
            }

            if (block.type === 'chart') {
                const card = document.createElement('div');
                card.className = 'ai-block ai-visual-card';
                const chartId = 'aiChart' + (++chartCounter);
                card.innerHTML = '<div class="ai-visual-head"><h4>' + escapeHtml(block.title || 'Grafik') + '</h4><span>' + escapeHtml((block.unit || '') + ' · ' + (block.chart_type || 'bar')) + '</span></div><div class="ai-chart" id="' + chartId + '"></div>';
                container.appendChild(card);
                pendingCharts.push({ id: chartId, block: block });
                return;
            }

            if (block.type === 'source') {
                const sources = container.querySelector('.ai-sources') || document.createElement('div');
                sources.className = 'ai-sources';
                const chip = document.createElement(block.url ? 'a' : 'span');
                chip.className = 'ai-source';
                if (block.url) {
                    chip.href = block.url;
                    chip.target = '_blank';
                    chip.rel = 'noopener noreferrer';
                }
                const icon = block.url ? 'ri-global-line' : 'ri-database-2-line';
                chip.innerHTML = '<i class="' + icon + '"></i>' + escapeHtml(block.label) + (block.detail ? '<span class="text-muted">· ' + escapeHtml(block.detail) + '</span>' : '');
                sources.appendChild(chip);
                container.appendChild(sources);
            }
        });

        flushCharts();
    }

    function flushCharts() {
        if (!window.ApexCharts || pendingCharts.length === 0) return;

        const charts = pendingCharts;
        pendingCharts = [];

        charts.forEach(function (item) {
            const element = document.getElementById(item.id);
            if (!element) return;

            const block = item.block || {};
            const chartType = ['bar', 'line', 'area', 'pie', 'donut'].includes(block.chart_type) ? block.chart_type : 'bar';
            const isPie = chartType === 'pie' || chartType === 'donut';
            const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const axisColor = dark ? '#9fb2bf' : '#6b7d89';
            const gridColor = dark ? '#28414f' : '#e6eef3';
            const series = (block.series || []).map(function (serie) {
                return {
                    name: serie.name || 'Nilai',
                    data: isPie ? Number((serie.data || [0])[0] || 0) : (serie.data || []),
                };
            });

            const options = isPie ? {
                chart: { type: chartType, height: 260, toolbar: { show: false }, foreColor: axisColor },
                labels: block.labels || [],
                series: series.map(function (serie) { return serie.data; }),
                legend: { position: 'bottom', fontSize: '11px', labels: { colors: axisColor } },
                dataLabels: { enabled: true },
                colors: ['#005981', '#0a7aa8', '#ffae01', '#0f9f6e', '#d9534f', '#6c7a89'],
                stroke: { width: 0 },
                tooltip: { theme: dark ? 'dark' : 'light' },
            } : {
                chart: { type: chartType, height: 260, toolbar: { show: false }, fontFamily: 'IBM Plex Sans, sans-serif', foreColor: axisColor },
                series: series,
                xaxis: { categories: block.labels || [], labels: { style: { fontSize: '10px', colors: axisColor } } },
                yaxis: { labels: { style: { fontSize: '10px', colors: axisColor } } },
                colors: ['#005981', '#0a7aa8', '#ffae01', '#0f9f6e', '#d9534f', '#6c7a89'],
                stroke: { curve: 'smooth', width: chartType === 'bar' ? 0 : 2.5 },
                fill: chartType === 'area' ? { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .04 } } : { opacity: 1 },
                dataLabels: { enabled: false },
                grid: { borderColor: gridColor, strokeDashArray: 3 },
                tooltip: { theme: dark ? 'dark' : 'light' },
                legend: { position: 'bottom', fontSize: '11px', labels: { colors: axisColor } },
            };

            try {
                new ApexCharts(element, options).render();
            } catch (error) {
                element.innerHTML = '<div class="text-muted small p-3">Grafik tidak dapat dirender.</div>';
            }
        });
    }

    function addAssistantStatus(statusText) {
        const { contentNode, body } = createMessage('assistant', '', null, {});
        const status = document.createElement('div');
        status.className = 'ai-status';
        status.innerHTML = '<span class="spinner"></span><span>' + escapeHtml(statusText || 'Memproses...') + '</span>';
        contentNode.appendChild(status);

        return { contentNode, body, status };
    }

    function clearStatus(assistant) {
        if (assistant?.status && typeof assistant.status.remove === 'function') {
            assistant.status.remove();
        }
        if (assistant) assistant.status = null;
    }

    function appendText(container, text) {
        let node = container.querySelector('.ai-stream-text');
        if (!node) {
            node = document.createElement('div');
            node.className = 'ai-text ai-stream-text';
            container.appendChild(node);
        }
        node.textContent += text;
    }

    function parseSseBlock(raw) {
        let event = 'message';
        const dataLines = [];
        raw.split('\n').forEach(function (line) {
            if (line.startsWith('event:')) event = line.slice(6).trim();
            if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
        });
        if (!dataLines.length) return null;
        try { return { event, data: JSON.parse(dataLines.join('\n')) }; } catch (e) { return null; }
    }

    async function sendMessage(text) {
        text = String(text || '').trim();
        if (!text || streaming || !providerReady || window.alimAiSending) return;

        window.alimAiSending = true;

        setMode('chat');
        createMessage('user', text, null, {});
        const clientMessageId = createClientMessageId();
        input.value = '';
        resizeInput();
        setBusy(true);
        const assistant = addAssistantStatus('Mengambil data...');

        try {
            const response = await fetch(streamUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId,
                    quality: quality.value || 'flash',
                    page: window.location.pathname,
                    client_message_id: clientMessageId,
                }),
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            if (!response.body || !String(response.headers.get('content-type') || '').includes('text/event-stream')) {
                throw new Error('Streaming tidak tersedia');
            }

            clearStatus(assistant);
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const parts = buffer.split('\n\n');
                buffer = parts.pop() || '';

                parts.forEach(function (part) {
                    const parsed = parseSseBlock(part);
                    if (!parsed) return;

                    if (parsed.event === 'status') {
                        assistant.status = document.createElement('div');
                        assistant.status.className = 'ai-status';
                        assistant.status.innerHTML = '<span class="spinner"></span><span>' + escapeHtml(parsed.data.message || 'Memproses...') + '</span>';
                        assistant.contentNode.appendChild(assistant.status);
                    }

                    if (parsed.event === 'delta') {
                        clearStatus(assistant);
                        appendText(assistant.contentNode, parsed.data.text || '');
                        scrollBottom();
                    }

                    if (parsed.event === 'blocks') {
                        clearStatus(assistant);
                        assistant.contentNode.innerHTML = '';
                        renderBlocks(assistant.contentNode, parsed.data.blocks || []);
                        scrollBottom();
                    }

                    if (parsed.event === 'done') {
                        conversationId = parsed.data.conversation_id || conversationId;
                        if (parsed.data.usage || parsed.data.cost) {
                            const usage = document.createElement('div');
                            usage.className = 'ai-usage';
                            usage.innerHTML = usageHtml(parsed.data.usage, parsed.data.cost);
                            assistant.body.appendChild(usage);
                        }
                        addMessageActions(assistant.article, parsed.data.message_id);
                        scrollBottom();
                        refreshHistory();
                    }

                    if (parsed.event === 'error') {
                        clearStatus(assistant);
                        assistant.contentNode.innerHTML = '<div class="ai-error">' + escapeHtml(parsed.data.message || 'AI gagal menjawab.') + '</div>';
                    }
                });
            }
        } catch (error) {
            clearStatus(assistant);
            assistant.contentNode.innerHTML = '<div class="ai-error">Koneksi AI terputus. Mencoba mode biasa...</div>';

            try {
                const response = await fetch(fallbackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: text,
                        conversation_id: conversationId,
                        quality: quality.value || 'flash',
                        page: window.location.pathname,
                        client_message_id: clientMessageId,
                    }),
                });
                const data = await response.json();

                if (!data.ok) {
                    assistant.contentNode.innerHTML = '<div class="ai-error">' + escapeHtml(data.message || 'AI gagal menjawab.') + '</div>';
                } else {
                    conversationId = data.conversation_id || conversationId;
                    assistant.contentNode.innerHTML = '';
                    renderBlocks(assistant.contentNode, data.blocks || [{ type: 'text', text: data.reply || '' }]);
                    addMessageActions(assistant.article, data.message_id);
                    scrollBottom();
                    refreshHistory();
                }
            } catch (fallbackError) {
                assistant.contentNode.innerHTML = '<div class="ai-error">AI tidak dapat dihubungi. Coba lagi.</div>';
            }
        } finally {
            window.alimAiSending = false;
            setBusy(false);
            scrollBottom();
        }
    }

    async function loadConversation(id) {
        try {
            const url = showUrlTemplate.replace('00000000-0000-0000-0000-000000000000', id);
            const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!data.ok) return;

            conversationId = data.conversation.id;
            setMode('chat');
            messages.innerHTML = '';
            (data.messages || []).forEach(function (message) {
                const meta = { messageId: message.id, model: message.model, usage: message.usage, cost: message.cost, rating: message.rating };
                if (message.role === 'user') {
                    createMessage('user', message.content, null, meta);
                } else if (message.role === 'assistant' || message.role === 'admin') {
                    createMessage('assistant', message.content, message.blocks, meta);
                }
            });
            markActiveHistory(id);
            history.classList.remove('is-open');
        } catch (error) {
            // Diamkan; pengguna tetap bisa memakai composer.
        }
    }

    async function refreshHistory() {
        try {
            const response = await fetch(conversationsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!data.ok) return;

            historyList.innerHTML = '<div class="ai-history-label">Riwayat terbaru</div>';
            if (!data.items.length) {
                historyList.innerHTML += '<div class="ai-history-empty">Belum ada riwayat. Mulai pertanyaan pertama Anda.</div>';
                return;
            }

            data.items.forEach(function (item) {
                const row = document.createElement('div');
                row.className = 'ai-history-row';
                row.dataset.historyRow = item.id;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ai-history-item' + (item.id === conversationId ? ' is-active' : '');
                button.dataset.conversationId = item.id;
                button.innerHTML = '<span class="title">' + escapeHtml(item.title || 'Percakapan') + '</span><span class="meta"><i class="ri-time-line"></i>' + escapeHtml(item.last_message_at ? new Date(item.last_message_at).toLocaleString('id-ID', {day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'}) : '-') + (item.model ? ' · ' + escapeHtml(item.model) : '') + '</span>';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'ai-history-delete';
                remove.dataset.deleteConversation = item.id;
                remove.title = 'Hapus riwayat';
                remove.setAttribute('aria-label', 'Hapus riwayat');
                remove.innerHTML = '<i class="ri-delete-bin-line"></i>';

                row.appendChild(button);
                row.appendChild(remove);
                historyList.appendChild(row);
            });
        } catch (error) {
            // Abaikan.
        }
    }

    function markActiveHistory(id) {
        historyList.querySelectorAll('.ai-history-item').forEach(function (item) {
            item.classList.toggle('is-active', item.dataset.conversationId === id);
        });
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 180) + 'px';
    }

    function newChat() {
        conversationId = null;
        messages.innerHTML = '';
        setMode('landing');
        markActiveHistory(null);
        history.classList.remove('is-open');
        input.focus();
    }

    // Events
    composer.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage(input.value);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage(input.value);
        }
    });

    input.addEventListener('input', resizeInput);

    document.querySelectorAll('.ai-suggestion').forEach(function (button) {
        button.addEventListener('click', function () {
            input.value = this.dataset.suggestion || '';
            resizeInput();
            sendMessage(input.value);
        });
    });

    let pendingDeleteId = null;

    historyList.addEventListener('click', function (event) {
        const remove = event.target.closest('.ai-history-delete');
        if (remove) {
            pendingDeleteId = remove.dataset.deleteConversation;
            const row = remove.closest('.ai-history-row');
            const title = row?.querySelector('.title')?.textContent || 'ini';
            document.getElementById('deleteHistoryTitle').textContent = title;
            document.getElementById('deleteHistoryForm').action = deleteUrlTemplate.replace('00000000-0000-0000-0000-000000000000', pendingDeleteId);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteHistoryModal')).show();
            return;
        }

        const item = event.target.closest('.ai-history-item');
        if (item) loadConversation(item.dataset.conversationId);
    });

    historySearch.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        historyList.querySelectorAll('.ai-history-row').forEach(function (row) {
            row.style.display = (row.textContent || '').toLowerCase().includes(term) ? '' : 'none';
        });
    });

    document.getElementById('aiNewChat').addEventListener('click', newChat);
    document.getElementById('aiHistoryToggle').addEventListener('click', function () {
        history.classList.toggle('is-open');
    });

    document.getElementById('deleteHistoryForm').addEventListener('submit', function (event) {
        event.preventDefault();

        if (!pendingDeleteId) return;

        const id = pendingDeleteId;
        const modalElement = document.getElementById('deleteHistoryModal');

        fetch(this.action, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);

            document.querySelector('[data-history-row="' + id + '"]')?.remove();

            if (conversationId === id) newChat();

            bootstrap.Modal.getInstance(modalElement)?.hide();
            pendingDeleteId = null;
        }).catch(function () {
            alert('Gagal menghapus riwayat. Coba lagi.');
        });
    });

    // Dikte suara (Web Speech API)
    const micButton = document.getElementById('aiMic');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let listening = false;

    if (micButton) {
        if (!SpeechRecognition) {
            micButton.addEventListener('click', function () {
                alert('Browser ini belum mendukung dikte suara. Gunakan Chrome/Edge terbaru.');
            });
        } else {
            recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = true;
            recognition.continuous = false;

            recognition.onstart = function () {
                listening = true;
                micButton.classList.add('is-listening');
                micButton.innerHTML = '<i class="ri-stop-circle-line"></i>';
                micButton.title = 'Hentikan dikte';
            };

            recognition.onend = function () {
                listening = false;
                micButton.classList.remove('is-listening');
                micButton.innerHTML = '<i class="ri-mic-line"></i>';
                micButton.title = 'Dikte suara';
            };

            recognition.onerror = function () {
                listening = false;
                micButton.classList.remove('is-listening');
                micButton.innerHTML = '<i class="ri-mic-line"></i>';
            };

            recognition.onresult = function (event) {
                let finalText = '';
                for (let index = event.resultIndex; index < event.results.length; index++) {
                    const transcript = event.results[index][0]?.transcript || '';
                    if (event.results[index].isFinal) {
                        finalText += transcript;
                    }
                }

                if (finalText.trim() !== '') {
                    input.value = (input.value ? input.value.trim() + ' ' : '') + finalText.trim();
                    resizeInput();
                }
            };

            micButton.addEventListener('click', function () {
                if (streaming) return;

                if (listening) {
                    recognition.stop();
                } else {
                    try {
                        recognition.start();
                    } catch (error) {
                        alert('Dikte suara tidak dapat dimulai.');
                    }
                }
            });
        }
    }

    quality.addEventListener('change', function () {
        if (models[this.value] && !models[this.value].ready) {
            this.value = 'flash';
        }
    });

    setBusy(false);
    if (input) input.focus();

    const initialConversation = new URLSearchParams(window.location.search).get('conversation');
    if (initialConversation) {
        loadConversation(initialConversation);
    }
});
</script>
@endpush
