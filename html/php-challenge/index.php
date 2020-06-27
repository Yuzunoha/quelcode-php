<?php
session_start();
require('dbconnect.php');
require_once('Lib.php');

Lib::initFields();
Lib::handleLike();
Lib::handleRetweet();

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
  // ログインしている
  $_SESSION['time'] = time();

  $members = $db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member = $members->fetch();
} else {
  // ログインしていない
  header('Location: login.php');
  exit();
}

// 自分のmember_id
$myId = intval($_SESSION['id']);

// 投稿を記録する
if (!empty($_POST)) {
  if ($_POST['message'] != '') {
    $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
    $message->execute(array(
      $member['id'],
      $_POST['message'],
      $_POST['reply_post_id']
    ));

    header('Location: index.php');
    exit();
  }
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
  $page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$sql = <<<PHP_EOL
SELECT 
	m.name, 
  m.picture,
  p.* 
FROM members m, posts p 
WHERE m.id=p.member_id 
ORDER BY p.id desc
LIMIT ?, 5
PHP_EOL;
$posts = $db->prepare($sql);
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);

// いいねrtカウント、自分がいいねrtしているかを記録する
foreach ($posts as &$postPtr) {
  $originalPostId = Lib::getOriginalPostId(intval($postPtr['id']));

  // いいね
  $likeCnt = 0;
  $amILiked = false;
  foreach (Lib::$likes as $row) {
    if (intval($row['post_id']) === $originalPostId) {
      $likeCnt++;
      if (intval($row['member_id']) === $myId) {
        /* 自分がいいねしている */
        $amILiked = true;
      }
    }
  }
  $postPtr['likeCnt'] = $likeCnt;
  $postPtr['amILiked'] = $amILiked;

  // rt
  $rtCnt = 0;
  $amIRetweeted = false;
  $postPtr['isRt'] = false;
  foreach (Lib::$retweets as $row) {
    if (intval($row['original_post_id']) === $originalPostId) {
      $rtCnt++;
      if (intval($row['member_id']) === $myId) {
        /* 自分がrtしている */
        $amIRetweeted = true;
      }
    }
    // このpostはオリジナルではなくrtであるか
    if (intval($row['retweet_post_id']) === intval($postPtr['id'])) {
      /* このpostはrtである */
      $postPtr['isRt'] = true;
    }
  }
  $postPtr['rtCnt'] = $rtCnt;
  $postPtr['amIRetweeted'] = $amIRetweeted;
}

// 返信の場合
if (isset($_REQUEST['res'])) {
  $response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
  $response->execute(array($_REQUEST['res']));

  $table = $response->fetch();
  $message = '@' . $table['name'] . ' ' . $table['message'];
}

// htmlspecialcharsのショートカット
function h($value)
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value)
{
  return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ひとこと掲示板</title>

  <link rel="stylesheet" href="style.css" />
  <style>
    table,
    th,
    td {
      border: none;
    }
  </style>
</head>

<body>
  <div id="wrap">
    <div id="head">
      <h1>ひとこと掲示板</h1>
    </div>
    <div id="content">
      <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
      <form action="" method="post">
        <dl>
          <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
          <dd>
            <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
            <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
          </dd>
        </dl>
        <div>
          <p>
            <input type="submit" value="投稿する" />
          </p>
        </div>
      </form>

      <?php
      foreach ($posts as $post) :
        $msgThisisRt = '';
        if ($post['isRt']) {
          $msgThisisRt = '<small><font color="#66CC99">retweeted </font></small>';
        }
      ?>
        <div class="msg">
          <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
          <p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）<?= $msgThisisRt ?></span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>
          <table>
            <tr>
              <td>
                <form method="POST">
                  <input type="hidden" name="like" value="<?= $post['id'] ?>">
                  <?php $srcLike = $post['amILiked'] ? 'images/likeon.png' : 'images/likeoff.png' ?>
                  <input type="image" src=<?= $srcLike ?> name="like" alt="like"></input>
                </form>
              </td>
              <td>
                <?= $post['likeCnt'] ?>
              </td>
              <td>
                <form method="POST">
                  <input type="hidden" name="rt" value="<?= $post['id'] ?>">
                  <?php $srcRt = $post['amIRetweeted'] ? 'images/rton.png' : 'images/rtoff.png' ?>
                  <input type="image" src=<?= $srcRt ?> name="like" alt="rt"></input>
                </form>
              </td>
              <td>
                <?= $post['rtCnt'] ?>
              </td>
            </tr>
          </table>
          <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
            <?php
            if ($post['reply_post_id'] > 0) :
            ?>
              <a href="view.php?id=<?php echo
                                      h($post['reply_post_id']); ?>">
                返信元のメッセージ</a>
            <?php
            endif;
            ?>
            <?php
            if ($_SESSION['id'] == $post['member_id']) :
            ?>
              [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
            <?php
            endif;
            ?>
          </p>
        </div>
      <?php
      endforeach;
      ?>

      <ul class="paging">
        <?php
        if ($page > 1) {
        ?>
          <li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
        <?php
        } else {
        ?>
          <li>前のページへ</li>
        <?php
        }
        ?>
        <?php
        if ($page < $maxPage) {
        ?>
          <li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
        <?php
        } else {
        ?>
          <li>次のページへ</li>
        <?php
        }
        ?>
      </ul>
    </div>
  </div>
</body>

</html>
