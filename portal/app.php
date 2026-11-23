<?php
declare(strict_types=1);
const ROOT = __DIR__;
<<<<<<< HEAD
const PAGES = ['index'=>'Home','products'=>'Products','services'=>'Services','mission-vision'=>'Mission & vision','plant-infrastructure'=>'Our plant','owner-profile'=>'Owner profile','contact'=>'Contact us'];
=======
const PAGES = ['index'=>'Home','products'=>'Products','services'=>'Services','mission-vision'=>'Mission & vision','plant-infrastructure'=>'Our Plant','owner-profile'=>'Our Owner','contact'=>'Contact us'];
>>>>>>> 83e3c64e782ae412c653c3f68fd70921e95fd23d
function config(): array {
    static $config;
    if ($config !== null) return $config;
    if (getenv('VBM_TEST_DSN')) return $config = ['dsn'=>getenv('VBM_TEST_DSN'),'user'=>getenv('VBM_TEST_USER')?:'root','password'=>getenv('VBM_TEST_PASSWORD')?:'','https'=>false];
    if (getenv('VBM_TEST_DB')) return $config = ['dsn'=>'sqlite:'.getenv('VBM_TEST_DB'),'user'=>null,'password'=>null,'https'=>false];
    if (!is_file(ROOT.'/config.php')) throw new RuntimeException('Setup required. Follow portal/DEPLOY.md.');
    return $config = require ROOT.'/config.php';
}
function db(): PDO {
    static $db;
    if (!$db) {
        $c=config(); $db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
    }
    return $db;
}
function query(string $sql,array $params=[]): PDOStatement { $s=db()->prepare($sql);$s->execute($params);return $s; }
function e(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function json(array $v): string {return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);}
function record(string $id): array { $r=query('SELECT payload FROM records WHERE id=?',[$id])->fetchColumn();return $r?json_decode($r,true,512,JSON_THROW_ON_ERROR):[]; }
function records(string $kind): array {
    return array_map(fn($r)=>['id'=>$r['id']]+json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR),query('SELECT id,payload FROM records WHERE kind=? ORDER BY updated_at DESC,id',[$kind])->fetchAll());
}
function save(string $id,string $kind,array $data): void {
    if(query('SELECT id FROM records WHERE id=?',[$id])->fetchColumn()) query('UPDATE records SET payload=?,updated_at=? WHERE id=?',[json($data),date('c'),$id]);
    else query('INSERT INTO records(id,kind,payload,updated_at) VALUES(?,?,?,?)',[$id,$kind,json($data),date('c')]);
}
function uid(): string {return bin2hex(random_bytes(12));}
function csrf(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function csrfField(): string {return '<input type="hidden" name="csrf" value="'.e(csrf()).'">';}
function checkCsrf(): void {if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new InvalidArgumentException('Your form expired. Reload the page and try again.');}
function redirect(string $url): never {header('Location: '.$url,true,303);exit;}
function user(): array|false {
    if(empty($_SESSION['user_id'])) return false;
    if(time()-($_SESSION['last_seen']??0)>1800){unset($_SESSION['user_id']);return false;}
    $u=query('SELECT * FROM users WHERE id=? AND active=1',[$_SESSION['user_id']])->fetch();
    if(!$u || !hash_equals($u['password'],$_SESSION['auth_hash']??'')){unset($_SESSION['user_id']);return false;}
    $_SESSION['last_seen']=time();return $u;
}
function audit(string $event): void {query('INSERT INTO audit(id,actor,event,created_at) VALUES(?,?,?,?)',[uid(),$_SESSION['user_id']??'system',$event,date('c')]);}
function throttle(string $key,int $limit,int $window): void {
    query('DELETE FROM attempts WHERE happened_at<?',[time()-86400]);
    $key=hash('sha256',$key);
    $count=query('SELECT COUNT(*) FROM attempts WHERE bucket=? AND happened_at>?',[$key,time()-$window])->fetchColumn();
    if($count >= $limit) throw new InvalidArgumentException('Too many attempts. Please try again later.');
    query('INSERT INTO attempts(id,bucket,happened_at) VALUES(?,?,?)',[uid(),$key,time()]);
}
function required(string $key,int $max=255): string {
    $v=trim((string)($_POST[$key]??''));if($v===''||mb_strlen($v)>$max) throw new InvalidArgumentException(ucfirst(str_replace('_',' ',$key)).' is required (maximum '.$max.' characters).');return $v;
}
function email(string $value): string {if(!filter_var($value,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');return strtolower($value);}
function password(string $value): string {if(strlen($value)<12||strlen($value)>72) throw new InvalidArgumentException('Use a password between 12 and 72 characters.');return password_hash($value,PASSWORD_DEFAULT);}
function document(string $slug): DOMDocument {
    if(!isset(PAGES[$slug])) throw new InvalidArgumentException('Page not found.');
    $d=new DOMDocument();libxml_use_internal_errors(true);
    $d->loadHTML('<?xml encoding="utf-8" ?>'.file_get_contents(ROOT.'/../dist/'.$slug.'.html'));libxml_clear_errors();return $d;
}
function textNodes(DOMDocument $d): array {
    $x=new DOMXPath($d);$out=[];
    foreach($x->query('//main//text()[normalize-space() and not(ancestor::script) and not(ancestor::style) and not(ancestor::form) and not(ancestor::*[contains(@class,"material-card")]) and not(ancestor::*[@id="catalogue"]) and not(ancestor::*[@id="certificates"])]') as $n) $out[]=$n;
    return $out;
}
function upload(string $field): ?string {
    $f=$_FILES[$field]??null;if(!$f||$f['error']===UPLOAD_ERR_NO_FILE) return null;
    if($f['error']!==UPLOAD_ERR_OK || $f['size']>10*1024*1024) throw new InvalidArgumentException('Choose a JPG, PNG, WebP or PDF smaller than 10 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    if(!isset($extensions[$mime])) throw new InvalidArgumentException('Only JPG, PNG, WebP and PDF files are allowed.');
    if(str_starts_with($mime,'image/')&&!getimagesize($f['tmp_name'])) throw new InvalidArgumentException('Invalid image.');
    $id=uid();$filename=$id.'.'.$extensions[$mime];
    if(!is_dir(ROOT.'/storage/uploads')) mkdir(ROOT.'/storage/uploads',0750,true);
    if(!move_uploaded_file($f['tmp_name'],ROOT.'/storage/uploads/'.$filename)) throw new RuntimeException('Upload could not be saved.');
    save($id,'media',['name'=>mb_substr(basename($f['name']),0,180),'file'=>$filename,'mime'=>$mime,'size'=>$f['size']]);return $id;
}
function imageUrl(string $id): string {
    if($id==='') return '';
    $r=record($id);if(!$r||!str_starts_with($r['mime']??'','image/')) throw new InvalidArgumentException('Select an image from the media library.');
    return '/media/'.$id;
}
