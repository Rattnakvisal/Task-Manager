<?php

// Vercel invokes /api/index.php for rewritten URLs. Present a root front
// controller so Symfony preserves /api in Laravel routes and generated URLs.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
