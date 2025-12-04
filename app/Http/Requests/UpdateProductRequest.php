<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update:products');
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where('tenant_id', TenantContext::getId())
                    ->ignore($this->route('product')),
            ],
            'title' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'metadata' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }
}

