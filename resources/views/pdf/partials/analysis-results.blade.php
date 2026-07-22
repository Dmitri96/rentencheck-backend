{{-- Analysis results (renders backend $analysis — no calculation in the template). --}}
@php
    $eur = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $rows = $analysis['rows'] ?? [];
    $totals = $analysis['totals'] ?? [];
    $gap = $analysis['gap'] ?? [];
    $capital = $analysis['capital'] ?? [];
    $disability = $analysis['disability'] ?? [];
    $pkv = $analysis['private_health_insurance'] ?? [];
    $desired = $analysis['desired_pension'] ?? [];
    $params = $analysis['parameters_used']['economic_assumptions'] ?? [];
    $kvdr = $analysis['parameters_used']['social_insurance']['total_insurance_rate'] ?? 12.15;
    $hasGap = ($gap['has_gap'] ?? (($gap['monthly_today'] ?? 0) > 0));
    $emrShortfall = max(0, ($disability['net_income'] ?? 0) - ($disability['emr_full_net'] ?? 0));
@endphp

{{-- ---- Reference parameters ---- --}}
<div class="section">
    <div class="eyebrow">Berechnungsgrundlage</div>
    <h2 class="serif">Ihre Nettoversorgung im Detail</h2>
    <div class="chips">
        <div class="chip"><div class="k">Inflation</div><div class="v tnum">{{ number_format((float) ($analysis['inflationRate'] ?? 2), 1, ',', '.') }} %</div></div>
        <div class="chip"><div class="k">Rentensteigerung</div><div class="v tnum">{{ number_format((float) ($params['pension_increase_rate'] ?? 1), 1, ',', '.') }} %</div></div>
        <div class="chip"><div class="k">KVdR gesamt</div><div class="v tnum">{{ number_format((float) $kvdr, 2, ',', '.') }} %</div></div>
        <div class="chip"><div class="k">Kapitalrendite</div><div class="v tnum">{{ number_format((float) ($params['investment_return_rate'] ?? 3), 1, ',', '.') }} %</div></div>
    </div>

    <table class="data" style="margin-top:6px;">
        <thead>
            <tr>
                <th>Einkommensquelle</th>
                <th>Brutto</th>
                <th>Steuer</th>
                <th>Kirche</th>
                <th>Soli</th>
                <th>KV/PV</th>
                <th>Netto</th>
                <th>Kaufkraft heute</th>
            </tr>
        </thead>
        <tbody>
            <tr class="wish">
                <td>Ihr Rentenwunsch</td>
                <td class="tnum">{{ $eur($desired['at_retirement'] ?? 0) }}</td>
                <td class="faint">–</td><td class="faint">–</td><td class="faint">–</td><td class="faint">–</td><td class="faint">–</td>
                <td class="tnum">{{ $eur($desired['today'] ?? 0) }}</td>
            </tr>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="tnum">{{ $eur($row['gross_at_retirement']) }}</td>
                <td class="tnum">{{ $eur($row['income_tax']) }}</td>
                <td class="tnum">{{ $eur($row['church_tax']) }}</td>
                <td class="tnum">{{ $eur($row['solidarity_surcharge']) }}</td>
                <td class="tnum">{{ $eur($row['health_care_insurance']) }}</td>
                <td class="tnum">{{ $eur($row['after_insurance']) }}</td>
                <td class="tnum pos">{{ $eur($row['purchasing_power']) }}</td>
            </tr>
            @endforeach
            @if(($pkv['monthly_at_retirement'] ?? 0) > 0)
            <tr>
                <td>Private Krankenversicherung</td>
                <td class="tnum">−{{ $eur($pkv['monthly_at_retirement']) }}</td>
                <td class="faint">–</td><td class="faint">–</td><td class="faint">–</td><td class="faint">–</td><td class="faint">–</td>
                <td class="tnum">−{{ $eur($pkv['purchasing_power']) }}</td>
            </tr>
            @endif
            <tr class="total">
                <td>Gesamt</td>
                <td class="tnum">{{ $eur($totals['gross_at_retirement'] ?? 0) }}</td>
                <td class="tnum">{{ $eur($totals['income_tax'] ?? 0) }}</td>
                <td class="tnum">{{ $eur($totals['church_tax'] ?? 0) }}</td>
                <td class="tnum">{{ $eur($totals['solidarity_surcharge'] ?? 0) }}</td>
                <td class="tnum">{{ $eur($totals['health_care_insurance'] ?? 0) }}</td>
                <td class="tnum">{{ $eur($totals['after_insurance'] ?? 0) }}</td>
                <td class="tnum">{{ $eur(($totals['purchasing_power'] ?? 0) - ($pkv['purchasing_power'] ?? 0)) }}</td>
            </tr>
        </tbody>
    </table>

    @php
        $grpColor = ['statutory' => '#3e7a55', 'occupational' => '#2f7fae', 'private' => '#6f5bd0', 'other' => '#7b8494'];
        $grpLabel = ['statutory' => 'Gesetzliche Versorgung', 'occupational' => 'bAV & Zusatzversorgung', 'private' => 'Private Vorsorge', 'other' => 'Weitere Einkünfte'];
        $byGroup = ['statutory' => 0, 'occupational' => 0, 'private' => 0, 'other' => 0];
        foreach ($rows as $r) { $g = $r['group'] ?? 'other'; $byGroup[$g] += (float) $r['after_insurance']; }
        $netSum = array_sum($byGroup) ?: 1;
    @endphp
    <div class="chart">
        <div class="chart-cap">Woraus sich Ihre monatliche Nettoversorgung zusammensetzt</div>
        <div class="composition">
            @foreach($byGroup as $g => $amt)
                @if($amt > 0)<div class="seg" style="width: {{ round($amt / $netSum * 100, 3) }}%; background: {{ $grpColor[$g] }};"></div>@endif
            @endforeach
        </div>
        <div class="legend">
            @foreach($byGroup as $g => $amt)
                @if($amt > 0)<div class="lg"><span class="sw" style="background: {{ $grpColor[$g] }};"></span>{{ $grpLabel[$g] }} · {{ $eur($amt) }}</div>@endif
            @endforeach
        </div>
    </div>
</div>

{{-- ---- Versorgungslücke + Kapitalbedarf ---- --}}
<div class="section">
    <div class="eyebrow">Ergebnis</div>
    <h2 class="serif">Ihre Versorgungslücke</h2>

    <div class="callout {{ $hasGap ? 'warn' : 'ok' }}">
        @if($hasGap)
            <div class="big tnum">{{ $eur($gap['monthly_today'] ?? 0) }} pro Monat fehlen Ihnen</div>
            <div class="muted" style="margin-top:4px;">
                in heutiger Kaufkraft.
                @if(($gap['first_gap_age'] ?? null) && ($gap['first_gap_age'] > ($analysis['retirementAge'] ?? 67)))
                    Ihre Versorgung reicht zunächst; ab Alter {{ $gap['first_gap_age'] }} entsteht eine Lücke, die bis Alter {{ $analysis['provisionEndAge'] ?? '' }} auf {{ $eur($gap['monthly_at_end'] ?? 0) }} wächst.
                @else
                    Zum Rentenbeginn beträgt die Lücke {{ $eur($gap['monthly_at_retirement'] ?? 0) }} und wächst bis Alter {{ $analysis['provisionEndAge'] ?? '' }} auf {{ $eur($gap['monthly_at_end'] ?? 0) }}.
                @endif
            </div>
        @else
            <div class="big">Keine Versorgungslücke</div>
            <div class="muted" style="margin-top:4px;">Ihre voraussichtlichen Einkünfte decken Ihren Rentenwunsch über den gesamten Ruhestand.</div>
        @endif
    </div>

    @php
        $proj = $analysis['retirement_projection'] ?? [];
        $n = count($proj);
    @endphp
    @if($n > 1)
    @php
        $W = 720; $H = 208; $pL = 4; $pR = 4; $pT = 8; $pB = 18;
        $incomes = array_map(fn ($p) => (float) $p['net_income'], $proj);
        $needs = array_map(fn ($p) => (float) $p['need'], $proj);
        $maxY = max(1.0, max($needs), max($incomes)) * 1.08;
        $base = $H - $pB;
        $xi = fn ($i) => round($pL + ($i / ($n - 1)) * ($W - $pL - $pR), 1);
        $yv = fn ($v) => round($pT + (1 - $v / $maxY) * ($H - $pT - $pB), 1);
        $incLine = ''; $needLine = ''; $gapTop = ''; $gapBot = '';
        foreach ($proj as $i => $p) {
            $incLine .= $xi($i).','.$yv($incomes[$i]).' ';
            $needLine .= $xi($i).','.$yv($needs[$i]).' ';
            $gapTop .= $xi($i).','.$yv($needs[$i]).' ';
        }
        for ($i = $n - 1; $i >= 0; $i--) { $gapBot .= $xi($i).','.$yv(min($incomes[$i], $needs[$i])).' '; }
        $incArea = $incLine.$xi($n - 1).','.$base.' '.$xi(0).','.$base;
    @endphp
    <div class="chart">
        <div class="chart-cap">Versorgungsverlauf im Ruhestand — Nettoeinkommen gegen inflationierten Rentenwunsch (Alter {{ $proj[0]['age'] }}–{{ $proj[$n - 1]['age'] }})</div>
        <svg viewBox="0 0 {{ $W }} {{ $H }}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="{{ $W }}" height="{{ $base }}" fill="#fbfaf6"/>
            <polygon points="{{ $incArea }}" fill="#3e7a55" fill-opacity="0.85"/>
            <polygon points="{{ $gapTop }}{{ $gapBot }}" fill="#c15540" fill-opacity="0.30"/>
            <polyline points="{{ $incLine }}" fill="none" stroke="#2f6b48" stroke-width="1.5"/>
            <polyline points="{{ $needLine }}" fill="none" stroke="#24314e" stroke-width="2" stroke-dasharray="6,4"/>
            <line x1="{{ $pL }}" y1="{{ $base }}" x2="{{ $W - $pR }}" y2="{{ $base }}" stroke="#d6d0c2" stroke-width="1"/>
        </svg>
        <div class="legend">
            <div class="lg"><span class="sw" style="background:#3e7a55;"></span>Ihr Nettoeinkommen</div>
            <div class="lg"><span class="sw" style="background:#e2b6ab;"></span>Versorgungslücke</div>
            <div class="lg"><span class="sw" style="background:#24314e;"></span>Rentenwunsch (inflationiert)</div>
        </div>
    </div>
    @endif

    @if($hasGap)
    <table class="ledger" style="margin-top:12px;">
        <tr class="head"><td>Monatliche Lücke zum Rentenbeginn (Alter {{ $analysis['retirementAge'] ?? '' }})</td><td class="tnum">{{ $eur($gap['monthly_at_retirement'] ?? 0) }}</td></tr>
        <tr class="head"><td>Monatliche Lücke am Ende (Alter {{ $analysis['provisionEndAge'] ?? '' }})</td><td class="tnum">{{ $eur($gap['monthly_at_end'] ?? 0) }}</td></tr>
        <tr class="head"><td>Gesamte Rentenzahlungen über {{ $capital['years'] ?? 0 }} Jahre Ruhestand</td><td class="tnum">{{ $eur($capital['total_payments'] ?? 0) }}</td></tr>
        <tr><td><strong>Notwendiges Kapital zum Rentenbeginn</strong> (bei {{ number_format((float) ($params['investment_return_rate'] ?? 3), 1, ',', '.') }} % Rendite)</td><td class="tnum val-red">{{ $eur($capital['required_capital'] ?? 0) }}</td></tr>
    </table>
    @endif
</div>

{{-- ---- Disability ---- --}}
<div class="section">
    <div class="eyebrow">Risikoabsicherung</div>
    <h2 class="serif">Bei Krankheit oder Erwerbsminderung</h2>
    <div class="lead">Krankengeld: 70 % vom Brutto, höchstens 90 % vom Netto (gedeckelt auf die Beitragsbemessungsgrenze). Die EM-Rente wird wie die gesetzliche Rente versteuert und verbeitragt.</div>
    @php
        $dBars = [
            ['Nettogehalt heute (Woche 1–6)', $disability['net_income'] ?? 0, '#24314e'],
            ['Krankengeld (Woche 7–78)', $disability['sick_pay'] ?? 0, '#c68a34'],
            ['Volle EM-Rente (netto)', $disability['emr_full_net'] ?? 0, '#c15540'],
            ['Halbe EM-Rente (netto)', $disability['emr_half_net'] ?? 0, '#d79684'],
            ['EM-Rente + private BU', $disability['emr_with_private_insurance'] ?? 0, '#3e7a55'],
        ];
        $dMax = max(1, max(array_map(fn ($r) => (float) $r[1], $dBars)));
    @endphp
    <table style="width:100%; border-collapse:collapse; font-size:8.5px;">
        @foreach($dBars as $r)
        <tr>
            <td style="width:32%; padding:5px 10px 5px 0; color:var(--ink-2); vertical-align:middle;">{{ $r[0] }}</td>
            <td style="padding:5px 0; vertical-align:middle;">
                <div style="background:#f1eee6; border-radius:3px; height:15px; width:100%;">
                    <div style="background:{{ $r[2] }}; height:15px; width:{{ max(1, round((float) $r[1] / $dMax * 100, 1)) }}%; border-radius:3px;"></div>
                </div>
            </td>
            <td style="width:16%; text-align:right; padding:5px 0 5px 10px; font-weight:600; vertical-align:middle;" class="tnum">{{ $eur($r[1]) }}</td>
        </tr>
        @endforeach
    </table>
    @if($emrShortfall > 0)
    <div class="muted" style="margin-top:8px; font-size:8.5px;">
        Nach 78 Wochen endet das Krankengeld. Ohne private Absicherung fehlen bei voller Erwerbsminderung monatlich
        <strong class="val-red">{{ $eur($emrShortfall) }}</strong> gegenüber dem heutigen Netto.
    </div>
    @endif
</div>
