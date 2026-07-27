(function () {
  var CharleyLayout = {
    // Khoi tao cac su kien dung chung cho layout.
    init: function () {
      this.bindDocumentClick();
      this.bindEscapeKey();
    },

    // Bat click ngoai dropdown/modal de dong UI dang mo.
    bindDocumentClick: function () {
      document.addEventListener('click', function (event) {
        if (!event.target.closest('.dropdown-wrap')) {
          CharleyLayout.closeDropdowns();
        }

        if (event.target.classList.contains('modal-overlay')) {
          CharleyLayout.closeAllModals();
        }
      });
    },

    // Dong dropdown, modal, sidebar khi bam phim Escape.
    bindEscapeKey: function () {
      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        CharleyLayout.closeDropdowns();
        CharleyLayout.closeAllModals();
        CharleyLayout.closeSidebar();
      });
    },

    // Dong tat ca dropdown, tru dropdown duoc truyen vao.
    closeDropdowns: function (except) {
      document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
        if (menu !== except) {
          menu.classList.remove('show');
        }
      });
    },

    // Dong/mo dropdown topbar theo id menu.
    toggleDropdown: function (id) {
      var menu = document.getElementById(id);
      if (!menu) return;

      var shouldOpen = !menu.classList.contains('show');
      this.closeDropdowns(menu);
      menu.classList.toggle('show', shouldOpen);
    },

    // Dong/mo sidebar tren mobile.
    toggleSidebar: function () {
      var sidebar = document.querySelector('.sidebar');
      var overlay = document.getElementById('sidebarOverlay');

      if (sidebar) sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
    },

    // Dong sidebar mobile.
    closeSidebar: function () {
      var sidebar = document.querySelector('.sidebar');
      var overlay = document.getElementById('sidebarOverlay');

      if (sidebar) sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('show');
    },

    // Tim modal theo cac kieu id dang ton tai trong output.
    findModal: function (id) {
      return document.getElementById(id) ||
        document.getElementById('modal-' + id) ||
        document.getElementById(id + 'Modal');
    },

    // Mo modal va khoa scroll nen.
    openModal: function (id) {
      var modal = this.findModal(id);
      if (!modal) return;

      this.closeAllModals();
      modal.classList.add('show');
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    },

    // Dong mot modal theo id, neu khong co id thi dong tat ca.
    closeModal: function (id) {
      var modal = id ? this.findModal(id) : null;

      if (!modal) {
        this.closeAllModals();
        return;
      }

      modal.classList.remove('show');
      modal.classList.remove('active');
      this.unlockBodyWhenNoModal();
    },

    // Dong tat ca modal dang mo.
    closeAllModals: function () {
      document.querySelectorAll('.modal-overlay.show, .modal-box.active, .modal.show').forEach(function (modal) {
        modal.classList.remove('show');
        modal.classList.remove('active');
      });

      document.body.style.overflow = '';
    },

    // Mo lai scroll body neu khong con modal nao.
    unlockBodyWhenNoModal: function () {
      if (!document.querySelector('.modal-overlay.show, .modal-box.active, .modal.show')) {
        document.body.style.overflow = '';
      }
    },

    // Hien toast co san cua page, neu khong co thi tao toast fallback.
    showToast: function (message, type) {
      var toast = document.getElementById('toast');
      var messageEl = document.getElementById('toastMsg');

      if (!toast || !messageEl) {
        this.showFallbackToast(message, type);
        return;
      }

      messageEl.textContent = message;
      toast.className = 'toast ' + (type || 'success');
      toast.classList.add('show');

      window.setTimeout(function () {
        toast.classList.remove('show');
      }, 3200);
    },

    // Tao toast fallback cho page chua co markup toast rieng.
    showFallbackToast: function (message, type) {
      var container = this.getToastContainer();
      var toast = document.createElement('div');

      toast.className = 'toast ' + (type || 'success');
      toast.textContent = message;
      container.appendChild(toast);

      window.setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .2s ease';

        window.setTimeout(function () {
          toast.remove();
        }, 220);
      }, 3000);
    },

    // Lay hoac tao container cho fallback toast.
    getToastContainer: function () {
      var container = document.getElementById('toastContainer');
      if (container) return container;

      container = document.createElement('div');
      container.id = 'toastContainer';
      container.style.position = 'fixed';
      container.style.right = '18px';
      container.style.bottom = '18px';
      container.style.zIndex = '9999';
      document.body.appendChild(container);

      return container;
    }
  };

  window.CharleyLayout = CharleyLayout;

  // Alias cho inline onclick cu trong HTML output.
  window.closeDropdowns = function (except) { CharleyLayout.closeDropdowns(except); };
  window.toggleDropdown = function (id) { CharleyLayout.toggleDropdown(id); };
  window.toggleSidebar = function () { CharleyLayout.toggleSidebar(); };
  window.closeSidebar = function () { CharleyLayout.closeSidebar(); };
  window.openModal = function (id) { CharleyLayout.openModal(id); };
  window.closeModal = function (id) { CharleyLayout.closeModal(id); };
  window.closeAllModals = function () { CharleyLayout.closeAllModals(); };
  window.showToast = function (message, type) { CharleyLayout.showToast(message, type); };

  CharleyLayout.init();
})();
