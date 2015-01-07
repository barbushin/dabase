<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
	<link rel="stylesheet" href="http://sindresorhus.com/github-markdown-css/github-markdown.css">
</head>
<body class="markdown-body">
<?php

require_once('config.php');

/***************************************************************
 * Init connection and base structure
 **************************************************************/

echo '<h1>Initialization</h1>';
echo 'Connect to database.<br>';

$class = DB_CONNECTION_CLASS;
/** @var DaBase\Connection $db */
if(strpos($class, 'PDO')) {
	$db = new $class(DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PERSISTENT);
}
else {
	$db = new $class(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PERSISTENT);
}
$db->setCache(new DaBase\Cache());

echo 'Init tables with some data.<br>';
foreach(explode(';', file_get_contents('init_' . DB_TYPE . '.sql')) as $sql) {
	if(trim($sql, " \n\r")) {
		$db->exec($sql);
	}
}

// debug
function __debugCode($line, $result = null) {
	static $isStart;
	static $startLine;
	$isStart = !$isStart;

	if($isStart) {
		$startLine = $line;
		echo "<pre class=\"code\" id=$line></pre>";
	}
	else {
		echo '<script>document.getElementById(' . $startLine . ').innerHTML = ' . json_encode(str_replace(array("\n", "\r", '<br /><br />', '&lt;?php<br />'), array('', '', '<br/>', ''), highlight_string("<?php\n". implode("\n", array_slice(file(__FILE__), $startLine, $line - $startLine - 1)), true))) . ';</script>';
		if($result) {
			echo '<pre class="code">' . print_r($result, true) . '</pre><br/>';
		}
	}
}

function debugSql($sql) {
	echo '<blockquote>' . $sql . '</blockquote>';
}

$db->setDebugCallback('debugSql');

/***************************************************************
 * Configure DaBase\Router
 **************************************************************/

$router = new DaBase\Router();
$router->baseCollectionsClass = 'DaBase\Collection';
$router->baseObjectsClass = 'DaBase\Object';
$router->ruleCollectionClass = 'ucAllWords|Collection';
$router->ruleObjectsClass = 'manyToOne|ucAllWords|Object';
$router->ruleTableName = 'dabase_|lcAllWords';
$router->ruleJoinFieldName = 'manyToOne|ucNotFirstWords|Id';
$db->setRouter($router);

/***************************************************************
 * DaBase\Connection query preparing and fetching
 **************************************************************/

echo '<h1>DaBase\Connection query preparing and fetching</h1>';

echo '<h2>Query preparing</h2>';

__debugCode(__LINE__);
$isActive = true;
$sets = array('isModerator' => true, 'isRoot' => false);
$usersIds = array(1, 2, 3);
$db->query('UPDATE dabase_users SET ,= WHERE id IN (,?) AND @ = ?', $sets, $usersIds, 'isActive', $isActive);
__debugCode(__LINE__);

echo '<h2>Query fetching</h2>';

__debugCode(__LINE__);
$rows = $db->fetch('SELECT * FROM dabase_users LIMIT 2'); // to get result as array of all rows and columns
__debugCode(__LINE__, $rows);

__debugCode(__LINE__);
$row = $db->fetchRow('SELECT * FROM dabase_users LIMIT 1'); // to get associative array of first row
__debugCode(__LINE__, $row);

__debugCode(__LINE__);
$column = $db->fetchColumn('SELECT id FROM dabase_users'); // to get array of first column of all rows
__debugCode(__LINE__, $column);

__debugCode(__LINE__);
$cell = $db->fetchCell('SELECT COUNT(*) FROM dabase_users'); // to get array of first column of all rows
__debugCode(__LINE__, $cell);

/***************************************************************
 * DaBase\Collection basic features
 **************************************************************/

echo '<h1>DaBase\Collection basic features</h1>';
echo '<h2>Selectors</h2>';

__debugCode(__LINE__);
$users = $db->users->get(); // get array of all rows like objects
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$users = $db->users->getByQuery('SELECT * FROM @ WHERE @ = ? LIMIT 2', $db->users->getTable(), 'isActive', $isActive); // get array of all rows like objects
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$usersIds = $db->users->getColumn('login'); // get associative array of id => property
__debugCode(__LINE__, $usersIds);

__debugCode(__LINE__);
$usersCount = $db->users->count(); // get count of all rows in table users
__debugCode(__LINE__, $usersCount);

__debugCode(__LINE__);
$user = $db->users->getObjectById(3); // get one object by id
__debugCode(__LINE__, $user);

__debugCode(__LINE__);
$user = $db->users(3); // get one object by id
__debugCode(__LINE__, $user);

echo '<h2>Filters</h2>';

__debugCode(__LINE__);
$users = $db->users->isActive(true)->isModerator(true)->posts(50, '>')->get(); // get users with isActive='1' AND is_moderator='1' AND posts > '50'
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$users = $db->users->isActive(true)->{' AND ('}->isRoot(true)->OR->isModerator(true)->{')'}->get(); // get users with isActive='1' AND is_moderator='1' AND posts > '50'
__debugCode(__LINE__, $users);

echo '<h2>Orders and limits</h2>';

__debugCode(__LINE__);
$users = $db->users->orderBy('posts', true)->orderBy('login')->get(); // get all users with ORDER BY posts DESC, login
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$users = $db->users->orderBy('id')->limit(5, 20)->get(); // get users with ORDER BY id LIMIT 5, 20
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$users = $db->users->limitPage(10, 3)->get(); // get users with LIMIT 10, 20
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$users = $db->users->orderByRand()->limit(5)->get(); // get 5 random users
__debugCode(__LINE__, $users);

__debugCode(__LINE__);
$user = $db->users->limit(1)->get(true); // ...->get(true) means getting just on object (not array of objects)
__debugCode(__LINE__, $user);

echo '<h2>Update</h2>';

__debugCode(__LINE__);
$db->users->posts(50, '>')->update(array('isModerator' => true)); // UPDATE users SET isModerator='1' WHERE posts<'50' LIMIT 5
__debugCode(__LINE__);

echo '<h2>Delete</h2>';

__debugCode(__LINE__);
$db->users->login('sergey')->delete(); // DELETE FROM users WHERE login='sergey'
__debugCode(__LINE__);

/***************************************************************
 * DaBase\Collection appenders(pseudo-JOINS)
 **************************************************************/

echo '<h1>DaBase\Collection appenders(pseudo-JOINS)</h1>';

__debugCode(__LINE__);
$users = $db->users->orderBy('login')->limit(2)->append(
	$db->videos->orderBy('id'))->append(
	$db->photos->orderBy('id')->append(
		$db->photosComments)
)->get();
__debugCode(__LINE__, $users);

echo '<h2>Appending by custom properties names</h2>';

__debugCode(__LINE__);
$users = $db->users->append(
	$db->videos->orderByRand(), 'randomVideos', 'userId'
)->get();
__debugCode(__LINE__, $users);

/***************************************************************
 * Custom collections extended from DaBase\Collection
 **************************************************************/

echo '<h2>Custom collections extended from DaBase\Collection</h2>';

__debugCode(__LINE__);
$users = $db->rootUsers->get();
__debugCode(__LINE__, $users);

/***************************************************************
 * Database objects definition by class models with autovalidation
 **************************************************************/

echo '<h1>Database objects definition by class models with autovalidation (DaBase\Object, Validator)</h1>';

echo '<h2>Insert</h2>';

__debugCode(__LINE__);
$user = $db->users->getNew(); // get clear object
$user->login = 'johny';
$user->password = md5('jdskalkjaslkd');
$db->users->insertObject($user);
__debugCode(__LINE__);

echo '<h2>Update</h2>';

__debugCode(__LINE__);
$user = $db->users->login('johny')->get(true);
$user->isRoot = true;
$user->isModerator = true;
$db->users->updateObject($user);
__debugCode(__LINE__);

echo '<h2>Delete</h2>';

__debugCode(__LINE__);
$user = $db->users->login('johny')->get(true);
$db->users->deleteObject($user);
__debugCode(__LINE__);

echo '<h1>Data models based on DaBase\Object</h1>';

echo '<pre class="code">' . file_get_contents(__DIR__ . '/UserObject.php') . '</pre>';

echo '<h2>DaBase\Object validation</h2>';

__debugCode(__LINE__);
try {
	$user = $db->users->getNew();
	$user->login = 'mike';
	$db->users->insertObject($user);
}
catch(DaBase\Validator_Exception $e) {
	// print_r($e->getErrors());
}
__debugCode(__LINE__, $e->getErrors());

echo '<h2>DaBase\Object custom validation without exception throwing</h2>';

__debugCode(__LINE__);
$user = $db->users->getNew();
$user->login = 'me';
if(!$user->validate('login,password', false)) {
	// print_r($user->getValidationErrors());
}
__debugCode(__LINE__, $user->getValidationErrors());

/***************************************************************
 * DaBase\Tree\Collection features
 **************************************************************/

echo '<h1>DaBase\Tree\Collection features</h1>';

echo '<h2>Create root node</h2>';

__debugCode(__LINE__);
$rootNode = $db->directoriesTree->getNew(array('name' => '/'));
$db->directoriesTree->addRootNode($rootNode);
__debugCode(__LINE__, $rootNode);

echo '<h2>Add child nodes</h2>';

__debugCode(__LINE__);
$usrNode = $db->directoriesTree->getNew(array('name' => '/usr'));
$db->directoriesTree->addNode($usrNode, $rootNode->id);

$logNode = $db->directoriesTree->getNew(array('name' => '/log'));
$db->directoriesTree->addNode($logNode, $rootNode->id);

$logSysNode = $db->directoriesTree->getNew(array('name' => '/log/sys'));
$db->directoriesTree->addNode($logSysNode, $logNode->id);

$etcNode = $db->directoriesTree->getNew(array('name' => '/etc'));
$db->directoriesTree->addNode($etcNode, $rootNode->id);

$etcTmpNode = $db->directoriesTree->getNew(array('name' => '/etc/tmp'));
$db->directoriesTree->addNode($etcTmpNode, $etcNode->id);
__debugCode(__LINE__);

echo '<h2>Get child nodes</h2>';

__debugCode(__LINE__);
$result = $db->directoriesTree->getTree();
__debugCode(__LINE__, $result);

__debugCode(__LINE__);
$result = $db->directoriesTree->getSubTree();
__debugCode(__LINE__, $result);

__debugCode(__LINE__);
$result = $db->directoriesTree->getSubTree(null, 1);
__debugCode(__LINE__, $result);

__debugCode(__LINE__);
$result = $db->directoriesTree->getNodes($etcNode->id);
__debugCode(__LINE__, $result);

echo '<h2>Move /log node to /usr</h2>';

__debugCode(__LINE__);
$db->directoriesTree->moveNode($logNode->id, $usrNode->id);
__debugCode(__LINE__);

echo '<h2>Delete /etc node with childs</h2>';

__debugCode(__LINE__);
$db->directoriesTree->deleteNode($etcNode->id);
__debugCode(__LINE__);

echo '<h2>Get all nodes</h2>';

__debugCode(__LINE__);
$result = $db->directoriesTree->getTree();
__debugCode(__LINE__, $result);

?>

</body>
</html>
