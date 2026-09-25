<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AttendanceQrToken;

class EventParticipantController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $participants = $event->participants()
            ->with([
                'participant' => function($q) { $q->withoutGlobalScope('ownership'); },
                'participantType' => function($q) { $q->withoutGlobalScope('ownership'); },
                'qrToken'
            ])
            ->latest()
            ->get();

        $availableParticipants = Participant::whereDoesntHave('eventParticipants', function($q) use ($event) {
            $q->where('event_id', $event->id);
        })->orderBy('name')->get();

        $participantTypes = $event->participantTypes()->orderBy('name')->get();

        return view('admin.participant.index', compact('event', 'participants', 'availableParticipants', 'participantTypes'));
    }

    public function print(Event $event)
    {
        $participants = $event->participants()
            ->with([
                'participant' => function($q) { $q->withoutGlobalScope('ownership'); },
                'participantType' => function($q) { $q->withoutGlobalScope('ownership'); }
            ])
            ->orderByRaw('(SELECT name FROM participants WHERE participants.id = event_participants.participant_id)')
            ->get();

        return view('admin.participant.print', compact('event', 'participants'));
    }

    public function store(Request $request, Event $event)
    {
        $request->validate([
            'participant_ids'     => 'required|array',
            'participant_ids.*'   => 'exists:participants,id',
            'participant_type_id' => 'required|exists:participant_types,id',
        ]);

        DB::transaction(function () use ($event, $request) {
            foreach ($request->participant_ids as $participantId) {
                // 1. Simpan data registrasi event
                $eventParticipant = EventParticipant::updateOrCreate([
                    'event_id'       => $event->id,
                    'participant_id' => $participantId,
                ], [
                    'participant_type_id' => $request->participant_type_id,
                    'registered_at'       => now(),
                    'registered_via'      => 'admin',
                ]);

                // 2. Generate QR Token jika belum ada
                if (!AttendanceQrToken::where('event_participant_id', $eventParticipant->id)->exists()) {
                    AttendanceQrToken::create([
                        'event_participant_id' => $eventParticipant->id,
                        'token'                => (string) Str::uuid(),
                        'expired_at'           => $event->end_date ? \Carbon\Carbon::parse($event->end_date)->endOfDay() : null,
                    ]);
                }
            }
        });

        return back()->with('success', count($request->participant_ids) . ' Peserta berhasil ditambahkan');
    }

    public function update(Request $request, Event $event, EventParticipant $eventParticipant)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'nullable|email|max:255|unique:participants,email,' . $eventParticipant->participant_id,
            'phone'               => 'nullable|string|max:30|unique:participants,phone,' . $eventParticipant->participant_id,
            'participant_type_id' => 'required|exists:participant_types,id',
        ]);

        $eventParticipant->update([
            'participant_type_id' => $validated['participant_type_id']
        ]);

        $eventParticipant->participant->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        return back()->with('success', 'Data peserta diperbarui');
    }

    public function destroy(Event $event, EventParticipant $eventParticipant)
    {
        $eventParticipant->delete();

        return back()->with('success', 'Peserta dihapus dari event');
    }
}

