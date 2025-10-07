<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Tambahkan ini jika belum ada

class CourseController extends Controller
{
    // GET /courses - Menampilkan daftar semua mata kuliah
    public function index()
    {
        try {
            // Menampilkan course beserta nama dosen pengampunya
            $courses = Course::with('lecturer:id,name')->get();

            return response()->json([
                'status' => true,
                'message' => 'Daftar mata kuliah berhasil dimuat',
                'data' => $courses
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat mata kuliah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /courses - Membuat mata kuliah baru (Hanya Dosen)
    public function store(Request $request)
    {
        try {
            // --- Cek Role (Hanya Dosen yang Boleh) ---
            if (Auth::user()->role !== 'dosen') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Dosen yang diizinkan untuk membuat Mata Kuliah.'
                ], 403);
            }

            // 1. Validasi
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // 2. Simpan
            $course = Course::query()->create([
                'name' => $request->name,
                'description' => $request->description,
                'lecturer_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Mata kuliah berhasil ditambahkan',
                'data' => $course
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan mata kuliah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PUT/PATCH /courses/{course} - Update mata kuliah (Hanya Dosen pemilik)
    public function update(Request $request, Course $course)
    {
        try {
            // --- Cek Role dan Otorisasi (Hanya Dosen Pemilik) ---
            if (Auth::user()->role !== 'dosen' || $course->lecturer_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda bukan Dosen pemilik Mata Kuliah ini.'
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $course->update($request->only(['name', 'description']));

            return response()->json([
                'status' => true,
                'message' => 'Mata kuliah berhasil diperbarui',
                'data' => $course
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui mata kuliah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /courses/{course} - Soft Delete mata kuliah (Hanya Dosen pemilik)
    public function destroy(Course $course)
    {
        try {
            // --- Cek Role dan Otorisasi (Hanya Dosen Pemilik) ---
            if (Auth::user()->role !== 'dosen' || $course->lecturer_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda bukan Dosen pemilik Mata Kuliah ini.'
                ], 403);
            }

            $course->delete();

            return response()->json([
                'status' => true,
                'message' => 'Mata kuliah berhasil dihapus (Soft Delete)'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus mata kuliah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /courses/{course}/enroll - Mahasiswa mendaftar (Hanya Mahasiswa)
    public function enroll(Course $course)
    {
        try {
            // --- Cek Role (Hanya Mahasiswa yang Boleh Enroll) ---
            $user = Auth::user();
            if ($user->role !== 'mahasiswa') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Mahasiswa yang dapat mendaftar.'
                ], 403);
            }

            // Cek apakah mahasiswa sudah terdaftar
            if ($user->courses()->where('course_id', $course->id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah terdaftar di Mata Kuliah ini.'
                ], 409); // 409 Conflict
            }

            // Lakukan pendaftaran (attach ke tabel course_user)
            $user->courses()->attach($course->id);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendaftar di Mata Kuliah: ' . $course->name
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendaftar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}