<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAdvisorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Der Vorname ist erforderlich.',
            'last_name.required' => 'Der Nachname ist erforderlich.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen haben.',
            'password.confirmed' => 'Die Passwort-Bestätigung stimmt nicht überein.',
        ];
    }
} 