<?php

/**
 *
 * @desc There is example of creating PHAR archive of DaBase, so now it can be included in your project just like: require_once('phar://'.DABASE_PHAR_FILEPATH);
 * @see http://code.google.com/p/dabase
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */

define('DABASE_DIR', dirname(dirname(__DIR__)). '/DaBase');
define('DABASE_PHAR_FILEPATH', __DIR__ . '/DaBase.phar');

if(!Phar::canWrite()) {
	throw new Exception('Unable to create PHAR archive, must be phar.readonly=Off option in php.ini');
}
if(file_exists(DABASE_PHAR_FILEPATH)) {
	unlink(DABASE_PHAR_FILEPATH);
}

$phar = new Phar(DABASE_PHAR_FILEPATH);
$phar = $phar->convertToExecutable(Phar::PHAR);
$phar->startBuffering();
$phar->buildFromDirectory(DABASE_DIR, '/\.php$/');
$phar->setStub('<?php

Phar::mapPhar("DaBase");
function autoloadDaBaseByDir($class) {
	if(strpos($class, "DaBase_") === 0) {
		require_once("phar://" . str_replace("_", DIRECTORY_SEPARATOR, $class) . ".php");
	}
}
spl_autoload_register("autoloadDaBaseByDir");
__HALT_COMPILER();

');
$phar->stopBuffering();

?>
<pre>
Done. See <?= DABASE_PHAR_FILEPATH ?>
Now you can include DaBase to your project just by:

require_once('phar://<?= DABASE_PHAR_FILEPATH ?>);