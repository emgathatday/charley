function engineerManagementReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
    return;
  }

  callback();
}

function engineerSelectedRows() {
  return document.querySelectorAll('.row-check:checked').length;
}

function updateEngineerBulkBar() {
  const checked = engineerSelectedRows();
  const bar = document.getElementById('bulkBar');
  const count = document.getElementById('bulkCount');

  if (!bar || !count) return;

  if (checked > 0) {
    bar.classList.add('show');
    count.textContent = `${checked} user${checked > 1 ? 's' : ''} selected`;
    return;
  }

  bar.classList.remove('show');
}

function clearEngineerSelection() {
  document.querySelectorAll('.row-check').forEach((checkbox) => {
    checkbox.checked = false;
  });

  const selectAll = document.getElementById('selectAll');
  if (selectAll) selectAll.checked = false;

  updateEngineerBulkBar();
}

function closeEngineerDrawer() {
  document.getElementById('drawerOverlay')?.classList.remove('show');
  document.getElementById('detailDrawer')?.classList.remove('open');
}

function openEngineerFreezeModal() {
  document.getElementById('freezeModal')?.classList.add('show');
}

function closeEngineerFreezeModal() {
  document.getElementById('freezeModal')?.classList.remove('show');
}

engineerManagementReady(() => {
  document.querySelectorAll('.js-auto-submit').forEach((input) => {
    input.addEventListener('change', () => input.form?.submit());
  });

  document.getElementById('selectAll')?.addEventListener('change', (event) => {
    document.querySelectorAll('.row-check').forEach((checkbox) => {
      checkbox.checked = event.currentTarget.checked;
    });
    updateEngineerBulkBar();
  });

  document.querySelectorAll('.row-check').forEach((checkbox) => {
    checkbox.addEventListener('change', updateEngineerBulkBar);
  });

  document.querySelectorAll('[data-clear-selection]').forEach((button) => {
    button.addEventListener('click', clearEngineerSelection);
  });

  document.querySelectorAll('[data-drawer-close]').forEach((button) => {
    button.addEventListener('click', closeEngineerDrawer);
  });

  document.querySelectorAll('[data-freeze-open]').forEach((button) => {
    button.addEventListener('click', openEngineerFreezeModal);
  });

  document.querySelectorAll('[data-freeze-close], [data-freeze-confirm]').forEach((button) => {
    button.addEventListener('click', closeEngineerFreezeModal);
  });
});
