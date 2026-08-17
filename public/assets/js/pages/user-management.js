/* ===== Page-specific source: pages/user-management.html ===== */
/* =========================================================
   DATA
   ========================================================= */
const DEFAULT_AVATAR = 'data:image/svg+xml;base64,' + btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="10" fill="#F1F4F8"/><circle cx="50" cy="38" r="18" fill="#C8D0DA"/><path d="M50 60c-22 0-34 14-34 32v8h68v-8c0-18-12-32-34-32z" fill="#C8D0DA"/></svg>');

const USERS = [
  { id:1, name:'Ahmed Al-Rashidi', email:'ahmed.alrashidi@aramco.com', initials:'AA', avatar:'https://i.pravatar.cc/100?img=12', role:'professional', expertise:'senior', plant:'Ammonia', status:'active', joined:'12 Jan 2024', points:8420, questions:34, answers:89, company:'Saudi Aramco', position:'Senior Process Engineer', experience:'18 years', verification:'Verified — Company email + LinkedIn', pendingVerif:false },
  { id:2, name:'Elena Vassiliev', email:'e.vassiliev@yara.com', initials:'EV', role:'professional', expertise:'experienced', plant:'Methanol', status:'active', joined:'5 Mar 2024', points:5670, questions:22, answers:61, company:'Yara International', position:'Process Engineer', experience:'11 years', verification:'Verified — Company letter', pendingVerif:false },
  { id:3, name:'Kwame Asante', email:'k.asante@gmail.com', initials:'KA', role:'member', expertise:'registered', plant:'—', status:'pending', joined:'2 Jul 2025', points:0, questions:0, answers:0, company:'—', position:'Graduate Student', experience:'< 1 year', verification:'Pending — Submitted university letter', pendingVerif:true },
  { id:4, name:'Priya Nair', email:'p.nair@iffco.in', initials:'PN', role:'professional', expertise:'experienced', plant:'Ammonia', status:'active', joined:'18 Aug 2023', points:6200, questions:28, answers:74, company:'IFFCO', position:'Plant Engineer', experience:'9 years', verification:'Verified — Company email', pendingVerif:false },
  { id:5, name:'Lars Eriksson', email:'l.eriksson@linde.com', initials:'LE', avatar:'https://i.pravatar.cc/100?img=33', role:'professional', expertise:'senior', plant:'Hydrogen', status:'active', joined:'3 Nov 2022', points:11340, questions:45, answers:130, company:'Linde plc', position:'Chief Process Engineer', experience:'22 years', verification:'Verified — LinkedIn + CV', pendingVerif:false },
  { id:6, name:'Maria Santos', email:'m.santos@outlook.com', initials:'MS', role:'member', expertise:'registered', plant:'—', status:'pending', joined:'9 Jul 2025', points:50, questions:1, answers:0, company:'—', position:'Unemployed Engineer', experience:'3 years', verification:'Pending — Submitted CV only', pendingVerif:true },
  { id:7, name:'Ravi Shankar', email:'ravi.shankar@toyo.jp', initials:'RS', role:'professional', expertise:'senior', plant:'GTL', status:'active', joined:'21 Apr 2023', points:9870, questions:37, answers:108, company:'Toyo Engineering', position:'Lead Process Engineer', experience:'17 years', verification:'Verified — Company letter', pendingVerif:false },
  { id:8, name:'Faisal Al-Mansoori', email:'fmansoori@adnoc.ae', initials:'FM', role:'professional', expertise:'experienced', plant:'SNG', status:'suspended', joined:'14 Feb 2024', points:3100, questions:14, answers:38, company:'ADNOC', position:'Process Engineer', experience:'8 years', verification:'Verified — Company email', pendingVerif:false },
  { id:9, name:'Chen Wei', email:'chen.w@sinopec.cn', initials:'CW', role:'professional', expertise:'senior', plant:'Methanol', status:'active', joined:'6 Oct 2022', points:13200, questions:52, answers:155, company:'Sinopec', position:'Senior Process Engineer', experience:'20 years', verification:'Verified — Company email + CV', pendingVerif:false },
  { id:10, name:'Olga Petrov', email:'o.petrov@gazprom.ru', initials:'OP', role:'member', expertise:'registered', plant:'—', status:'pending', joined:'11 Jul 2025', points:0, questions:0, answers:0, company:'—', position:'Student', experience:'< 1 year', verification:'Pending — No documents submitted', pendingVerif:true },
  { id:11, name:'James Okafor', email:'j.okafor@dangote.com', initials:'JO', role:'professional', expertise:'experienced', plant:'Ammonia', status:'active', joined:'28 Jan 2024', points:4500, questions:18, answers:52, company:'Dangote Fertilizer', position:'Process Engineer', experience:'10 years', verification:'Verified — Company letter', pendingVerif:false },
  { id:12, name:'Sophie Bauer', email:'s.bauer@basf.com', initials:'SB', avatar:'https://i.pravatar.cc/100?img=47', role:'professional', expertise:'senior', plant:'Hydrogen', status:'frozen', joined:'12 Jul 2023', points:7800, questions:30, answers:90, company:'BASF SE', position:'Senior Engineer', experience:'16 years', verification:'Verified — Company email', pendingVerif:false },
];

/* =========================================================
   RENDER TABLE
   ========================================================= */
function expertiseLabel(e){
  if(e==='senior') return {label:'Senior Industry Expert', cls:'senior'};
  if(e==='experienced') return {label:'Experienced Professional', cls:'experienced'};
  if(e==='professional2') return {label:'Industry Professional', cls:'professional2'};
  return {label:'Registered Member', cls:'registered'};
}
function roleLabel(r){
  if(r==='professional') return {label:'Professional', cls:'professional'};
  if(r==='admin') return {label:'Admin', cls:'admin'};
  return {label:'Registered Member', cls:'member'};
}
function statusLabel(s){
  if(s==='active') return 'active';
  if(s==='pending') return 'pending';
  if(s==='suspended') return 'suspended';
  return 'frozen';
}
function avatarImgHtml(user){
  const src = user.avatar || DEFAULT_AVATAR;
  return `<img class="user-avatar-img" src="${src}" alt="${user.name}" onerror="this.src=DEFAULT_AVATAR;">`;
}
function starsHtml(pts){
  let filled = 0;
  if(pts>=10000) filled=5;
  else if(pts>=7500) filled=4;
  else if(pts>=5000) filled=3;
  else if(pts>=2500) filled=2;
  else if(pts>=1000) filled=1;
  let html='<div class="stars">';
  for(let i=0;i<5;i++) html+=`<svg class="star ${i<filled?'':'empty'}" viewBox="0 0 24 24" fill="${i<filled?'#F59E0B':'#E2E8F0'}" stroke="${i<filled?'#F59E0B':'#CBD5E1'}" stroke-width="1"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
  html+='</div>';
  return html;
}

function renderTable(users){
  const tbody = document.getElementById('userTableBody');
  tbody.innerHTML = users.map(u=>{
    const exp = expertiseLabel(u.expertise);
    const role = roleLabel(u.role);
    const st = statusLabel(u.status);
    return `
    <tr>
      <td><input type="checkbox" class="row-check" data-id="${u.id}" onchange="handleRowCheck()" style="cursor:pointer;width:15px;height:15px;"></td>
      <td>
        <div class="user-cell">
          <div class="user-avatar">${avatarImgHtml(u)}</div>
          <div>
            <div class="user-name" style="cursor:pointer;" onclick="location.href='user-detail.html?id=${u.id}'">${u.name}</div>
            <div class="user-email">${u.email}</div>
          </div>
        </div>
      </td>
      <td><span class="role-badge ${role.cls}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        ${role.label}
      </span></td>
      <td><span class="exp-badge ${exp.cls}">${exp.label}</span></td>
      <td>
        <div class="points-cell">${u.points.toLocaleString()} <span>pts</span></div>
        ${starsHtml(u.points)}
      </td>
      <td style="color:var(--ink-soft);font-size:12.5px;">${u.plant}</td>
      <td><span class="status-dot ${st}">${u.status.charAt(0).toUpperCase()+u.status.slice(1)}</span></td>
      <td style="color:var(--ink-faint);font-size:12px;white-space:nowrap;">${u.joined}</td>
      <td>
        <div class="action-group">
          <button class="act-btn primary" title="View profile" onclick="location.href='user-detail.html?id=${u.id}'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <button class="act-btn" title="Send message">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </button>
          <button class="act-btn danger" title="Freeze account">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

renderTable(USERS);

/* =========================================================
   TABS
   ========================================================= */
function switchTab(btn, tab){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  let filtered = USERS;
  if(tab==='pending') filtered = USERS.filter(u=>u.status==='pending');
  else if(tab==='professionals') filtered = USERS.filter(u=>u.role==='professional');
  else if(tab==='members') filtered = USERS.filter(u=>u.role==='member');
  else if(tab==='restricted') filtered = USERS.filter(u=>u.status==='suspended'||u.status==='frozen');
  renderTable(filtered);
}

/* =========================================================
   CHECKBOXES / BULK
   ========================================================= */
function handleRowCheck(){
  const checked = document.querySelectorAll('.row-check:checked').length;
  const bar = document.getElementById('bulkBar');
  const cnt = document.getElementById('bulkCount');
  if(checked>0){bar.classList.add('show');cnt.textContent=checked+' user'+(checked>1?'s':'')+' selected';}
  else{bar.classList.remove('show');}
}
function toggleSelectAll(cb){
  document.querySelectorAll('.row-check').forEach(c=>c.checked=cb.checked);
  handleRowCheck();
}
function clearSelection(){
  document.querySelectorAll('.row-check').forEach(c=>c.checked=false);
  document.getElementById('selectAll').checked=false;
  document.getElementById('bulkBar').classList.remove('show');
}

/* =========================================================
   DRAWER
   ========================================================= */
let currentUser = null;

function openDrawer(id){
  const u = USERS.find(x=>x.id===id);
  if(!u) return;
  currentUser = u;
  document.getElementById('drawerName').textContent = u.name;
  document.getElementById('drawerEmail').textContent = u.email;
  document.getElementById('drawerBody').innerHTML = userDetailHTML(u);
  document.getElementById('drawerFooter').innerHTML = footerHTML(u);
  document.getElementById('detailDrawer').classList.add('open');
  document.getElementById('drawerOverlay').classList.add('show');
}

function userDetailHTML(u){
  const exp = expertiseLabel(u.expertise);
  const role = roleLabel(u.role);
  const st = statusLabel(u.status);
  const progPct = Math.min(100, Math.round((u.points/10000)*100));

  return `
  <div class="profile-hero">
    <div class="profile-avatar-lg">${avatarImgHtml(u)}</div>
    <div class="profile-hero-info">
      <div class="user-name">${u.name}</div>
      <div class="user-email">${u.email}</div>
      <div class="profile-badges">
        <span class="role-badge ${role.cls}" style="font-size:11px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px;stroke-width:2.5;"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
          ${role.label}
        </span>
        <span class="exp-badge ${exp.cls}" style="font-size:11px;">${exp.label}</span>
        <span class="status-dot ${st}" style="font-size:11.5px;">${u.status.charAt(0).toUpperCase()+u.status.slice(1)}</span>
      </div>
    </div>
  </div>

  ${u.pendingVerif ? `
  <div class="verify-strip">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    <div class="verify-strip-text">Verification pending review — ${u.verification}</div>
    <div class="verify-strip-actions">
      <button class="vbtn approve" onclick="approveUser()">Approve</button>
      <button class="vbtn reject" onclick="rejectUser()">Reject</button>
    </div>
  </div>` : ''}

  <div class="info-section">
    <div class="info-section-title">Professional Info</div>
    <div class="info-grid">
      <div class="info-item"><label>Company</label><value>${u.company}</value></div>
      <div class="info-item"><label>Position</label><value>${u.position}</value></div>
      <div class="info-item"><label>Experience</label><value>${u.experience}</value></div>
      <div class="info-item"><label>Plant Focus</label><value>${u.plant}</value></div>
      <div class="info-item full"><label>Verification Status</label><value>${u.verification}</value></div>
    </div>
  </div>

  <div class="info-section">
    <div class="info-section-title">Contribution & Activity</div>
    <div class="activity-bar"><span class="label">Total Points</span><span class="val">${u.points.toLocaleString()} pts</span></div>
    <div class="activity-bar"><span class="label">Questions Asked</span><span class="val">${u.questions}</span></div>
    <div class="activity-bar"><span class="label">Answers Given</span><span class="val">${u.answers}</span></div>
    <div class="contrib-progress">
      <label><span>Progress to Top Contributor (10,000 pts)</span><span>${u.points.toLocaleString()} / 10,000</span></label>
      <div class="progress-track"><div class="progress-fill" style="width:${progPct}%;"></div></div>
    </div>
  </div>

  <div class="info-section">
    <div class="info-section-title">Admin Actions</div>
    <div class="action-row">
      <button class="drawer-act-btn primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Profile
      </button>
      <button class="drawer-act-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18.4 8.6 13 14l-3-3-4.5 4.5"/></svg>
        View Activity Log
      </button>
      <button class="drawer-act-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.82 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>
        View Q&amp;A Posts
      </button>
      <button class="drawer-act-btn" onclick="openFreezeModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/></svg>
        Suspend
      </button>
      <button class="drawer-act-btn danger" onclick="openFreezeModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Freeze Account
      </button>
    </div>
  </div>

  <div class="info-section">
    <div class="info-section-title">Admin Note</div>
    <textarea class="note-area" placeholder="Add internal note about this user (visible to admins only)…"></textarea>
  </div>

  <div class="info-section">
    <div class="info-section-title">Activity Timeline</div>
    <div class="timeline">
      <div class="timeline-item">
        <div class="timeline-dot green"></div>
        <div class="timeline-content">
          <div class="t-title">Account verified — company email confirmed</div>
          <div class="t-time">14 Jan 2024, 09:32</div>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-dot"></div>
        <div class="timeline-content">
          <div class="t-title">Answered "High pressure drop across primary reformer catalyst"</div>
          <div class="t-time">3 Feb 2024, 14:15</div>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-dot"></div>
        <div class="timeline-content">
          <div class="t-title">Submitted 2 documents via Help Improve Charley (+100 pts)</div>
          <div class="t-time">22 Mar 2024, 11:00</div>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-dot warn"></div>
        <div class="timeline-content">
          <div class="t-title">Verification renewal reminder sent — due in 30 days</div>
          <div class="t-time">10 Jun 2025, 08:00</div>
        </div>
      </div>
    </div>
  </div>`;
}

function footerHTML(u){
  if(u.pendingVerif){
    return `
    <button class="drawer-act-btn success" onclick="approveUser()" style="flex:1;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      Approve Verification
    </button>
    <button class="drawer-act-btn danger" onclick="rejectUser()" style="flex:1;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
      Reject Verification
    </button>`;
  }
  return `
  <button class="drawer-act-btn primary" onclick="approveUser()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
    Re-verify
  </button>
  <button class="drawer-act-btn danger" onclick="openFreezeModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    Freeze Account
  </button>
  <button class="drawer-act-btn" onclick="sendMessage()" style="margin-left:auto;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Message
  </button>`;
}

function closeDrawer(){
  document.getElementById('detailDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('show');
}

/* =========================================================
   MODALS & ACTIONS
   ========================================================= */
function openFreezeModal(){ document.getElementById('freezeModal').classList.add('show'); }
function closeFreezeModal(){ document.getElementById('freezeModal').classList.remove('show'); }
function confirmFreeze(){
  closeFreezeModal();
  closeDrawer();
  showToast('Account frozen and notification sent.', 'red');
}
function approveUser(){
  closeDrawer();
  showToast('Verification approved — user notified.', 'green');
}
function rejectUser(){
  closeDrawer();
  showToast('Verification rejected — user notified.', 'amber');
}
function sendMessage(){ showToast('Opening message composer…'); }

/* =========================================================
   TOAST
   ========================================================= */
function showToast(msg, color='blue'){
  const colors={blue:'#3B82F6',green:'#10B981',red:'#EF4444',amber:'#F59E0B'};
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:28px;right:28px;z-index:999;background:#fff;border:1px solid var(--border-soft);border-left:4px solid ${colors[color]};border-radius:12px;padding:14px 18px;font-size:13.5px;font-weight:600;color:var(--ink);box-shadow:var(--shadow-lg);animation:dropdownIn .2s ease;max-width:340px;`;
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),3000);
}
