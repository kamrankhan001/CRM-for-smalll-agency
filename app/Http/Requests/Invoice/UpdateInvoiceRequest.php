<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0|max:'.$this->amount,
            'status' => 'required|in:draft,sent,partially_paid,paid,cancelled,overdue',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.max' => 'Amount paid cannot exceed the total invoice amount.',
            'due_date.after_or_equal' => 'Due date must be on or after the issue date.',
        ];
    }
}
