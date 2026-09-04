<?php
declare(strict_types=1);
session_start();

const APP_NAME = 'Absensi Magang UP3 Serpong';
const APP_ENV = 'local'; // ubah menjadi production saat deploy
const BASE_URL = '';      // jika diakses dari http://localhost/absensi-pln/public, biarkan kosong

$dbHost = '127.0.0.1';
$dbName = 'absensi_pln';
$dbUser = 'root';
$dbPass = ''; // default XAMPP biasanya kosong; isi jika MySQL Anda memakai password

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database belum terhubung. Periksa config.php dan pastikan MySQL XAMPP aktif. Detail: '.htmlspecialchars($e->getMessage()));
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

function csrf(): string { return $_SESSION['csrf']; }
function check_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); exit('CSRF token tidak valid.'); }
}
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function flash(string $type,string $msg): void { $_SESSION['flash']=[$type,$msg]; }
function get_flash(): ?array { $x=$_SESSION['flash']??null; unset($_SESSION['flash']); return $x; }

function login_user(string $role,int $id): void {
    session_regenerate_id(true);
    $_SESSION['role']=$role; $_SESSION['user_id']=$id;
}
function logout_user(): void { $_SESSION=[]; session_destroy(); redirect('index.php?page=role'); }
function auth_role(?string $role=null): bool {
    if (!isset($_SESSION['role'],$_SESSION['user_id'])) return false;
    return $role===null || $_SESSION['role']===$role;
}
function require_role(string $role): void { if(!auth_role($role)) redirect('index.php?page=role'); }

function current_user(PDO $pdo): ?array {
    if (!auth_role()) return null;
    $id=(int)$_SESSION['user_id']; $role=$_SESSION['role'];
    $sql=$role==='intern'
        ? "SELECT i.*,a.username FROM data_intern i JOIN akun_intern a ON a.id_intern=i.id_intern WHERE i.id_intern=?"
        : "SELECT m.*,a.username FROM data_mentor m JOIN akun_mentor a ON a.id_mentor=m.id_mentor WHERE m.id_mentor=?";
    $st=$pdo->prepare($sql); $st->execute([$id]); return $st->fetch() ?: null;
}
function json_out(array $data,int $code=200): never {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); exit;
}
function save_base64_image(string $data,string $prefix): ?string {
    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/s',$data,$m)) return null;
    $ext=$m[1]==='jpeg'||$m[1]==='jpg'?'jpg':$m[1];
    $bin=base64_decode(str_replace(' ','+',$m[2]),true);
    if ($bin===false || strlen($bin)>5*1024*1024) return null;
    $dir=__DIR__.'/storage/uploads'; if(!is_dir($dir)) mkdir($dir,0755,true);
    $name=$prefix.'_'.bin2hex(random_bytes(8)).'.'.$ext; file_put_contents($dir.'/'.$name,$bin);
    return 'storage/uploads/'.$name;
}
function haversine(float $lat1,float $lon1,float $lat2,float $lon2): float {
    $R=6371000; $p=pi()/180; $a=sin(($lat2-$lat1)*$p/2)**2+cos($lat1*$p)*cos($lat2*$p)*sin(($lon2-$lon1)*$p/2)**2;
    return 2*$R*asin(min(1,sqrt($a)));
}
function work_schedule(PDO $pdo,string $date): ?array {
    $day=['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l',strtotime($date))];
    $st=$pdo->prepare("SELECT * FROM jadwal_kerja WHERE hari=?"); $st->execute([$day]); return $st->fetch() ?: null;
}
function permission_for(PDO $pdo,int $intern,string $date): ?array {
    $st=$pdo->prepare("SELECT * FROM perizinan WHERE id_intern=? AND tanggal_izin=?");
    $st->execute([$intern,$date]); return $st->fetch() ?: null;
}
function auto_assign_mentor(PDO $pdo,int $internId,string $divisi): void {
    $st=$pdo->prepare("SELECT id_mentor FROM data_mentor WHERE divisi=? ORDER BY id_mentor LIMIT 1");
    $st->execute([$divisi]); if($m=$st->fetch()) {
        $u=$pdo->prepare("UPDATE data_intern SET id_mentor=? WHERE id_intern=?"); $u->execute([$m['id_mentor'],$internId]);
    }
}
function calculate_analysis(PDO $pdo,int $internId,string $start,string $end): array {
    $st=$pdo->prepare("SELECT tanggal,jam_kedatangan,jam_pulang FROM presensi WHERE id_intern=? AND status_kehadiran='Hadir' AND tanggal BETWEEN ? AND ? ORDER BY tanggal");
    $st->execute([$internId,$start,$end]); $rows=$st->fetchAll();
    $scheduled=0; $attendance=count($rows); $onTime=0; $overtime=0; $hours=[];
    $d=new DateTime($start); $to=new DateTime($end);
    while($d <= $to){ $day=(int)$d->format('N'); if($day<=5)$scheduled++; $d->modify('+1 day'); }
    foreach($rows as $r){
        $s=work_schedule($pdo,$r['tanggal']); if(!$s) continue;
        if($r['jam_kedatangan'] && $r['jam_kedatangan'] <= $s['jam_masuk']) $onTime++;
        if($r['jam_pulang'] && $r['jam_pulang'] > $s['jam_pulang']) $overtime++;
        if($r['jam_kedatangan'] && $r['jam_pulang']) {
            $a=strtotime($r['tanggal'].' '.$r['jam_kedatangan']); $b=strtotime($r['tanggal'].' '.$r['jam_pulang']);
            if($b>$a)$hours[]=($b-$a)/3600;
        }
    }
    $rajin=$scheduled?min(100,round($attendance/$scheduled*100)):0;
    $tepat=$attendance?round($onTime/$attendance*100):0;
    $lembur=$attendance?round($overtime/$attendance*100):0;
    $avg=$hours?array_sum($hours)/count($hours):0;
    $std=0; if(count($hours)>1){$sum=0;foreach($hours as $h)$sum+=($h-$avg)**2;$std=sqrt($sum/count($hours));}
    $konsistensi=$avg?max(0,min(100,round(100-($std/$avg*100)))):0;
    return compact('rajin','tepat','lembur','konsistensi');
}
