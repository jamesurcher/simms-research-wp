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
    qtyButton: '[data-simms-cart-qty]',
    qtyInput: '[data-simms-cart-qty-input]',
    couponForm: '[data-simms-cart-coupon]',
    removeCoupon: '[data-simms-remove-coupon]',
  };

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

  function renderNotices(notices) {
    const target = document.querySelector(selectors.notices);

    if (!target) {
      return;
    }

    target.innerHTML = notices || '';
  }

  function replaceFragments(fragments) {
    if (!fragments) {
      return;
    }

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
  }

  async function postCart(action, data = new FormData()) {
    if (!(data instanceof FormData)) {
      const formData = new FormData();
      Object.entries(data).forEach(([key, value]) => formData.append(key, value));
      data = formData;
    }

    data.set('action', action);
    data.set('nonce', config.nonce);

    setBusy(true);

    try {
      const response = await fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      });
      const payload = await response.json();
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

  async function addCardProduct(button) {
    const productId = button.getAttribute('data-product_id') || button.dataset.productId;

    if (!productId || pending) {
      return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', button.dataset.quantity || '1');

    setButtonLoading(button, true);

    try {
      const payload = await postCart('simms_cart_drawer_add', formData);
      if (payload?.success) {
        bumpCart();
      }
    } finally {
      setButtonLoading(button, false);
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
})();
