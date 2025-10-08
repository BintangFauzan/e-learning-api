<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Course; // Dipakai untuk validasi Course Dosen
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
  
    // 1. Dosen: Membuat Tugas Baru (POST /assignments)
  
    public function store(Request $request)
    {
        try {
            // --- Cek Role (Hanya Dosen yang Boleh) ---
            $user = Auth::user();
            if ($user->role !== 'dosen') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Dosen yang diizinkan.'
                ], 403);
            }

            // 1. Validasi Input
            $request->validate([
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'required|date|after:now', // Waktu deadline harus di masa depan
            ]);

            // 2. Cek Otorisasi (Dosen harus pengampu course tersebut)
            $course = Course::find($request->course_id);
            if ($course->lecturer_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda bukan Dosen pengampu Mata Kuliah ini.'
                ], 403);
            }

            // 3. Simpan Tugas
            $assignment = Assignment::query()->create([
                'course_id' => $request->course_id,
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tugas berhasil dibuat',
                'data' => $assignment
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat tugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  
    // 2. Mahasiswa: Mengunggah Jawaban (POST /submissions)
  
    public function submit(Request $request)
    {
        try {
            // --- Cek Role (Hanya Mahasiswa yang Boleh) ---
            $user = Auth::user();
            if ($user->role !== 'mahasiswa') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Mahasiswa yang diizinkan untuk mengunggah jawaban.'
                ], 403);
            }

            // 1. Validasi
            $request->validate([
                'assignment_id' => 'required|exists:assignments,id',
                'file' => 'required|file|mimes:pdf,doc,docx,zip,rar|max:50000',
            ]);

            $assignment = Assignment::find($request->assignment_id);
            $course = $assignment->course;

            // 2. Cek Otorisasi (Mahasiswa harus terdaftar di course)
            if (!$user->courses()->where('course_id', $course->id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda belum terdaftar di Mata Kuliah Tugas ini.'
                ], 403);
            }

            // 3. Cek Deadline
            if (now()->greaterThan($assignment->deadline)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal mengunggah. Tugas sudah melewati batas waktu (deadline).'
                ], 400);
            }

            // 4. Hapus submission lama (jika ada, untuk update)
            Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $user->id)
                ->delete();

            // 5. Simpan File ke Storage
            $path = $request->file('file')->store('submissions/' . $assignment->id, 'public');

            // 6. Simpan Path ke Database
            $submission = Submission::query()->create([
                'assignment_id' => $assignment->id,
                'student_id' => $user->id,
                'file_path' => $path,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jawaban berhasil diunggah',
                'data' => $submission
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunggah jawaban',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  
    // 3. Dosen: Memberi Nilai (POST /submissions/{submission}/grade)
  
    public function grade(Request $request, Submission $submission)
    {
        try {
            // --- Cek Role (Hanya Dosen yang Boleh) ---
            $user = Auth::user();
            if ($user->role !== 'dosen') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Dosen yang diizinkan untuk memberi nilai.'
                ], 403);
            }

            // 1. Cek Otorisasi (Dosen harus pengampu course tugas ini)
            $assignment = $submission->assignment;
            $course = $assignment->course;

            if ($course->lecturer_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda bukan Dosen pengampu Mata Kuliah tugas ini.'
                ], 403);
            }

            // 2. Validasi Nilai
            $request->validate([
                'score' => 'required|integer|min:0|max:100',
            ]);

            // 3. Update Submission
            $submission->update(['score' => $request->score]);

            return response()->json([
                'status' => true,
                'message' => 'Nilai berhasil disimpan',
                'data' => $submission->load('student:id,name') // Tampilkan nama mahasiswa
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memberi nilai',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}