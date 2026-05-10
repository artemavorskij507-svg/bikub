@props([
    'orderId',
    'uploadUrl' => null,
    'token' => config('services.mapbox.token'),
])

@php
    $endpoint = $uploadUrl ?? route('moving.orders.photos.store', ['moving_order' => $orderId], false);
@endphp

<div
    x-data="movingPhotoUploader({
        orderId: '{{ $orderId }}',
        uploadUrl: '{{ $endpoint }}',
        token: '{{ $token }}',
    })"
    x-init="init()"
    class="space-y-4"
>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-3">
            <h3 class="text-lg font-semibold">Фото-документація</h3>
            <p class="text-sm text-slate-500">Додайте до 10 фото перед або після переїзду. Геолокація підтягується автоматично з Mapbox.</p>

            <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary-500 transition" @click="$refs.fileInput.click()">
                <p class="font-semibold">Натисніть, щоб обрати фото</p>
                <p class="text-xs text-slate-500">Допустимі формати: jpg, jpeg, png, webp (до 5Мб)</p>
                <input type="file" accept="image/*" multiple class="hidden" x-ref="fileInput" @change="queueFiles($event.target.files)">
            </div>

            <div class="space-y-2" x-show="files.length">
                <template x-for="(file, index) in files" :key="file.id">
                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                        <img :src="file.preview" class="w-12 h-12 object-cover rounded-md" alt="Preview">
                        <div class="flex-1">
                            <p class="text-sm font-medium" x-text="file.file.name"></p>
                            <p class="text-xs text-slate-500" x-text="humanFileSize(file.file.size)"></p>
                        </div>
                        <button type="button" class="text-red-500 hover:text-red-600" @click="removeFile(index)">
                            <x-heroicon-o-x class="w-5 h-5" />
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <select x-model="collection" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="pre_move_photos">Перед переїздом</option>
                    <option value="post_move_photos">Після переїзду</option>
                    <option value="damage_photos">Пошкодження</option>
                </select>
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700 transition disabled:opacity-50" :disabled="!files.length || uploading" @click="upload()">
                    <span x-show="!uploading">Завантажити</span>
                    <span x-show="uploading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Завантаження…
                    </span>
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-lg font-semibold">Геолокація</h3>
            <div class="rounded-xl overflow-hidden border border-slate-200">
                <div x-ref="map" class="h-64"></div>
            </div>
            <p class="text-sm text-slate-500">
                Координати: <span x-text="location.lat ?? '—'"></span>, <span x-text="location.lng ?? '—'"></span>
            </p>
        </div>
    </div>

    <template x-if="successMessage">
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" x-text="successMessage"></div>
    </template>

    <template x-if="errorMessage">
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="errorMessage"></div>
    </template>
</div>

@once
    @push('styles')
        <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    @endpush
@endonce

@once
    @push('scripts')
        <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    @endpush
@endonce

@push('scripts')
    <script>
        function movingPhotoUploader(config) {
            return {
                files: [],
                collection: 'pre_move_photos',
                location: { lat: null, lng: null },
                uploading: false,
                successMessage: null,
                errorMessage: null,
                map: null,
                marker: null,

                init() {
                    if (!config.token) {
                        console.warn('Mapbox token is missing. Set services.mapbox.token');
                        return;
                    }

                    mapboxgl.accessToken = config.token;
                    this.map = new mapboxgl.Map({
                        container: this.$refs.map,
                        style: 'mapbox://styles/mapbox/streets-v11',
                        center: [17.4273, 68.4385],
                        zoom: 11,
                    });

                    this.map.addControl(new mapboxgl.NavigationControl());
                    this.map.addControl(new mapboxgl.GeolocateControl({
                        positionOptions: { enableHighAccuracy: true },
                        trackUserLocation: false,
                    }));

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.setLocation(position.coords.latitude, position.coords.longitude);
                        },
                        () => {},
                        { enableHighAccuracy: true }
                    );
                },

                setLocation(lat, lng) {
                    this.location = { lat, lng };

                    if (!this.map) return;

                    if (this.marker) {
                        this.marker.setLngLat([lng, lat]);
                    } else {
                        this.marker = new mapboxgl.Marker({ color: '#2563eb' })
                            .setLngLat([lng, lat])
                            .addTo(this.map);
                    }

                    this.map.flyTo({ center: [lng, lat], zoom: 13 });
                },

                queueFiles(fileList) {
                    Array.from(fileList).slice(0, 10 - this.files.length).forEach((file) => {
                        const id = crypto.randomUUID();
                        this.files.push({
                            id,
                            file,
                            preview: URL.createObjectURL(file),
                        });
                    });
                },

                removeFile(index) {
                    URL.revokeObjectURL(this.files[index].preview);
                    this.files.splice(index, 1);
                },

                humanFileSize(bytes) {
                    if (!bytes) return '0 B';
                    const units = ['B', 'kB', 'MB', 'GB'];
                    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                    return (bytes / Math.pow(1024, exponent)).toFixed(1) + ' ' + units[exponent];
                },

                async upload() {
                    if (!this.files.length) return;
                    this.uploading = true;
                    this.successMessage = null;
                    this.errorMessage = null;

                    const form = new FormData();
                    this.files.forEach((file) => form.append('photos[]', file.file));
                    form.append('collection_name', this.collection);
                    if (this.location.lat && this.location.lng) {
                        form.append('latitude', this.location.lat);
                        form.append('longitude', this.location.lng);
                    }

                    try {
                        const response = await fetch(config.uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: form,
                        });

                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }

                        this.files.forEach((file) => URL.revokeObjectURL(file.preview));
                        this.files = [];
                        this.successMessage = 'Фото успішно завантажені.';
                    } catch (error) {
                        console.error(error);
                        this.errorMessage = 'Не вдалося завантажити фото. Спробуйте ще раз.';
                    } finally {
                        this.uploading = false;
                    }
                },
            };
        }
    </script>
@endpush
