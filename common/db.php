<?php

class db
{
	protected $user = "popcorn";
	protected $pass = "popcorn";
	protected $dbname = "popcorn";

	protected $db;

	public function __construct() {
		$this->db = new PDO('mysql:host=localhost;dbname='.$this->dbname.';charset=utf8', $this->user, $this->pass);
	}

	public function getAllUsers() {
		$st = $this->db->prepare("SELECT * FROM `users`");
		$st->execute();

		return $st->fetchAll();
	}

	public function userexists($username) {
		$st = $this->db->prepare("SELECT `email` FROM `users` WHERE LOWER(`email`)=? LIMIT 1");
		$st->execute(array(strtolower($username)));

		$res = $st->fetch();

		if($res === false) return false;

		return true;
	}

	public function getAdminUsers() {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `is_admin`=1");
		$st->execute();

		return $st->fetchAll();
	}

	public function getUserById($id) {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `id`=? LIMIT 1");
		$st->execute(array($id));

		return $st->fetch();
	}

	public function getUserByUsername($username) {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `email`=? LIMIT 1");
		$st->execute(array($username));

		return $st->fetch();
	}

	public function getUserByResetToken($token) {
		$st = $this->db->prepare("DELETE FROM `reset_tokens` WHERE `timestamp` < (NOW() - INTERVAL 24 HOUR)");
		$st->execute();

		$st = $this->db->prepare("SELECT `user_id` FROM `reset_tokens` WHERE `token`=? LIMIT 1");
		$st->execute(array($token));

		return $st->fetch();
	}

	public function storeResetToken($userId) {
		$token = uniqid();

		$st = $this->db->prepare("INSERT INTO `reset_tokens` SET `user_id`=?, `token`=?");
		$st->execute(array($userId,$token));

		return $token;
	}

	public function dropResetToken($token) {
		$st = $this->db->prepare("DELETE FROM `reset_tokens` WHERE `token`=? LIMIT 1");
		$st->execute(array($token));
	}

	public function setNewPassword($userId,$password) {
		$st = $this->db->prepare("UPDATE `users` SET `password_hash`=? WHERE `id`=? LIMIT 1");
		$st->execute(array(crypt($password),$userId));
	}

	public function addUser($username, $password) {
		$activationKey = uniqid();

		$st = $this->db->prepare("INSERT INTO `users` SET `email`=?, `password_hash`=?, `activation_hash`=?");
		$st->execute(array($username, crypt($password), $activationKey));

		return $activationKey;
	}

	public function activateUser($activationKey) {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `activation_hash`=? LIMIT 1");
		$st->execute(array($activationKey));

		$res = $st->fetch();

		if($res === false || count($res) == 0) return false;

		$st = $this->db->prepare("UPDATE `users` SET `activation_hash`=NULL WHERE `id`=? LIMIT 1");
		$foo = $st->execute(array($res['id']));

		return true;
	}

	public function verifyLogin($username, $password) {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `email`=? AND `activation_hash` IS NULL LIMIT 1");
		$st->execute(array($username));

		$user = $st->fetch();

		if($user === false) return false;

		if(crypt($password,$user['password_hash']) === $user['password_hash']) {
			return $user['id'];
		}

		return false;
	}

	public function getAllMovies() {
		$st = $this->db->prepare("SELECT * FROM `movies` ORDER BY `title` ASC");
		$st->execute();

		return $st->fetchAll();
	}

	public function getAllVotesForUser($userId) {
		$st = $this->db->prepare("SELECT * FROM `votes` WHERE `user_id`=?");
		$st->execute(array($userId));

		$res = $st->fetchAll();

		$votes = array();
		foreach($res as $row) {
			$votes[$row['movie_id']] = $row['vote'];
		}

		return $votes;
	}

	public function getAllMoviesWithStats() {
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote`, COUNT(`votes`.`vote`) AS `votecount` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` GROUP BY `movies`.`id` ORDER BY `movies`.`title` ASC;");
		$st->execute();

		return $st->fetchAll();
	}

	public function getTop10Movies() {
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote`, COUNT(`votes`.`vote`) AS `votecount` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` WHERE `active`=1 AND `vote` IS NOT NULL GROUP BY `movies`.`id` ORDER BY `vote` DESC, `votecount` ASC, `title` ASC LIMIT 10;");
		$st->execute();

		return $st->fetchAll();
	}

	public function getFlop10Movies() {
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote`, COUNT(`votes`.`vote`) AS `votecount` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` WHERE `active`=1 AND `vote` IS NOT NULL GROUP BY `movies`.`id` ORDER BY `vote` ASC, `votecount` DESC, `title` ASC LIMIT 10;");
		$st->execute();

		return $st->fetchAll();
	}

	public function updateVote($userId,$movieId,$vote) {
		if($vote > 2 || $vote < -2) return;

		if($vote == 0) {
			$st = $this->db->prepare("DELETE FROM `votes` WHERE `movie_id`=? AND `user_id`=? LIMIT 1");
			$st->execute(array($movieId,$userId));
			die("moooo");
		} else {
			$st = $this->db->prepare("INSERT INTO `votes` SET `movie_id`=?, `user_id`=?, `vote`=? ON DUPLICATE KEY UPDATE `vote`=?");
			$st->execute(array($movieId,$userId,$vote,$vote));
		}
	}

	public function isAdmin($userId) {
		$st = $this->db->prepare("SELECT * from `users` WHERE `id`=? LIMIT 1");
		$st->execute(array($userId));

		$res = $st->fetch();

		if(isset($res['is_admin']) && $res['is_admin'] == 1) return true;

		return false;
	}

	public function addMovie($title,$year,$imdb_id) {
		$st = $this->db->prepare("INSERT INTO `movies` SET `title`=?, `year`=?, `imdb_id`=?");
		$st->execute(array($title,$year,$imdb_id));
	}

	public function updateMovie($id,$title,$year,$imdb_id) {
		$st = $this->db->prepare("UPDATE `movies` SET `title`=?, `year`=?, `imdb_id`=? WHERE `id`=? LIMIT 1");
		$st->execute(array($title,$year,$imdb_id,$id));
	}

	public function deleteMovie($id) {
		$st = $this->db->prepare("DELETE FROM `movies` WHERE `id`=? LIMIT 1");
		$st->execute(array($id));
	}

	public function deactivateMovie($id) {
		$st = $this->db->prepare("UPDATE `movies` SET `active`=0 WHERE `id`=? LIMIT 1");
		$st->execute(array($id));
	}

	public function activateMovie($id) {
		$st = $this->db->prepare("UPDATE `movies` SET `active`=1 WHERE `id`=? LIMIT 1");
		$st->execute(array($id));
	}

	public function addCommitment($userId) {
		$st = $this->db->prepare("INSERT INTO `commitments` SET `user_id`=?");
		$st->execute(array($userId));
	}

	public function removeCommitment($userId) {
		$st = $this->db->prepare("DELETE FROM `commitments` WHERE `user_id`=? LIMIT 1");
		$st->execute(array($userId));
	}

	public function hasCommitment($userId) {
		$st = $this->db->prepare("SELECT `timestamp` FROM `commitments` WHERE `user_id`=? LIMIT 1");
		$st->execute(array($userId));

		$res = $st->fetch();

		return $res;
	}

	public function getAllCommitments() {
		$st = $this->db->prepare("SELECT `commitments`.`user_id` AS `user_id`, `commitments`.`timestamp` AS `timestamp`, `users`.`email` AS `email` FROM `commitments`, `users` WHERE `users`.`id`=`commitments`.`user_id` GROUP BY `commitments`.`id`");
		$st->execute();

		$res = $st->fetchAll();

		return $res;
	}

	public function getAllCommitmentsSorted() {
		$st = $this->db->prepare("SELECT `commitments`.`user_id` AS `user_id`, `commitments`.`timestamp` AS `timestamp`, `users`.`email` AS `email` FROM `commitments`, `users` WHERE `users`.`id`=`commitments`.`user_id` GROUP BY `commitments`.`id` ORDER BY `email` ASC");
		$st->execute();

		$res = $st->fetchAll();

		return $res;
	}

	public function dropAllCommitments() {
		$st = $this->db->prepare("DELETE FROM `commitments`");
		$st->execute();
	}

}
