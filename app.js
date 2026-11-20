document.addEventListener('DOMContentLoaded', () => {
  const rows = document.getElementById('productRows');
  const add = document.getElementById('addProduct');
  if (rows && add) {
    add.addEventListener('click', () => {
      const first = rows.querySelector('.product-row');
      const clone = first.cloneNode(true);
      clone.querySelector('select').value = '';
      clone.querySelector('input').value = '1';
      rows.appendChild(clone);
    });
    rows.addEventListener('click', event => {
      if (event.target.classList.contains('remove-product')) {
        const all = rows.querySelectorAll('.product-row');
        if (all.length > 1) event.target.closest('.product-row').remove();
      }
    });
  }
  document.querySelectorAll('input[name="estado"]').forEach(el => {
    el.addEventListener('input', () => el.value = el.value.toUpperCase().slice(0,2));
  });
});
