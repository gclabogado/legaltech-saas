<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Estudio Juridico | Software legal open source para estudios juridicos</title>
    <link rel="canonical" href="https://example.com/">
    <meta name="description" content="Software legal open source para estudios juridicos y abogados: leads, cotizaciones, directorio profesional y workspace operativo en una base lista para forkear e implementar.">
    <meta property="og:title" content="Tu Estudio Juridico | Software legal open source para estudios juridicos">
    <meta property="og:description" content="Software legal open source para estudios juridicos y abogados: leads, cotizaciones, directorio profesional y workspace operativo en una base lista para forkear e implementar.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://example.com/">
    <meta property="og:image" content="https://example.com/og-lawyers-1200x630.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Tu Estudio Juridico | Software legal open source para estudios juridicos">
    <meta name="twitter:description" content="Software legal open source para estudios juridicos y abogados: leads, cotizaciones, directorio profesional y workspace operativo en una base lista para forkear e implementar.">
    <meta name="twitter:image" content="https://example.com/og-lawyers-1200x630.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        :root {
            --landing-bg: #eef3fb;
            --landing-ink: #0f172a;
            --landing-muted: #5b6777;
            --landing-line: rgba(15, 23, 42, .10);
            --landing-brand: #0a66c2;
            --landing-brand-deep: #0f4c81;
            --landing-panel: rgba(255, 255, 255, .78);
        }
        body {
            margin: 0;
            color: var(--landing-ink);
            background:
                radial-gradient(780px 420px at 0% 0%, rgba(10,102,194,.11), transparent 55%),
                radial-gradient(720px 360px at 100% 0%, rgba(15,76,129,.10), transparent 48%),
                linear-gradient(180deg, #f4f7fc 0%, #eef3fb 46%, #f8fbff 100%);
        }
        .landing-shell {
            max-width: 1320px;
            margin: 0 auto;
            padding: 22px 20px 64px;
            display: grid;
            gap: 22px;
        }
        .landing-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 20px;
            border: 1px solid var(--landing-line);
            background: rgba(255,255,255,.72);
            backdrop-filter: blur(12px);
            box-shadow: 0 18px 40px rgba(15,23,42,.06);
        }
        .landing-brand {
            display: grid;
            gap: 2px;
        }
        .landing-brand strong {
            font-size: 20px;
            letter-spacing: -.03em;
        }
        .landing-brand span {
            font-size: 13px;
            color: var(--landing-muted);
            font-weight: 600;
        }
        .landing-top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .landing-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--landing-muted);
        }
        .landing-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(360px, .95fr);
            gap: 18px;
            align-items: stretch;
        }
        .landing-open-source {
            display: grid;
            gap: 22px;
            padding: 34px;
            border-radius: 32px;
            border: 1px solid rgba(10,102,194,.12);
            background:
                radial-gradient(220px 220px at 14% 18%, rgba(255,255,255,.10), transparent 74%),
                radial-gradient(320px 180px at 0% 0%, rgba(255,255,255,.14), transparent 72%),
                radial-gradient(340px 180px at 100% 100%, rgba(255,255,255,.08), transparent 74%),
                linear-gradient(145deg, #091625 0%, #0d2947 48%, #0a66c2 100%);
            color: #f8fbff;
            box-shadow: 0 30px 60px rgba(15,23,42,.16);
            text-align: center;
            justify-items: center;
            overflow: hidden;
        }
        .landing-open-source-head {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .landing-open-source-kicker {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,.16);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .landing-open-source h2 {
            margin: 0;
            max-width: 980px;
            font-size: 52px;
            line-height: .94;
            letter-spacing: -.05em;
        }
        .landing-open-source p {
            margin: 0;
            font-size: 18px;
            line-height: 1.6;
            color: rgba(239,246,255,.84);
            max-width: 760px;
        }
        .landing-open-source-lead {
            display: grid;
            gap: 14px;
            max-width: 980px;
        }
        .landing-open-source-proof {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .landing-open-source-proof span {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.08);
            color: rgba(239,246,255,.94);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .03em;
        }
        .landing-open-source-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .landing-open-source-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 52px;
            padding: 0 18px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.08);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
        }
        .landing-open-source-link.primary {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 14px 24px rgba(15,23,42,.18);
            border-color: transparent;
        }
        .landing-open-source-meta {
            font-size: 12px;
            color: rgba(239,246,255,.74);
            font-weight: 700;
        }
        .landing-open-source-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .landing-open-source-badge {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.08);
            color: rgba(239,246,255,.94);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .01em;
        }
        .landing-open-source-stats {
            display: grid;
            gap: 12px;
            width: 100%;
            max-width: 860px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .landing-open-source-stat {
            display: grid;
            gap: 6px;
            padding: 16px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.07);
            text-align: left;
        }
        .landing-open-source-stat span {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(239,246,255,.64);
            font-weight: 800;
        }
        .landing-open-source-stat strong {
            font-size: 24px;
            line-height: 1;
            letter-spacing: -.04em;
        }
        .landing-open-source-stat small {
            font-size: 13px;
            line-height: 1.5;
            color: rgba(239,246,255,.78);
        }
        .landing-decision-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .landing-decision-card {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.08);
        }
        .landing-decision-card.is-light {
            border-color: rgba(15,23,42,.08);
            background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.9));
            color: var(--landing-ink);
        }
        .landing-decision-card strong {
            font-size: 22px;
            letter-spacing: -.03em;
        }
        .landing-decision-card p {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
        }
        .landing-decision-card.is-light p {
            color: var(--landing-muted);
        }
        .landing-decision-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .landing-hero-main {
            padding: 28px;
            border-radius: 30px;
            border: 1px solid rgba(10,102,194,.12);
            background:
                radial-gradient(280px 170px at 0% 0%, rgba(255,255,255,.14), transparent 72%),
                radial-gradient(280px 170px at 100% 100%, rgba(255,255,255,.09), transparent 72%),
                linear-gradient(145deg, #0f172a 0%, #11335b 56%, #0a66c2 100%);
            color: #f8fbff;
            box-shadow: 0 30px 60px rgba(15,23,42,.18);
            display: grid;
            gap: 18px;
            align-content: start;
        }
        .landing-kicker {
            display: inline-flex;
            align-items: center;
            width: max-content;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.08);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .landing-hero-main h1 {
            margin: 0;
            font-size: 58px;
            line-height: .94;
            letter-spacing: -.05em;
            max-width: 700px;
        }
        .landing-hero-main p {
            margin: 0;
            max-width: 720px;
            font-size: 18px;
            line-height: 1.55;
            color: rgba(239,246,255,.86);
        }
        .landing-launch-strip {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .landing-launch-chip {
            display: grid;
            gap: 6px;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.08);
        }
        .landing-launch-chip span {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(239,246,255,.68);
        }
        .landing-launch-chip strong {
            font-size: 18px;
            line-height: 1.15;
            letter-spacing: -.03em;
        }
        .landing-launch-chip small {
            font-size: 12px;
            line-height: 1.45;
            color: rgba(239,246,255,.78);
        }
        .landing-hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .landing-btn {
            min-height: 52px;
            padding: 0 18px;
            border-radius: 16px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.01em;
        }
        .landing-btn.primary {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 14px 24px rgba(15,23,42,.16);
        }
        .landing-btn.secondary {
            background: rgba(255,255,255,.08);
            color: #ffffff;
            border-color: rgba(255,255,255,.18);
        }
        .landing-proof-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .landing-proof-chip {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.14);
            color: rgba(239,246,255,.92);
            font-size: 12px;
            font-weight: 700;
        }
        .landing-hero-side {
            display: grid;
            gap: 14px;
        }
        .landing-panel {
            padding: 18px;
            border-radius: 24px;
            border: 1px solid var(--landing-line);
            background: var(--landing-panel);
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 40px rgba(15,23,42,.08);
        }
        .landing-stats-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .landing-stat-card {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,.08);
            background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(248,250,252,.92));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
        }
        .landing-stat-card span {
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .landing-stat-card strong {
            display: block;
            margin-top: 8px;
            font-size: 34px;
            line-height: 1;
            letter-spacing: -.04em;
        }
        .landing-stat-card p {
            margin: 8px 0 0;
            font-size: 13px;
            line-height: 1.45;
            color: var(--landing-muted);
        }
        .landing-command-card {
            display: grid;
            gap: 14px;
        }
        .landing-command-card h2,
        .landing-audience-card h2,
        .landing-materias h2,
        .landing-value h2 {
            margin: 0;
            font-size: 24px;
            letter-spacing: -.03em;
        }
        .landing-command-card p,
        .landing-audience-card p,
        .landing-materias p,
        .landing-value p {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
            color: var(--landing-muted);
        }
        .landing-command-preview {
            display: grid;
            gap: 10px;
        }
        .landing-command-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 14px;
            border-radius: 18px;
            background: #f8fbff;
            border: 1px solid rgba(15,23,42,.08);
        }
        .landing-command-item strong {
            display: block;
            font-size: 14px;
        }
        .landing-command-item span {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: var(--landing-muted);
            line-height: 1.4;
        }
        .landing-command-pill {
            min-width: 72px;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e8f3ff;
            color: var(--landing-brand);
            font-size: 11px;
            font-weight: 800;
        }
        .landing-value {
            display: grid;
            gap: 16px;
        }
        .landing-value-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .landing-value-card {
            padding: 18px;
            border-radius: 22px;
            border: 1px solid var(--landing-line);
            background: rgba(255,255,255,.86);
            box-shadow: 0 16px 34px rgba(15,23,42,.06);
            display: grid;
            gap: 10px;
        }
        .landing-value-card strong {
            font-size: 18px;
            letter-spacing: -.02em;
        }
        .landing-value-card p {
            font-size: 13px;
            line-height: 1.55;
            color: var(--landing-muted);
        }
        .landing-audience-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .landing-audience-card {
            display: grid;
            gap: 14px;
        }
        .landing-list {
            display: grid;
            gap: 10px;
        }
        .landing-list-item {
            display: grid;
            gap: 4px;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid rgba(15,23,42,.08);
            background: #f8fbff;
        }
        .landing-list-item strong {
            font-size: 14px;
        }
        .landing-list-item span {
            font-size: 12px;
            line-height: 1.45;
            color: var(--landing-muted);
        }
        .landing-materias-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .landing-materia-card {
            display: grid;
            gap: 8px;
            padding: 16px;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,.08);
            background: #ffffff;
            box-shadow: 0 16px 34px rgba(15,23,42,.05);
        }
        .landing-materia-card strong {
            font-size: 16px;
            line-height: 1.2;
        }
        .landing-materia-card span {
            font-size: 12px;
            color: var(--landing-muted);
            line-height: 1.45;
        }
        .landing-materia-card a {
            color: var(--landing-brand);
            font-size: 12px;
            font-weight: 800;
        }
        .landing-interest {
            display: grid;
            gap: 18px;
            padding: 24px;
            border-radius: 28px;
            border: 1px solid rgba(10,102,194,.14);
            background:
                radial-gradient(300px 180px at 0% 0%, rgba(10,102,194,.10), transparent 72%),
                linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.94));
            box-shadow: 0 24px 44px rgba(15,23,42,.08);
        }
        .landing-interest-head {
            display: grid;
            gap: 10px;
            max-width: 760px;
        }
        .landing-interest-kicker {
            display: inline-flex;
            align-items: center;
            width: max-content;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(10,102,194,.12);
            background: rgba(10,102,194,.08);
            color: var(--landing-brand-deep);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .landing-interest-head h2 {
            margin: 0;
            font-size: 36px;
            line-height: .98;
            letter-spacing: -.04em;
        }
        .landing-interest-head p {
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
            color: var(--landing-muted);
        }
        .landing-interest-form {
            display: grid;
            gap: 14px;
        }
        .landing-interest-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .landing-interest-field {
            display: grid;
            gap: 8px;
        }
        .landing-interest-field.is-full {
            grid-column: 1 / -1;
        }
        .landing-interest-field label,
        .landing-interest-choice-title {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
        }
        .landing-interest-field input,
        .landing-interest-field textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(15,23,42,.10);
            background: #ffffff;
            color: var(--landing-ink);
            font: inherit;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
        }
        .landing-interest-field textarea {
            min-height: 128px;
            resize: vertical;
        }
        .landing-interest-choices {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .landing-interest-choice {
            position: relative;
            display: grid;
            gap: 6px;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.94);
            cursor: pointer;
        }
        .landing-interest-choice input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .landing-interest-choice strong {
            font-size: 18px;
            letter-spacing: -.02em;
        }
        .landing-interest-choice span {
            font-size: 14px;
            line-height: 1.5;
            color: var(--landing-muted);
        }
        .landing-interest-choice:has(input:checked) {
            border-color: rgba(10,102,194,.40);
            box-shadow: 0 0 0 3px rgba(10,102,194,.12);
            background: linear-gradient(180deg, rgba(236,244,255,.96), rgba(255,255,255,.98));
        }
        .landing-interest-submit {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: center;
        }
        .landing-interest-note {
            font-size: 13px;
            color: var(--landing-muted);
            font-weight: 600;
        }
        .landing-flash {
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 700;
        }
        .landing-flash.success {
            background: rgba(16,185,129,.10);
            color: #047857;
            border: 1px solid rgba(16,185,129,.20);
        }
        .landing-flash.error {
            background: rgba(239,68,68,.10);
            color: #b91c1c;
            border: 1px solid rgba(239,68,68,.16);
        }
        .landing-flash.info {
            background: rgba(59,130,246,.10);
            color: #1d4ed8;
            border: 1px solid rgba(59,130,246,.16);
        }
        .landing-honeypot {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
        .landing-bottom-cta {
            display: grid;
            gap: 14px;
            padding: 24px;
            border-radius: 28px;
            border: 1px solid rgba(10,102,194,.12);
            background:
                radial-gradient(340px 180px at 0% 100%, rgba(10,102,194,.12), transparent 72%),
                radial-gradient(260px 150px at 100% 0%, rgba(15,76,129,.10), transparent 70%),
                linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
            box-shadow: 0 24px 48px rgba(15,23,42,.08);
            text-align: center;
        }
        .landing-bottom-cta h2 {
            margin: 0;
            font-size: 34px;
            letter-spacing: -.04em;
        }
        .landing-bottom-cta p {
            margin: 0;
            max-width: 760px;
            justify-self: center;
            font-size: 15px;
            line-height: 1.6;
            color: var(--landing-muted);
        }
        .landing-bottom-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .landing-btn.solid {
            background: linear-gradient(180deg, var(--landing-brand), var(--landing-brand-deep));
            color: #ffffff;
            box-shadow: 0 18px 30px rgba(10,102,194,.18);
        }
        .landing-btn.ghost {
            background: #ffffff;
            color: var(--landing-brand);
            border-color: rgba(10,102,194,.16);
        }
        .landing-footer {
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .landing-value > div:first-child h2,
        .landing-audience-card h2,
        .landing-materias h2 {
            color: #0f172a;
        }
        .landing-value > div:first-child p,
        .landing-audience-card p,
        .landing-materias p {
            max-width: 760px;
        }
        @media (max-width: 1100px) {
            .landing-hero,
            .landing-decision-grid,
            .landing-audience-grid,
            .landing-value-grid,
            .landing-materias-grid,
            .landing-interest-grid,
            .landing-interest-choices {
                grid-template-columns: 1fr;
            }
            .landing-hero-main h1 {
                font-size: 46px;
            }
        }
        @media (max-width: 760px) {
            .landing-shell {
                padding: 14px 12px 36px;
            }
            .landing-topbar {
                padding: 12px;
                align-items: start;
                flex-direction: column;
            }
            .landing-top-actions {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr;
            }
            .landing-link,
            .landing-top-actions .landing-btn {
                width: 100%;
                justify-content: center;
            }
            .landing-brand {
                width: 100%;
            }
            .landing-brand strong {
                font-size: 16px;
            }
            .landing-brand span {
                font-size: 12px;
                line-height: 1.35;
            }
            .landing-hero-main,
            .landing-panel,
            .landing-bottom-cta,
            .landing-interest {
                padding: 18px;
                border-radius: 22px;
            }
            .landing-interest-head h2 {
                font-size: 30px;
            }
            .landing-open-source {
                padding: 24px 18px;
                border-radius: 22px;
            }
            .landing-open-source h2 {
                font-size: 34px;
                line-height: 1.02;
            }
            .landing-open-source p {
                font-size: 16px;
            }
            .landing-open-source-stats {
                grid-template-columns: 1fr;
            }
            .landing-launch-strip {
                grid-template-columns: 1fr;
            }
            .landing-hero-main h1 {
                font-size: 34px;
                line-height: .96;
            }
            .landing-hero-main p {
                font-size: 15px;
            }
            .landing-hero-actions,
            .landing-decision-actions,
            .landing-bottom-actions {
                display: grid;
                grid-template-columns: 1fr;
            }
            .landing-btn {
                width: 100%;
            }
            .landing-stats-grid {
                grid-template-columns: 1fr;
            }
            .landing-bottom-cta h2 {
                font-size: 28px;
            }
            .landing-stat-card strong {
                font-size: 28px;
            }
        }
    </style>
</head>
<?php
    $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $isAdminTheme = ($themeEmail === 'gmcalderonlewin@gmail.com');
    $bodyThemeClass = 'theme-black' . ($isAdminTheme ? ' theme-admin' : ' theme-olive');
    $isLoggedIn = !empty($_SESSION['user_id']);
    $projectInterestOld = is_array($project_interest_old ?? null) ? $project_interest_old : [];
?>
<body class="<?= htmlspecialchars($bodyThemeClass) ?>">
    <main class="landing-shell">
        <header class="landing-topbar">
            <div class="landing-brand">
                <strong>Tu Estudio Juridico</strong>
                <span>Software legal open source para estudios juridicos, equipos y abogados independientes</span>
            </div>
            <div class="landing-top-actions">
                <a class="landing-link" href="/#interes-proyecto">Quiero una prueba</a>
                <?php if ($isLoggedIn): ?>
                    <a class="landing-btn ghost" href="/dashboard">Ir al dashboard</a>
                <?php else: ?>
                    <a class="landing-btn ghost" href="/acceso-profesional">Ver acceso profesional</a>
                <?php endif; ?>
            </div>
        </header>

        <section class="landing-open-source">
            <div class="landing-open-source-head">
                <span class="landing-open-source-kicker">SaaS Open Source</span>
                <span class="landing-open-source-meta">Release publico en GitHub</span>
            </div>
            <div class="landing-open-source-badges">
                <span class="landing-open-source-badge">MIT License</span>
                <span class="landing-open-source-badge">Preview Live</span>
                <span class="landing-open-source-badge">Fork-ready</span>
            </div>
            <div class="landing-open-source-lead">
                <h2>El software legal que ya corre, ya capta demanda y ahora tambien se puede implementar.</h2>
                <p>Esta base legaltech vive como codigo abierto y se presenta como producto B2B: una operacion para estudios juridicos y abogados que quieren captar, cotizar y operar mejor sobre una base real.</p>
            </div>
            <div class="landing-open-source-proof">
                <span>Codigo real, no template vacio</span>
                <span>Deploy documentado</span>
                <span>Base lista para demo, fork e implementacion</span>
            </div>
            <div class="landing-open-source-actions">
                <a class="landing-open-source-link primary" href="https://github.com/gclabogado/legaltech-saas" target="_blank" rel="noreferrer">Ver repositorio en GitHub</a>
                <a class="landing-open-source-link" href="https://github.com/gclabogado/legaltech-saas" target="_blank" rel="noreferrer">Explorar codigo y docs</a>
            </div>
            <div class="landing-open-source-stats">
                <article class="landing-open-source-stat">
                    <span>Stack</span>
                    <strong>PHP + Slim</strong>
                    <small>Arquitectura server-rendered, directa de leer y facil de desplegar.</small>
                </article>
                <article class="landing-open-source-stat">
                    <span>Release</span>
                    <strong>Open</strong>
                    <small>Codigo, documentacion y flujo de despliegue visibles desde GitHub.</small>
                </article>
                <article class="landing-open-source-stat">
                    <span>Uso ideal</span>
                    <strong>Fork + Ship</strong>
                    <small>Base lista para estudiar, adaptar y convertir en tu propio producto legal B2B.</small>
                </article>
            </div>
        </section>

        <section class="landing-hero">
            <div class="landing-hero-main">
                <span class="landing-kicker">SaaS legal para Chile</span>
                <h1>El software legal open source para estudios y abogados que quieren captar, cotizar y operar mejor.</h1>
                <p>Tu Estudio Juridico combina captacion comercial, directorio profesional y workspace operativo para firmas, equipos y abogados que quieren partir con una base legaltech real y visible.</p>

                <div class="landing-launch-strip">
                    <article class="landing-launch-chip">
                        <span>Go Live</span>
                        <strong>Preview real en linea</strong>
                        <small>Producto visible, dominio activo y flujo demo listo para revisar.</small>
                    </article>
                    <article class="landing-launch-chip">
                        <span>Open Repo</span>
                        <strong>Codigo listo para fork</strong>
                        <small>Base abierta con deploy, documentacion y estructura ya publicada.</small>
                    </article>
                    <article class="landing-launch-chip">
                        <span>Use Case</span>
                        <strong>B2B legal claro</strong>
                        <small>Captacion, cotizacion y operacion diaria en una sola propuesta.</small>
                    </article>
                </div>

                <div class="landing-decision-grid">
                    <article class="landing-decision-card is-light">
                        <strong>Quiero implementar esta base</strong>
                        <p>Solicita una prueba, revisa el producto y conversa como adaptar esta base open source a tu estudio, equipo o proyecto legal.</p>
                        <div class="landing-decision-actions">
                            <a class="landing-btn solid" href="/#interes-proyecto">Pedir una prueba</a>
                        </div>
                    </article>
                    <article class="landing-decision-card">
                        <strong>Quiero ver el producto funcionando</strong>
                        <p>Explora el acceso profesional y entiende como conviven leads, cotizaciones y operacion diaria en una sola capa de producto.</p>
                        <div class="landing-decision-actions">
                            <?php if ($isLoggedIn): ?>
                                <a class="landing-btn secondary" href="/dashboard">Ir al dashboard</a>
                            <?php else: ?>
                                <a class="landing-btn secondary" href="/acceso-profesional">Ver acceso profesional</a>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>

                <div class="landing-proof-row">
                    <span class="landing-proof-chip">Leads, cotizaciones y operacion en un solo flujo</span>
                    <span class="landing-proof-chip">Preparado para abogados, estudios y teams</span>
                    <span class="landing-proof-chip">Base lista para demo e implementacion</span>
                </div>
            </div>

            <div class="landing-hero-side">
                <section class="landing-stats-grid">
                    <article class="landing-stat-card">
                        <span>Profesionales</span>
                        <strong>+<?= number_format((int)($stats_inicio['profesionales'] ?? 0), 0, ',', '.') ?></strong>
                        <p>Abogados visibles en materias clave para Chile.</p>
                    </article>
                    <article class="landing-stat-card">
                        <span>Clientes</span>
                        <strong><?= number_format((int)($stats_inicio['clientes'] ?? 0), 0, ',', '.') ?></strong>
                        <p>Usuarios y demanda captada para convertir en conversación comercial o derivacion.</p>
                    </article>
                </section>

                <section class="landing-panel landing-command-card">
                    <h2>Demo operativa del producto</h2>
                    <p>Lo que se ve aqui no es un mockup. Es la capa visible de una base que ya combina captacion, ventas y workspace legal.</p>
                    <div class="landing-command-preview">
                        <div class="landing-command-item">
                            <div>
                                <strong>Inbox comercial</strong>
                                <span>Captura rapida, seguimiento comercial y estado del caso sin perder contexto.</span>
                            </div>
                            <span class="landing-command-pill">Leads</span>
                        </div>
                        <div class="landing-command-item">
                            <div>
                                <strong>Cotizador listo para venta</strong>
                                <span>Servicios, montos, anticipo y propuesta lista para WhatsApp, email o PDF.</span>
                            </div>
                            <span class="landing-command-pill">Ventas</span>
                        </div>
                        <div class="landing-command-item">
                            <div>
                                <strong>Workspace white-label</strong>
                                <span>Marca comercial, catalogo, rendimiento y team juridico en la misma capa.</span>
                            </div>
                            <span class="landing-command-pill">Deploy</span>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section class="landing-value">
            <div>
                <h2>Un sistema comercial y operativo para firmas legales</h2>
                <p>Tu Estudio Juridico no se presenta solo como directorio. La propuesta central es ayudar a estudios y abogados a captar demanda, ordenar cotizaciones y operar con una base mas cercana a un SaaS que a un sitio institucional.</p>
            </div>
            <div class="landing-value-grid">
                <article class="landing-value-card">
                    <strong>Captacion</strong>
                    <p>Directorio, materias, cobertura y discovery para convertir trafico legal en oportunidades reales para abogados y estudios.</p>
                </article>
                <article class="landing-value-card">
                    <strong>Operación comercial</strong>
                    <p>Leads, cotizaciones, seguimiento y cierre con una experiencia más cercana a un SaaS moderno que a un panel improvisado.</p>
                </article>
                <article class="landing-value-card">
                    <strong>Escalabilidad</strong>
                    <p>Catálogo, branding, suscripción y team jurídico para pasar de ejercicio individual a estudio u operación compartida.</p>
                </article>
            </div>
        </section>

        <section class="landing-audience-grid">
            <article class="landing-panel landing-audience-card">
                <h2>Para estudios y abogados</h2>
                <p>Activa un sistema que ayude a ordenar leads, cotizaciones, catalogo y operacion diaria con una narrativa claramente B2B.</p>
                <div class="landing-list">
                    <div class="landing-list-item">
                        <strong>Leads en una bandeja clara</strong>
                        <span>Prioriza rapido, responde mejor y evita enfriar oportunidades entrantes.</span>
                    </div>
                    <div class="landing-list-item">
                        <strong>Cotizaciones listas para enviar</strong>
                        <span>Desde catalogo de servicios hasta propuesta y PDF dentro del mismo flujo.</span>
                    </div>
                    <div class="landing-list-item">
                        <strong>Workspace legal moderno</strong>
                        <span>Marca, rendimiento, plan y team juridico dentro de un panel con ambicion SaaS.</span>
                    </div>
                </div>
            </article>

            <article class="landing-panel landing-audience-card">
                <h2>Para clientes y usuarios finales</h2>
                <p>La capa B2C sigue existiendo, pero como motor de adquisicion para el producto principal orientado a firmas legales.</p>
                <div class="landing-list">
                    <div class="landing-list-item">
                        <strong>Exploracion mas clara</strong>
                        <span>Materias, submaterias, ubicacion y cobertura para llegar mas rapido al profesional correcto.</span>
                    </div>
                    <div class="landing-list-item">
                        <strong>Perfiles con contexto</strong>
                        <span>Experiencia, especialidad y señales de confianza para orientar mejor la decision.</span>
                    </div>
                    <div class="landing-list-item">
                        <strong>Contacto mas rapido</strong>
                        <span>Menos friccion para conectar con el abogado correcto y menos ruido de directorio generico.</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="landing-value landing-materias">
            <div>
                <h2>Explora por materia</h2>
                <p>Usa las rutas por materia para entrar más rápido al directorio legal filtrado.</p>
            </div>
            <div class="landing-materias-grid">
                <?php foreach (($materias_inicio ?? []) as $mat): ?>
                    <article class="landing-materia-card">
                        <strong><?= htmlspecialchars((string)($mat['nombre'] ?? 'Materia')) ?></strong>
                        <span>Directorio filtrado y landing temática para orientar mejor el caso.</span>
                        <a href="<?= htmlspecialchars((string)($mat['explorar_href'] ?? '/explorar')) ?>">Explorar abogados</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="landing-interest" id="interes-proyecto">
            <div class="landing-interest-head">
                <span class="landing-interest-kicker">Contacto</span>
                <h2>¿Quieres una prueba o implementarlo en tu estudio?</h2>
                <p>Cuéntanos qué te interesa y te contactaremos desde el equipo del proyecto para coordinar una demo o conversar una implementación.</p>
            </div>

            <?php if (!empty($mensaje ?? null)): ?>
                <div class="landing-flash <?= htmlspecialchars((string)($tipo_mensaje ?? 'info')) ?>">
                    <?= htmlspecialchars((string)$mensaje) ?>
                </div>
            <?php endif; ?>

            <form class="landing-interest-form" method="post" action="/solicitar-proyecto">
                <div class="landing-honeypot" aria-hidden="true">
                    <label for="project-website">Website</label>
                    <input id="project-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="landing-interest-grid">
                    <div class="landing-interest-field">
                        <label for="project-nombre">Nombre</label>
                        <input id="project-nombre" type="text" name="nombre" value="<?= htmlspecialchars((string)($projectInterestOld['nombre'] ?? '')) ?>" required>
                    </div>
                    <div class="landing-interest-field">
                        <label for="project-email">Email</label>
                        <input id="project-email" type="email" name="email" value="<?= htmlspecialchars((string)($projectInterestOld['email'] ?? '')) ?>" required>
                    </div>
                    <div class="landing-interest-field is-full">
                        <label for="project-empresa">Estudio / empresa</label>
                        <input id="project-empresa" type="text" name="empresa" value="<?= htmlspecialchars((string)($projectInterestOld['empresa'] ?? '')) ?>" placeholder="Opcional">
                    </div>
                    <div class="landing-interest-field is-full">
                        <span class="landing-interest-choice-title">¿Qué te interesa?</span>
                        <div class="landing-interest-choices">
                            <label class="landing-interest-choice">
                                <input type="radio" name="interes" value="prueba" <?= (($projectInterestOld['interes'] ?? '') === 'prueba' || empty($projectInterestOld['interes'])) ? 'checked' : '' ?> required>
                                <strong>Quiero una prueba</strong>
                                <span>Muéstrenme el producto, el flujo y cómo podría verse en operación.</span>
                            </label>
                            <label class="landing-interest-choice">
                                <input type="radio" name="interes" value="implementar" <?= (($projectInterestOld['interes'] ?? '') === 'implementar') ? 'checked' : '' ?> required>
                                <strong>Quiero implementarlo</strong>
                                <span>Quiero conversar cómo llevar esta base a mi estudio o proyecto legal.</span>
                            </label>
                        </div>
                    </div>
                    <div class="landing-interest-field is-full">
                        <label for="project-mensaje">Mensaje</label>
                        <textarea id="project-mensaje" name="mensaje" placeholder="Cuéntanos brevemente qué necesitas o qué te gustaría ver."><?= htmlspecialchars((string)($projectInterestOld['mensaje'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="landing-interest-submit">
                    <button class="landing-btn solid" type="submit">Quiero que me contacten</button>
                    <span class="landing-interest-note">Si el entorno no tiene correo activo, verás un mensaje informativo en vez de un envío real.</span>
                </div>
            </form>
        </section>

        <section class="landing-bottom-cta">
            <h2>Tu Estudio Juridico quiere posicionarse como software legal, no solo como directorio.</h2>
            <p>La historia central del producto es clara: una base open source para estudios, equipos y abogados que quieren ver el software funcionando o implementarlo sobre su propia marca.</p>
            <div class="landing-bottom-actions">
                <a class="landing-btn solid" href="/#interes-proyecto">Quiero una prueba</a>
                <?php if ($isLoggedIn): ?>
                    <a class="landing-btn ghost" href="/dashboard">Ir al dashboard</a>
                <?php else: ?>
                    <a class="landing-btn ghost" href="/acceso-profesional">Ver acceso profesional</a>
                <?php endif; ?>
            </div>
        </section>

        <div class="landing-footer">© 2026 Tu Estudio Juridico · Directorio legal + workspace profesional</div>
    </main>
</body>
</html>
