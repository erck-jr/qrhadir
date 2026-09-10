@extends('layouts.guest')

@section('title', 'Registrasi Event - ' . $event->name)

@section('content')
<div x-data="eventRegistration()" class="w-full max-w-md bg-white p-6 rounded-xl shadow-lg border border-gray-100">
    <div class="mb-4">
        <a href="{{ route('home') }}" class="inline-flex items-center text-gray-500 hover:text-orange-600 transition text-sm">
            <span class="material-icons text-base mr-1">arrow_back</span>
            Kembali
        </a>
    </div>

    <div class="text-center mb-6">
        @if($event->logo_url)
            <div class="mb-4">
                <img src="{{ $event->logo_url }}" alt="{{ $event->name }}" class="h-24 mx-auto object-contain">
            </div>
        @endif
        <h2 class="text-2xl font-bold text-orange-600">Registrasi Event</h2>
        <p class="text-gray-600 mt-1">{{ $event->name }}</p>
        <p class="text-sm text-gray-400 mt-1">
            <x-icon name="calendar_today" class="align-middle" size="14px" />
            {{ \Carbon\Carbon::parse($event->start_date)->translatedFormat('d F Y') }}
        </p>
    </div>

    @if($event->type === 'hybrid' && empty($mode))
        <div class="text-center py-6">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Silakan Pilih Mode Kehadiran Anda</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ request()->fullUrlWithQuery(['mode' => 'online']) }}" class="flex flex-col items-center p-6 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 hover:border-blue-300 transition cursor-pointer group">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-icons text-blue-500 text-3xl">laptop_mac</span>
                    </div>
                    <span class="font-bold text-blue-800">Hadir Daring (Online)</span>
                    <span class="text-xs text-blue-600 mt-2">Absensi akan otomatis tercatat saat form disubmit pada jam acara.</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['mode' => 'offline']) }}" class="flex flex-col items-center p-6 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 hover:border-green-300 transition cursor-pointer group">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-icons text-green-500 text-3xl">storefront</span>
                    </div>
                    <span class="font-bold text-green-800">Hadir Luring (Offline)</span>
                    <span class="text-xs text-green-600 mt-2">Dapatkan QR Tiket untuk discan panitia di lokasi acara.</span>
                </a>
            </div>
        </div>
    @elseif(isset($registrationStatus) && $registrationStatus !== 'active')
        <div class="text-center py-6">
            @if($registrationStatus === 'not_started')
                <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-3xl">schedule</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Event Belum Dimulai</h3>
                <p class="text-gray-600 mb-4 text-sm">
                    Registrasi dan Absensi baru dapat dilakukan pada saat acara dimulai, yaitu tanggal: <br>
                    <strong class="block mt-2">{{ \Carbon\Carbon::parse($event->start_date)->translatedFormat('d F Y - H:i') }}</strong>
                </p>
            @elseif($registrationStatus === 'ended')
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-3xl">event_busy</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Event Sudah Selesai</h3>
                <p class="text-gray-600 mb-4 text-sm">
                    Registrasi dan Absensi telah ditutup. Terima kasih atas partisipasinya.
                </p>
            @endif
            <a href="{{ route('events.index') }}" class="inline-block mt-4 px-6 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                Lihat Event Lainnya
            </a>
        </div>
    @else
        {{-- Loading State --}}
        <div x-show="isLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600 mb-2"></div>
            <p class="text-gray-500">Memproses...</p>
        </div>

        <form x-show="!isLoading" action="{{ route('event.register.store', $event->slug) }}" method="POST" @submit.prevent="submitForm">
        @csrf
        @if(isset($mode) && $mode)
            <input type="hidden" name="mode" value="{{ $mode }}">
        @endif

        {{-- Step 1: Cek Identifier (Email/HP) --}}
        <div x-show="step === 1">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Email atau Nomor WhatsApp / HP</label>
                <div class="relative">
                    <input type="text" x-model="identifier" @keyup="validateIdentifier" 
                        class="w-full px-3 py-2 border rounded-lg focus:ring-orange-400 focus:border-orange-400 transition-colors duration-200"
                        :class="isValid ? 'border-green-500 bg-green-50' : (identifier.length > 3 ? 'border-red-300' : 'border-gray-300')"
                        placeholder="contoh@email.com atau 0812..." required>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" x-show="isValid">
                        <span class="material-icons text-green-500 text-sm">check_circle</span>
                    </div>
                </div>
                <p class="text-xs mt-1 text-gray-500" x-text="validationMessage"></p>
            </div>
            
            <button type="button" @click="checkParticipant" :disabled="!isValid"
                class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                Lanjut
            </button>
        </div>

        {{-- Step 2: Form Lengkap --}}
        <div x-show="step === 2" x-cloak>
            <div class="p-4 bg-orange-50 border border-orange-100 rounded-lg mb-6">
                <p class="text-sm font-semibold text-orange-800 mb-2">Data Terverifikasi:</p>
                <div class="flex items-center text-sm text-orange-700">
                    <span class="material-icons text-sm mr-1" x-text="identifierType === 'email' ? 'email' : 'phone'"></span>
                    <span x-text="identifier"></span>
                </div>
                <button type="button" @click="resetForm" class="text-xs underline mt-2 text-orange-600 hover:text-orange-800">Ganti Data</button>
            </div>

            {{-- Hidden Identifier --}}
            <input type="hidden" :name="identifierType" :value="identifier">

            {{-- Fields that were NOT provided in Step 1 or provided but need to be shown --}}
            {{-- We always need Name and Type --}}
            <div class="mb-4">
                <x-input type="text" label="Nama Lengkap" name="name" x-model="form.name" placeholder='Masukkan nama Anda' required />
            </div>

            {{-- If identifier was phone, ask for email and vice versa --}}
            <div class="mb-4" x-show="identifierType === 'phone'">
                <x-input type="email" label="Alamat Email" name="email" x-model="form.email" placeholder='contoh@email.com' required />
            </div>
            <div class="mb-4" x-show="identifierType === 'email'">
                <x-input type="text" label="Nomor WhatsApp / HP" name="phone" x-model="form.phone" placeholder='08xxxxx' required />
            </div>

            {{-- Participant Type --}}
            <div class="mb-6">
                <label class="block text-sm font-bold mb-2" for="participant_type_id">
                    Mendaftar Sebagai
                </label>
                <select name="participant_type_id" id="participant_type_id" required x-model="form.participant_type_id"
                    class="w-full px-3 py-2 border border-gray-300 focus:ring-orange-400 focus:border-orange-400 rounded-lg">
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($participantTypes as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <x-button type="submit" class="w-full mx-auto">Daftar Sekarang</x-button>
        </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    function eventRegistration() {
        return {
            step: 1,
            isLoading: false,
            identifier: "",
            identifierType: "", // 'email' or 'phone'
            isValid: false,
            validationMessage: "Masukkan email atau nomor HP untuk memulai.",
            form: {
                name: "{{ old('name') }}",
                email: "{{ old('email') }}",
                phone: "{{ old('phone') }}",
                participant_type_id: "{{ old('participant_type_id') }}"
            },

            validateIdentifier() {
                const val = this.identifier.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const phoneRegex = /^[0-9+]{8,15}$/;

                if (emailRegex.test(val)) {
                    this.isValid = true;
                    this.identifierType = 'email';
                    this.validationMessage = "Format Email valid.";
                } else if (phoneRegex.test(val)) {
                    this.isValid = true;
                    this.identifierType = 'phone';
                    this.validationMessage = "Format Nomor HP valid.";
                } else {
                    this.isValid = false;
                    this.identifierType = '';
                    this.validationMessage = val.length > 3 ? "Format tidak dikenali (Gunakan email atau nomor HP)." : "Masukkan email atau nomor HP.";
                }
            },
            
            async checkParticipant() {
                if (!this.isValid) return;

                this.isLoading = true;
                
                try {
                    const response = await axios.post("{{ route('event.register.check', $event->slug) }}", {
                        identifier: this.identifier,
                        _token: "{{ csrf_token() }}"
                    });

                    const data = response.data;

                    if (data.status === 'registered') {
                        Swal.fire({
                            title: 'Sudah Terdaftar',
                            text: "Anda sudah terdaftar di event ini. Lihat tiket sekarang?",
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: '#fe5915',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, Lihat Tiket',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed && data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                this.resetForm();
                            }
                        });
                    } else if (data.status === 'exists') {
                        Swal.fire({
                            title: 'Data Ditemukan',
                            text: `Halo ${data.participant.name}, data Anda sudah ada. Gunakan data ini untuk mendaftar?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#fe5915',
                            confirmButtonText: 'Ya, Lanjut',
                            cancelButtonText: 'Gunakan yang lain'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.form.name = data.participant.name;
                                this.form.email = data.participant.email;
                                this.form.phone = data.participant.phone;
                                this.step = 2;
                            } else {
                                this.resetForm();
                            }
                        });
                    } else {
                        // New User
                        this.step = 2;
                        // Pre-fill the one they typed
                        if (this.identifierType === 'email') this.form.email = this.identifier;
                        if (this.identifierType === 'phone') this.form.phone = this.identifier;
                    }

                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Terjadi kesalahan saat mengecek data.', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            submitForm(e) {
                e.target.submit();
            },

            resetForm() {
                this.step = 1;
                this.identifier = '';
                this.isValid = false;
                this.identifierType = '';
                this.validationMessage = "Masukkan email atau nomor HP untuk memulai.";
                this.form.name = '';
                this.form.email = '';
                this.form.phone = '';
                this.form.participant_type_id = '';
            }
        }
    }
</script>
@endpush
@endsection
