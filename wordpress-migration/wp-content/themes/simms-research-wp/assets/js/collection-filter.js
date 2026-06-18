/**
 * Inline collection filter (WooCommerce port of the Shopify storefront component).
 * - Search: client-side title filtering against the rendered product cards.
 * - Sort: client-side reordering of the rendered cards (all products render on one page).
 *
 * Companion markup: archive-product.php (<collection-filter>), styles in simms-sections.css.
 */
class CollectionFilter extends HTMLElement {
  connectedCallback() {
    this.input = this.querySelector('[data-search-input]');
    this.clearBtn = this.querySelector('[data-search-clear]');
    this.sortSelect = this.querySelector('[data-sort-select]');
    this.statusEl = this.querySelector('[data-status]');

    const gridSelector = this.dataset.gridSelector || '.shop-page__inner ul.products';
    const cardSelector = this.dataset.cardSelector || 'li.product';

    this.grid = document.querySelector(gridSelector);
    if (!this.grid) {
      // Grid hasn't mounted yet — retry once on next frame, then give up.
      if (!this._retried) {
        this._retried = true;
        requestAnimationFrame(() => this.connectedCallback());
      }
      return;
    }

    this.cards = Array.from(this.grid.querySelectorAll(cardSelector));
    this.originalOrder = this.cards.slice();
    this.totalCount = this.cards.length;
    this.cacheData();
    this.bindEvents();
    this.applyFilter();
  }

  cacheData() {
    this.cards.forEach((card) => {
      const titleEl = card.querySelector('.simms-product-card__title');
      card.dataset.searchTitle = (titleEl ? titleEl.textContent : card.textContent || '').trim().toLowerCase();
      const priceEl = card.querySelector('.simms-product-card__price');
      const priceText = (priceEl ? priceEl.textContent : '').replace(/[^0-9.]/g, '');
      card.dataset.searchPrice = String(parseFloat(priceText) || 0);
    });
  }

  bindEvents() {
    if (this.input) this.input.addEventListener('input', () => this.applyFilter());
    if (this.clearBtn) this.clearBtn.addEventListener('click', () => this.clearSearch());
    if (this.sortSelect) this.sortSelect.addEventListener('change', () => this.applySort());
  }

  applyFilter() {
    const q = (this.input && this.input.value ? this.input.value : '').trim().toLowerCase();
    let visible = 0;
    this.cards.forEach((card) => {
      const title = card.dataset.searchTitle || '';
      const match = !q || title.includes(q);
      card.hidden = !match;
      if (match) visible++;
    });
    this.updateStatus(visible, q);
    if (this.clearBtn) this.clearBtn.hidden = !q;
  }

  clearSearch() {
    if (!this.input) return;
    this.input.value = '';
    this.applyFilter();
    this.input.focus();
  }

  applySort() {
    if (!this.sortSelect) return;
    const value = this.sortSelect.value;
    const sorted = this.originalOrder.slice();

    if (value === 'title-ascending') {
      sorted.sort((a, b) => (a.dataset.searchTitle || '').localeCompare(b.dataset.searchTitle || ''));
    } else if (value === 'title-descending') {
      sorted.sort((a, b) => (b.dataset.searchTitle || '').localeCompare(a.dataset.searchTitle || ''));
    } else if (value === 'price-ascending') {
      sorted.sort((a, b) => parseFloat(a.dataset.searchPrice) - parseFloat(b.dataset.searchPrice));
    } else if (value === 'price-descending') {
      sorted.sort((a, b) => parseFloat(b.dataset.searchPrice) - parseFloat(a.dataset.searchPrice));
    }
    // 'manual' (Featured) keeps the original rendered order.

    sorted.forEach((card) => this.grid.appendChild(card));
  }

  updateStatus(visible, q) {
    if (!this.statusEl) return;
    const total = this.totalCount;
    if (q) {
      this.statusEl.textContent = `Showing ${visible} of ${total} products matching "${q}"`;
    } else {
      this.statusEl.textContent = `Showing ${total} of ${total} products`;
    }
  }
}

if (!customElements.get('collection-filter')) {
  customElements.define('collection-filter', CollectionFilter);
}
