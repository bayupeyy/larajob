<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = Auth::user();
        $company = Company::with(['employer'])->where('employer_id', $user->id)->first();
        if (!$company) {
            return redirect()->route('admin.company.create');
        }

        return view('admin.company.index', compact('company'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('admin.company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        //
        // Ambil user yang sedang login
        $user = Auth::user();

        // Cek apakah user sudah memiliki company
        $company = Company::where('employer_id', $user->id)->first();
        if ($company) {
            return redirect()->back()->withErrors(['company' => 'Failed! Anda sudah membuat company.']);
        }

        // Mulai transaksi database
        DB::transaction(function () use ($request, $user) {
            // Validasi data
            $validated = $request->validated();

            // Jika ada file logo diupload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos/' . date('Y/m/d'), 'public');
                $validated['logo'] = $logoPath;
            }

            // Generate slug dari nama perusahaan
            $validated['slug'] = Str::slug($validated['name']);
            // Tambahkan employer_id dari user yang login
            $validated['employer_id'] = $user->id;

            // Simpan data company ke database
            $newData = Company::create($validated);
        });

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.company.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        //Digunakan untuk mengedit company
        return view('admin.company.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        //Digunakan untuk mengupdate company
        // Validasi data
        DB::transaction(function () use ($request, $company) {
            $validated = $request->validated();

            // Jika ada file logo diupload
            if ($request->hasFile('logo')) {
                // Hapus logo lama jika ada
                $logoPath =
                $request->file('logo')->store('logos/' . date('Y/m/d'), 'public');
                $validated['logo'] = $logoPath;
            }
            // Update slug jika nama perusahaan berubah
            $validated['slug'] = Str::slug($validated['name']);
            // Update data company
            $company->update($validated);
        });

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.company.index')->with('success', 'Company updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //Digunakan untuk menghapus company
        DB::transaction(function () use ($company) {
            // Hapus data company
            $company->delete();
        });
        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.company.index')->with('success', 'Company deleted successfully!');
    }
}
