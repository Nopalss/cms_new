<?php
// Security: Prevent direct directory browsing
http_response_code(403);
header("Location: /cms/error/403.html");
exit;
