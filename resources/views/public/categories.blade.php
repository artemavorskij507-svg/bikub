@extends('layouts.app')
@section('title', 'GLF BiKuBe — Послуги')
@section('content')
<style>
.cats-page{min-height:100vh;background:#0a0a0f;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;padding:40px 20px}
.cats-page::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 15% 50%,rgba(99,102,241,.08) 0%,transparent 50%),radial-gradient(circle at 85% 30%,rgba(236,72,153,.06) 0%,transparent 50%),radial-gradient(circle at 50% 85%,rgba(34,211,238,.08) 0%,transparent 50%);animation:aurora 20s ease-in-out infinite alternate}
@keyframes aurora{0%{filter:hue-rotate(0deg);opacity:.8}50%{filter:hue-rotate(60deg);opacity:1}100%{filter:hue-rotate(-30deg);opacity:.8}}
.orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none;opacity:.4;animation:orbFloat 12s ease-in-out infinite alternate}
@keyframes orbFloat{0%{transform:translate(0,0) scale(1)}100%{transform:translate(50px,-30px) scale(1.2)}}
.slider-wrap{position:relative;z-index:2;width:100%;max-width:1400px;overflow:hidden}
.slider-track{display:flex;transition:transform .7s cubic-bezier(.25,.46,.45,.94);will-change:transform}
.slide{min-width:100%;flex-shrink:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;padding:20px}
@media(min-width:768px){.slide{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1200px){.slide{grid-template-columns:repeat(3,1fr)}}
.cat-card{position:relative;border-radius:20px;overflow:hidden;aspect-ratio:4/3;cursor:pointer;transition:transform .5s cubic-bezier(.23,1,.32,1),box-shadow .5s ease;transform-style:preserve-3d}
.cat-card:hover{transform:translateY(-8px) scale(1.04);box-shadow:0 25px 50px rgba(0,0,0,.5),var(--glow)}
.cat-card-bg{position:absolute;inset:0;background-size:cover;background-position:center;transition:transform .7s ease,filter .7s ease}
.cat-card:hover .cat-card-bg{transform:scale(1.1);filter:brightness(.55)}
.cat-card-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 0%,transparent 30%,rgba(0,0,0,.85) 100%);z-index:1}
.cat-card-content{position:absolute;bottom:0;left:0;right:0;padding:24px;z-index:2;pointer-events:none}
.cat-emoji{font-size:2.5rem;margin-bottom:8px;display:inline-block;transition:transform .4s ease}
.cat-card:hover .cat-emoji{transform:scale(1.2) rotate(-5deg)}
.cat-title{font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:8px;text-shadow:0 2px 8px rgba(0,0,0,.5)}
.cat-desc{font-size:.8rem;color:rgba(255,255,255,.7);line-height:1.4;margin-bottom:12px}
.cat-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:50px;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .3s ease;pointer-events:all}
.cat-btn:hover{background:rgba(255,255,255,.3);border-color:rgba(255,255,255,.5);transform:translateX(4px)}
.cat-flag{position:absolute;top:12px;right:12px;font-size:1.3rem;z-index:3;filter:drop-shadow(0 2px 4px rgba(0,0,0,.5))}
.dots{display:flex;justify-content:center;gap:10px;margin-top:30px;position:relative;z-index:2}
.dot{width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,.2);cursor:pointer;transition:all .3s ease}
.dot.active{background:rgba(255,255,255,.9);transform:scale(1.3);box-shadow:0 0 12px rgba(255,255,255,.4)}
.arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:10;width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.15);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .3s ease;font-size:1.3rem}
.arrow:hover{background:rgba(255,255,255,.25);border-color:rgba(255,255,255,.4)}
.arrow-l{left:10px}
.arrow-r{right:10px}
@media(max-width:480px){.slide{grid-template-columns:1fr;gap:14px;padding:10px}.cat-card{aspect-ratio:3/2}.arrow{display:none}.cat-emoji{font-size:2rem}.cat-title{font-size:1rem}}
</style>
<div class="cats-page">
  <div class="orb" style="width:300px;height:300px;background:rgba(99,102,241,.15);top:-50px;left:-80px;animation-delay:0s"></div>
  <div class="orb" style="width:400px;height:400px;background:rgba(236,72,153,.1);bottom:-100px;right:-100px;animation-delay:3s"></div>
  <div class="orb" style="width:250px;height:250px;background:rgba(34,211,238,.1);top:50%;left:50%;animation-delay:6s"></div>
  <div class="slider-wrap">
    <div class="arrow arrow-l" onclick="moveSlide(-1)">‹</div>
    <div class="arrow arrow-r" onclick="moveSlide(1)">›</div>
    <div class="slider-track" id="sliderTrack">
      <div class="slide">
        <a href="/category/delivery" class="cat-card" style="--glow:0 0 30px rgba(6,182,212,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇳🇴</span>
          <div class="cat-card-content">
            <div class="cat-emoji">📦</div>
            <div class="cat-title">Доставка</div>
            <div class="cat-desc">Продукти, посилки, великі замовлення</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/moving" class="cat-card" style="--glow:0 0 30px rgba(59,130,246,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1600518464441-9154a4dea21b?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇺🇦</span>
          <div class="cat-card-content">
            <div class="cat-emoji">🚚</div>
            <div class="cat-title">Переїзд</div>
            <div class="cat-desc">Під ключ — від пакування до розстановки</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/handyman" class="cat-card" style="--glow:0 0 30px rgba(16,185,129,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1581783898377-1c85bf937427?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇵🇱</span>
          <div class="cat-card-content">
            <div class="cat-emoji">🔧</div>
            <div class="cat-title">Майстер</div>
            <div class="cat-desc">Ремонт, сантехніка, електрика</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/eco" class="cat-card" style="--glow:0 0 30px rgba(245,158,11,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇸🇪</span>
          <div class="cat-card-content">
            <div class="cat-emoji">♻️</div>
            <div class="cat-title">Еко-утилізація</div>
            <div class="cat-desc">Збір та переробка безпечно</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/social-help" class="cat-card" style="--glow:0 0 30px rgba(236,72,153,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇩🇰</span>
          <div class="cat-card-content">
            <div class="cat-emoji">❤️</div>
            <div class="cat-title">Соціальна допомога</div>
            <div class="cat-desc">Підтримка для кожного</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/personal-task" class="cat-card" style="--glow:0 0 30px rgba(100,116,139,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1552581234-26160f608093?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇫🇮</span>
          <div class="cat-card-content">
            <div class="cat-emoji">👤</div>
            <div class="cat-title">Помічник</div>
            <div class="cat-desc">Доручення, покупки, зустрічі</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
      </div>
      <div class="slide">
        <a href="/category/tow" class="cat-card" style="--glow:0 0 30px rgba(239,68,68,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇩🇪</span>
          <div class="cat-card-content">
            <div class="cat-emoji">🚗</div>
            <div class="cat-title">Евакуатор</div>
            <div class="cat-desc">Допомога на дорозі 24/7</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/classifieds" class="cat-card" style="--glow:0 0 30px rgba(251,191,36,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1586769852044-692d6e3703f0?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇪🇪</span>
          <div class="cat-card-content">
            <div class="cat-emoji">📋</div>
            <div class="cat-title">Оголошення</div>
            <div class="cat-desc">Товари та послуги поруч</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
        <a href="/category/food" class="cat-card" style="--glow:0 0 30px rgba(251,113,133,.3)">
          <div class="cat-card-bg" style="background-image:url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=60')"></div>
          <div class="cat-card-overlay"></div>
          <span class="cat-flag">🇦🇿</span>
          <div class="cat-card-content">
            <div class="cat-emoji">🍽️</div>
            <div class="cat-title">GLF Mat</div>
            <div class="cat-desc">Українська та аз. їжа</div>
            <span class="cat-btn">Перейти →</span>
          </div>
        </a>
      </div>
    </div>
  </div>
  <div class="dots">
    <div class="dot active" onclick="goSlide(0)"></div>
    <div class="dot" onclick="goSlide(1)"></div>
  </div>
</div>
<script>
(function(){
  var cur=0,total=2,track=document.getElementById('sliderTrack'),dots=document.querySelectorAll('.dot');
  window.moveSlide=function(d){cur=Math.max(0,Math.min(total-1,cur+d));upd()};
  window.goSlide=function(i){cur=i;upd()};
  function upd(){track.style.transform='translateX(-'+(cur*100)+'%)';dots.forEach(function(d,j){d.classList.toggle('active',j===cur)})}
  setInterval(function(){cur=(cur+1)%total;upd()},8000);
  var sx=0,w=document.querySelector('.slider-wrap');
  w.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
  w.addEventListener('touchend',function(e){var d=e.changedTouches[0].clientX-sx;if(d<-50)moveSlide(1);else if(d>50)moveSlide(-1)},{passive:true});
})();
</script>
@endsection
