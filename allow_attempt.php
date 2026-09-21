<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(410);
exit('This legacy action has been disabled. Account security changes require authenticated support review.');
