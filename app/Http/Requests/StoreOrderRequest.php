<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create:orders');
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_email' => 'required_without:customer_id|email|max:255',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:product_variants,id',
            'lines.*.quantity' => 'required|integer|min:1|max:999',
            'billing_address' => 'required|array',
            'billing_address.line1' => 'required|string|max:255',
            'billing_address.line2' => 'nullable|string|max:255',
            'billing_address.city' => 'required|string|max:100',
            'billing_address.state' => 'required|string|max:100',
            'billing_address.zip' => 'required|string|max:20',
            'billing_address.country' => 'required|string|size:2',
            'shipping_address' => 'required|array',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.line2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'required|string|max:100',
            'shipping_address.zip' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|size:2',
            'customer_note' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'At least one product must be added to the order.',
            'lines.*.variant_id.exists' => 'One or more selected products are invalid.',
            'lines.*.quantity.min' => 'Product quantity must be at least 1.',
            'billing_address.required' => 'Billing address is required.',
            'shipping_address.required' => 'Shipping address is required.',
        ];
    }
}

