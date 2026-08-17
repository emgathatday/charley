window.createEngineerConfig = (() => {
    const configEl = document.getElementById('createEngineerConfig');

    if (!configEl) return {};

    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        return {};
    }
})();

        (function () {
            const page = {
                knowledgeDomainsByPlantType: window.createEngineerConfig.knowledgeDomainsByPlantType || {},
                expertiseAreas: [],
                eaIdCounter: 0,
                init() {
                    this.bindActionControls();
                    this.bindAccountTypeCards();
                    this.bindPlantTypeChips();
                    this.bindSignInMethod();
                    this.bindYearsExperience();
                    this.syncPrimaryPlantType();
                    this.updateRank();
                    this.toggleTempPasswordField();
                    this.renderExpertiseAreas();
                },
                getTypeCards() {
                    return document.querySelectorAll('#typeGrid .type-card');
                },
                bindActionControls() {
                    document.querySelectorAll('.js-back').forEach((button) => {
                        button.addEventListener('click', () => history.back());
                    });
                    document.querySelectorAll('#saveBtn, #saveBtnBottom').forEach((button) => {
                        button.addEventListener('click', () => this.createUser());
                    });
                    const addAreaBtn = document.getElementById('addAreaBtn');
                    if (addAreaBtn) addAreaBtn.addEventListener('click', () => this.addExpertiseArea());
                    const generateBtn = document.getElementById('generateTempPasswordBtn');
                    if (generateBtn) generateBtn.addEventListener('click', () => this.generateTempPassword());
                },
                bindSignInMethod() {
                    document.querySelectorAll('input[name="signin"]').forEach((input) => {
                        input.addEventListener('change', () => this.toggleTempPasswordField());
                    });
                },
                bindYearsExperience() {
                    const yearsInput = document.getElementById('yearsExp');
                    if (yearsInput) yearsInput.addEventListener('input', () => this.updateRank());
                },
                bindAccountTypeCards() {
                    this.getTypeCards().forEach((card) => {
                        card.addEventListener('click', () => this.selectAccountType(card));
                        const input = card.querySelector('input[name="account_type"]');
                        if (input) input.addEventListener('change', () => this.selectAccountType(card));
                    });
                    const checked = document.querySelector('#typeGrid input[name="account_type"]:checked');
                    if (checked) this.toggleProfessionalSections(checked.value);
                },
                selectAccountType(selectedCard) {
                    this.getTypeCards().forEach((card) => {
                        const input = card.querySelector('input[name="account_type"]');
                        card.classList.remove('checked');
                        if (input) input.checked = false;
                    });
                    selectedCard.classList.add('checked');
                    const input = selectedCard.querySelector('input[name="account_type"]');
                    if (input) input.checked = true;
                    this.toggleProfessionalSections(selectedCard.dataset.type);
                },
                toggleProfessionalSections(accountType) {
                    ['verificationCard', 'expertiseAreasCard'].forEach((id) => {
                        const section = document.getElementById(id);
                        if (section) section.classList.toggle('hidden-section', accountType !== 'professional');
                    });
                },
                bindPlantTypeChips() {
                    document.querySelectorAll('#plantChips input[name="plant_type_ids[]"]').forEach((box) => {
                        box.addEventListener('change', () => {
                            this.syncPrimaryPlantType();
                            this.resetExpertiseAreas();
                        });
                    });
                },
                syncPrimaryPlantType() {
                    const boxes = Array.from(document.querySelectorAll('#plantChips input[name="plant_type_ids[]"]'));
                    const primary = document.getElementById('primaryPlantTypeId');
                    if (!primary) return;
                    const selected = boxes.find((box) => box.checked);
                    primary.value = selected ? selected.value : '';
                },
                updateRank() {
                    const yearsInput = document.getElementById('yearsExp');
                    const rankValue = document.getElementById('rankValue');
                    if (!yearsInput || !rankValue) return;
                    const years = parseFloat(yearsInput.value);
                    rankValue.textContent = this.getRankLabel(years);
                    this.updateExpertiseCeiling();
                },
                getExpertiseCeiling(years) {
                    if (Number.isNaN(years)) return { label: 'Registered Member', max: 0 };
                    if (years < 8) return { label: 'Industry Professional', max: 30 };
                    if (years < 15) return { label: 'Experienced Professional', max: 50 };
                    return { label: 'Senior Industry Expert', max: 70 };
                },
                getRankLabel(years) {
                    if (Number.isNaN(years)) return 'Registered Member - no rank';
                    if (years < 8) return 'Industry Professional (0-7 yrs)';
                    if (years < 15) return 'Experienced Professional (8-15 yrs)';
                    return 'Senior Industry Expert (15+ yrs)';
                },
                updateExpertiseCeiling() {
                    const yearsInput = document.getElementById('yearsExp');
                    const noteText = document.getElementById('ceilingNoteText');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (noteText) {
                        noteText.textContent = ceiling.max === 0
                            ? 'Registered Member has no expertise rank yet - self-ratings will unlock once verified with years of experience.'
                            : 'Current rank: ' + ceiling.label + ' - self-rating ceiling is ' + ceiling.max + "% per area (100% only unlocks per-area after the user later passes that area's quiz).";
                    }
                    this.expertiseAreas.forEach((area) => {
                        if (area.rate > ceiling.max) area.rate = ceiling.max;
                    });
                    this.renderExpertiseAreas();
                },
                resetExpertiseAreas() {
                    this.expertiseAreas = [];
                    this.renderExpertiseAreas();
                },
                technicalAreas() {
                    const primary = document.getElementById('primaryPlantTypeId');
                    if (!primary || !primary.value) return [];

                    return this.knowledgeDomainsByPlantType[primary.value] || [];
                },
                addExpertiseArea() {
                    if (this.expertiseAreas.length >= 5) return;
                    const technicalAreas = this.technicalAreas();
                    if (technicalAreas.length === 0) return;
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    this.eaIdCounter += 1;
                    this.expertiseAreas.push({
                        id: this.eaIdCounter,
                        domainId: '',
                        rate: Math.min(20, ceiling.max)
                    });
                    this.renderExpertiseAreas();
                },
                removeExpertiseArea(id) {
                    this.expertiseAreas = this.expertiseAreas.filter((area) => area.id !== id);
                    this.renderExpertiseAreas();
                },
                setAreaName(id, value) {
                    const area = this.findExpertiseArea(id);
                    if (area) area.domainId = value;
                },
                setAreaRate(id, value) {
                    const area = this.findExpertiseArea(id);
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (!area) return;
                    area.rate = Math.min(parseInt(value, 10) || 0, ceiling.max);
                    this.updateAreaRateUi(area, ceiling.max);
                },
                findExpertiseArea(id) {
                    return this.expertiseAreas.find((area) => area.id === id);
                },
                renderExpertiseAreas() {
                    const list = document.getElementById('expertiseAreaList');
                    const emptyMsg = document.getElementById('eaEmptyMsg');
                    const addBtn = document.getElementById('addAreaBtn');
                    const yearsInput = document.getElementById('yearsExp');
                    const years = yearsInput ? parseFloat(yearsInput.value) : NaN;
                    const ceiling = this.getExpertiseCeiling(years);
                    if (!list) return;
                    list.innerHTML = this.expertiseAreas.map((area, index) => this.renderExpertiseArea(area, index, ceiling.max)).join('');
                    this.expertiseAreas.forEach((area) => this.updateAreaRateUi(area, ceiling.max));
                    this.bindExpertiseAreaControls();
                    if (emptyMsg) {
                        emptyMsg.textContent = this.technicalAreas().length === 0
                            ? 'Select an Industry background Plant Type to show active technical areas.'
                            : 'No expertise areas added yet.';
                        emptyMsg.style.display = this.expertiseAreas.length === 0 ? 'block' : 'none';
                    }
                    if (addBtn) {
                        const hasTechnicalAreas = this.technicalAreas().length > 0;
                        addBtn.disabled = this.expertiseAreas.length >= 5 || !hasTechnicalAreas;
                        addBtn.innerHTML = this.expertiseAreas.length >= 5
                            ? '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-trash"></use></svg> Maximum of 5 areas reached'
                            : !hasTechnicalAreas
                                ? '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg> Select Industry background first'
                                : '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg> Add expertise area';
                    }
                },
                renderExpertiseArea(area, index, max) {
                    const selectedIds = this.expertiseAreas.map((item) => String(item.domainId));
                    const options = this.technicalAreas()
                        .filter((domain) => String(domain.id) === String(area.domainId) || selectedIds.indexOf(String(domain.id)) === -1)
                        .map((domain) => '<option value="' + this.escapeHtml(domain.id) + '"' + (String(domain.id) === String(area.domainId) ? ' selected' : '') + '>' + this.escapeHtml(domain.name) + '</option>')
                        .join('');
                    return [
                        '<div class="ea-card">',
                        '<div class="ea-num">' + (index + 1) + '</div>',
                        '<select class="ea-select" data-area-name="' + area.id + '">',
                        '<option value="">Select a technical area...</option>',
                        options,
                        '</select>',
                        '<div class="ea-rate">',
                        '<input class="ea-range-input" type="range" id="eaRange' + area.id + '" min="0" max="' + max + '" value="' + area.rate + '" data-area-rate="' + area.id + '">',
                        '<span class="ea-rate-val" id="eaVal' + area.id + '">' + area.rate + '%</span>',
                        '</div>',
                        '<button type="button" class="ea-remove" data-area-remove="' + area.id + '" aria-label="Remove area">',
                        '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-trash-2"></use></svg>',
                        '</button>',
                        '</div>'
                    ].join('');
                },
                bindExpertiseAreaControls() {
                    document.querySelectorAll('[data-area-name]').forEach((select) => {
                        select.addEventListener('change', () => this.setAreaName(parseInt(select.dataset.areaName, 10), select.value));
                    });
                    document.querySelectorAll('[data-area-rate]').forEach((input) => {
                        input.addEventListener('input', () => this.setAreaRate(parseInt(input.dataset.areaRate, 10), input.value));
                    });
                    document.querySelectorAll('[data-area-remove]').forEach((button) => {
                        button.addEventListener('click', () => this.removeExpertiseArea(parseInt(button.dataset.areaRemove, 10)));
                    });
                },
                updateAreaRateUi(area, max) {
                    const valEl = document.getElementById('eaVal' + area.id);
                    const rangeEl = document.getElementById('eaRange' + area.id);
                    const fillPct = max > 0 ? (area.rate / max) * 100 : 0;
                    if (valEl) valEl.textContent = area.rate + '%';
                    if (rangeEl) {
                        rangeEl.value = area.rate;
                        rangeEl.max = max;
                        rangeEl.style.setProperty('--fill', fillPct + '%');
                    }
                },
                toggleTempPasswordField() {
                    const field = document.getElementById('tempPasswordField');
                    const selected = document.querySelector('input[name="signin"]:checked');
                    if (field) field.classList.toggle('hidden-section', !selected || selected.value !== 'password');
                },
                generateTempPassword() {
                    const words = ['Charley', 'Syngas', 'Reformer', 'Catalyst', 'Synloop', 'Ammonia', 'Methanol', 'Hydrogen'];
                    const symbols = '!@#$%*?';
                    const word = words[Math.floor(Math.random() * words.length)];
                    const symbol = symbols[Math.floor(Math.random() * symbols.length)];
                    const digits = Math.floor(1000 + Math.random() * 9000);
                    const input = document.getElementById('tempPassword');
                    if (input) input.value = word + symbol + digits + 'Xk';
                },
                createUser() {
                    const firstName = this.getInputValue('firstName');
                    const lastName = this.getInputValue('lastName');
                    const email = this.getInputValue('email');
                    if (!firstName) { alert('Please enter a first name before creating the account.'); return; }
                    if (!lastName) { alert('Please enter a last name before creating the account.'); return; }
                    if (!email) { alert('Please enter an email address before creating the account.'); return; }
                    document.getElementById('createEngineerForm').submit();
                },
                getInputValue(id) {
                    const input = document.getElementById(id);
                    return input ? input.value.trim() : '';
                },
                escapeHtml(value) {
                    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                }
            };
            document.addEventListener('DOMContentLoaded', () => page.init());
        })();
    
