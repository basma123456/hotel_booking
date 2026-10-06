<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return   [
            'hotel_id' => [  'required',  'integer',  'exists:hotels,id', ],
            'room_type_id' => [  'required',  'integer',  'exists:room_types,id',   ],
            'check_in' => [   'required',  'date',  ],
            'check_out' => [ 'required',  'date',  'after:check_in',   ],
            'rooms_count' => [  'required',   'integer',  'min:1', ],
            'customer_name' => [ 'required',   'string', 'max:255',  ],
            'phone' => [  'nullable', 'string',  'max:30', ],
            'email' => [ 'nullable', 'email',  'max:255',  ],
            'idempotency_key' => ['required', 'string', 'max:255'],

        ];
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }
}
