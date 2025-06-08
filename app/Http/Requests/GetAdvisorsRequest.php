<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetAdvisorsRequest extends FormRequest
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
            'status' => 'sometimes|in:all,active,blocked',
            'search' => 'sometimes|string|max:255',
            'sort_by' => 'sometimes|in:created_at,name,email,status',
            'sort_order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Ungültiger Status-Filter.',
            'sort_by.in' => 'Ungültiges Sortierfeld.',
            'sort_order.in' => 'Ungültige Sortierreihenfolge.',
            'per_page.integer' => 'Anzahl pro Seite muss eine Zahl sein.',
            'per_page.min' => 'Mindestens 1 Element pro Seite.',
            'per_page.max' => 'Maximal 100 Elemente pro Seite.',
            'page.integer' => 'Seitenzahl muss eine Zahl sein.',
            'page.min' => 'Seitenzahl muss mindestens 1 sein.',
        ];
    }
} 