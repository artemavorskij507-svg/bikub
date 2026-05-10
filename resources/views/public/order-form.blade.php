@extends('layouts.app')

@section('title', 'Book ' . $service->name . ' - GLF BiKube')
@section('description', 'Book ' . $service->name . ' service with GLF BiKube')

@section('content')
<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold mb-4">Book {{ $service->name }}</h1>
        <p class="text-xl">Professional {{ strtolower($service->name) }} service</p>
    </div>
</div>

<div class="py-16">
    <div class="max-w-4xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Service Details -->
            <div>
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-4xl text-blue-600 mb-6">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">{{ $service->name }}</h2>
                    <p class="text-gray-600 mb-6">{{ $service->description ?: 'Professional service for your bike needs.' }}</p>
                    
                    @if($service->default_pricing)
                        @php
                            $pricing = is_string($service->default_pricing) ? json_decode($service->default_pricing, true) : $service->default_pricing;
                            $basePrice = $pricing['base_price'] ?? null;
                        @endphp
                        @if($basePrice)
                            <div class="text-3xl font-bold text-green-600 mb-6">
                                ${{ number_format($basePrice, 2) }}
                            </div>
                        @endif
                    @endif
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Professional Service</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>24/7 Availability</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Quality Guarantee</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Form -->
            <div>
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-2xl font-bold mb-6">Book This Service</h3>
                    
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" id="phone" name="phone" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea id="address" name="address" rows="3" required 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div>
                            <label for="preferred_date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                            <input type="date" id="preferred_date" name="preferred_date" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="preferred_time" class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
                            <select id="preferred_time" name="preferred_time" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Time</option>
                                <option value="morning">Morning (8:00 - 12:00)</option>
                                <option value="afternoon">Afternoon (12:00 - 17:00)</option>
                                <option value="evening">Evening (17:00 - 20:00)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Any special requirements or notes..."></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 font-semibold text-lg">
                            <i class="fas fa-calendar-check mr-2"></i>Book Service
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back to Services -->
<div class="bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <a href="/catalog" class="text-blue-600 hover:text-blue-800 font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Services
        </a>
    </div>
</div>

<script>
// Set minimum date to today
document.getElementById('preferred_date').min = new Date().toISOString().split('T')[0];
</script>
@endsection