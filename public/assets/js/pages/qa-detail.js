/* ===== Page-specific source: pages/qa-detail.html ===== */
// ===================== QUESTION DATA =====================
const QUESTIONS = {
  1:{
    title:'High pressure drop across primary reformer catalyst — is this normal after 3 years?',
    plant:'Ammonia', topic:'Troubleshooting', publish:'published', flagged:true,
    flagReason:'Reported by <b>2 members</b> and auto-flagged by the confidentiality filter: the attached pressure-trend file and inspection photo may contain plant-specific operating data that should be reviewed before staying public.',
    author:'Anonymous', anonymous:true, adminIdentity:'Ahmed Ghani (Process Technology Manager, Linde plc)',
    posted:'12 Jul 2026, 09:14', views:312,
    attachments:['pressure-trend.xlsx','catalyst-inspection-photo.jpg'],
    body:'ΔP has climbed steadily over the last 6 months, now sitting at 1.8 bar vs 1.1 bar at start-of-run. Catalyst is 3 years into a 5-year design life. No unusual feed upsets that we know of. Attached the pressure trend and a photo from the last shutdown inspection. Is this normal ageing, or a sign of channelling / tube breakage that warrants an early catalyst change?',
    answers:[
      {author:'Johan Dahl', role:'Experienced Professional', confidence:5, featured:true, text:'A ΔP rise of this magnitude over 3 years is on the high side but not unheard of, especially with sulfur upsets. Before committing to an early change-out, check for asymmetric ΔP across parallel tube rows — that pattern points to localised breakage rather than uniform ageing.'},
      {author:'Priya Nataraj', role:'Senior Process Engineer', confidence:4, text:'Worth pulling a few pellets from the top layer next shutdown to check for physical degradation. We saw a similar trend that turned out to be dust accumulation from an upstream desulfuriser breakthrough.'},
      {author:'Global Reformer Parts Co.', role:'Unverified account', confidence:1, flagged:true, flagReason:'Contains an unsolicited commercial pitch and an external contact link from an account outside the verified Partner Directory — matches the automatic spam / advertisement detection rule.', text:'We sell replacement reformer catalyst at half the price of your current licensor — message us directly off-platform and we will send a quote today, no need to go through official channels.'},
      {author:'Synvex Catalysts', role:'Diamond Partner', confidence:3, text:'Happy to run a remote ΔP diagnostic against our reformer catalyst database if you can share the full pressure and temperature trend — this pattern is common enough that we track it across several clients.'},
    ]
  },
  2:{
    title:'LTS CO slip increased right after startup — normal drift or catalyst issue?',
    plant:'Hydrogen', topic:'Troubleshooting', publish:'published', flagged:false,
    author:'Priya Nataraj', role:'Senior Process Engineer', anonymous:false,
    posted:'14 Jul 2026, 03:20', views:145,
    attachments:[],
    body:'CO slip jumped from 0.3% to 0.9% within 48 hours of a cold startup. Feed composition and steam-to-gas ratio both look unchanged versus the pre-shutdown baseline. Bed temperature profile looks normal on the skin TCs. Could this just be re-equilibration after the cold soak, or should we be worried about catalyst poisoning?',
    answers:[
      {author:'Marco Ferretti', role:'Senior Industry Expert', confidence:4, text:'A short-lived CO slip bump after a cold startup is common as the bed re-equilibrates thermally — usually settles within 3-5 days. If it is still elevated after a week, then I would start looking at poisoning (chloride or sulfur slip from upstream).'},
    ]
  },
  3:{
    title:'Methanator temperature excursion during load swing — IOW guidance?',
    plant:'Ammonia', topic:'IOW', publish:'published', flagged:false,
    author:'Marco Ferretti', role:'Senior Industry Expert', anonymous:false,
    posted:'07 Jul 2026, 16:45', views:480,
    attachments:[],
    body:'Bed temperature spikes roughly 40°C above normal whenever we ramp from 70% to 100% load in under 20 minutes. We currently do not have a documented IOW for maximum ramp rate into the methanator. Would appreciate real-world numbers other plants are using, and how the excursion risk is typically managed operationally.',
    answers:[
      {author:'Fatima Al-Rashid', role:'Experienced Professional', confidence:5, text:'Most licensor guidance caps ramp rate into the methanator at around 5%/min once CO+CO2 in the feed exceeds ~0.5%. We enforce a hard IOW of 15 minutes minimum from 70% to 100% load specifically because of this excursion risk, with an automatic load-ramp hold if inlet CO2 crosses a set threshold.'},
      {author:'Johan Dahl', role:'Experienced Professional', confidence:4, text:'Also worth checking your upstream CO2 removal system performance during the swing — a slower amine system response during fast ramps can transiently push more CO2 into the methanator feed, which is often the real driver of the excursion rather than the ramp rate itself.'},
    ]
  },
  4:{
    title:'CO2 absorber foaming after amine change-out',
    plant:'Ammonia', topic:'Troubleshooting', publish:'published', flagged:false,
    author:'Synvex Catalysts', role:'Diamond Partner', anonymous:false,
    posted:'11 Jul 2026, 11:02', views:210,
    attachments:[],
    body:'A client plant started seeing foaming and liquid carryover in the CO2 absorber within a week of switching to a new activated MDEA blend. Anti-foam dosing has had limited effect so far. Looking for input from anyone who has dealt with foaming shortly after an amine change-out, particularly around whether it is more likely a contamination issue or an incompatibility with the new blend.',
    answers:[
      {author:'Lars Eriksson', role:'Senior Industry Expert', confidence:4, text:'In our experience this is almost always trace hydrocarbon or corrosion-inhibitor carryover from the old amine system rather than the new blend itself. Worth doing a full solvent analysis (surfactant screen) before assuming incompatibility — a proper flush and filter change resolved it for us within days.'},
      {author:'Fatima Al-Rashid', role:'Experienced Professional', confidence:3, text:'Agree with the contamination angle. Also check if the new blend has a different foam-tendency spec on its datasheet — some activated MDEA blends are simply more foam-prone at the same anti-foam dosing rate.'},
    ]
  },
  5:{
    title:'Secondary reformer outlet temperature measurement error?',
    plant:'Ammonia', topic:'Design', publish:'pending', flagged:false,
    author:'Lars Eriksson', role:'Senior Industry Expert', anonymous:false,
    posted:'12 Jul 2026, 08:47', views:98,
    attachments:[],
    body:'Skin thermocouples and the process thermocouple on the secondary reformer outlet have drifted apart by roughly 25°C over the last quarter. Which measurement should we trust for control purposes, and is there a standard way to reconcile the two?',
    answers:[]
  },
  6:{
    title:'Suspicious answer promoting an unlicensed catalyst supplier — flagging for review',
    plant:'General', topic:'Safety', publish:'pending', flagged:true,
    flagReason:'Reported by <b>3 members</b> and auto-detected by the commercial-content filter: one of the answers on this thread contains an unsolicited advertisement and a direct contact channel for a supplier that is not in the verified Partner Directory.',
    author:'Anonymous', anonymous:true, adminIdentity:'Marco Ferretti (Senior Industry Expert)',
    posted:'14 Jul 2026, 02:10', views:54,
    attachments:[],
    body:'One of the answers on this thread reads like an unsolicited advertisement for a supplier that is not in our verified Partner Directory, with a contact number embedded directly in the answer text. Flagging this for admin review — recommend removing the answer and checking the account for repeat commercial-content violations.',
    answers:[
      {author:'Reformer Solutions Ltd', role:'Unverified account', confidence:1, flagged:true, flagReason:'Contains a direct commercial contact (WhatsApp) and an unverified pricing/undercut offer — matches the automatic spam / advertisement detection rule.', text:'Contact us directly for a much cheaper catalyst alternative — reach out on WhatsApp for pricing, we beat any licensor quote by 30%.'},
    ]
  },
  7:{
    title:'Reformer tube hot band — inspection interval recommendations',
    plant:'Hydrogen', topic:'Integrity', publish:'published', flagged:false,
    author:'Johan Dahl', role:'Experienced Professional', anonymous:false,
    posted:'30 Jun 2026, 13:38', views:623,
    attachments:['thermal-scan-report.pdf'],
    body:'Thermal imaging picked up a localised hot band on 3 tubes in the same row. Tubes are at approximately 65% of design life. Looking for industry-typical inspection interval recommendations once a hot band is first detected, and what threshold usually triggers a tube replacement decision versus continued monitoring.',
    answers:[
      {author:'Priya Nataraj', role:'Senior Process Engineer', confidence:5, text:'Once a hot band is confirmed on 2+ tubes in the same row, most operators move to a 6-monthly thermal survey (down from annual) plus a dimensional/creep check at the next planned outage. A sustained hot band exceeding design skin temperature by more than 30-40°C is the typical trigger point for replacement planning rather than continued monitoring.'},
      {author:'Fatima Al-Rashid', role:'Experienced Professional', confidence:4, text:'Also worth trending the hot band width over successive surveys — a widening band is a stronger replacement signal than a stable one at similar severity.'},
    ]
  },
  8:{
    title:'Benfield corrosion at CO2 stripper bottom — material selection?',
    plant:'Ammonia', topic:'Material Selection', publish:'published', flagged:false,
    author:'Fatima Al-Rashid', role:'Experienced Professional', anonymous:false,
    posted:'10 Jul 2026, 17:25', views:176,
    attachments:[],
    body:'Localised corrosion was found on the stripper bottom shell during a recent turnaround. Current material is carbon steel with a corrosion inhibitor dosed into the Benfield solution. Looking for input on whether a cladding or full material upgrade is typically justified at this stage, versus continued inhibitor-based mitigation.',
    answers:[
      {author:'Lars Eriksson', role:'Senior Industry Expert', confidence:4, text:'Depends heavily on remaining wall thickness versus corrosion rate trend. If the rate is stable and inhibitor dosing is well controlled, many plants continue with carbon steel plus a more aggressive inspection interval rather than an immediate upgrade. A 300-series stainless clad on the bottom head is the common upgrade path once localised pitting starts, though.'},
    ]
  },
  9:{
    title:'Process air compressor limiting plant rate — surge margin question',
    plant:'Ammonia', topic:'Optimization', publish:'published', flagged:false,
    author:'Charley Admin', role:'Seed content', anonymous:false,
    posted:'14 Jun 2026, 10:00', views:22,
    attachments:[],
    body:'Cold-start seed question: what surge margin do plants typically design or operate to on the process air compressor, and how is this traded off against maximising plant rate? Looking for a range of real operating practices to seed discussion.',
    answers:[]
  },
  10:{
    title:'WHB leakage symptoms — tube or header?',
    plant:'Hydrogen', topic:'Troubleshooting', publish:'published', flagged:false,
    author:'Anonymous', anonymous:true, adminIdentity:'Priya Nataraj (Senior Process Engineer)',
    posted:'09 Jul 2026, 20:16', views:289,
    attachments:[],
    body:'Seeing a slow, steady rise in BFW makeup and a faint knocking sound near the waste heat boiler. Trying to narrow down whether this points to a tube leak or a header issue before committing to the scope for the next outage.',
    answers:[
      {author:'Johan Dahl', role:'Experienced Professional', confidence:5, text:'A slow, steady BFW makeup rise with an audible knock is a classic small tube leak signature rather than a header issue — headers tend to fail more abruptly with a sharper makeup-rate change. Worth doing an eddy-current scan on the suspect bank before the outage to pre-identify the tube if possible.'},
      {author:'Marco Ferretti', role:'Senior Industry Expert', confidence:3, text:'Agree with the tube-leak read. Also check BFW chemistry for a rising iron/copper trend — that can help confirm before you open up.'},
      {author:'Fatima Al-Rashid', role:'Experienced Professional', confidence:4, text:'One more data point: if the knock is intermittent and load-dependent, that also leans tube-leak, since header cracks are typically more constant once initiated.'},
    ]
  },
};

const AVATAR_COLORS = {
  'Johan Dahl':'linear-gradient(135deg,#EC4899,#F43F5E)',
  'Priya Nataraj':'linear-gradient(135deg,#F59E0B,#EF4444)',
  'Synvex Catalysts':'linear-gradient(135deg,#6366F1,#8B5CF6)',
  'Marco Ferretti':'linear-gradient(135deg,#10B981,#06B6D4)',
  'Fatima Al-Rashid':'linear-gradient(135deg,#F59E0B,#F43F5E)',
  'Lars Eriksson':'linear-gradient(135deg,#06B6D4,#3B82F6)',
  'Charley Admin':'linear-gradient(135deg,#334155,#0F172A)',
  'Reformer Solutions Ltd':'linear-gradient(135deg,#94A3B8,#64748B)',
  'Ahmed Ghani':'linear-gradient(135deg,#8B5CF6,#6366F1)',
};

const AVATAR_CLASSES = {
  'Johan Dahl':'avatar-johan-dahl',
  'Priya Nataraj':'avatar-priya-nataraj',
  'Synvex Catalysts':'avatar-synvex-catalysts',
  'Marco Ferretti':'avatar-marco-ferretti',
  'Fatima Al-Rashid':'avatar-fatima-al-rashid',
  'Lars Eriksson':'avatar-lars-eriksson',
  'Charley Admin':'avatar-charley-admin',
  'Reformer Solutions Ltd':'avatar-reformer-solutions',
  'Ahmed Ghani':'avatar-ahmed-ghani',
};

// Additional profile metadata shown in the "Posted By" sidebar card
const AUTHOR_INFO = {
  'Johan Dahl':{accountType:'Verified Professional', company:'Yara Sluiskil', memberSince:'Aug 2021'},
  'Priya Nataraj':{accountType:'Verified Professional', company:'NextGen Ammonia', memberSince:'Jan 2023'},
  'Synvex Catalysts':{accountType:'Diamond Partner', company:'Synvex Catalysts', memberSince:'Mar 2024'},
  'Marco Ferretti':{accountType:'Verified Professional', company:'EuroSyngas Consulting', memberSince:'Sep 2020'},
  'Fatima Al-Rashid':{accountType:'Verified Professional', company:'Gulf Nitrogen Products', memberSince:'Nov 2022'},
  'Lars Eriksson':{accountType:'Verified Professional', company:'Nordic Ammonia AB', memberSince:'Feb 2022'},
  'Charley Admin':{accountType:'Platform Admin', company:'Charley', memberSince:'—'},
  'Reformer Solutions Ltd':{accountType:'Unverified Account', company:'Unknown / Unverified', memberSince:'Jul 2026'},
  'Ahmed Ghani':{accountType:'Verified Professional', company:'Linde plc', memberSince:'Mar 2024'},
};

function initials(name){
  return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
}

function avatarClass(name){
  return AVATAR_CLASSES[name] || 'avatar-default';
}

function escapeHtml(str){
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

let currentQuestion = null;
let answerCounter = 0;

// ----- Answer sub-render helpers -----
function answerBadgesHtml(a){
  let html = '';
  if(a.featured){
    html += `<span class="status-pill featured"><svg class="icon"><use href="../assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Admin Featured</span>`;
  }
  if(a.flagged){
    html += `<span class="status-pill flagged"><svg class="icon"><use href="../assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14"></use></svg>Flagged</span>`;
  }
  return html;
}

function flagNoticeHtml(a,i){
  if(!a.flagged) return '';
  return `
    <div class="qd-answer-flag-notice" id="answerFlagNotice${i}">
      <svg class="icon"><use href="../assets/icons/sprite.svg#icon-danger-zone-div-class-set"></use></svg>
      <div class="qd-answer-flag-notice-text"><b>Flagged as spam / advertisement:</b> ${a.flagReason || 'Reported as potential spam or unauthorized commercial content.'}</div>
      <button class="qd-mark-safe-btn" onclick="markAnswerSafe(${i})">
        <svg class="icon"><use href="../assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>
        Mark as Safe
      </button>
    </div>`;
}

function featureBtnHtml(a){
  return a.featured
    ? `<svg class="icon"><use href="../assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Featured — Unfeature`
    : `<svg class="icon"><use href="../assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Feature Answer`;
}

function answerBlockHtml(a,i){
  return `
    <div class="qd-answer ${a.flagged?'flagged-answer':''} ${a.featured?'featured-answer':''}" id="answerBlock${i}">
      <div class="qd-answer-head">
        <div class="qd-answer-author">
          <div class="qd-author-avatar ${avatarClass(a.author)}">${initials(a.author)}</div>
          <div>
            <div class="qd-answer-author-name">${a.author}</div>
            <div class="qd-answer-author-role">${a.role}</div>
          </div>
        </div>
        <div class="qd-answer-badges" id="answerBadges${i}">${answerBadgesHtml(a)}</div>
      </div>
      ${flagNoticeHtml(a,i)}
      <div class="qd-answer-text">${a.text}</div>
      <div id="nestedReplies${i}"></div>
      <div class="qd-answer-foot">
        <div class="qd-answer-foot-left">
          <button class="qd-feature-btn ${a.featured?'active':''}" id="featureBtn${i}" onclick="toggleFeatureAnswer(${i})">${featureBtnHtml(a)}</button>
          <button class="qd-delete-answer-btn" onclick="deleteAnswer(${i})">
            <svg class="icon"><use href="../assets/icons/sprite.svg#icon-delete"></use></svg>
            Delete Answer
          </button>
        </div>
        <button class="qd-reply-btn" onclick="toggleAnswerReply(${i})">
          <svg class="icon"><use href="../assets/icons/sprite.svg#icon-reply-to-answer"></use></svg>
          Reply
        </button>
      </div>
      <div class="qd-reply-composer d-none" id="replyComposer${i}">
        <textarea class="qd-reply-textarea" id="replyText${i}" placeholder="Reply to ${escapeHtml(a.author)}'s answer…"></textarea>
        <div class="qd-reply-actions">
          <button class="btn-ghost" onclick="toggleAnswerReply(${i})">Cancel</button>
          <button class="btn-primary" onclick="publishAnswerReply(${i})">
            <svg class="icon"><use href="../assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>
            Publish Reply
          </button>
        </div>
      </div>
    </div>
  `;
}

// ----- Admin actions: feature answer / mark answer safe / mark question safe -----
function toggleFeatureAnswer(i){
  const a = currentQuestion.answers[i];
  if(!a) return;
  a.featured = !a.featured;
  const block = document.getElementById('answerBlock'+i);
  if(block) block.classList.toggle('featured-answer', a.featured);
  const badges = document.getElementById('answerBadges'+i);
  if(badges) badges.innerHTML = answerBadgesHtml(a);
  const btn = document.getElementById('featureBtn'+i);
  if(btn){
    btn.classList.toggle('active', a.featured);
    btn.innerHTML = featureBtnHtml(a);
  }
  showToast(a.featured ? 'Marked as Admin Featured answer — now highlighted for users' : 'Removed from Admin Featured', a.featured ? 'amber' : 'blue');
}

function markAnswerSafe(i){
  const a = currentQuestion.answers[i];
  if(!a) return;
  a.flagged = false;
  delete a.flagReason;
  const block = document.getElementById('answerBlock'+i);
  if(block) block.classList.remove('flagged-answer');
  const badges = document.getElementById('answerBadges'+i);
  if(badges) badges.innerHTML = answerBadgesHtml(a);
  const notice = document.getElementById('answerFlagNotice'+i);
  if(notice) notice.remove();
  showToast('Answer marked as safe — flag removed','green');
}

function markQuestionSafe(){
  if(!currentQuestion) return;
  currentQuestion.flagged = false;
  delete currentQuestion.flagReason;
  const banner = document.getElementById('qdFlagBanner');
  if(banner) banner.classList.add('d-none');
  const flaggedPill = document.querySelector('#qdBadges .status-pill.flagged');
  if(flaggedPill) flaggedPill.remove();
  showToast('Question marked as safe — flag removed','green');
}

function publishPillHtml(status){
  if(status === 'published') return '<svg class="icon"><use href="../assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>Published';
  if(status === 'unpublished') return '<svg class="icon"><use href="../assets/icons/sprite.svg#icon-un-publish-question-svg-viewbox-0-0"></use></svg>Unpublished';
  return '<svg class="icon"><use href="../assets/icons/sprite.svg#icon-9-verifications-exceeded-the-48h"></use></svg>Pending Approval';
}

function toggleUnpublishQuestion(){
  if(!currentQuestion) return;
  if(currentQuestion.publish === 'unpublished'){
    currentQuestion.publish = currentQuestion._prevPublish || 'published';
  } else {
    currentQuestion._prevPublish = currentQuestion.publish;
    currentQuestion.publish = 'unpublished';
  }
  const cls = currentQuestion.publish;
  const html = publishPillHtml(cls);
  const badgePill = document.getElementById('qdPublishPill');
  if(badgePill){ badgePill.className = 'publish-pill '+cls; badgePill.innerHTML = html; }
  const infoPill = document.getElementById('infoPublishPill');
  if(infoPill){ infoPill.className = 'publish-pill '+cls; infoPill.innerHTML = html; }
  const btnText = document.getElementById('unpublishBtnText');
  if(btnText) btnText.textContent = cls === 'unpublished' ? 'Re-publish Question' : 'Un-publish Question';
  showToast(cls === 'unpublished' ? 'Question un-published — hidden from members' : 'Question re-published — visible to members', cls === 'unpublished' ? 'amber' : 'green');
}

// ----- Delete answer -----
function deleteAnswer(i){
  const a = currentQuestion && currentQuestion.answers[i];
  if(!a) return;
  if(!confirm(`Delete this answer by ${a.author}? This cannot be undone.`)) return;
  const block = document.getElementById('answerBlock'+i);
  if(block) block.remove();
  currentQuestion.answers[i] = null; // keep index alignment for remaining action handlers
  const remaining = currentQuestion.answers.filter(Boolean).length;
  document.getElementById('qdAnswersLabel').textContent = `Answers (${remaining})`;
  document.getElementById('infoAnswers').textContent = remaining;
  if(remaining === 0){
    document.getElementById('qdAnswersList').innerHTML = '<div class="qd-empty-answers">No answers yet — this question is still open.</div>';
  }
  showToast('Answer deleted','red');
}

function renderQuestion(id){
  const q = QUESTIONS[id];
  if(!q){
    document.querySelector('.qd-page-grid').innerHTML = '<div class="qd-main-card">Question not found.</div>';
    return;
  }

  document.getElementById('breadcrumbTitle').textContent = q.title.length > 60 ? q.title.slice(0,60)+'…' : q.title;

  document.getElementById('qdBadges').innerHTML =
    `<span class="qa-mini-badge plant">${q.plant}</span><span class="qa-mini-badge topic">${q.topic}</span>` +
    `<span class="publish-pill ${q.publish}" id="qdPublishPill">${publishPillHtml(q.publish)}</span>` +
    (q.flagged ? '<span class="status-pill flagged"><svg class="icon"><use href="../assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14"></use></svg>Flagged</span>' : '');

  const flagBanner = document.getElementById('qdFlagBanner');
  if(q.flagged){
    flagBanner.classList.remove('d-none');
    document.getElementById('qdFlagBannerText').innerHTML = q.flagReason || 'This question was reported by the community and is awaiting admin review.';
  } else {
    flagBanner.classList.add('d-none');
  }

  document.getElementById('qdTitle').textContent = q.title;
  document.getElementById('qdMeta').innerHTML =
    `<span>${q.anonymous ? 'Posted anonymously' : 'Posted by <strong>'+q.author+'</strong>'+(q.role?' · '+q.role:'')}</span>` +
    `<span>·</span><span>${q.posted}</span><span>·</span><span>${q.views} views</span>`;
  document.getElementById('qdQuestionText').textContent = q.body;

  const noteEl = document.getElementById('qdAdminNote');
  if(q.anonymous && q.adminIdentity){
    noteEl.classList.remove('d-none');
    document.getElementById('qdAdminNoteText').textContent = 'Admin-only: posted by ' + q.adminIdentity;
  } else {
    noteEl.classList.add('d-none');
  }

  // ----- Posted By card -----
  const posterAvatar = document.getElementById('posterAvatar');
  const posterAdminNote = document.getElementById('posterAdminNote');
  if(q.anonymous){
    posterAvatar.className = 'qd-author-avatar qd-author-avatar-lg anon-avatar';
    posterAvatar.innerHTML = '<svg class="icon qd-anon-icon"><use href="../assets/icons/sprite.svg#icon-anonymous-identity-visible-to-admin"></use></svg>';
    document.getElementById('posterName').textContent = 'Anonymous';
    document.getElementById('posterRole').textContent = 'Identity visible to admin only';

    if(q.adminIdentity){
      const realName = q.adminIdentity.split(' (')[0];
      const info = AUTHOR_INFO[realName] || {};
      posterAdminNote.classList.remove('d-none');
      document.getElementById('posterAdminNoteText').textContent = 'Admin-only: this is ' + q.adminIdentity;
      document.getElementById('posterAccountType').textContent = info.accountType || '—';
      document.getElementById('posterCompany').textContent = info.company || '—';
      document.getElementById('posterMemberSince').textContent = info.memberSince || '—';
    } else {
      posterAdminNote.classList.add('d-none');
      document.getElementById('posterAccountType').textContent = 'Anonymous';
      document.getElementById('posterCompany').textContent = '—';
      document.getElementById('posterMemberSince').textContent = '—';
    }
  } else {
    posterAdminNote.classList.add('d-none');
    posterAvatar.className = 'qd-author-avatar qd-author-avatar-lg ' + avatarClass(q.author);
    posterAvatar.textContent = initials(q.author);
    document.getElementById('posterName').textContent = q.author;
    document.getElementById('posterRole').textContent = q.role || '';

    const info = AUTHOR_INFO[q.author] || {};
    document.getElementById('posterAccountType').textContent = info.accountType || '—';
    document.getElementById('posterCompany').textContent = info.company || '—';
    document.getElementById('posterMemberSince').textContent = info.memberSince || '—';
  }

  if(q.attachments && q.attachments.length){
    document.getElementById('qdAttachWrap').classList.remove('d-none');
    document.getElementById('qdAttachList').innerHTML = q.attachments.map(f => `
      <span class="attach-chip"><svg class="icon"><use href="../assets/icons/sprite.svg#icon-2-files-svg-viewbox-0-0"></use></svg>${f}</span>
    `).join('');
  } else {
    document.getElementById('qdAttachWrap').classList.add('d-none');
    document.getElementById('qdAttachList').innerHTML = '';
  }

  document.getElementById('qdAnswersLabel').textContent = `Answers (${q.answers.length})`;
  currentQuestion = q;
  answerCounter = q.answers.length;
  const list = document.getElementById('qdAnswersList');
  if(q.answers.length === 0){
    list.innerHTML = '<div class="qd-empty-answers">No answers yet — this question is still open.</div>';
  } else {
    list.innerHTML = q.answers.map((a,i) => answerBlockHtml(a,i)).join('');
  }

  // Right sidebar info
  document.getElementById('infoPublish').innerHTML = `<span class="publish-pill qd-info-publish-pill ${q.publish}" id="infoPublishPill">${publishPillHtml(q.publish)}</span>`;
  const unpublishBtnText = document.getElementById('unpublishBtnText');
  if(unpublishBtnText) unpublishBtnText.textContent = q.publish==='unpublished' ? 'Re-publish Question' : 'Un-publish Question';
  document.getElementById('infoPlant').textContent = q.plant;
  document.getElementById('infoTopic').textContent = q.topic;
  document.getElementById('infoPosted').textContent = q.posted;
  document.getElementById('infoViews').textContent = q.views;
  document.getElementById('infoAnswers').textContent = q.answers.length;
}

// ===================== REPLIES =====================
function toggleAnswerReply(i){
  const el = document.getElementById('replyComposer'+i);
  const open = !el.classList.contains('d-none');
  el.classList.toggle('d-none', open);
  if(!open) document.getElementById('replyText'+i).focus();
}

function publishAnswerReply(i){
  const textEl = document.getElementById('replyText'+i);
  const text = textEl.value.trim();
  if(!text){ showToast('Write a reply before publishing','red'); return; }
  const container = document.getElementById('nestedReplies'+i);
  container.insertAdjacentHTML('beforeend', `
    <div class="qd-nested-reply">
      <div class="qd-nested-reply-head">
        <div class="qd-author-avatar qd-author-avatar-sm avatar-charley-admin">CA</div>
        <div>
          <div class="qd-nested-reply-author">Charley Admin <span>Admin reply</span></div>
          <div class="qd-nested-reply-time">Just now</div>
        </div>
      </div>
      <div class="qd-nested-reply-text">${escapeHtml(text)}</div>
    </div>
  `);
  textEl.value = '';
  toggleAnswerReply(i);
  showToast('Reply published','green');
}

function publishMainReply(){
  const textEl = document.getElementById('qdMainReplyText');
  const text = textEl.value.trim();
  if(!text){ showToast('Write an answer before publishing','red'); return; }

  const list = document.getElementById('qdAnswersList');
  const emptyMsg = list.querySelector('.qd-empty-answers');
  if(emptyMsg) emptyMsg.remove();

  const i = answerCounter++;
  const newAnswer = {author:'Charley Admin', role:'Admin answer', confidence:5, text: escapeHtml(text)};
  currentQuestion.answers[i] = newAnswer;
  list.insertAdjacentHTML('beforeend', answerBlockHtml(newAnswer, i));
  textEl.value = '';
  const newCount = answerCounter;
  document.getElementById('qdAnswersLabel').textContent = `Answers (${newCount})`;
  document.getElementById('infoAnswers').textContent = newCount;
  showToast('Answer published','green');
}

// ===================== INIT =====================
const urlParams = new URLSearchParams(window.location.search);
const questionId = parseInt(urlParams.get('id')) || 1;
renderQuestion(questionId);

// ===================== SHARED HELPERS (sidebar/topbar/toast) =====================
