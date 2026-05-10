/*
 * QuickWay Pixel‑Perfect Replica Script
 *
 * Provides interactivity for carousels (products and collections) and category
 * tabs. The code uses straightforward DOM manipulation and smooth scrolling
 * to create a polished user experience without external dependencies. All
 * functions are executed once the DOM is fully loaded.
 */

document.addEventListener('DOMContentLoaded', () => {
  /* Product carousel navigation */
  const productCarousel = document.getElementById('productCarousel');
  const prodPrev = document.getElementById('prodPrev');
  const prodNext = document.getElementById('prodNext');

  function scrollCarousel(container, direction) {
    const cardWidth = container.querySelector('.product-card').offsetWidth + 16;
    // scroll by the width of two cards for a smoother feel
    const scrollAmount = cardWidth * 2;
    container.scrollBy({
      left: direction === 'next' ? scrollAmount : -scrollAmount,
      behavior: 'smooth'
    });
  }
  prodPrev.addEventListener('click', () => scrollCarousel(productCarousel, 'prev'));
  prodNext.addEventListener('click', () => scrollCarousel(productCarousel, 'next'));

  /* Collections carousel navigation */
  const collectionsCarousel = document.getElementById('collectionsCarousel');
  const colPrev = document.getElementById('colPrev');
  const colNext = document.getElementById('colNext');
  colPrev.addEventListener('click', () => {
    const cardWidth = collectionsCarousel.querySelector('.collection-card').offsetWidth + 16;
    collectionsCarousel.scrollBy({ left: -cardWidth * 2, behavior: 'smooth' });
  });
  colNext.addEventListener('click', () => {
    const cardWidth = collectionsCarousel.querySelector('.collection-card').offsetWidth + 16;
    collectionsCarousel.scrollBy({ left: cardWidth * 2, behavior: 'smooth' });
  });

  /* Product tabs: toggles active state. Filtering could be implemented here. */
  const productTabs = document.querySelectorAll('#productTabs .tab');
  productTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      productTabs.forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      // In future, filter products by tab.dataset.category
    });
  });
});