/* ===== Page-specific source: pages/weekly-theme-management.html ===== */
// ===================== TABS =====================
function switchThemeTab(el){
  document.querySelectorAll('.qa-tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  const tab = el.getAttribute('data-tab');
  document.getElementById('upcomingList').classList.toggle('d-none', tab !== 'upcoming');
  document.getElementById('activeList').classList.toggle('d-none', tab !== 'active');
  document.getElementById('archivedList').classList.toggle('d-none', tab !== 'archived');
}

// ===================== PIN =====================
function togglePin(btn){
  const pinning = !btn.classList.contains('active-pin');
  btn.classList.toggle('active-pin');
  btn.title = pinning ? 'Unpin' : 'Pin';
  showToast(pinning ? 'Theme pinned to top' : 'Theme unpinned', 'blue');
}

// ===================== CHIP PICKER =====================
function toggleChip(el){
  el.classList.toggle('selected');
}

// ===================== MODAL =====================
function openThemeModal(){
  document.getElementById('tmHeaderTitle').textContent = 'Create New Theme';
  document.getElementById('tmSaveLabel').textContent = 'Schedule Theme';
  document.getElementById('tmTitle').value = '';
  document.getElementById('tmDescription').value = '';
  document.querySelectorAll('#tmPlantChips .tm-chip, #tmEquipChips .tm-chip').forEach(c=>c.classList.remove('selected'));
  document.getElementById('tmPinToggle').checked = false;
  document.getElementById('themeModal').classList.add('show');
}

function editTheme(id){
  document.getElementById('tmHeaderTitle').textContent = 'Edit Theme';
  document.getElementById('tmSaveLabel').textContent = 'Save Changes';
  document.getElementById('themeModal').classList.add('show');
  // In a full build this would load the specific theme's saved fields by id.
}

function saveTheme(){
  const title = document.getElementById('tmTitle').value.trim();
  if(!title){ showToast('Give the theme a title before saving','red'); return; }
  showToast('Theme scheduled', 'green');
  closeModal('theme');
}

// ===================== SHARED HELPERS (sidebar/topbar/modal/toast) =====================
