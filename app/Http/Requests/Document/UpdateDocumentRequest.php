<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
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
            'type' => 'required|string|in:proposal,contract,invoice,report,brief,misc',
            'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:5120',
            'documentable_type' => 'required|string|in:lead,client,project',
            'documentable_id' => 'required|integer',
        ];
    }
}
