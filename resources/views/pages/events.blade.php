@extends('layouts.guest')

@section('title', 'Event Aktif - ' . setting('app_name'))

@section('content')
<div class="w-full max-w-6xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Daftar Event Aktif</h2>
        <p class="text-gray-500 mb-6 text-sm">Temukan dan daftar event yang diselenggarakan saat ini.</p>
        
        <form method="GET" action="{{ route('events.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-input id="search" name="search" label="Cari Nama/Lokasi" type="text" :value="request('search')" placeholder="Ketik kata kunci..." />
            </div>
            <div class="space-y-2">
                <label for="organizer" class="text-sm font-medium text-gray-700">Penyelenggara :</label>
                <select id="organizer" name="organizer" class="w-full rounded-lg border border-gray-300 focus:ring-orange-400 focus:border-orange-400 p-2 text-sm">
                    <option value="">Semua Penyelenggara</option>
                    @foreach($organizers as $org)
                        <option value="{{ $org->id }}" {{ request('organizer') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <x-input id="start_date" name="start_date" label="Tgl Mulai" type="date" :value="request('start_date')" />
                <x-input id="end_date" name="end_date" label="Tgl Selesai" type="date" :value="request('end_date')" />
            </div>
            <div class="flex items-end gap-2 pb-1">
                <div class="flex-1 flex items-center h-10 px-3 bg-gray-50 border border-gray-300 rounded-lg">
                    <input type="checkbox" id="has_certificate" name="has_certificate" value="1" class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500" {{ request('has_certificate') ? 'checked' : '' }}>
                    <label for="has_certificate" class="ml-2 text-sm text-gray-700 select-none">Ada Sertifikat</label>
                </div>
                <x-button type="submit" variant="primary" class="h-10 px-6">
                    <span class="material-icons">search</span>
                </x-button>
                <a href="{{ route('events.index') }}" class="h-10 px-4 inline-flex items-center justify-center bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    @if($events->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($events as $event)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:border-red-400 cursor-pointer transition group flex flex-col relative overflow-hidden">
                <a href="{{ route('event.register', $event->slug) }}" class="absolute inset-0 z-10"></a>
                
                <div class="p-4 flex flex-col h-full">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($event->type === 'online')
                                @if(now() < $event->start_date)
                                    <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-1 rounded uppercase">Dibuka Saat Acara</span>
                                @else
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded uppercase">Absensi Berjalan</span>
                                @endif
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded uppercase">Online</span>
                            @elseif($event->type === 'hybrid')
                                @if(now() < $event->start_date)
                                    <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-1 rounded uppercase">Pilih Mode</span>
                                @else
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded uppercase">Absensi / Registrasi</span>
                                @endif
                                <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-1 rounded uppercase">Hybrid</span>
                            @else
                                <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded uppercase">Registrasi</span>
                                <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded uppercase">Offline</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col items-center text-center mb-4">
                        <div class="w-20 h-20 rounded-xl bg-gray-50 flex-shrink-0 flex items-center justify-center overflow-hidden border border-gray-100 mb-3 shadow-sm">
                            @if($event->logo)
                                <img src="{{ $event->logo_url }}" alt="{{ $event->name }}" class="w-full h-full object-contain p-2">
                            @else
                                <span class="material-icons text-gray-300 text-4xl">event</span>
                            @endif
                        </div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-red-600 line-clamp-2 leading-tight">
                            {{ $event->name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Oleh: {{ $event->organizer->name ?? '-' }}</p>
                    </div>

                    <div class="mt-auto space-y-2 pt-4 border-t border-gray-100 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-gray-400 text-[18px]">calendar_today</span>
                            {{ \Carbon\Carbon::parse($event->start_date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-icons text-gray-400 text-[18px]">location_on</span>
                            <span class="truncate">{{ $event->location ?? 'Online' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-6 flex justify-center">
            {{ $events->links() }}
        </div>
    @else
        <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
            <div class="w-20 h-20 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons text-4xl">search_off</span>
            </div>
            <h3 class="text-lg font-bold text-gray-700">Tidak Ada Event Ditemukan</h3>
            <p class="text-gray-500 mt-2">Coba sesuaikan filter pencarian Anda atau saat ini memang tidak ada event yang aktif.</p>
        </div>
    @endif
</div>
@endsection
