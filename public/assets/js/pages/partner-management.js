function partnerManagementReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
    return;
  }

  callback();
}

function openPartnerDrawer(button) {
  const row = button.closest('tr');
  const name = row?.querySelector('.company-name')?.textContent || 'Partner Review';
  const meta = row?.querySelector('.company-meta')?.textContent || '-';

  document.getElementById('drawerCompanyName').textContent = name;
  document.getElementById('drawerCompanyMeta').textContent = meta;
  document.getElementById('reviewDrawer')?.classList.add('show');
  document.getElementById('drawerOverlay')?.classList.add('show');
}

function closePartnerDrawer() {
  document.getElementById('reviewDrawer')?.classList.remove('show');
  document.getElementById('drawerOverlay')?.classList.remove('show');
}

function updatePartnerSelectAllState() {
  const selectAll = document.getElementById('selectAllCheckbox');
  if (!selectAll) return;

  const rows = Array.from(document.querySelectorAll('.row-check'));
  selectAll.checked = rows.length > 0 && rows.every((checkbox) => checkbox.checked);
}

partnerManagementReady(() => {
  document.querySelectorAll('.js-auto-submit').forEach((input) => {
    input.addEventListener('change', () => input.form?.submit());
  });

  document.getElementById('selectAllCheckbox')?.addEventListener('change', (event) => {
    document.querySelectorAll('.row-check').forEach((checkbox) => {
      checkbox.checked = event.currentTarget.checked;
    });
  });

  document.querySelectorAll('.row-check').forEach((checkbox) => {
    checkbox.addEventListener('change', updatePartnerSelectAllState);
  });

  document.querySelectorAll('[data-drawer-open]').forEach((button) => {
    button.addEventListener('click', () => openPartnerDrawer(button));
  });

  document.querySelectorAll('[data-drawer-close]').forEach((button) => {
    button.addEventListener('click', closePartnerDrawer);
  });
});
