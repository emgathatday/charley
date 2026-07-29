(function () {
  var UserEditPage = {
    isDirty: false,

    sections: [
      'sec-basic',
      'sec-professional',
      'sec-expertise',
      'sec-topexpertise',
      'sec-account',
      'sec-verification',
      'sec-privacy',
      'sec-audit'
    ],

    sectionLabels: {
      'sec-basic': 'basic',
      'sec-professional': 'professional',
      'sec-expertise': 'expertise',
      'sec-topexpertise': 'top expertise',
      'sec-account': 'account',
      'sec-verification': 'verification',
      'sec-privacy': 'privacy',
      'sec-audit': 'audit'
    },

    sectionOptions: [
      'Reforming Section',
      'Synthesis Loop',
      'CO2 Removal',
      'Compression',
      'Distillation',
      'Utilities',
      'Heat Recovery',
      'Water Treatment'
    ],

    rankCeilings: {
      'Registered Member': 0,
      'Industry Professional': 30,
      'Experienced Professional': 50,
      'Senior Industry Expert': 70
    },

    // Khoi tao tuong tac rieng cua trang user edit.
    init: function () {
      this.initSliders();
      this.updateAreaCount();
      this.bindEditNav();
      this.bindScrollSpy();
    },

    // Danh dau form da co thay doi va hien unsaved pill.
    markDirty: function () {
      var pill = document.getElementById('unsavedPill');

      if (this.isDirty) return;

      this.isDirty = true;
      if (pill) pill.classList.add('show');
    },

    // Luu thay doi demo va an unsaved pill.
    saveChanges: function () {
      var pill = document.getElementById('unsavedPill');

      this.isDirty = false;
      if (pill) pill.classList.remove('show');
      this.showToast('Profile saved - changes logged to audit trail', 'green');
    },

    // Mo modal xac nhan discard neu dang co thay doi.
    confirmDiscard: function () {
      var modal = document.getElementById('discardModal');

      if (!this.isDirty) {
        history.back();
        return;
      }

      if (modal) modal.classList.add('show');
    },

    // Dong modal theo id cua source cu.
    closeModal: function (id) {
      if (window.CharleyLayout) {
        window.CharleyLayout.closeModal(id);
        return;
      }

      var modal = document.getElementById(id + 'Modal');
      if (modal) modal.classList.remove('show');
    },

    // Toggle class on cho switch custom neu page can.
    toggleSwitch: function (element) {
      if (element) element.classList.toggle('on');
    },

    // Chon mot radio-chip trong group.
    selectRadio: function (element, groupId) {
      var group = document.getElementById(groupId);
      if (!group || !element) return;

      group.querySelectorAll('.radio-chip').forEach(function (chip) {
        chip.classList.remove('selected');
      });

      element.classList.add('selected');
      this.markDirty();
    },

    // Xoa tag expertise/keyword.
    removeTag: function (button) {
      var tag = button ? button.closest('.tag-chip') : null;

      if (tag) {
        tag.remove();
        this.markDirty();
      }
    },

    // Them tag khi bam Enter trong input.
    addTagOnEnter: function (event, inputId, wrapId, bg, border, color) {
      if (event.key !== 'Enter') return;

      event.preventDefault();

      var input = inputId ? document.getElementById(inputId) : event.target;
      var wrap = wrapId ? document.getElementById(wrapId) : event.target.closest('.tag-input-wrap');
      var value = input ? input.value.trim() : '';

      if (!input || !wrap || !value) return;

      this.addTag(wrap, input, value, bg, border, color);
      input.value = '';
      this.markDirty();
    },

    // Chen tag moi vao truoc input.
    addTag: function (wrap, input, value, bg, border, color) {
      var chip = document.createElement('span');

      chip.className = 'tag-chip';
      if (bg) {
        chip.style.cssText = 'background:' + bg + ';border-color:' + border + ';color:' + color + ';';
      }

      chip.innerHTML = value + ' <button onclick="removeTag(this)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
      wrap.insertBefore(chip, input);
    },

    // Gan click cho edit navigation bang data-section.
    bindEditNav: function () {
      document.querySelectorAll('.edit-nav-item[data-section]').forEach(function (item) {
        item.addEventListener('click', function () {
          UserEditPage.navScrollTo(item.dataset.section, item);
        });
      });
    },

    // Scroll toi section va cap nhat nav active.
    navScrollTo: function (id, navItem) {
      var section = document.getElementById(id);

      if (!section) return;

      window.scrollTo({
        top: section.getBoundingClientRect().top + window.pageYOffset - 100,
        behavior: 'smooth'
      });

      if (navItem) {
        this.setActiveNavItem(navItem);
      } else {
        this.setActiveNav(id);
      }
    },

    // Cap nhat background va label cho slider expertise.
    updateSlider: function (input) {
      var ceiling = parseInt(input.dataset.ceiling || '100', 10);
      var value = parseInt(input.value, 10);
      var label = input.closest('div').previousElementSibling;

      if (value > ceiling) {
        value = ceiling;
        input.value = ceiling;
      }

      input.style.background = 'linear-gradient(to right,#3B82F6 0%,#3B82F6 ' + value + '%,#E2E8F0 ' + value + '%,#E2E8F0 100%)';
      if (label) label.textContent = value + '%';
    },

    // Khoi tao tat ca slider dang co san.
    initSliders: function () {
      document.querySelectorAll('.expertise-slider').forEach(function (slider) {
        UserEditPage.updateSlider(slider);
      });
    },

    // Lay rank hien tai tu levelGroup.
    getCurrentRank: function () {
      var selected = document.querySelector('#levelGroup .radio-chip.selected');

      if (!selected) return 'Senior Industry Expert';

      return selected.textContent
        .replace(/0[-–]7 yrs|8[-–]15 yrs|15\+ yrs|\(Unverified\)/g, '')
        .trim();
    },

    // Cap nhat tran expertise theo rank hien tai.
    updateExpertiseCeiling: function () {
      var rank = this.getCurrentRank();
      var ceiling = this.rankCeilings[rank] == null ? 70 : this.rankCeilings[rank];
      var ceilingValue = document.getElementById('rankCeilingValue');
      var bannerRank = document.querySelector('#rankCeilingBanner span:first-of-type');

      if (ceilingValue) ceilingValue.textContent = ceiling + '%';
      if (bannerRank) bannerRank.textContent = rank;

      document.querySelectorAll('.expertise-area-row').forEach(function (row) {
        if (row.dataset.quizPassed === 'true') return;

        UserEditPage.updateAreaRowCeiling(row, ceiling);
      });
    },

    // Cap nhat tran cho mot expertise area row.
    updateAreaRowCeiling: function (row, ceiling) {
      var slider = row.querySelector('.expertise-slider');
      var marker = row.querySelector('.slider-ceiling-marker');
      var maxLabel = row.querySelector('span:last-child');

      if (slider) {
        slider.dataset.ceiling = ceiling;
        this.updateSlider(slider);
      }

      if (marker) marker.style.left = ceiling + '%';
      if (maxLabel) maxLabel.textContent = 'Max: ' + ceiling + '%';
    },

    // Them expertise area moi, toi da 5 dong.
    addAreaRow: function () {
      var list = document.getElementById('expertiseAreasList');
      if (!list) return;

      var count = list.querySelectorAll('.expertise-area-row').length;
      if (count >= 5) {
        this.showToast('Maximum 5 expertise areas allowed', 'amber');
        return;
      }

      var ceiling = this.rankCeilings[this.getCurrentRank()] == null ? 70 : this.rankCeilings[this.getCurrentRank()];
      var row = this.buildAreaRow(ceiling);

      list.appendChild(row);
      this.updateSlider(row.querySelector('.expertise-slider'));
      this.updateAreaCount();
      this.markDirty();
    },

    // Tao DOM cho expertise area row moi.
    buildAreaRow: function (ceiling) {
      var row = document.createElement('div');
      var options = this.sectionOptions.map(function (option) {
        return '<option>' + option + '</option>';
      }).join('');

      row.className = 'expertise-area-row';
      row.dataset.quizPassed = 'false';
      row.innerHTML =
        '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
          '<select class="form-select" style="flex:1;" onchange="markDirty()"><option value="">-- Select section --</option>' + options + '</select>' +
          '<span class="quiz-badge" style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:20px;white-space:nowrap;background:#F1F5F9;color:var(--ink-faint);border:1px solid var(--border);">Quiz not taken</span>' +
          '<button onclick="removeAreaRow(this)" style="width:28px;height:28px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--ink-faint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;stroke-width:2.5;"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
          '</button>' +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:12px;">' +
          '<span style="font-size:12px;font-weight:600;color:var(--ink-faint);width:28px;text-align:right;">0%</span>' +
          '<div style="flex:1;position:relative;padding-top:26px;">' +
            '<input type="range" min="0" max="100" value="0" data-ceiling="' + ceiling + '" class="expertise-slider" oninput="updateSlider(this);markDirty()" style="width:100%;">' +
            '<div class="slider-ceiling-marker" style="left:' + ceiling + '%;" title="Rank ceiling: ' + ceiling + '%"></div>' +
          '</div>' +
          '<span style="font-size:12px;color:var(--ink-faint);width:80px;font-weight:500;">Max: ' + ceiling + '%</span>' +
        '</div>';

      return row;
    },

    // Xoa mot expertise area row.
    removeAreaRow: function (button) {
      var row = button ? button.closest('.expertise-area-row') : null;

      if (row) {
        row.remove();
        this.updateAreaCount();
        this.markDirty();
      }
    },

    // Cap nhat so luong expertise area va disabled button neu du 5.
    updateAreaCount: function () {
      var list = document.getElementById('expertiseAreasList');
      var label = document.getElementById('areaCountLabel');
      var addButton = document.getElementById('addAreaBtn');
      var count = list ? list.querySelectorAll('.expertise-area-row').length : 0;

      if (label) label.textContent = '(' + count + ' of 5 used)';
      if (addButton) addButton.disabled = count >= 5;
    },

    // Hien toast thong bao.
    showToast: function (message, color) {
      var container = document.getElementById('toastContainer');
      var toast = document.createElement('div');

      if (!container) {
        if (window.CharleyLayout) window.CharleyLayout.showToast(message, color || 'blue');
        return;
      }

      toast.className = 'toast ' + (color || 'blue');
      toast.textContent = message;
      container.appendChild(toast);

      window.setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .3s';
        window.setTimeout(function () {
          toast.remove();
        }, 300);
      }, 3000);
    },

    // Bat scroll spy de highlight edit nav theo section hien tai.
    bindScrollSpy: function () {
      window.addEventListener('scroll', function () {
        UserEditPage.updateActiveNavFromScroll();
      }, { passive: true });
    },

    // Tim section dang hien va cap nhat nav active.
    updateActiveNavFromScroll: function () {
      var current = this.sections[0];

      this.sections.forEach(function (id) {
        var section = document.getElementById(id);
        if (section && section.getBoundingClientRect().top < 140) current = id;
      });

      this.setActiveNav(current);
    },

    // Set active nav theo section id.
    setActiveNav: function (sectionId) {
      document.querySelectorAll('.edit-nav-item').forEach(function (item) {
        item.classList.toggle('active', item.dataset.section === sectionId);
      });
    },

    // Set active truc tiep cho nav item vua click.
    setActiveNavItem: function (activeItem) {
      document.querySelectorAll('.edit-nav-item').forEach(function (item) {
        item.classList.toggle('active', item === activeItem);
      });
    }
  };

  window.UserEditPage = UserEditPage;

  // Alias cho inline handler cu trong HTML output.
  window.markDirty = function () { UserEditPage.markDirty(); };
  window.saveChanges = function () { UserEditPage.saveChanges(); };
  window.confirmDiscard = function () { UserEditPage.confirmDiscard(); };
  window.closeModal = function (id) { UserEditPage.closeModal(id); };
  window.toggleSwitch = function (element) { UserEditPage.toggleSwitch(element); };
  window.selectRadio = function (element, groupId) { UserEditPage.selectRadio(element, groupId); };
  window.removeTag = function (button) { UserEditPage.removeTag(button); };
  window.addTagOnEnter = function (event, inputId, wrapId, bg, border, color) { UserEditPage.addTagOnEnter(event, inputId, wrapId, bg, border, color); };
  window.updateSlider = function (input) { UserEditPage.updateSlider(input); };
  window.updateExpertiseCeiling = function () { UserEditPage.updateExpertiseCeiling(); };
  window.addAreaRow = function () { UserEditPage.addAreaRow(); };
  window.removeAreaRow = function (button) { UserEditPage.removeAreaRow(button); };
  window.updateAreaCount = function () { UserEditPage.updateAreaCount(); };
  window.showToast = function (message, color) { UserEditPage.showToast(message, color); };

  document.addEventListener('DOMContentLoaded', function () {
    UserEditPage.init();
  });
})();
