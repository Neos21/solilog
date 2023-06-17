<?php

// ======================================================================
// Solilog : 投稿する (管理者用)
// 
// ex. `solilog-admin-post.php?credential=CREDENTIAL&text=example`
// ======================================================================


// グローバル変数
// ======================================================================

// 設定ファイルのパス
$CONFIG_FILE_PATH = './solilog-config.json';


// メイン処理
// ======================================================================

date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');

// パラメータチェック・不正な場合は関数内でエラーレスポンスを出力しているので終了する
if(!isValidParameter()) { exit(); }

// 設定ファイルを読み込む・読み込めなかった場合は NULL が返る・関数内でエラーレスポンスを出力しているので終了する
$config = loadConfig();
if(empty($config)) { exit(); }

// クレデンシャルチェック・不正な場合は関数内でエラーレスポンスを出力しているので終了する
if(!isValidCredential($config['credential'])) { exit(); }

// 投稿をファイルに書き込む・レスポンスを出力して終了する
writePost($config['postDirectoryPath'], $config['postFileNamePrefix']);
exit();


// 関数
// ================================================================================


/** パラメータをチェックする */
function isValidParameter() {
  if(isEmpty(getParameter('credential'))) {
    responseError('No Credential');
    return false;
  }
  
  if(isEmpty(getParameter('text'))) {
    responseError('No Text');
    return false;
  }
  
  if(getParameter('text') === 'about:blank') {
    responseError('Invalid Text');
    return false;
  }
  
  return true;
}

/** 引数が空値かどうか判定する */
function isEmpty($value) {
  return !isset($value) || trim($value) === '';
}

/** POST か GET から指定のパラメータを取得する (POST 優先・trim 済の値を返す) */
function getParameter($parameterName) {
  // JSON 文字列を POST された場合
  $jsonValues = json_decode(file_get_contents('php://input'), true);
  $jsonValue = trim($jsonValues[$parameterName]);
  if(!isEmpty($jsonValue)) { return $jsonValue; }
  
  if(!empty($_POST[$parameterName])) {
    $postValue = trim($_POST[$parameterName]);
    if(!isEmpty($postValue)) { return $postValue; }
  }
  
  if(!empty($_GET[$parameterName])) {
    $getValue = trim($_GET[$parameterName]);
    if(!isEmpty($getValue)) { return $getValue; }
  }
  
  return '';
}

/** エラーレスポンスを返す */
function responseError($errorMessage) {
  echo json_encode(array('error' => $errorMessage));
}

/** 設定ファイルを読み込む */
function loadConfig() {
  if(!file_exists($GLOBALS['CONFIG_FILE_PATH'])) {
    responseError('Config File Does Not Exist');
    return NULL;
  }
  
  // 空ファイル・不正な形式などの場合は以下の項目チェックでエラーとなる
  $configFile = file_get_contents($GLOBALS['CONFIG_FILE_PATH']);
  $configObject = json_decode($configFile, true);
  
  // 必要な項目がなければエラーとする
  if(isEmpty($configObject['credential']) || isEmpty($configObject['postDirectoryPath']) || isEmpty($configObject['postFileNamePrefix'])) {
    responseError('Invalid Config File');
    return NULL;
  }
  
  return $configObject;
}

/** クレデンシャル情報をチェックする */
function isValidCredential($credential) {
  if(getParameter('credential') !== $credential) {
    responseError('Invalid Credential');
    return false;
  }
  // 本文チェック
  if(strpos(getParameter('text'), $credential) !== false) {
    responseError('Invalid Text');
    return false;
  }
  
  return true;
}

/** 投稿をファイルに書き込む */
function writePost($postDirectoryPath, $postFileNamePrefix) {
  // 投稿をトリム・エスケープする
  $text = htmlspecialchars(getParameter('text'), ENT_QUOTES, 'UTF-8');
  // 現在年月
  $currentYearMonth = date('Y-m');
  
  // 現在年月の投稿ファイル名を組み立てる
  $postFilePath = $postDirectoryPath . '/' . $postFileNamePrefix . $currentYearMonth . '.json';
  
  // 投稿データの配列を用意する : ファイルが存在しない場合は空配列とする
  $posts = [];
  if(file_exists($postFilePath)) {
    $postsFile = file_get_contents($postFilePath);  // ファイルを読み込む
    // 内容があれば連想配列に変換する (空ファイルなどの対策)
    if(!empty($postsFile)) {
      $posts = json_decode($postsFile, true);
    }
  }
  
  // 削除用に ID を振る
  $id = 0;
  
  // 先頭の投稿がある場合は重複投稿チェックを行う・問題なければ ID を採番する
  if(!empty($posts) && !empty($posts[0])) {
    $latestPostText = $posts[0]['text'];
    if($text === $latestPostText) {
      responseError('This Post Is Already Posted');
      return false;
    }
    $id = $posts[0]['id'] + 1;
  }
  
  // 日時を取得する
  $time = date('Y-m-d H:i:s');
  // 保存する投稿を組み立てる
  $newPost = array('id' => $id, 'time' => $time, 'text' => $text);
  // 配列の先頭に投稿を追加する
  array_unshift($posts, $newPost);
  
  // 2スペースインデントの JSON 文字列に変換する
  $newPostsText = preg_replace_callback('/^ +/m', function($spaces) {
    return str_repeat(' ', strlen($spaces[0]) / 2);
  }, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "\n";
  
  // null チェックしておく
  if(is_null($newPostsText)) {
    responseError('Failed To Make Post File. New Posts Text Is Null');
    return false;
  }
  
  // ファイルに上書き保存する (ファイルが存在しない場合は新規作成になる)
  // @ を先頭に付けるとエラーメッセージの出力を抑止できる
  //$result = @file_put_contents($postFilePath, $newPostsText);
  $result = file_put_contents($postFilePath, $newPostsText);
  if(!$result) {
    responseError('Failed To Write Post File');
    return false;
  }
  
  // 成功レスポンス
  echo json_encode(array(
    'result' => 'Success To Post',
    't' => $currentYearMonth
  ));
  return true;
}

?>
