@extends('layouts.app')

@section('title', 'GLF BiKube - Professional Bike Care Services')
@section('description', 'Professional bike care services including maintenance, repair, delivery, and eco-friendly solutions.')

@section('content')
<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-5xl font-bold mb-6">GLF BiKube</h1>
        <p class="text-xl mb-8">Professional Bike Care Services</p>
        <p class="text-lg opacity-90">Maintenance, Repair, Delivery & Eco Solutions</p>
    </div>
</div>

<div class="py-16">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Our Services</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Care Services -->
            <div class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow">
                <div class="text-4xl text-blue-600 mb-4">
                    <i class="fas fa-wrench"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Bike Care</h3>
                <p class="text-gray-600 mb-4">Professional maintenance and repair services</p>
                <a href="/care" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Learn More
                </a>
            </div>

            <!-- Eco Services -->
            <div class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow">
                <div class="text-4xl text-green-600 mb-4">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Eco Services</h3>
                <p class="text-gray-600 mb-4">Environmentally friendly bike solutions</p>
                <a href="/eco" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    Learn More
                </a>
            </div>

            <!-- Market Delivery -->
            <div class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow">
                <div class="text-4xl text-orange-600 mb-4">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Market Delivery</h3>
                <p class="text-gray-600 mb-4">Fast and reliable delivery services</p>
                <a href="/market" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700">
                    Learn More
                </a>
            </div>

            <!-- Tow Service -->
            <div class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow">
                <div class="text-4xl text-red-600 mb-4">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Tow Service</h3>
                <p class="text-gray-600 mb-4">Emergency bike towing and transport</p>
                <a href="/tow" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                    Learn More
                </a>
            </div>
        </div>

        <!-- Additional Services -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-4xl text-purple-600 mb-4">
                    <i class="fas fa-key"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Rent Services</h3>
                <p class="text-gray-600 mb-4">Bike rental and sharing solutions</p>
                <a href="/rent" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
                    Learn More
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-4xl text-indigo-600 mb-4">
                    <i class="fas fa-bus"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Shuttle Service</h3>
                <p class="text-gray-600 mb-4">Convenient shuttle transportation</p>
                <a href="/shuttle" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    Learn More
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <div class="text-4xl text-pink-600 mb-4">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Master Services</h3>
                <p class="text-gray-600 mb-4">Expert bike master services</p>
                <a href="/master" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700">
                    Learn More
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-gray-100 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Why Choose GLF BiKube?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="text-4xl text-blue-600 mb-4">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">24/7 Service</h3>
                <p class="text-gray-600">Round-the-clock bike care services</p>
            </div>
            
            <div class="text-center">
                <div class="text-4xl text-green-600 mb-4">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Professional</h3>
                <p class="text-gray-600">Certified technicians and quality service</p>
            </div>
            
            <div class="text-center">
                <div class="text-4xl text-purple-600 mb-4">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Easy Booking</h3>
                <p class="text-gray-600">Simple online booking and tracking</p>
            </div>
        </div>
    </div>
</div>
@endsection