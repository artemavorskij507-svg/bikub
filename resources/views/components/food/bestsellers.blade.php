<!-- Bestsellers Section -->
<section class="bestsellers" style="
  padding: 80px 20px;
  background: #0a0e27;
  color: #fff;
">
  <div style="max-width: 1400px; margin: 0 auto;">
    <!-- Section Header -->
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
          margin: 0 0 15px 0;
          font-weight: 700;
          letter-spacing: -0.02em;
        ">
          🔥 Популярно сегодня
        </h2>
        <p style="
          color: rgba(255, 255, 255, 0.6);
          font-size: 1.05rem;
          margin: 0;
          max-width: 400px;
        ">
          Самые заказываемые блюда, которые полюбили гости GLF MaT
        </p>
      </div>
      <button onclick="document.querySelector('.full-menu').scrollIntoView({behavior: 'smooth'})" style="
        padding: 12px 28px;
        background: rgba(212, 175, 55, 0.2);
        border: 1px solid #d4af37;
        color: #d4af37;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
      " onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.3)'" 
         onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">
        Смотреть все →
      </button>
    </div>

    <!-- Bestsellers Grid -->
    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    ">
      <!-- Dish 1: Borsch -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <!-- Image -->
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop" alt="Борщ" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <!-- Badge: HIT -->
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #d4af37;
            color: #0a0e27;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
          ">
            ⭐ ХИТ
          </div>
          <!-- Badge: Rating -->
          <div style="
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            color: #d4af37;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
          ">
            ⭐ 4.9
          </div>
        </div>

        <!-- Content -->
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #d4af37;
          ">
            Борщ Украинский
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            Классический борщ с говядиной, щедрая порция, гущаво и ароматно
          </p>

          <!-- Specs -->
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🍲 600 г</span>
            <span>⏱ 45 мин</span>
            <span>🌶 Средняя острота</span>
          </div>

          <!-- Price & CTA -->
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #d4af37;">139 kr</div>
              <div style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.5); text-decoration: line-through;">189 kr</div>
            </div>
            <button onclick="addToCart({id: 1, name: 'Борщ Украинский', price: 139, image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>

      <!-- Dish 2: Plov -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=400&fit=crop" alt="Плов" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #e8a87c;
            color: #0a0e27;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
          ">
            🔥 НОВИНКА
          </div>
          <div style="
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(232, 168, 124, 0.2);
            border: 1px solid #e8a87c;
            color: #e8a87c;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
          ">
            ⭐ 5.0
          </div>
        </div>
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #e8a87c;
          ">
            Плов с ягненком
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            Древний рецепт из Азербайджана, ароматный рис со специями и нежным ягненком
          </p>
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🍛 700 г</span>
            <span>⏱ 50 мин</span>
            <span>🌶 Острый</span>
          </div>
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #e8a87c;">189 kr</div>
            </div>
            <button onclick="addToCart({id: 2, name: 'Плов с ягненком', price: 189, image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #e8a87c 0%, #d4af37 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>

      <!-- Dish 3: Shashlik -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1565299624946-b28974268df5?w=400&h=400&fit=crop" alt="Шашлык" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #d4af37;
            color: #0a0e27;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
          ">
            🥩 РЕКОМЕНДУЕМ
          </div>
          <div style="
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            color: #d4af37;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
          ">
            ⭐ 4.8
          </div>
        </div>
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #d4af37;
          ">
            Шашлык из свинины
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            На углях, с дымком, маринованный в специях, подается с лавашом и соусом
          </p>
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🍖 500 г</span>
            <span>⏱ 40 мин</span>
            <span>🌶 Острый</span>
          </div>
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #d4af37;">159 kr</div>
              <div style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.5); text-decoration: line-through;">199 kr</div>
            </div>
            <button onclick="addToCart({id: 3, name: 'Шашлык из свинины', price: 159, image: 'https://images.unsplash.com/photo-1565299624946-b28974268df5?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>

      <!-- Dish 4: Khachapuri -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1555939594-58d7cb561ee1?w=400&h=400&fit=crop" alt="Хачапури" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #d4af37;
            color: #0a0e27;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
          ">
            🧀 СЫРНОЕ
          </div>
        </div>
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #d4af37;
          ">
            Хачапури
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            Пышный хлеб с плавленым сыром, чесноком и зеленью, горячий из духовки
          </p>
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🥖 350 г</span>
            <span>⏱ 30 мин</span>
            <span>🌶 Мягкий вкус</span>
          </div>
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #d4af37;">89 kr</div>
            </div>
            <button onclick="addToCart({id: 4, name: 'Хачапури', price: 89, image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ee1?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>

      <!-- Dish 5: Varenyky -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1569718776388-58fb37e2d6d9?w=400&h=400&fit=crop" alt="Вареники" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <div style="
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            color: #d4af37;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
          ">
            ⭐ 4.7
          </div>
        </div>
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #d4af37;
          ">
            Вареники творог & ягода
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            Мягкие, нежные вареники со свежим творогом и малиной, сметана на гарнире
          </p>
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🥟 450 г</span>
            <span>⏱ 35 мин</span>
            <span>🌶 Сладкий</span>
          </div>
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #d4af37;">119 kr</div>
            </div>
            <button onclick="addToCart({id: 5, name: 'Вареники творог & ягода', price: 119, image: 'https://images.unsplash.com/photo-1569718776388-58fb37e2d6d9?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #d4af37 0%, #e8a87c 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>

      <!-- Dish 6: Luyla-kebab -->
      <div class="dish-card" style="
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
      " onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 60px rgba(212, 175, 55, 0.2)'" 
         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div style="
          position: relative;
          height: 200px;
          background: linear-gradient(135deg, #1a1f3a 0%, #2a2f4a 100%);
          overflow: hidden;
        ">
          <img src="https://images.unsplash.com/photo-1599599810694-d3003ca6b984?w=400&h=400&fit=crop" alt="Люля-кебаб" style="
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
          " onmouseover="this.style.transform='scale(1.05)'" 
             onmouseout="this.style.transform='scale(1)'">
          <div style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #e8a87c;
            color: #0a0e27;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
          ">
            🔥 ОСТРОЕ
          </div>
        </div>
        <div style="padding: 24px;">
          <h3 style="
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #e8a87c;
          ">
            Люля-кебаб
          </h3>
          <p style="
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 16px 0;
            line-height: 1.5;
          ">
            Мясной фарш с пряностями, на углях, с лавашом и овощами, острая икра
          </p>
          <div style="
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          ">
            <span>🌯 350 г</span>
            <span>⏱ 40 мин</span>
            <span>🌶 Острый</span>
          </div>
          <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div>
              <div style="font-size: 1.4rem; font-weight: 700; color: #e8a87c;">149 kr</div>
            </div>
            <button onclick="addToCart({id: 6, name: 'Люля-кебаб', price: 149, image: 'https://images.unsplash.com/photo-1599599810694-d3003ca6b984?w=400&h=400&fit=crop'})" style="
              padding: 10px 18px;
              background: linear-gradient(135deg, #e8a87c 0%, #d4af37 100%);
              color: #0a0e27;
              border: none;
              border-radius: 8px;
              font-weight: 700;
              cursor: pointer;
              transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.05)'" 
               onmouseout="this.style.transform='scale(1)'">
              + Добавить
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function addToCart(dish) {
  let cart = JSON.parse(localStorage.getItem('cart') || '[]');
  const existing = cart.find(item => item.id === dish.id);
  if (existing) {
    existing.quantity++;
  } else {
    dish.quantity = 1;
    cart.push(dish);
  }
  localStorage.setItem('cart', JSON.stringify(cart));
  alert(`${dish.name} добавлен в корзину!`);
  updateCartCount();
}

function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem('cart') || '[]');
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);
  const badge = document.querySelector('.cart-count');
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }
}

window.addEventListener('load', updateCartCount);
</script>
