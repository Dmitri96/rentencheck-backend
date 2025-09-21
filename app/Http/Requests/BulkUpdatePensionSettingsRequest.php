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
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array|min:1',
            'settings.*.id' => 'required|integer|exists:pension_settings,id',
            'settings.*.value' => 'required|numeric|min:0|max:1000',
        ];
    }

    /**
     * Get custom validation error messages in German.
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
            'settings.*.value.max' => 'Der Wert darf maximal 1000 betragen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
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