<?php
session_start();

require_once('../vendor/autoload.php');

$domain = $_SERVER['HTTP_HOST'];

$app = new \Slim\Slim();	
$app->config(array(
	'view' => new \Slim\Views\Twig(),
	'templates.path' => dirname(__FILE__) . '/templates/',
));

$view = $app->view();
$view->parserExtensions = array(
	new \Slim\Views\TwigExtension(),
);
$view->parserOptions = array(
	'autoescape' => true,
);

$viewData = array();

require_once(dirname(__FILE__) . '/../common/db.php');
$db = new db();

if(!isset($_SESSION['is_admin'])) {
	$app->get('/:whatever', function($whatever) use ($app) {
		$app->render('admin_login.html.twig');
	})->conditions(array('whatever' => '.*'));
	$app->post('/', function() use($app,$db) {
		if($db->verifyLogin($_POST['username'],$_POST['password']) === false) {
			$app->render('admin_login.html.twig', array('username'=>$_POST['username']));
		} else {
			$_SESSION['is_admin'] = true;
			header('location:/');
			die();
		}
	});
} else {

	$app->get('/', function() use ($app, $domain, $viewData, $db) {
		die("ADMIN");
	//	$app->render('admin_index.html.twig', $viewData);
	});

	$app->get('/logout', function() use($app) {
		unset($_SESSION['is_admin']);
		header('location:/');
		die();
	});
}
// DONE :-)
$app->run();
