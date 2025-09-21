<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRentencheckStepRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $step = (int) $this->route('step');
        if ($step === 1) {
            // Default Kirchensteuer toggle to false if not sent
            $this->merge([
                'hasToChurchTax' => $this->has('hasToChurchTax') ? $this->boolean('hasToChurchTax') : false,
            ]);
        }
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $step = (int) $this->route('step');

        return match ($step) {
            1 => $this->getStep1Rules(),
            2 => $this->getStep2Rules(),
            3 => $this->getStep3Rules(),
            4 => $this->getStep4Rules(),
            5 => $this->getStep5Rules(),
            default => [],
        };
    }

    /**
     * Step 1: Personal and Financial Information rules
     */
    private function getStep1Rules(): array
    {
        return [
            'profession' => 'required|string|max:255',
            'currentGrossIncome' => 'required|numeric|min:0',
            'currentNetIncome' => 'required|numeric|min:0',
            'maritalStatus' => 'required|string',
            'assetSeparation' => 'required|string',
            'healthInsurance' => 'required|string',
            'healthInsuranceContribution' => 'required|numeric|min:0',
            'hasToChurchTax' => 'nullable|boolean',
        ];
    }

    /**
     * Step 2: Expectations rules
     */
    private function getStep2Rules(): array
    {
        return [
            'currentAge' => 'required|integer|min:18|max:100',
            'retirementAge' => 'required|integer|min:50|max:100',
            'pensionWishCurrentValue' => 'required|numeric|min:0',
            'guaranteedAmount' => 'required|numeric|min:0',
            'provisionDuration' => 'required|integer|min:0',
            'assumedInflation' => 'required|numeric|min:0|max:10',
        ];
    }

    /**
     * Step 3: Contract Overview rules - Updated for comprehensive contract structure
     */
    private function getStep3Rules(): array
    {
        return [
            // Pension type checkboxes
            'statutoryPensionClaims' => 'required|boolean',
            'statutoryPensionAge' => 'nullable|integer|min:0|max:100',
            'statutoryPensionAmount' => 'nullable|numeric|min:0',
            // New: Erwerbsminderungsrente (monatl. Betrag ohne Alter)
            'disabilityPensionAmount' => 'nullable|numeric|min:0',
            
            'professionalProvisionWorks' => 'required|boolean',
            'professionalProvisionAge' => 'nullable|integer|min:0|max:100',
            'professionalProvisionAmount' => 'nullable|numeric|min:0',
            
            'publicServiceAdditionalProvision' => 'required|boolean',
            'publicServiceProvisionAge' => 'nullable|integer|min:0|max:100',
            'publicServiceProvisionAmount' => 'nullable|numeric|min:0',
            
            'civilServiceProvision' => 'required|boolean',
            'civilServiceProvisionAge' => 'nullable|integer|min:0|max:100',
            'civilServiceProvisionAmount' => 'nullable|numeric|min:0',
            
            // Payout contracts with comprehensive fields
            'payoutContracts' => 'array',
            'payoutContracts.*.contract' => 'required_with:payoutContracts.*|string|max:255',
            'payoutContracts.*.company' => 'required_with:payoutContracts.*|string|max:255',
            'payoutContracts.*.contractType' => 'required_with:payoutContracts.*|string|in:Kapital-Lebensvers.,Rentenvers.,Direktvers.,Investment,andere Art',
            'payoutContracts.*.interestRate' => 'required_with:payoutContracts.*|numeric|min:0|max:20',
            'payoutContracts.*.maturityYear' => 'required_with:payoutContracts.*|integer|min:2024|max:2100',
            'payoutContracts.*.guaranteedAmount' => 'required_with:payoutContracts.*|numeric|min:0',
            'payoutContracts.*.projectedAmount' => 'nullable|numeric|min:0',
            
            // Pension contracts with comprehensive fields
            'pensionContracts' => 'array',
            'pensionContracts.*.contract' => 'required_with:pensionContracts.*|string|max:255',
            'pensionContracts.*.company' => 'required_with:pensionContracts.*|string|max:255',
            'pensionContracts.*.contractType' => 'required_with:pensionContracts.*|string|in:Basis-Rente,Riester-Rente,BAV-Rente,Mieteinnahme,andere Art',
            'pensionContracts.*.interestRate' => 'required_with:pensionContracts.*|numeric|min:0|max:20',
            'pensionContracts.*.pensionStartYear' => 'required_with:pensionContracts.*|integer|min:2024|max:2100',
            'pensionContracts.*.guaranteedAmount' => 'nullable|numeric|min:0',
            'pensionContracts.*.projectedAmount' => 'nullable|numeric|min:0',
            'pensionContracts.*.monthlyAmount' => 'required_with:pensionContracts.*|numeric|min:0',
            
            // Additional income with comprehensive fields
            'additionalIncome' => 'array',
            'additionalIncome.*.type' => 'required_with:additionalIncome.*|string|max:255',
            'additionalIncome.*.startYear' => 'required_with:additionalIncome.*|integer|min:2024|max:2100',
            'additionalIncome.*.amount' => 'required_with:additionalIncome.*|numeric|min:0',
            'additionalIncome.*.frequency' => 'required_with:additionalIncome.*|string|in:Einmalig,Monatlich,Jährlich',
        ];
    }

    /**
     * Step 4: Important Aspects rules
     */
    private function getStep4Rules(): array
    {
        return [
            'aspectRatings' => 'required|array',
            'aspectRatings.availabilityDuringSavings' => 'required|string',
            'aspectRatings.flexibilityInRetirement' => 'required|string',
            'aspectRatings.capitalOrAnnuityChoice' => 'required|string',
            'aspectRatings.childBenefits' => 'required|string',
            'aspectRatings.initialPaymentOption' => 'required|string',
            'aspectRatings.taxSavingsInSavingsPhase' => 'required|string',
            'aspectRatings.lowTaxInPayoutPhase' => 'required|string',
            'aspectRatings.protectionAgainstDisability' => 'required|string',
            'aspectRatings.survivorBenefits' => 'required|string',
            'aspectRatings.deathBenefitsOutsideFamily' => 'required|string',
            'aspectRatings.protectionAgainstThirdParties' => 'required|string',
            'productDependsOnEmployer' => 'required|string',
            'taxLimitationsInSavingsPhase' => 'required|string',
            'onlyForRetirement' => 'required|string',
        ];
    }

    /**
     * Step 5: Conclusion rules
     */
    private function getStep5Rules(): array
    {
        return [
            'finalNotes' => 'nullable|string',
            'date' => 'required|string',
            'location' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Step 1 messages
            'profession.required' => 'Der Beruf ist erforderlich.',
            'profession.max' => 'Der Beruf darf maximal 255 Zeichen lang sein.',
            'currentGrossIncome.required' => 'Das aktuelle Bruttoeinkommen ist erforderlich.',
            'currentGrossIncome.numeric' => 'Das aktuelle Bruttoeinkommen muss eine Zahl sein.',
            'currentGrossIncome.min' => 'Das aktuelle Bruttoeinkommen muss mindestens 0 sein.',
            'currentNetIncome.required' => 'Das aktuelle Nettoeinkommen ist erforderlich.',
            'currentNetIncome.numeric' => 'Das aktuelle Nettoeinkommen muss eine Zahl sein.',
            'currentNetIncome.min' => 'Das aktuelle Nettoeinkommen muss mindestens 0 sein.',
            'maritalStatus.required' => 'Der Familienstand ist erforderlich.',
            'assetSeparation.required' => 'Die Gütertrennung ist erforderlich.',
            'healthInsurance.required' => 'Die Krankenversicherung ist erforderlich.',
            'healthInsuranceContribution.required' => 'Der Krankenversicherungsbeitrag ist erforderlich.',
            'healthInsuranceContribution.numeric' => 'Der Krankenversicherungsbeitrag muss eine Zahl sein.',
            'healthInsuranceContribution.min' => 'Der Krankenversicherungsbeitrag muss mindestens 0 sein.',

            // Step 2 messages
            'currentAge.required' => 'Das aktuelle Alter ist erforderlich.',
            'currentAge.integer' => 'Das aktuelle Alter muss eine ganze Zahl sein.',
            'currentAge.min' => 'Das aktuelle Alter muss mindestens 18 Jahre betragen.',
            'currentAge.max' => 'Das aktuelle Alter darf maximal 100 Jahre betragen.',
            'retirementAge.required' => 'Das Rentenalter ist erforderlich.',
            'retirementAge.integer' => 'Das Rentenalter muss eine ganze Zahl sein.',
            'retirementAge.min' => 'Das Rentenalter muss mindestens 50 Jahre betragen.',
            'retirementAge.max' => 'Das Rentenalter darf maximal 100 Jahre betragen.',
            'pensionWishCurrentValue.required' => 'Der Rentenwunsch in heutiger Kaufkraft ist erforderlich.',
            'pensionWishCurrentValue.numeric' => 'Der Rentenwunsch muss eine Zahl sein.',
            'pensionWishCurrentValue.min' => 'Der Rentenwunsch muss mindestens 0 sein.',
            'guaranteedAmount.required' => 'Der garantierte Betrag ist erforderlich.',
            'guaranteedAmount.numeric' => 'Der garantierte Betrag muss eine Zahl sein.',
            'guaranteedAmount.min' => 'Der garantierte Betrag muss mindestens 0 sein.',
            'provisionDuration.required' => 'Die Versorgungsdauer ist erforderlich.',
            'provisionDuration.integer' => 'Die Versorgungsdauer muss eine ganze Zahl sein.',
            'provisionDuration.min' => 'Die Versorgungsdauer muss mindestens 0 Jahre betragen.',
            'assumedInflation.required' => 'Die angenommene Inflation ist erforderlich.',
            'assumedInflation.numeric' => 'Die angenommene Inflation muss eine Zahl sein.',
            'assumedInflation.min' => 'Die angenommene Inflation muss mindestens 0% betragen.',
            'assumedInflation.max' => 'Die angenommene Inflation darf maximal 10% betragen.',

            // Step 3 messages - Updated for comprehensive contract structure
            'statutoryPensionClaims.required' => 'Die Angabe zu gesetzlichen Rentenansprüchen ist erforderlich.',
            'statutoryPensionAge.integer' => 'Das Alter für gesetzliche Rente muss eine ganze Zahl sein.',
            'statutoryPensionAge.min' => 'Das Alter für gesetzliche Rente muss mindestens 0 Jahre betragen.',
            'statutoryPensionAge.max' => 'Das Alter für gesetzliche Rente darf maximal 100 Jahre betragen.',
            'statutoryPensionAmount.numeric' => 'Der Betrag für gesetzliche Rente muss eine Zahl sein.',
            'statutoryPensionAmount.min' => 'Der Betrag für gesetzliche Rente muss mindestens 0 sein.',
            'disabilityPensionAmount.numeric' => 'Der Betrag für Erwerbsminderungsrente muss eine Zahl sein.',
            'disabilityPensionAmount.min' => 'Der Betrag für Erwerbsminderungsrente muss mindestens 0 sein.',
            
            'professionalProvisionWorks.required' => 'Die Angabe zur betrieblichen Altersvorsorge ist erforderlich.',
            'professionalProvisionAge.integer' => 'Das Alter für betriebliche Altersvorsorge muss eine ganze Zahl sein.',
            'professionalProvisionAge.min' => 'Das Alter für betriebliche Altersvorsorge muss mindestens 0 Jahre betragen.',
            'professionalProvisionAge.max' => 'Das Alter für betriebliche Altersvorsorge darf maximal 100 Jahre betragen.',
            'professionalProvisionAmount.numeric' => 'Der Betrag für betriebliche Altersvorsorge muss eine Zahl sein.',
            'professionalProvisionAmount.min' => 'Der Betrag für betriebliche Altersvorsorge muss mindestens 0 sein.',
            
            'publicServiceAdditionalProvision.required' => 'Die Angabe zur öffentlich-rechtlichen Zusatzversorgung ist erforderlich.',
            'publicServiceProvisionAge.integer' => 'Das Alter für öffentlich-rechtliche Zusatzversorgung muss eine ganze Zahl sein.',
            'publicServiceProvisionAge.min' => 'Das Alter für öffentlich-rechtliche Zusatzversorgung muss mindestens 0 Jahre betragen.',
            'publicServiceProvisionAge.max' => 'Das Alter für öffentlich-rechtliche Zusatzversorgung darf maximal 100 Jahre betragen.',
            'publicServiceProvisionAmount.numeric' => 'Der Betrag für öffentlich-rechtliche Zusatzversorgung muss eine Zahl sein.',
            'publicServiceProvisionAmount.min' => 'Der Betrag für öffentlich-rechtliche Zusatzversorgung muss mindestens 0 sein.',
            
            'civilServiceProvision.required' => 'Die Angabe zur Beamtenversorgung ist erforderlich.',
            'civilServiceProvisionAge.integer' => 'Das Alter für Beamtenversorgung muss eine ganze Zahl sein.',
            'civilServiceProvisionAge.min' => 'Das Alter für Beamtenversorgung muss mindestens 0 Jahre betragen.',
            'civilServiceProvisionAge.max' => 'Das Alter für Beamtenversorgung darf maximal 100 Jahre betragen.',
            'civilServiceProvisionAmount.numeric' => 'Der Betrag für Beamtenversorgung muss eine Zahl sein.',
            'civilServiceProvisionAmount.min' => 'Der Betrag für Beamtenversorgung muss mindestens 0 sein.',
            
            // Payout contract messages
            'payoutContracts.*.contract.required_with' => 'Der Vertragsname ist erforderlich.',
            'payoutContracts.*.contract.max' => 'Der Vertragsname darf maximal 255 Zeichen lang sein.',
            'payoutContracts.*.company.required_with' => 'Die Gesellschaft ist erforderlich.',
            'payoutContracts.*.company.max' => 'Die Gesellschaft darf maximal 255 Zeichen lang sein.',
            'payoutContracts.*.contractType.required_with' => 'Die Vertragsart ist erforderlich.',
            'payoutContracts.*.contractType.in' => 'Die Vertragsart muss eine gültige Option sein.',
            'payoutContracts.*.interestRate.required_with' => 'Der Zinssatz ist erforderlich.',
            'payoutContracts.*.interestRate.numeric' => 'Der Zinssatz muss eine Zahl sein.',
            'payoutContracts.*.interestRate.min' => 'Der Zinssatz muss mindestens 0% betragen.',
            'payoutContracts.*.interestRate.max' => 'Der Zinssatz darf maximal 20% betragen.',
            'payoutContracts.*.maturityYear.required_with' => 'Das Ablaufjahr ist erforderlich.',
            'payoutContracts.*.maturityYear.integer' => 'Das Ablaufjahr muss eine ganze Zahl sein.',
            'payoutContracts.*.maturityYear.min' => 'Das Ablaufjahr muss mindestens 2024 sein.',
            'payoutContracts.*.maturityYear.max' => 'Das Ablaufjahr darf maximal 2100 sein.',
            'payoutContracts.*.guaranteedAmount.required_with' => 'Der garantierte Betrag ist erforderlich.',
            'payoutContracts.*.guaranteedAmount.numeric' => 'Der garantierte Betrag muss eine Zahl sein.',
            'payoutContracts.*.guaranteedAmount.min' => 'Der garantierte Betrag muss mindestens 0 sein.',
            'payoutContracts.*.projectedAmount.numeric' => 'Der prognostizierte Betrag muss eine Zahl sein.',
            'payoutContracts.*.projectedAmount.min' => 'Der prognostizierte Betrag muss mindestens 0 sein.',
            
            // Pension contract messages
            'pensionContracts.*.contract.required_with' => 'Der Vertragsname ist erforderlich.',
            'pensionContracts.*.contract.max' => 'Der Vertragsname darf maximal 255 Zeichen lang sein.',
            'pensionContracts.*.company.required_with' => 'Die Gesellschaft ist erforderlich.',
            'pensionContracts.*.company.max' => 'Die Gesellschaft darf maximal 255 Zeichen lang sein.',
            'pensionContracts.*.contractType.required_with' => 'Die Vertragsart ist erforderlich.',
            'pensionContracts.*.contractType.in' => 'Die Vertragsart muss eine gültige Option sein.',
            'pensionContracts.*.interestRate.required_with' => 'Der Zinssatz ist erforderlich.',
            'pensionContracts.*.interestRate.numeric' => 'Der Zinssatz muss eine Zahl sein.',
            'pensionContracts.*.interestRate.min' => 'Der Zinssatz muss mindestens 0% betragen.',
            'pensionContracts.*.interestRate.max' => 'Der Zinssatz darf maximal 20% betragen.',
            'pensionContracts.*.pensionStartYear.required_with' => 'Das Rentenbeginn-Jahr ist erforderlich.',
            'pensionContracts.*.pensionStartYear.integer' => 'Das Rentenbeginn-Jahr muss eine ganze Zahl sein.',
            'pensionContracts.*.pensionStartYear.min' => 'Das Rentenbeginn-Jahr muss mindestens 2024 sein.',
            'pensionContracts.*.pensionStartYear.max' => 'Das Rentenbeginn-Jahr darf maximal 2100 sein.',
            'pensionContracts.*.guaranteedAmount.required_with' => 'Der garantierte Betrag ist erforderlich.',
            'pensionContracts.*.guaranteedAmount.numeric' => 'Der garantierte Betrag muss eine Zahl sein.',
            'pensionContracts.*.guaranteedAmount.min' => 'Der garantierte Betrag muss mindestens 0 sein.',
            'pensionContracts.*.projectedAmount.numeric' => 'Der prognostizierte Betrag muss eine Zahl sein.',
            'pensionContracts.*.projectedAmount.min' => 'Der prognostizierte Betrag muss mindestens 0 sein.',
            'pensionContracts.*.monthlyAmount.required_with' => 'Der monatliche Betrag ist erforderlich.',
            'pensionContracts.*.monthlyAmount.numeric' => 'Der monatliche Betrag muss eine Zahl sein.',
            'pensionContracts.*.monthlyAmount.min' => 'Der monatliche Betrag muss mindestens 0 sein.',
            
            // Additional income messages
            'additionalIncome.*.type.required_with' => 'Der Einkommenstyp ist erforderlich.',
            'additionalIncome.*.type.max' => 'Der Einkommenstyp darf maximal 255 Zeichen lang sein.',
            'additionalIncome.*.startYear.required_with' => 'Das Startjahr ist erforderlich.',
            'additionalIncome.*.startYear.integer' => 'Das Startjahr muss eine ganze Zahl sein.',
            'additionalIncome.*.startYear.min' => 'Das Startjahr muss mindestens 2024 sein.',
            'additionalIncome.*.startYear.max' => 'Das Startjahr darf maximal 2100 sein.',
            'additionalIncome.*.amount.required_with' => 'Der Betrag ist erforderlich.',
            'additionalIncome.*.amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'additionalIncome.*.amount.min' => 'Der Betrag muss mindestens 0 sein.',
            'additionalIncome.*.frequency.required_with' => 'Die Häufigkeit ist erforderlich.',
            'additionalIncome.*.frequency.in' => 'Die Häufigkeit muss eine gültige Option sein.',

            // Step 4 messages
            'aspectRatings.required' => 'Die Bewertungen der wichtigen Aspekte sind erforderlich.',
            'aspectRatings.availabilityDuringSavings.required' => 'Die Bewertung für Verfügbarkeit während der Ansparphase ist erforderlich.',
            'aspectRatings.flexibilityInRetirement.required' => 'Die Bewertung für Flexibilität in der Rentenphase ist erforderlich.',
            'aspectRatings.capitalOrAnnuityChoice.required' => 'Die Bewertung für Kapital- oder Rentenwahl ist erforderlich.',
            'aspectRatings.childBenefits.required' => 'Die Bewertung für Kinderzulagen ist erforderlich.',
            'aspectRatings.initialPaymentOption.required' => 'Die Bewertung für Einmalzahlungsmöglichkeit ist erforderlich.',
            'aspectRatings.taxSavingsInSavingsPhase.required' => 'Die Bewertung für Steuerersparnis in der Ansparphase ist erforderlich.',
            'aspectRatings.lowTaxInPayoutPhase.required' => 'Die Bewertung für niedrige Besteuerung in der Auszahlungsphase ist erforderlich.',
            'aspectRatings.protectionAgainstDisability.required' => 'Die Bewertung für Schutz bei Berufsunfähigkeit ist erforderlich.',
            'aspectRatings.survivorBenefits.required' => 'Die Bewertung für Hinterbliebenenschutz ist erforderlich.',
            'aspectRatings.deathBenefitsOutsideFamily.required' => 'Die Bewertung für Todesfallleistungen außerhalb der Familie ist erforderlich.',
            'aspectRatings.protectionAgainstThirdParties.required' => 'Die Bewertung für Schutz vor Dritten ist erforderlich.',
            'productDependsOnEmployer.required' => 'Die Angabe zur Arbeitgeberabhängigkeit des Produkts ist erforderlich.',
            'taxLimitationsInSavingsPhase.required' => 'Die Angabe zu steuerlichen Beschränkungen in der Ansparphase ist erforderlich.',
            'onlyForRetirement.required' => 'Die Angabe zur ausschließlichen Verwendung für die Rente ist erforderlich.',

            // Step 5 messages
            'date.required' => 'Das Datum ist erforderlich.',
            'location.required' => 'Der Ort ist erforderlich.',
            'location.max' => 'Der Ort darf maximal 255 Zeichen lang sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            // Step 1 attributes
            'profession' => 'Beruf',
            'currentGrossIncome' => 'Aktuelles Bruttoeinkommen',
            'currentNetIncome' => 'Aktuelles Nettoeinkommen',
            'maritalStatus' => 'Familienstand',
            'assetSeparation' => 'Gütertrennung',
            'healthInsurance' => 'Krankenversicherung',
            'healthInsuranceContribution' => 'Krankenversicherungsbeitrag',
            'hasToChurchTax' => 'Kirchensteuerpflicht',

            // Step 2 attributes
            'currentAge' => 'Aktuelles Alter',
            'retirementAge' => 'Rentenalter',
            'pensionWishCurrentValue' => 'Rentenwunsch in heutiger Kaufkraft',
            'guaranteedAmount' => 'Garantierter Betrag',
            'provisionDuration' => 'Versorgungsdauer',
            'assumedInflation' => 'Angenommene Inflation',

            // Step 3 attributes
            'statutoryPensionClaims' => 'Gesetzliche Rentenansprüche',
            'disabilityPensionAmount' => 'Erwerbsminderungsrente (monatlich)',
            'professionalProvisionWorks' => 'Betriebliche Altersvorsorge',
            'publicServiceAdditionalProvision' => 'Öffentlich-rechtliche Zusatzversorgung',
            'civilServiceProvision' => 'Beamtenversorgung',

            // Step 4 attributes
            'aspectRatings' => 'Aspektbewertungen',
            'productDependsOnEmployer' => 'Arbeitgeberabhängigkeit des Produkts',
            'taxLimitationsInSavingsPhase' => 'Steuerliche Beschränkungen in der Ansparphase',
            'onlyForRetirement' => 'Ausschließlich für die Rente',

            // Step 5 attributes
            'finalNotes' => 'Abschließende Notizen',
            'date' => 'Datum',
            'location' => 'Ort',
        ];
    }
} 