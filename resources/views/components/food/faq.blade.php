<!-- FAQ Section -->
<section class="faq" style="
  padding: 80px 20px;
  background: #0a0e27;
  color: #fff;
">
  <div style="max-width: 1000px; margin: 0 auto;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 60px;">
      <h2 style="
        font-size: clamp(2rem, 5vw, 3.5rem);
        margin-bottom: 15px;
        font-weight: 700;
        letter-spacing: -0.02em;
      ">
        Часто спрашивают
      </h2>
      <p style="
        font-size: 1.05rem;
        color: rgba(255, 255, 255, 0.6);
      ">
        Вся информация о доставке, заказе и нашей кухне
      </p>
    </div>

    <!-- FAQ Items -->
    <div style="display: flex; flex-direction: column; gap: 15px;">
      <!-- FAQ Item 1 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #d4af37;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>❓ Как долго доставляется заказ?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;">Стандартное время доставки в Нарвике: <strong>30-45 минут</strong> с момента подтверждения заказа. В пиковые часы (обед и ужин) может быть на 10-15 минут дольше. Вы получите уведомление, когда курьер выехал, и сможете отследить время прибытия.</p>
          </div>
        </div>
      </div>

      <!-- FAQ Item 2 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #e8a87c;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>💳 Какие способы оплаты вы принимаете?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;">Мы принимаем:</p>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
              <li>Карты Visa и Mastercard</li>
              <li>Mobile Pay и других платежных сервисов</li>
              <li>Оплата наличными при доставке</li>
              <li>Переводы через банк (для заказов от 500 kr)</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- FAQ Item 3 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #d4af37;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>🌱 Есть ли веганские или диетические опции?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;">Да! Мы предлагаем:</p>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
              <li><strong>Вегетарианские блюда:</strong> Вареники с творогом и ягодой, Гарнир плов, Овощные приготовления</li>
              <li><strong>Низкокалорийные опции:</strong> Салаты, Гарниры без масла</li>
              <li><strong>Без глютена:</strong> Уточните с менеджером при заказе</li>
            </ul>
            <p style="margin: 10px 0 0 0;"><strong>Звоните:</strong> +47 94 40 40 40, чтобы узнать специальные диетические опции</p>
          </div>
        </div>
      </div>

      <!-- FAQ Item 4 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #e8a87c;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>❌ Как отменить или изменить заказ?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;"><strong>Вы можете отменить заказ:</strong></p>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
              <li>В течение <strong>5 минут после подачи</strong> - полный возврат</li>
              <li>От 5 до 10 минут - 50% возврата</li>
              <li>После отправки курьера - только частичный возврат или замена</li>
            </ul>
            <p style="margin: 10px 0 0 0;">Для изменения заказа звоните: <strong>+47 94 40 40 40</strong></p>
          </div>
        </div>
      </div>

      <!-- FAQ Item 5 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #d4af37;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>🎁 Как использовать промокод?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;"><strong>Используй промокод GLFTOP15 при оформлении:</strong></p>
            <ol style="margin: 10px 0 0 20px; padding: 0;">
              <li>Выбери блюда в меню</li>
              <li>Перейди к оформлению заказа</li>
              <li>В поле 'Промокод' введи: <strong>GLFTOP15</strong></li>
              <li>Получи скидку <strong>15%</strong> от суммы заказа</li>
            </ol>
            <p style="margin: 10px 0 0 0;"><strong>Условие:</strong> Только для первого заказа, сумма от 100 kr, срок действия до конца месяца</p>
          </div>
        </div>
      </div>

      <!-- FAQ Item 6 -->
      <div style="
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
      ">
        <button onclick="toggleFaq(this)" style="
          width: 100%;
          padding: 20px;
          background: rgba(255, 255, 255, 0.02);
          border: none;
          color: #e8a87c;
          text-align: left;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.05)'" 
           onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.02)'">
          <span>📍 Какой район вы обслуживаете?</span>
          <span style="font-size: 1.3rem;">+</span>
        </button>
        <div class="faq-answer" style="
          max-height: 0;
          overflow: hidden;
          transition: max-height 0.3s ease;
        ">
          <div style="padding: 0 20px 20px 20px; color: rgba(255, 255, 255, 0.7); line-height: 1.6;">
            <p style="margin: 0;">Мы доставляем по всему Нарвику:</p>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
              <li>Центр города - 30-40 минут</li>
              <li>Кингсерека - 35-45 минут</li>
              <li>Сумпиярви - 40-50 минут</li>
              <li>Окраинные районы - 45-60 минут</li>
            </ul>
            <p style="margin: 10px 0 0 0;"><strong>Проверить доступность</strong> можно введя свой адрес при оформлении заказа</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Section -->
    <div style="
      margin-top: 60px;
      background: rgba(212, 175, 55, 0.1);
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 16px;
      padding: 40px;
      text-align: center;
    ">
      <h3 style="
        margin: 0 0 15px 0;
        color: #d4af37;
        font-size: 1.4rem;
        font-weight: 700;
      ">
        Не нашел ответ?
      </h3>
      <p style="
        margin: 0 0 20px 0;
        color: rgba(255, 255, 255, 0.7);
      ">
        Свяжись с нами напрямую
      </p>
      <div style="
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
      ">
        <a href="tel:+4794404040" style="
          padding: 12px 28px;
          background: #d4af37;
          color: #0a0e27;
          border: none;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 700;
          cursor: pointer;
          transition: all 0.3s ease;
          display: inline-block;
        " onmouseover="this.style.transform='scale(1.05)'" 
           onmouseout="this.style.transform='scale(1)'">
          📞 +47 94 40 40 40
        </a>
        <a href="https://wa.me/4794404040" target="_blank" style="
          padding: 12px 28px;
          background: rgba(212, 175, 55, 0.2);
          color: #d4af37;
          border: 1px solid #d4af37;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 700;
          cursor: pointer;
          transition: all 0.3s ease;
          display: inline-block;
        " onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.3)'" 
           onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'">
          💬 WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

<script>
function toggleFaq(button) {
  const answer = button.nextElementSibling;
  const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';
  
  if (isOpen) {
    answer.style.maxHeight = '0';
    button.querySelector('span:last-child').textContent = '+';
  } else {
    answer.style.maxHeight = answer.scrollHeight + 'px';
    button.querySelector('span:last-child').textContent = '−';
  }
}
</script>
