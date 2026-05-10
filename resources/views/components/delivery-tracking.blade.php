{{-- resources/views/components/delivery-tracking.blade.php --}}
@props(['orderId', 'deliveryOrderId'])

@php
    $deliveryOrder = \App\Models\Delivery\DeliveryOrder::with(['order', 'courier'])->find($deliveryOrderId ?? $orderId);
@endphp

@if($deliveryOrder)
<div 
    x-data="deliveryTracker({{ $deliveryOrder->order_id }}, {{ $deliveryOrder->id }}, '{{ $deliveryOrder->tracking_token }}')" 
    class="relative h-96 rounded-xl overflow-hidden shadow-lg bg-white"
>
    <!-- Map Container -->
    <div 
        id="delivery-map-{{ $deliveryOrder->id }}" 
        class="w-full h-full"
        x-init="initMap()"
    ></div>
    
    <!-- Tracking Info Overlay -->
    <div class="absolute bottom-4 left-4 right-4 bg-white rounded-lg shadow-xl p-4 z-10">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <div 
                    :class="{
                        'bg-green-500': trackingStatus === 'delivered',
                        'bg-blue-500': trackingStatus === 'in_transit',
                        'bg-yellow-500': trackingStatus === 'picked_up',
                        'bg-gray-500': trackingStatus === 'pending' || trackingStatus === 'assigned'
                    }"
                    class="w-3 h-3 rounded-full animate-pulse"
                ></div>
                <span 
                    x-text="statusLabel" 
                    class="font-semibold text-slate-900"
                ></span>
            </div>
            <span 
                x-text="`ETA: ${eta || '--:--'}`" 
                class="font-bold text-sky-600"
            ></span>
        </div>
        
        @if($deliveryOrder->courier)
        <div class="mb-3 text-sm text-slate-600">
            <span class="font-medium">Кур'єр:</span>
            <span>{{ $deliveryOrder->courier->email }}</span>
        </div>
        @endif
        
        <div class="flex space-x-2">
            <button 
                @click="contactCourier" 
                class="flex-1 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2 transition-colors"
                :disabled="!courierId"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <span>Написати кур'єру</span>
            </button>
            
            <button 
                @click="refreshLocation" 
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function deliveryTracker(orderId, deliveryOrderId, trackingToken) {
    return {
        orderId: orderId,
        deliveryOrderId: deliveryOrderId,
        trackingToken: trackingToken,
        map: null,
        marker: null,
        courierMarker: null,
        eta: null,
        trackingStatus: 'pending',
        courierLocation: null,
        courierId: null,
        statusLabels: {
            'pending': 'Очікується',
            'assigned': 'Призначено',
            'picked_up': 'Забрано',
            'in_transit': 'В дорозі',
            'delivered': 'Доставлено',
            'cancelled': 'Скасовано',
        },
        
        get statusLabel() {
            return this.statusLabels[this.trackingStatus] || this.trackingStatus;
        },
        
        init() {
            // Load initial data
            this.loadTrackingData();
            
            // Subscribe to Laravel Echo (if available)
            if (typeof Echo !== 'undefined') {
                Echo.private(`order.${this.orderId}`)
                    .listen('OrderUpdated', (e) => {
                        this.updateFromEvent(e);
                    });
            }
            
            // Poll for updates every 10 seconds
            setInterval(() => {
                this.loadTrackingData();
            }, 10000);
        },
        
        initMap() {
            // Initialize Mapbox map
            if (typeof mapboxgl === 'undefined') {
                console.error('Mapbox GL JS not loaded');
                return;
            }
            
            mapboxgl.accessToken = @js(config('services.mapbox.token', ''));
            
            this.map = new mapboxgl.Map({
                container: `delivery-map-${this.deliveryOrderId}`,
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [17.4273, 68.4378], // Narvik default
                zoom: 13
            });
            
            this.map.on('load', () => {
                this.updateMapMarkers();
            });
        },
        
        loadTrackingData() {
            const url = new URL(`/api/v1/delivery/orders/${this.deliveryOrderId}/tracking`, window.location.origin);
            url.searchParams.set('tracking_token', this.trackingToken);

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.updateFromData(data.data);
                    }
                })
                .catch(error => {
                    console.error('Failed to load tracking data:', error);
                });
        },
        
        updateFromData(data) {
            this.eta = data.eta ? new Date(data.eta).toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' }) : null;
            this.trackingStatus = data.tracking_status;
            this.courierLocation = data.courier_location;
            
            if (this.map) {
                this.updateMapMarkers();
            }
        },
        
        updateFromEvent(event) {
            this.eta = event.eta ? new Date(event.eta).toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' }) : null;
            this.trackingStatus = event.tracking_status;
            this.courierLocation = event.courier_location;
            
            if (this.map) {
                this.updateMapMarkers();
            }
        },
        
        updateMapMarkers() {
            if (!this.map) return;
            
            // Remove existing markers
            if (this.marker) this.marker.remove();
            if (this.courierMarker) this.courierMarker.remove();
            
            // Add delivery location marker
            const deliveryLocation = @json($deliveryOrder->delivery_location);
            if (deliveryLocation && deliveryLocation.lat && deliveryLocation.lng) {
                this.marker = new mapboxgl.Marker({ color: '#10b981' })
                    .setLngLat([deliveryLocation.lng, deliveryLocation.lat])
                    .setPopup(new mapboxgl.Popup().setHTML('<div class="p-2"><strong>Адреса доставки</strong><br>{{ $deliveryOrder->delivery_address }}</div>'))
                    .addTo(this.map);
                
                // Center map on delivery location
                this.map.flyTo({
                    center: [deliveryLocation.lng, deliveryLocation.lat],
                    zoom: 14
                });
            }
            
            // Add courier marker if location available
            if (this.courierLocation && this.courierLocation.lat && this.courierLocation.lng) {
                this.courierMarker = new mapboxgl.Marker({ color: '#2563eb' })
                    .setLngLat([this.courierLocation.lng, this.courierLocation.lat])
                    .setPopup(new mapboxgl.Popup().setHTML('<div class="p-2"><strong>Кур\'єр</strong><br>В дорозі</div>'))
                    .addTo(this.map);
            }
        },
        
        contactCourier() {
            if (!this.courierId) {
                alert('Кур\'єр ще не призначено');
                return;
            }
            // In real implementation, open chat or call
            alert('Функція зв\'язку з кур\'єром буде реалізована');
        },
        
        refreshLocation() {
            this.loadTrackingData();
        }
    }
}
</script>

@push('styles')
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
@endpush
@else
<div class="p-8 text-center text-slate-500">
    Замовлення не знайдено
</div>
@endif

