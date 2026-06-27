(() => {
  const root = document.querySelector('.pdp');

  if (!root) {
    return;
  }

  const form = root.querySelector('[data-pdp-form]');
  const quantityInput = root.querySelector('[data-pdp-quantity]');
  const price = root.querySelector('[data-pdp-price]');
  const sticky = root.querySelector('[data-pdp-sticky-cart]');
  const stickyVariant = root.querySelector('[data-pdp-sticky-variant]');
  const stickyPrice = root.querySelector('[data-pdp-sticky-price]');
  const submitButton = root.querySelector('[data-pdp-submit]');
  const submitText = root.querySelector('[data-pdp-submit-text]');
  const submitIcon = root.querySelector('[data-pdp-submit-icon]');
  const stockStatus = root.querySelector('[data-pdp-stock-status]');
  const stockLabel = root.querySelector('[data-pdp-stock-label]');
  const stockDetail = root.querySelector('[data-pdp-stock-detail]');
  const stockSeparator = root.querySelector('[data-pdp-stock-separator]');
  const expressButton = root.querySelector('[data-pdp-express]');
  const paymentOptions = root.querySelector('[data-pdp-payment-options]');
  const stickySubmitButton = root.querySelector('[data-pdp-sticky-submit]');
  const stickySubmitText = root.querySelector('[data-pdp-sticky-submit-text]');
  const stickySubmitIcon = root.querySelector('[data-pdp-sticky-icon]');
  const config = window.simmsCartDrawer || {};
  let currentAvailable = form?.dataset.pdpAvailable !== 'false';

  function setElementHidden(element, isHidden) {
    if (!element) return;
    element.hidden = isHidden;
  }

  function parsePayload(text) {
    try {
      const value = JSON.parse(text);
      return value && typeof value === 'object' ? value : null;
    } catch (_error) {
      return null;
    }
  }

  function sendCart(data) {
    data.set('nonce', config.nonce);

    return fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
    });
  }

  async function refreshNonce() {
    try {
      const data = new FormData();
      data.set('action', 'simms_cart_drawer_nonce');

      const response = await fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      });
      const payload = parsePayload(await response.text());
      const nonce = payload && payload.success ? payload.data?.nonce : null;

      if (nonce) {
        config.nonce = nonce;
        return true;
      }
    } catch (_error) {
      // Fall back to the localized nonce and regular product form submit.
    }

    return false;
  }

  async function postExpressAdd(data) {
    let response = await sendCart(data);
    let payload = parsePayload(await response.text());

    // A stale WordPress nonce returns "-1"/403 instead of JSON. Rebind to the
    // live WooCommerce session and retry once; the rejected request dies before
    // the cart is touched, so this cannot double-add.
    if (!payload && (await refreshNonce())) {
      response = await sendCart(data);
      payload = parsePayload(await response.text());
    }

    return payload;
  }

  function setQuantity(value) {
    if (!quantityInput) return;

    const min = Number.parseInt(quantityInput.min || '1', 10) || 1;
    const next = Math.max(min, Number.parseInt(value, 10) || min);
    quantityInput.value = String(next);
    syncBundleState(next);
  }

  function syncBundleState(quantity = Number.parseInt(quantityInput?.value || '1', 10) || 1) {
    const tiers = [...root.querySelectorAll('[data-pdp-bundle-tier]')];

    if (!tiers.length) return;

    let active = tiers[0];

    tiers.forEach((tier) => {
      const tierQuantity = Number.parseInt(tier.dataset.pdpBundleTier || '1', 10) || 1;

      if (quantity >= tierQuantity) {
        active = tier;
      }
    });

    tiers.forEach((tier) => {
      const isActive = tier === active;
      tier.classList.toggle('is-active', isActive);
      tier.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  function setPurchaseState(isAvailable) {
    currentAvailable = isAvailable;

    if (form) {
      form.dataset.pdpAvailable = isAvailable ? 'true' : 'false';
      form.classList.toggle('is-sold-out', !isAvailable);
    }

    stockStatus?.classList.toggle('is-in-stock', isAvailable);
    stockStatus?.classList.toggle('is-out-of-stock', !isAvailable);

    if (stockLabel) {
      stockLabel.textContent = isAvailable
        ? form?.dataset.inStockLabel || 'In stock'
        : form?.dataset.outOfStockLabel || 'Out of stock';
    }

    if (stockDetail) {
      stockDetail.textContent = isAvailable ? form?.dataset.readyLabel || 'Ready to ship' : '';
    }

    setElementHidden(stockDetail, !isAvailable);
    setElementHidden(stockSeparator, !isAvailable);

    if (submitButton) {
      submitButton.disabled = !isAvailable;
      submitButton.setAttribute('aria-disabled', isAvailable ? 'false' : 'true');
    }

    if (submitText && submitButton) {
      submitText.textContent = isAvailable
        ? submitButton.dataset.addLabel || 'Add to cart'
        : submitButton.dataset.soldOutLabel || 'Sold out';
    }

    setElementHidden(submitIcon, !isAvailable);
    setElementHidden(expressButton, !isAvailable);
    setElementHidden(paymentOptions, !isAvailable);

    if (stickySubmitButton) {
      stickySubmitButton.disabled = !isAvailable;
      stickySubmitButton.setAttribute('aria-disabled', isAvailable ? 'false' : 'true');
    }

    if (stickySubmitText && stickySubmitButton) {
      stickySubmitText.textContent = isAvailable
        ? stickySubmitButton.dataset.addLabel || 'Add to cart'
        : stickySubmitButton.dataset.soldOutLabel || 'Sold out';
    }

    setElementHidden(stickySubmitIcon, !isAvailable);

    if (!isAvailable) {
      setStickyVisible(false);
    }
  }

  function ensureAttributeInput(name) {
    let input = form?.querySelector(`[data-pdp-attribute="${CSS.escape(name)}"]`);

    if (!input && form) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.dataset.pdpAttribute = name;
      form.append(input);
    }

    return input;
  }

  function syncCoa(variantKey) {
    const cards = [...root.querySelectorAll('[data-pdp-coa-key]')];

    if (!cards.length) return;

    const selected = cards.find((card) => card.dataset.pdpCoaKey === variantKey) || cards[0];

    cards.forEach((card) => {
      const isActive = card === selected;
      card.classList.toggle('is-active', isActive);
      card.hidden = !isActive;
    });
  }

  function selectVariant(button) {
    if (!button) return;

    root.querySelectorAll('[data-pdp-variant]').forEach((variantButton) => {
      const isActive = variantButton === button;
      variantButton.classList.toggle('is-active', isActive);
      variantButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    const variationInput = root.querySelector('[data-pdp-variation-id]');
    if (variationInput) {
      variationInput.value = button.dataset.variationId || '';
    }

    try {
      const attributes = JSON.parse(button.dataset.attributes || '{}');
      Object.entries(attributes).forEach(([name, value]) => {
        const input = ensureAttributeInput(name);
        if (input) input.value = value;
      });
    } catch (_error) {
      // Malformed variation metadata should not block the visible selection.
    }

    if (price && button.dataset.price) {
      price.innerHTML = button.dataset.price;
    }

    if (stickyVariant) {
      stickyVariant.textContent = button.dataset.variantLabel || '';
    }

    if (stickyPrice && button.dataset.priceText) {
      stickyPrice.textContent = button.dataset.priceText;
    }

    setPurchaseState(button.dataset.available !== 'false');
    syncCoa(button.dataset.variantKey || '');
  }

  async function addThenCheckout(button) {
    if (!currentAvailable) {
      return;
    }

    if (!form || !config.ajaxUrl || !config.nonce || !config.checkoutUrl) {
      form?.requestSubmit?.(submitButton);
      return;
    }

    const formData = new FormData(form);

    if (submitButton?.name && submitButton.value && !formData.has(submitButton.name)) {
      formData.append(submitButton.name, submitButton.value);
    }

    const legacyId = formData.get('add-to-cart');
    if (legacyId && !formData.has('product_id')) {
      formData.append('product_id', legacyId);
    }
    formData.delete('add-to-cart');
    formData.set('action', 'simms_cart_drawer_add');
    // Buy-now semantics: the server skips the add if this product is already in
    // the cart, so clicking PayPal never duplicates a line.
    formData.set('express', '1');

    button?.classList.add('is-loading');
    button?.setAttribute('aria-disabled', 'true');

    try {
      const payload = await postExpressAdd(formData);

      if (payload?.success) {
        window.location.href = config.checkoutUrl;
        return;
      }

      form.requestSubmit?.(submitButton);
    } finally {
      button?.classList.remove('is-loading');
      button?.removeAttribute('aria-disabled');
    }
  }

  root.addEventListener('click', (event) => {
    const variantButton = event.target.closest('[data-pdp-variant]');
    if (variantButton) {
      event.preventDefault();
      selectVariant(variantButton);
      return;
    }

    const stepButton = event.target.closest('[data-pdp-qty-step]');
    if (stepButton) {
      event.preventDefault();
      const step = Number.parseInt(stepButton.dataset.pdpQtyStep || '0', 10) || 0;
      setQuantity((Number.parseInt(quantityInput?.value || '1', 10) || 1) + step);
      return;
    }

    const bundleButton = event.target.closest('[data-pdp-bundle-tier]');
    if (bundleButton) {
      event.preventDefault();
      setQuantity(bundleButton.dataset.pdpBundleTier || '1');
      return;
    }

    const expressButton = event.target.closest('[data-pdp-express]');
    if (expressButton) {
      event.preventDefault();
      addThenCheckout(expressButton);
      return;
    }

    const stickySubmit = event.target.closest('[data-pdp-sticky-submit]');
    if (stickySubmit) {
      event.preventDefault();
      if (!currentAvailable) {
        return;
      }
      if (form?.requestSubmit) {
        form.requestSubmit(submitButton);
      } else {
        submitButton?.click();
      }
    }
  });

  form?.addEventListener('submit', (event) => {
    if (!currentAvailable) {
      event.preventDefault();
    }
  });

  quantityInput?.addEventListener('change', () => setQuantity(quantityInput.value));
  quantityInput?.addEventListener('input', () => syncBundleState());

  function setStickyVisible(isVisible) {
    if (!sticky) return;

    const shouldShow = isVisible && currentAvailable;

    sticky.classList.toggle('is-visible', shouldShow);
    sticky.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
  }

  if (sticky && submitButton && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      ([entry]) => {
        setStickyVisible(!entry.isIntersecting && window.scrollY > 240);
      },
      { threshold: 0.1 }
    );
    observer.observe(submitButton);
  } else if (sticky) {
    window.addEventListener('scroll', () => setStickyVisible(window.scrollY > 640), { passive: true });
  }

  syncBundleState();
  const activeVariant = root.querySelector('[data-pdp-variant].is-active');
  setPurchaseState(
    activeVariant ? activeVariant.dataset.available !== 'false' : currentAvailable
  );
  // Prefer the active variant's key; for products with no variant picker (simple
  // products) fall back to the COA card the server already marked active rather
  // than cards[0], which may belong to a different dosage (e.g. GLP-3's 20mg batch).
  syncCoa(
    root.querySelector('[data-pdp-variant].is-active')?.dataset.variantKey
      || root.querySelector('[data-pdp-coa-key].is-active')?.dataset.pdpCoaKey
      || ''
  );
})();
