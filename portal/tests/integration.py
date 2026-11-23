"""HTTP integration tests against a disposable SQLite database. No production data is touched."""
import base64
from contextlib import closing
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import socket
import sqlite3
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]

class PortalTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory(prefix='vbm-test-')
        cls.database = str(Path(cls.temp.name) / 'test.sqlite')
        cls.password = secrets.token_urlsafe(24)
        env = dict(os.environ, VBM_TEST_DB=cls.database, VBM_ADMIN_PASSWORD=cls.password)
        subprocess.run(['php', str(ROOT / 'setup.php'), 'admin', 'admin@example.test'], env=env, check=True, capture_output=True)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
        cls.url = f'http://127.0.0.1:{port}'
        cls.log = open(Path(cls.temp.name) / 'server.log', 'w')
        cls.server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'public'), str(ROOT / 'public/index.php')], env=env, stdout=cls.log, stderr=cls.log)
        for _ in range(60):
            try:
                urllib.request.urlopen(cls.url + '/admin/login'); break
            except OSError: time.sleep(.1)
        else: raise RuntimeError('Test server failed to start')

    @classmethod
    def tearDownClass(cls):
        # Remove only files created by this test database.
        with closing(sqlite3.connect(cls.database)) as conn:
            for (payload,) in conn.execute("SELECT payload FROM records WHERE kind='media'"):
                (ROOT / 'storage/uploads' / json.loads(payload)['file']).unlink(missing_ok=True)
        cls.server.terminate(); cls.server.wait(timeout=10); cls.log.close(); cls.temp.cleanup()

    def setUp(self):
        self.browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, data=None, raw=None, headers=None):
        body = raw if raw is not None else (urllib.parse.urlencode(data).encode() if data is not None else None)
        request = urllib.request.Request(self.url + path, data=body, headers=headers or {})
        try: response = self.browser.open(request)
        except urllib.error.HTTPError as ex: response = ex
        return response.status, response.read().decode('utf-8', errors='replace'), response

    def token(self, path):
        status, html, _ = self.request(path); self.assertEqual(status, 200)
        return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)

    def login(self):
        token = self.token('/admin/login')
        status, html, _ = self.request('/admin/login', {'csrf': token, 'email': 'admin@example.test', 'password': self.password})
        self.assertEqual(status, 200); self.assertIn('Recent activity', html)

    def db(self, sql, args=()):
        with closing(sqlite3.connect(self.database)) as conn: return conn.execute(sql, args).fetchall()

    def test_01_protected_routes_and_static_assets(self):
        for path in ['/admin','/admin/enquiries','/admin/users']:
            _, _, response = self.request(path); self.assertTrue(response.url.endswith('/admin/login'))
        for path in ['/admin.css','/portal.js','/assets/vbm-logo.png','/contact.html','/plant-infrastructure.html']:
            self.assertEqual(self.request(path)[0], 200, path)
        for path in ['/config.php','/storage/preview.sqlite','/.git/config','/assets/../../portal/config.php']:
            self.assertEqual(self.request(path)[0], 404, path)

    def test_02_authentication_and_csrf(self):
        token = self.token('/admin/login')
        self.assertEqual(self.request('/admin/login', {'csrf': token,'email':'admin@example.test','password':'wrong'})[0],422)
        self.assertEqual(self.request('/admin/login', {'email':'admin@example.test','password':self.password})[0],422)
        self.login()
        before=self.db("SELECT payload FROM records WHERE id='company'")
        self.assertEqual(self.request('/admin/company', {'name':'Unauthorized'})[0],422)
        self.assertEqual(before,self.db("SELECT payload FROM records WHERE id='company'"))

    def test_03_product_lifecycle_and_safe_output(self):
        self.login(); token=self.token('/admin/products?new=1')
        data={'csrf':token,'title':'<script>alert(1)</script> Test stone','description':'Rock for construction','order':'12','published':'1','image':''}
        self.assertEqual(self.request('/admin/products?new=1',data)[0],200)
        rows=self.db("SELECT id,payload FROM records WHERE kind='products'")
        item=next((i,p) for i,p in rows if 'Test stone' in p)
        html=self.request('/products.html')[1]
        self.assertIn('&lt;script&gt;alert(1)&lt;/script&gt;',html)
        self.assertNotIn('<script>alert(1)</script>',html)
        data.update(id=item[0],title='Draft test material'); data.pop('published')
        self.request('/admin/products?edit='+item[0],data)
        self.assertNotIn('Draft test material',self.request('/products.html')[1])
        self.request('/admin/products',{'csrf':token,'id':item[0],'action':'delete','confirm':'yes'})
        self.assertEqual(self.db('SELECT id FROM records WHERE id=?',(item[0],)),[])

    def test_04_enquiry_submission_validation_and_workflow(self):
        token=self.token('/contact.html')
        data={'csrf':token,'name':'Test Customer','email':'customer@example.test','material':'M Sand','quantity':'25 tonnes','details':'Coimbatore delivery','phone':'','company':'','service':''}
        invalid=dict(data,email='not-an-email')
        self.assertEqual(self.request('/enquiry',invalid)[0],422)
        status, body,_=self.request('/enquiry',data);self.assertEqual(status,200)
        enquiry=json.loads(body)['reference']
        self.login();token=self.token('/admin/enquiries?view='+enquiry)
        self.request('/admin/enquiries?view='+enquiry,{'csrf':token,'id':enquiry,'status':'Contacted','notes':'Called customer'})
        saved=json.loads(self.db('SELECT payload FROM records WHERE id=?',(enquiry,))[0][0])
        self.assertEqual(saved['status'],'Contacted');self.assertEqual(saved['notes'],'Called customer')
        self.request('/admin/enquiries',{'csrf':token,'id':enquiry,'action':'delete','confirm':'yes'})
        self.assertEqual(self.db('SELECT id FROM records WHERE id=?',(enquiry,)),[])

    def test_05_page_editor_and_company_settings(self):
        self.login();token=self.token('/admin/pages?page=services')
        self.request('/admin/pages?page=services',{'csrf':token,'slug':'services','text[0]':'Changed service introduction'})
        self.assertIn('Changed service introduction',self.request('/services.html')[1])
        settings=json.loads(self.db("SELECT payload FROM records WHERE id='company'")[0][0])
        settings.update(csrf=token,email='sales@example.test',capacity='400')
        self.request('/admin/company',settings)
        self.assertIn('mailto:sales@example.test',self.request('/contact.html')[1])
        self.assertIn('126/2A &amp; 127',self.request('/contact.html')[1])
        self.assertIn('400 TPH',self.request('/plant-infrastructure.html')[1])

    def test_06_upload_and_certificate(self):
        self.login();token=self.token('/admin/media')
        def send(name, payload, mime):
            boundary='VBM'+secrets.token_hex(12)
            body=(f'--{boundary}\r\nContent-Disposition: form-data; name="csrf"\r\n\r\n{token}\r\n--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n').encode()+payload+f'\r\n--{boundary}--\r\n'.encode()
            return self.request('/admin/media',raw=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})
        self.assertEqual(send('evil.php',b'<?php echo 1;','application/x-php')[0],422)
        self.assertEqual(send('certificate.pdf',b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF','application/pdf')[0],200)
        media=self.db("SELECT id FROM records WHERE kind='media'")[0][0]
        self.request('/admin/certificates?new=1',{'csrf':token,'title':'Test clearance','description':'Reference TEST','order':'0','published':'1','media':media})
        self.assertIn('/media/'+media,self.request('/contact.html')[1])
        response=self.request('/media/'+media)[2]
        self.assertIn('attachment',response.headers['Content-Disposition'])
        self.assertEqual(response.headers['X-Content-Type-Options'],'nosniff')

    def test_07_admin_accounts_and_logout(self):
        self.login();token=self.token('/admin/users')
        self.request('/admin/users',{'csrf':token,'name':'Second admin','email':'second@example.test','password':secrets.token_urlsafe(24)})
        user_id=self.db("SELECT id FROM users WHERE email='second@example.test'")[0][0]
        self.request('/admin/users',{'csrf':token,'action':'disable','id':user_id})
        self.assertEqual(self.db('SELECT active FROM users WHERE id=?',(user_id,))[0][0],0)
        own=self.db("SELECT id FROM users WHERE email='admin@example.test'")[0][0]
        self.assertEqual(self.request('/admin/users',{'csrf':token,'action':'disable','id':own})[0],422)
        self.request('/admin',{'csrf':token,'action':'logout'})
        self.assertTrue(self.request('/admin')[2].url.endswith('/admin/login'))

    def test_08_login_rate_limit(self):
        token=self.token('/admin/login')
        for _ in range(15):
            _,html,_=self.request('/admin/login',{'csrf':token,'email':'missing@example.test','password':'incorrect'})
        self.assertIn('Too many attempts',html)

if __name__ == '__main__': unittest.main(verbosity=2)
