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

	public function setNewPassword($username) {
		$st = $this->db->prepare("SELECT * FROM `users` WHERE `email`=? AND `activation_hash` IS NULL LIMIT 1");
		$st->execute(array($username));

		$res = $st->fetch();
		if($res === false || count($res) == 0) return false;

		$password = substr(uniqid(),0,8);

		$st = $this->db->prepare("UPDATE `users` SET `password`=? WHERE `email`=? LIMIT 1");
		$st->execute(array(crypt($password),$username));

		return $password;
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
		$st = $this->db->prepare("SELECT * FROM `movies` WHERE `active`=1 ORDER BY `title` ASC");
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
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` GROUP BY `movies`.`id` ORDER BY `movies`.`title` ASC;");
		$st->execute();

		return $st->fetchAll();
	}

	public function getTop10Movies() {
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` WHERE `active`=1 AND `vote` IS NOT NULL GROUP BY `movies`.`id` ORDER BY `vote` DESC, `title` ASC LIMIT 10;");
		$st->execute();

		return $st->fetchAll();
	}

	public function getFlop10Movies() {
		$st = $this->db->prepare("SELECT `movies`.`id` AS `id`, `movies`.`title` AS `title`, `movies`.`imdb_id` AS `imdb_id`, `movies`.`year` AS `year`, `movies`.`active` AS `active`, SUM(`votes`.`vote`) AS `vote` FROM `movies` LEFT JOIN `votes` ON `votes`.`movie_id`=`movies`.`id` WHERE `active`=1 AND `vote` IS NOT NULL GROUP BY `movies`.`id` ORDER BY `vote` ASC, `title` ASC LIMIT 10;");
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

}
