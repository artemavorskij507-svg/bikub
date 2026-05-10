<!-- Hero Banner with Video Background -->
<section class="hero-banner" style="
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
">
  <!-- Video Background -->
  <video autoplay muted loop playsinline style="
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.4;
  ">
    <source src="https://videos.pexels.com/video-files/3571885/3571885-hd_1920_1080_30fps.mp4" type="video/mp4">
  </video>

  <!-- Dark Overlay -->
  <div style="
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 14, 39, 0.65);
    z-index: 2;
  "></div>

  <!-- Content -->
  <div class="hero-content" style="
    position: relative;
    z-index: 3;
    text-align: center;
    max-width: 900px;
    padding: 40px 20px;
    animation: fadeInUp 1s ease-out;
  ">
    <!-- Main Headline -->
    <h1 style="
      font-size: clamp(2.5rem, 8vw, 4.5rem);
      font-weight: 700;
      line-height: 1.1;
      margin-bottom: 20px;
      color: #fff;
      letter-spacing: -0.02em;
    ">
      Украинская душа.<br>
      <span style="background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        Азербайджанский огонь.
      </span>
    </h1>

    <!-- Subheading -->
    <p style="
      font-size: clamp(1.1rem, 3vw, 1.4rem);
      color: rgba(255, 255, 255, 0.85);
      margin-bottom: 40px;
      line-height: 1.6;
      font-weight: 300;
    ">
      Аутентичная кухня с доставкой по Нарвику — горячо, щедро, по-настоящему вкусно
    </p>

    <!-- Trust Triggers -->
    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
      padding: 30px 20px;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    ">
      <div style="text-align: center;">
        <div style="font-size: 2rem; color: #d4af37; font-weight: bold;">⭐ 4.9</div>
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">2800+ отзывов</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 2rem; color: #e8a87c; font-weight: bold;">🚚 30-45</div>
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">мин доставка</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 2rem; color: #d4af37; font-weight: bold;">✨ 100%</div>
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">свежие ингредиенты</div>
      </div>
      <div style="text-align: center;">
        <div style="font-size: 2rem; color: #e8a87c; font-weight: bold;">🎁 -15%</div>
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">на первый заказ</div>
      </div>
    </div>

    <!-- CTA Buttons -->
    <div style="
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 40px;
    ">
      <button onclick="document.querySelector('.menu-section').scrollIntoView({behavior: 'smooth'})" style="
        padding: 16px 48px;
        font-size: 1.1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
        color: #0a0e27;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
      " onmouseover="this.style.boxShadow='0 12px 48px rgba(212, 175, 55, 0.5)'" 
         onmouseout="this.style.boxShadow='0 8px 32px rgba(212, 175, 55, 0.3)'">
        📱 Заказать сейчас
      </button>
      <button onclick="document.querySelector('.full-menu').scrollIntoView({behavior: 'smooth'})" style="
        padding: 16px 48px;
        font-size: 1.1rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 2px solid #d4af37;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.15)'" 
         onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.1)'">
        📖 Смотреть меню
      </button>
    </div>

    <!-- Promo Badge -->
    <div style="
      display: inline-block;
      background: rgba(212, 175, 55, 0.2);
      border: 1px solid #d4af37;
      padding: 12px 24px;
      border-radius: 50px;
      font-size: 0.95rem;
      color: #d4af37;
      font-weight: 600;
    ">
      🎉 Введи код <strong>GLFTOP15</strong> и получи скидку 15%
    </div>
  </div>

  <!-- Scroll Indicator -->
  <div style="
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    animation: bounce 2s infinite;
  ">
    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 5V20M10 15L15 20L20 15" stroke="#d4af37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</section>

<style>
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes bounce {
    0%, 100% {
      transform: translateX(-50%) translateY(0);
    }
    50% {
      transform: translateX(-50%) translateY(10px);
    }
  }

  @media (max-width: 768px) {
    .hero-content {
      animation: none;
    }
  }
</style>
