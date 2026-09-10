<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\EventParticipant;
use App\Models\AttendanceQrToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventRegistrationController extends Controller
{
    
    /**
     * Tampilkan form registrasi untuk public
     */
    /**
     * Cek apakah peserta sudah terdaftar
     */
    public function checkParticipant(Request $request, Event $event)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->identifier;
        $type = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $participant = Participant::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$participant) {
            return response()->json([
                'status' => 'new',
                'type' => $type,
                'value' => $identifier
            ]);
        }

        // Cek apakah sudah terdaftar di event ini
        $isRegistered = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->exists();

        if ($isRegistered) {
            $eventParticipant = EventParticipant::where('event_id', $event->id)
                ->where('participant_id', $participant->id)
                ->first();
            
            $qrToken = AttendanceQrToken::where('event_participant_id', $eventParticipant->id)->first();
            
            return response()->json([
                'status' => 'registered',
                'participant' => $participant,
                'redirect_url' => $qrToken ? route('event.ticket', ['event' => $event->slug, 'qrToken' => $qrToken->token]) : null
            ]);
        }

        return response()->json([
            'status' => 'exists',
            'participant' => $participant,
            'type' => $type,
            'value' => $identifier
        ]);
    }

    /**
     * Tampilkan form registrasi untuk public
     */
    public function show(Request $request, Event $event)
    {
        $registrationStatus = 'active';
        $mode = $request->query('mode');

        if ($event->type === 'online' || ($event->type === 'hybrid' && $mode === 'online')) {
            if (now() < $event->start_date) {
                $registrationStatus = 'not_started';
            } elseif (now() > $event->end_date) {
                $registrationStatus = 'ended';
            }
        }

        $participantTypes = $event->participantTypes()->orderBy('name')->get();

        return view('pages.events.register', compact('event', 'participantTypes', 'registrationStatus', 'mode'));
    }

    /**
     * Proses registrasi public
     */
    public function store(Request $request, Event $event, \App\Services\EventRegistrationService $registrationService)
    {
        if ($event->status !== 'active') {
            abort(404); // Atau redirect error
        }

        $rules = [
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'phone'               => 'required|string|max:20',
            'participant_type_id' => 'required|exists:participant_types,id',
        ];

        if ($event->type === 'hybrid') {
            $rules['mode'] = 'required|in:online,offline';
        }

        $validated = $request->validate($rules);

        $tokenUuid = $registrationService->registerParticipant($event, $validated);

        if ($event->type === 'online' || ($event->type === 'hybrid' && $validated['mode'] === 'online')) {
            return redirect()->route('event.attendance.success', ['event' => $event->slug, 'qrToken' => $tokenUuid])
                             ->with('success', 'Absensi berhasil dicatat.');
        }

        // Redirect ke halaman tiket
        return redirect()->route('event.ticket', ['event' => $event->slug, 'qrToken' => $tokenUuid])
                         ->with('success', 'Registrasi Berhasil! Simpan QR Code ini.');
    }

    public function attendanceSuccess(Event $event, AttendanceQrToken $qrToken)
    {
        if ($qrToken->eventParticipant->event_id != $event->id || $event->type !== 'online') {
            abort(404);
        }

        $idCardTemplate = \App\Models\IdCardTemplate::where('is_active', true)->where('event_id', $event->id)->first();
        // Fallback ke template global jika tidak ada
        if (!$idCardTemplate) {
            $idCardTemplate = \App\Models\IdCardTemplate::where('is_active', true)->whereNull('event_id')->first();
        }

        return view('pages.events.attendance-success', compact('event', 'qrToken', 'idCardTemplate'));
    }

    /**
     * Tampilkan Tiket / QR Code
     */
    public function ticket(Event $event, AttendanceQrToken $qrToken)
    {
        // Pastikan token ini milik event yang benar
        if ($qrToken->eventParticipant->event_id != $event->id) {
            abort(404);
        }

        $idCardTemplate = \App\Models\IdCardTemplate::where('is_active', true)->first();

        return view('pages.events.ticket', compact('event', 'qrToken', 'idCardTemplate'));
    }

    /**
     * Halaman ID Card yang menampilkan gambar hasil generate
     */
    public function idCard(Event $event, AttendanceQrToken $qrToken)
    {
        if ($qrToken->eventParticipant->event_id != $event->id) {
            abort(404);
        }

        $token = $qrToken->token;
        $fileName = "card_{$event->id}_{$token}.png";
        $filePath = "assets/images/generated-card/{$event->id}/{$fileName}";
        $isGenerated = file_exists(public_path($filePath));

        return view('pages.events.id-card', compact('event', 'qrToken', 'isGenerated', 'filePath'));
    }

    /**
     * Public generate ID Card
     */
    public function generateIdCard(Event $event, AttendanceQrToken $qrToken, \App\Services\IdCardService $idCardService)
    {
        if ($qrToken->eventParticipant->event_id != $event->id) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid untuk event ini'], 403);
        }

        try {
            $idCardService->generateAndSave($qrToken->eventParticipant);
            return response()->json(['success' => true, 'message' => 'Berhasil generate idcard']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cari tiket peserta berdasarkan email/phone
     */
    public function checkTickets(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->identifier;

        $participant = Participant::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$participant) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Email atau Nomor HP belum terdaftar di sistem kami.'
            ], 404);
        }

        // Ambil event yang diikuti peserta
        // Syarat: Event Active & sedang berlangsung (now >= start_date dan now <= end_date)
        $registrations = EventParticipant::with(['event', 'qrToken'])
            ->where('participant_id', $participant->id)
            ->whereHas('event', function($q) {
                $now = now();
                $q->where('status', 'active')
                  ->where('start_date', '<=', $now)
                  ->where('end_date', '>=', $now);
            })
            ->get()
            ->map(function($reg) {
                return [
                    'event_name' => $reg->event->name,
                    'event_date' => $reg->event->start_date->translatedFormat('d M Y, H:i'),
                    'ticket_url' => $reg->qrToken ? route('event.ticket', ['event' => $reg->event->slug, 'qrToken' => $reg->qrToken->token]) : null
                ];
            });

        if ($registrations->isEmpty()) {
            return response()->json([
                'status'  => 'empty',
                'message' => 'Anda belum terdaftar di event aktif yang akan datang.'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'participant' => $participant->name,
            'data' => $registrations
        ]);
    }
}
