<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Reply;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    // -----------------------------------------------------------
    // 1. Dosen/Mahasiswa: Membuat Thread Diskusi Baru (POST /discussions)
    // -----------------------------------------------------------
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // 1. Validasi Input
            $request->validate([
                'course_id' => 'required|exists:courses,id',
                'content' => 'required|string',
            ]);

            // 2. Cek Otorisasi (Harus Dosen pengampu ATAU Mahasiswa terdaftar)
            $course = Course::find($request->course_id);

            $isLecturer = ($course->lecturer_id === $user->id);
            $isEnrolled = $user->role === 'mahasiswa' && $user->courses()->where('course_id', $course->id)->exists();

            if (!$isLecturer && !$isEnrolled) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda harus menjadi Dosen pengampu atau Mahasiswa terdaftar di Mata Kuliah ini.'
                ], 403);
            }

            // 3. Simpan Thread
            $discussion = Discussion::query()->create([
                'course_id' => $request->course_id,
                'user_id' => $user->id,
                'content' => $request->content,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Thread diskusi berhasil dibuat',
                'data' => $discussion->load('user:id,name,role')
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat thread diskusi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -----------------------------------------------------------
    // 2. Dosen/Mahasiswa: Membalas Thread Diskusi (POST /discussions/{discussion}/replies)
    // -----------------------------------------------------------
    public function reply(Request $request, Discussion $discussion)
    {
        try {
            $user = Auth::user();

            // 1. Validasi Input
            $request->validate([
                'content' => 'required|string',
            ]);

            // 2. Cek Otorisasi (Sama seperti di atas: Dosen pengampu ATAU Mahasiswa terdaftar)
            $course = $discussion->course;

            $isLecturer = ($course->lecturer_id === $user->id);
            $isEnrolled = $user->role === 'mahasiswa' && $user->courses()->where('course_id', $course->id)->exists();

            if (!$isLecturer && !$isEnrolled) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak. Anda harus terdaftar di Mata Kuliah ini untuk membalas diskusi.'
                ], 403);
            }

            // 3. Simpan Balasan
            $reply = Reply::query()->create([
                'discussion_id' => $discussion->id,
                'user_id' => $user->id,
                'content' => $request->content,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Balasan berhasil ditambahkan',
                'data' => $reply->load('user:id,name,role')
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan balasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}