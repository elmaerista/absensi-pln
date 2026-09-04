<?php
require __DIR__.'/config.php';
$action=$_GET['action']??'';
if($_SERVER['REQUEST_METHOD']==='POST' && !in_array($action,['check_username','send_otp','verify_otp'])) check_csrf();

function input(string $key,string $default=''): string { return trim($_POST[$key]??$default); }

try {
switch($action){
case 'check_username':
    $u=input('username'); $role=input('role');
    if(strlen($u)<1) json_out(['available'=>false,'message'=>'']);
    $table=$role==='mentor'?'akun_mentor':'akun_intern';
    $st=$pdo->prepare("SELECT 1 FROM $table WHERE username=? LIMIT 1"); $st->execute([$u]);
    $taken=(bool)$st->fetch(); json_out(['available'=>!$taken,'message'=>$taken?'username sudah terpakai':'']);
case 'register_data':
    $role=input('role');
    if(!in_array($role,['intern','mentor'])) json_out(['ok'=>false,'message'=>'Role tidak valid.'],422);
    $required=$role==='intern'
        ? ['nama','tanggal_lahir','nim','email','no_telp','asal_universitas','program_studi','divisi','tanggal_mulai','tanggal_selesai']
        : ['nama','tanggal_lahir','nip','email','no_telp','divisi'];
    foreach($required as $k) if(input($k)==='') json_out(['ok'=>false,'message'=>"Field $k wajib diisi."],422);
    if($role==='intern' && strtotime(input('tanggal_selesai')) < strtotime(input('tanggal_mulai'))) json_out(['ok'=>false,'message'=>'Tanggal selesai tidak boleh sebelum tanggal mulai.'],422);
    $foto=null;
    if(!empty($_POST['foto_base64'])) $foto=save_base64_image($_POST['foto_base64'],$role.'_profile');
    $_SESSION['reg_'.$role]=[
        'nama'=>input('nama'),'tanggal_lahir'=>input('tanggal_lahir'),'email'=>input('email'),'no_telp'=>input('no_telp'),'divisi'=>input('divisi'),'foto'=>$foto
    ];
    if($role==='intern'){
        $_SESSION['reg_intern'] += ['NIM'=>input('nim'),'asal_universitas'=>input('asal_universitas'),'program_studi'=>input('program_studi'),'tanggal_mulai'=>input('tanggal_mulai'),'tanggal_selesai'=>input('tanggal_selesai')];
    } else {
        $_SESSION['reg_mentor'] += ['NIP'=>input('nip')];
    }
    json_out(['ok'=>true,'redirect'=>"index.php?page=register-account&role=$role"]);
case 'register_account':
    $role=input('role'); $r=$_SESSION['reg_'.$role]??null;
    if(!$r) json_out(['ok'=>false,'message'=>'Sesi pendaftaran habis.'],422);
    $u=input('username'); $p=$_POST['password']??''; $p2=$_POST['password2']??'';
    if(strlen($p)<4) json_out(['ok'=>false,'message'=>'Password minimal 4 karakter.'],422);
    if($p!==$p2) json_out(['ok'=>false,'message'=>'Password belum sama.'],422);
    $table=$role==='mentor'?'akun_mentor':'akun_intern';
    $st=$pdo->prepare("SELECT 1 FROM $table WHERE username=?");$st->execute([$u]);
    if($st->fetch()) json_out(['ok'=>false,'message'=>'Username sudah terpakai.'],422);
    $pdo->beginTransaction();
    if($role==='intern'){
        $st=$pdo->prepare("INSERT INTO data_intern (nama,tanggal_lahir,NIM,email,no_telp,asal_universitas,program_studi,divisi,tanggal_mulai,tanggal_selesai,foto) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $st->execute([$r['nama'],$r['tanggal_lahir'],$r['NIM'],$r['email'],$r['no_telp'],$r['asal_universitas'],$r['program_studi'],$r['divisi'],$r['tanggal_mulai'],$r['tanggal_selesai'],$r['foto']]);
        $id=(int)$pdo->lastInsertId(); auto_assign_mentor($pdo,$id,$r['divisi']);
        $st=$pdo->prepare("INSERT INTO akun_intern(id_intern,username,password) VALUES(?,?,?)"); $st->execute([$id,$u,password_hash($p,PASSWORD_DEFAULT)]);
    } else {
        $st=$pdo->prepare("INSERT INTO data_mentor (nama,tanggal_lahir,NIP,email,no_telp,divisi,foto) VALUES (?,?,?,?,?,?,?)");
        $st->execute([$r['nama'],$r['tanggal_lahir'],$r['NIP'],$r['email'],$r['no_telp'],$r['divisi'],$r['foto']]);
        $id=(int)$pdo->lastInsertId();
        $st=$pdo->prepare("INSERT INTO akun_mentor(id_mentor,username,password) VALUES(?,?,?)"); $st->execute([$id,$u,password_hash($p,PASSWORD_DEFAULT)]);
    }
    $pdo->commit(); unset($_SESSION['reg_'.$role]);
    json_out(['ok'=>true,'message'=>'Pendaftaran berhasil.','redirect'=>"index.php?page=login&role=$role"]);
case 'login':
    $role=input('role');$u=input('username');$p=$_POST['password']??'';
    $table=$role==='mentor'?'akun_mentor':'akun_intern';$idcol=$role==='mentor'?'id_mentor':'id_intern';
    $st=$pdo->prepare("SELECT * FROM $table WHERE username=?");$st->execute([$u]);$a=$st->fetch();
    if(!$a || !password_verify($p,$a['password'])) json_out(['ok'=>false,'message'=>'Username atau password salah.'],401);
    login_user($role,(int)$a[$idcol]); json_out(['ok'=>true,'redirect'=>"index.php?page=dashboard"]);
case 'send_otp':
    $role=input('role');$contact=input('contact');
    $table=$role==='mentor'?'data_mentor':'data_intern';$contactCol=str_contains($contact,'@')?'email':'no_telp';
    $st=$pdo->prepare("SELECT * FROM $table WHERE $contactCol=?");$st->execute([$contact]);$person=$st->fetch();
    if(!$person) json_out(['ok'=>true,'message'=>'Jika data cocok, kode OTP telah dibuat.']);
    $otp=(string)random_int(100000,999999); $_SESSION['otp']=['role'=>$role,'id'=>(int)$person[$role==='mentor'?'id_mentor':'id_intern'],'code'=>$otp,'expires'=>time()+300];
    file_put_contents(__DIR__.'/storage/logs/otp.log',date('c')." [$role] {$contact} OTP={$otp}\n",FILE_APPEND);
    json_out(['ok'=>true,'message'=>'OTP dibuat. Untuk mode lokal, kode ditampilkan agar dapat diuji.','dev_otp'=>APP_ENV==='local'?$otp:null]);
case 'verify_otp':
    $code=input('code');$o=$_SESSION['otp']??null;
    if(!$o || time()>$o['expires'] || !hash_equals($o['code'],$code)) json_out(['ok'=>false,'message'=>'OTP salah atau sudah kedaluwarsa.'],422);
    $_SESSION['otp_verified']=$o; unset($_SESSION['otp']); json_out(['ok'=>true]);
case 'reset_password':
    $o=$_SESSION['otp_verified']??null;$p=$_POST['password']??'';$p2=$_POST['password2']??'';
    if(!$o || strlen($p)<4 || $p!==$p2) json_out(['ok'=>false,'message'=>'OTP belum diverifikasi atau password tidak valid.'],422);
    $table=$o['role']==='mentor'?'akun_mentor':'akun_intern';$idcol=$o['role']==='mentor'?'id_mentor':'id_intern';
    $st=$pdo->prepare("UPDATE $table SET password=? WHERE $idcol=?");$st->execute([password_hash($p,PASSWORD_DEFAULT),$o['id']]);
    unset($_SESSION['otp_verified']);json_out(['ok'=>true,'message'=>'Password berhasil diubah.']);
case 'attendance':
    require_role('intern'); $u=current_user($pdo);$type=input('type');$date=date('Y-m-d');$now=date('H:i:s');
    $s=work_schedule($pdo,$date); if(!$s || $s['jam_masuk']==='00:00:00') json_out(['ok'=>false,'message'=>'Hari ini bukan hari kerja.'],422);
    $perm=permission_for($pdo,(int)$u['id_intern'],$date);
    if($type==='datang'){
        if($perm) json_out(['ok'=>false,'message'=>'Anda memiliki perizinan pada hari ini.'],422);
        $st=$pdo->prepare("SELECT * FROM presensi WHERE id_intern=? AND tanggal=?");$st->execute([$u['id_intern'],$date]);$p=$st->fetch();
        if($p && $p['jam_kedatangan']) json_out(['ok'=>false,'message'=>'Presensi kedatangan hari ini sudah ada.'],422);
        $lat=(float)input('latitude');$lon=(float)input('longitude');$foto=save_base64_image($_POST['foto_base64']??'','datang'); if(!$foto) json_out(['ok'=>false,'message'=>'Bukti foto wajib diambil dari kamera.'],422);
        $loc=$pdo->query("SELECT * FROM lokasi_kantor LIMIT 1")->fetch();$distance=haversine($lat,$lon,(float)$loc['latitude'],(float)$loc['longitude']);
        if($distance > (float)$loc['radius_meter']) json_out(['ok'=>false,'message'=>'Anda berada '.round($distance).' meter dari kantor. Maksimal 100 meter.'],422);
        $late=$now>$s['jam_masuk'];$reason=input('alasan_keterlambatan');$ket=$late?('Terlambat'.($reason?' - '.$reason:'')):'Tepat waktu';
        if($p){$st=$pdo->prepare("UPDATE presensi SET jam_kedatangan=?,foto_datang=?,latitude_datang=?,longitude_datang=?,keterangan=? WHERE id_presensi=?");$st->execute([$now,$foto,$lat,$lon,$ket,$p['id_presensi']]);}
        else {$st=$pdo->prepare("INSERT INTO presensi(id_intern,tanggal,jam_kedatangan,foto_datang,latitude_datang,longitude_datang,keterangan) VALUES(?,?,?,?,?,?,?)");$st->execute([$u['id_intern'],$date,$now,$foto,$lat,$lon,$ket]);}
        json_out(['ok'=>true,'message'=>'Presensi kedatangan berhasil.','late'=>$late]);
    }
    if($type==='pulang'){
        $st=$pdo->prepare("SELECT * FROM presensi WHERE id_intern=? AND tanggal=?");$st->execute([$u['id_intern'],$date]);$p=$st->fetch();
        if(!$p || !$p['jam_kedatangan']) json_out(['ok'=>false,'message'=>'Belum ada presensi kedatangan.'],422);
        if($p['jam_pulang']) json_out(['ok'=>false,'message'=>'Presensi kepulangan sudah ada.'],422);
        $foto=save_base64_image($_POST['foto_base64']??'','pulang'); if(!$foto) json_out(['ok'=>false,'message'=>'Bukti foto wajib diambil dari kamera.'],422);
        $ket=$p['keterangan']??'';
        if($now>$s['jam_pulang']) $ket=$ket?($ket.', Lembur'):'Lembur';
        $st=$pdo->prepare("UPDATE presensi SET jam_pulang=?,foto_pulang=?,latitude_pulang=?,longitude_pulang=?,keterangan=? WHERE id_presensi=?");
        $st->execute([$now,$foto,(float)input('latitude'),(float)input('longitude'),$ket,$p['id_presensi']]);
        json_out(['ok'=>true,'message'=>'Presensi kepulangan berhasil.']);
    }
    json_out(['ok'=>false,'message'=>'Tipe presensi tidak valid.'],422);
case 'permission':
    require_role('intern');$u=current_user($pdo);$reason=input('alasan');$start=input('tanggal_mulai');$end=input('tanggal_selesai');
    if(!$reason||!$start||!$end||strtotime($end)<strtotime($start)) json_out(['ok'=>false,'message'=>'Lengkapi tanggal dan alasan.'],422);
    $mentorId=$u['id_mentor']??null;$pdo->beginTransaction();
    for($d=new DateTime($start);$d<=new DateTime($end);$d->modify('+1 day')){
        $date=$d->format('Y-m-d');$st=$pdo->prepare("SELECT id_perizinan FROM perizinan WHERE id_intern=? AND tanggal_izin=?");$st->execute([$u['id_intern'],$date]);
        if($st->fetch()) continue;
        $st=$pdo->prepare("INSERT INTO perizinan(id_intern,tanggal_izin,alasan_izin,id_mentor) VALUES(?,?,?,?)");$st->execute([$u['id_intern'],$date,$reason,$mentorId]);
        $pid=(int)$pdo->lastInsertId();
        $st=$pdo->prepare("SELECT id_presensi FROM presensi WHERE id_intern=? AND tanggal=?");$st->execute([$u['id_intern'],$date]);$existing=$st->fetch();
        if($existing){
            $pdo->prepare("UPDATE presensi SET status_kehadiran='Izin',id_perizinan=?,jam_kedatangan=NULL,jam_pulang=NULL,keterangan='Menunggu approval' WHERE id_presensi=?")->execute([$pid,$existing['id_presensi']]);
        } else {
            $pdo->prepare("INSERT INTO presensi(id_intern,tanggal,status_kehadiran,id_perizinan,keterangan) VALUES(?,?, 'Izin',?, 'Menunggu approval')")->execute([$u['id_intern'],$date,$pid]);
        }
    }
    $pdo->commit();json_out(['ok'=>true,'message'=>'Perizinan dikirim ke mentor.']);
case 'update_profile':
    if(!auth_role()) json_out(['ok'=>false,'message'=>'Belum login.'],401);
    $u=current_user($pdo);$role=$_SESSION['role'];$id=(int)$_SESSION['user_id'];$old=$_POST['current_password']??'';
    $table=$role==='mentor'?'akun_mentor':'akun_intern';$idcol=$role==='mentor'?'id_mentor':'id_intern';
    $st=$pdo->prepare("SELECT password FROM $table WHERE $idcol=?");$st->execute([$id]);$a=$st->fetch();
    if(!$a || !password_verify($old,$a['password'])) json_out(['ok'=>false,'message'=>'Password saat ini salah.'],422);
    $allowed=$role==='intern'?['nama','tanggal_lahir','email','no_telp','asal_universitas','program_studi','divisi','tanggal_mulai','tanggal_selesai']:['nama','tanggal_lahir','email','no_telp','divisi'];
    $sets=[];$vals=[];foreach($allowed as $k){if(isset($_POST[$k])){$sets[]="$k=?";$vals[]=input($k);}}
    if(!empty($_POST['new_password'])){$sets2=$sets;$vals2=$vals;$sets[]='';}
    if($sets){$sets=array_values(array_filter($sets));$sql="UPDATE ".($role==='mentor'?'data_mentor':'data_intern')." SET ".implode(',',$sets)." WHERE ".($role==='mentor'?'id_mentor':'id_intern')."=?";$vals[]=$id;$pdo->prepare($sql)->execute($vals);}
    if(!empty($_POST['new_password'])){$np=$_POST['new_password'];if(strlen($np)<4)json_out(['ok'=>false,'message'=>'Password baru minimal 4 karakter.'],422);$pdo->prepare("UPDATE $table SET password=? WHERE $idcol=?")->execute([password_hash($np,PASSWORD_DEFAULT),$id]);}
    json_out(['ok'=>true,'message'=>'Profil diperbarui.']);
case 'mentor_decision':
    require_role('mentor');$id=(int)input('id_perizinan');$decision=input('decision');if(!in_array($decision,['disetujui','ditolak']))json_out(['ok'=>false,'message'=>'Keputusan tidak valid.'],422);
    $st=$pdo->prepare("SELECT p.*,i.id_mentor FROM perizinan p JOIN data_intern i ON i.id_intern=p.id_intern WHERE p.id_perizinan=? AND i.id_mentor=?");$st->execute([$id,$_SESSION['user_id']]);$p=$st->fetch();if(!$p)json_out(['ok'=>false,'message'=>'Perizinan tidak ditemukan.'],404);
    $pdo->beginTransaction();$pdo->prepare("UPDATE perizinan SET status_approval=?,id_mentor=? WHERE id_perizinan=?")->execute([$decision,$_SESSION['user_id'],$id]);
    $ket=$decision==='disetujui'?'approved':'declined';$pdo->prepare("UPDATE presensi SET keterangan=?,status_kehadiran='Izin' WHERE id_perizinan=?")->execute([$ket,$id]);$pdo->commit();
    json_out(['ok'=>true,'message'=>'Status perizinan diperbarui.']);
case 'delete_intern':
    require_role('mentor');$id=(int)input('id_intern');$st=$pdo->prepare("DELETE FROM data_intern WHERE id_intern=? AND id_mentor=?");$st->execute([$id,$_SESSION['user_id']]);json_out(['ok'=>$st->rowCount()>0,'message'=>$st->rowCount()?'Intern dihapus.':'Tidak dapat menghapus intern.']);
case 'assign_existing':
    require_role('mentor');$st=$pdo->prepare("UPDATE data_intern SET id_mentor=? WHERE divisi=(SELECT divisi FROM data_mentor WHERE id_mentor=?) AND id_mentor IS NULL");$st->execute([$_SESSION['user_id'],$_SESSION['user_id']]);json_out(['ok'=>true]);
default: json_out(['ok'=>false,'message'=>'API action tidak ditemukan.'],404);
}
} catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    json_out(['ok'=>false,'message'=>APP_ENV==='local'?$e->getMessage():'Terjadi kesalahan server.'],500);
}
