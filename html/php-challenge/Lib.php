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
		self::initFields();
		print_r(self::$likes);
	}
}
