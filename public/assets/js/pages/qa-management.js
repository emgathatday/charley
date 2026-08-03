/* ===== Page-specific source: pages/qa-management.html ===== */
// ===================== SELECTION / BULK BAR =====================
function toggleSelectAll(box){
  document.querySelectorAll('#qaTableBody tr').forEach(row=>{
    if(row.style.display !== 'none'){
      const checkbox = row.querySelector('.qa-row-checkbox');
      if(checkbox) checkbox.checked = box.checked;
    }
  });
  onRowCheck();
}

function onRowCheck(){
  const checked = document.querySelectorAll('#qaTableBody .qa-row-checkbox:checked').length;
  const bar = document.getElementById('bulkBar');
  document.getElementById('bulkCount').textContent = checked;
  bar.classList.toggle('show', checked>0);
}

// ===================== SHARED HELPERS (sidebar/topbar/modal/toast) =====================
function showToast(message, tone = 'blue'){
  const container = document.getElementById('toastContainer');
  if(!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${tone}`;
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(()=>toast.classList.add('show'), 10);
  setTimeout(()=>{
    toast.classList.remove('show');
    setTimeout(()=>toast.remove(), 200);
  }, 2600);
}