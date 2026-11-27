<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_type' => ['nullable', new Enum(PropertyType::class)],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $criteria = [
                $this->input('property_type'),
                $this->input('max_price'),
                $this->input('min_bedrooms'),
                $this->input('region'),
            ];

            $hasCriterion = collect($criteria)->contains(
                fn (mixed $value) => $value !== null && $value !== '',
            );

            if (! $hasCriterion) {
                $validator->errors()->add(
                    'criteria',
                    'Choose at least one filter before saving a search.',
                );
            }
        });
    }
}
