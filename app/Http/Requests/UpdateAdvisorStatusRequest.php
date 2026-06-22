<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdvisorStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Role enforcement happens via the route middleware (role:admin).
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
            'status' => 'required|in:active,blocked',
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
            'status.required' => 'Der Status ist erforderlich.',
            'status.in' => 'Ungültiger Status.',
        ];
    }
}
