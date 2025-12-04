<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create:products');
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where('tenant_id', TenantContext::getId()),
            ],
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,published,archived',
            'metadata' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'The product SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'title.required' => 'The product title is required.',
            'title.max' => 'The product title cannot exceed 500 characters.',
        ];
    }
}

