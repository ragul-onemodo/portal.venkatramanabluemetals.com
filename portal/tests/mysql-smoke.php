<?php
// CLI-only: creates and drops only a uniquely named temporary test database.
if(PHP_SAPI!=='cli')exit(1);
$server=new PDO(getenv('VBM_MYSQL_SERVER')?:'mysql:host=127.0.0.1;port=3306',getenv('VBM_TEST_USER')?:'root',getenv('VBM_TEST_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$database='vbm_test_'.bin2hex(random_bytes(8));
if(!preg_match('/^vbm_test_[a-f0-9]{16}$/',$database))exit(1);
$server->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
 putenv('VBM_TEST_DSN='.(getenv('VBM_MYSQL_SERVER')?:'mysql:host=127.0.0.1;port=3306').';dbname='.$database.';charset=utf8mb4');
 require dirname(__DIR__).'/setup.php';
 if(count(records('products'))!==10)throw new RuntimeException('Product seeding failed');
 save('test-enquiry','enquiries',['name'=>'MySQL integration test','status'=>'New']);
 if(record('test-enquiry')['status']!=='New')throw new RuntimeException('Record read failed');
 save('test-enquiry','enquiries',['name'=>'MySQL integration test','status'=>'Closed']);
 if(record('test-enquiry')['status']!=='Closed')throw new RuntimeException('Record update failed');
 query('DELETE FROM records WHERE id=?',['test-enquiry']);
 throttle('smoke',2,60);audit('MySQL smoke test');
 echo 'MySQL '.$server->getAttribute(PDO::ATTR_SERVER_VERSION).": schema, seed, CRUD, rate limiter and audit passed.\n";
} finally {$server->exec('DROP DATABASE `'.$database.'`');}
