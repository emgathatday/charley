/* ===== Page-specific source: pages/partner-management.html ===== */
function setTab(el){
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }

  function openDrawer(btn){
    const row = btn.closest('tr');
    const name = row.querySelector('.company-name').textContent;
    const meta = row.querySelector('.company-meta').textContent;
    const logo = row.querySelector('.company-logo');
    document.getElementById('drawerCompanyName').textContent = name;
    document.getElementById('drawerCompanyMeta').textContent = meta;
    document.getElementById('reviewDrawer').classList.add('show');
    document.getElementById('drawerOverlay').classList.add('show');
  }
  function closeDrawer(){
    document.getElementById('reviewDrawer').classList.remove('show');
    document.getElementById('drawerOverlay').classList.remove('show');
  }

  document.querySelectorAll('.tier-option').forEach(opt=>{
    opt.addEventListener('click', function(){
      this.parentElement.querySelectorAll('.tier-option').forEach(o=>o.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
  }
  function closeSidebar(){
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
  }
