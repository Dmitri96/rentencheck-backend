<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Update Pension Settings Request
 *
 * Handles validation for bulk updating multiple pension settings
 */
class BulkUpdatePensionSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by PensionSettingPolicy::bulkUpdate via
     * $this->authorize('bulkUpdate', PensionSetting::class) in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array|min:1',
            'settings.*.id' => 'required|integer|exists:pension_settings,id',
            'settings.*.value' => 'required|numeric|min:0|max:1000000',
        ];
    }

    /**
     * Get custom validation error messages in German.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Einstellungen sind erforderlich.',
            'settings.array' => 'Einstellungen müssen ein Array sein.',
            'settings.min' => 'Mindestens eine Einstellung ist erforderlich.',
            'settings.*.id.required' => 'Die Einstellungs-ID ist erforderlich.',
            'settings.*.id.integer' => 'Die Einstellungs-ID muss eine Zahl sein.',
            'settings.*.id.exists' => 'Die Einstellung existiert nicht.',
            'settings.*.value.required' => 'Der Wert ist erforderlich.',
            'settings.*.value.numeric' => 'Der Wert muss eine Zahl sein.',
            'settings.*.value.min' => 'Der Wert muss mindestens 0 betragen.',
            'settings.*.value.max' => 'Der Wert darf maximal 1.000.000 betragen.',
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
            'settings' => 'Einstellungen',
            'settings.*.id' => 'Einstellungs-ID',
            'settings.*.value' => 'Wert',
        ];
    }
}
