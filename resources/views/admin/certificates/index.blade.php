@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kelola Sertifikat: {{ $event->name }}</h1>
        <x-link-button href="{{ route('admin.certificates.list') }}" variant="secondary" icon="arrow_back">
            Kembali ke Event
        </x-link-button>
    </div>

    {{-- Error Validation Alert handled by x-swal mostly, but inline validation errors on fields are good --}}

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Left Column: Settings -->
        <div class="space-y-6">
            <!-- 1. General Settings -->
            <x-card title="Pengaturan Umum" color="primary">
                <form action="{{ route('admin.events.certificates.update', $event) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="has_certificate" value="1" class="sr-only peer" {{ $event->has_certificate ? 'checked' : '' }}>
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900">Aktifkan Sertifikat</span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Template Background (Wajib)</label>
                        <input type="file" name="template_image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        @if($event->template && $event->template->template_image)
                            <div class="mt-2">
                                <p class="text-xs text-green-600 mb-1">Terupload saat ini:</p>
                                {{-- Check if path starts with assets (new) or assumes storage (old) --}}
                                @php
                                    $imagePath = $event->template->template_image;
                                    $imageUrl = Str::startsWith($imagePath, 'assets') ? asset($imagePath) : asset('storage/' . $imagePath);
                                @endphp
                                <img src="{{ $imageUrl }}" class="h-32 object-cover border rounded">
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_event_logo" value="1" class="rounded text-orange-600 focus:ring-orange-500" {{ ($event->template && $event->template->use_event_logo) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Tampilkan Logo Event di Sertifikat</span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="print_only_name_type" value="1" class="rounded text-orange-600 focus:ring-orange-500" {{ ($event->template && $event->template->print_only_name_type) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Cetak hanya nama dan tipe peserta pada template</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Jika diaktifkan, sistem hanya akan mencetak Nama dan Tipe Peserta. Teks lain seperti "SERTIFIKAT", logo, dan tanda tangan tidak akan dicetak (diasumsikan sudah ada pada gambar template).</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Warna Teks</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" name="text_color" value="{{ $event->template->text_color ?? '#000000' }}" class="h-10 w-14 rounded cursor-pointer border border-gray-300">
                            <span class="text-sm text-gray-500">Pilih warna untuk teks yang akan dicetak pada sertifikat</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-input label="Kota Tanda Tangan (Opsional)" name="signature_city" value="{{ $event->template->signature_city ?? '' }}" placeholder="Contoh: Ambon" />
                        <x-input type="date" label="Tanggal Tanda Tangan" name="signature_date" value="{{ $event->template->signature_date ?? now()->format('Y-m-d') }}" />
                    </div>

                    <h3 class="text-md font-semibold mt-6 mb-2 text-gray-700">Teks Sertifikat per Tipe Peserta</h3>
                    @foreach($event->participantTypes as $type)
                        <div class="mb-3">
                            <x-input label="{{ $type->name }}" name="certificate_texts[{{ $type->id }}]" value="{{ $type->certificate_text }}" placeholder="Contoh: atas partisipasinya sebagai" />
                        </div>
                    @endforeach

                    <div class="mt-6">
                        <x-button type="submit" variant="primary" size="md" class="w-full">
                            Simpan Pengaturan
                        </x-button>
                    </div>
                </form>
            </x-card>

            <!-- 2. Signatures -->
            <x-card title="Tanda Tangan" color="warning"> <!-- warning color matches orange/yellow theme usually -->
                
                <!-- Add New Signature -->
                <form action="{{ route('admin.events.certificates.signature.store', $event) }}" method="POST" enctype="multipart/form-data" class="mb-6 bg-gray-50 p-4 rounded-lg">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <x-input name="name" placeholder="Nama Penandatangan" required />
                        </div>
                        <div>
                             <x-input name="nip" placeholder="NIP (Opsional)" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <x-input name="jabatan" placeholder="Jabatan (e.g. Ketua Panitia)" required />
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs text-gray-500 mb-1">Scan Tanda Tangan (PNG Transparan)</label>
                        <input type="file" name="signature_image" class="block w-full text-xs">
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Urutan</label>
                            <input type="number" name="sort_order" placeholder="Urutan" value="0" class="w-16 border rounded px-2 py-1 text-sm bg-white">
                        </div>
                        <x-button type="submit" variant="success" size="sm">Tambah</x-button>
                    </div>
                </form>

                <!-- List Signatures -->
                <div class="space-y-3">
                    @foreach($event->signatures as $sig)
                        <div class="flex items-center justify-between bg-white border p-3 rounded shadow-sm">
                            <div class="flex items-center space-x-3">
                                @if($sig->signature_image)
                                    <img src="{{ asset('storage/' . $sig->signature_image) }}" class="h-10 w-10 object-contain bg-gray-100 rounded">
                                @else
                                    <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">No Img</div>
                                @endif
                                <div>
                                    <p class="font-bold text-sm">{{ $sig->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $sig->jabatan }}</p>
                                </div>
                            </div>
                            <form action="{{ route('admin.events.certificates.signature.destroy', ['event' => $event, 'signature' => $sig]) }}" method="POST" onsubmit="return confirm('Hapus tanda tangan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold">Hapus</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <!-- Right Column: Reports & Preview -->
        <div class="space-y-6">
            <!-- Reports -->
            <x-card title="Laporan Masalah">
                @if($reports->count() > 0)
                    <div class="mb-4">
                        <x-badge type="danger">{{ $reports->count() }} Pending</x-badge>
                    </div>
                @endif
                
                @forelse($reports as $report)
                    <div class="border rounded-lg p-4 mb-3 bg-red-50 border-red-100">
                        <div class="flex justify-between items-start mb-2">
                             <div>
                                 <h4 class="font-bold text-sm text-gray-800">{{ $report->eventParticipant->participant->name }}</h4>
                                 <p class="text-xs text-gray-500">{{ $report->eventParticipant->participant->email }}</p>
                             </div>
                             <span class="text-xs text-gray-400">{{ $report->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-700 italic mb-3">"{{ $report->message }}"</p>
                        
                        <div class="flex space-x-2">
                            <x-link-button href="{{ route('admin.participants.index', ['search' => $report->eventParticipant->participant->email]) }}" target="_blank" size="xs" variant="info">
                                Edit Peserta
                            </x-link-button>
                            <form action="{{ route('admin.events.certificates.report.resolve', ['event' => $event, 'report' => $report]) }}" method="POST">
                                @csrf
                                <x-button type="submit" size="xs" variant="success">
                                    Tandai Selesai
                                </x-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Tidak ada laporan masalah yang belum diselesaikan.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</div>
<x-swal />
@endsection


