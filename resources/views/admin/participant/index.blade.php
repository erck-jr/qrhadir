@extends('layouts.app')

@section('title', 'Peserta Event')
@section('page-desc', $event->name)

@section('content')

<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex gap-2">
        <x-link-button href="{{ route('admin.events.index') }}" variant="secondary" size="sm">
            ← Kembali
        </x-link-button>

    </div>

    <div class="flex flex-wrap gap-2">
        <form action="{{ route('admin.events.id-cards.generate-batch', $event) }}" method="POST" class="inline">
            @csrf
            <x-button type="submit" variant="info" size="sm" onclick="return confirm('Mulai proses generate massal di background?')">
                <x-icon name="sync" class="mr-1"/> Generate Semua ID Card
            </x-button>
        </form>
        <a href="{{ route('admin.events.id-cards.download-batch', $event) }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <x-icon name="download" class="mr-1"/> Download Semua (.zip)
        </a>
        <a href="{{ route('admin.events.participants.print', $event) }}" target="_blank" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
            <x-icon name="print" class="mr-1"/> Cetak Daftar Peserta
        </a>
        <x-button
            data-modal-target="participantModal"
            data-modal-toggle="participantModal"
            onclick="openCreateModal()" variant="primary" size="sm">
            + Tambah Peserta
        </x-button>
    </div>
</div>

<div class="bg-white rounded shadow overflow-x-auto p-4">
    <table class="w-full text-sm datatable">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Tipe</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">No HP</th>
                <th class="px-4 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $row)
                <tr class="border-t">
                    <td class="px-4 py-3">
                        <div class="font-bold">{{ $row->participant->name }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <x-badge variant="info">{{ $row->participantType->name ?? '-' }}</x-badge>
                    </td>
                    <td class="px-4 py-3">{{ $row->participant->email }}</td>
                    <td class="px-4 py-3">{{ $row->participant->phone }}</td>
                    <td class="px-4 py-3 space-x-2">
                        {{-- QR Button --}}
                        @if($row->qrToken)
                            <x-link-button 
                                href="{{ route('event.ticket', ['event' => $event->slug, 'qrToken' => $row->qrToken->token]) }}" 
                                variant="dark" 
                                size="sm" 
                                target="_blank"
                                title="Lihat QR Code">
                                <x-icon name="qr_code_2" />
                            </x-link-button>

                            <x-button size="sm" variant="info" icon="edit" title="Edit Peserta"
                                onclick="openEditModal(this)"
                                data-modal-target="participantModal"
                                data-modal-toggle="participantModal"
                                data-action="{{ route('admin.events.participants.update', [$event, $row]) }}"
                                data-name="{{ $row->participant->name }}"
                                data-email="{{ $row->participant->email }}"
                                data-phone="{{ $row->participant->phone }}"
                                data-type-id="{{ $row->participant_type_id }}" />
                        @else
                            <span class="text-xs text-red-500">No Token</span>
                        @endif

                        <form method="POST"
                              action="{{ route('admin.events.participants.destroy', [$event, $row]) }}"
                              class="inline"
                              onsubmit="return confirmDelete(this)">
                            @csrf @method('DELETE')
                            <x-button size="sm" type="submit" variant="danger" icon="delete" title="Hapus dari Event" />
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                        Belum ada peserta
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- MODAL FORM --}}
<x-modal id="participantModal" title="Form Peserta">
    <form id="participantForm" method="POST">
        @csrf
        <div id="methodField"></div>

        {{-- Mode ADD: Select Multiple --}}
        <div id="addSection" class="hidden">
            <div class="mb-4 text-xs text-blue-800 bg-blue-50 p-3 rounded border border-blue-200 flex gap-2 items-start">
                <span class="material-icons text-sm mt-0.5">info</span>
                <div>
                    Jika peserta belum ada dalam list, silakan daftarkan terlebih dahulu di menu 
                    <a href="{{ route('admin.participants.index') }}" class="font-bold underline" target="_blank">Data Peserta</a> 
                    atau via Portal Registrasi.
                </div>
            </div>

            <div class="relative mb-2">
                <input type="text" id="searchParticipant" placeholder="Cari nama / email..." 
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <span class="material-icons absolute right-3 top-2 text-gray-400 text-sm">search</span>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Tipe Partisipan</label>
                <select name="participant_type_id" id="inputTypeAdd" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($participantTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="max-h-60 overflow-y-auto border rounded-lg bg-gray-50 p-2 space-y-1" id="participantList">
                @if(isset($availableParticipants) && $availableParticipants->count() > 0)
                    @foreach($availableParticipants as $p)
                        <label class="flex items-start space-x-3 p-2 hover:bg-white rounded-md cursor-pointer transition participant-item border border-transparent hover:border-gray-200">
                            <input type="checkbox" name="participant_ids[]" value="{{ $p->id }}" class="mt-1 rounded text-orange-600 focus:ring-orange-500">
                            <div>
                                <div class="font-bold text-gray-800 text-sm search-text">{{ $p->name }}</div>
                                <div class="text-xs text-gray-500 search-sub">{{ $p->email }} | {{ $p->phone }}</div>
                            </div>
                        </label>
                    @endforeach
                @else
                    <div class="text-center text-gray-400 py-6 text-sm">
                        <span class="material-icons text-3xl mb-1">groups</span><br>
                        Tidak ada peserta tersedia untuk ditambahkan.<br>
                        (Semua peserta sudah terdaftar atau Database kosong)
                    </div>
                @endif
            </div>
            <div class="text-xs text-gray-400 mt-2 text-right">Terpilih: <span id="selectedCount">0</span></div>
        </div>

        {{-- Mode EDIT: Input Fields --}}
        <div id="editSection" class="hidden space-y-4">
            <div class="text-sm font-bold text-gray-700 border-b pb-2 mb-4">Edit Data Peserta (Master)</div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                <input type="text" name="name" id="inputName" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" id="inputEmail" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">No HP / WhatsApp</label>
                <input type="text" name="phone" id="inputPhone" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Tipe Partisipan</label>
                <select name="participant_type_id" id="inputTypeEdit" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($participantTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-2">
            <x-button type="button" data-modal-hide="participantModal" variant="secondary" size="sm">Batal</x-button>
            <x-button type="submit" variant="primary" size="sm">Simpan</x-button>
        </div>
    </form>
</x-modal>

@push('scripts')
    <x-swal-del-confirm data="Peserta" message="Akan menghapus peserta dari event {{ $event->name }}"/>
    <script>
        const modal = document.getElementById('participantModal');
        const form = document.getElementById('participantForm');
        const methodField = document.getElementById('methodField');
        
        const addSection = document.getElementById('addSection');
        const editSection = document.getElementById('editSection');
        const modalTitle = document.querySelector('#participantModal h3') || document.getElementById('participantModal').querySelector('.font-bold'); // Adjust based on x-modal structure if needed, but assuming title prop

        // Add Logic
        const searchInput = document.getElementById('searchParticipant');
        const participantItems = document.querySelectorAll('.participant-item');
        const checkboxes = document.querySelectorAll('input[name="participant_ids[]"]');
        const selectedCount = document.getElementById('selectedCount');

        // Search Filter
        searchInput?.addEventListener('keyup', function() {
            const val = this.value.toLowerCase();
            participantItems.forEach(item => {
                const text = item.querySelector('.search-text').innerText.toLowerCase();
                const sub = item.querySelector('.search-sub').innerText.toLowerCase();
                if(text.includes(val) || sub.includes(val)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });

        // Count
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const count = document.querySelectorAll('input[name="participant_ids[]"]:checked').length;
                if(selectedCount) selectedCount.innerText = count;
            });
        });

        // Edit Inputs
        const inputName = document.getElementById('inputName');
        const inputEmail = document.getElementById('inputEmail');
        const inputPhone = document.getElementById('inputPhone');

        function openCreateModal() {
            // Setup Form for ADD
            form.action = "{{ route('admin.events.participants.store', $event) }}";
            methodField.innerHTML = ''; 
            form.reset();
            
            // Toggle Sections
            addSection.classList.remove('hidden');
            editSection.classList.add('hidden');
            
            // Enable/Disable inputs to avoid validation errors
            toggleInputs(false); // Disable edit inputs

        }

        function openEditModal(btn) {
            // Setup Form for EDIT
            const { action, name, email, phone } = btn.dataset;

            form.action = action;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            // Fill Values
            inputName.value = name;
            inputEmail.value = email || '';
            inputPhone.value = phone || '';
            document.getElementById('inputTypeEdit').value = btn.dataset.typeId || '';

            // Toggle Sections
            addSection.classList.add('hidden');
            editSection.classList.remove('hidden');
            
            // Enable inputs
            toggleInputs(true); // Enable edit inputs

        }

        function toggleInputs(isEdit) {
            // Edit Section Inputs
            inputName.disabled = !isEdit;
            inputName.required = isEdit;
            document.getElementById('inputTypeEdit').disabled = !isEdit;
            document.getElementById('inputTypeEdit').required = isEdit;

            // Add Section Inputs
            const typeAdd = document.getElementById('inputTypeAdd');
            if (typeAdd) {
                typeAdd.disabled = isEdit;
                typeAdd.required = !isEdit;
            }

            const pIds = document.querySelectorAll('input[name="participant_ids[]"]');
            pIds.forEach(cb => {
                cb.disabled = isEdit;
                // No required here, usually checkboxes are validated by at least one selected or by backend
            });
        }
    </script>
@endpush

@endsection
