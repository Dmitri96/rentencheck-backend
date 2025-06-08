<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientRequest extends FormRequest
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
        return [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('clients', 'email')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                }),
            ],
            'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/|max:20',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|regex:/^[0-9]{5}$/|max:5',
            'birth_date' => 'nullable|date|before:today|after:1900-01-01',
            'notes' => 'nullable|string|max:1000',
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
            'first_name.required' => 'Der Vorname ist erforderlich.',
            'first_name.min' => 'Der Vorname muss mindestens 2 Zeichen lang sein.',
            'first_name.max' => 'Der Vorname darf maximal 50 Zeichen lang sein.',
            
            'last_name.required' => 'Der Nachname ist erforderlich.',
            'last_name.min' => 'Der Nachname muss mindestens 2 Zeichen lang sein.',
            'last_name.max' => 'Der Nachname darf maximal 50 Zeichen lang sein.',
            
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits für einen anderen Mandanten registriert.',
            'email.max' => 'Die E-Mail-Adresse darf maximal 255 Zeichen lang sein.',
            
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'phone.max' => 'Die Telefonnummer darf maximal 20 Zeichen lang sein.',
            
            'street.max' => 'Die Straße darf maximal 255 Zeichen lang sein.',
            'city.max' => 'Die Stadt darf maximal 100 Zeichen lang sein.',
            
            'postal_code.regex' => 'Bitte geben Sie eine gültige 5-stellige PLZ ein.',
            'postal_code.max' => 'Die PLZ darf maximal 5 Zeichen lang sein.',
            
            'birth_date.date' => 'Bitte geben Sie ein gültiges Geburtsdatum ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Das Geburtsdatum muss nach dem 01.01.1900 liegen.',
            
            'notes.max' => 'Die Notizen dürfen maximal 1000 Zeichen lang sein.',
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
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'email' => 'E-Mail-Adresse',
            'phone' => 'Telefonnummer',
            'street' => 'Straße',
            'city' => 'Stadt',
            'postal_code' => 'PLZ',
            'birth_date' => 'Geburtsdatum',
            'notes' => 'Notizen',
        ];
    }
}
