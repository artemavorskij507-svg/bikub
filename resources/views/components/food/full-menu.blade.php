<!-- Full Menu Section -->
<section class="menu-section full-menu" style="
  padding: 80px 20px;
  background: #0a0e27;
  color: #fff;
">
  <div style="max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 60px;
      flex-wrap: wrap;
      gap: 30px;
    ">
      <div>
        <h2 style="
          font-size: clamp(2rem, 5vw, 3.5rem);
          margin: 0 0 10px 0;
          font-weight: 700;
          letter-spacing: -0.02em;
        ">
          📖 Повне меню
        </h2>
        <p style="
          color: rgba(255, 255, 255, 0.6);
          margin: 0;
        ">
          Все блюда, які ми готуємо для вас
        </p>
      </div>
      <input type="text" id="menuSearch" placeholder="🔍 Пошук по назві..." style="
        padding: 12px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 12px;
        color: #fff;
        font-size: 0.95rem;
        min-width: 250px;
        transition: all 0.3s ease;
      " onmouseover="this.style.borderColor='#d4af37'" 
         onmouseout="this.style.borderColor='rgba(212, 175, 55, 0.3)'" 
         oninput="filterMenu()">
    </div>

    <!-- Category Tabs -->
    <div style="
      display: flex;
      gap: 15px;
      overflow-x: auto;
      padding-bottom: 30px;
      margin-bottom: 60px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    ">
      <button class="category-tab" onclick="filterByCategory('all')" style="
        padding: 10px 24px;
        background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
        color: #0a0e27;
        border: none;
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
      ">
        Всі
      </button>
      <button class="category-tab" onclick="filterByCategory('ukrainian')" style="
        padding: 10px 24px;
        background: rgba(212, 175, 55, 0.2);
        color: #d4af37;
        border: 1px solid #d4af37;
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.3)'" 
         onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">
        🇺🇦 Українська
      </button>
      <button class="category-tab" onclick="filterByCategory('azerbaijan')" style="
        padding: 10px 24px;
        background: rgba(232, 168, 124, 0.2);
        color: #e8a87c;
        border: 1px solid #e8a87c;
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='rgba(232, 168, 124, 0.3)'" 
         onmouseout="this.style.backgroundColor='rgba(232, 168, 124, 0.2)'">
        🇦🇿 Азербайджанська
      </button>
      <button class="category-tab" onclick="filterByCategory('spicy')" style="
        padding: 10px 24px;
        background: rgba(212, 175, 55, 0.15);
        color: #d4af37;
        border: 1px solid rgba(212, 175, 55, 0.5);
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.25)'" 
         onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.15)'">
        🌶️ Гостре
      </button>
      <button class="category-tab" onclick="filterByCategory('vegetarian')" style="
        padding: 10px 24px;
        background: rgba(212, 175, 55, 0.15);
        color: #d4af37;
        border: 1px solid rgba(212, 175, 55, 0.5);
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.25)'" 
         onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.15)'">
        🌱 Вегетаріанське
      </button>
    </div>

    <!-- Menu Items Grid -->
    <div id="menuGrid" style="
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 25px;
    ">
      <!-- Menu Item Template - будет заполнено JavaScript -->
      <div class="menu-item" data-category="ukrainian" style="
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="height: 150px; overflow: hidden; background: linear-gradient(135deg, #1a1f3a, #2a2f4a);">
          <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=300&fit=crop" alt="Борщ" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="padding: 18px;">
          <h4 style="margin: 0 0 8px 0; color: #d4af37; font-weight: 700;">Борщ</h4>
          <p style="margin: 0 0 12px 0; color: rgba(255, 255, 255, 0.6); font-size: 0.85rem;">Класичний борщ з говядиною</p>
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="color: #d4af37; font-weight: 700; font-size: 1.1rem;">139 kr</span>
            <button style="padding: 6px 12px; background: rgba(212, 175, 55, 0.2); border: 1px solid #d4af37; color: #d4af37; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.3)'" onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">+ Замовити</button>
          </div>
        </div>
      </div>

      <!-- Інші блюда генеруються через JS -->
    </div>

    <!-- CTA Section -->
    <div style="
      margin-top: 80px;
      background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
      color: #0a0e27;
      border-radius: 20px;
      padding: 50px 40px;
      text-align: center;
    ">
      <h3 style="
        font-size: 1.6rem;
        margin: 0 0 15px 0;
        font-weight: 700;
      ">
        Не можете вибрати? 🤔
      </h3>
      <p style="
        font-size: 1rem;
        margin: 0 0 25px 0;
        opacity: 0.9;
      ">
        Скористайтеся нашим комбо-набором і спробуйте краще з двох традицій!
      </p>
      <button onclick="document.querySelector('.combo-offers').scrollIntoView({behavior: 'smooth'})" style="
        padding: 16px 48px;
        background: #0a0e27;
        color: #d4af37;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
      " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        Проглянути комбо
      </button>
    </div>
  </div>
</section>

<script>
// Menu items data
const menuItems = [
  { name: 'Борщ', price: 139, category: ['ukrainian'], img: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=300&fit=crop' },
  { name: 'Вареники', price: 119, category: ['ukrainian'], img: 'https://images.unsplash.com/photo-1569718776388-58fb37e2d6d9?w=300&h=300&fit=crop' },
  { name: 'Деруны', price: 99, category: ['ukrainian', 'vegetarian'], img: 'https://images.unsplash.com/photo-1626082225019-6692f12f0cf1?w=300&h=300&fit=crop' },
  { name: 'Плов з ягненком', price: 189, category: ['azerbaijan', 'spicy'], img: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300&h=300&fit=crop' },
  { name: 'Люля-кебаб', price: 149, category: ['azerbaijan', 'spicy'], img: 'https://images.unsplash.com/photo-1599599810694-d3003ca6b984?w=300&h=300&fit=crop' },
  { name: 'Долма', price: 129, category: ['azerbaijan'], img: 'https://images.unsplash.com/photo-1626082225019-6692f12f0cf1?w=300&h=300&fit=crop' },
  { name: 'Шашлик', price: 159, category: ['azerbaijan', 'spicy'], img: 'https://images.unsplash.com/photo-1565299624946-b28974268df5?w=300&h=300&fit=crop' },
  { name: 'Хачапури', price: 89, category: ['azerbaijan', 'vegetarian'], img: 'https://images.unsplash.com/photo-1555939594-58d7cb561ee1?w=300&h=300&fit=crop' },
];

function renderMenu(items) {
  const grid = document.getElementById('menuGrid');
  grid.innerHTML = items.map(item => `
    <div class="menu-item" data-category="${item.category.join(',')}" style="
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
    " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(212, 175, 55, 0.2)'" 
       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
      <div style="height: 150px; overflow: hidden; background: linear-gradient(135deg, #1a1f3a, #2a2f4a);">
        <img src="${item.img}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">
      </div>
      <div style="padding: 18px;">
        <h4 style="margin: 0 0 8px 0; color: #d4af37; font-weight: 700;">${item.name}</h4>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
          <span style="color: #d4af37; font-weight: 700; font-size: 1.1rem;">${item.price} kr</span>
          <button onclick="addToCart({id: '${item.name}', name: '${item.name}', price: ${item.price}, image: '${item.img}'})" style="padding: 6px 12px; background: rgba(212, 175, 55, 0.2); border: 1px solid #d4af37; color: #d4af37; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.3)'" onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">+ Замовити</button>
        </div>
      </div>
    </div>
  `).join('');
}

function filterByCategory(category) {
  if (category === 'all') {
    renderMenu(menuItems);
  } else {
    const filtered = menuItems.filter(item => item.category.includes(category));
    renderMenu(filtered);
  }
}

function filterMenu() {
  const search = document.getElementById('menuSearch').value.toLowerCase();
  const filtered = menuItems.filter(item => item.name.toLowerCase().includes(search));
  renderMenu(filtered);
}

// Initialize menu on load
window.addEventListener('load', () => renderMenu(menuItems));
</script>
