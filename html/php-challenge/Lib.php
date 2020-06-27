<?php

class Lib
{
	public static $pdo;
	public static $likes;
	public static $retweets;

	public static function initFields()
	{
		self::updateFieldLikes();
		self::updateFieldRetweets();
	}

	public static function updateFieldLikes()
	{
		self::$likes = self::selectAllLikes();
	}

	public static function updateFieldRetweets()
	{
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

	public static function deleteLike(int $memberId, int $postId)
	{
		$sql = 'delete from likes';
		$sql .= ' where (member_id = :member_id)';
		$sql .= ' and (post_id = :post_id)';
		$stmt = self::$pdo->prepare($sql);
		$stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
		$stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
		return $stmt->execute();
	}

	public static function insertLike(int $memberId, int $postId)
	{
		$sql = 'insert into likes (member_id, post_id)';
		$sql .= ' values (:member_id, :post_id)';
		$stmt = self::$pdo->prepare($sql);
		$stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
		$stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
		return $stmt->execute();
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
					$amILiked = true;
				}
			}
		}

		if ($amILiked) {
			/* 既にいいねしているので、いいねを取り消す */
			self::deleteLike($myId, $originalPostId);
		} else {
			/* まだいいねしていないので、いいねを追加する */
			self::insertLike($myId, $originalPostId);
		}

		// フィールドを更新する
		self::updateFieldLikes();
	}

	public static function handleRetweet()
	{
		if (!isset($_REQUEST['rt'])) {
			/* rtボタンは押されていない */
			return;
		}

		// rtボタンが押された投稿のidを取得する
		$retweetedPostId = intval($_REQUEST['rt']);

		// 自分のidを取得する
		$myId = intval($_SESSION['id']);

		// オリジナルポストidを取得する
		$originalPostId = self::getOriginalPostId($retweetedPostId);

		// 既にrtしているかどうか判定する
		$amIRetweeted = false;
		foreach (self::$likes as $row) {
			if (intval($row['member_id']) === $myId) {
				if (intval($row['original_post_id']) === $originalPostId) {
					$amIRetweeted = true;
					break;
				}
			}
		}

		if ($amIRetweeted) {
			/* 既にrtしているので、rtを取り消す */
		} else {
			/* まだrtしていないので、rtする */
		}

		// フィールドを更新する
		self::updateFieldRetweets();
	}
}
