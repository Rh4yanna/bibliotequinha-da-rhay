<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class LoanRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', Loan::class); }
    public function rules(): array
    {
        return ['user_id' => ['required', 'integer', 'exists:users,id'], 'book_id' => ['required', 'integer', 'exists:books,id'], 'due_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']];
    }
}
