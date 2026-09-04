const $=(s,p=document)=>p.querySelector(s), $$=(s,p=document)=>[...p.querySelectorAll(s)];
const csrf=document.body?.dataset.csrf||'';
async function api(action,data={}){
  const body=new URLSearchParams(data); body.append('csrf',csrf);
  const r=await fetch(`../api.php?action=${encodeURIComponent(action)}`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
  const j=await r.json().catch(()=>({ok:false,message:'Respons server tidak valid.'}));
  if(!r.ok && !j.message) j.message='Terjadi kesalahan.';
  return j;
}
function toast(msg,ok=false){let t=$('#toast');if(!t){t=document.createElement('div');t.id='toast';t.style='position:fixed;left:50%;bottom:25px;transform:translateX(-50%);z-index:100;background:#17324d;color:#fff;padding:13px 18px;border-radius:999px;box-shadow:0 8px 30px #0003;font-weight:700;font-size:13px';document.body.appendChild(t)}t.textContent=msg;t.style.background=ok?'#198754':'#17324d';t.hidden=false;clearTimeout(window._toast);window._toast=setTimeout(()=>t.hidden=true,2800)}
function setupPasswords(){ $$('.toggle-pass').forEach(b=>b.onclick=()=>{const i=b.parentElement.querySelector('input');i.type=i.type==='password'?'text':'password';b.textContent=i.type==='password'?'show':'hide'})}
function setupLogin(){
 const f=$('#loginForm'); if(!f)return;
 f.onsubmit=async e=>{e.preventDefault();const j=await api('login',Object.fromEntries(new FormData(f)));if(j.ok)location.href=j.redirect;else toast(j.message)};
 const open=$('#forgotOpen');const modal=$('#forgotModal'); if(open&&modal){open.onclick=()=>modal.classList.add('show');$('.close-modal',modal).onclick=()=>modal.classList.remove('show')}
 const send=$('#sendOtp');if(send)send.onclick=async()=>{const j=await api('send_otp',{role:f.role.value,contact:$('#otpContact').value});$('#otpMessage').textContent=j.message+(j.dev_otp?` Kode lokal: ${j.dev_otp}`:'');$('#otpStep2').hidden=!j.dev_otp};
 const verify=$('#verifyOtp');if(verify)verify.onclick=async()=>{const j=await api('verify_otp',{code:$('#otpCode').value});if(j.ok){$('#otpStep3').hidden=false;$('#otpStep2').hidden=true}else toast(j.message)};
 const reset=$('#resetPassword');if(reset)reset.onclick=async()=>{const j=await api('reset_password',{password:$('#newPass').value,password2:$('#newPass2').value});toast(j.message,j.ok);if(j.ok)modal.classList.remove('show')};
 setupPasswords();
}
function setupRegistration(){
 const dataForm=$('#dataForm'); if(dataForm)dataForm.onsubmit=async e=>{e.preventDefault();const fd=new FormData(dataForm);const j=await api('register_data',Object.fromEntries(fd));if(j.ok)location.href=j.redirect;else toast(j.message)};
 const acc=$('#accountForm'); if(acc){const u=$('#regUsername'),err=$('#userErr');let timer;
 u.addEventListener('input',()=>{clearTimeout(timer);const val=u.value.trim();if(!val){err.textContent='';return}timer=setTimeout(async()=>{const j=await api('check_username',{username:val,role:acc.role.value});err.textContent=j.available?'':'username sudah terpakai';err.className=j.available?'success':'error';},180)});
 $('#pass2')?.addEventListener('input',()=>$('#passErr').textContent=$('#pass2').value && $('#pass2').value!==$('#pass1').value?'password belum sama':'');
 acc.onsubmit=async e=>{e.preventDefault();const j=await api('register_account',Object.fromEntries(new FormData(acc)));if(j.ok)location.href=j.redirect;else toast(j.message)}
 }
 setupPasswords();
}
function openCamera(){
 const modal=$('#cameraModal');if(!modal)return;
 modal.classList.add('show');const video=$('#cameraVideo'),canvas=$('#cameraCanvas');navigator.mediaDevices?.getUserMedia({video:{facingMode:'environment'},audio:false}).then(stream=>{video.srcObject=stream;video.play();modal._stream=stream}).catch(()=>toast('Kamera tidak dapat diakses. Pastikan browser memberi izin kamera.'));
 $('#takePhoto').onclick=()=>{canvas.width=video.videoWidth||640;canvas.height=video.videoHeight||480;canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);$('#photoBase64').value=canvas.toDataURL('image/jpeg',.85);$('#photoPreview').src=$('#photoBase64').value;$('#photoPreview').hidden=false;modal.classList.remove('show');modal._stream?.getTracks().forEach(t=>t.stop())};
 $('#closeCamera').onclick=()=>{modal.classList.remove('show');modal._stream?.getTracks().forEach(t=>t.stop())};
}
async function getPosition(){
 return new Promise((resolve,reject)=>navigator.geolocation.getCurrentPosition(p=>resolve(p.coords),e=>reject(e),{enableHighAccuracy:true,timeout:10000}));
}
function setupAttendance(){
 const f=$('#attendanceForm');if(!f)return;$('#cameraBtn').onclick=openCamera;
 f.onsubmit=async e=>{e.preventDefault();try{const pos=await getPosition();const data=Object.fromEntries(new FormData(f));data.latitude=pos.latitude;data.longitude=pos.longitude;const j=await api('attendance',data);toast(j.message,j.ok);if(j.ok)setTimeout(()=>location.href='index.php?page=dashboard',900)}catch(e){toast('Lokasi tidak dapat diambil. Aktifkan Location Services untuk browser.')}};
}
function setupPermission(){
 const f=$('#permissionForm');if(!f)return;
 const start=$('#pStart'),end=$('#pEnd');start.onchange=()=>{end.min=start.value;if(end.value<start.value)end.value=start.value};
 f.onsubmit=async e=>{e.preventDefault();const j=await api('permission',Object.fromEntries(new FormData(f)));toast(j.message,j.ok);if(j.ok)setTimeout(()=>location.href='index.php?page=dashboard',900)}
}
function setupProfile(){
 const f=$('#profileForm');if(!f)return;
 f.onsubmit=async e=>{e.preventDefault();const j=await api('update_profile',Object.fromEntries(new FormData(f)));toast(j.message,j.ok);if(j.ok)setTimeout(()=>location.reload(),800)}
}
function setupMentor(){
 $$('.decision').forEach(b=>b.onclick=async()=>{const j=await api('mentor_decision',{id_perizinan:b.dataset.id,decision:b.dataset.decision});toast(j.message,j.ok);if(j.ok)setTimeout(()=>location.reload(),500)});
 $$('.delete-intern').forEach(b=>b.onclick=async()=>{if(!confirm('Hapus data dan akun intern ini?'))return;const j=await api('delete_intern',{id_intern:b.dataset.id});toast(j.message,j.ok);if(j.ok)setTimeout(()=>location.reload(),500)});
 $('#assignExisting')?.addEventListener('click',async()=>{const j=await api('assign_existing');toast('Sinkronisasi divisi selesai.',j.ok);setTimeout(()=>location.reload(),500)});
}
function setupSidebar(){
 const sb=$('#sidebar'),ov=$('#overlay'),open=$('#avatarOpen'),close=$('#sidebarClose');if(!sb)return;
 const go=()=>{sb.classList.add('open');ov.classList.add('show')},hide=()=>{sb.classList.remove('open');ov.classList.remove('show')};
 open?.addEventListener('click',go);close?.addEventListener('click',hide);ov?.addEventListener('click',hide);
}
function setupFilters(){
 $$('.filter').forEach(s=>s.onchange=()=>{const v=s.value.toLowerCase();const target=s.dataset.target;$$(`[data-filter-item="${target}"]`).forEach(el=>{el.hidden=v&&v!=='semua'&&!el.textContent.toLowerCase().includes(v)})});
}

function setupDashboardLocation(){
 const a=$('#arrivalChoice'); if(!a || a.dataset.serverDisabled==='1') return;
 if(!navigator.geolocation)return;
 navigator.geolocation.getCurrentPosition(pos=>{
   const R=6371000, rad=x=>x*Math.PI/180, lat1=rad(pos.coords.latitude),lat2=rad(+a.dataset.lat),dlat=rad(+a.dataset.lat-pos.coords.latitude),dlon=rad(+a.dataset.lon-pos.coords.longitude);
   const h=Math.sin(dlat/2)**2+Math.cos(lat1)*Math.cos(lat2)*Math.sin(dlon/2)**2;
   const dist=2*R*Math.asin(Math.min(1,Math.sqrt(h)));
   if(dist>+(a.dataset.radius||100)){a.classList.add('btn-gray');a.href='#';a.querySelector('p').textContent='nyampe dulu kali';a.setAttribute('aria-disabled','true');}
 },()=>{});
}

document.addEventListener('DOMContentLoaded',()=>{setupLogin();setupRegistration();setupAttendance();setupDashboardLocation();setupPermission();setupProfile();setupMentor();setupSidebar();setupFilters();});
