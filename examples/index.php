<?php

require_once('config.php');


/***************************************************************
  Init connection and base structure
 **************************************************************/

echo '<pre><h1>Initialization</h1>';
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
foreach(explode(';', file_get_contents('init_'.DB_TYPE.'.sql')) as $sql) {
	if(trim($sql, " \n\r")) {
		$db->exec($sql);
	}
}

// debug
function debugSql($sql, $milisec) {
	echo '<strong style="color: blue">' . $sql . '</strong><br>';
}
$db->setDebugCallback('debugSql');

/***************************************************************
  Configure DaBase\Router
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
  DaBase\Connection query preparing and fetching
 **************************************************************/

echo '<h1>DaBase\Connection query preparing and fetching</h1>';

?>
<h2>Query preparing</h2>
$isActive = true;
$sets = array('isModerator' => true, 'isRoot' => false);
$usersIds = array(1, 2, 3);

$result = $db->query('UPDATE users SET ,= WHERE id IN (,?) AND @ = ?', $sets, $usersIds, 'isActive', $isActive);
<?php
$isActive = true;
$sets = array('isModerator' => true, 'isRoot' => false);
$usersIds = array(1, 2, 3);

$result = $db->query('UPDATE dabase_users SET ,= WHERE id IN (,?) AND @ = ?', $sets, $usersIds, 'isActive', $isActive);
?>
<h2>Query fetching</h2>
$rows = $db->fetch('SELECT * FROM users LIMIT 2'); // to get result as array of all rows and columns
print_r($rows);

<?php
$rows = $db->fetch('SELECT * FROM dabase_users LIMIT 2'); // to get result as array of all rows and columns
print_r($rows);
?>

$row = $db->fetchRow('SELECT * FROM users LIMIT 1'); // to get associative array of first row
print_r($row);

<?php
$row = $db->fetchRow('SELECT * FROM dabase_users LIMIT 1'); // to get associative array of first row
print_r($row);
?>

$column = $db->fetchColumn('SELECT id FROM users'); // to get array of first column of all rows
print_r($column);

<?php
$column = $db->fetchColumn('SELECT id FROM dabase_users'); // to get array of first column of all rows
print_r($column);
?>

$cell = $db->fetchCell('SELECT COUNT(*) FROM users'); // to get array of first column of all rows
print_r($cell);

<?php
$cell = $db->fetchCell('SELECT COUNT(*) FROM dabase_users'); // to get array of first column of all rows
print_r($cell);

/***************************************************************
  DaBase\Collection basic features
 **************************************************************/

echo '<h1>DaBase\Collection basic features</h1>';
echo '<h2>Selectors</h2>';

echo '$db->users->get() <br>';
$users = $db->users->get(); // get array of all rows like objects
print_r($users);

echo '<br>';
echo '$db->users->getByQuery(...) <br>';
$users = $db->users->getByQuery('SELECT * FROM @ WHERE @ = ? LIMIT 2', $db->users->getTable(), 'isActive', $isActive); // get array of all rows like objects
print_r($users);

echo '<br>';
echo '$db->users->getColumn(\'login\') <br>';
$usersIds = $db->users->getColumn('login'); // get associative array of id => property
print_r($usersIds);

echo '<br>';
echo '$db->users->count() <br>';
$usersCount = $db->users->count(); // get count of all rows in table users
print_r($usersCount);

echo '<br><br>';
echo '$db->users->getObjectById(3) <br>';
$user = $db->users->getObjectById(3); // get one object by id
print_r($user);

echo '<br>';
echo '$db->users(3) <br>';
$user = $db->users(3); // get one object by id
print_r($user);

echo '<h2>Filters</h2>';

echo '$db->users->isActive(true)->isModerator(true)->posts(50, \'>\')->get() <br>';
$users = $db->users->isActive(true)->isModerator(true)->posts(50, '>')->get(); // get users with isActive='1' AND is_moderator='1' AND posts > '50'
print_r($users);

echo '$db->users->isActive(true)->{\' AND (\'}->isRoot(true)->OR->isModerator(true)->{\')\'}->get() <br>';
$users = $db->users->isActive(true)->{' AND ('}->isRoot(true)->OR->isModerator(true)->{')'}->get(); // get users with isActive='1' AND is_moderator='1' AND posts > '50'
print_r($users);

echo '<h2>Orders and limits</h2>';

echo '$db->users->orderBy(\'posts\', true)->orderBy(\'login\')->get()<br>';
$users = $db->users->orderBy('posts', true)->orderBy('login')->get(); // get all users with ORDER BY posts DESC, login


echo '<br>$db->users->orderBy(\'id\')->limit(5, 20)->get()<br>';
$users = $db->users->orderBy('id')->limit(5, 20)->get(); // get users with ORDER BY id LIMIT 5, 20


echo '<br>$db->users->limitPage(10, 3)->get()<br>';
$users = $db->users->limitPage(10, 3)->get(); // get users with LIMIT 10, 20


echo '<br>$db->users->orderByRand()->limit(5)->get()<br>';
$users = $db->users->orderByRand()->limit(5)->get(); // get 5 random users


echo '<br>$db->users->limit(1)->get(true)<br>';
$user = $db->users->limit(1)->get(true); // ...->get(true) means getting just on object (not array of objects)


echo '<h2>Update</h2>';
echo '$db->users->posts(50, \'>\')->limit(5)->update(array(\'isModerator\' => true))<br>';
$db->users->posts(50, '>')->update(array('isModerator' => true)); // UPDATE users SET isModerator='1' WHERE posts<'50' LIMIT 5


echo '<h2>Delete</h2>';
echo '$db->users->login(\'sergey\')->delete()<br>';
$db->users->login('sergey')->delete(); // DELETE FROM users WHERE login='sergey'


/***************************************************************
  DaBase\Collection appenders(pseudo-JOINS)
 **************************************************************/

echo '<h1>DaBase\Collection appenders(pseudo-JOINS)</h1>';

echo '$db->users->orderBy(\'login\')->limit(2)
->append($db->videos->orderBy(\'id\'))
->append($db->photos->orderBy(\'id\')
  ->append($db->photosComments))
->get()<br>';
$users = $db->users->orderBy('login')->limit(2)->append($db->videos->orderBy('id'))->append($db->photos->orderBy('id')->append($db->photosComments))->get();
print_r($users);

echo '<h2>Appending by custom properties names</h2>';

echo '$db->users
->append($db->videos->orderByRand(), \'randomVideos\', \'userId\')
->get()<br>';
$users = $db->users->append($db->videos->orderByRand(), 'randomVideos', 'userId')->get();
print_r($users);

/***************************************************************
  Custom collections extended from DaBase\Collection
 **************************************************************/

echo '<h2>Custom collections extended from DaBase\Collection</h2>';

echo '$db->rootUsers->get()<br>';
$users = $db->rootUsers->get();
print_r($users);

/***************************************************************
  Database objects definition by class models with autovalidation
 **************************************************************/

echo '<h1>Database objects definition by class models with autovalidation (DaBase\Object, Validator)</h1>';

echo '<h2>Insert</h2>';
?>
$user = $db->users->getNew(); // get clear object
$user->login = 'johny';
$user->password = md5('jdskalkjaslkd');
$db->users->insertObject($user);
<?php
$user = $db->users->getNew(); // get clear object
$user->login = 'johny';
$user->password = md5('jdskalkjaslkd');
$db->users->insertObject($user);
print_r($user);

echo '<h2>Update</h2>';
?>
$user = $db->users->login('johny')->get(true);
$user->isRoot = true;
$user->isModerator = true;
$db->users->updateObject($user);
<?php
$user = $db->users->login('johny')->get(true);
$user->isRoot = true;
$user->isModerator = true;
$db->users->updateObject($user);
print_r($user);

echo '<h2>Delete</h2>';
?>
$user = $db->users->login('johny')->get(true);
$db->users->deleteObject($user);
<?php
$user = $db->users->login('johny')->get(true);
$db->users->deleteObject($user);
print_r($user);

echo '<h1>Data models based on DaBase\Object</h1>';

?>
class UserObject extends DaBase\Object {

	public $login;
	public $password;
	public $posts;
	public $isModerator;
	public $isRoot;
	public $isActive;

	protected function initValidator() {
		$validator = new \DaBase\Validator();

		$validator->add('login', array(
		new DaBase\Valid\Required(),
		new DaBase\Valid\Length(3, 20),
		new DaBase\Valid\Regexp('/^[a-z\d]*$/ui')));

		$validator->add('password', array(
		new DaBase\Valid\Required(),
		new DaBase\Valid\Regexp('/^[a-z\d]*$/ui'),
		new DaBase\Valid\Length(6, 50)));

		return $validator;
	}
}
<?php

echo '<h2>DaBase\Object validation</h2>';
?>
try {
  $db->users->getNew()->login('mike')->insert();
}
catch(DaBase\Validator\Exception $e) {
  print_r($e->getErrors());
}
<?php

try {
	$user = $db->users->getNew();
	$user->login = 'mike';
	$db->users->insertObject($user);
}
catch(DaBase\Validator_Exception $e) {
	print_r($e->getErrors());
}

echo '<h2>DaBase\Object custom validation without exception throwing</h2>';
?>
$user = $db->users->getNew();
$user->login = 'me';
if(!$user->validate('login,password', false)) {
  print_r($user->getValidationErrors());
}
<?php
$user = $db->users->getNew();
$user->login = 'me';
if(!$user->validate('login,password', false)) {
	print_r($user->getValidationErrors());
}

/***************************************************************
  DaBase\Tree\Collection features
 **************************************************************/

echo '<h1>DaBase\Tree\Collection features</h1>';

echo '<h2>Create root node</h2>';
?>
$rootNode = $db->directoriesTree->getNew(array('name' => '/'));
$db->directoriesTree->addRootNode($rootNode);

<?php
$rootNode = $db->directoriesTree->getNew(array('name' => '/'));
$db->directoriesTree->addRootNode($rootNode);

echo '<h2>Add child nodes</h2>';
?>
$usrNode = $db->directoriesTree->getNew(array('name' => '/usr'));
$db->directoriesTree->addNode($usrNode, $rootNode->id);

$logNode = $db->directoriesTree->getNew(array('name' => '/log'));
$db->directoriesTree->addNode($logNode, $logNode->id);

$etcNode = $db->directoriesTree->getNew(array('name' => '/etc'));
$db->directoriesTree->addNode($etcNode, $rootNode->id);

$etcTmpNode = $db->directoriesTree->getNew(array('name' => '/etc/tmp'));
$db->directoriesTree->addNode($etcTmpNode, $etcNode->id);

<?php
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

echo '<h2>Get child nodes</h2>';
?>
print_r($db->directoriesTree->getTree());
print_r($db->directoriesTree->getSubTree());
print_r($db->directoriesTree->getSubTree(null, 1));
print_r($db->directoriesTree->getNodes($etcNode->id));

<?php

print_r($db->directoriesTree->getTree());
print_r($db->directoriesTree->getSubTree());
print_r($db->directoriesTree->getSubTree(null, 1));
print_r($db->directoriesTree->getNodes($etcNode->id));

echo '<h2>Move /log node to /usr</h2>';

?>
$db->directoriesTree->moveNode($logNode->id, $usrNode->id);

<?php

$db->directoriesTree->moveNode($logNode->id, $usrNode->id);

echo '<h2>Delete /etc node with childs</h2>';
?>
$db->directoriesTree->deleteNode($etcNode->id);

<?php
$db->directoriesTree->deleteNode($etcNode->id);

echo '<h2>Get all nodes</h2>';
?>
print_r($db->directoriesTree->getTree());

<?php
print_r($db->directoriesTree->getTree());

?>
