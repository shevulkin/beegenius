<?php
// robots.txt — генерується динамічно, щоб посилання на sitemap завжди
// вказувало на правильний домен/шлях (локально й на проді).
header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Disallow: /admin\n";
echo "Disallow: /login\n";
echo "Disallow: /dev-login\n";
echo "Disallow: /auth/\n";
echo "Allow: /\n";
echo "\n";
echo 'Sitemap: ' . site_url('/sitemap.xml') . "\n";
