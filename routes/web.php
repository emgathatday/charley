<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/web/pages.php';
require __DIR__.'/web/auth.php';
require __DIR__.'/web/dashboard/iam.php';
require __DIR__.'/web/dashboard/media-files.php';
require __DIR__.'/web/dashboard/plant-types.php';
require __DIR__.'/web/dashboard/taxonomy.php';
require __DIR__.'/web/dashboard/partner-profiles.php';
require __DIR__.'/web/dashboard/subscriptions.php';
require __DIR__.'/web/dashboard/admin-operations.php';
require __DIR__.'/web/dashboard/feed-cms.php';
require __DIR__.'/web/dashboard/library.php';
// Handbook web UI is deferred for phase 1; keep the route file intact for later reuse.
// require __DIR__.'/web/handbook.php';
