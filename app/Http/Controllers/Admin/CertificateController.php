<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\Signature;
use App\Models\CertificateReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /**
     * Show the certificate management dashboard for an event.
     */
    /**
     * List all events that have certificates enabled.
     */
    public function listEvents()
    {
        $events = Event::where('has_certificate', true)
            ->withCount(['participantTypes', 'signatures'])
            ->withCount(['certificateReports as pending_reports_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->latest()
            ->get();

        return view('admin.certificates.list_events', compact('events'));
    }

    /**
     * Show the certificate management dashboard for an event.
     */
    public function index(Event $event)
    {
        $event->load(['template', 'signatures', 'participantTypes']);
        $reports = CertificateReport::whereHas('eventParticipant', function($q) use ($event) {
            $q->where('event_id', $event->id);
        })->where('status', 'pending')->get();

        // Get available fonts
        $fonts = [];
        $fontsDir = public_path('assets/fonts');
        if (is_dir($fontsDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fontsDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'ttf') {
                    // Get path relative to assets/fonts
                    $relativePath = str_replace($fontsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath); // normalize slashes
                    $fonts[] = $relativePath;
                }
            }
        }
        sort($fonts);

        return view('admin.certificates.index', compact('event', 'reports', 'fonts'));
    }

    /**
     * Update certificate settings (Template, Text, etc).
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'has_certificate' => 'boolean',
            'template_image' => 'nullable|image|max:2048',
            'use_event_logo' => 'boolean',
            'signature_city' => 'nullable|string|max:100',
            'signature_date' => 'nullable|date',
            'certificate_texts' => 'array', 
            'print_only_name_type' => 'boolean',
            'text_color' => 'nullable|string|max:20',
            'font' => 'nullable|string',
        ]);

        // Toggle Status
        $event->update([
            'has_certificate' => $request->has('has_certificate')
        ]);

        // Update Template
        if ($request->hasFile('template_image')) {
            // New logic: Store in public/assets/images/sertifikat/
            $file = $request->file('template_image');
            $filename = 'template_' . $event->slug . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('assets/images/sertifikat');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            
            // Delete old file if exists and not default or empty
            if ($event->template && $event->template->template_image) {
                // Check if it's a file in the new path or old path and delete
                $oldPath = public_path('assets/images/sertifikat/' . basename($event->template->template_image));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                } else {
                    // Fallback to delete from storage/app/public if it was there
                     Storage::disk('public')->delete($event->template->template_image);
                }
            }

            $event->template()->updateOrCreate(
                ['event_id' => $event->id],
                [
                    'template_image' => 'assets/images/sertifikat/' . $filename,
                    'use_event_logo' => $request->has('use_event_logo'),
                    'signature_city' => $request->signature_city,
                    'signature_date' => $request->signature_date,
                    'print_only_name_type' => $request->has('print_only_name_type'),
                    'text_color' => $request->text_color ?? '#000000',
                    'font' => $request->font ?? 'Nunito/Nunito-Bold.ttf'
                ] 
            );
        } else {
            // No new file, just update settings
            $event->template()->updateOrCreate(
                ['event_id' => $event->id],
                [
                    'use_event_logo' => $request->has('use_event_logo'),
                    'signature_city' => $request->signature_city,
                    'signature_date' => $request->signature_date,
                    'print_only_name_type' => $request->has('print_only_name_type'),
                    'text_color' => $request->text_color ?? '#000000',
                    'font' => $request->font ?? 'Nunito/Nunito-Bold.ttf'
                ]
            );
        }

        // Update Participant Types Texts
        if ($request->has('certificate_texts')) {
            foreach ($request->certificate_texts as $typeId => $text) {
                $event->participantTypes()->where('id', $typeId)->update(['certificate_text' => $text]);
            }
        }

        return back()->with('success', 'Pengaturan sertifikat berhasil disimpan.');
    }

    /**
     * Add a signature.
     */
    public function storeSignature(Request $request, Event $event)
    {
        $request->validate([
            'name' => 'required|string',
            'jabatan' => 'required|string',
            'nip' => 'nullable|string',
            'signature_image' => 'nullable|image|max:2048',
            'sort_order' => 'integer',
        ]);

        $path = null;
        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('certificates/signatures', 'public');
        }

        $event->signatures()->create([
            'name' => $request->name,
            'jabatan' => $request->jabatan,
            'nip' => $request->nip,
            'signature_image' => $path,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Tanda tangan berhasil ditambahkan.');
    }

    /**
     * Delete a signature.
     */
    public function destroySignature(Event $event, Signature $signature)
    {
        if ($signature->signature_image) {
            Storage::disk('public')->delete($signature->signature_image);
        }
        $signature->delete();
        return back()->with('success', 'Tanda tangan dihapus.');
    }

    /**
     * Resolve a certificate report.
     */
    public function resolveReport(Request $request, Event $event, CertificateReport $report)
    {
        // Maybe update participant data here if requested?
        $report->update(['status' => 'resolved']);
        return back()->with('success', 'Laporan ditandai selesai.');
    }
}
