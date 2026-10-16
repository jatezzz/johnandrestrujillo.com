<?php

// Copy to config/local.php (ignored by git), or point PORTFOLIO_CONFIG at a file
// outside the web root. Every key is optional; defaults are in src/Config.php.
return [
    'env' => 'production',              // production | staging | development
    'base_url' => 'https://johnandrestrujillo.com',
    'noindex' => false,                 // true on staging: robots.txt and X-Robots-Tag close the site
    'mail_mode' => 'mail',              // mail (PHP mail()) | log (var_dir/mail.log) | off
    'contact_to' => '',                 // inbox that receives the contact form
    'contact_from' => '',               // a mailbox on the site's own domain
    'form_secret' => '',                // php -r 'echo bin2hex(random_bytes(32)), "\n";'
    'var_dir' => '',                    // writable and outside the web root (default: <root>/var)
    'linkedin_url' => '',               // shown in Contact when set
    'source_url' => '',                 // link to this repository in the footer when set
];
