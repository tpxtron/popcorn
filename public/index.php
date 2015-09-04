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
	$user = $db->getUserByUsername($_POST['username']);
	if($user === false) {
		$viewData['error'] = true;
	} else {
		$token = $db->storeResetToken($user['id']);
		$mailer->sendPasswordResetMail($user['email'],$token);
		$viewData['success'] = true;
	}
	$app->render("login_forgotpw.html.twig",$viewData);
});
$app->get('/resetpassword/:key', function($key) use ($app, $viewData, $db, $mailer) {
	$user = $db->getUserByResetToken($key);
	if($user === false) {
		$viewData['error'] = "invalidToken";
	} else {
		$viewData['key'] = $key;
	}
	$app->render("login_resetpw.html.twig",$viewData);
});
$app->post('/resetpassword', function() use ($app, $viewData, $db, $mailer) {
	$user = $db->getUserByResetToken($_POST['key']);
	$viewData['key'] = $_POST['key'];
	if($user === false) {
		$viewData['error'] = "invalidToken";
	} else {
		if($_POST['password'] != $_POST['passwordrepeat']) {
			$viewData['error'] = "passwordsDontMatch";
		}
		if(strlen($_POST['password']) < 8) {
			$viewData['error'] = "passwordTooShort";
		}
	}

	if(isset($viewData['error'])) {
		$app->render("login_resetpw.html.twig",$viewData);
	} else {
		$db->setNewPassword($user['user_id'],$_POST['password']);
		$db->dropResetToken($_POST['key']);
		$app->render("login_resetpw_success.html.twig",$viewData);
	}
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
		header("location:/dashboard");
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
	if(stristr($_POST['username'],"+") !== false) {
		$viewData['error'] = "invalidemailplus";
	}
	if(strtolower(substr($_POST['username'],-11)) != "@sipgate.de") {
		$viewData['error'] = "invalidemail";
	}
	if($db->userexists($_POST['username'])) {
		$viewData['error'] = "userexists";
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
	if(!isset($_SESSION['user'])) {
		header("location:/login");
		die();
	}
	$viewData['movies'] = $db->getAllMovies();
	$viewData['votes'] = $db->getAllVotesForUser($_SESSION['user']);
	$viewData['nextDate'] = file_get_contents(dirname(__FILE__)."/../date.txt");
	$viewData['nextMovie'] = json_decode(file_get_contents(dirname(__FILE__)."/../movie.json"));
	$viewData['top10movies'] = $db->getTop10Movies();
	$viewData['flop10movies'] = $db->getFlop10Movies();
	$viewData['commitment'] = $db->hasCommitment($_SESSION['user']);
	$viewData['voteEndDate'] = strtotime($viewData['nextDate']);
	$viewData['voteEndDate'] = $viewData['voteEndDate'] - (60 * 60 * 24 * 4);

	$app->render("vote.html.twig",$viewData);
});
$app->post('/vote', function() use($app, $viewData, $db) {
	$db->updateVote($_SESSION['user'],$_POST['movieId'],$_POST['value']);
});
$app->post('/vote/suggest', function() use($app, $viewData, $db, $mailer) {
	if(!isset($_SESSION['user'])) {
		header("location:/login");
		die();
	}
	if(trim($_POST['link']) != "") {
		$user = $db->getUserById($_SESSION['user']);
		$admins = $db->getAdminUsers();
		$mailer->sendSuggestionToAdmins($_POST['link'],$user['email'],$admins);
		$viewData['success'] = 'voteSuggest';
	} else {
		$viewData['error'] = 'voteSuggest';
	}
	$viewData['movies'] = $db->getAllMovies();
	$viewData['votes'] = $db->getAllVotesForUser($_SESSION['user']);
	$viewData['nextDate'] = file_get_contents(dirname(__FILE__)."/../date.txt");
	$viewData['nextMovie'] = json_decode(file_get_contents(dirname(__FILE__)."/../movie.json"));
	$viewData['top10movies'] = $db->getTop10Movies();
	$viewData['flop10movies'] = $db->getFlop10Movies();
	$viewData['commitment'] = $db->hasCommitment($_SESSION['user']);
	$viewData['voteEndDate'] = strtotime($viewData['nextDate']);
	$viewData['voteEndDate'] = $viewData['voteEndDate'] - (60 * 60 * 24 * 4);

	$app->render("vote.html.twig",$viewData);
});
$app->post('/vote/commitment', function() use($app, $viewData, $db) {
	if(!isset($_SESSION['user'])) {
		header("location:/login");
		die();
	}
	if($_POST['type'] == "in") {
		$db->addCommitment($_SESSION['user']);
	} else {
		$db->removeCommitment($_SESSION['user']);
	}
});
$app->get('/dashboard', function() use($app, $viewData, $db) {
	if(!isset($_SESSION['user'])) {
		header("location:/login");
		die();
	}
	$viewData['nextDate'] = file_get_contents(dirname(__FILE__)."/../date.txt");
	$viewData['nextMovie'] = json_decode(file_get_contents(dirname(__FILE__)."/../movie.json"));
	$viewData['top10movies'] = $db->getTop10Movies();
	$viewData['flop10movies'] = $db->getFlop10Movies();
	$viewData['commitment'] = $db->hasCommitment($_SESSION['user']);
	$viewData['commitments'] = $db->getAllCommitmentsSorted();
	$viewData['voteEndDate'] = strtotime($viewData['nextDate']);
	$viewData['voteEndDate'] = $viewData['voteEndDate'] - (60 * 60 * 24 * 4);
	$app->render("dashboard.html.twig",$viewData);
});

$app->get('/admin', function() use ($app, $viewData, $db) {
	if(!isset($_SESSION['is_admin'])) {
		header("location:/vote");
		die();
	}
	$viewData['movies'] = $db->getAllMoviesWithStats();
	$viewData['nextMovie'] = json_decode(file_get_contents(dirname(__FILE__)."/../movie.json"));
	$viewData['nextDate'] = file_get_contents(dirname(__FILE__)."/../date.txt");
	$app->render("admin.html.twig",$viewData);
});
$app->get('/admin/stats', function() use ($app, $viewData, $db) {
	if(!isset($_SESSION['is_admin'])) {
		header("location:/vote");
		die();
	}
	$viewData['top10movies'] = $db->getTop10Movies();
	$viewData['flop10movies'] = $db->getFlop10Movies();
	$viewData['users'] = $db->getAllUsers();
	$viewData['commitments'] = $db->getAllCommitments();
	$app->render("admin_stats.html.twig",$viewData);
});
$app->post('/admin/deletecommitments', function() use($app, $db) {
	if(!isset($_SESSION['is_admin'])) {
		header("location:/vote");
		die();
	}
	$db->dropAllCommitments();
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
		case 'deactivateMovie':
			$db->deactivateMovie($_POST['id']);
			$viewData['success'] = 'deactivateMovie';
			break;
		case 'activateMovie':
			$db->activateMovie($_POST['id']);
			$viewData['success'] = 'activateMovie';
			break;
		case 'setDate':
			file_put_contents(dirname(__FILE__)."/../date.txt",$_POST['date']);
			$viewData['success'] = 'setDate';
			break;
		case 'setNextMovie':
			$data = array(
				'title' => $_POST['title'],
				'year' => $_POST['year'],
				'imdb_id' => $_POST['imdb_id']
			);
			file_put_contents(dirname(__FILE__)."/../movie.json",json_encode($data));
			$viewData['success'] = 'setNextMovie';
			break;
	}
	$viewData['movies'] = $db->getAllMoviesWithStats();
	$viewData['nextMovie'] = json_decode(file_get_contents(dirname(__FILE__)."/../movie.json"));
	$viewData['nextDate'] = file_get_contents(dirname(__FILE__)."/../date.txt");
	$app->render("admin.html.twig",$viewData);
});

$app->get('/logout', function() use($app, $viewData, $db) {
	unset($_SESSION['user']);
	header("location:/");
	die();
});

// DONE :-)
$app->run();
