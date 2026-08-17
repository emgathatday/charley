/* ===== Page-specific source: pages/partner-detail.html ===== */
function setDetailTab(el, id){
    document.querySelectorAll('.dtab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('panel-' + id).classList.add('active');
  }

  function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
  }
  function closeSidebar(){
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
  }

  function openModal(id){
    document.querySelectorAll('.modal-box').forEach(function(box){ box.classList.remove('active'); });
    var target = document.getElementById('modal-' + id);
    if(target) target.classList.add('active');
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function viewInvoice(number, period, total, method, issued){
    document.getElementById('inv-title').textContent = 'Invoice ' + number;
    document.getElementById('inv-issued').textContent = 'Issued ' + issued;
    document.getElementById('inv-period').textContent = period;
    document.getElementById('inv-method').textContent = method;
    document.getElementById('inv-total').textContent = total;
    openModal('invoice');
  }
  function closeModal(){
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = '';
  }
  function closeModalOnOverlay(e){
    if(e.target.id === 'modalOverlay') closeModal();
  }
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
  });

  var qaCurrentPage = 1;
  var qaTotalPages = 3;
  var qaPageSize = 8;
  var qaTotalRows = 24;
  function qaGoToPage(page){
    if(page < 1 || page > qaTotalPages) return;
    qaCurrentPage = page;
    document.querySelectorAll('#qaTableBody tr').forEach(function(row){
      row.style.display = (parseInt(row.getAttribute('data-qa-page'), 10) === page) ? '' : 'none';
    });
    document.querySelectorAll('#qaPaginationControls .page-btn:not(.nav)').forEach(function(btn){
      btn.classList.toggle('active', parseInt(btn.textContent, 10) === page);
    });
    document.getElementById('qaPrevBtn').classList.toggle('disabled', page === 1);
    document.getElementById('qaNextBtn').classList.toggle('disabled', page === qaTotalPages);
    var start = (page - 1) * qaPageSize + 1;
    var end = Math.min(page * qaPageSize, qaTotalRows);
    document.getElementById('qaPaginationInfo').textContent = 'Showing ' + start + '–' + end + ' of ' + qaTotalRows;
  }

  /* ---------- Connections tab ---------- */
  var connCurrentPage = 1;
  var connPageSize = 8;
  var connFilterMode = 'all';
  var connSearchQuery = '';

  function connFilterClick(mode){
    connFilterMode = mode;
    connCurrentPage = 1;
    document.querySelectorAll('.conn-filter-chip').forEach(function(c){
      c.classList.toggle('active', c.getAttribute('data-mode') === mode);
    });
    document.getElementById('pendingStatCard').classList.toggle('active', mode === 'pending');
    document.getElementById('connListTitle').textContent = mode === 'pending' ? 'Pending Requests' : 'All Connections';
    document.getElementById('connListSub').textContent = mode === 'pending'
      ? 'Users waiting for a connection request to be approved'
      : 'Verified professionals connected with this partner';
    connRender();
  }

  function connSearchInput(val){
    connSearchQuery = val.trim().toLowerCase();
    connCurrentPage = 1;
    connRender();
  }

  function connGoToPage(page){
    if(page < 1) return;
    connCurrentPage = page;
    connRender();
  }

  function connRender(){
    var rows = Array.prototype.slice.call(document.querySelectorAll('#connectionListBody .connection-row'));
    var filtered = rows.filter(function(row){
      var status = row.getAttribute('data-status');
      var matchesFilter = (connFilterMode === 'all') || (status === connFilterMode);
      var matchesSearch = !connSearchQuery || row.textContent.toLowerCase().indexOf(connSearchQuery) !== -1;
      return matchesFilter && matchesSearch;
    });

    rows.forEach(function(row){ row.style.display = 'none'; });

    var total = filtered.length;
    var totalPages = Math.max(1, Math.ceil(total / connPageSize));
    if(connCurrentPage > totalPages) connCurrentPage = totalPages;

    var start = (connCurrentPage - 1) * connPageSize;
    var end = Math.min(start + connPageSize, total);
    for(var i = start; i < end; i++){ filtered[i].style.display = ''; }

    document.getElementById('connEmptyState').style.display = total === 0 ? '' : 'none';
    document.getElementById('connPaginationInfo').textContent = total === 0
      ? 'No connections found'
      : ('Showing ' + (start + 1) + '–' + end + ' of ' + total);

    var controls = document.getElementById('connPaginationControls');
    var html = '<div class="page-btn nav' + (connCurrentPage === 1 ? ' disabled' : '') + '" onclick="connGoToPage(' + (connCurrentPage - 1) + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></div>';
    for(var p = 1; p <= totalPages; p++){
      html += '<div class="page-btn' + (p === connCurrentPage ? ' active' : '') + '" onclick="connGoToPage(' + p + ')">' + p + '</div>';
    }
    html += '<div class="page-btn nav' + (connCurrentPage === totalPages ? ' disabled' : '') + '" onclick="connGoToPage(' + (connCurrentPage + 1) + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></div>';
    controls.innerHTML = html;
  }

  connRender();
