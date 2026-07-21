{{-- Resultate-Seite (MVP Bild 0 + Bild 3 + Situation BR/KU): rendert die
     Backend-Analyse ($analysis) — keine eigene Berechnung im Template. --}}
@php
    $fmt = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $rows = $analysis['rows'] ?? [];
    $totals = $analysis['totals'] ?? [];
    $gap = $analysis['gap'] ?? [];
    $capital = $analysis['capital'] ?? [];
    $disability = $analysis['disability'] ?? [];
    $pkv = $analysis['private_health_insurance'] ?? [];
    $desired = $analysis['desired_pension'] ?? [];
@endphp

<div class="section" style="page-break-before: always;">
    <div class="section-title">Resultate der Rentenanalyse</div>
    <div class="section-content">
        @if($advisor)
        <div style="margin-bottom: 10px; font-size: 8px; color: #4b5563;">
            Erstellt durch: <strong>{{ $advisor->name }}</strong>@if($advisor->email) &middot; {{ $advisor->email }}@endif
        </div>
        @endif

        <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
            <thead>
                <tr style="background: #eef2ff;">
                    <th style="text-align: left; padding: 4px; border-bottom: 1px solid #cbd5e1;">Einkommensquelle</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Brutto (Rentenbeginn)</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Steuer</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Kirche</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Soli</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">nach Steuer</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">KV/PV</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">nach KV</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Kaufkraft (heute)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 4px; font-weight: bold;">Ihr Rentenwunsch</td>
                    <td style="text-align: right; padding: 4px; font-weight: bold;">{{ $fmt($desired['at_retirement'] ?? 0) }}</td>
                    <td colspan="6"></td>
                    <td style="text-align: right; padding: 4px; font-weight: bold;">{{ $fmt($desired['today'] ?? 0) }}</td>
                </tr>
                @foreach($rows as $row)
                <tr style="{{ $loop->even ? 'background: #f8fafc;' : '' }}">
                    <td style="padding: 4px;">{{ $row['label'] }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['gross_at_retirement']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['income_tax']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['church_tax']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['solidarity_surcharge']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['after_tax']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['health_care_insurance']) }}</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($row['after_insurance']) }}</td>
                    <td style="text-align: right; padding: 4px; font-weight: bold;">{{ $fmt($row['purchasing_power']) }}</td>
                </tr>
                @endforeach
                @if(($pkv['monthly_at_retirement'] ?? 0) > 0)
                <tr>
                    <td style="padding: 4px;">Private Krankenversicherung (Beitrag)</td>
                    <td style="text-align: right; padding: 4px;">−{{ $fmt($pkv['monthly_at_retirement']) }}</td>
                    <td colspan="6"></td>
                    <td style="text-align: right; padding: 4px;">−{{ $fmt($pkv['purchasing_power']) }}</td>
                </tr>
                @endif
                <tr style="background: #e2e8f0; font-weight: bold;">
                    <td style="padding: 4px; border-top: 1px solid #64748b;">Gesamt</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['gross_at_retirement'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['income_tax'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['church_tax'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['solidarity_surcharge'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['after_tax'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['health_care_insurance'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt($totals['after_insurance'] ?? 0) }}</td>
                    <td style="text-align: right; padding: 4px; border-top: 1px solid #64748b;">{{ $fmt(($totals['purchasing_power'] ?? 0) - ($pkv['purchasing_power'] ?? 0)) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 12px; border-top: 2px solid #1f2937; padding-top: 8px; font-size: 9px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 3px;">Versorgungslücke (Altersrente, heutige Kaufkraft)</td>
                    <td style="text-align: right; padding: 3px; font-weight: bold; color: #b91c1c;">{{ $fmt($gap['monthly_today'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px;">Bei {{ number_format((float) ($analysis['inflationRate'] ?? 2), 1, ',', '.') }} % Inflation erhöht sich Ihre Lücke zum Rentenbeginn auf</td>
                    <td style="text-align: right; padding: 3px;">{{ $fmt($gap['monthly_at_retirement'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px;">Dies entspricht einem zusätzlichen Kapitalbedarf pro Jahr von</td>
                    <td style="text-align: right; padding: 3px;">{{ $fmt($gap['annual_at_retirement'] ?? 0) }}</td>
                </tr>
            </table>
        </div>

        @if(($gap['monthly_today'] ?? 0) > 0)
        <div class="subsection" style="margin-top: 12px;">
            <div class="subsection-title">Kapitalbedarf zur Schließung der Lücke</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                <tr>
                    <td style="padding: 3px;">Gesamte Kapitalzahlungen über eine Rentenzeit von {{ $capital['years'] ?? 0 }} Jahren (bis Alter {{ $analysis['provisionEndAge'] ?? '' }})</td>
                    <td style="text-align: right; padding: 3px; font-weight: bold;">{{ $fmt($capital['total_payments'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px;">Um diesen Betrag ab Alter {{ $analysis['retirementAge'] ?? '' }} zahlen zu können, benötigen Sie ein Gesamtkapital von</td>
                    <td style="text-align: right; padding: 3px; font-weight: bold;">{{ $fmt($capital['required_capital'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px;">Am Ende verbleibt ein Restkapital von</td>
                    <td style="text-align: right; padding: 3px;">{{ $fmt($capital['remaining_capital'] ?? 0) }}</td>
                </tr>
            </table>
        </div>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">Situation längere Krankheit / Berufs- oder Erwerbsunfähigkeit</div>
    <div class="section-content">
        <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
            <thead>
                <tr style="background: #eef2ff;">
                    <th style="text-align: left; padding: 4px; border-bottom: 1px solid #cbd5e1;">Phase</th>
                    <th style="text-align: right; padding: 4px; border-bottom: 1px solid #cbd5e1;">Netto verfügbar (mtl.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 4px;">Nettogehalt heute (Lohnfortzahlung in den ersten 6 Wochen)</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($disability['net_income'] ?? 0) }}</td>
                </tr>
                <tr style="background: #f8fafc;">
                    <td style="padding: 4px;">Krankengeld Woche 7–78 (70 % vom Brutto, max. 90 % vom Netto)</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($disability['sick_pay'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px;">Volle Erwerbsminderungsrente (netto, frühestens nach 6 Monaten)</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($disability['emr_full_net'] ?? 0) }}</td>
                </tr>
                <tr style="background: #f8fafc;">
                    <td style="padding: 4px;">Halbe Erwerbsminderungsrente (netto)</td>
                    <td style="text-align: right; padding: 4px;">{{ $fmt($disability['emr_half_net'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px; font-weight: bold;">EM-Rente + private BU-Rente</td>
                    <td style="text-align: right; padding: 4px; font-weight: bold;">{{ $fmt($disability['emr_with_private_insurance'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
        <div style="margin-top: 6px; font-size: 7px; color: #6b7280;">
            Nach 78 Wochen endet das Krankengeld. Ohne private Absicherung fehlen bei voller
            Erwerbsminderung monatlich {{ $fmt(max(0, ($disability['net_income'] ?? 0) - ($disability['emr_full_net'] ?? 0))) }}
            gegenüber dem heutigen Netto.
        </div>
    </div>
</div>

<div class="section">
    <div class="section-content" style="font-size: 7px; color: #6b7280;">
        <strong>Disclaimer:</strong> Diese Analyse wurde auf Basis der von Ihnen gemachten Angaben und der zum
        Erstellungszeitpunkt hinterlegten Referenzwerte (Steuer- und Sozialversicherungsparameter) erstellt.
        Alle Berechnungen sind unverbindliche Modellrechnungen und ersetzen keine Steuer- oder Rechtsberatung.
        Künftige Entwicklungen von Inflation, Rentenanpassungen, Steuerrecht und Sozialabgaben können von den
        getroffenen Annahmen abweichen. Für die Vollständigkeit und Richtigkeit der zugrunde liegenden Angaben
        wird keine Gewähr übernommen.
    </div>
</div>
