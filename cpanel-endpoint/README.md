# AIMS cPanel form endpoint

1. Create `public_html/forms` in cPanel and upload `submit.php` and `composer.json`.
2. Run `composer install --no-dev --optimize-autoloader` in that directory using cPanel Terminal.
3. Copy `config.php.example` to `config.php` and insert the mailbox password. Keep `config.php` out of source control.
   Generate the administrator password hash with `php -r "echo password_hash('YOUR-STRONG-PASSWORD', PASSWORD_DEFAULT);"` and paste it into `admin_password_hash`.
4. Confirm the mailbox supports port 465 with SSL. If cPanel specifies port 587, change the port to `587` and encryption to `tls`.
5. Test both forms from the published website.
6. Open `https://aimsnigeria.org.ng/forms/admin.php` to review applications, update statuses and export CSV records.

The public endpoint expected by the website is `https://aimsnigeria.org.ng/forms/submit.php`.
