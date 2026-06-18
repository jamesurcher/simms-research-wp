(() => {
  const config = window.simmsCartDrawer || {};

  if (!config.ajaxUrl || !config.nonce) {
    return;
  }

  const couponKeys = ['dcode', 'discount_code'];
  const appliedKeyPrefix = 'simmsAffiliateCouponApplied:';
  const reloadKeyPrefix = 'simmsAffiliateCouponReloaded:';
  let pending = false;
  let scheduled = 0;

  function decode(value) {
    try {
      return decodeURIComponent(value.replace(/\+/g, ' '));
    } catch (error) {
      return value;
    }
  }

  function cookieValue(name) {
    const cookies = document.cookie ? document.cookie.split('; ') : [];

    for (const cookie of cookies) {
      const separator = cookie.indexOf('=');
      const key = separator >= 0 ? cookie.slice(0, separator) : cookie;

      if (decode(key) === name) {
        return decode(separator >= 0 ? cookie.slice(separator + 1) : '');
      }
    }

    return '';
  }

  function storageValue(name) {
    try {
      return window.localStorage.getItem(name) || '';
    } catch (error) {
      return '';
    }
  }

  function couponCode() {
    for (const key of couponKeys) {
      const value = (cookieValue(key) || storageValue(key)).trim();

      if (value) {
        return value;
      }
    }

    return '';
  }

  function storageGet(key) {
    try {
      return window.sessionStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function storageSet(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch (error) {}
  }

  function cartHasItems() {
    const countNode = document.querySelector('[data-simms-cart-count], [data-simms-cart-heading-count]');
    const count = Number.parseInt(countNode?.textContent || '0', 10);

    return Number.isFinite(count) && count > 0;
  }

  function replaceFragments(fragments) {
    if (!fragments) {
      return;
    }

    Object.entries(fragments).forEach(([selector, markup]) => {
      document.querySelectorAll(selector).forEach((node) => {
        const template = document.createElement('template');
        template.innerHTML = String(markup).trim();
        const replacement = template.content.firstElementChild;

        if (replacement) {
          node.replaceWith(replacement);
        }
      });
    });
  }

  function renderNotices(notices) {
    const target = document.querySelector('[data-simms-cart-notices]');

    if (target && notices) {
      target.innerHTML = notices;
    }
  }

  function isBlockCartPage() {
    return /^\/(?:cart|checkout)\/?$/.test(window.location.pathname);
  }

  function refreshBlockCart(code) {
    if (!isBlockCartPage()) {
      return;
    }

    const key = reloadKeyPrefix + code;

    if (storageGet(key)) {
      return;
    }

    storageSet(key, '1');
    window.location.reload();
  }

  async function applyAffiliateCoupon() {
    if (pending) {
      return;
    }

    const code = couponCode();

    if (!code || storageGet(appliedKeyPrefix + code)) {
      return;
    }

    if (!cartHasItems() && !isBlockCartPage()) {
      return;
    }

    const formData = new FormData();
    formData.set('action', 'simms_cart_drawer_apply_coupon');
    formData.set('nonce', config.nonce);
    formData.set('coupon_code', code);

    pending = true;

    try {
      const response = await fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      });
      const payload = await response.json();
      const body = payload.data || {};

      replaceFragments(body.fragments);
      renderNotices(body.notices);

      if (payload.success) {
        storageSet(appliedKeyPrefix + code, '1');
        refreshBlockCart(code);
      }
    } catch (error) {
    } finally {
      pending = false;
    }
  }

  function scheduleApply(delay = 250) {
    window.setTimeout(applyAffiliateCoupon, delay);
  }

  function scheduleInitialChecks() {
    [250, 1000, 2500, 5000].forEach(scheduleApply);
  }

  window.addEventListener('goaffproCookieSet', (event) => {
    const key = event?.detail?.key;

    if (couponKeys.includes(key)) {
      scheduleApply(100);
    }
  });
  window.addEventListener('goaffproVisitTracked', () => scheduleApply(100));
  window.addEventListener('goaffproScriptLoaded', () => scheduleApply(300));

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-simms-add-to-cart], .ajax_add_to_cart, .single_add_to_cart_button')) {
      scheduleApply(1500);
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    scheduleInitialChecks();

    const cartCount = document.querySelector('[data-simms-cart-count]');
    if (!cartCount) {
      return;
    }

    new MutationObserver(() => {
      if (scheduled) {
        window.clearTimeout(scheduled);
      }

      scheduled = window.setTimeout(applyAffiliateCoupon, 300);
    }).observe(cartCount, { childList: true, subtree: true, attributes: true });
  });
})();
