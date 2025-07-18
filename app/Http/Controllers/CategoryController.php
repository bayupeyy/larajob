<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        //$categories = Category::orderByDesc('id')->paginate(10);
        $categories = Category::with('jobs')->orderByDesc('id')->paginate(10);
        return view('super_admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //Digunakan untuk membuat kategori baru
        return view('super_admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        //
        DB::transaction(function () use ($request) {
            $validated = $request->validated();
            // Validasi dan simpan ikon jika ada
            if ($request->hasFile('icon')) {
                $iconPath = $request->file('icon')->store('icons' . date('Y/m/d'), 'public');
                $validated['icon'] = $iconPath;
            }
            // Simpan kategori
            $validated['slug'] = Str::slug($validated['name']);

            $newData = Category::create($validated);
        });

        // Redirect ke halaman kategori dengan pesan sukses
        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }
    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //Fitur untuk mengedit kategori
        return view('super_admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        DB::transaction(function () use ($request, $category) {
            $validated = $request->validated();

            if ($request->hasFile('icon')) {
                $iconPath = $request->file('icon')->store('icons/' . date('Y/m/d'), 'public');
                $validated['icon'] = $iconPath;
            }

            $validated['slug'] = Str::slug($validated['name']);

            $category->update($validated);
        });

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
        DB::transaction(function () use ($category) {
            // Hapus ikon jika ada
            if ($category->icon) {
                $category->delete();
            }
        });

        // Redirect ke halaman kategori dengan pesan sukses
        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus!');
    }
}
