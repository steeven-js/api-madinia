<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'firebaseId' => ['required', 'string', 'unique:events,firebaseId'],
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'url'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['required', 'date'],
            'is_active' => ['boolean'],
            'stripe_event_id' => ['nullable', 'string'],
            'stripe_price_id' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'activated_at' => ['nullable', 'date']
        ];
    }
}
