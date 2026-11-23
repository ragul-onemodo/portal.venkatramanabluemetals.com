<?php
declare(strict_types=1);
require dirname(__DIR__).'/app.php';
set_exception_handler(function(Throwable $ex){error_log((string)$ex);http_response_code(503);echo 'The website is temporarily unavailable. Please contact the website administrator.';});
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/';
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');header('X-Frame-Options: SAMEORIGIN');
if($path==='/admin.css'){header('Content-Type: text/css');readfile(ROOT.'/admin.css');exit;}
if($path==='/portal.js'){header('Content-Type: text/javascript');readfile(ROOT.'/portal.js');exit;}
// Serve only the public website assets; source, config and uploaded files are never directly accessible.
if(preg_match('~^/(assets/[a-zA-Z0-9_./-]+|[a-zA-Z0-9_-]+\.(css|js))$~',$path)) {
 $base=realpath(ROOT.'/../dist');$file=realpath($base.$path);
 if(!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit;}
 $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));$types=['css'=>'text/css','js'=>'text/javascript','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','svg'=>'image/svg+xml','woff2'=>'font/woff2'];
 if(!isset($types[$ext])){http_response_code(404);exit;}
 header('Content-Type: '.$types[$ext]);header('Cache-Control: public,max-age=3600');readfile($file);exit;
}
$cfg=config();
if($cfg['https'] && (empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off')) {http_response_code(400);exit('Please use HTTPS to access this website.');}
ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
session_name('vbm_session');session_set_cookie_params(['httponly'=>true,'secure'=>$cfg['https'],'samesite'=>'Lax','path'=>'/']);session_start();
header('Cache-Control: no-store');
if(preg_match('~^/media/([a-f0-9]{24})$~',$path,$m)) {
 $r=record($m[1]);$file=ROOT.'/storage/uploads/'.basename($r['file']??'missing');
 if(!$r||!is_file($file)){http_response_code(404);exit;}
 header('Content-Type: '.$r['mime']);header("Content-Security-Policy: sandbox");
 header('Content-Disposition: '.($r['mime']==='application/pdf'?'attachment':'inline').'; filename="'.basename($file).'"');readfile($file);exit;
}
if($path==='/enquiry' && $_SERVER['REQUEST_METHOD']==='POST') {
 header('Content-Type: application/json');
 try {
  checkCsrf();throttle('enquiry:'.($_SERVER['REMOTE_ADDR']??''),5,3600);
  if(!empty($_POST['website'])) throw new InvalidArgumentException('Unable to submit this enquiry.');
  $data=['name'=>required('name',100),'email'=>email(required('email',190)),'quantity'=>required('quantity',100),'details'=>required('details',2000),'material'=>required('material',120),'status'=>'New','notes'=>'','created_at'=>date('c')];
  foreach(['phone'=>30,'company'=>120,'service'=>120] as $key=>$max){$value=trim((string)($_POST[$key]??''));if(mb_strlen($value)>$max)throw new InvalidArgumentException(ucfirst($key).' is too long.');$data[$key]=$value;}
  $id=uid();save($id,'enquiries',$data);echo json(['ok'=>true,'message'=>'Thank you. Your enquiry has been received. Our team will review your requirements.','reference'=>$id]);
 }catch(InvalidArgumentException $ex){http_response_code(422);echo json(['ok'=>false,'message'=>$ex->getMessage()]);}exit;
}
if(str_starts_with($path,'/admin')) {require ROOT.'/admin.php';exit;}
if($path==='/machinery.html') redirect('/plant-infrastructure.html#jaw');
$slug=$path==='/'?'index':preg_replace('/\.html$/','',trim($path,'/'));
if(!isset(PAGES[$slug])){http_response_code(404);echo '<h1>Page not found</h1><a href="/">Return to home</a>';exit;}
require ROOT.'/website.php';
