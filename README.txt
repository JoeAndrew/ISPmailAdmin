ISPmail Admin 0.9.8

Purpose
~~~~~~~
ISPmail Admin allows you to comfortably manage:

- Domains: Add and remove domains (example.com) handled by your mailserver.    
- Accounts: Add and remove the email-accounts (user1@example.com) you and your
  users access with POP3(s), IMAP(s), and SMTP.
- Aliases: Add and remove aliases to these email-accounts (info@example.com as
  an alias of user1@example.com).
- Redirects: Add and remove redirects (forward emails for
  not.an.account@example.com to somebody.else@over.there.com).

Praise, suggestions, and bug reports are all welcome at ima@jungclaussen.net
Check ima.jungclaussen.com for updates.

ISPmail Admin can be configured for one of three types of login:

- Only Admin: Only one user will use ISPmail Admin and that is the
  administrator.
- Only Admin without login: Same as above, but as you've protected ISPmail Admin
  behind a .htaccess username and password anyway, another login is not really
  needed.
- Admin and Users: Only one user will administrate ISPmail Admin but all users
  with an email@account may login and manage aliases for their accounts.

Requirements
~~~~~~~~~~~~
If you have setup your mailserver following the guide ISPmail by workaround.org
you already have at least MySql running on your server. ISPmail Admin is written
in PHP and therefore needs:

- Webserver: Any will do as long as PHP is supported. It must not even be on the
  same server as the mailserver database as long as it can access it.
- PHP: Any version above 5.0 (the mysqli extension is built in). I recommend the
  latest.
- MySql: Version 4.1.13 or newer, or 5.0.7 or newer.

Additionally you need

- Write access to the database: The ISPmail guide rightfully limited the
  database user mailuser to readonly. ISPmail Admin naturally needs write access
  to the mailserver database.
- Javascript and cookies:: The browser you intend to use ISPmail Admin with
  needs Javascript and cookies enabled.

Legal stuff
~~~~~~~~~~~
ISPmail Admin is "free" as in "Free beer":

- You can use it both privately and commercially for free.
- You could distribute it freely but I'd prefer if users would download it from
  here: ima.jungclaussen.com
- You can modify the code as you like and distribute that, too, but always leave
  the footer as it is:
  "ISPmail Admin by Ole Jungclaussen (http://ima.jungclaussen.com), version 0.9.
  Icons by Freepik from www.flaticon.com."


Config
~~~~~~
Edit "cfg/config.inc.php":
================================================================================
= /**
= ** DATABASE ACCESS
= **/
= define('IMA_CFG_DB_HOST',     '127.0.0.1');
= define('IMA_CFG_DB_PORT',     '3306');
= /// optional, can be faster
= // define('IMA_CFG_DB_SOCKET',   '/path/to/database.socket');
= define('IMA_CFG_DB_USER',     'db_user');
= define('IMA_CFG_DB_PASSWORD', 'db_pass');
= define('IMA_CFG_DB_DATABASE', 'mailserver');
= 
= /**
= ** ACCESS CONTROL
= ** uncomment the type you want to use.
= **/
= // define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ACCOUNT);  
= // define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADM);  
= // define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADMAUTO);  
= 
= /// Define the administrator's name and password.
= define('IMA_CFG_ADM_USER',  'admin_user');     // admin username
= define('IMA_CFG_ADM_PASS',  'admin_Pass');     // admin password
= 
= /**
= ** PASSWORD HASHES
= ** Enable only *one* of the following
= **/
= define('IMA_CFG_USE_BCRYPT_HASHES', true);
= // define('IMA_CFG_USE_SHA256_HASHES', true);
= // define('IMA_CFG_USE_MD5_HASHES', true);
= 
= /**
= ** QUOTAS
= **/
= /// true or false
= define('IMA_CFG_USE_QUOTAS', true);
= /// in bytes. 0 is unlimited, 1GB = 2^30 Bytes = 1073741824
= define('IMA_CFG_DEFAULT_QUOTA', 0);
= /// convenience for input field
= define('IMA_CFG_QUOTA_STEP', 1073741824);
= 
= /**
= ** GUI
= ** Spread long lists on multiple pages.
= ** Set number of maximum entries per page.
= ** Changes take effect after login/logout.
= ** If not defined, defaults to 65535.
= **/
= // define('IMA_LIST_MAX_ENTRIES', 200);
================================================================================

- Set the database access information (IMA_CFG_DB_ ...)

- Choose, which type of password hashes you want to use

- Set the ISPmail Admin access (login) type by uncommenting (remove the leading
  "//") the line with the method you want to use:
  * "define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ACCOUNT)":
    Only one user will administrate ISPmail Admin but all users with an email
    account may login (using their password) and manage aliases for their
    accounts.
  * "define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADM)":
    Only one user will use ISPmail Admin and that is the administrator.
  * "define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADMAUTO)":
    Same as above, but when you've protected ISPmail Admin behind a .htaccess
    username and password, another login is not really needed.

- Set the ISPmail Admin administrative user and his password (IMA_CFG_ADM_ ...).
  Use a strong password, yes? Please?

Version History / Changelog
~~~~~~~~~~~~~~~~~~~~~~~~~~~
0.9.8
    Fixed: Quotas not showing correctly on Overview page (always show as unlimited).
    
0.9.7
    Added support of quotas (config.inc.php) as of the latest 'Buster' guide
    (see https://workaround.org/ispmail/buster)
    On Update, make sure you have adjusted your database accordingly:
    $> ALTER TABLE `virtual_users` ADD `quota` BIGINT(11) NOT NULL DEFAULT '0' AFTER `email`;
    Added choice (config.inc.php) of password hashing method: BCRYPT, SHA-256, md5 (not recommended).
    Added database connection via socket
    
0.9.6
    Fixed a bug with not verifying domain names when using the "Enter"-key in the input field.
    New configuration option to spread long lists on multiple "pages": define("IMA_LIST_MAX_ENTRIES", 200)

0.9.5
    FIX: SQL-Error on Alias Page with Mysql 5.7 (sql_mode 'only_full_group_by')
      
0.9.4
    Changed to SHA-256 passwords introduced by https://workaround.org/ispmail.
    On Update, make sure you have adjusted your database accordingly:
    $> ALTER TABLE virtual_users MODIFY password varchar(150) NOT NULL
    $> UPDATE virtual_users SET password=CONCAT('{PLAIN-MD5}', password)
    see: https://workaround.org/ispmail/jessie/migrate-from-wheezy

0.9.3
    Added "Overview" page showing all e-mail addresses (accounts and aliases)
    Fixed bug concerning login/cookie in chromium based browsers

0.9.2
    Multiple targets for same alias/redirect
    alias@somewhere.tld => user1@somewhere.tld
    alias@somewhere.tld => user2@somewhere.tld
    alias@somewhere.tld => user3@somewhere.else.tld
      
0.9.1
    Minor Bugfixes
0.9 
    Initial Relase
