<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:unpaid,paid,cancelled,refunded'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'qr_code' => ['sometimes', 'string'],
            'customer_email' => ['sometimes', 'email'],
            'customer_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est requis',
            'status.in' => 'Le statut doit être l\'un des suivants : unpaid, paid, cancelled, refunded',
            'total_price.numeric' => 'Le prix total doit être un nombre',
            'total_price.min' => 'Le prix total ne peut pas être négatif',
            'customer_email.email' => 'L\'adresse email doit être valide',
            'customer_name.max' => 'Le nom ne peut pas dépasser 255 caractères',
        ];
    }
}
