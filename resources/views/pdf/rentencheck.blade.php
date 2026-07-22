<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Rentencheck – {{ $client->full_name }}</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,600&family=Geist:wght@300;400;500;600&display=swap');

    :root {
        --paper: #faf8f3;
        --surface: #ffffff;
        --surface-2: #f4f1e9;
        --ink: #22262f;
        --ink-2: #565b66;
        --ink-3: #8a8e98;
        --navy: #24314e;
        --navy-2: #1b2540;
        --green: #3e7a55;
        --green-bg: #eaf1eb;
        --red: #c15540;
        --red-bg: #f7ebe6;
        --amber: #c68a34;
        --border: #e6e1d6;
        --border-2: #d6d0c2;
        --gold: #b08a4f;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    @page {
        size: A4;
        margin: 18mm 16mm 20mm 16mm;
    }

    body {
        font-family: 'Geist', 'Helvetica Neue', sans-serif;
        font-size: 9.5px;
        line-height: 1.5;
        color: var(--ink);
        background: var(--paper);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    h1, h2, h3, .serif {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 500;
        letter-spacing: -0.01em;
        color: var(--navy);
    }

    .tnum { font-variant-numeric: tabular-nums; }
    .muted { color: var(--ink-2); }
    .faint { color: var(--ink-3); }

    /* ---- Masthead ---- */
    .masthead {
        background: var(--navy);
        color: #fff;
        border-radius: 10px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
    }
    .masthead::after {
        content: "";
        position: absolute;
        left: 22px; right: 22px; bottom: 14px;
        height: 2px;
        background: var(--gold);
        opacity: 0.7;
    }
    .brand-row { display: flex; align-items: center; gap: 10px; }
    .monogram {
        width: 28px; height: 28px; border-radius: 7px;
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.28);
        text-align: center; line-height: 28px;
        font-family: 'Fraunces', serif; font-weight: 600; font-size: 15px; color: #fff;
    }
    .brand-name { font-family: 'Geist', sans-serif; font-weight: 600; font-size: 13px; letter-spacing: 0.08em; }
    .brand-tag { margin-left: auto; font-size: 8px; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.7); }
    .report-title { font-family: 'Fraunces', serif; color: #fff; font-size: 25px; line-height: 1.1; margin-top: 22px; font-weight: 500; }
    .report-sub { font-size: 9px; color: rgba(255,255,255,0.75); margin-top: 4px; margin-bottom: 6px; }

    /* ---- Client strip ---- */
    .client-strip {
        display: flex; align-items: flex-end; justify-content: space-between;
        margin: 16px 0 4px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-2);
    }
    .client-name { font-family: 'Fraunces', serif; font-size: 19px; color: var(--navy); font-weight: 500; }
    .client-meta { font-size: 8.5px; color: var(--ink-2); margin-top: 3px; }
    .client-meta span { margin-right: 12px; }
    .pill {
        display: inline-block; font-size: 7.5px; font-weight: 600; letter-spacing: 0.08em;
        text-transform: uppercase; padding: 3px 9px; border-radius: 999px;
        background: var(--green-bg); color: var(--green); border: 1px solid #cfe0d2;
    }

    /* ---- Section headings ---- */
    .section { margin-top: 22px; page-break-inside: avoid; }
    .eyebrow {
        font-size: 8px; letter-spacing: 0.16em; text-transform: uppercase;
        color: var(--gold); font-weight: 600; margin-bottom: 4px;
    }
    .section > h2 { font-size: 16px; margin-bottom: 10px; }
    .lead { font-size: 9.5px; color: var(--ink-2); margin-bottom: 12px; max-width: 78%; }

    /* ---- KPI cards ---- */
    .kpis { display: flex; gap: 10px; }
    .kpi {
        flex: 1; background: var(--surface); border: 1px solid var(--border);
        border-radius: 9px; padding: 13px 14px;
    }
    .kpi .label { font-size: 7.5px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--ink-3); font-weight: 600; }
    .kpi .value { font-family: 'Fraunces', serif; font-size: 21px; font-weight: 500; margin-top: 7px; line-height: 1; }
    .kpi .hint { font-size: 8px; color: var(--ink-3); margin-top: 6px; }
    .kpi.accent-green { border-color: #cfe0d2; background: var(--green-bg); }
    .kpi.accent-green .value { color: var(--green); }
    .kpi.accent-red { border-color: #e8c9bf; background: var(--red-bg); }
    .kpi.accent-red .value { color: var(--red); }
    .kpi.accent-amber { border-color: #ecdcbc; background: #f8f1e2; }
    .kpi.accent-amber .value { color: var(--amber); }
    .kpi.accent-navy { background: var(--navy); border-color: var(--navy); }
    .kpi.accent-navy .label { color: rgba(255,255,255,0.65); }
    .kpi.accent-navy .value { color: #fff; }
    .kpi.accent-navy .hint { color: rgba(255,255,255,0.6); }

    /* ---- Parameter chips ---- */
    .chips { display: flex; gap: 8px; margin: 10px 0 4px; }
    .chip {
        flex: 1; background: var(--surface-2); border: 1px solid var(--border);
        border-radius: 7px; padding: 7px 10px;
    }
    .chip .k { font-size: 7px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ink-3); }
    .chip .v { font-size: 11px; font-weight: 600; color: var(--navy); margin-top: 2px; }

    /* ---- Data tables ---- */
    table.data { width: 100%; border-collapse: collapse; font-size: 8.5px; }
    table.data th {
        text-align: right; padding: 7px 6px; font-size: 7px; letter-spacing: 0.05em;
        text-transform: uppercase; color: var(--ink-2); font-weight: 600;
        border-bottom: 1.5px solid var(--border-2);
    }
    table.data th:first-child, table.data td:first-child { text-align: left; }
    table.data td { padding: 6px 6px; border-bottom: 1px solid var(--border); text-align: right; }
    table.data tbody tr:nth-child(even) { background: #fbfaf6; }
    table.data td.pos { color: var(--green); font-weight: 500; }
    table.data tr.total td {
        border-top: 1.5px solid var(--navy); border-bottom: none;
        font-weight: 600; color: var(--navy); padding-top: 8px;
    }
    table.data tr.wish td { font-weight: 600; color: var(--ink); }

    /* ---- Ledger (label/value rows) ---- */
    .ledger { width: 100%; border-collapse: collapse; font-size: 9px; }
    .ledger td { padding: 6px 2px; border-bottom: 1px solid var(--border); }
    .ledger td:last-child { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
    .ledger tr:last-child td { border-bottom: none; }
    .ledger .head td { color: var(--ink-2); font-weight: 400; }
    .val-red { color: var(--red); }
    .val-green { color: var(--green); }

    /* ---- Callout ---- */
    .callout {
        border-radius: 9px; padding: 12px 15px; margin-top: 12px;
        border: 1px solid var(--border);
    }
    .callout.warn { background: var(--red-bg); border-color: #e8c9bf; }
    .callout.ok { background: var(--green-bg); border-color: #cfe0d2; }
    .callout .big { font-family: 'Fraunces', serif; font-size: 15px; }
    .callout.warn .big { color: var(--red); }
    .callout.ok .big { color: var(--green); }

    /* ---- Definition grid (input appendix) ---- */
    .defs { width: 100%; border-collapse: collapse; }
    .defs td { width: 25%; padding: 9px 10px; border: 1px solid var(--border); background: var(--surface); vertical-align: top; }
    .defs .dl { font-size: 7px; letter-spacing: 0.06em; text-transform: uppercase; color: var(--ink-3); }
    .defs .dv { font-size: 10px; font-weight: 500; color: var(--ink); margin-top: 3px; }
    .yes { color: var(--green); font-weight: 600; }
    .no { color: var(--ink-3); font-weight: 500; }

    /* ---- Aspects ---- */
    .aspects { width: 100%; border-collapse: collapse; }
    .aspects td { padding: 6px 8px; border-bottom: 1px solid var(--border); font-size: 8.5px; }
    .aspects td:last-child { text-align: right; }
    .rate { display: inline-block; font-size: 7.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
    .rate.r5 { background: var(--red-bg); color: var(--red); }
    .rate.r4 { background: #f6ecdd; color: var(--amber); }
    .rate.r3 { background: var(--surface-2); color: var(--ink-2); }
    .rate.r2 { background: var(--green-bg); color: var(--green); }
    .rate.r1 { background: #eef0f2; color: var(--ink-3); }

    /* ---- Signatures ---- */
    .signs { display: flex; gap: 24px; margin-top: 20px; }
    .sign { flex: 1; }
    .sign .line { border-top: 1px solid var(--ink-2); margin-top: 34px; padding-top: 5px; font-size: 8px; color: var(--ink-2); }

    /* ---- Notes ---- */
    .notes {
        background: var(--surface); border: 1px solid var(--border); border-left: 3px solid var(--gold);
        border-radius: 6px; padding: 11px 13px; font-size: 9px; color: var(--ink); line-height: 1.55;
    }

    /* ---- Charts (inline SVG) ---- */
    .chart { margin-top: 14px; }
    .chart svg { width: 100%; height: auto; display: block; }
    .chart-cap { font-size: 8.5px; color: var(--ink-2); margin-bottom: 7px; }
    .legend { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 9px; font-size: 8px; color: var(--ink-2); }
    .legend .lg { display: flex; align-items: center; gap: 5px; }
    .legend .sw { width: 9px; height: 9px; border-radius: 2px; }
    .composition { height: 26px; border-radius: 5px; overflow: hidden; display: flex; }
    .composition .seg { height: 100%; }

    .disclaimer { margin-top: 20px; font-size: 7.5px; color: var(--ink-3); line-height: 1.5; }

    .foot {
        margin-top: 16px; padding-top: 10px; border-top: 1px solid var(--border-2);
        display: flex; justify-content: space-between; font-size: 7.5px; color: var(--ink-3);
    }
    .foot b { color: var(--navy); font-family: 'Geist', sans-serif; letter-spacing: 0.06em; }

    .page-break { page-break-before: always; }
</style>
</head>
<body>
@php
    $eur = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $pct = fn ($v, $d = 1) => number_format((float) $v, $d, ',', '.') . ' %';
    $s1 = $rentencheck->step_1_data ?? [];
    $s2 = $rentencheck->step_2_data ?? [];
    $s3 = $rentencheck->step_3_data ?? [];
    $s4 = $rentencheck->step_4_data ?? [];
    $s5 = $rentencheck->step_5_data ?? [];
    $gap = $analysis['gap'] ?? [];
    $totals = $analysis['totals'] ?? [];
    $pkv = $analysis['private_health_insurance'] ?? [];
    $netMonthly = ($totals['after_insurance'] ?? 0) - ($pkv['purchasing_power'] ?? 0) * 0 - ($pkv['monthly_at_retirement'] ?? 0);
    $hasGap = ($gap['has_gap'] ?? (($gap['monthly_today'] ?? 0) > 0));
@endphp

{{-- ============ MASTHEAD ============ --}}
<div class="masthead">
    <div class="brand-row">
        <div class="monogram">R</div>
        <div class="brand-name">RENTENBLICK.de</div>
        <div class="brand-tag">Rentenberatung</div>
    </div>
    <div class="report-title">Rentencheck &amp; Vorsorgeanalyse</div>
    <div class="report-sub">Individuelle Auswertung Ihrer Altersvorsorge und Versorgungslücke</div>
</div>

{{-- ============ CLIENT STRIP ============ --}}
<div class="client-strip">
    <div>
        <div class="client-name">{{ $client->full_name }}</div>
        <div class="client-meta">
            <span>{{ $client->email }}</span>
            <span>Rentencheck&nbsp;#{{ $rentencheck->id }}</span>
            <span>{{ $rentencheck->created_at->format('d.m.Y') }}</span>
            @if($advisor)<span>Berater: {{ $advisor->name }}</span>@endif
        </div>
    </div>
    <div><span class="pill">Abgeschlossen</span></div>
</div>

{{-- ============ EXECUTIVE SUMMARY ============ --}}
<div class="section">
    <div class="eyebrow">Auf einen Blick</div>
    <h2 class="serif">Ihre Versorgung im Ruhestand</h2>
    <div class="kpis">
        <div class="kpi accent-navy">
            <div class="label">Monatliche Nettoversorgung</div>
            <div class="value tnum">{{ $eur($netMonthly) }}</div>
            <div class="hint">Alle Quellen nach Steuern &amp; KV, zum Rentenbeginn</div>
        </div>
        @php
            $gapToday = (float) ($gap['monthly_today'] ?? 0);
            if ($gapToday > 0) {
                $gapTone = 'accent-red'; $gapVal = $gapToday;
                $gapHint = 'Monatlicher Fehlbetrag in heutiger Kaufkraft';
            } elseif ($hasGap) {
                $gapTone = 'accent-amber'; $gapVal = (float) ($gap['monthly_at_end'] ?? 0);
                $gapHint = 'Ab Alter '.($gap['first_gap_age'] ?? '').' — wächst bis '.($analysis['provisionEndAge'] ?? '').' auf diesen Betrag';
            } else {
                $gapTone = 'accent-green'; $gapVal = 0.0;
                $gapHint = 'Ihr Rentenwunsch ist voll gedeckt';
            }
        @endphp
        <div class="kpi {{ $gapTone }}">
            <div class="label">Versorgungslücke</div>
            <div class="value tnum">{{ $eur($gapVal) }}</div>
            <div class="hint">{{ $gapHint }}</div>
        </div>
        <div class="kpi">
            <div class="label">Rentenwunsch</div>
            <div class="value tnum" style="color: var(--navy);">{{ $eur(($analysis['desired_pension']['today'] ?? 0)) }}</div>
            <div class="hint">Gewünschte Nettorente (heutige Kaufkraft)</div>
        </div>
    </div>
</div>

{{-- ============ ANALYSIS (income table, gap, capital, disability) ============ --}}
@if(!empty($analysis))
    @include('pdf.partials.analysis-results')
@endif

{{-- ============ INPUT APPENDIX ============ --}}
<div class="section page-break">
    <div class="eyebrow">Grundlage der Berechnung</div>
    <h2 class="serif">Ihre Angaben</h2>

    @if($s1)
    <div style="margin-bottom:12px;">
        <table class="defs">
            <tr>
                <td><div class="dl">Beruf</div><div class="dv">{{ $s1['profession'] ?? '–' }}</div></td>
                <td><div class="dl">Familienstand</div><div class="dv">{{ $s1['maritalStatus'] ?? '–' }}</div></td>
                <td><div class="dl">Bruttoeinkommen (mtl.)</div><div class="dv tnum">{{ $eur($s1['currentGrossIncome'] ?? 0) }}</div></td>
                <td><div class="dl">Nettoeinkommen (mtl.)</div><div class="dv tnum">{{ $eur($s1['currentNetIncome'] ?? 0) }}</div></td>
            </tr>
            <tr>
                <td><div class="dl">Krankenversicherung</div><div class="dv">{{ $s1['healthInsurance'] ?? '–' }}</div></td>
                <td><div class="dl">Bundesland</div><div class="dv">{{ $s1['federalState'] ?? '–' }}</div></td>
                <td><div class="dl">Kinder</div><div class="dv">{{ ($s1['hasChildren'] ?? false) ? 'Ja' : 'Nein' }}</div></td>
                <td><div class="dl">Kirchensteuer</div><div class="dv">{{ ($s1['hasToChurchTax'] ?? false) ? 'Ja' : 'Nein' }}</div></td>
            </tr>
        </table>
    </div>
    @endif

    @if($s2)
    <div style="margin-bottom:12px;">
        <table class="defs">
            <tr>
                <td><div class="dl">Aktuelles Alter</div><div class="dv">{{ $s2['currentAge'] ?? '–' }} Jahre</div></td>
                <td><div class="dl">Rentenalter</div><div class="dv">{{ $s2['retirementAge'] ?? '–' }} Jahre</div></td>
                <td><div class="dl">Versorgung bis</div><div class="dv">{{ $s2['provisionDuration'] ?? '–' }} Jahre</div></td>
                <td><div class="dl">Angenommene Inflation</div><div class="dv tnum">{{ $pct($s2['assumedInflation'] ?? 0) }}</div></td>
            </tr>
            <tr>
                <td><div class="dl">Rentenwunsch (heute)</div><div class="dv tnum">{{ $eur($s2['pensionWishCurrentValue'] ?? 0) }}</div></td>
                <td><div class="dl">Garantierter Betrag</div><div class="dv tnum">{{ $eur($s2['guaranteedAmount'] ?? 0) }}</div></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
    @endif

    @if($s3)
    <table class="defs">
        <tr>
            <td><div class="dl">Gesetzliche Rente</div><div class="dv {{ ($s3['statutoryPensionClaims'] ?? false) ? 'yes' : 'no' }}">{{ ($s3['statutoryPensionClaims'] ?? false) ? 'Ja · '.$eur($s3['statutoryPensionAmount'] ?? 0) : 'Nein' }}</div></td>
            <td><div class="dl">Versorgungswerk</div><div class="dv {{ ($s3['professionalProvisionWorks'] ?? false) ? 'yes' : 'no' }}">{{ ($s3['professionalProvisionWorks'] ?? false) ? 'Ja · '.$eur($s3['professionalProvisionAmount'] ?? 0) : 'Nein' }}</div></td>
            <td><div class="dl">Zusatzvers. öffentl. Dienst</div><div class="dv {{ ($s3['publicServiceAdditionalProvision'] ?? false) ? 'yes' : 'no' }}">{{ ($s3['publicServiceAdditionalProvision'] ?? false) ? 'Ja · '.$eur($s3['publicServiceProvisionAmount'] ?? 0) : 'Nein' }}</div></td>
            <td><div class="dl">Beamtenversorgung</div><div class="dv {{ ($s3['civilServiceProvision'] ?? false) ? 'yes' : 'no' }}">{{ ($s3['civilServiceProvision'] ?? false) ? 'Ja · '.$eur($s3['civilServiceProvisionAmount'] ?? 0) : 'Nein' }}</div></td>
        </tr>
    </table>

    @if(!empty($s3['pensionContracts']))
    <div style="margin-top:10px;">
        <div class="dl" style="margin-bottom:5px; color: var(--ink-2);">Verträge</div>
        <table class="data">
            <thead><tr><th>Vertrag</th><th>Gesellschaft</th><th style="text-align:left;">Art</th><th>Monatlich</th></tr></thead>
            <tbody>
            @foreach($s3['pensionContracts'] as $c)
                <tr>
                    <td>{{ $c['contract'] ?? '–' }}</td>
                    <td style="text-align:right;">{{ $c['company'] ?? '–' }}</td>
                    <td style="text-align:left;">{{ $c['contractType'] ?? '–' }}</td>
                    <td class="tnum">{{ $eur($c['monthlyAmount'] ?? $c['amount'] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($s3['payoutContracts']))
    <div style="margin-top:10px;">
        <div class="dl" style="margin-bottom:5px; color: var(--ink-2);">Auszahlungsverträge (Kapital)</div>
        <table class="data">
            <thead><tr><th>Vertrag</th><th>Gesellschaft</th><th style="text-align:left;">Art</th><th>Garantiert</th><th>Prognose</th></tr></thead>
            <tbody>
            @foreach($s3['payoutContracts'] as $c)
                <tr>
                    <td>{{ $c['contract'] ?? '–' }}</td>
                    <td style="text-align:right;">{{ $c['company'] ?? '–' }}</td>
                    <td style="text-align:left;">{{ $c['contractType'] ?? '–' }}</td>
                    <td class="tnum">{{ $eur($c['guaranteedAmount'] ?? 0) }}</td>
                    <td class="tnum">{{ $eur($c['projectedAmount'] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($s3['additionalIncome']))
    <div style="margin-top:10px;">
        <div class="dl" style="margin-bottom:5px; color: var(--ink-2);">Weitere Einkünfte</div>
        <table class="data">
            <thead><tr><th>Art</th><th>Ab Jahr</th><th style="text-align:left;">Frequenz</th><th>Betrag</th></tr></thead>
            <tbody>
            @foreach($s3['additionalIncome'] as $inc)
                <tr>
                    <td>{{ $inc['type'] ?? '–' }}</td>
                    <td class="tnum">{{ $inc['startYear'] ?? '–' }}</td>
                    <td style="text-align:left;">{{ $inc['frequency'] ?? '–' }}</td>
                    <td class="tnum">{{ $eur($inc['amount'] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif
</div>

{{-- ============ ASPECTS ============ --}}
@if(!empty($s4['aspectRatings']))
@php
    $rateClass = function ($v) {
        $v = mb_strtolower((string) $v);
        if (str_contains($v, 'sehr')) return 'r5';
        if (str_contains($v, 'weniger')) return 'r2';
        if (str_contains($v, 'unwichtig')) return 'r1';
        if (str_contains($v, 'wichtig')) return 'r4';
        return 'r3';
    };
@endphp
<div class="section">
    <div class="eyebrow">Präferenzen</div>
    <h2 class="serif">Wichtige Aspekte Ihrer Vorsorge</h2>
    <table class="aspects">
        @foreach($aspectLabels as $key => $label)
            @if(isset($s4['aspectRatings'][$key]))
            <tr>
                <td>{{ $label }}</td>
                <td><span class="rate {{ $rateClass($s4['aspectRatings'][$key]) }}">{{ $s4['aspectRatings'][$key] }}</span></td>
            </tr>
            @endif
        @endforeach
    </table>
</div>
@endif

{{-- ============ CONCLUSION ============ --}}
<div class="section">
    <div class="eyebrow">Empfehlung</div>
    <h2 class="serif">Fazit &amp; Abschluss</h2>
    @if(!empty($s5['finalNotes']))
        <div class="notes">{{ $s5['finalNotes'] }}</div>
    @endif
    <div class="signs">
        <div class="sign"><div class="line">Ort, Datum{{ !empty($s5['location']) ? ' — '.$s5['location'] : '' }}{{ !empty($s5['date']) ? ', '.$s5['date'] : '' }}</div></div>
        <div class="sign"><div class="line">Unterschrift Mandant</div></div>
        <div class="sign"><div class="line">Unterschrift Berater{{ $advisor ? ' — '.$advisor->name : '' }}</div></div>
    </div>
</div>

<div class="disclaimer">
    <strong>Hinweis:</strong> Diese Analyse basiert auf Ihren Angaben und den zum Erstellungszeitpunkt hinterlegten
    Referenzwerten (Steuer- und Sozialversicherungsparameter). Alle Berechnungen sind unverbindliche Modellrechnungen
    und ersetzen keine Steuer- oder Rechtsberatung. Künftige Entwicklungen von Inflation, Rentenanpassungen, Steuerrecht
    und Sozialabgaben können von den Annahmen abweichen.
</div>

<div class="foot">
    <div><b>RENTENBLICK.de</b> · Professionelle Rentenberatung</div>
    <div>Erstellt am {{ now()->format('d.m.Y') }} · Rentencheck #{{ $rentencheck->id }}</div>
</div>

</body>
</html>
