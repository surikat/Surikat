<?php
use Surikat\Component\Git\GitDeploy\GitDeploy;

set_time_limit(0);
$this->Http_Request()->nocacheHeaders();
ob_implicit_flush(true);
@ob_end_flush();

echo '<pre>';
GitDeploy::factory()
	->maintenanceOn()
	->deploy(SURIKAT_PATH)
	->maintenanceOff()
;
echo '</pre>';