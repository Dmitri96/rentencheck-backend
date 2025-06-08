<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRentencheckStepRequest extends FormRequest
{
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
     * Step 3: Contract Overview rules
     */
    private function getStep3Rules(): array
    {
        return [
            'statutoryPensionClaims' => 'required|boolean',
            'professionalProvisionWorks' => 'required|boolean',
            'publicServiceAdditionalProvision' => 'required|boolean',
            'civilServiceProvision' => 'required|boolean',
            'payoutContracts' => 'array',
            'payoutContracts.*.type' => 'required|string',
            'payoutContracts.*.amount' => 'required|numeric|min:0',
            'payoutContracts.*.description' => 'nullable|string',
            'pensionContracts' => 'array',
            'pensionContracts.*.type' => 'required|string',
            'pensionContracts.*.amount' => 'required|numeric|min:0',
            'pensionContracts.*.description' => 'nullable|string',
            'additionalIncome' => 'array',
            'additionalIncome.*.type' => 'required|string',
            'additionalIncome.*.amount' => 'required|numeric|min:0',
            'additionalIncome.*.description' => 'nullable|string',
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

            // Step 3 messages
            'statutoryPensionClaims.required' => 'Die Angabe zu gesetzlichen Rentenansprüchen ist erforderlich.',
            'professionalProvisionWorks.required' => 'Die Angabe zur betrieblichen Altersvorsorge ist erforderlich.',
            'publicServiceAdditionalProvision.required' => 'Die Angabe zur öffentlich-rechtlichen Zusatzversorgung ist erforderlich.',
            'civilServiceProvision.required' => 'Die Angabe zur Beamtenversorgung ist erforderlich.',
            'payoutContracts.*.type.required' => 'Der Vertragstyp ist erforderlich.',
            'payoutContracts.*.amount.required' => 'Der Betrag ist erforderlich.',
            'payoutContracts.*.amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'payoutContracts.*.amount.min' => 'Der Betrag muss mindestens 0 sein.',
            'pensionContracts.*.type.required' => 'Der Vertragstyp ist erforderlich.',
            'pensionContracts.*.amount.required' => 'Der Betrag ist erforderlich.',
            'pensionContracts.*.amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'pensionContracts.*.amount.min' => 'Der Betrag muss mindestens 0 sein.',
            'additionalIncome.*.type.required' => 'Der Einkommenstyp ist erforderlich.',
            'additionalIncome.*.amount.required' => 'Der Betrag ist erforderlich.',
            'additionalIncome.*.amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'additionalIncome.*.amount.min' => 'Der Betrag muss mindestens 0 sein.',

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

            // Step 2 attributes
            'currentAge' => 'Aktuelles Alter',
            'retirementAge' => 'Rentenalter',
            'pensionWishCurrentValue' => 'Rentenwunsch in heutiger Kaufkraft',
            'guaranteedAmount' => 'Garantierter Betrag',
            'provisionDuration' => 'Versorgungsdauer',
            'assumedInflation' => 'Angenommene Inflation',

            // Step 3 attributes
            'statutoryPensionClaims' => 'Gesetzliche Rentenansprüche',
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