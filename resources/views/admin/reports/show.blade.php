@extends('layouts.app')

@section('title', 'Laporan Absensi')
@section('page-desc', 'Rekap kehadiran peserta untuk event: ' . $event->name)

@section('content')

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex gap-2">
        <x-link-button href="{{ route('admin.events.index') }}" variant="secondary" icon="arrow_back" title="Kembali" />
        <x-link-button href="{{ route('admin.reports.export', $event) }}" variant="success" icon="file_download" title="Export Excel" />
        <x-link-button href="{{ route('admin.reports.print', $event) }}" variant="dark" icon="print" target="_blank" title="Cetak PDF" />
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-4 rounded shadow border-l-4 border-orange-500">
        <div class="text-gray-500 text-sm">Total Peserta</div>
        <div class="text-2xl font-bold">{{ $totalParticipants }}</div>
    </div>
    <div class="bg-white p-4 rounded shadow border-l-4 border-green-500">
        <div class="text-gray-500 text-sm">Hadir (Minimal 1x)</div>
        <div class="text-2xl font-bold">{{ $presentCount }}</div>
    </div>
    <div class="bg-white p-4 rounded shadow border-l-4 border-red-500">
        <div class="text-gray-500 text-sm">Tidak Hadir</div>
        <div class="text-2xl font-bold">{{ $absentCount }}</div>
    </div>
</div>

<div class="bg-white rounded shadow overflow-x-auto p-4">
    <table class="w-full text-sm datatable">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left">Nama Peserta</th>
                <th class="px-4 py-3 text-left">Tipe</th>
                @if($event->type === 'hybrid')
                    <th class="px-4 py-3 text-center">Mode Kehadiran</th>
                @endif
                @foreach($dates as $date)
                    <th class="px-4 py-3 text-center whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($date)->format('d M') }}
                    </th>
                @endforeach
                <th class="px-4 py-3 text-center">Status Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $ep)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $ep->participant?->name ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">{{ $ep->participant?->email ?? 'Unknown' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <x-badge variant="info">{{ $ep->participantType?->name ?? '-' }}</x-badge>
                    </td>
                    
                    @if($event->type === 'hybrid')
                        <td class="px-4 py-3 text-center">
                            {{ $ep->attendance_mode ? ucfirst($ep->attendance_mode) : '-' }}
                        </td>
                    @endif

                    @foreach($dates as $date)
                        @php
                            // Check attendance for this date
                            $attn = $ep->attendances->first(function($a) use ($date) {
                                return $a->attendance_date == $date;
                            });
                        @endphp
                        <td class="px-4 py-3 text-center">
                            @if($attn)
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold" title="{{ \Carbon\Carbon::parse($attn->checkin_time)->format('H:i') }}">
                                    &#10003; {{ \Carbon\Carbon::parse($attn->checkin_time)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                    @endforeach

                    <td class="px-4 py-3 text-center">
                        @if($ep->attendances->isNotEmpty())
                            <span class="text-green-600 font-bold">Hadir</span>
                        @else
                            <span class="text-red-500">Tidak Hadir</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dates) + ($event->type === 'hybrid' ? 4 : 3) }}" class="px-4 py-6 text-center text-gray-500">
                        Belum ada peserta terdaftar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


@endsection
