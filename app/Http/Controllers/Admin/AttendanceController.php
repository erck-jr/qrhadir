<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceQrToken;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Tampilkan halaman scan QR
     */
    public function scan()
    {
        return view('pages.attendance.scan');
    }

    /**
     * Proses hasil scan code via AJAX/API form submit
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $tokenStr = $request->token;

        // Cari tokennya
        $qrToken = AttendanceQrToken::with([
            'eventParticipant.participant' => function ($query) {
                $query->withoutGlobalScope('ownership');
            },
            'eventParticipant.event'
        ])
            ->where('token', $tokenStr)
            ->first();

        // 1. Cek Validitas Token
        if (!$qrToken || !$qrToken->eventParticipant->event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid, atau Anda tidak memiliki akses ke event ini.'
            ], 404);
        }
        
        $event = $qrToken->eventParticipant->event;
        $participant = $qrToken->eventParticipant->participant;

        // 2. Cek Event Status
        if ($event->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Event tidak aktif (Draft/Closed).'
            ], 400);
        }

        // 3. Cek Tanggal Event (Range)
        $now = now();
        
        // Pastikan start_date dan end_date adalah Carbon object (jika belum dicast di model)
        $startDate = \Carbon\Carbon::parse($event->start_date);
        $endDate   = \Carbon\Carbon::parse($event->end_date)->endOfDay(); // Asumsi end_date sampai akhir hari

        if ($now->lessThan($startDate)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event belum dimulai. Dimulai pada: ' . $startDate->translatedFormat('d F Y')
            ], 400);
        }

        if ($now->greaterThan($endDate)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event sudah berakhir pada: ' . $endDate->translatedFormat('d F Y')
            ], 400);
        }

        // 4. Cek Expired Token (Backup check)
        if ($qrToken->expired_at && $now->greaterThan($qrToken->expired_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token sudah kedaluwarsa.'
            ], 400);
        }

        // 5. Cek Absensi Hari Ini (Daily Check-in)
        $attendedToday = Attendance::where('event_participant_id', $qrToken->event_participant_id)
            ->where('attendance_date', now()->toDateString())
            ->exists();

        if ($attendedToday) {
            return response()->json([
                'status' => 'warning',
                'message' => "Peserta $participant->name SUDAH check-in hari ini (" . now()->format('H:i') . ")."
            ], 200);
        }

        // 6. Catat Kehadiran
        DB::transaction(function () use ($qrToken) {
            Attendance::create([
                'event_participant_id' => $qrToken->event_participant_id,
                'attendance_date'      => now()->toDateString(),
                'checkin_time'         => now(),
            ]);
        });
        
        return response()->json([
            'status' => 'success',
            'message' => "Hadir! $participant->name berhasil check-in ($event->name)."
        ], 200);
    }
}

