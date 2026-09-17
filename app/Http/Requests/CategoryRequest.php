<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper(trim((string) $this->code))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'code' => ['required', 'alpha_dash:ascii', 'max:20', Rule::unique('categories')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'code' => 'código', 'description' => 'descrição'];
    }
}
