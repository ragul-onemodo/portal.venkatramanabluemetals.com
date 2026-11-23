# Venkatramana Blue Metals

Company website and PHP/MySQL admin panel for portal.venkatramanabluemetals.com.

- dist/: responsive website design, seven HTML pages and image assets.
- portal/: dynamic website, administrator login, dashboard, page editing, product and certificate management, uploads, company settings and enquiry inbox.
- portal/public/: cPanel document root for the full website.
- portal/DEPLOY.md: installation, administrator setup, backups and tests.

The backend uses PHP, PDO and MySQL. No Composer or Node build is required. Database credentials, uploads and local test data are excluded from Git.

For the original static preview only, run `node server.cjs` and open http://127.0.0.1:4173. Its form downloads an enquiry text file. For the full website, follow [the deployment guide](portal/DEPLOY.md); its form submits to the admin inbox. Automatic email notifications are not configured.

Company details reflect supplied documents: established 2022, 30+ years of industry experience, 350 TPH plant capacity, GSTIN 33AAVFV7259F1ZK. Certificate placeholders remain until actual documents are uploaded. Stock photographs are representative; see dist/assets/photo-credits.txt.
