<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tag;
use http\Client\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = \App\Models\Product::query()->where('in_stock', true)
            ->with('tags')
            ->latest()
            ->get();

        $tags = Tag::all();

        return [
            'tags' => $tags,
            'products' => $products,
        ];
    }

    public function store(Request $request)
    {
        return \App\Models\Product::query()->create($request->all);
    }

    public function show($id)
    {
        return \App\Models\Product::query()->find($id);
    }

    public function update(Request $request, $id)
    {
        $product = \App\Models\Product::query()->findOrFail($id);
        $product->update($request->all());

        return $product;
    }

    public function delete($id)
    {
        \App\Models\Product::query()->find($id)->delete();

        return 204;
    }

    public function addToCart(Product $product, Request $request): void
    {
        $product->clicks()->create([
            'ip' => $request->ip(),
        ]);
    }
}
