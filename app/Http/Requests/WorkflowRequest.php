<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'uuid', Rule::exists('tenants', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'definition' => ['required', 'array'],
            'definition.steps' => ['required', 'array', 'min:1'],
            'definition.steps.*.id' => ['required', 'string', 'max:255'],
            'definition.steps.*.type' => ['required', 'string', 'max:255'],
            'definition.steps.*.depends_on' => ['sometimes', 'array'],
            'definition.steps.*.depends_on.*' => ['string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
