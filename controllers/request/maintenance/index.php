<?php
// Security: Prevent direct directory browsing
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
http_response_code(403);
header("Location: /cms/error/403.html");
exit;
