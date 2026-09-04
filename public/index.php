<?php
require __DIR__.'/../config.php';
$page=$_GET['page']??'role'; $role=$_GET['role']??'intern';
if($page==='logout') logout_user();
$user=current_user($pdo);
function photo_url(?string $p): string { return $p ? '../'.$p : 'assets/logo-pln.jpg'; }
function day_id(string $d): string { return ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l',strtotime($d))]; }
function page_title(string $page): string { return ['role'=>'Siapa nih?','login'=>'Masuk','register-data'=>'Data diri','register-account'=>'Buat akun','dashboard'=>'Dashboard','arrival'=>'Kedatangan','departure'=>'Kepulangan','permission'=>'Perizinan','profile'=>'Data diri','history'=>'History','analysis'=>'Analisis','interns'=>'Daftar intern','intern-analysis'=>'Analisis intern','mentor-attendance'=>'Presensi intern','mentor-permission'=>'Perizinan intern'][$page]??APP_NAME; }
function nav_header(?array $user): void { ?>
<header class="topbar">
  <div class="mini-brand"><img src="assets/logo-pln.jpg"><span><?=e(APP_NAME)?></span></div>
  <?php if($user): ?><button class="avatar-btn" id="avatarOpen"><img src="<?=e(photo_url($user['foto']??null))?>" alt="avatar"></button><?php endif;?>
</header>
<?php }
function sidebar(PDO $pdo,array $user): void { $role=$_SESSION['role']; ?>
<div class="overlay" id="overlay"></div><aside class="sidebar" id="sidebar">
  <button id="sidebarClose" style="float:right;background:transparent;font-size:22px">×</button>
  <div class="side-profile"><img class="profile-photo" src="<?=e(photo_url($user['foto']??null))?>" alt="foto"><h3><?=e($user['nama'])?></h3><div class="muted small"><?=e(ucfirst($role))?></div></div>
  <nav class="side-nav">
    <a href="index.php?page=profile">👤 Data diri</a>
    <a href="index.php?page=history">🕘 History</a>
    <a href="index.php?page=analysis">🕸️ Analisis</a>
    <a href="index.php?page=logout">↪ Keluar</a>
  </nav>
</aside>
<?php }
function layout_start(?array $user,string $title=''): void { ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title?:APP_NAME)?></title><link rel="stylesheet" href="assets/app.css"></head>
<body data-csrf="<?=e(csrf())?>">
<?php nav_header($user); if($user) sidebar($GLOBALS['pdo'],$user); ?>
<?php }
function layout_end(): void { ?><script src="assets/app.js"></script></body></html><?php }

if($page==='role'){
layout_start(null,'Siapa nih?'); ?>
<div class="app-bg" style="background-image:url('assets/Kantor-UP3-Serpong.png')"><div class="center-wrap"><div class="card narrow">
<div class="brand"><img src="assets/logo-pln.jpg" alt="PLN"><h1>Siapa nih?</h1><p class="muted">Selamat datang di Absensi Magang UP3 Serpong</p></div>
<div class="role-buttons"><a class="btn btn-blue" href="index.php?page=login&role=intern">Anak Magang</a><a class="btn btn-yellow" href="index.php?page=login&role=mentor">Mentor Kece</a></div>
</div></div></div><?php layout_end(); exit;}

if(in_array($page,['login','register-data','register-account'])){
layout_start(null,page_title($page)); $role=in_array($role,['intern','mentor'])?$role:'intern'; ?>
<div class="center-wrap" style="min-height:calc(100vh - 63px);"><div class="card narrow">
<a class="back" href="index.php?page=role">← pilih peran</a>
<div class="brand"><img src="assets/logo-pln.jpg" alt="PLN"><h1><?=e(page_title($page))?></h1></div>
<?php if($page==='login'): ?>
<h2 style="text-align:center"><?= $role==='intern'?'Hai, anak magang!':'Halo, mentor kece!'?></h2>
<form id="loginForm"><input type="hidden" name="role" value="<?=e($role)?>">
<div class="field"><label>Username</label><input name="username" autocomplete="username" required></div>
<div class="field" style="margin-top:14px"><label>Password</label><div class="password-wrap"><input id="loginPass" name="password" type="password" autocomplete="current-password" required><button type="button" class="toggle-pass">show</button></div></div>
<div style="text-align:right;margin:9px 0 18px"><button type="button" id="forgotOpen" style="background:none;color:var(--blue2);font-weight:800">lupa password?</button></div>
<button class="btn btn-blue">Masuk</button><div style="height:9px"></div>
<a class="btn btn-white" style="display:block;text-align:center;text-decoration:none" href="index.php?page=register-data&role=<?=$role?>">Register</a>
</form>
<div class="modal" id="forgotModal"><div class="modal-card"><button class="close-modal" style="float:right;background:none;font-size:22px">×</button><h3>Reset password</h3><p class="muted small">Masukkan email atau nomor telepon yang terdaftar.</p><div class="field"><label>Email / No. Telp</label><input id="otpContact" placeholder="contoh: nama@email.com / 08xxxxxxxxxx"></div><button id="sendOtp" class="btn btn-blue" style="margin-top:12px">Kirim OTP</button><p id="otpMessage" class="notice"></p><div id="otpStep2" hidden><div class="field"><label>Kode OTP</label><input id="otpCode" inputmode="numeric" maxlength="6"></div><button id="verifyOtp" class="btn btn-yellow" style="margin-top:10px">Verifikasi OTP</button></div><div id="otpStep3" hidden><hr><div class="field"><label>Password baru</label><input id="newPass" type="password"></div><div class="field" style="margin-top:10px"><label>Ulangi password</label><input id="newPass2" type="password"></div><button id="resetPassword" class="btn btn-blue" style="margin-top:10px">Simpan password</button></div></div></div>
<?php elseif($page==='register-data'): ?>
<h2 style="text-align:center"><?= $role==='intern'?'wih, anak baru ya? daftar dulu sini!':'hai, mentor kece!'?></h2>
<p class="muted small" style="text-align:center">Email digunakan sebagai salah satu kanal OTP untuk pemulihan akun.</p>
<form id="dataForm"><input type="hidden" name="role" value="<?=$role?>">
<div class="form-grid">
<div class="field"><label>Nama</label><input name="nama" required></div>
<div class="field"><label>Tanggal Lahir</label><input name="tanggal_lahir" type="date" required></div>
<?php if($role==='intern'): ?>
<div class="field"><label>NIM</label><input name="nim" required></div>
<div class="field"><label>No. Telp</label><input name="no_telp" placeholder="contoh: 08xxxxxxxxxx" required></div>
<div class="field"><label>Email</label><input name="email" type="email" placeholder="contoh: nama@email.com" required></div>
<div class="field"><label>Asal Universitas</label><input name="asal_universitas" required></div>
<div class="field"><label>Program Studi</label><input name="program_studi" placeholder="contoh: S1 Teknik Elektro" required></div>
<div class="field"><label>Divisi</label><input name="divisi" required></div>
<div class="field"><label>Tanggal Mulai Magang</label><input name="tanggal_mulai" type="date" required></div>
<div class="field"><label>Tanggal Selesai Magang</label><input name="tanggal_selesai" type="date" required></div>
<?php else: ?>
<div class="field"><label>NIP</label><input name="nip" required></div>
<div class="field"><label>No. Telp</label><input name="no_telp" placeholder="contoh: 08xxxxxxxxxx" required></div>
<div class="field"><label>Email</label><input name="email" type="email" placeholder="contoh: nama@email.com" required></div>
<div class="field"><label>Divisi</label><input name="divisi" required></div>
<?php endif; ?>
<div class="field full"><label>Foto</label><input name="foto_base64" id="profilePhotoInput" type="file" accept="image/*"><span class="muted small">Untuk foto profil, pilih file foto dari perangkat.</span></div>
</div><button class="btn btn-blue" style="margin-top:20px">Next</button></form>
<?php else: ?>
<h2 style="text-align:center">Bikin akunmu</h2><form id="accountForm"><input type="hidden" name="role" value="<?=$role?>">
<div class="field"><label>Username</label><input id="regUsername" name="username" required><div id="userErr" class="error"></div></div>
<div class="field" style="margin-top:14px"><label>Password <span class="muted">(minimal 4 karakter)</span></label><div class="password-wrap"><input id="pass1" name="password" type="password" minlength="4" required><button type="button" class="toggle-pass">show</button></div></div>
<div class="field" style="margin-top:14px"><label>Reinput Password</label><div class="password-wrap"><input id="pass2" name="password2" type="password" minlength="4" required><button type="button" class="toggle-pass">show</button></div><div id="passErr" class="error"></div></div>
<button class="btn btn-blue" style="margin-top:20px">Register</button></form>
<?php endif; ?></div></div><?php layout_end(); exit;}

require_role($role==='mentor'?'mentor':'intern');
$user=current_user($pdo);

if($page==='dashboard'){
layout_start($user,'Dashboard');$role=$_SESSION['role'];?>
<main class="main"><div class="hero-title"><h1><?= $role==='intern'?'Mau presensi apa hari ini?':'Dashboard Mentor Kece'?></h1><p class="muted"><?=e($user['nama'])?></p></div>
<?php if($role==='intern'):
$date=date('Y-m-d');$s=work_schedule($pdo,$date);$perm=permission_for($pdo,$user['id_intern'],$date);$st=$pdo->prepare("SELECT * FROM presensi WHERE id_intern=? AND tanggal=?");$st->execute([$user['id_intern'],$date]);$p=$st->fetch();$now=date('H:i:s');$loc=$pdo->query("SELECT * FROM lokasi_kantor LIMIT 1")->fetch();$arrivalDisabled=$perm||($p&&$p['jam_kedatangan']);$arrivalText=$perm?'lah, katanya izin':(($p&&$p['jam_kedatangan'])?'lah, kan tadi udah':'udah sampe nih');$afterEnd=($s&&$now>=$s['jam_pulang']);$depLockedBeforeEnd=(!$afterEnd);$depAlready=($p&&$p['jam_pulang']);$depGray=$depAlready||$depLockedBeforeEnd; $depText=$depAlready?'lah, kan tadi udah':($afterEnd?( (!$p||!$p['jam_kedatangan'])?'loh, lupa absen pagi kah?':'yok balik yok'):'mau kemana hayo');?>
<div class="choice-grid">
<a id="arrivalChoice" data-lat="<?=e($loc['latitude'])?>" data-lon="<?=e($loc['longitude'])?>" data-radius="<?=e($loc['radius_meter'])?>" data-server-disabled="<?= $arrivalDisabled?'1':'0'?>" class="choice btn-blue <?= $arrivalDisabled?'btn-gray':''?>" style="text-decoration:none;color:inherit" href="<?= $arrivalDisabled?'#':'index.php?page=arrival'?>" <?= $arrivalDisabled?'aria-disabled="true"':''?>><h3>Kedatangan</h3><p><?=$arrivalText?></p></a>
<a class="choice btn-yellow <?= $depGray?'btn-gray':''?>" style="text-decoration:none;color:inherit" href="<?= $depGray?'#':'index.php?page=departure'?>"><h3>Kepulangan</h3><p><?=$depText?></p></a>
<a class="choice btn-red" style="text-decoration:none;color:inherit" href="index.php?page=permission"><h3>Perizinan</h3><p>punten, pak</p></a>
</div>
<?php else:
$st=$pdo->prepare("SELECT COUNT(*) c FROM perizinan p JOIN data_intern i ON i.id_intern=p.id_intern WHERE i.id_mentor=? AND p.status_approval='menunggu'");$st->execute([$user['id_mentor']]);$pending=(int)$st->fetch()['c'];?>
<div class="cards">
<a class="choice btn-blue" style="text-decoration:none;color:inherit" href="index.php?page=interns"><h3>Daftar Intern</h3><p>anak-anak magangmu</p></a>
<a class="choice btn-yellow" style="text-decoration:none;color:inherit" href="index.php?page=intern-analysis"><h3>Analisis Intern</h3><p>lihat performa</p></a>
<a class="choice btn-blue" style="text-decoration:none;color:inherit" href="index.php?page=mentor-attendance"><h3>Presensi Intern</h3><p>rekap kehadiran</p></a>
<a class="choice btn-red relative" style="text-decoration:none;color:inherit" href="index.php?page=mentor-permission"><h3>Perizinan</h3><p>approval izin</p><?php if($pending):?><span class="counter"><?=$pending?></span><?php endif;?></a>
</div><?php endif;?></main>
<?php layout_end();exit;}

if($page==='arrival'||$page==='departure'){
layout_start($user,page_title($page));$type=$page==='arrival'?'datang':'pulang';$s=work_schedule($pdo,date('Y-m-d'));?>
<main class="main"><div class="card narrow" style="margin:auto"><a class="back" href="index.php?page=dashboard">← kembali</a><h1><?=page_title($page)?></h1>
<form id="attendanceForm"><input type="hidden" name="type" value="<?=$type?>"><div class="field"><label>Waktu <?= $type==='datang'?'kedatangan':'kepulangan'?></label><input value="<?=date('Y-m-d H:i:s')?>" readonly><span class="muted small">Timestamp akan dicatat server saat tombol kirim ditekan.</span></div>
<div class="field" style="margin-top:16px"><label>Bukti foto</label><button type="button" id="cameraBtn" class="btn btn-white">📷 Buka kamera</button><input type="hidden" id="photoBase64" name="foto_base64"><img id="photoPreview" class="camera" hidden style="margin-top:10px"></div>
<?php if($type==='datang'): ?><div class="notice">Jika waktu datang melewati jam <?=e($s['jam_masuk']??'07:30:00')?>, alasan keterlambatan akan dicatat pada presensi.</div><div class="field"><label>Alasan Keterlambatan <span class="muted">(isi jika terlambat)</span></label><textarea name="alasan_keterlambatan" rows="3" placeholder="Contoh: kendaraan mengalami kendala"></textarea></div><?php endif;?>
<button class="btn btn-blue" style="margin-top:20px">Kirim</button></form></div></main>
<div class="modal" id="cameraModal"><div class="modal-card"><h3>Ambil bukti foto</h3><video id="cameraVideo" class="camera" playsinline></video><canvas id="cameraCanvas" hidden></canvas><div class="actions" style="margin-top:12px"><button id="takePhoto" class="btn btn-blue">Ambil foto</button><button id="closeCamera" class="btn btn-white">Batal</button></div></div></div>
<?php layout_end();exit;}

if($page==='permission'){
layout_start($user,'Perizinan');?><main class="main"><div class="card narrow" style="margin:auto"><a class="back" href="index.php?page=dashboard">← kembali</a><h1>Perizinan</h1><p class="muted">Pilih rentang tanggal. Sistem membuat satu pengajuan untuk setiap hari.</p><form id="permissionForm"><div class="form-grid"><div class="field"><label>Tanggal Mulai</label><input id="pStart" name="tanggal_mulai" type="date" min="<?=date('Y-m-d')?>" required></div><div class="field"><label>Tanggal Selesai</label><input id="pEnd" name="tanggal_selesai" type="date" min="<?=date('Y-m-d')?>" required></div><div class="field full"><label>Alasan</label><textarea name="alasan" rows="5" required placeholder="Tulis alasan perizinan..."></textarea></div></div><button class="btn btn-red" style="margin-top:18px">Kirim</button></form></div></main><?php layout_end();exit;}

if($page==='profile'){
layout_start($user,'Data diri');$role=$_SESSION['role'];$fields=$role==='intern'?['nama'=>'Nama','tanggal_lahir'=>'Tanggal Lahir','NIM'=>'NIM','email'=>'Email','no_telp'=>'No. Telp','asal_universitas'=>'Asal Universitas','program_studi'=>'Program Studi','divisi'=>'Divisi','tanggal_mulai'=>'Mulai Magang','tanggal_selesai'=>'Selesai Magang']:['nama'=>'Nama','tanggal_lahir'=>'Tanggal Lahir','NIP'=>'NIP','email'=>'Email','no_telp'=>'No. Telp','divisi'=>'Divisi'];$account=$user['username'];?>
<main class="main"><div class="card wide" style="margin:auto"><a class="back" href="index.php?page=dashboard">← kembali</a><img class="profile-photo" src="<?=e(photo_url($user['foto']??null))?>"><h1 style="text-align:center">Data diri</h1>
<div class="detail-grid"><?php foreach($fields as $k=>$label):?><div class="detail-item"><b><?=$label?></b><?=e($user[$k]??'-')?></div><?php endforeach;?><div class="detail-item"><b>Username</b><?=e($account)?></div></div>
<button class="btn btn-blue" style="margin-top:18px" onclick="document.getElementById('editModal').classList.add('show')">✎ Edit</button></div></main>
<div class="modal" id="editModal"><div class="modal-card"><h3>Edit data & akun</h3><form id="profileForm"><div class="field"><label>Password saat ini (wajib)</label><input name="current_password" type="password" required></div><div class="form-grid" style="margin-top:12px"><?php foreach($fields as $k=>$label):if(in_array($k,['NIM','NIP']))continue;?><div class="field"><label><?=$label?></label><input name="<?=$k?>" value="<?=e($user[$k]??'')?>"></div><?php endforeach;?></div><div class="field" style="margin-top:12px"><label>Password baru <span class="muted">(opsional)</span></label><input name="new_password" type="password" minlength="4"></div><div class="actions" style="margin-top:15px"><button class="btn btn-blue">Selesai</button><button type="button" class="btn btn-white" onclick="this.closest('.modal').classList.remove('show')">Batal</button></div></form></div></div>
<?php layout_end();exit;}

if($page==='history'){
layout_start($user,'History');$st=$pdo->prepare("SELECT p.*,z.status_approval FROM presensi p LEFT JOIN perizinan z ON z.id_perizinan=p.id_perizinan WHERE p.id_intern=? ORDER BY p.tanggal DESC");$st->execute([$user['id_intern']]);$rows=$st->fetchAll();?>
<main class="main"><h1>History</h1><div class="table-wrap"><table><thead><tr><th>Hari</th><th>Tanggal</th><th>Jam Datang</th><th>Jam Pulang</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach($rows as $r):$ket=$r['keterangan']??'-';if($r['status_kehadiran']==='Izin')$ket=$r['status_approval']==='disetujui'?'approved':($r['status_approval']==='ditolak'?'declined':'menunggu approval');?><tr><td><?=day_id($r['tanggal'])?></td><td><?=$r['tanggal']?></td><?php if($r['status_kehadiran']==='Izin'):?><td colspan="2" style="text-align:center;font-weight:900">izin</td><?php else:?><td><?=$r['jam_kedatangan']??'-'?></td><td><?=$r['jam_pulang']??'-'?></td><?php endif;?><td><?=$ket?></td></tr><?php endforeach;?></tbody></table></div></main><?php layout_end();exit;}

if($page==='analysis'){
layout_start($user,'Analisis');$a=calculate_analysis($pdo,$user['id_intern'],$user['tanggal_mulai'],$user['tanggal_selesai']);?>
<main class="main"><h1>Analisis Kehadiran</h1><p class="muted">Empat kriteria: rajin, tepat waktu, lembur, konsistensi.</p><div class="radar-box"><svg class="radar" viewBox="0 0 600 520" id="radarSvg"><g transform="translate(300 250)"><polygon points="0,-190 180,-60 112,155 -112,155 -180,-60" fill="none" stroke="#dfe7ed"/><polygon points="0,-143 135,-48 84,116 -84,116 -135,-48" fill="none" stroke="#dfe7ed"/><polygon points="0,-95 90,-30 56,77 -56,77 -90,-30" fill="none" stroke="#dfe7ed"/><polygon points="0,-48 45,-15 28,39 -28,39 -45,-15" fill="none" stroke="#dfe7ed"/>
<?php $vals=[$a['rajin'],$a['tepat'],$a['lembur'],$a['konsistensi']];$pts=[];for($i=0;$i<4;$i++){ $ang=(-90+$i*90)*pi()/180;$rad=190*$vals[$i]/100;$pts[]=round(cos($ang)*$rad).','.round(sin($ang)*$rad);} ?>
<polygon points="<?=implode(' ',$pts)?>" fill="#11a9e244" stroke="#087fbf" stroke-width="4"/>
<text x="0" y="-212" text-anchor="middle">Rajin</text><text x="212" y="-50" text-anchor="middle">Tepat Waktu</text><text x="0" y="220" text-anchor="middle">Konsistensi</text><text x="-212" y="-50" text-anchor="middle">Lembur</text></g></svg></div>
<?php foreach(['rajin'=>'Rajin','tepat'=>'Tepat waktu','lembur'=>'Lembur','konsistensi'=>'Konsistensi'] as $k=>$label):?><div class="metric-row"><b><?=$label?></b><div class="bar"><span style="width:<?=$a[$k]?>%"></span></div><b><?=$a[$k]?>%</b></div><?php endforeach;?></main><?php layout_end();exit;}

if($role==='mentor' && $page==='interns'){
layout_start($user,'Daftar Intern');$st=$pdo->prepare("SELECT * FROM data_intern WHERE id_mentor=? ORDER BY nama");$st->execute([$user['id_mentor']]);$interns=$st->fetchAll();?>
<main class="main"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h1>Daftar Intern</h1><button id="assignExisting" class="btn btn-white" style="width:auto">↻ Sinkron divisi</button></div><div class="filterbar"><select class="filter" data-target="intern"><option>Semua</option><option>Aktif</option><option>Nonaktif</option></select><select class="filter" data-target="intern"><option>Semua</option><?php $uv=[];foreach($interns as $i)$uv[$i['asal_universitas']]=1;foreach(array_keys($uv) as $x)echo '<option>'.e($x).'</option>';?></select></div><div class="cards"><?php foreach($interns as $i):$active=date('Y-m-d')>=$i['tanggal_mulai']&&date('Y-m-d')<=$i['tanggal_selesai'];?><article class="intern-card" data-filter-item="intern"><img src="<?=e(photo_url($i['foto']))?>"><div><h3><?=e($i['nama'])?></h3><div class="muted small"><?=e($i['NIM'])?> · <?=e($i['asal_universitas'])?></div><div class="muted small"><?=e($i['divisi'])?> · <?=$active?'Aktif':'Selesai'?></div><a href="index.php?page=intern-detail&id=<?=$i['id_intern']?>" class="small" style="color:var(--blue2);font-weight:800">lihat detail</a></div><button class="delete delete-intern" data-id="<?=$i['id_intern']?>" title="Hapus">🗑</button></article><?php endforeach;?></div></main><?php layout_end();exit;}

if($role==='mentor' && $page==='intern-detail'){
$id=(int)($_GET['id']??0);$st=$pdo->prepare("SELECT * FROM data_intern WHERE id_intern=? AND id_mentor=?");$st->execute([$id,$user['id_mentor']]);$i=$st->fetch();if(!$i)redirect('index.php?page=interns');layout_start($user,'Detail Intern');?>
<main class="main"><div class="card wide" style="margin:auto"><a class="back" href="index.php?page=interns">← kembali</a><img class="profile-photo" src="<?=e(photo_url($i['foto']))?>"><h1 style="text-align:center"><?=e($i['nama'])?></h1><div class="detail-grid"><?php foreach(['NIM','tanggal_lahir','email','no_telp','asal_universitas','program_studi','divisi','tanggal_mulai','tanggal_selesai'] as $k):?><div class="detail-item"><b><?=$k?></b><?=e($i[$k])?></div><?php endforeach;?></div></div></main><?php layout_end();exit;}

if($role==='mentor' && $page==='intern-analysis'){
layout_start($user,'Analisis Intern');$st=$pdo->prepare("SELECT * FROM data_intern WHERE id_mentor=? ORDER BY nama");$st->execute([$user['id_mentor']]);$interns=$st->fetchAll();?>
<main class="main"><h1>Analisis Intern</h1><div class="filterbar"><select class="filter" data-target="ia"><option>Semua</option><option>Aktif</option><option>Nonaktif</option></select></div><div class="cards"><?php foreach($interns as $i):$active=date('Y-m-d')>=$i['tanggal_mulai']&&date('Y-m-d')<=$i['tanggal_selesai'];?><a class="intern-card" data-filter-item="ia" style="text-decoration:none;color:inherit" href="index.php?page=intern-analysis-detail&id=<?=$i['id_intern']?>"><img src="<?=e(photo_url($i['foto']))?>"><div><h3><?=e($i['nama'])?></h3><div class="muted small"><?=e($i['asal_universitas'])?> · <?=$active?'Aktif':'Selesai'?></div></div></a><?php endforeach;?></div></main><?php layout_end();exit;}

if($role==='mentor' && $page==='intern-analysis-detail'){
$id=(int)($_GET['id']??0);$st=$pdo->prepare("SELECT * FROM data_intern WHERE id_intern=? AND id_mentor=?");$st->execute([$id,$user['id_mentor']]);$i=$st->fetch();if(!$i)redirect('index.php?page=intern-analysis');$a=calculate_analysis($pdo,$id,$i['tanggal_mulai'],$i['tanggal_selesai']);layout_start($user,'Analisis '. $i['nama']);?>
<main class="main"><a class="back" href="index.php?page=intern-analysis">← kembali</a><h1>Analisis <?=e($i['nama'])?></h1><div class="radar-box"><svg class="radar" viewBox="0 0 600 520"><g transform="translate(300 250)"><polygon points="0,-190 180,-60 112,155 -112,155 -180,-60" fill="none" stroke="#dfe7ed"/><polygon points="0,-143 135,-48 84,116 -84,116 -135,-48" fill="none" stroke="#dfe7ed"/><?php $vals=[$a['rajin'],$a['tepat'],$a['lembur'],$a['konsistensi']];$pts=[];for($j=0;$j<4;$j++){ $ang=(-90+$j*90)*pi()/180;$rad=190*$vals[$j]/100;$pts[]=round(cos($ang)*$rad).','.round(sin($ang)*$rad);} ?><polygon points="<?=implode(' ',$pts)?>" fill="#ffe90055" stroke="#d49b00" stroke-width="4"/><text x="0" y="-212" text-anchor="middle">Rajin</text><text x="212" y="-50" text-anchor="middle">Tepat Waktu</text><text x="0" y="220" text-anchor="middle">Konsistensi</text><text x="-212" y="-50" text-anchor="middle">Lembur</text></g></svg></div><?php foreach(['rajin'=>'Rajin','tepat'=>'Tepat waktu','lembur'=>'Lembur','konsistensi'=>'Konsistensi'] as $k=>$label):?><div class="metric-row"><b><?=$label?></b><div class="bar"><span style="width:<?=$a[$k]?>%"></span></div><b><?=$a[$k]?>%</b></div><?php endforeach;?></main><?php layout_end();exit;}

if($role==='mentor' && $page==='mentor-attendance'){
layout_start($user,'Presensi Intern');$st=$pdo->prepare("SELECT i.nama,p.*,z.status_approval FROM data_intern i LEFT JOIN presensi p ON p.id_intern=i.id_intern LEFT JOIN perizinan z ON z.id_perizinan=p.id_perizinan WHERE i.id_mentor=? ORDER BY p.tanggal DESC,i.nama");$st->execute([$user['id_mentor']]);$rows=$st->fetchAll();?>
<main class="main"><h1>Presensi Intern</h1><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Hari</th><th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Keterangan</th></tr></thead><tbody><?php foreach($rows as $r):$isIzin=$r['status_kehadiran']==='Izin';$ket=$isIzin?($r['status_approval']==='disetujui'?'approved':($r['status_approval']==='ditolak'?'declined':'menunggu approval')):($r['keterangan']??'-');?><tr><td><?=e($r['nama'])?></td><td><?= $r['tanggal']?day_id($r['tanggal']):'-'?></td><td><?=$r['tanggal']??'-'?></td><?php if($isIzin):?><td colspan="2" style="text-align:center"><a href="index.php?page=mentor-permission" style="font-weight:900;color:var(--blue2)">izin</a></td><?php else:?><td><?=$r['jam_kedatangan']??'-'?></td><td><?=$r['jam_pulang']??'-'?></td><?php endif;?><td><?=$ket?></td></tr><?php endforeach;?></tbody></table></div></main><?php layout_end();exit;}

if($role==='mentor' && $page==='mentor-permission'){
layout_start($user,'Perizinan Intern');$st=$pdo->prepare("SELECT p.*,i.nama,i.asal_universitas,i.tanggal_mulai,i.tanggal_selesai FROM perizinan p JOIN data_intern i ON i.id_intern=p.id_intern WHERE i.id_mentor=? ORDER BY p.created_at DESC");$st->execute([$user['id_mentor']]);$rows=$st->fetchAll();?>
<main class="main"><h1>Perizinan Intern</h1><div class="cards"><?php foreach($rows as $r):?><article class="card"><div style="display:flex;justify-content:space-between;gap:10px"><div><h3 style="margin:0"><?=e($r['nama'])?></h3><div class="muted small"><?=e($r['asal_universitas'])?></div></div><span class="badge <?=$r['status_approval']==='menunggu'?'badge-red':($r['status_approval']==='disetujui'?'badge-green':'badge-yellow')?>"><?=e($r['status_approval'])?></span></div><p><b>Tanggal:</b> <?=$r['tanggal_izin']?></p><p><b>Alasan:</b> <?=e($r['alasan_izin'])?></p><?php if($r['status_approval']==='menunggu'):?><div class="actions"><button class="btn btn-blue decision" data-id="<?=$r['id_perizinan']?>" data-decision="disetujui">Approve</button><button class="btn btn-yellow decision" data-id="<?=$r['id_perizinan']?>" data-decision="ditolak">Decline</button></div><?php endif;?></article><?php endforeach;?></div></main><?php layout_end();exit;}
redirect('index.php?page=dashboard');
