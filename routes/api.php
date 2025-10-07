<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DiscussionController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    // Rute Mata Kuliah (Semua butuh Auth)
Route::resource('courses', CourseController::class)->only(['index', 'store', 'update', 'destroy']);
Route::get('courses/{course}/materials', [MaterialController::class, 'index']);
Route::post('courses/{course}/materials', [MaterialController::class, 'store']);
Route::get('materials/{material}/download', [MaterialController::class, 'download']);

// Rute Enrollment (Khusus)
Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']);

// Rute Tugas & Penilaian
Route::post('assignments', [AssignmentController::class, 'store']);             // Dosen: Buat Tugas
Route::post('submissions', [AssignmentController::class, 'submit']);           // Mahasiswa: Unggah Jawaban
Route::post('submissions/{submission}/grade', [AssignmentController::class, 'grade']); // Dosen: Beri Nilai
//Rute untuk forum diskusi
Route::post('discussions', [DiscussionController::class, 'store']);
Route::post('discussions/{discussion}/replies', [DiscussionController::class, 'reply']);

//Rute untuk statistik
Route::get('reports/courses', [ReportController::class, 'courseReport']);
Route::get('reports/assignments', [ReportController::class, 'assignmentReport']);
Route::get('reports/students/{id}', [ReportController::class, 'studentReport']);
});