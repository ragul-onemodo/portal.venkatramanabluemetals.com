# cPanel installation — VBM website and admin panel

This application uses HTML, CSS, JavaScript, PHP and MySQL. No Node, Composer or Laravel installation is required. phpMyAdmin can manage and back up the database. The existing `dist/` folder is a static preview; deploy the PHP application to receive enquiries and use the admin panel. The earlier ChatGPT Sites hosting cannot execute this PHP backend.

## Hosting requirements

PHP 8.2+ with PDO MySQL, DOM, mbstring and fileinfo; MySQL; Apache rewrite rules / `.htaccess`; cPanel Terminal or SSH for setup; and HTTPS for `portal.venkatramanabluemetals.com`.

## 1. Clone and connect the domain

Use cPanel Git Version Control with a read-only GitHub deploy key:

```text
git@github.com:Ragul-Sakkaravel/portal.venkatramanabluemetals.com.git
```

Example repository location: `/home/CPANELUSER/repositories/vbm`.

Set the domain's **document root** to `/home/CPANELUSER/repositories/vbm/portal/public`.

Only `portal/public` may be web-accessible. Do not point the domain at the repository root, `portal`, or `dist`. Keep `dist` beside `portal`; the application reads its design and assets from there. If your hosting restricts document roots, ask the host to configure this domain to the application's `portal/public` directory.

Enable AutoSSL and Force HTTPS Redirect in cPanel.

## 2. Create the MySQL database

1. In cPanel MySQL Databases, create a dedicated database and user.
2. Assign the user all privileges on that database only.
3. Copy `portal/config.example.php` to `portal/config.php`.
4. Enter your database name, username and password. Leave `https` set to `true`.

`config.php` is excluded from Git. Keep it outside the public document root and readable only by the hosting account / PHP process.

## 3. Initialize and create an administrator

In cPanel Terminal, replace the account path and email:

```bash
cd /home/CPANELUSER/repositories/vbm
php portal/setup.php
read -s -p "New admin password (12–72 characters): " VBM_ADMIN_PASSWORD
echo
export VBM_ADMIN_PASSWORD
php portal/setup.php admin ragul@onemodo.com
unset VBM_ADMIN_PASSWORD
```

The password is entered privately, without putting it in command history. Use the same command to reset an existing administrator if access is lost. There is no default password or public registration.

Setup creates tables and seeds company details, ten products and two certificate placeholders. Re-running setup preserves existing content. Upload the actual licence and clearance documents through the admin panel.

The PHP process needs write access to `portal/storage`; use your host's ownership/permissions without making it world-writable. In MultiPHP INI Editor, set `upload_max_filesize=10M` and `post_max_size=16M` for uploads.

## 4. Verify

- Sign in at `https://portal.venkatramanabluemetals.com/admin/login`.
- Review company details, addresses and 350 TPH capacity.
- Edit a page and check Preview website.
- Submit a test enquiry through Contact us, then check Enquiries.
- Upload an image and PDF; assign them to a product and certificate.
- Check HTTPS and mobile navigation.

Enquiries are saved in the admin inbox. **Automatic email notifications are not configured.** Reply by email opens your email application. Check the inbox regularly.

## Admin features

| Menu | Features |
|---|---|
| Overview | Enquiry counts, products, page links and recent activity |
| Enquiries | Search, status, private notes, email reply link, deletion |
| Website pages | Seven pages: text, images, search title and description |
| Products | Add/edit/delete, draft/publish, image, order; updates home and catalogue |
| Certificates | Add/edit/delete, draft/publish, PDF, reference/validity details |
| Media library | Upload images and public certificate PDFs |
| Company details | Logo, email, phone, GST, addresses, partners, experience, capacity, video |
| Administrators | Create or disable administrator accounts |
| My account | Change password and invalidate other sessions |

Page text is edited as plain text while layout stays protected. Shared company fields, products and certificates have their own menus. All active administrators have full access. Uploaded media is public: upload only documents intended for visitors. Deleting a product or certificate does not delete the underlying media file.

## Updates and backups

Back up MySQL using cPanel/phpMyAdmin, plus `portal/storage/uploads` and `portal/config.php`, to a private location. These are not stored in Git. Restore all three together when moving hosts. Never put backups in the public document root.

Use cPanel Update from Remote for code updates. This layout serves the checked-out `portal/public` folder directly. No deployment file with a guessed cPanel account path is included. Preserve configuration and uploads during updates.

Text overrides currently refer to the existing template's text positions. Before restructuring HTML templates in `dist/`, back up the database and review page overrides in staging to keep them attached to the intended content.

## Local development and tests

For a local preview, use a SQLite DSN in `portal/config.php` with `https => false`, or set `VBM_TEST_DB` to a local SQLite path. Then:

```text
php portal/setup.php
php -S 127.0.0.1:4180 -t portal/public portal/public/index.php
```

Create a local admin with `portal/setup.php admin EMAIL` and the `VBM_ADMIN_PASSWORD` environment variable. The PHP development server is local-only.

HTTP integration tests start their own server and use a disposable SQLite database:

```text
py -3 portal/tests/integration.py
```

The optional MySQL smoke test creates and drops only a uniquely named `vbm_test_...` database. Run it on a development server with create-database permission:

```text
php portal/tests/mysql-smoke.php
```

Set `VBM_MYSQL_SERVER`, `VBM_TEST_USER` and `VBM_TEST_PASSWORD` in the terminal environment if needed. Never configure test environment variables in production.
