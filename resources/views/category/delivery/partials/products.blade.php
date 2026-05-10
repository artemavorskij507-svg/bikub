<section class="products">
    <div class="container">
        <h2>Популярные товары</h2>

        <div class="product-tabs" id="productTabs">
            <button class="tab active" data-category="all">Продукты</button>
            <button class="tab" data-category="meals">Готовые блюда</button>
            <button class="tab" data-category="home">Для дома</button>
            <button class="tab" data-category="beauty">Красота и здоровье</button>
            <button class="tab" data-category="drinks">Напитки</button>
            <button class="tab" data-category="kids">Детские товары</button>
            <button class="see-all">Смотреть все</button>
        </div>

        <div class="product-carousel-wrapper">
            <button class="carousel-arrow left" id="prodPrev"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="product-carousel" id="productCarousel">
                <article class="product-card" data-category="all">
                    <img class="product-img" src="{{ asset('images/delivery-template/bananas.jpg') }}" alt="Бананы">
                    <div class="product-info">
                        <div class="product-name">Бананы</div>
                        <div class="product-weight">1 кг</div>
                        <div class="product-pricing"><span class="price">129 ₽</span><span class="old-price">152 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>

                <article class="product-card" data-category="all">
                    <img class="product-img" src="{{ asset('images/delivery-template/avocado.jpg') }}" alt="Авокадо">
                    <div class="product-info">
                        <div class="product-name">Авокадо Хасс</div>
                        <div class="product-weight">700 г</div>
                        <div class="product-pricing"><span class="price">189 ₽</span><span class="old-price">235 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>

                <article class="product-card" data-category="all">
                    <img class="product-img" src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Raw_salmon_fillets.jpg/500px-Raw_salmon_fillets.jpg" alt="Лосось филе">
                    <div class="product-info">
                        <div class="product-name">Лосось филе</div>
                        <div class="product-weight">500 г</div>
                        <div class="product-pricing"><span class="price">899 ₽</span><span class="old-price">999 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>

                <article class="product-card" data-category="all">
                    <img class="product-img" src="{{ asset('images/delivery-template/milk.jpg') }}" alt="Молоко">
                    <div class="product-info">
                        <div class="product-name">Молоко</div>
                        <div class="product-weight">1 л</div>
                        <div class="product-pricing"><span class="price">89 ₽</span><span class="old-price">110 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>

                <article class="product-card" data-category="all">
                    <img class="product-img" src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/Tomatoes.jpg/500px-Tomatoes.jpg" alt="Томаты черри">
                    <div class="product-info">
                        <div class="product-name">Томаты черри</div>
                        <div class="product-weight">250 г</div>
                        <div class="product-pricing"><span class="price">119 ₽</span><span class="old-price">149 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>

                <article class="product-card" data-category="all">
                    <img class="product-img" src="{{ asset('images/delivery-template/chips.jpg') }}" alt="Чипсы">
                    <div class="product-info">
                        <div class="product-name">Чипсы</div>
                        <div class="product-weight">100 г</div>
                        <div class="product-pricing"><span class="price">129 ₽</span><span class="old-price">157 ₽</span></div>
                    </div>
                    <button class="add-to-cart"><i class="fa-solid fa-basket-shopping"></i> В корзину</button>
                </article>
            </div>
            <button class="carousel-arrow right" id="prodNext"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
</section>

