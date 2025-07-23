<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplyJobRequest;
use App\Models\Category;
use App\Models\CompanyJob;
use App\Models\JobCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <- Penting untuk DB::transaction

class FrontController extends Controller
{
    public function index()
    {
        $categories = Category::with('jobs')->get();
        $jobs = CompanyJob::with(['category', 'company'])
            ->latest()
            ->take(6)
            ->get();

        return view('frontend.index', compact('jobs', 'categories'));
    }

    public function details(CompanyJob $companyJob)
    {
        $jobs = CompanyJob::with(['category', 'company'])
            ->where('id', '!=', $companyJob->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('frontend.details', compact('companyJob', 'jobs'));
    }

    // Menampilkan form apply
    public function apply(CompanyJob $companyJob)
    {
        return view('frontend.apply', compact('companyJob'));
    }

    // Menyimpan data lamaran
    public function apply_store(StoreApplyJobRequest $request, CompanyJob $companyJob)
    {
        $user = auth()->user();

        // Cek apakah sudah melamar
        $hasApplied = JobCandidate::where('company_job_id', $companyJob->id)
            ->where('candidate_id', $user->id)
            ->exists(); // lebih efisien dibanding first()

        if ($hasApplied) {
            return redirect()->back()->withErrors(['applied' => 'Anda sudah pernah melamar pada pekerjaan ini.']);
        }

        // Simpan dalam transaksi
        DB::transaction(function () use ($request, $companyJob, $user) {
            $validated = $request->validated();

            if ($request->hasFile('resume')) {
                $resumePath = $request->file('resume')->store('resumes/' . date('Y/m/d'), 'public');
                $validated['resume'] = $resumePath;
            }

            $validated['candidate_id'] = $user->id;
            $validated['company_job_id'] = $companyJob->id;
            $validated['is_hired'] = false;

            JobCandidate::create($validated);
        });

        return redirect()->route('frontend.apply.success');
    }
    public function success_apply()
    {
        return view('frontend.apply_success');
    }

    public function search(Request $request)
    {
        $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
        ]);

        $keyword = $request->keyword;
        $jobs = CompanyJob::with(['company', 'category'])
            ->where('name', 'like', '%' . $keyword . '%')
            ->paginate(1);

        return view('frontend.search', compact('jobs', 'keyword'));
    }
}
