<header class="top-nav">
    <div class="container nav-inner">
        <a class="brand" href="{{ url('/') }}" aria-label="BiKuBe">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="logo-text">Quick<span>Way</span></span>
        </a>

        <nav class="nav-links" aria-label="Навигация">
            <a href="#" class="active">Москва</a>
            <a href="#">О сервисе</a>
            <a href="#">Доставка</a>
            <a href="#">Магазины</a>
            <a href="#">Рестораны</a>
            <a href="#">Для бизнеса</a>
        </nav>

        <div class="nav-actions">
            @auth
                <a href="{{ route('account.dashboard') }}" class="btn login">Кабинет</a>
            @else
                <a href="{{ route('login') }}" class="btn login">Войти</a>
            @endauth
            <a href="{{ route('register') }}" class="btn signup">Регистрация</a>
        </div>
    </div>
</header>

