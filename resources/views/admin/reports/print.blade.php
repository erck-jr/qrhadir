<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi - {{ $event->name }}</title>
    <link rel="icon" type="image/png" href="@cachebust(setting('app_favicon'))">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()" class="font-sans text-[12px]">

    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <x-button onclick="window.close()" variant="dark" size="sm">Tutup</x-button>
    </div>

    <div class="text-center mb-5">
        @if($event->logo_url)
            <div class="mb-4">
                <img src="{{ $event->logo_url }}" alt="{{ $event->name }}" class="h-16 mx-auto object-contain">
            </div>
        @endif
        <h1 class="text-2xl font-bold uppercase m-0">Laporan Absensi Event ({{ setting('app_name') }})</h1>
        <h2 class="text-lg font-normal my-1">{{ $event->name }}</h2>
        <p class="text-md">@tanggalIndo($event->start_date) - @tanggalIndo($event->end_date)</p>
    </div>

    <table class="w-full border-collapse mt-2">
        <thead>
            <tr style="background-color: #eee">
                <th class="border border-black p-1 text-center">No</th>
                <th class="border border-black p-1 text-center">Nama Peserta</th>
                <th class="border border-black p-1 text-center">Tipe</th>
                @if($event->type === 'hybrid')
                    <th class="border border-black p-1 text-center">Mode Kehadiran</th>
                @endif
                @foreach($dates as $date)
                    <th class="border border-black p-1 text-center">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</th>
                @endforeach
                <th class="border border-black p-1 text-center" style="width: 80px;">Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($participants as $index => $ep)
            <tr>
                <td class="border border-black p-1 text-center">{{ $index + 1 }}</td>
                <td class="border border-black p-1 text-left">{{ $ep->participant?->name ?? 'Unknown' }}</td>
                <td class="border border-black p-1 text-center">{{ $ep->participantType?->name ?? '-' }}</td>
                @if($event->type === 'hybrid')
                    <td class="border border-black p-1 text-center">{{ $ep->attendance_mode ? ucfirst($ep->attendance_mode) : '-' }}</td>
                @endif
                @php $present = false; @endphp
                @foreach($dates as $date)
                    @php
                        $attn = $ep->attendances->first(function($a) use ($date) {
                            return $a->attendance_date == $date;
                        });
                        if($attn) $present = true;
                    @endphp
                    <td class="border border-black p-1 text-center">
                        {{ $attn ? \Carbon\Carbon::parse($attn->checkin_time)->format('H:i') : '' }}
                    </td>
                @endforeach
                <td class="border border-black p-1 text-center">{{ $present ? 'Hadir' : 'Alfa' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex flex-col items-center justify-center mt-8">
        <div class="flex justify-center">
            {!! QrCode::size(120)->generate(url()->current()) !!}
        </div>
    
        <div class="mt-4 text-center">
            Dicetak pada: @tanggalWaktuIndo(now()) - oleh : {{ auth('admin')->user()->name }} <br>
            Scan QR-Code untuk menuju URL Halaman : <span class="italic">{{ url()->current() }}</span>
        </div>
    </div>


</body>
</html>
