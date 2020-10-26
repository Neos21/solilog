<?php

// ======================================================================
// Solilog : 投稿を削除する (管理者用)
// 
// ex. `solilog-admin-remove.php?credential=CREDENTIAL&t=2020-01&id=1`
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

// 投稿を削除する・レスポンスを出力して終了する
removePost($config['postDirectoryPath'], $config['postFileNamePrefix'], getParameter('t'), getParameter('id'));
exit();


// 関数
// ================================================================================


/** パラメータをチェックする */
function isValidParameter() {
  if(isEmpty(getParameter('credential'))) {
    responseError('No Credential');
    return false;
  }
  
  $t = getParameter('t');
  if(isEmpty($t)) {
    responseError('No T');
    return false;
  }
  else if(!preg_match('/^2[0-9]{3}-[0-1][0-9]$/u', $t)) {
    responseError('Invalid T');
    return false;
  }
  
  $id = getParameter('id');
  if(isEmpty($id)) {
    responseError('No ID');
    return false;
  }
  else if(!is_numeric($id)) {
    responseError('Invalid ID');
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
  if(isEmpty($configObject['credential']) || isEmpty($configObject['postDirectoryPath'])) {
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
  
  return true;
}

/** 投稿を削除する*/
function removePost($postDirectoryPath, $postFileNamePrefix, $t, $id) {
  // 投稿ファイル名を組み立てる
  $postFilePath = $postDirectoryPath . '/' . $postFileNamePrefix . $t . '.json';
  if(!file_exists($postFilePath)) {
    responseError('That File Not Found');
    return false;
  }
  
  // ファイルを読み込む
  $postsFile = file_get_contents($postFilePath);
  $posts = json_decode($postsFile, true);
  
  // 配列 $posts の各連想配列が持つ 'id' プロパティの値で検索する
  // array_column($posts, 'id') を使いたかったが PHP v5.5 以降の機能なので代替手段で行う
  $postIds = array_map(function($post) {
    return $post['id'];
  }, $posts);
  $targetPostIndex = array_search($id, $postIds);
  if($targetPostIndex === false) {  // 0 が Falsy なので厳密に型判定する
    responseError('The ID Does Not Exist');
    return;
  }
  
  $removedPost = $posts[$targetPostIndex];
  
  // 指定の要素を削除する
  array_splice($posts, $targetPostIndex, 1);
  
  // 2スペースインデントの JSON 文字列に変換する
  $newPostsText = preg_replace_callback('/^ +/m', function($spaces) {
    return str_repeat(' ', strlen($spaces[0]) / 2);
  }, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "\n";
  
  // ファイルに上書き保存する
  // @ を先頭に付けるとエラーメッセージの出力を抑止できる
  $result = @file_put_contents($postFilePath, $newPostsText);
  if(!$result) {
    responseError('Failed To Remove Post File');
    return false;
  }
  
  // 成功レスポンス
  echo json_encode(array(
    'result' => 'Success To Remove',
    'removed_post' => $removedPost
  ));
  return true;
}

?>
