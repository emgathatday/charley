window.editEngineerConfig = (() => {
    const configEl = document.getElementById('editEngineerConfig');

    if (!configEl) return {};

    try {
        return JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        return {};
    }
})();

window.adminIcon = (name) => {
    const iconId = String(name || '').startsWith('icon-') ? String(name || '') : 'icon-' + String(name || '');
    return '<svg class="icon"><use href="/assets/icons/sprite.svg#' + iconId + '"></use></svg>';
};

const knowledgeDomains = window.editEngineerConfig.knowledgeDomains || [];
    const markDirty = () => document.getElementById('unsavedPill')?.classList.add('show');

    document.querySelectorAll('.edit-nav-item[data-section]').forEach((item) => {
        item.addEventListener('click', () => {
            const target = document.getElementById(item.dataset.section);
            if (! target) return;
            document.querySelectorAll('.edit-nav-item').forEach((nav) => nav.classList.remove('active'));
            item.classList.add('active');
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const syncTagInput = (wrap) => {
        const target = document.getElementById(wrap.dataset.target);
        if (! target) return;
        target.value = Array.from(wrap.querySelectorAll('.tag-chip')).map((chip) => chip.childNodes[0]?.textContent.trim()).filter(Boolean).join(', ');
    };

    document.querySelectorAll('.tag-input-wrap').forEach((wrap) => {
        const input = wrap.querySelector('.tag-inline-input');
        wrap.addEventListener('click', () => input?.focus());
        wrap.addEventListener('click', (event) => {
            const button = event.target.closest('.tag-chip button');
            if (! button) return;
            button.closest('.tag-chip')?.remove();
            syncTagInput(wrap);
            markDirty();
        });
        input?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const value = input.value.trim();
            if (! value) return;
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            if (wrap.id === 'keywordTags') {
                chip.style.background = '#F0FDF4';
                chip.style.borderColor = '#BBF7D0';
                chip.style.color = '#065F46';
            }
            chip.append(document.createTextNode(value + ' '));
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('aria-label', 'Remove tag');
            button.innerHTML = window.adminIcon('x');
            chip.append(button);
            wrap.insertBefore(chip, input);
            input.value = '';
            syncTagInput(wrap);
            markDirty();
        });
        syncTagInput(wrap);
    });

    // Plant focus filters knowledge-domain options, while rank and per-domain quiz unlocks control each slider ceiling.
    const getSelectedPlantIds = () => Array.from(document.querySelectorAll('#plantGroup input[name="plant_type_ids[]"]:checked')).map((input) => String(input.value));

    const isDomainValidForPlants = (domain, plantIds = getSelectedPlantIds()) => {
        if (! domain) return false;
        if (! domain.plant_type_id) return true;
        return plantIds.length === 0 || plantIds.includes(String(domain.plant_type_id));
    };

    const getAvailableDomains = () => {
        const plantIds = getSelectedPlantIds();
        return knowledgeDomains.filter((domain) => isDomainValidForPlants(domain, plantIds));
    };

    const findDomainByName = (name) => knowledgeDomains.find((domain) => domain.name === name) || null;
    const findDomainById = (id) => knowledgeDomains.find((domain) => String(domain.id) === String(id)) || null;

    const setOptionDataset = (option, domain) => {
        option.dataset.domainId = domain?.id ?? '';
        option.dataset.plantTypeId = domain?.plant_type_id ?? '';
        option.dataset.quizPassed = domain?.quiz_passed ? 'true' : 'false';
        option.dataset.isQuizUnlocked = domain?.is_quiz_unlocked ? 'true' : 'false';
    };

    const applyRowState = (row, state, domain = null) => {
        const badge = row.querySelector('.quiz-badge');
        row.dataset.domainId = domain?.id ?? '';
        row.dataset.plantTypeId = domain?.plant_type_id ?? '';
        row.dataset.quizPassed = domain?.quiz_passed ? 'true' : 'false';
        row.dataset.isQuizUnlocked = domain?.is_quiz_unlocked ? 'true' : 'false';
        row.dataset.invalidDomain = state === 'invalid' ? 'true' : 'false';
        if (! badge) return;

        if (state === 'invalid') {
            badge.textContent = 'Invalid for plant focus';
            badge.style.background = '#FEF2F2';
            badge.style.color = '#B91C1C';
            badge.style.borderColor = '#FECACA';
            return;
        }

        if (domain?.quiz_passed || domain?.is_quiz_unlocked) {
            badge.textContent = 'Quiz unlocked';
            badge.style.background = '#ECFDF5';
            badge.style.color = '#047857';
            badge.style.borderColor = '#A7F3D0';
            return;
        }

        badge.textContent = 'Quiz not taken';
        badge.style.background = '#F1F5F9';
        badge.style.color = 'var(--ink-faint)';
        badge.style.borderColor = 'var(--border)';
    };

    const refreshDomainSelect = (select) => {
        const row = select.closest('.expertise-area-row');
        const currentValue = select.value || select.dataset.currentValue || '';
        const currentDomain = findDomainById(row?.dataset.domainId) || findDomainByName(currentValue);
        const availableDomains = getAvailableDomains();
        const validCurrent = currentValue === '' || availableDomains.some((domain) => domain.name === currentValue);
        select.innerHTML = '';

        const placeholder = new Option('- Select section -', '');
        select.append(placeholder);
        availableDomains.forEach((domain) => {
            const option = new Option(domain.name, domain.name);
            setOptionDataset(option, domain);
            select.append(option);
        });

        if (currentValue && ! validCurrent) {
            const legacy = new Option(currentValue + ' (not valid for selected plant focus)', currentValue, true, true);
            legacy.dataset.invalidDomain = 'true';
            setOptionDataset(legacy, currentDomain);
            select.append(legacy);
            select.value = currentValue;
            applyRowState(row, 'invalid', currentDomain);
        } else {
            select.value = currentValue;
            const selectedDomain = findDomainByName(select.value);
            applyRowState(row, select.value ? 'valid' : 'empty', selectedDomain);
        }

        select.dataset.currentValue = select.value;
    };

    const refreshAllDomainSelects = () => {
        document.querySelectorAll('.js-top-area-select').forEach(refreshDomainSelect);
    };

    const getExperienceRank = (value) => {
        const normalized = String(value ?? '').trim();
        if (normalized === '') return { label: 'Registered Member', ceiling: 0 };

        const years = Number(normalized);
        if (! Number.isFinite(years) || years < 0) return { label: 'Registered Member', ceiling: 0 };
        if (years >= 15) return { label: 'Senior Industry Expert', ceiling: 70 };
        if (years >= 8) return { label: 'Experienced Professional', ceiling: 50 };
        return { label: 'Industry Professional', ceiling: 30 };
    };

    const toBoolean = (value) => ['1', 'true', 'yes', 'passed', 'unlocked'].includes(String(value ?? '').toLowerCase());

    const isAreaUnlocked = (row) => {
        const select = row.querySelector('.js-top-area-select');
        const option = select?.selectedOptions?.[0];
        return toBoolean(row.dataset.quizPassed)
            || toBoolean(row.dataset.isQuizUnlocked)
            || toBoolean(option?.dataset.quizPassed)
            || toBoolean(option?.dataset.isQuizUnlocked);
    };

    const getRowMax = (row, rankCeiling) => isAreaUnlocked(row) ? 100 : rankCeiling;

    const syncSliderRow = (row, rankCeiling, clampValue = true) => {
        const slider = row?.querySelector('.expertise-slider');
        const value = row?.querySelector('.js-slider-value');
        const maxLabel = slider?.closest('div')?.nextElementSibling;
        const marker = row?.querySelector('.slider-ceiling-marker');
        if (! slider) return;

        const rowMax = getRowMax(row, rankCeiling);
        if (clampValue && Number(slider.value) > rowMax) slider.value = rowMax;
        slider.max = 100;
        slider.dataset.ceiling = String(rowMax);

        if (value) value.textContent = slider.value + '%';
        if (maxLabel) maxLabel.textContent = 'Max: ' + rowMax + '%';
        if (marker) {
            marker.style.left = rowMax + '%';
            marker.title = (rowMax === 100 ? 'Quiz unlocked ceiling: ' : 'Rank ceiling: ') + rowMax + '%';
        }
    };

    const syncTopAreas = () => {
        const target = document.getElementById('topAreasInput');
        if (! target) return;
        target.value = Array.from(document.querySelectorAll('.js-top-area-select')).map((select) => select.value.trim()).filter(Boolean).join(', ');
        const label = document.getElementById('areaCountLabel');
        if (label) label.textContent = '(' + document.querySelectorAll('.expertise-area-row').length + ' of 5 used)';
    };

    const syncRankUi = (markAsDirty = false) => {
        const input = document.querySelector('[name="experience_years"]');
        const rank = getExperienceRank(input?.value);
        const banner = document.getElementById('rankCeilingBanner');
        const bannerRank = banner?.querySelector('span:first-child');
        const bannerValue = document.getElementById('rankCeilingValue');

        document.querySelectorAll('#levelGroup .radio-chip').forEach((chip) => {
            chip.classList.toggle('selected', chip.textContent.includes(rank.label));
        });

        if (bannerRank) bannerRank.textContent = rank.label;
        document.querySelector('.edit-title-badges .badge.senior')?.replaceChildren(document.createTextNode(rank.label));
        if (bannerValue) bannerValue.textContent = rank.ceiling + '%';
        document.querySelectorAll('.expertise-area-row').forEach((row) => syncSliderRow(row, rank.ceiling));
        syncTopAreas();
        if (markAsDirty) markDirty();
    };

    document.querySelector('[name="experience_years"]')?.addEventListener('input', () => syncRankUi(true));
    document.querySelector('[name="experience_years"]')?.addEventListener('change', () => syncRankUi(true));

    document.getElementById('plantGroup')?.addEventListener('change', (event) => {
        if (! event.target.matches('input[name="plant_type_ids[]"]')) return;
        refreshAllDomainSelects();
        syncRankUi(true);
    });

    document.getElementById('expertiseAreasList')?.addEventListener('input', (event) => {
        if (event.target.matches('.expertise-slider')) {
            const rank = getExperienceRank(document.querySelector('[name="experience_years"]')?.value);
            syncSliderRow(event.target.closest('.expertise-area-row'), rank.ceiling);
        }
        syncTopAreas();
        markDirty();
    });

    document.getElementById('expertiseAreasList')?.addEventListener('change', (event) => {
        if (event.target.matches('.js-top-area-select')) {
            const select = event.target;
            const row = select.closest('.expertise-area-row');
            const domain = findDomainByName(select.value);
            select.dataset.currentValue = select.value;
            applyRowState(row, select.selectedOptions[0]?.dataset.invalidDomain === 'true' ? 'invalid' : (select.value ? 'valid' : 'empty'), domain);
            const rank = getExperienceRank(document.querySelector('[name="experience_years"]')?.value);
            syncSliderRow(row, rank.ceiling);
        }
        syncTopAreas();
        markDirty();
    });

    document.getElementById('expertiseAreasList')?.addEventListener('click', (event) => {
        const button = event.target.closest('.js-remove-area');
        if (! button) return;
        button.closest('.expertise-area-row')?.remove();
        syncTopAreas();
        markDirty();
    });

    document.getElementById('addAreaBtn')?.addEventListener('click', () => {
        const list = document.getElementById('expertiseAreasList');
        const first = list?.querySelector('.expertise-area-row');
        if (! list || ! first || list.querySelectorAll('.expertise-area-row').length >= 5) return;
        const clone = first.cloneNode(true);
        const select = clone.querySelector('.js-top-area-select');
        const slider = clone.querySelector('.expertise-slider');
        const value = clone.querySelector('.js-slider-value');
        clone.dataset.domainId = '';
        clone.dataset.plantTypeId = '';
        clone.dataset.quizPassed = 'false';
        clone.dataset.isQuizUnlocked = 'false';
        clone.dataset.invalidDomain = 'false';
        if (select) {
            select.dataset.currentValue = '';
            select.value = '';
            refreshDomainSelect(select);
        }
        if (slider) slider.value = 0;
        if (value) value.textContent = '0%';
        list.appendChild(clone);
        syncRankUi();
        syncTopAreas();
        markDirty();
    });

    document.getElementById('editEngineerForm')?.addEventListener('input', markDirty);
    document.getElementById('editEngineerForm')?.addEventListener('change', markDirty);
    refreshAllDomainSelects();
    syncRankUi();
    syncTopAreas();
