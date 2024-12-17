<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajustez selon vos besoins d'authentification
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firebaseId' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'url'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:draft,pending,current,past,cancelled'],
            'is_active' => ['boolean'],
            'stripe_event_id' => ['nullable', 'string'],
            'stripe_price_id' => ['nullable', 'string']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'firebaseId.required' => 'L\'ID Firebase est requis',
            'title.required' => 'Le titre est requis',
            'image_url.url' => 'L\'URL de l\'image doit être valide',
            'price.required' => 'Le prix est requis',
            'scheduled_date.required' => 'La date est requise',
            'status.required' => 'Le statut est requis',
            'status.in' => 'Le statut doit être l\'un des suivants : draft, pending, current, past, cancelled',
            'is_active.required' => 'Le statut actif est requis',
            'stripe_event_id.required_with' => 'L\'ID Stripe est requis pour un événement payant',
            'stripe_price_id.required_with' => 'L\'ID Prix Stripe est requis pour un événement payant'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Convertir la date au format approprié si nécessaire
        if ($this->has('scheduled_date')) {
            $this->merge([
                'scheduled_date' => date('Y-m-d H:i:s', strtotime($this->scheduled_date))
            ]);
        }
    }
}
