<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Pension Setting Request
 * 
 * Handles validation for updating individual pension settings
 */
class UpdatePensionSettingRequest extends FormRequest
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
            'value' => 'required|numeric|min:0|max:1000',
            'description' => 'sometimes|string|max:255',
            'description_de' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom validation error messages in German.
     */
    public function messages(): array
    {
        return [
            'value.required' => 'Der Wert ist erforderlich.',
            'value.numeric' => 'Der Wert muss eine Zahl sein.',
            'value.min' => 'Der Wert muss mindestens 0 betragen.',
            'value.max' => 'Der Wert darf maximal 1000 betragen.',
            'description.string' => 'Die Beschreibung muss ein Text sein.',
            'description.max' => 'Die Beschreibung darf maximal 255 Zeichen lang sein.',
            'description_de.string' => 'Die deutsche Beschreibung muss ein Text sein.',
            'description_de.max' => 'Die deutsche Beschreibung darf maximal 255 Zeichen lang sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'value' => 'Wert',
            'description' => 'Beschreibung',
            'description_de' => 'Deutsche Beschreibung',
        ];
    }
} 