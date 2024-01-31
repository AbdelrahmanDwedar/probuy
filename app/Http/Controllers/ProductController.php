<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tag;
use http\Client\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('in_stock', true)
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
        return Product::create($request->all);
    }

    public function show($id)
    {
        return Product::find($id);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());

        return $product;
    }

    public function delete($id)
    {
        Product::find($id)->delete();

        return 204;
    }

    public function addToCart(Product $product, Request $request)
    {
        $product->clicks()->create([
            'ip' => $request->ip()
        ]);
    }
}
