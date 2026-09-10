<x-card title="{{ isset($event) ? 'Edit' : 'Tambah' }} Event" color="primary">
    <x-input label="Nama Event" name="name" value="{{ $event->name ?? '' }}" required />
    <x-input
        type="datetime-local"
        label="Waktu Mulai Event"
        name="start_date"
        value="{{ old('start_date', isset($event)
                ? $event->start_date?->format('Y-m-d\TH:i')
                : now()->format('Y-m-d\TH:i')
        ) }}"
        required
    />
    
    <x-input
        type="datetime-local"
        label="Waktu Selesai Event"
        name="end_date"
        value="{{ old('end_date', isset($event)
                ? $event->end_date?->format('Y-m-d\TH:i')
                : now()->addHour()->format('Y-m-d\TH:i')
        ) }}"
        required
    />

    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Pelaksanaan Event</label>
        <select name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
            <option value="offline" {{ old('type', $event->type ?? '') == 'offline' ? 'selected' : '' }}>Offline (Luring) - Menggunakan QR Tiket</option>
            <option value="online" {{ old('type', $event->type ?? '') == 'online' ? 'selected' : '' }}>Online (Daring) - Absensi Langsung di Form</option>
            <option value="hybrid" {{ old('type', $event->type ?? '') == 'hybrid' ? 'selected' : '' }}>Hybrid - Bebas Pilih (Online & Offline)</option>
        </select>
    </div>

    <x-input label="Lokasi" name="location" value="{{ $event->location ?? '' }}" />

    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Event (PNG)</label>
        @if(isset($event) && $event->logo_url)
            <div class="mb-2">
                <img src="{{ $event->logo_url }}" alt="Current Logo" class="h-20 w-auto object-contain border rounded p-1">
                <p class="text-xs text-gray-500 mt-1">Logo saat ini</p>
            </div>
        @endif
        <input 
            type="file" 
            name="logo" 
            accept="image/png"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        >
        <p class="text-xs text-gray-500 mt-1">Hanya file PNG yang diperbolehkan. Maksimal 2MB.</p>
        @error('logo')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4">
        <label class="flex items-center space-x-2 cursor-pointer">
            <input 
                type="checkbox" 
                name="has_certificate" 
                value="1" 
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                {{ old('has_certificate', $event->has_certificate ?? false) ? 'checked' : '' }}
            >
            <span class="text-sm font-medium text-gray-700">Aktifkan Sertifikat Otomatis</span>
        </label>
        <p class="text-xs text-gray-500 mt-1 ml-6">Jika aktif, peserta dapat mengunduh sertifikat setelah event selesai & melakukan absensi.</p>
    </div>
</x-card>
