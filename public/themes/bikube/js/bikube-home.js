// минимальная логика для анимаций / парраллакса

document.addEventListener('DOMContentLoaded', function(){
  const hero = document.querySelector('.bikube-hero');
  if(!hero) return;

  window.addEventListener('scroll', function(){
    const y = window.scrollY;
    hero.style.backgroundPosition = `center ${Math.max(-20, -y/8)}px`;
  });
});

