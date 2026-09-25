<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Peserta - {{ $event->name }}</title>
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
        <h1 class="text-2xl font-bold uppercase m-0">Daftar Peserta Event</h1>
        <h2 class="text-lg font-normal my-1">{{ $event->name }}</h2>
        <p class="text-md">@tanggalIndo($event->start_date) - @tanggalIndo($event->end_date)</p>
    </div>

    <table class="w-full border-collapse mt-2">
        <thead>
            <tr style="background-color: #eee">
                <th class="border border-black p-2 text-center">No</th>
                <th class="border border-black p-2 text-center">Nama Peserta</th>
                <th class="border border-black p-2 text-center">Email</th>
                <th class="border border-black p-2 text-center">No HP</th>
                <th class="border border-black p-2 text-center">Tipe Peserta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $index => $ep)
            <tr>
                <td class="border border-black p-2 text-center">{{ $index + 1 }}</td>
                <td class="border border-black p-2 text-left">{{ $ep->participant?->name ?? 'Unknown' }}</td>
                <td class="border border-black p-2 text-center">{{ $ep->participant?->email ?? '-' }}</td>
                <td class="border border-black p-2 text-center">{{ $ep->participant?->phone ?? '-' }}</td>
                <td class="border border-black p-2 text-center">{{ $ep->participantType?->name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="border border-black p-2 text-center">Belum ada peserta</td>
            </tr>
            @endforelse
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
