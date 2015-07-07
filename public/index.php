<?php
require_once('../vendor/autoload.php');

session_start();

$domain = $_SERVER['HTTP_HOST'];

$app = new \Slim\Slim();	
$app->config(array(
	'view' => new \Slim\Views\Twig(),
	'templates.path' => dirname(__FILE__) . '/../common/templates/',
));

$view = $app->view();
$view->parserExtensions = array(
	new \Slim\Views\TwigExtension(),
);
$view->parserOptions = array(
	'autoescape' => true,
);

$viewData = array();

if(isset($_SESSION['user'])) {
	$viewData['loggedin'] = true;
	if(isset($_SESSION['is_admin'])) {
		$viewData['is_admin'] = true;
	}
} else {
	$viewData['loggedin'] = false;
}

require_once(dirname(__FILE__) . '/../common/db.php');
$db = new db();
require_once(dirname(__FILE__) . '/../common/mail.php');
$mailer = new mailer();

$app->get('/', function() use ($app, $viewData, $db) {
	$app->render("index.html.twig",$viewData);
});
$app->get('/login', function() use ($app, $viewData, $db) {
	$app->render("login.html.twig",$viewData);
});
$app->get('/login/forgotpw', function() use ($app, $viewData, $db) {
	$app->render("login_forgotpw.html.twig",$viewData);
});
$app->post('/login/forgotpw', function() use ($app, $viewData, $db, $mailer) {
	$newpw = $db->setNewPassword($_POST['username']);
	if($newpw !== false) {
		$mailer->sendNewPasswordMail($_POST['username'],$newpw);
	}
	$viewData['success'] = true;
	$app->render("login_forgotpw.html.twig",$viewData);
});

$app->post('/login', function() use ($app, $viewData, $db) {
	$res = $db->verifyLogin($_POST['username'], $_POST['password']);

	if($res === false) {
		$viewData['error'] = true;
		$viewData['username'] = $_POST['username'];
		$app->render("login.html.twig",$viewData);
	} else {
		$_SESSION['user'] = $res;
		if($db->isAdmin($res)) {
			$_SESSION['is_admin'] = true;
		}
		header("location:/vote");
		die();
	}
});
$app->get('/signup', function() use ($app, $viewData, $db) {
	$app->render("signup.html.twig",$viewData);
});
$app->post('/signup', function() use ($app, $viewData, $db, $mailer) {
	$viewData['username'] = $_POST['username'];
	if($_POST['password'] != $_POST['password_repeat']) {
		$viewData['error'] = "pwdontmatch";
	}
	if(strlen(trim($_POST['password'])) < 8) {
		$viewData['error'] = "pwtooshort";
	}
	if(strtolower(substr($_POST['username'],-11)) != "@sipgate.de") {
		$viewData['error'] = "invalidemail";
	}
	if(!isset($viewData['error'])) {
		$key = $db->addUser($_POST['username'],$_POST['password']);
		if($key !== false) {
			$viewData['success'] = true;
			$mailer->sendActivationMail($_POST['username'], $key);
		} else {
			$viewData['error'] = "userexists";
		}
	}
	$app->render("signup.html.twig",$viewData);
});
$app->get('/signup/:key', function($key) use($app, $viewData, $db) {
	$viewData['success'] = $db->activateUser($key);

	$app->render("signup_activate.html.twig",$viewData);
});
$app->get('/vote', function() use($app, $viewData, $db) {
	$viewData['movies'] = $db->getAllMovies();
	$viewData['votes'] = $db->getAllVotesForUser($_SESSION['user']);
	$app->render("vote.html.twig",$viewData);
});
$app->post('/vote', function() use($app, $viewData, $db) {
	$db->updateVote($_SESSION['user'],$_POST['movieId'],$_POST['value']);
});
$app->get('/admin', function() use ($app, $viewData, $db) {
	if(!isset($_SESSION['is_admin'])) {
		header("location:/vote");
		die();
	}
	$viewData['movies'] = $db->getAllMoviesWithStats();
	$app->render("admin.html.twig",$viewData);
});
$app->get('/admin/stats', function() use ($app, $viewData, $db) {
	if(!isset($_SESSION['is_admin'])) {
		header("location:/vote");
		die();
	}
	$viewData['top10movies'] = $db->getTop10Movies();
	$viewData['flop10movies'] = $db->getFlop10Movies();
	$app->render("admin_stats.html.twig",$viewData);
});

$app->post('/admin', function() use ($app, $viewData, $db) {
	if(!isset($_SESSION['is_admin'])) {
		die();
	}
	switch($_POST['action']) {
		case 'addMovie':
			$db->addMovie($_POST['title'],$_POST['year'],$_POST['imdb_id']);
			$viewData['success'] = 'addMovie';
			break;
		case 'updateMovie':
			$db->updateMovie($_POST['id'],$_POST['title'],$_POST['year'],$_POST['imdb_id']);
			$viewData['success'] = 'updateMovie';
			break;
		case 'deleteMovie':
			$db->deleteMovie($_POST['id']);
			$viewData['success'] = 'deleteMovie';
			break;
	}
	$viewData['movies'] = $db->getAllMoviesWithStats();
	$app->render("admin.html.twig",$viewData);
});

$app->get('/logout', function() use($app, $viewData, $db) {
	unset($_SESSION['user']);
	header("location:/");
	die();
});

// DONE :-)
$app->run();
