<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Material;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    // GET /courses/{course}/materials - Menampilkan daftar materi
    public function index(Course $course)
    {
        try {
            // Cek apakah pengguna terdaftar di course ini (Dosen pengampu atau Mahasiswa terdaftar)
            $user = Auth::user();
            if (
                $course->lecturer_id !== $user->id && // Bukan Dosen Pengampu
                $user->role === 'mahasiswa' &&       // Adalah Mahasiswa
                !$user->courses()->where('course_id', $course->id)->exists() // Tidak terdaftar
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda harus terdaftar di Mata Kuliah ini untuk melihat materi.'
                ], 403);
            }
            
            // Tampilkan daftar materi untuk course ini
            $materials = $course->materials()->get(['id', 'title', 'file_path', 'created_at']);

            return response()->json([
                'status' => true,
                'message' => 'Daftar materi berhasil dimuat',
                'data' => $materials
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /courses/{course}/materials - Mengunggah materi baru (Hanya Dosen)
    public function store(Request $request, Course $course)
    {
        try {
            // --- Cek Role dan Otorisasi (Hanya Dosen Pemilik) ---
            $user = Auth::user();
            if ($user->role !== 'dosen' || $course->lecturer_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Hanya Dosen pengampu yang diizinkan untuk mengunggah materi.'
                ], 403);
            }
            
            // 1. Validasi
            $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,zip,rar|max:50000', // max 50MB
            ]);

            // 2. Simpan File ke Storage
            // File akan disimpan di storage/app/public/materials
            $path = $request->file('file')->store('materials', 'public');

            // 3. Simpan Path ke Database
            $material = Material::query()->create([
                'course_id' => $course->id,
                'title' => $request->title,
                'file_path' => $path, // Simpan path public/materials/namafile.ext
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Materi berhasil diunggah dan ditambahkan',
                'data' => $material
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunggah materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /materials/{material}/download - Mengunduh Materi
    public function download(Material $material)
    {
        try {
            // Cek apakah pengguna (Dosen atau Mahasiswa terdaftar) berhak mengakses Course ini
            $user = Auth::user();
            $course = $material->course;
            
            if (
                $course->lecturer_id !== $user->id && // Bukan Dosen Pengampu
                $user->role === 'mahasiswa' &&       // Adalah Mahasiswa
                !$user->courses()->where('course_id', $course->id)->exists() // Tidak terdaftar
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda tidak terdaftar di Mata Kuliah ini.'
                ], 403);
            }

            // Cek apakah file ada
            if (!Storage::disk('public')->exists($material->file_path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File tidak ditemukan.'
                ], 404);
            }
            
            // Lakukan pengunduhan
            return Storage::disk('public')->download($material->file_path, $material->title);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunduh materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}