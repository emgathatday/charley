(function () {
  var CreateNewUserPage = {
    technicalAreas: [
      'Primary Reforming',
      'Secondary Reforming',
      'Shift Conversion (HTS / LTS)',
      'CO2 Removal (Benfield / MDEA / PSA)',
      'Methanation',
      'Ammonia Synthesis Loop',
      'Methanol Synthesis Loop',
      'Hydrogen Purification (PSA / Membrane)',
      'Process & Syngas Compression',
      'Waste Heat Boilers & Heat Recovery',
      'Catalyst Management & Troubleshooting',
      'Utilities (Steam / Cooling Water / Instrument Air)',
      'Water Treatment & Boiler Feedwater',
      'Rotating Equipment (Compressors / Turbines)',
      'Distillation & Product Purification',
      'Process Safety & Integrity Operating Windows',
      'Instrumentation & Control Systems',
      'Corrosion & Material Selection',
      'Plant Commissioning & Startup',
      'SNG / GTL Process Technology'
    ],
    expertiseAreas: [],
    eaIdCounter: 0,

    // Khoi tao cac tuong tac rieng cua trang create user.
    init: function () {
      this.bindAccountTypeCards();
      this.updateRank();
    },

    // Lay danh sach card Account type.
    getTypeCards: function () {
      return document.querySelectorAll('#typeGrid .type-card');
    },

    // Gan su kien chon Account type va dong/mo khoi verification.
    bindAccountTypeCards: function () {
      var page = this;
      var typeCards = this.getTypeCards();

      typeCards.forEach(function (card) {
        card.addEventListener('click', function () {
          page.selectAccountType(card);
        });

        var input = card.querySelector('input[name="accountType"]');
        if (input) {
          input.addEventListener('change', function () {
            page.selectAccountType(card);
          });
        }
      });
    },

    // Chon mot Account type card va cap nhat radio tuong ung.
    selectAccountType: function (selectedCard) {
      this.getTypeCards().forEach(function (card) {
        var input = card.querySelector('input[name="accountType"]');

        card.classList.remove('checked');
        if (input) input.checked = false;
      });

      selectedCard.classList.add('checked');
      this.checkAccountTypeInput(selectedCard);
      this.toggleVerificationCard(selectedCard.dataset.type);
    },

    // Check radio input ben trong card dang duoc chon.
    checkAccountTypeInput: function (card) {
      var input = card.querySelector('input[name="accountType"]');

      if (input) {
        input.checked = true;
      }
    },

    // Hien verification khi Account type la Professional.
    toggleVerificationCard: function (accountType) {
      var verificationCard = document.getElementById('verificationCard');
      var expertiseAreasCard = document.getElementById('expertiseAreasCard');
      var isProfessional = accountType === 'professional';

      if (verificationCard) {
        verificationCard.classList.toggle('hidden-section', !isProfessional);
      }

      if (expertiseAreasCard) {
        expertiseAreasCard.classList.toggle('hidden-section', !isProfessional);
      }
    },

    // Tinh expertise rank tu Years of industry experience.
    updateRank: function () {
      var yearsInput = document.getElementById('yearsExp');
      var rankValue = document.getElementById('rankValue');

      if (!yearsInput || !rankValue) return;

      rankValue.textContent = this.getRankLabel(parseFloat(yearsInput.value));
      this.updateExpertiseCeiling();
    },

    getExpertiseCeiling: function (years) {
      if (Number.isNaN(years)) {
        return { label: 'Registered Member', max: 0 };
      }

      if (years < 8) {
        return { label: 'Industry Professional', max: 30 };
      }

      if (years < 15) {
        return { label: 'Experienced Professional', max: 50 };
      }

      return { label: 'Senior Industry Expert', max: 70 };
    },

    // Tra ve label rank theo so nam kinh nghiem.
    getRankLabel: function (years) {
      if (Number.isNaN(years)) {
        return 'Registered Member - no rank';
      }

      if (years < 8) {
        return 'Industry Professional (0-7 yrs)';
      }

      if (years < 15) {
        return 'Experienced Professional (8-15 yrs)';
      }

      return 'Senior Industry Expert (15+ yrs)';
    },

    // Cap nhat note tran diem va clamp cac row hien co theo rank.
    updateExpertiseCeiling: function () {
      var yearsInput = document.getElementById('yearsExp');
      var noteText = document.getElementById('ceilingNoteText');
      var years = yearsInput ? parseFloat(yearsInput.value) : NaN;
      var ceiling = this.getExpertiseCeiling(years);

      if (noteText) {
        noteText.textContent = ceiling.max === 0
          ? 'Registered Member has no expertise rank yet - self-ratings will unlock once verified with years of experience.'
          : 'Current rank: ' + ceiling.label + ' - self-rating ceiling is ' + ceiling.max + "% per area (100% only unlocks per-area after the user later passes that area's quiz).";
      }

      this.expertiseAreas.forEach(function (area) {
        if (area.rate > ceiling.max) {
          area.rate = ceiling.max;
        }
      });

      this.renderExpertiseAreas();
    },

    addExpertiseArea: function () {
      var yearsInput = document.getElementById('yearsExp');
      var years = yearsInput ? parseFloat(yearsInput.value) : NaN;
      var ceiling = this.getExpertiseCeiling(years);

      if (this.expertiseAreas.length >= 5) return;

      this.eaIdCounter += 1;
      this.expertiseAreas.push({
        id: this.eaIdCounter,
        name: '',
        rate: Math.min(20, ceiling.max)
      });
      this.renderExpertiseAreas();
    },

    removeExpertiseArea: function (id) {
      this.expertiseAreas = this.expertiseAreas.filter(function (area) {
        return area.id !== id;
      });
      this.renderExpertiseAreas();
    },

    setAreaName: function (id, value) {
      var area = this.findExpertiseArea(id);
      if (area) {
        area.name = value;
      }
    },

    setAreaRate: function (id, value) {
      var area = this.findExpertiseArea(id);
      var yearsInput = document.getElementById('yearsExp');
      var years = yearsInput ? parseFloat(yearsInput.value) : NaN;
      var ceiling = this.getExpertiseCeiling(years);
      var rateValue;

      if (!area) return;

      rateValue = Math.min(parseInt(value, 10) || 0, ceiling.max);
      area.rate = rateValue;
      this.updateAreaRateUi(area, ceiling.max);
    },

    findExpertiseArea: function (id) {
      return this.expertiseAreas.find(function (area) {
        return area.id === id;
      });
    },

    renderExpertiseAreas: function () {
      var list = document.getElementById('expertiseAreaList');
      var emptyMsg = document.getElementById('eaEmptyMsg');
      var addBtn = document.getElementById('addAreaBtn');
      var yearsInput = document.getElementById('yearsExp');
      var years = yearsInput ? parseFloat(yearsInput.value) : NaN;
      var ceiling = this.getExpertiseCeiling(years);
      var page = this;

      if (!list) return;

      list.innerHTML = this.expertiseAreas.map(function (area, index) {
        return page.renderExpertiseArea(area, index, ceiling.max);
      }).join('');

      this.expertiseAreas.forEach(function (area) {
        page.updateAreaRateUi(area, ceiling.max);
      });

      if (emptyMsg) {
        emptyMsg.style.display = this.expertiseAreas.length === 0 ? 'block' : 'none';
      }

      if (addBtn) {
        addBtn.disabled = this.expertiseAreas.length >= 5;
        addBtn.innerHTML = this.expertiseAreas.length >= 5
          ? '<svg class="icon"><use href="#icon-delete"></use></svg> Maximum of 5 areas reached'
          : '<svg class="icon"><use href="#icon-add-note"></use></svg> Add expertise area';
      }
    },

    renderExpertiseArea: function (area, index, max) {
      var selectedNames = this.expertiseAreas.map(function (item) {
        return item.name;
      });
      var options = this.technicalAreas
        .filter(function (name) {
          return name === area.name || selectedNames.indexOf(name) === -1;
        })
        .map(function (name) {
          return '<option value="' + CreateNewUserPage.escapeHtml(name) + '"' + (name === area.name ? ' selected' : '') + '>' + CreateNewUserPage.escapeHtml(name) + '</option>';
        })
        .join('');

      return [
        '<div class="ea-card">',
        '<div class="ea-num">' + (index + 1) + '</div>',
        '<select class="ea-select" onchange="setAreaName(' + area.id + ', this.value)">',
        '<option value="">Select a technical area...</option>',
        options,
        '</select>',
        '<div class="ea-rate">',
        '<input class="ea-range-input" type="range" id="eaRange' + area.id + '" min="0" max="' + max + '" value="' + area.rate + '" oninput="setAreaRate(' + area.id + ', this.value)">',
        '<span class="ea-rate-val" id="eaVal' + area.id + '">' + area.rate + '%</span>',
        '</div>',
        '<button type="button" class="ea-remove" onclick="removeExpertiseArea(' + area.id + ')" aria-label="Remove area">',
        '<svg class="icon"><use href="#icon-remove-partner-account"></use></svg>',
        '</button>',
        '</div>'
      ].join('');
    },

    updateAreaRateUi: function (area, max) {
      var valEl = document.getElementById('eaVal' + area.id);
      var rangeEl = document.getElementById('eaRange' + area.id);
      var fillPct = max > 0 ? (area.rate / max) * 100 : 0;

      if (valEl) {
        valEl.textContent = area.rate + '%';
      }

      if (rangeEl) {
        rangeEl.value = area.rate;
        rangeEl.max = max;
        rangeEl.style.setProperty('--fill', fillPct + '%');
      }
    },

    escapeHtml: function (value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },

    toggleTempPasswordField: function () {
      var method = document.querySelector('input[name="signin"]:checked');
      var field = document.getElementById('tempPasswordField');

      if (field) {
        field.classList.toggle('hidden-section', !method || method.value !== 'password');
      }
    },

    generateTempPassword: function () {
      var words = ['Charley', 'Syngas', 'Reformer', 'Catalyst', 'Synloop', 'Ammonia', 'Methanol', 'Hydrogen'];
      var symbols = '!@#$%*?';
      var word = words[Math.floor(Math.random() * words.length)];
      var symbol = symbols[Math.floor(Math.random() * symbols.length)];
      var digits = Math.floor(1000 + Math.random() * 9000);
      var input = document.getElementById('tempPassword');

      if (input) {
        input.value = word + symbol + digits + 'Xk';
      }
    },

    // Tao user demo tren frontend va hien success banner.
    createUser: function () {
      var firstName = this.getInputValue('firstName');
      var lastName = this.getInputValue('lastName');
      var email = this.getInputValue('email');

      if (!firstName) {
        alert('Please enter a first name before creating the account.');
        return;
      }

      if (!lastName) {
        alert('Please enter a last name before creating the account.');
        return;
      }

      if (!email) {
        alert('Please enter an email address before creating the account.');
        return;
      }

      this.showSuccessBanner(email);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Lay value cua input theo id.
    getInputValue: function (id) {
      var input = document.getElementById(id);

      return input ? input.value.trim() : '';
    },

    // Hien thong bao tao account thanh cong.
    showSuccessBanner: function (email) {
      var banner = document.getElementById('successBanner');
      var detail = document.getElementById('successDetail');
      var signin = document.querySelector('input[name="signin"]:checked');
      var signinMethod = signin ? signin.value : 'invite';

      if (detail) {
        detail.textContent = signinMethod === 'invite'
          ? 'An email invitation has been queued for ' + email + '.'
          : 'A temporary password has been generated for ' + email + '.';
      }

      if (banner) {
        banner.classList.add('show');
      }
    }
  };

  window.CreateNewUserPage = CreateNewUserPage;

  // Alias cho inline handler cu trong HTML output.
  window.updateRank = function () { CreateNewUserPage.updateRank(); };
  window.addExpertiseArea = function () { CreateNewUserPage.addExpertiseArea(); };
  window.removeExpertiseArea = function (id) { CreateNewUserPage.removeExpertiseArea(id); };
  window.setAreaName = function (id, value) { CreateNewUserPage.setAreaName(id, value); };
  window.setAreaRate = function (id, value) { CreateNewUserPage.setAreaRate(id, value); };
  window.toggleTempPasswordField = function () { CreateNewUserPage.toggleTempPasswordField(); };
  window.generateTempPassword = function () { CreateNewUserPage.generateTempPassword(); };
  window.createUser = function () { CreateNewUserPage.createUser(); };

  document.addEventListener('DOMContentLoaded', function () {
    CreateNewUserPage.init();
  });
})();
