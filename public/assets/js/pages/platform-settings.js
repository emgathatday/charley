(function () {
  var PlatformSettingsPage = {
    // Khoi tao cac tuong tac rieng cua Platform Settings.
    init: function () {
      this.bindSettingsTabs();
      this.bindDirtyFields();
      this.bindPillChecks();
      this.bindEscapeKey();
    },

    // Gan click cho sidebar tab bang data-tab, khong dung inline onclick.
    bindSettingsTabs: function () {
      document.querySelectorAll('.settings-nav-item[data-tab]').forEach(function (item) {
        item.addEventListener('click', function () {
          PlatformSettingsPage.setSettingsTab(item, item.dataset.tab);
        });
      });
    },

    // Chuyen tab settings va hien section tuong ung.
    setSettingsTab: function (activeItem, tabId) {
      var targetSection = document.getElementById('settings-' + tabId);
      if (!activeItem || !targetSection) return;

      document.querySelectorAll('.settings-nav-item').forEach(function (item) {
        item.classList.remove('active');
      });

      document.querySelectorAll('.settings-section').forEach(function (section) {
        section.classList.remove('active');
      });

      activeItem.classList.add('active');
      targetSection.classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Mo modal nguy hiem trong Danger Zone.
    openModal: function (id) {
      var overlay = document.getElementById('modalOverlay');
      var target = document.getElementById('modal-' + id);

      document.querySelectorAll('.modal-box').forEach(function (box) {
        box.classList.remove('active');
      });

      if (target) target.classList.add('active');
      if (overlay) overlay.classList.add('show');
      document.body.style.overflow = 'hidden';
    },

    // Dong modal platform settings.
    closeModal: function () {
      var overlay = document.getElementById('modalOverlay');

      if (overlay) overlay.classList.remove('show');
      document.querySelectorAll('.modal-box.active').forEach(function (box) {
        box.classList.remove('active');
      });
      document.body.style.overflow = '';
    },

    // Dong modal khi click vao overlay.
    closeModalOnOverlay: function (event) {
      if (event.target && event.target.id === 'modalOverlay') {
        this.closeModal();
      }
    },

    // Dong modal khi bam Escape.
    bindEscapeKey: function () {
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          PlatformSettingsPage.closeModal();
        }
      });
    },

    // Gan dirty state cho input/select/textarea trong settings content.
    bindDirtyFields: function () {
      document.querySelectorAll('.settings-content input, .settings-content select, .settings-content textarea').forEach(function (field) {
        field.addEventListener('input', function () {
          PlatformSettingsPage.markDirty();
        });
        field.addEventListener('change', function () {
          PlatformSettingsPage.markDirty();
        });
      });
    },

    // Hien save bar khi co thay doi.
    markDirty: function () {
      var saveBar = document.getElementById('saveBar');

      if (saveBar) saveBar.classList.add('dirty');
    },

    // Cap nhat visual checked cho pill checkbox.
    bindPillChecks: function () {
      document.querySelectorAll('.pill-check').forEach(function (pill) {
        pill.addEventListener('click', function () {
          window.setTimeout(function () {
            var input = pill.querySelector('input');
            if (input) pill.classList.toggle('checked', input.checked);
          }, 0);
        });
      });
    },

    // Luu thay doi demo va an save bar.
    saveChanges: function () {
      this.clearDirty();
    },

    // Huy thay doi demo va an save bar.
    discardChanges: function () {
      this.clearDirty();
    },

    // An save bar.
    clearDirty: function () {
      var saveBar = document.getElementById('saveBar');

      if (saveBar) saveBar.classList.remove('dirty');
    }
  };

  window.PlatformSettingsPage = PlatformSettingsPage;

  // Alias cho inline handler con lai trong HTML output.
  window.openModal = function (id) { PlatformSettingsPage.openModal(id); };
  window.closeModal = function () { PlatformSettingsPage.closeModal(); };
  window.closeModalOnOverlay = function (event) { PlatformSettingsPage.closeModalOnOverlay(event); };
  window.markDirty = function () { PlatformSettingsPage.markDirty(); };
  window.saveChanges = function () { PlatformSettingsPage.saveChanges(); };
  window.discardChanges = function () { PlatformSettingsPage.discardChanges(); };

  document.addEventListener('DOMContentLoaded', function () {
    PlatformSettingsPage.init();
  });
})();
