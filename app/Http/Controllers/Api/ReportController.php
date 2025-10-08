<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    
    // 1. GET /reports/courses (Statistik Mahasiswa per Mata Kuliah)
    
    public function courseReport()
    {
        try {
            // Menggunakan withCount untuk menghitung relasi students
            $courses = Course::withCount('students') 
                ->with('lecturer:id,name') // Tampilkan nama dosen
                ->get(['id', 'name', 'description', 'lecturer_id']);

            return response()->json([
                'status' => true,
                'message' => 'Laporan jumlah mahasiswa per mata kuliah berhasil dimuat',
                'data' => $courses
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    // 2. GET /reports/assignments (Statistik Tugas: Dinilai/Belum Dinilai)
    
    public function assignmentReport()
    {
        try {
            // Menggunakan withCount dengan constraint untuk menghitung submission
            $assignments = Assignment::withCount([
                'submissions', // Total submission
                'submissions as graded_count' => function ($query) {
                    $query->whereNotNull('score'); // Submission sudah dinilai
                },
                'submissions as ungraded_count' => function ($query) {
                    $query->whereNull('score'); // Submission belum dinilai
                },
            ])
            ->with('course:id,name') // Tampilkan nama mata kuliah
            ->get(['id', 'course_id', 'title', 'deadline']);

            return response()->json([
                'status' => true,
                'message' => 'Laporan status penilaian tugas berhasil dimuat',
                'data' => $assignments
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    // 3. GET /reports/students/{id} (Statistik Tugas & Nilai Mahasiswa Tertentu)
    
    public function studentReport($id)
    {
        try {
            // 1. Pastikan pengguna adalah Mahasiswa
            $student = User::where('id', $id)->where('role', 'mahasiswa')->first();

            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mahasiswa tidak ditemukan.'
                ], 404);
            }

            // 2. Ambil semua submission yang dilakukan oleh mahasiswa ini
            $submissions = $student->submissions()
                ->with('assignment:id,title,deadline') // Tampilkan data assignment
                ->get(['id', 'assignment_id', 'score', 'created_at']);

            // 3. Hitung statistik
            $submittedCount = $submissions->count();
            $gradedCount = $submissions->whereNotNull('score')->count();
            $averageScore = $submissions->whereNotNull('score')->avg('score');

            // 4. Hitung total tugas yang harus dikerjakan (dari semua course yang diikuti)
            $totalAssignments = $student->courses->sum(fn ($course) => $course->assignments->count());

            return response()->json([
                'status' => true,
                'message' => 'Statistik tugas mahasiswa berhasil dimuat',
                'data' => [
                    'student' => ['id' => $student->id, 'name' => $student->name],
                    'statistics' => [
                        'total_assignments_in_enrolled_courses' => $totalAssignments,
                        'submitted_count' => $submittedCount,
                        'graded_count' => $gradedCount,
                        'average_score' => round($averageScore ?? 0, 2), // Pembulatan 2 angka desimal
                    ],
                    'submissions_detail' => $submissions,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat statistik mahasiswa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}