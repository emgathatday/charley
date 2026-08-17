/* ===== Page-specific source: pages/admin-profile.html ===== */
(function () {
  var AdminProfilePage = {
    profileEditing: false,
    tfaStep: 1,
    tfaEnabled: false,

    showSection: function (id, navItem) {
      ['profile', 'account', 'notifications', 'security', 'sessions', 'activity'].forEach(function (sectionId) {
        var section = document.getElementById('section-' + sectionId);
        if (section) section.classList.toggle('is-hidden', sectionId !== id);
      });

      document.querySelectorAll('.snav-item').forEach(function (item) {
        item.classList.remove('active');
      });

      if (navItem) navItem.classList.add('active');
    },

    toggleProfileEdit: function () {
      var page = this;
      var button = document.getElementById('editProfileBtn');
      var buttonText = document.getElementById('editProfileBtnText');

      this.profileEditing = !this.profileEditing;

      ['name', 'title', 'org'].forEach(function (field) {
        var view = document.getElementById('pf-' + field + '-view');
        var edit = document.getElementById('pf-' + field + '-edit');
        if (view) view.classList.toggle('is-hidden', page.profileEditing);
        if (edit) edit.classList.toggle('is-hidden', !page.profileEditing);
      });

      if (this.profileEditing) {
        if (button) button.classList.add('is-editing');
        if (buttonText) buttonText.textContent = 'Save changes';

        var nameInput = document.getElementById('pf-name-input');
        if (nameInput) nameInput.focus();
        return;
      }

      this.saveProfileValues();
      if (button) button.classList.remove('is-editing');
      if (buttonText) buttonText.textContent = 'Edit profile';
    },

    saveProfileValues: function () {
      var fields = {
        name: 'Sara Reyes',
        title: 'Platform Administrator',
        org: 'Charley Platform'
      };

      Object.keys(fields).forEach(function (field) {
        var view = document.getElementById('pf-' + field + '-view');
        var input = document.getElementById('pf-' + field + '-input');
        if (view && input) view.textContent = input.value || fields[field];
      });
    },

    openPasswordModal: function () {
      var modal = document.getElementById('pwModal');
      if (!modal) return;

      modal.classList.add('show');
      document.body.classList.add('modal-open');
      window.setTimeout(function () {
        var input = document.getElementById('currentPw');
        if (input) input.focus();
      }, 100);
    },

    closePasswordModal: function () {
      var modal = document.getElementById('pwModal');
      var bar = document.getElementById('pwBar');
      var hint = document.getElementById('pwHint');

      if (modal) modal.classList.remove('show');
      document.body.classList.remove('modal-open');

      ['currentPw', 'newPw', 'confirmPw'].forEach(function (id) {
        var input = document.getElementById(id);
        if (input) input.value = '';
      });

      if (bar) bar.className = 'pw-strength-bar';
      if (hint) {
        hint.textContent = 'Use 8+ characters, a mix of letters, numbers & symbols.';
        hint.className = 'pw-hint';
      }
    },

    togglePasswordVisibility: function (fieldId, button) {
      var input = document.getElementById(fieldId);
      if (!input) return;

      input.type = input.type === 'text' ? 'password' : 'text';
      if (button) button.classList.toggle('active', input.type === 'text');
    },

    checkStrength: function (value) {
      var bar = document.getElementById('pwBar');
      var hint = document.getElementById('pwHint');
      var score = 0;
      var levels = [
        { className: '', text: '' },
        { className: 'is-score-1', text: 'Too weak' },
        { className: 'is-score-2', text: 'Fair' },
        { className: 'is-score-3', text: 'Good' },
        { className: 'is-score-4', text: 'Strong' }
      ];

      if (value.length >= 8) score += 1;
      if (value.length >= 12) score += 1;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
      if (/[0-9]/.test(value)) score += 1;
      if (/[^A-Za-z0-9]/.test(value)) score += 1;

      var level = levels[Math.min(score, 4)];
      if (bar) {
        bar.className = 'pw-strength-bar';
        if (level.className) bar.classList.add(level.className);
      }
      if (hint) {
        hint.textContent = level.text || 'Use 8+ characters, a mix of letters, numbers & symbols.';
        hint.className = 'pw-hint';
        if (score >= 3) hint.classList.add('is-strong');
        else if (score >= 2) hint.classList.add('is-fair');
        else if (score >= 1) hint.classList.add('is-weak');
      }
    },

    savePassword: function () {
      var currentPassword = document.getElementById('currentPw');
      var newPassword = document.getElementById('newPw');
      var confirmPassword = document.getElementById('confirmPw');
      var status = document.getElementById('pw-sub');
      var button = document.getElementById('changePasswordBtn');

      if (!currentPassword || !currentPassword.value) {
        window.alert('Please enter your current password.');
        return;
      }

      if (!newPassword || newPassword.value.length < 8) {
        window.alert('New password must be at least 8 characters.');
        return;
      }

      if (!confirmPassword || newPassword.value !== confirmPassword.value) {
        window.alert('New passwords do not match.');
        return;
      }

      if (status) status.textContent = 'Just changed - Strength: Strong';
      this.closePasswordModal();

      if (button) {
        button.textContent = 'Password updated';
        button.classList.add('is-success');
        window.setTimeout(function () {
          button.textContent = 'Change password';
          button.classList.remove('is-success');
        }, 3000);
      }
    },

    handle2FA: function (toggle) {
      var input = toggle ? toggle.querySelector('input') : null;
      var icon = document.getElementById('tfa-icon');
      var status = document.getElementById('tfa-sub');

      if (this.tfaEnabled) {
        if (window.confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')) {
          this.tfaEnabled = false;
          if (input) input.checked = false;
          if (status) status.textContent = 'Strongly recommended for admin accounts';
          if (icon) this.setIconTone(icon, 'warning');
        } else if (input) {
          input.checked = true;
        }
        return;
      }

      if (input) input.checked = true;
      this.tfaStep = 1;
      this.open2FAModal();
    },

    open2FAModal: function () {
      var modal = document.getElementById('tfaModal');
      if (!modal) return;

      modal.classList.add('show');
      document.body.classList.add('modal-open');
      this.setTfaStep(1);
    },

    close2FAModal: function () {
      var modal = document.getElementById('tfaModal');
      var input = document.querySelector('#tfaToggle input');

      if (modal) modal.classList.remove('show');
      document.body.classList.remove('modal-open');

      if (!this.tfaEnabled && input) input.checked = false;
    },

    setTfaStep: function (step) {
      var backButton = document.getElementById('modalBackBtn');
      var nextButton = document.getElementById('modalNextBtn');

      this.tfaStep = step;

      [1, 2, 3].forEach(function (stepNumber) {
        var section = document.getElementById('tfa-step' + stepNumber);
        var dot = document.getElementById('dot' + stepNumber);
        if (section) section.classList.toggle('active', stepNumber === step);
        if (dot) dot.classList.toggle('active', stepNumber === step);
      });

      if (!backButton || !nextButton) return;

      if (step === 1) {
        backButton.classList.add('is-hidden');
        nextButton.textContent = 'Next - Enter code';
        nextButton.classList.remove('is-hidden');
      } else if (step === 2) {
        backButton.classList.remove('is-hidden');
        nextButton.textContent = 'Verify & enable 2FA';
        nextButton.classList.remove('is-hidden');
      } else {
        backButton.classList.add('is-hidden');
        nextButton.textContent = 'Done';
      }
    },

    tfaNext: function () {
      var page = this;

      if (this.tfaStep === 1) {
        this.setTfaStep(2);
        window.setTimeout(function () {
          var input = document.getElementById('otp0');
          if (input) input.focus();
        }, 0);
        return;
      }

      if (this.tfaStep === 2) {
        var code = [0, 1, 2, 3, 4, 5].map(function (index) {
          var input = document.getElementById('otp' + index);
          return input ? input.value : '';
        }).join('');

        if (code.length < 6) {
          window.alert('Please enter the full 6-digit code.');
          return;
        }

        this.setTfaStep(3);
        this.tfaEnabled = true;
        this.syncTfaEnabledUi();
        return;
      }

      page.close2FAModal();
    },

    tfaBack: function () {
      if (this.tfaStep === 2) this.setTfaStep(1);
    },

    syncTfaEnabledUi: function () {
      var input = document.querySelector('#tfaToggle input');
      var status = document.getElementById('tfa-sub');
      var icon = document.getElementById('tfa-icon');

      if (input) input.checked = true;
      if (status) status.textContent = '2FA enabled - Authenticator app';
      if (icon) this.setIconTone(icon, 'success');
    },

    setIconTone: function (element, tone) {
      element.classList.remove('icon-tone-warning', 'icon-tone-success');
      element.classList.add('icon-tone-' + tone);
    },

    otpMove: function (element, index) {
      element.value = element.value.replace(/\D/g, '').slice(-1);
      if (element.value && index < 5) {
        var next = document.getElementById('otp' + (index + 1));
        if (next) next.focus();
      }
    }
  };

  window.AdminProfilePage = AdminProfilePage;
  window.showSection = function (id, element) { AdminProfilePage.showSection(id, element); };
  window.toggleProfileEdit = function () { AdminProfilePage.toggleProfileEdit(); };
  window.openPasswordModal = function () { AdminProfilePage.openPasswordModal(); };
  window.closePasswordModal = function () { AdminProfilePage.closePasswordModal(); };
  window.togglePwVis = function (fieldId, button) { AdminProfilePage.togglePasswordVisibility(fieldId, button); };
  window.checkStrength = function (value) { AdminProfilePage.checkStrength(value); };
  window.savePassword = function () { AdminProfilePage.savePassword(); };
  window.handle2FA = function (toggle) { AdminProfilePage.handle2FA(toggle); };
  window.close2FAModal = function () { AdminProfilePage.close2FAModal(); };
  window.tfa2FANext = function () { AdminProfilePage.tfaNext(); };
  window.tfa2FABack = function () { AdminProfilePage.tfaBack(); };
  window.otpMove = function (element, index) { AdminProfilePage.otpMove(element, index); };
})();
