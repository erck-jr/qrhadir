<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\EventParticipant;
use App\Models\AttendanceQrToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventRegistrationService
{
    /**
     * Daftarkan peserta ke event dan hasilkan QR Token.
     *
     * @param Event $event
     * @param array $validatedData
     * @return string $tokenUuid
     */
    public function registerParticipant(Event $event, array $validatedData)
    {
        $tokenUuid = null;

        DB::transaction(function () use ($event, $validatedData, &$tokenUuid) {
            // 1. Cari/Buat Participant (Proteksi Duplikat Email/Phone)
            $participant = Participant::where('email', $validatedData['email'])
                ->orWhere('phone', $validatedData['phone'])
                ->first();

            if ($participant) {
                // Update data jika sudah ada
                $participant->update([
                    'name'  => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'phone' => $validatedData['phone'],
                ]);
            } else {
                $participant = Participant::create([
                    'name'  => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'phone' => $validatedData['phone'],
                ]);
            }

            // 2. Daftarkan di EventParticipant
            $eventParticipant = EventParticipant::updateOrCreate(
                [
                    'event_id'       => $event->id,
                    'participant_id' => $participant->id,
                ],
                [
                    'participant_type_id' => $validatedData['participant_type_id'],
                    'registered_via'      => 'self',
                    'attendance_mode'     => $validatedData['mode'] ?? null,
                    'registered_at'       => now(),
                ]
            );

            // 3. Generate QR Token
            $qrToken = AttendanceQrToken::where('event_participant_id', $eventParticipant->id)->first();

            if (!$qrToken) {
                $qrToken = AttendanceQrToken::create([
                    'event_participant_id' => $eventParticipant->id,
                    'token'                => (string) Str::uuid(),
                    'expired_at'           => $event->end_date ? \Carbon\Carbon::parse($event->end_date)->endOfDay() : null,
                ]);
            }
            
            $tokenUuid = $qrToken->token;

            // 4. Jika event bertipe online atau hybrid (mode online), langsung set ke hadir (Attendance)
            if ($event->type === 'online' || ($event->type === 'hybrid' && isset($validatedData['mode']) && $validatedData['mode'] === 'online')) {
                \App\Models\Attendance::firstOrCreate([
                    'event_participant_id' => $eventParticipant->id,
                    'attendance_date'      => now()->toDateString(),
                ], [
                    'checkin_time' => now(),
                ]);
            }
        });

        return $tokenUuid;
    }
}
