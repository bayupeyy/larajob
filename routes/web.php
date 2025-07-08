<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\JobCandidateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontController::class, 'index'])->name('frontend.index');
Route::get('/details/{company_job:slug}', [FrontController::class, 'details'])->name('frontend.details');
Route::get('/category/{category:slug}', [FrontController::class, 'category'])->name('frontend.category');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('can:apply job')->group(function () {
        Route::get('/apply/success', [FrontController::class, 'success_apply'])->name('frontend.apply.success');

        Route::get('/apply/{company_job:slug}', [FrontController::class, 'apply'])->name('frontend.apply');
        Route::post('/apply/{company_job:slug}/submit', [FrontController::class, 'apply_store'])->name('frontend.apply.store');

        });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {

        Route::middleware('can:apply job')->group(function () {
            Route::get('my-applications', [DashboardController::class, 'my_applications'])->name('my.applications');
            Route::get('my-applications/{job_candidate}', [DashboardController::class, 'my_applications_details'])->name('my.application.details');
        });
    });

    Route::prefix('admin')->name('admin.')->group(function () {

        Route::middleware('can:manage categories')->group(function () {
            Route::resource('categories', CategoryController::class);
        });

        Route::middleware('can:manage company')->group(function () {
            Route::resource('company', CompanyController::class);
        });

        Route::middleware('can:manage jobs')->group(function () {
            Route::resource('company_jobs', CompanyJobController::class);
        });

        Route::middleware('can:manage applicants')->group(function () {
            Route::resource('job_candidates', JobCandidateController::class);
            Route::get('/candidate/{job_candidate}/resume/download', [JobCandidateController::class, 'download_file'])->name('download_resume');
        });
    });
});

require __DIR__ . '/auth.php';
