<?php
// Local dev only: let Moodle reach the host's Ollama (private address, port 11434).
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
$ip = gethostbyname('host.docker.internal');
$blocked = array_filter(array_map('trim', explode("\n", get_config('core', 'curlsecurityblockedhosts'))),
    fn($h) => $h !== '' && !in_array($h, ['172.16.0.0/12', '192.168.0.0/16', '10.0.0.0/8', 'localhost', '127.0.0.0/8'], true));
set_config('curlsecurityblockedhosts', implode("\n", $blocked));
$ports = array_filter(array_map('trim', explode("\n", get_config('core', 'curlsecurityallowedport'))));
set_config('curlsecurityallowedport', implode("\n", array_unique(array_merge($ports, ['443', '80', '11434']))));
echo "host.docker.internal = $ip; private ranges unblocked, port 11434 allowed\n";
