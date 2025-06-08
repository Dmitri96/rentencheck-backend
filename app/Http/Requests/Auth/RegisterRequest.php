<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

final class RegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
            'company' => ['nullable', 'string', 'max:100'],
            'plan' => ['required', 'string', 'in:free,basic,premium,vip'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->letters()->mixedCase()->numbers()],
            'password_confirmation' => ['required', 'string'],
            'accept_terms' => ['required', 'boolean', 'accepted'],
            'accept_privacy' => ['required', 'boolean', 'accepted'],
            'newsletter' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Vorname ist erforderlich.',
            'first_name.max' => 'Vorname darf maximal 50 Zeichen lang sein.',
            'last_name.required' => 'Nachname ist erforderlich.',
            'last_name.max' => 'Nachname darf maximal 50 Zeichen lang sein.',
            'email.required' => 'E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'company.max' => 'Unternehmensname darf maximal 100 Zeichen lang sein.',
            'plan.required' => 'Bitte wählen Sie einen Tarif aus.',
            'plan.in' => 'Der gewählte Tarif ist ungültig.',
            'password.required' => 'Passwort ist erforderlich.',
            'password.min' => 'Passwort muss mindestens 8 Zeichen lang sein.',
            'password.letters' => 'Passwort muss mindestens einen Buchstaben enthalten.',
            'password.mixed' => 'Passwort muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
            'password.numbers' => 'Passwort muss mindestens eine Zahl enthalten.',
            'password.confirmed' => 'Passwörter stimmen nicht überein.',
            'password_confirmation.required' => 'Passwort-Bestätigung ist erforderlich.',
            'accept_terms.required' => 'Sie müssen die Allgemeinen Geschäftsbedingungen akzeptieren.',
            'accept_terms.accepted' => 'Sie müssen die Allgemeinen Geschäftsbedingungen akzeptieren.',
            'accept_privacy.required' => 'Sie müssen die Datenschutzerklärung akzeptieren.',
            'accept_privacy.accepted' => 'Sie müssen die Datenschutzerklärung akzeptieren.',
        ];
    }
} 