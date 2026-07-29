/* ===== Page-specific source: pages/qa-management.html ===== */
// ===================== TABS =====================
function switchTab(el){
  document.querySelectorAll('.qa-tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');

  const tab = el.getAttribute('data-tab');
  const isPending = tab === 'pending';
  document.getElementById('publishedTableWrap').classList.toggle('d-none', isPending);
  document.getElementById('pendingTableWrap').classList.toggle('d-none', !isPending);
  document.getElementById('bulkBar').classList.remove('show');

  applyFilters();
}

// ===================== FILTERS =====================
function applyFilters(){
  const search = document.getElementById('qaSearch').value.toLowerCase().trim();
  const plant = document.getElementById('fPlant').value;
  const topic = document.getElementById('fTopic').value;
  const status = document.getElementById('fStatus').value;
  const authorType = document.getElementById('fAuthor').value;

  const activeTab = document.querySelector('.qa-tab.active')?.getAttribute('data-tab') || 'all';

  if(activeTab === 'pending'){
    const pendingRows = document.querySelectorAll('#qaPendingTableBody tr');
    let visibleP = 0;
    pendingRows.forEach(row=>{
      const rPlant = row.getAttribute('data-plant');
      const rTopic = row.getAttribute('data-topic');
      const rAuthor = row.getAttribute('data-authortype');
      const rSearch = row.getAttribute('data-search');

      let show = true;
      if(plant && rPlant!==plant) show=false;
      if(topic && rTopic!==topic) show=false;
      if(authorType && rAuthor!==authorType) show=false;
      if(search && !rSearch.includes(search)) show=false;

      row.style.display = show ? '' : 'none';
      if(show) visibleP++;
    });
    document.getElementById('pendingVisibleCount').textContent = visibleP;
    document.getElementById('emptyStatePending').classList.toggle('show', visibleP===0);
    document.querySelector('#pendingTableWrap table.qa-table').style.display = visibleP===0 ? 'none' : 'table';
    return;
  }

  const rows = document.querySelectorAll('#qaTableBody tr');
  let visible = 0;
  rows.forEach(row=>{
    const rPlant = row.getAttribute('data-plant');
    const rTopic = row.getAttribute('data-topic');
    const rStatus = row.getAttribute('data-status');
    const rAuthor = row.getAttribute('data-authortype');
    const rSearch = row.getAttribute('data-search');

    let show = true;
    if(activeTab==='open' && rStatus!=='open') show=false;
    if(plant && rPlant!==plant) show=false;
    if(topic && rTopic!==topic) show=false;
    if(status && rStatus!==status) show=false;
    if(authorType && rAuthor!==authorType) show=false;
    if(search && !rSearch.includes(search)) show=false;

    row.style.display = show ? '' : 'none';
    if(show) visible++;
  });

  document.getElementById('visibleCount').textContent = visible;
  document.getElementById('emptyState').classList.toggle('show', visible===0);
  document.querySelector('#publishedTableWrap table.qa-table').style.display = visible===0 ? 'none' : 'table';
}

function resetFilters(){
  document.getElementById('qaSearch').value='';
  document.getElementById('fPlant').value='';
  document.getElementById('fTopic').value='';
  document.getElementById('fStatus').value='';
  document.getElementById('fAuthor').value='';
  document.querySelectorAll('.qa-tab').forEach(t=>t.classList.remove('active'));
  document.querySelector('.qa-tab[data-tab="all"]').classList.add('active');
  document.getElementById('publishedTableWrap').classList.remove('d-none');
  document.getElementById('pendingTableWrap').classList.add('d-none');
  applyFilters();
}

// ===================== SELECTION / BULK BAR =====================
function toggleSelectAll(box){
  document.querySelectorAll('#qaTableBody tr').forEach(row=>{
    if(row.style.display !== 'none'){
      row.querySelector('.qa-row-checkbox').checked = box.checked;
    }
  });
  onRowCheck();
}
function toggleSelectAllPending(box){
  document.querySelectorAll('#qaPendingTableBody tr').forEach(row=>{
    if(row.style.display !== 'none'){
      row.querySelector('.qa-row-checkbox').checked = box.checked;
    }
  });
  onRowCheck();
}
function onRowCheck(){
  const checked = document.querySelectorAll('#qaTableBody .qa-row-checkbox:checked, #qaPendingTableBody .qa-row-checkbox:checked').length;
  const bar = document.getElementById('bulkBar');
  document.getElementById('bulkCount').textContent = checked;
  bar.classList.toggle('show', checked>0);
}

// ===================== APPROVE (Pending Approval tab) =====================
function approvePendingRow(rowId){
  const row = document.getElementById(rowId);
  if(!row) return;
  row.style.transition = 'opacity .2s ease';
  row.style.opacity = '0';
  setTimeout(()=>{
    row.remove();
    const remaining = document.querySelectorAll('#qaPendingTableBody tr').length;
    document.getElementById('pendingVisibleCount').textContent = remaining;
    document.querySelector('.qa-tab[data-tab="pending"] .qa-tab-count').textContent = remaining;
    if(remaining === 0){
      document.getElementById('emptyStatePending').classList.add('show');
      document.querySelector('#pendingTableWrap table.qa-table').style.display = 'none';
    }
  }, 200);
  showToast('Question approved & published','green');
}

// ===================== SHARED HELPERS (sidebar/topbar/modal/toast) =====================
