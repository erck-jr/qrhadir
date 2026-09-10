<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->when(auth()->user()->isSuperAdmin(), fn($q) => $q->with('owner'))
            ->latest()
            ->get();

        $adminEvents = [];
        if (auth()->user()->isSuperAdmin()) {
            $adminEvents = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN_EVENT)->get();
        }

        return view('admin.events.index', compact('events', 'adminEvents'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'required|in:offline,online,hybrid',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'location'   => 'nullable|string|max:255',
            'logo'       => 'nullable|image|mimes:png|max:2048',
            'has_certificate' => 'boolean',
        ]);
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $event = new Event([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name'])
            ]);
            
            $fileName = $event->slug . '_logo.png';
            $destinationPath = public_path('assets/images/event-logo');
            
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $request->file('logo')->move($destinationPath, $fileName);
            $logoPath = $fileName;
        }

        Event::create([
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'location'   => $validated['location'],
            'status'     => 'draft',
            'logo'       => $logoPath,
            'has_certificate' => $validated['has_certificate'] ?? false,
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil dibuat');
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'required|in:offline,online,hybrid',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'location'   => 'nullable|string|max:255',
            'logo'       => 'nullable|image|mimes:png|max:2048',
            'has_certificate' => 'boolean',
        ]);
        
        // Ensure boolean is set if unchecked (Laravel checkbox behavior usually ommitted if unchecked)
        if (!$request->has('has_certificate')) {
             $validated['has_certificate'] = false;
        }

        if ($request->hasFile('logo')) {
            $newSlug = Str::slug($validated['name']);
            $fileName = $newSlug . '_logo.png';
            $destinationPath = public_path('assets/images/event-logo');
            
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Delete old logo if exists
            if ($event->logo && File::exists($destinationPath . '/' . $event->logo)) {
                File::delete($destinationPath . '/' . $event->logo);
            }
            
            $request->file('logo')->move($destinationPath, $fileName);
            $validated['logo'] = $fileName;
        }

        $event->update($validated);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil diperbarui');
    }

    public function setStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,active,closed'
        ]);
        $status = $request->status;
        if ($status === 'active' && !$event->participantTypes()->exists()) {
            return back()->with('error', 'Event tidak dapat diaktifkan karena belum memiliki Tipe Peserta. Silakan tambahkan minimal satu tipe peserta terlebih dahulu.');
        }

        $event->update(['status' => $status]);
        $message = [
            'draft' => 'Event dikembalikan ke draft',
            'active' => 'Event berhasil diaktifkan',
            'closed' => 'Event ditutup'
        ];
        return back()->with('success', $message[$status]);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus beserta seluruh data terkait');
    }

    /**
     * Transfer ownership of event and its participants.
     */
    public function transfer(Request $request, Event $event)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $newOwnerId = $request->user_id;

        \DB::transaction(function() use ($event, $newOwnerId) {
            // 1. Transfer Event
            $event->update(['user_id' => $newOwnerId]);

            // 2. Transfer associated participants
            // Only transfer participants that are registered to THIS event
            $participantIds = $event->eventParticipants()->pluck('participant_id');
            \App\Models\Participant::whereIn('id', $participantIds)
                ->update(['user_id' => $newOwnerId]);
        });

        return back()->with('success', 'Kepemilikan event dan seluruh pesertanya berhasil dipindahkan.');
    }
    public function printQr(Event $event)
    {
        return view('admin.events.print-qr', compact('event'));
    }
}
