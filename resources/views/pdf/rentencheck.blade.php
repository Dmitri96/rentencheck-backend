<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentencheck - {{ $client->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #1f2937;
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        
        .logo-icon {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .logo-r {
            font-size: 16px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .subtitle {
            font-size: 10px;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.3px;
            margin-top: 4px;
        }
        
        .client-info {
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        
        .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .client-details {
            color: #64748b;
            font-size: 8px;
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .client-detail-item {
            background: white;
            padding: 2px 5px;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
        }
        
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: white;
            padding: 8px 12px;
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
            margin: 0;
            text-align: center;
        }
        
        .section-content {
            padding: 12px;
        }
        
        .subsection {
            margin-bottom: 10px;
        }
        
        .subsection-title {
            font-size: 9px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6px;
            padding: 3px 0;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .field-row {
            display: grid;
            gap: 8px;
            margin-bottom: 8px;
            align-items: start;
        }
        
        .field-row-2 {
            grid-template-columns: 1fr 1fr;
        }
        
        .field-row-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .field-row-4 {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }
        
        .field-row-mixed {
            grid-template-columns: 2fr 1fr 1fr;
        }
        
        .field {
            background: #f8fafc;
            border-radius: 4px;
            padding: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .field-compact {
            padding: 4px;
        }
        
        .field-label {
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 3px;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        
        .field-value {
            color: #1f2937;
            font-size: 8px;
            font-weight: 500;
            background: white;
            padding: 4px 6px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
            text-align: center;
        }
        
        .field-value-large {
            font-size: 9px;
            padding: 5px 7px;
        }
        
        .boolean-yes {
            color: #059669;
            background: #ecfdf5;
            border-color: #10b981;
            font-weight: bold;
        }
        
        .boolean-no {
            color: #dc2626;
            background: #fef2f2;
            border-color: #ef4444;
            font-weight: bold;
        }
        
        .amount-value {
            color: #1e40af;
            font-weight: bold;
        }
        
        .contracts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            border-radius: 4px;
            overflow: hidden;
            font-size: 7px;
        }
        
        .contracts-table th,
        .contracts-table td {
            padding: 4px 6px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .contracts-table th {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            font-size: 6px;
            letter-spacing: 0.2px;
        }
        
        .contracts-table tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .aspects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 6px;
            margin-top: 6px;
        }
        
        .aspect-item {
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            text-align: center;
        }
        
        .aspect-label {
            font-size: 6px;
            color: #6b7280;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }
        
        .aspect-value {
            font-weight: bold;
            font-size: 7px;
            padding: 2px 4px;
            border-radius: 2px;
        }
        
        .rating-sehr-wichtig { 
            color: white; 
            background: #dc2626;
        }
        .rating-wichtig { 
            color: white; 
            background: #ea580c;
        }
        .rating-mittel { 
            color: white; 
            background: #ca8a04;
        }
        .rating-weniger-wichtig { 
            color: white; 
            background: #65a30d;
        }
        .rating-unwichtig { 
            color: white; 
            background: #6b7280;
        }
        
        .conclusion-section {
            background: linear-gradient(145deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0284c7;
        }
        
        .conclusion-section .section-title {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        }
        
        .notes-area {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px;
            margin: 6px 0;
            min-height: 40px;
            font-style: italic;
            color: #4b5563;
            text-align: left;
            font-size: 7px;
            line-height: 1.3;
        }
        
        .signature-section {
            margin-top: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            text-align: center;
        }
        
        .signature-box {
            padding: 8px;
            border: 2px dashed #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
        }
        
        .signature-line {
            border-top: 1px solid #374151;
            margin: 20px 10px 6px;
            padding-top: 4px;
            font-size: 6px;
            color: #6b7280;
        }
        
        .footer {
            margin-top: 20px;
            padding: 10px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 6px;
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            border: 1px solid #cbd5e1;
        }
        
        .footer-logo {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2px;
        }
        
        .highlight-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 6px;
            margin: 6px 0;
            text-align: center;
            font-size: 7px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 6px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        
        .badge-completed {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #22c55e;
        }
        
        @media print {
            body { 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <div class="logo-icon">
                    <span class="logo-r">R</span>
                </div>
                <div class="logo">RENTENBLICK.de</div>
            </div>
            <div class="subtitle">Professionelle Rentenberatung & Altersvorsorgeplanung</div>
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <div class="client-name">{{ $client->full_name }}</div>
            <div class="client-details">
                <span class="client-detail-item">📧 {{ $client->email }}</span>
                <span class="client-detail-item">📅 {{ $rentencheck->created_at->format('d.m.Y H:i') }}</span>
                <span class="client-detail-item">🆔 Rentencheck #{{ $rentencheck->id }}</span>
            </div>
            <div style="margin-top: 6px;">
                <span class="badge badge-completed">✓ Abgeschlossen</span>
            </div>
        </div>

        <!-- Step 1: Personal and Financial Information -->
        @if($rentencheck->step_1_data)
        <div class="section">
            <div class="section-title">1. Persönliche und finanzielle Angaben</div>
            <div class="section-content">
                <!-- Personal Info Row -->
                <div class="field-row field-row-3">
                    <div class="field field-compact">
                        <div class="field-label">Beruf</div>
                        <div class="field-value">{{ $rentencheck->step_1_data['profession'] ?? 'N/A' }}</div>
                    </div>
                    <div class="field field-compact">
                        <div class="field-label">Familienstand</div>
                        <div class="field-value">{{ $rentencheck->step_1_data['maritalStatus'] ?? 'N/A' }}</div>
                    </div>
                    <div class="field field-compact">
                        <div class="field-label">Gütertrennung</div>
                        <div class="field-value">{{ $rentencheck->step_1_data['assetSeparation'] ?? 'N/A' }}</div>
                    </div>
                </div>
                
                <!-- Income Row -->
                <div class="field-row field-row-3">
                    <div class="field">
                        <div class="field-label">Bruttoeinkommen</div>
                        <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_1_data['currentGrossIncome'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Nettoeinkommen</div>
                        <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_1_data['currentNetIncome'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                    <div class="field">
                        <div class="field-label">KV-Beitrag</div>
                        <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_1_data['healthInsuranceContribution'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                </div>
                
                <!-- Health Insurance Row -->
                <div class="field-row field-row-2">
                    <div class="field">
                        <div class="field-label">Krankenversicherung</div>
                        <div class="field-value">{{ $rentencheck->step_1_data['healthInsurance'] ?? 'N/A' }}</div>
                    </div>
                    <div></div> <!-- Empty cell for spacing -->
                </div>
            </div>
        </div>
        @endif

        <!-- Step 2: Expectations -->
        @if($rentencheck->step_2_data)
        <div class="section">
            <div class="section-title">2. Ihre Erwartungen</div>
            <div class="section-content">
                <!-- Age and Duration Row -->
                <div class="field-row field-row-4">
                    <div class="field field-compact">
                        <div class="field-label">Aktuelles Alter</div>
                        <div class="field-value">{{ $rentencheck->step_2_data['currentAge'] ?? 'N/A' }} Jahre</div>
                    </div>
                    <div class="field field-compact">
                        <div class="field-label">Rentenalter</div>
                        <div class="field-value">{{ $rentencheck->step_2_data['retirementAge'] ?? 'N/A' }} Jahre</div>
                    </div>
                    <div class="field field-compact">
                        <div class="field-label">Versorgungsdauer</div>
                        <div class="field-value">{{ $rentencheck->step_2_data['provisionDuration'] ?? 'N/A' }} Jahre</div>
                    </div>
                    <div class="field field-compact">
                        <div class="field-label">Inflation</div>
                        <div class="field-value">{{ number_format($rentencheck->step_2_data['assumedInflation'] ?? 0, 1, ',', '.') }} %</div>
                    </div>
                </div>
                
                <!-- Financial Expectations Row -->
                <div class="field-row field-row-2">
                    <div class="field">
                        <div class="field-label">Rentenwunsch (heutige Kaufkraft)</div>
                        <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_2_data['pensionWishCurrentValue'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Garantierter Betrag</div>
                        <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_2_data['guaranteedAmount'] ?? 0, 2, ',', '.') }} €</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Step 3: Contract Overview -->
        @if($rentencheck->step_3_data)
        <div class="section">
            <div class="section-title">3. Vertragsübersicht</div>
            <div class="section-content">
                <div class="subsection">
                    <div class="subsection-title">Bestehende Altersvorsorge</div>
                    <!-- Existing Provision Row -->
                    <div class="field-row field-row-4">
                        <div class="field field-compact">
                            <div class="field-label">Gesetzliche Rente</div>
                            <div class="field-value {{ ($rentencheck->step_3_data['statutoryPensionClaims'] ?? false) ? 'boolean-yes' : 'boolean-no' }}">
                                {{ ($rentencheck->step_3_data['statutoryPensionClaims'] ?? false) ? '✓ Ja' : '✗ Nein' }}
                            </div>
                        </div>
                        <div class="field field-compact">
                            <div class="field-label">Betriebliche AV</div>
                            <div class="field-value {{ ($rentencheck->step_3_data['professionalProvisionWorks'] ?? false) ? 'boolean-yes' : 'boolean-no' }}">
                                {{ ($rentencheck->step_3_data['professionalProvisionWorks'] ?? false) ? '✓ Ja' : '✗ Nein' }}
                            </div>
                        </div>
                        <div class="field field-compact">
                            <div class="field-label">Öffentl. Zusatzvers.</div>
                            <div class="field-value {{ ($rentencheck->step_3_data['publicServiceAdditionalProvision'] ?? false) ? 'boolean-yes' : 'boolean-no' }}">
                                {{ ($rentencheck->step_3_data['publicServiceAdditionalProvision'] ?? false) ? '✓ Ja' : '✗ Nein' }}
                            </div>
                        </div>
                        <div class="field field-compact">
                            <div class="field-label">Beamtenversorgung</div>
                            <div class="field-value {{ ($rentencheck->step_3_data['civilServiceProvision'] ?? false) ? 'boolean-yes' : 'boolean-no' }}">
                                {{ ($rentencheck->step_3_data['civilServiceProvision'] ?? false) ? '✓ Ja' : '✗ Nein' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if(($rentencheck->step_3_data['statutoryPensionClaims'] ?? false) && (isset($rentencheck->step_3_data['statutoryPensionAmount']) || isset($rentencheck->step_3_data['statutoryPensionAge']) || isset($rentencheck->step_3_data['disabilityPensionAmount'])))
                <div class="subsection">
                    <div class="subsection-title">📄 Gesetzliche Rente – Details</div>
                    <div class="field-row field-row-3">
                        @if(isset($rentencheck->step_3_data['statutoryPensionAmount']))
                        <div class="field field-compact">
                            <div class="field-label">Altersrente (mtl.)</div>
                            <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_3_data['statutoryPensionAmount'] ?? 0, 2, ',', '.') }} €</div>
                        </div>
                        @endif
                        @if(isset($rentencheck->step_3_data['statutoryPensionAge']))
                        <div class="field field-compact">
                            <div class="field-label">Altersrente ab Alter</div>
                            <div class="field-value">{{ $rentencheck->step_3_data['statutoryPensionAge'] }} Jahre</div>
                        </div>
                        @endif
                        @if(isset($rentencheck->step_3_data['disabilityPensionAmount']))
                        <div class="field field-compact">
                            <div class="field-label">Erwerbsminderungsrente (mtl.)</div>
                            <div class="field-value field-value-large amount-value">{{ number_format($rentencheck->step_3_data['disabilityPensionAmount'] ?? 0, 2, ',', '.') }} €</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Contracts Tables -->
                @if(!empty($rentencheck->step_3_data['payoutContracts']) && count($rentencheck->step_3_data['payoutContracts']) > 0)
                <div class="subsection">
                    <div class="subsection-title">💰 Auszahlungsverträge</div>
                    <table class="contracts-table">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Betrag</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentencheck->step_3_data['payoutContracts'] as $contract)
                            <tr>
                                <td>{{ $contract['type'] ?? 'N/A' }}</td>
                                <td class="amount-value">{{ number_format($contract['amount'] ?? 0, 2, ',', '.') }} €</td>
                                <td>{{ $contract['description'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(!empty($rentencheck->step_3_data['pensionContracts']) && count($rentencheck->step_3_data['pensionContracts']) > 0)
                <div class="subsection">
                    <div class="subsection-title">🏦 Rentenverträge</div>
                    <table class="contracts-table">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Betrag</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentencheck->step_3_data['pensionContracts'] as $contract)
                            <tr>
                                <td>{{ $contract['type'] ?? 'N/A' }}</td>
                                <td class="amount-value">{{ number_format($contract['amount'] ?? 0, 2, ',', '.') }} €</td>
                                <td>{{ $contract['description'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(!empty($rentencheck->step_3_data['additionalIncome']) && count($rentencheck->step_3_data['additionalIncome']) > 0)
                <div class="subsection">
                    <div class="subsection-title">📈 Zusätzliche Einkommen</div>
                    <table class="contracts-table">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Betrag</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentencheck->step_3_data['additionalIncome'] as $income)
                            <tr>
                                <td>{{ $income['type'] ?? 'N/A' }}</td>
                                <td class="amount-value">{{ number_format($income['amount'] ?? 0, 2, ',', '.') }} €</td>
                                <td>{{ $income['description'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Step 4: Important Aspects -->
        @if($rentencheck->step_4_data && isset($rentencheck->step_4_data['aspectRatings']))
        <div class="section">
            <div class="section-title">4. Wichtige Aspekte bei der Altersvorsorge</div>
            <div class="section-content">
                <div class="highlight-box">
                    <strong>Bewertung:</strong> Sehr wichtig • Wichtig • Mittel • Weniger wichtig • Unwichtig
                </div>
                
                <div class="aspects-grid">
                    @foreach($aspectLabels as $key => $label)
                        @if(isset($rentencheck->step_4_data['aspectRatings'][$key]))
                            <div class="aspect-item">
                                <div class="aspect-label">{{ $label }}</div>
                                <div class="aspect-value rating-{{ str_replace([' ', 'ä', 'ö', 'ü'], ['-', 'ae', 'oe', 'ue'], strtolower($rentencheck->step_4_data['aspectRatings'][$key])) }}">
                                    {{ $rentencheck->step_4_data['aspectRatings'][$key] }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if(isset($rentencheck->step_4_data['productDependsOnEmployer']) || isset($rentencheck->step_4_data['taxLimitationsInSavingsPhase']) || isset($rentencheck->step_4_data['onlyForRetirement']))
                <div style="margin-top: 8px;">
                    <div class="field-row field-row-3">
                        @if(isset($rentencheck->step_4_data['productDependsOnEmployer']))
                        <div class="field field-compact">
                            <div class="field-label">Abhängig vom AG</div>
                            <div class="field-value">{{ $rentencheck->step_4_data['productDependsOnEmployer'] ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if(isset($rentencheck->step_4_data['taxLimitationsInSavingsPhase']))
                        <div class="field field-compact">
                            <div class="field-label">Steuerl. Beschränkungen</div>
                            <div class="field-value">{{ $rentencheck->step_4_data['taxLimitationsInSavingsPhase'] ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if(isset($rentencheck->step_4_data['onlyForRetirement']))
                        <div class="field field-compact">
                            <div class="field-label">Nur für AV</div>
                            <div class="field-value">{{ $rentencheck->step_4_data['onlyForRetirement'] ?? 'N/A' }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Step 5: Conclusion -->
        @if($rentencheck->step_5_data)
        <div class="section conclusion-section">
            <div class="section-title">5. Fazit und Abschluss</div>
            <div class="section-content">
                @if(isset($rentencheck->step_5_data['finalNotes']) && !empty($rentencheck->step_5_data['finalNotes']))
                <div style="margin-bottom: 8px;">
                    <div class="field-label" style="margin-bottom: 4px;">Abschließende Notizen</div>
                    <div class="notes-area">{{ $rentencheck->step_5_data['finalNotes'] }}</div>
                </div>
                @endif
                
                <div class="field-row field-row-2">
                    <div class="field">
                        <div class="field-label">Datum</div>
                        <div class="field-value">{{ $rentencheck->step_5_data['date'] ?? 'N/A' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Ort</div>
                        <div class="field-value">{{ $rentencheck->step_5_data['location'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line">Unterschrift Kunde</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line">Unterschrift Berater</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">RENTENBLICK.de</div>
            <div>Ihr Partner für professionelle Rentenberatung und Altersvorsorgeplanung</div>
            <div style="margin-top: 4px; font-size: 6px;">
                Erstellt am {{ now()->format('d.m.Y H:i') }} | Rentencheck ID: #{{ $rentencheck->id }}
            </div>
        </div>
    </div>
</body>
</html> 