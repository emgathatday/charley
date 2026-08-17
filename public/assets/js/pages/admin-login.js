/* ===== Page-specific source: pages/admin-login.html ===== */
/* ── Password visibility toggle ── */
  function togglePassword(){
    const pw = document.getElementById('password');
    const icon = document.getElementById('pwToggle');
    if(pw.type === 'password'){
      pw.type = 'text';
      icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
      pw.type = 'password';
      icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
  }

  /* ── Login handler (demo flow → goes to OTP) ── */
  function handleLogin(){
    const email = document.getElementById('email').value.trim();
    const pw    = document.getElementById('password').value;
    const errEl = document.getElementById('errorMsg');

    errEl.classList.remove('show');

    if(!email || !pw){
      document.getElementById('errorText').textContent = 'Please enter your email and password.';
      errEl.classList.add('show');
      return;
    }

    // Loading state
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    document.getElementById('loginSpinner').style.display = 'block';
    document.getElementById('loginArrow').style.display   = 'none';

    // Simulate API call → show OTP view
    setTimeout(()=>{
      btn.disabled = false;
      document.getElementById('loginSpinner').style.display = 'none';
      document.getElementById('loginArrow').style.display   = '';

      document.getElementById('otpEmail').textContent = email;
      document.getElementById('mainView').classList.add('hide');
      document.getElementById('otpView').classList.add('show');
      startResendTimer();
      document.querySelectorAll('.otp-digit')[0].focus();
    }, 1200);
  }

  /* ── Back to login ── */
  function backToLogin(){
    document.getElementById('mainView').classList.remove('hide');
    document.getElementById('otpView').classList.remove('show');
    document.getElementById('errorMsg').classList.remove('show');
    document.querySelectorAll('.otp-digit').forEach(i=>i.value='');
  }

  /* ── OTP input auto-advance ── */
  document.addEventListener('DOMContentLoaded', ()=>{
    const digits = document.querySelectorAll('.otp-digit');
    digits.forEach((el, idx)=>{
      el.addEventListener('input', e=>{
        const val = e.target.value.replace(/[^0-9]/g,'');
        e.target.value = val;
        if(val && idx < digits.length - 1) digits[idx+1].focus();
      });
      el.addEventListener('keydown', e=>{
        if(e.key==='Backspace' && !e.target.value && idx > 0) digits[idx-1].focus();
      });
      el.addEventListener('paste', e=>{
        e.preventDefault();
        const pasted = (e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'');
        [...pasted].slice(0,6).forEach((ch,i)=>{ if(digits[idx+i]) digits[idx+i].value=ch; });
        const next = Math.min(idx+pasted.length, digits.length-1);
        digits[next].focus();
      });
    });
  });

  /* ── OTP verify (demo) ── */
  function handleOtp(){
    const code = [...document.querySelectorAll('.otp-digit')].map(i=>i.value).join('');
    if(code.length < 6){ return; }

    const btn = document.getElementById('otpBtn');
    btn.disabled = true;
    document.getElementById('otpSpinner').style.display = 'block';

    setTimeout(()=>{
      // On real implementation → redirect to admin dashboard
      window.location.href = 'admin-dashboard.html';
    }, 1000);
  }

  /* ── Resend timer ── */
  let resendInterval;
  function startResendTimer(){
    const btn = document.getElementById('resendBtn');
    const cnt = document.getElementById('timerCount');
    btn.disabled = true;
    let t = 30;
    cnt.textContent = t;
    clearInterval(resendInterval);
    resendInterval = setInterval(()=>{
      t--;
      cnt.textContent = t;
      if(t<=0){
        clearInterval(resendInterval);
        btn.disabled = false;
        btn.innerHTML = 'Resend code';
        btn.onclick = ()=>{
          btn.innerHTML = 'Resend in <span id="timerCount">30</span>s';
          startResendTimer();
        };
      }
    }, 1000);
  }

  /* ── Enter key submit ── */
  document.addEventListener('keydown', e=>{
    if(e.key==='Enter'){
      if(!document.getElementById('otpView').classList.contains('show')){
        handleLogin();
      } else {
        handleOtp();
      }
    }
  });
