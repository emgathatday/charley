function administratorManagementReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
    return;
  }

  callback();
}

function updateAdministratorBulkBar() {
  const checked = document.querySelectorAll('.row-check:checked').length;
  const bar = document.getElementById('bulkBar');
  const count = document.getElementById('bulkCount');

  if (!bar || !count) return;

  if (checked > 0) {
    bar.classList.add('show');
    count.textContent = `${checked} operator${checked > 1 ? 's' : ''} selected`;
    return;
  }

  bar.classList.remove('show');
}

function clearAdministratorSelection() {
  document.querySelectorAll('.row-check').forEach((checkbox) => {
    checkbox.checked = false;
  });

  const selectAll = document.getElementById('selectAll');
  if (selectAll) selectAll.checked = false;

  updateAdministratorBulkBar();
}

function openAdministratorFreezeModal() {
  document.getElementById('freezeModal')?.classList.add('show');
}

function closeAdministratorFreezeModal() {
  document.getElementById('freezeModal')?.classList.remove('show');
}

administratorManagementReady(() => {
  document.querySelectorAll('.js-auto-submit').forEach((input) => {
    input.addEventListener('change', () => input.form?.submit());
  });

  document.getElementById('selectAll')?.addEventListener('change', (event) => {
    document.querySelectorAll('.row-check').forEach((checkbox) => {
      checkbox.checked = event.currentTarget.checked;
    });
    updateAdministratorBulkBar();
  });

  document.querySelectorAll('.row-check').forEach((checkbox) => {
    checkbox.addEventListener('change', updateAdministratorBulkBar);
  });

  document.querySelectorAll('[data-clear-selection]').forEach((button) => {
    button.addEventListener('click', clearAdministratorSelection);
  });

  document.querySelectorAll('[data-freeze-open]').forEach((button) => {
    button.addEventListener('click', openAdministratorFreezeModal);
  });

  document.querySelectorAll('[data-freeze-close], [data-freeze-confirm]').forEach((button) => {
    button.addEventListener('click', closeAdministratorFreezeModal);
  });
});
