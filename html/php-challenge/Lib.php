<?php

class Lib
{
	public static $pdo;
	public static $likes;
	public static $retweets;

	public static function initFields()
	{
		self::$likes = self::selectAllLikes();
		self::$retweets = self::selectAllretweets();
	}

	public static function selectAllLikes()
	{
		$sql = 'select * from likes';
		$stmt = self::$pdo->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function selectAllRetweets()
	{
		$sql = 'select * from retweets';
		$stmt = self::$pdo->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function dispButtonLikeRt()
	{
	}

	public static function handleLike()
	{
		// いいねボタンが押された投稿のid
		$likePostId = $_REQUEST['like'];
		if (!isset($likePostId)) {
			/* いいねボタンは押されていない */
			return;
		}
		/* いいねボタンが押された */
		// 自分のid
		$myId = $_SESSION['id'];

		echo 'myId : ' . $myId . '<br>';
		echo 'likedPostId : ' . $likePostId . '<br>';
	}
}
