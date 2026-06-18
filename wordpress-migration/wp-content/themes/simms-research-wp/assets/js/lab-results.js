/* Lab results page interactions — ported from Shopify sections/lab-results-index.liquid.
   Defines the <lab-results-page> custom element: normalized multi-term search,
   #tests/{handle} hash routing for the in-page detail table, and the COA dialog. */

if (!customElements.get('lab-results-page')) {
    customElements.define(
      'lab-results-page',
      class LabResultsPage extends HTMLElement {
        connectedCallback() {
          this.sortAndCapBatches();
          this.grid       = this.querySelector('[data-lab-grid]');
          this.details    = this.querySelectorAll('[data-lab-detail]');
          this.searchInput = this.querySelector('[data-lab-search]');
          this.searchClear = this.querySelector('[data-lab-search-clear]');
          this.noResults  = this.querySelector('[data-lab-no-results]');
          this.dialog     = this.querySelector('[data-lab-dialog]');
          this.dialogFrame = this.querySelector('[data-lab-dialog-frame]');
          this.dialogTitle = this.querySelector('[data-lab-dialog-title]');
          this.cards      = this.grid ? Array.from(this.grid.querySelectorAll('[data-lab-card]')) : [];

          this.handleHashChange = () => this.applyHash();
          this.handleClick      = (e) => this.onClick(e);
          this.handleSearch     = () => this.scheduleSearch();
          this.handleKeydown    = (e) => { if (e.key === 'Escape' && this.dialog?.open) this.closeDialog(); };

          window.addEventListener('hashchange', this.handleHashChange);
          this.addEventListener('click', this.handleClick);
          this.searchInput?.addEventListener('input', this.handleSearch);
          this.dialog?.addEventListener('click', (e) => {
            if (e.target === this.dialog) this.closeDialog();
          });
          document.addEventListener('keydown', this.handleKeydown);

          this.applyHash();
          this.applySearch();
        }

        disconnectedCallback() {
          window.removeEventListener('hashchange', this.handleHashChange);
          this.removeEventListener('click', this.handleClick);
          this.searchInput?.removeEventListener('input', this.handleSearch);
          document.removeEventListener('keydown', this.handleKeydown);
        }

        onClick(event) {
          const coaTrigger = event.target.closest('[data-lab-coa]');
          if (coaTrigger) {
            event.preventDefault();
            this.openDialog(coaTrigger.dataset.coaUrl, coaTrigger.dataset.coaTitle);
            return;
          }
          if (event.target.closest('[data-lab-dialog-close]')) {
            event.preventDefault();
            this.closeDialog();
            return;
          }
          if (event.target.closest('[data-lab-back]')) {
            event.preventDefault();
            this.clearHash();
            return;
          }
          if (event.target.closest('[data-lab-clear-search]')) {
            event.preventDefault();
            this.clearSearch();
            return;
          }
        }

        applyHash() {
          const hash = (window.location.hash || '').replace(/^#/, '');
          const match = hash.match(/^tests\/(.+)$/);
          const handle = match ? match[1] : null;

          let activeDetail = null;
          this.details.forEach((el) => {
            const isMatch = handle && el.dataset.productHandle === handle;
            el.hidden = !isMatch;
            if (isMatch) activeDetail = el;
          });

          if (activeDetail) {
            if (this.grid) this.grid.hidden = true;
            if (this.searchInput?.value) {
              this.searchInput.value = '';
              this.applySearch();
            } else if (this.noResults) {
              this.noResults.hidden = true;
            }
            // Scroll into view so user lands on the detail title, not the page top
            requestAnimationFrame(() => {
              activeDetail.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
          } else if (this.grid) {
            this.grid.hidden = false;
          }
        }

        clearHash() {
          if (window.location.hash) {
            // Use pushState so back-button restores the previous detail view
            history.pushState(null, '', window.location.pathname + window.location.search);
            this.applyHash();
          }
        }

        scheduleSearch() {
          if (this.searchTimer) cancelAnimationFrame(this.searchTimer);
          this.searchTimer = requestAnimationFrame(() => this.applySearch());
        }

        clearSearch() {
          if (!this.searchInput) return;
          this.searchInput.value = '';
          this.applySearch();
          this.searchInput.focus();
        }

        applySearch() {
          const raw = (this.searchInput?.value || '').trim().toLowerCase();
          const terms = raw.split(/\s+/).map((term) => this.normalizeSearchValue(term)).filter(Boolean);
          if (terms.length > 0 && /^#tests\//.test(window.location.hash || '')) {
            this.clearHash();
          }

          const cards = this.cards || [];
          let visible = 0;
          cards.forEach((card) => {
            const haystack = this.normalizeSearchValue(card.dataset.search || '');
            const matches = terms.length === 0 || terms.every((t) => haystack.includes(t));
            card.hidden = !matches;
            if (matches) visible++;
          });
          if (this.noResults) {
            this.noResults.hidden = (visible > 0 || terms.length === 0);
          }
          if (this.searchClear) this.searchClear.hidden = raw.length === 0;
        }

        normalizeSearchValue(value) {
          return value.toLowerCase().replace(/[^a-z0-9]+/g, '');
        }

        openDialog(url, title) {
          if (!this.dialog || !url) return;
          // iOS Safari (and several Android browsers) render PDFs in iframes
          // at the PDF's natural size from the top-left — the user sees a
          // zoomed-in corner of page 1. Hand off to the OS PDF viewer on
          // mobile instead of using the in-page dialog.
          const isMobile = window.matchMedia('(max-width: 749px)').matches;
          if (isMobile) {
            window.open(url, '_blank', 'noopener');
            return;
          }
          if (this.dialogFrame) this.dialogFrame.src = url;
          if (this.dialogTitle && title) this.dialogTitle.textContent = title;
          if (typeof this.dialog.showModal === 'function') {
            this.dialog.showModal();
            document.documentElement.setAttribute('scroll-lock', '');
          } else {
            window.open(url, '_blank', 'noopener');
          }
        }

        closeDialog() {
          if (!this.dialog) return;
          if (typeof this.dialog.close === 'function') this.dialog.close();
          document.documentElement.removeAttribute('scroll-lock');
          if (this.dialogFrame) this.dialogFrame.src = 'about:blank';
        }

        sortAndCapBatches() {
          const lists = this.querySelectorAll('[data-lab-card-rows]');
          lists.forEach((list) => {
            const rows = Array.from(list.querySelectorAll('.lab-card__row'));
            rows.sort((a, b) => {
              const da = a.dataset.testedAt || '';
              const db = b.dataset.testedAt || '';
              return db.localeCompare(da);
            });
            rows.forEach((row, i) => {
              row.style.display = i < 4 ? '' : 'none';
              list.appendChild(row);
            });
          });
        }
      }
    );
  }
