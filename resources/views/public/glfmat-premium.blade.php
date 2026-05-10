@extends("layouts.app")
@section("title", "GLF Mat - Українська та Азербайджанська кухня з доставкою по Нарвику")
@section("content")

<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  html {
    scroll-behavior: smooth;
  }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #0a0e27;
    color: #fff;
  }

  /* CSS Variables */
  :root {
    --gold: #d4af37;
    --terracotta: #e8a87c;
    --dark: #0a0e27;
    --dark-light: #1a1f3a;
    --text-secondary: rgba(255, 255, 255, 0.7);
  }

  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Sticky Mobile CTA */
  .mobile-cta {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: linear-gradient(180deg, rgba(10, 14, 39, 0) 0%, rgba(10, 14, 39, 0.95) 100%);
    padding: 20px;
    animation: slideUp 0.4s ease-out;
  }

  .mobile-cta button {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
    color: #0a0e27;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .mobile-cta button:active {
    transform: scale(0.98);
  }

  @media (max-width: 768px) {
    .mobile-cta {
      display: block;
    }
    
    body {
      padding-bottom: 70px;
    }
  }

  /* Cart Badge */
  .cart-count {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 999;
    background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
    color: #0a0e27;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
    animation: slideDown 0.4s ease-out;
  }

  /* Loading Screen */
  .loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease-out;
  }

  .loading-screen.hidden {
    animation: fadeOut 0.5s ease-out forwards;
    pointer-events: none;
  }

  @keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
  }

  .loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(212, 175, 55, 0.2);
    border-top-color: #d4af37;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .loading-text {
    color: #d4af37;
    font-size: 1.1rem;
    font-weight: 600;
  }

  /* Section base styles */
  section {
    animation: slideUp 0.6s ease-out;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    section {
      padding: 50px 20px !important;
    }

    h2 {
      font-size: 2rem !important;
    }
  }
</style>

<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
  <div class="loading-spinner"></div>
  <div class="loading-text">GLF MaT готується...</div>
</div>

<!-- Cart Count Badge -->
<div class="cart-count" id="cartCount" style="display: none;">0</div>

<!-- Mobile Sticky CTA -->
<div class="mobile-cta" id="mobileCTA">
  <button onclick="document.querySelector('.menu-section').scrollIntoView({behavior: 'smooth'})">
    📱 Замовити зараз
  </button>
</div>

<!-- Hero Banner -->
@include('components.food.hero-banner')

<!-- Two Kitchens Section -->
@include('components.food.two-kitchens')

<!-- Bestsellers Section -->
@include('components.food.bestsellers')

<!-- Social Proof Section -->
@include('components.food.social-proof')

<!-- Combo Offers Section -->
@include('components.food.combo-offers')

<!-- FAQ Section -->
@include('components.food.faq')

<!-- Footer -->
<footer style="
  padding: 60px 20px 30px;
  background: linear-gradient(180deg, #0a0e27 0%, #000000 100%);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
">
  <div style="max-width: 1400px; margin: 0 auto;">
    <!-- Footer Content Grid -->
    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
      margin-bottom: 50px;
    ">
      <!-- About -->
      <div>
        <h3 style="color: #d4af37; margin-bottom: 15px; font-weight: 700;">🍽️ GLF MaT</h3>
        <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem; line-height: 1.6;">
          Аутентична українська та азербайджанська кухня з доставкою по Нарвику. Щоденно готуємо для вас найсмачніше.
        </p>
      </div>

      <!-- Hours -->
      <div>
        <h3 style="color: #d4af37; margin-bottom: 15px; font-weight: 700;">⏰ Години роботи</h3>
        <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem; line-height: 1.8;">
          Пн-Чт: 11:00 - 22:00<br>
          Пт-Сб: 11:00 - 23:00<br>
          Нд: 12:00 - 22:00
        </p>
      </div>

      <!-- Contact -->
      <div>
        <h3 style="color: #d4af37; margin-bottom: 15px; font-weight: 700;">📞 Контакти</h3>
        <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem; line-height: 1.8;">
          <strong>Телефон:</strong> +47 94 40 40 40<br>
          <strong>Email:</strong> info@glfmat.no<br>
          <strong>Адреса:</strong> Нарвик, Норвегія
        </p>
      </div>

      <!-- Links -->
      <div>
        <h3 style="color: #d4af37; margin-bottom: 15px; font-weight: 700;">🔗 Посилання</h3>
        <ul style="list-style: none; padding: 0;">
          <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;" onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)'">Про нас</a></li>
          <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;" onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)'">Умови доставки</a></li>
          <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;" onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)'">Політика конфіденційності</a></li>
          <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease;" onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='rgba(255, 255, 255, 0.6)'">Відгуки</a></li>
        </ul>
      </div>
    </div>

    <!-- Payment & Security -->
    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      padding: 30px;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      margin-bottom: 30px;
    ">
      <div style="text-align: center;">
        <div style="font-size: 1.5rem; margin-bottom: 8px;">💳</div>
        <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Безпечна оплата</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 1.5rem; margin-bottom: 8px;">🔒</div>
        <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Захист даних</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 1.5rem; margin-bottom: 8px;">🍽️</div>
        <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Гарантія якості</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 1.5rem; margin-bottom: 8px;">🚚</div>
        <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Швидка доставка</div>
      </div>
    </div>

    <!-- Social Media & Bottom Text -->
    <div style="
      text-align: center;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    ">
      <div style="margin-bottom: 15px;">
        <a href="#" style="display: inline-block; width: 40px; height: 40px; background: rgba(212, 175, 55, 0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #d4af37; margin: 0 8px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.4)'" onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">f</a>
        <a href="#" style="display: inline-block; width: 40px; height: 40px; background: rgba(212, 175, 55, 0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #d4af37; margin: 0 8px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.4)'" onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">📷</a>
        <a href="#" style="display: inline-block; width: 40px; height: 40px; background: rgba(212, 175, 55, 0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #d4af37; margin: 0 8px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.4)'" onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">🐦</a>
      </div>
      <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.85rem; margin: 0;">
        © 2024 GLF MaT. Всі права захищені. | Розроблено з ❤️ для найкращої кухні Нарвику
      </p>
    </div>
  </div>
</footer>

<script>
  // Hide loading screen when page loads
  window.addEventListener('load', function() {
    const loadingScreen = document.getElementById('loadingScreen');
    loadingScreen.classList.add('hidden');
    setTimeout(() => {
      loadingScreen.style.display = 'none';
    }, 500);
  });

  // Update cart count on load
  window.addEventListener('load', updateCartCount);

  // Track scroll for mobile CTA
  window.addEventListener('scroll', function() {
    const mobileCTA = document.getElementById('mobileCTA');
    const heroHeight = document.querySelector('.hero-banner')?.offsetHeight || 800;
    
    if (window.scrollY > heroHeight) {
      mobileCTA.style.display = 'none';
    } else {
      mobileCTA.style.display = 'block';
    }
  });

  // Prevent scroll while loading
  document.addEventListener('DOMContentLoaded', function() {
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
      document.body.style.overflow = 'auto';
    }, 1500);
  });
</script>

@endsection
