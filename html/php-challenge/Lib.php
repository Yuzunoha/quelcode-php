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

	/**
	 * オリジナルポストidを取得する
	 * 引数がオリジナルポストidだったなら同じ値を返却する
	 */
	public static function getOriginalPostId(int $postId): int
	{
		foreach (self::$retweets as $row) {
			if (intval($row['retweet_post_id']) === $postId) {
				return intval($row['original_post_id']);
			}
		}
		return $postId;
	}

	public static function handleLike()
	{
		if (!isset($_REQUEST['like'])) {
			/* いいねボタンは押されていない */
			return;
		}

		// いいねボタンが押された投稿のidを取得する
		$likePostId = intval($_REQUEST['like']);

		// 自分のidを取得する
		$myId = intval($_SESSION['id']);

		// オリジナルポストidを取得する
		$originalPostId = self::getOriginalPostId($likePostId);

		// 既にいいねしているかどうか判定する
		$amILiked = false;
		foreach (self::$likes as $row) {
			if (intval($row['member_id']) === $myId) {
				if (intval($row['post_id']) === $originalPostId) {
					$amILiked === true;
				}
			}
		}

		if ($amILiked) {
			/* 既にいいねしているので、いいねを取り消す */
		} else {
			/* まだいいねしていないので、いいねを追加する */
		}
	}
}
