<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search', '');
        $perPage = in_array((int)$request->get('per_page', 10), [10, 25, 50, 100])
                    ? (int)$request->get('per_page', 10)
                    : 10;

        $products = Product::orderBy('name')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('variasi', 'like', "%{$search}%"))
            ->paginate($perPage)
            ->withQueryString();

        return view('products.index', compact('products', 'search', 'perPage'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'variasi'=> ['required', 'string', 'max:255'],
            'price'  => ['required', 'numeric', 'min:0'],
        ]);

        Product::create([
            'name'   => $validated['name'],
            'variasi'=> $validated['variasi'],
            'price'  => $validated['price'],
            'stock'  => 0,
        ]);

        return redirect()->route('products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'variasi'=> ['required', 'string', 'max:255'],
            'price'  => ['required', 'numeric', 'min:0'],
        ]);

        $product->update([
            'name'   => $validated['name'],
            'variasi'=> $validated['variasi'],
            'price'  => $validated['price'],
        ]);

        return redirect()->route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        try {
            $product->sales()->delete();
            $product->delete();

            return redirect()->route('products.index')->with('status', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Produk gagal dihapus. Terjadi kesalahan pada database.');
        }
    }
}
