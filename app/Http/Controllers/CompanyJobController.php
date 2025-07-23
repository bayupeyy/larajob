<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyJobRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = Auth::user();
        $my_company = Company::where('employer_id', $user->id)->first();

        if ($my_company) {
            $company_jobs = CompanyJob::with(['category'])->where('company_id', $my_company->id)->paginate(1);
        } else {
            $company_jobs = collect();
        }

        return view('admin.company_jobs.index', compact('company_jobs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        // Mengambil data user yang sedang login
        $user = Auth::user();

        // Mencari perusahaan yang dimiliki oleh user berdasarkan employer_id
        $my_company = Company::where('employer_id', $user->id)->first();

        // Jika user belum memiliki perusahaan, arahkan ke halaman pembuatan perusahaan
        if (!$my_company) {
            return redirect()->route('admin.company.create');
        }

        // Mengambil semua data kategori untuk digunakan di form pembuatan pekerjaan
        $categories = Category::all();

        // Menampilkan view form pembuatan pekerjaan,
        // dan mengirimkan data kategori dan perusahaan milik user ke dalam view
        return view('admin.company_jobs.create', compact('categories', 'my_company'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyJobRequest $request)
    {
        //
        // Menjalankan proses dalam transaksi database
        DB::transaction(function () use ($request) {

            // Validasi input dari request
            $validated = $request->validated();

            // Jika ada file thumbnail yang diunggah, simpan ke folder 'thumbnails' di storage publik
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath; // Tambahkan path thumbnail ke data yang akan disimpan
            }

            // Buat slug dari nama pekerjaan (untuk URL friendly)
            $validated['slug'] = Str::slug($validated['name']);

            // Set pekerjaan baru sebagai 'terbuka' (is_open = true)
            $validated['is_open'] = true;

            // Simpan data pekerjaan baru ke tabel company_jobs
            $newJob = CompanyJob::create($validated);

            // Jika ada data tanggung jawab (responsibilities), simpan satu per satu
            if (!empty($validated['responsibilities'])) {
                foreach ($validated['responsibilities'] as $responsibility) {
                    $newJob->responsibilities()->create([
                        'name' => $responsibility,
                    ]);
                }
            }

            // Jika ada data kualifikasi (qualifications), simpan satu per satu
            if (!empty($validated['qualifications'])) {
                foreach ($validated['qualifications'] as $qualification) {
                    $newJob->qualifications()->create([
                        'name' => $qualification,
                    ]);
                }
            }
        });

        // Setelah berhasil menyimpan, redirect ke halaman daftar pekerjaan dengan pesan sukses
        return redirect()->route('admin.company_jobs.index')->with('success', 'Pekerjaan berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CompanyJob $companyJob)
    {
        //Digunakan untuk menampilkan detail pekerjaan
        return view('admin.company_jobs.show', compact('companyJob'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CompanyJob $companyJob)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompanyJob $companyJob)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyJob $companyJob)
    {
        //
    }
}
