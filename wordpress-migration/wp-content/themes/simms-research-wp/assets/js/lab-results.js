(function () {
  const search = document.querySelector('[data-lab-search]');
  const cards = Array.from(document.querySelectorAll('[data-lab-card]'));

  if (!search || !cards.length) {
    return;
  }

  search.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase();

    cards.forEach((card) => {
      const haystack = card.getAttribute('data-search') || '';
      card.hidden = query.length > 0 && !haystack.includes(query);
    });
  });
})();

