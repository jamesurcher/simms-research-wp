(() => {
  const config = window.simmsCartDrawer || {};

  if (!config.ajaxUrl || !config.nonce) {
    return;
  }

  const selectors = {
    drawer: '[data-simms-cart-drawer]',
    drawerContent: '[data-simms-cart-drawer-content]',
    notices: '[data-simms-cart-notices]',
    open: '[data-simms-cart-open]',
    close: '[data-simms-cart-close]',
    addToCart: '[data-simms-add-to-cart]',
    addon: '[data-simms-cart-addon]',
    addonDismiss: '[data-simms-cart-addon-dismiss]',
    qtyButton: '[data-simms-cart-qty]',
    qtyInput: '[data-simms-cart-qty-input]',
    couponForm: '[data-simms-cart-coupon]',
    removeCoupon: '[data-simms-remove-coupon]',
  };

  const addonDismissedKey = 'simmsCartReconWaterDismissed';
  let lastFocus = null;
  let pending = false;

  function drawer() {
    return document.querySelector(selectors.drawer);
  }

  function openDrawer() {
    const el = drawer();

    if (!el) {
      return;
    }

    lastFocus = document.activeElement;
    el.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('simms-cart-drawer-open');
    document.body.classList.add('simms-cart-drawer-open');

    requestAnimationFrame(() => {
      el.classList.add('is-open');
      el.querySelector(selectors.close)?.focus({ preventScroll: true });
    });
  }

  function closeDrawer() {
    const el = drawer();

    if (!el) {
      return;
    }

    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('simms-cart-drawer-open');
    document.body.classList.remove('simms-cart-drawer-open');

    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus({ preventScroll: true });
    }
  }

  function setBusy(isBusy) {
    pending = isBusy;
    drawer()?.classList.toggle('is-loading', isBusy);
  }

  // Brief bounce on the header cart icon to register that an item was added,
  // without opening the drawer.
  function bumpCart() {
    document.querySelectorAll(selectors.open).forEach((cart) => {
      cart.classList.remove('is-bumped');
      void cart.offsetWidth;
      cart.classList.add('is-bumped');
      setTimeout(() => cart.classList.remove('is-bumped'), 600);
    });
  }

  // Quick affirmative pulse on the tapped add-to-cart button so the user sees
  // their tap register immediately, before the cart request comes back.
  function flashTap(button) {
    if (!button) {
      return;
    }

    button.classList.remove('is-tapped');
    void button.offsetWidth; // restart the animation on a rapid re-tap
    button.classList.add('is-tapped');
    setTimeout(() => button.classList.remove('is-tapped'), 450);
  }

  function renderNotices(notices) {
    const target = document.querySelector(selectors.notices);

    if (!target) {
      return;
    }

    target.innerHTML = notices || '';
  }

  function isAddonDismissed() {
    try {
      return window.sessionStorage?.getItem(addonDismissedKey) === '1';
    } catch (error) {
      return false;
    }
  }

  function setAddonDismissed() {
    try {
      window.sessionStorage?.setItem(addonDismissedKey, '1');
    } catch (error) {
      // Removing the current card is enough when storage is unavailable.
    }
  }

  function hideDismissedAddon() {
    if (!isAddonDismissed()) {
      return;
    }

    document.querySelectorAll(selectors.addon).forEach((addon) => addon.remove());
  }

  function dismissAddon(addon) {
    if (!addon) {
      return;
    }

    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
      addon.remove();
      return;
    }

    addon.style.height = `${addon.getBoundingClientRect().height}px`;
    addon.style.boxSizing = 'border-box';
    void addon.offsetHeight;
    addon.classList.add('is-dismissing');
    setTimeout(() => addon.remove(), 220);
  }

  function replaceFragments(fragments) {
    if (!fragments) {
      return;
    }

    const wasOpen = drawer()?.classList.contains('is-open');

    Object.entries(fragments).forEach(([selector, markup]) => {
      document.querySelectorAll(selector).forEach((node) => {
        const template = document.createElement('template');
        template.innerHTML = markup.trim();
        const replacement = template.content.firstElementChild;

        if (replacement) {
          node.replaceWith(replacement);
        }
      });
    });

    if (wasOpen) {
      requestAnimationFrame(() => {
        hideDismissedAddon();
        drawer()?.querySelector('.simms-cart-drawer__items')?.scrollTo({ top: 0, left: 0 });
      });
    } else {
      hideDismissedAddon();
    }
  }

  function parsePayload(text) {
    try {
      const value = JSON.parse(text);
      return value && typeof value === 'object' ? value : null;
    } catch (error) {
      return null;
    }
  }

  function sendCart(action, data) {
    data.set('action', action);
    data.set('nonce', config.nonce);

    return fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
    });
  }

  // Mint a nonce bound to the live session. admin-ajax is never cached, so this
  // recovers from a stale nonce that was baked into a full-page-cached document.
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
    } catch (error) {
      // Fall back to the existing nonce.
    }

    return false;
  }

  async function postCart(action, data = new FormData()) {
    if (!(data instanceof FormData)) {
      const formData = new FormData();
      Object.entries(data).forEach(([key, value]) => formData.append(key, value));
      data = formData;
    }

    setBusy(true);

    try {
      let response = await sendCart(action, data);
      let payload = parsePayload(await response.text());

      // A bad nonce makes WooCommerce reply with "-1"/403, which is not a JSON
      // object. That is what breaks add-to-cart on the homepage with an empty
      // cart: the page is served from full-page cache with a stale nonce, while
      // a non-empty cart bypasses the cache and renders a fresh one. Rebind the
      // nonce to the live session and retry the request once. The rejected
      // request dies before WooCommerce touches the cart, so this never
      // double-adds.
      if (!payload && (await refreshNonce())) {
        response = await sendCart(action, data);
        payload = parsePayload(await response.text());
      }

      if (!payload) {
        renderNotices(
          '<div class="woocommerce-error" role="alert">' +
            (config.errorText || 'Something went wrong. Please refresh and try again.') +
            '</div>'
        );
        openDrawer();
        return { success: false };
      }

      const body = payload.data || {};

      replaceFragments(body.fragments);
      renderNotices(body.notices);

      if (!payload.success) {
        openDrawer();
      }

      return payload;
    } finally {
      setBusy(false);
    }
  }

  async function refreshDrawer() {
    await postCart('simms_cart_drawer_refresh');
  }

  function setButtonLoading(button, isLoading) {
    if (!button) {
      return;
    }

    button.classList.toggle('is-loading', isLoading);
    button.toggleAttribute('aria-disabled', isLoading);
  }

  // Surface the just-added line so the tracking layer can fire AddToCart.
  function notifyAdded(payload) {
    const added = payload?.data?.added_item;

    if (added) {
      document.dispatchEvent(new CustomEvent('simms:added-to-cart', { detail: added }));
    }
  }

  async function addCardProduct(button) {
    const productId = button.getAttribute('data-product_id') || button.dataset.productId;

    if (!productId || pending) {
      return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', button.dataset.quantity || '1');

    // Instant, optimistic acknowledgement that the tap landed — fired before the
    // request resolves so the press reads as a tap, not a hold. The add runs in
    // the background; the header cart bump confirms it actually landed, and the
    // module-level `pending` flag blocks double-taps for the full round trip.
    flashTap(button);

    const payload = await postCart('simms_cart_drawer_add', formData);
    if (payload?.success) {
      bumpCart();
      notifyAdded(payload);
    }
  }

  async function addProductForm(form, submitter) {
    if (pending) {
      return;
    }

    const formData = new FormData(form);

    // Simple products carry the product id on the submit button as `add-to-cart`,
    // which FormData(form) omits, so forward the submitter value here.
    if (submitter?.name && submitter.value && !formData.has(submitter.name)) {
      formData.append(submitter.name, submitter.value);
    }

    // WooCommerce's own add-to-cart handler runs on every request (including
    // admin-ajax) whenever it sees an `add-to-cart` param, which would add the
    // product a second time. Send the id as `product_id` and strip add-to-cart
    // so only our AJAX handler touches the cart.
    const legacyId = formData.get('add-to-cart');
    if (legacyId && !formData.has('product_id')) {
      formData.append('product_id', legacyId);
    }
    formData.delete('add-to-cart');

    setButtonLoading(submitter, true);

    try {
      const payload = await postCart('simms_cart_drawer_add', formData);
      if (payload?.success) {
        bumpCart();
        notifyAdded(payload);
      }
    } finally {
      setButtonLoading(submitter, false);
    }
  }

  async function updateQuantity(key, quantity) {
    if (!key || pending) {
      return;
    }

    await postCart('simms_cart_drawer_update', {
      cart_item_key: key,
      quantity: String(Math.max(0, Number.parseInt(quantity, 10) || 0)),
    });
  }

  document.addEventListener('click', (event) => {
    const openButton = event.target.closest(selectors.open);

    if (openButton) {
      event.preventDefault();
      openDrawer();
      refreshDrawer();
      return;
    }

    if (event.target.closest(selectors.close)) {
      event.preventDefault();
      closeDrawer();
      return;
    }

    const addonDismiss = event.target.closest(selectors.addonDismiss);

    if (addonDismiss) {
      event.preventDefault();
      setAddonDismissed();
      addonDismiss.disabled = true;
      dismissAddon(addonDismiss.closest(selectors.addon));
      return;
    }

    const addButton = event.target.closest(selectors.addToCart);

    if (addButton) {
      event.preventDefault();
      addCardProduct(addButton);
      return;
    }

    const quantityButton = event.target.closest(selectors.qtyButton);

    if (quantityButton) {
      event.preventDefault();
      updateQuantity(quantityButton.dataset.simmsCartQty, quantityButton.dataset.quantity);
      return;
    }

    const removeCoupon = event.target.closest(selectors.removeCoupon);

    if (removeCoupon) {
      event.preventDefault();
      postCart('simms_cart_drawer_remove_coupon', {
        coupon_code: removeCoupon.dataset.simmsRemoveCoupon || '',
      });
    }
  });

  document.addEventListener('change', (event) => {
    const input = event.target.closest(selectors.qtyInput);

    if (!input) {
      return;
    }

    updateQuantity(input.dataset.simmsCartQtyInput, input.value);
  });

  document.addEventListener(
    'submit',
    (event) => {
      const couponForm = event.target.closest(selectors.couponForm);

      if (couponForm) {
        event.preventDefault();
        postCart('simms_cart_drawer_apply_coupon', new FormData(couponForm));
        return;
      }

      const productForm = event.target.closest('.pdp__cart form.cart');

      if (!productForm) {
        return;
      }

      event.preventDefault();
      addProductForm(productForm, event.submitter || productForm.querySelector('[type="submit"]'));
    },
    true
  );

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer()?.classList.contains('is-open')) {
      closeDrawer();
    }
  });

  hideDismissedAddon();
})();
