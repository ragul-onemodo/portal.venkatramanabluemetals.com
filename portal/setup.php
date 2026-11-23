<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app.php';
foreach([
 'CREATE TABLE IF NOT EXISTS users(id VARCHAR(48) PRIMARY KEY,email VARCHAR(190) NOT NULL UNIQUE,name VARCHAR(120) NOT NULL,password VARCHAR(255) NOT NULL,active INTEGER NOT NULL DEFAULT 1)',
 'CREATE TABLE IF NOT EXISTS records(id VARCHAR(100) PRIMARY KEY,kind VARCHAR(30) NOT NULL,payload TEXT NOT NULL,updated_at VARCHAR(40) NOT NULL)',
 'CREATE TABLE IF NOT EXISTS attempts(id VARCHAR(48) PRIMARY KEY,bucket VARCHAR(64) NOT NULL,happened_at BIGINT NOT NULL)',
 'CREATE TABLE IF NOT EXISTS audit(id VARCHAR(48) PRIMARY KEY,actor VARCHAR(48) NOT NULL,event VARCHAR(255) NOT NULL,created_at VARCHAR(40) NOT NULL)',
] as $sql) db()->exec($sql);
if(!record('company')) save('company','settings',[
 'name'=>'Venkatramana Blue Metals','email'=>'venkatramana.vbm@gmail.com','phone'=>'+91 98422 72439','gst'=>'33AAVFV7259F1ZK','established'=>'2022','partners'=>'B. Nandakumar & E. Kamalaveni',
 'address'=>'SF NO.126/1,126/2A & 127, Thaneerpanthal, Chinnakallipatti Village, Mettupalayam, Sirumugai, Coimbatore, Tamil Nadu – 641302',
 'branch'=>'Branch address to be confirmed.','site'=>'Chinnakallipatti, Coimbatore, Tamil Nadu','experience'=>'30+','capacity'=>'350','video'=>'hcX54FTgfYM','logo'=>'']);
if(!record('seeded')) {
 foreach(['M Sand','P Sand','6mm','12mm','20mm','40mm','60mm','W Mix','GSP','W Dust'] as $i=>$name) save('product-'.$i,'products',['title'=>$name,'description'=>'Contact our team for specifications, availability and delivery.','image'=>'','published'=>'1','order'=>(string)$i]);
 foreach(['Quarry & crusher licence','Environmental clearance certificate'] as $i=>$title) save('certificate-'.$i,'certificates',['title'=>$title,'description'=>'Document not yet uploaded.','media'=>'','published'=>'1','order'=>(string)$i]);
 save('seeded','system',['version'=>1]);
}
if(($argv[1]??'')==='admin') {
 $mail=email($argv[2]??'');$secret=getenv('VBM_ADMIN_PASSWORD')?:'';
 if(!$secret){fwrite(STDERR,"Set VBM_ADMIN_PASSWORD in your current terminal session. Do not put it in command arguments.\n");exit(1);}
 $hash=password($secret);$u=query('SELECT id FROM users WHERE email=?',[$mail])->fetchColumn();
 if($u) query('UPDATE users SET password=?,active=1 WHERE id=?',[$hash,$u]);
 else query('INSERT INTO users(id,email,name,password,active) VALUES(?,?,?,?,1)',[uid(),$mail,'Administrator',$hash]);
 echo "Administrator account ready.\n";
}
echo "Database ready. Existing content preserved.\n";
