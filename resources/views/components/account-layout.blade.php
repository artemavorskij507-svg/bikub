<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex flex-col md:flex-row gap-8">
        <aside class="w-full md:w-64 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-xl">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 truncate w-32">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500">Member since {{ Auth::user()->created_at->format('Y') }}</div>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('account.classifieds.my-ads') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.my-ads') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>📋</span> My Listings
                    </a>
                    <a href="{{ route('account.classifieds.messages') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.messages') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>💬</span> Messages
                    </a>
                    <a href="{{ route('account.classifieds.favorites') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.favorites') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>❤️</span> Favorites
                    </a>
                    <a href="{{ route('account.classifieds.shop') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.shop') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>🏪</span> My Shop
                    </a>
                    <div class="h-px bg-gray-100 my-2"></div>
                    <a href="{{ route('account.classifieds.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition">
                        <span>+</span> Post New Ad
                    </a>
                </nav>
            </div>
        </aside>
        <main class="flex-grow">
            {{ $slot }}
        </main>
    </div>
</div>


    <div class="flex flex-col md:flex-row gap-8">
        <aside class="w-full md:w-64 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-xl">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 truncate w-32">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500">Member since {{ Auth::user()->created_at->format('Y') }}</div>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('account.classifieds.my-ads') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.my-ads') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>📋</span> My Listings
                    </a>
                    <a href="{{ route('account.classifieds.messages') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.messages') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>💬</span> Messages
                    </a>
                    <a href="{{ route('account.classifieds.favorites') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.favorites') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>❤️</span> Favorites
                    </a>
                    <a href="{{ route('account.classifieds.shop') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition {{ request()->routeIs('account.classifieds.shop') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>🏪</span> My Shop
                    </a>
                    <div class="h-px bg-gray-100 my-2"></div>
                    <a href="{{ route('account.classifieds.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition">
                        <span>+</span> Post New Ad
                    </a>
                </nav>
            </div>
        </aside>
        <main class="flex-grow">
            {{ $slot }}
        </main>
    </div>
</div>

