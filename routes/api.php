<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/api/v1/auth.php';
require __DIR__.'/api/v1/profiles.php';
require __DIR__.'/api/v1/connections.php';
require __DIR__.'/api/v1/feed-cms.php';
require __DIR__.'/api/v1/media-files.php';
require __DIR__.'/api/v1/plant-types.php';
require __DIR__.'/api/v1/taxonomy.php';
require __DIR__.'/api/v1/subscriptions.php';
require __DIR__.'/api/v1/admin-operations.php';
require __DIR__.'/api/v1/partner-profiles.php';
require __DIR__.'/api/v1/library.php';
// Phase 1 keeps Handbook deferred; leave route file intact for phase 2 reuse.
// require __DIR__.'/api/v1/handbook.php';
