/* ===== Page-specific source: pages/user-detail.html ===== */
const RECOGNITIONS = [
  {month:'June 2025',    rank:1, pts:11340, q:45,  a:130, docs:20},
  {month:'March 2025',   rank:1, pts:9820,  q:38,  a:104, docs:14},
  {month:'November 2024',rank:2, pts:8100,  q:31,  a:89,  docs:11},
  {month:'August 2024',  rank:1, pts:7450,  q:28,  a:76,  docs:9},
  {month:'April 2024',   rank:2, pts:5900,  q:20,  a:55,  docs:7},
  {month:'January 2024', rank:1, pts:4800,  q:16,  a:44,  docs:6},
  {month:'October 2023', rank:3, pts:3900,  q:12,  a:33,  docs:4},
  {month:'June 2023',    rank:2, pts:2750,  q:9,   a:22,  docs:3},
];
const RANK_MEDAL={1:'🥇',2:'🥈',3:'🥉'};
const RANK_COLOR={1:'#B45309',2:'#64748B',3:'#854D0E'};

function openRecogPopup(){
  const list=document.getElementById('recogList');
  list.innerHTML=RECOGNITIONS.map((r,i)=>`
    <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;background:${r.rank===1?'linear-gradient(135deg,rgba(245,158,11,0.08),rgba(251,191,36,0.04))':'#F8FAFC'};border:1px solid ${r.rank===1?'rgba(245,158,11,0.2)':'var(--border-soft)'};">
      <span style="font-size:20px;flex-shrink:0;">${RANK_MEDAL[r.rank]||'🏅'}</span>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--ink);">${r.month}</div>
        <div style="font-size:12px;color:var(--ink-faint);margin-top:2px;">Rank <strong style="color:${RANK_COLOR[r.rank]};">#${r.rank}</strong> · ${r.pts.toLocaleString()} pts · ${r.q} questions · ${r.a} answers · ${r.docs} documents upload</div>
      </div>
      ${i<5?`<svg width="14" height="14" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`:'<span style="font-size:12px;color:var(--ink-faint);font-weight:600;">+star</span>'}
    </div>
  `).join('');

  document.getElementById('recogModal').classList.add('show');
}
function closeRecogPopup(){
  document.getElementById('recogModal').classList.remove('show');
}

// ===== POINTS HISTORY POPUP =====
const POINTS_HISTORY = [
  {date:'14 Jun 2025', action:'Answer — High pressure drop across primary reformer catalyst bed', pts:+30, type:'answer'},
  {date:'12 Jun 2025', action:'Document upload approved — Reformer Operating Guide', pts:+50, type:'upload'},
  {date:'2 Jun 2025',  action:'Question posted — PSA tail gas composition shift', pts:+10, type:'question'},
  {date:'18 May 2025', action:'Answer — Reformer tube hot band visual inspection criteria', pts:+30, type:'answer'},
  {date:'5 May 2025',  action:'Answer — WGS catalyst deactivation symptoms vs sulfur poisoning', pts:+30, type:'answer'},
  {date:'22 Apr 2025', action:'Question posted — Secondary reformer outlet temperature uncertainty', pts:+10, type:'question'},
  {date:'18 Apr 2025', action:'3 documents submitted via Help Improve — 2 approved', pts:+150, type:'upload'},
  {date:'1 Apr 2025',  action:'Monthly Expert Recognition award — March 2025 Rank #1', pts:+200, type:'award'},
  {date:'12 Mar 2025', action:'Answer — Ammonia loop purge rate optimisation strategy', pts:+30, type:'answer'},
  {date:'5 Mar 2025',  action:'Answer — Methanator temperature excursion root cause', pts:+30, type:'answer'},
  {date:'20 Feb 2025', action:'Document upload approved — PSA Cycle Analysis Report', pts:+50, type:'upload'},
  {date:'14 Feb 2025', action:'Answer — Pressure swing absorber bed regeneration failure mode', pts:+30, type:'answer'},
  {date:'7 Feb 2025',  action:'Question posted — CO₂ removal solvent degradation indicators', pts:+10, type:'question'},
  {date:'1 Feb 2025',  action:'Monthly Expert Recognition award — January 2025 Rank #1', pts:+200, type:'award'},
  {date:'18 Jan 2025', action:'Answer — HTS catalyst reduction procedure optimisation', pts:+30, type:'answer'},
  {date:'10 Jan 2025', action:'Question posted — Steam-to-carbon ratio monitoring during turndown', pts:+10, type:'question'},
  {date:'3 Jan 2025',  action:'Answer — Reformer flue gas O₂ trim control tuning', pts:+30, type:'answer'},
  {date:'20 Dec 2024', action:'Document upload approved — Hydrogen Purity Benchmark Study', pts:+50, type:'upload'},
  {date:'15 Dec 2024', action:'Answer — Pre-reformer catalyst deactivation early signs', pts:+30, type:'answer'},
  {date:'8 Dec 2024',  action:'Monthly Expert Recognition award — November 2024 Rank #2', pts:+100, type:'award'},
];

const PTS_PER_PAGE = 7;
let ptsCurrentPage = 1;

const PTS_TYPE_COLOR = {answer:'#2563EB', question:'#059669', upload:'#7C3AED', award:'#B45309'};
const PTS_TYPE_LABEL = {answer:'Answer', question:'Question', upload:'Upload', award:'Award'};

function openPointsPopup() {
  ptsCurrentPage = 1;
  renderPointsPage();
  document.getElementById('pointsModal').classList.add('show');
}
function closePointsPopup() {
  document.getElementById('pointsModal').classList.remove('show');
}
function renderPointsPage() {
  const total = POINTS_HISTORY.length;
  const totalPages = Math.ceil(total / PTS_PER_PAGE);
  const start = (ptsCurrentPage - 1) * PTS_PER_PAGE;
  const slice = POINTS_HISTORY.slice(start, start + PTS_PER_PAGE);

  const wrap = document.getElementById('pointsListWrap');
  wrap.innerHTML = slice.map(r => {
    const col = PTS_TYPE_COLOR[r.type] || '#64748B';
    const lbl = PTS_TYPE_LABEL[r.type] || r.type;
    return `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:11px;background:#F8FAFC;border:1px solid var(--border-soft);">
        <div style="flex:1;min-width:0;">
          <div style="font-size:12.5px;font-weight:600;color:var(--ink);line-height:1.4;">${r.action}</div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <span style="font-size:11.5px;font-weight:700;color:${col};background:${col}18;padding:2px 8px;border-radius:20px;">${lbl}</span>
            <span style="font-size:11.5px;color:var(--ink-faint);font-weight:500;">${r.date}</span>
          </div>
        </div>
        <span style="font-size:14px;font-weight:800;color:#059669;white-space:nowrap;flex-shrink:0;">+${r.pts} pts</span>
      </div>`;
  }).join('');

  // Pagination
  const pg = document.getElementById('pointsPagination');
  pg.innerHTML = '';
  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.style.cssText = `width:30px;height:30px;border-radius:8px;border:1px solid ${i===ptsCurrentPage?'var(--accent-blue)':'var(--border)'};background:${i===ptsCurrentPage?'var(--accent-blue)':'#fff'};color:${i===ptsCurrentPage?'#fff':'var(--ink-soft)'};font-size:12.5px;font-weight:700;cursor:pointer;transition:.13s;`;
    btn.onclick = () => { ptsCurrentPage = i; renderPointsPage(); };
    pg.appendChild(btn);
  }
  document.getElementById('pointsPageInfo').textContent = `Page ${ptsCurrentPage} of ${totalPages} · ${total} entries`;
}

// ===== CONNECTIONS POPUP =====
const ALL_CONNECTIONS = [
  {initials:'AA', bg:'linear-gradient(135deg,#4F8DFD,#6366F1)', name:'Ahmed Al-Rashidi',     role:'Senior Process Engineer',   company:'Saudi Aramco',       date:'12 Jan 2025'},
  {initials:'PN', bg:'linear-gradient(135deg,#10B981,#06B6D4)', name:'Priya Nair',            role:'Plant Engineer',            company:'IFFCO',              date:'5 Mar 2025'},
  {initials:'RS', bg:'linear-gradient(135deg,#F59E0B,#EF4444)', name:'Ravi Shankar',          role:'Lead Process Engineer',     company:'Toyo Engineering',   date:'18 Feb 2025'},
  {initials:'MK', bg:'linear-gradient(135deg,#6366F1,#8B5CF6)', name:'Maria Kowalski',        role:'Process Engineer',          company:'BASF SE',            date:'20 Nov 2024'},
  {initials:'TL', bg:'linear-gradient(135deg,#06B6D4,#3B82F6)', name:'Thomas Lindqvist',      role:'Chief Engineer',            company:'Haldor Topsoe',      date:'9 Oct 2024'},
  {initials:'FA', bg:'linear-gradient(135deg,#F59E0B,#10B981)', name:'Fatima Al-Mansoori',    role:'Reliability Engineer',      company:'ADNOC',              date:'7 Sep 2024'},
  {initials:'JB', bg:'linear-gradient(135deg,#EC4899,#6366F1)', name:'Jean-Luc Bertrand',     role:'Senior Consultant',         company:'Air Liquide',        date:'14 Aug 2024'},
  {initials:'HW', bg:'linear-gradient(135deg,#0F172A,#3B82F6)', name:'Hans Werner',           role:'Plant Manager',             company:'Yara International', date:'2 Jul 2024'},
  {initials:'SI', bg:'linear-gradient(135deg,#10B981,#3B82F6)', name:'Suleiman Ibrahim',      role:'Process Specialist',        company:'OCI Nitrogen',       date:'19 Jun 2024'},
  {initials:'CL', bg:'linear-gradient(135deg,#F59E0B,#6366F1)', name:'Chen Lei',              role:'Senior Engineer',           company:'Sinopec',            date:'30 May 2024'},
  {initials:'AV', bg:'linear-gradient(135deg,#EF4444,#F59E0B)', name:'Aleksei Volkov',        role:'Process Engineer',          company:'EuroChem',           date:'11 Apr 2024'},
  {initials:'NB', bg:'linear-gradient(135deg,#06B6D4,#10B981)', name:'Nadia Bouchard',        role:'Catalyst Engineer',         company:'Johnson Matthey',    date:'3 Mar 2024'},
  {initials:'PK', bg:'linear-gradient(135deg,#6366F1,#06B6D4)', name:'Pieter Klaassen',       role:'Lead Engineer',             company:'Yara Sluiskil',      date:'22 Feb 2024'},
  {initials:'SK', bg:'linear-gradient(135deg,#3B82F6,#10B981)', name:'Sunita Krishnamurthy',  role:'Process Engineer',          company:'ONGC',               date:'10 Jan 2024'},
  {initials:'RV', bg:'linear-gradient(135deg,#F59E0B,#EF4444)', name:'Roberto Valletta',      role:'Senior Process Engineer',   company:'Tecnimont',          date:'5 Dec 2023'},
  {initials:'BH', bg:'linear-gradient(135deg,#8B5CF6,#EC4899)', name:'Brigitte Hoffmann',     role:'Operations Manager',        company:'Linde Engineering',  date:'18 Nov 2023'},
  {initials:'KM', bg:'linear-gradient(135deg,#06B6D4,#6366F1)', name:'Kenji Matsuda',         role:'Engineering Specialist',    company:'Mitsubishi Corp',    date:'7 Oct 2023'},
  {initials:'OD', bg:'linear-gradient(135deg,#10B981,#F59E0B)', name:'Oluwaseun Dada',        role:'Process Engineer',          company:'Dangote Fertiliser', date:'29 Sep 2023'},
  {initials:'YK', bg:'linear-gradient(135deg,#3B82F6,#EF4444)', name:'Yusuf Kaya',            role:'Plant Operations Lead',     company:'TÜPRAŞ',             date:'14 Aug 2023'},
  {initials:'LM', bg:'linear-gradient(135deg,#6366F1,#10B981)', name:'Luisa Mendes',          role:'Instrumentation Engineer',  company:'Petrobras',          date:'2 Jul 2023'},
  {initials:'AR', bg:'linear-gradient(135deg,#EC4899,#F59E0B)', name:'Andrei Rudnev',         role:'Senior Process Engineer',   company:'PhosAgro',           date:'20 Jun 2023'},
  {initials:'GM', bg:'linear-gradient(135deg,#06B6D4,#EF4444)', name:'Giovanni Marchetti',    role:'Process Consultant',        company:'Casale SA',          date:'8 May 2023'},
  {initials:'MH', bg:'linear-gradient(135deg,#3B82F6,#8B5CF6)', name:'Mohammed Hassan',       role:'Lead Process Engineer',     company:'Ma\'aden',           date:'26 Apr 2023'},
  {initials:'TC', bg:'linear-gradient(135deg,#10B981,#EC4899)', name:'Thi Cam Nguyen',        role:'Plant Engineer',            company:'PVFCCo',             date:'13 Mar 2023'},
  {initials:'IB', bg:'linear-gradient(135deg,#F59E0B,#06B6D4)', name:'Ibrahim Al-Bahrani',    role:'Senior Engineer',           company:'Gulf Petrochemical', date:'1 Feb 2023'},
  {initials:'EP', bg:'linear-gradient(135deg,#6366F1,#EF4444)', name:'Elena Papadopoulos',    role:'Catalyst Specialist',       company:'Clariant AG',        date:'19 Jan 2023'},
  {initials:'DT', bg:'linear-gradient(135deg,#EF4444,#3B82F6)', name:'David Tanner',          role:'Operations Director',       company:'CF Industries',      date:'5 Dec 2022'},
  {initials:'AO', bg:'linear-gradient(135deg,#10B981,#6366F1)', name:'Amara Osei',            role:'Process Engineer',          company:'Yara Ghana',         date:'20 Nov 2022'},
];

let connSearchTerm = '';

function openConnectionsPopup() {
  connSearchTerm = '';
  document.getElementById('connSearchInput').value = '';
  renderConnections();
  document.getElementById('connectionsModal').classList.add('show');
}
function closeConnectionsPopup() {
  document.getElementById('connectionsModal').classList.remove('show');
}
function filterConnections() {
  connSearchTerm = document.getElementById('connSearchInput').value.toLowerCase();
  renderConnections();
}
function renderConnections() {
  const filtered = ALL_CONNECTIONS.filter(c =>
    c.name.toLowerCase().includes(connSearchTerm) ||
    c.company.toLowerCase().includes(connSearchTerm) ||
    c.role.toLowerCase().includes(connSearchTerm)
  );
  const wrap = document.getElementById('connListWrap');
  if (filtered.length === 0) {
    wrap.innerHTML = `<div style="text-align:center;padding:32px 0;color:var(--ink-faint);font-size:13px;font-weight:500;">No connections found for "${connSearchTerm}"</div>`;
  } else {
    wrap.innerHTML = filtered.map(c => `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:11px;transition:.13s;cursor:default;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
        <div style="width:38px;height:38px;border-radius:11px;background:${c.bg};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#fff;flex-shrink:0;">${c.initials}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;color:var(--ink);">${c.name}</div>
          <div style="font-size:12px;color:var(--ink-soft);font-weight:500;margin-top:1px;">${c.role} · ${c.company}</div>
        </div>
        <div style="font-size:11.5px;color:var(--ink-faint);font-weight:500;white-space:nowrap;flex-shrink:0;">${c.date}</div>
      </div>
    `).join('');
  }
  document.getElementById('connCountLabel').textContent = connSearchTerm
    ? `${filtered.length} of ${ALL_CONNECTIONS.length} connections`
    : `${ALL_CONNECTIONS.length} connections total`;
}

function removeKw(btn){
  btn.closest('.kw-tag').remove();
  showToast('Keyword removed','amber');
}
function addKw(){
  const inp=document.getElementById('kwInput');
  const val=inp.value.trim();
  if(!val) return;
  const wrap=document.getElementById('kwWrap');
  const tag=document.createElement('span');
  tag.className='kw-tag';
  tag.innerHTML=val+' <button onclick="removeKw(this)" title="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
  wrap.appendChild(tag);
  inp.value='';
  showToast('Keyword added','green');
}
