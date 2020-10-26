<?php

// ======================================================================
// Solilog : 投稿を表示する
// 
// ex. `solilog-show.php` → 現在年月の投稿を返す
// ex. `solilog-show.php?t=2020-01` → 指定年月の投稿を返す
// ファイルが見つからない場合は現在年月の投稿を返す
// ======================================================================


// グローバル変数
// ======================================================================

// 設定ファイルのパス
$CONFIG_FILE_PATH = './solilog-config.json';


// メイン処理
// ======================================================================

date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');

// 設定ファイルを読み込む・読み込めなかった場合は NULL が返る・関数内でエラーレスポンスを出力しているので終了する
$config = loadConfig();
if(empty($config)) { exit(); }

// ファイルを取得しレスポンスして終了する
loadPosts($config['postDirectoryPath'], $config['postFileNamePrefix']);
exit();


// 関数
// ================================================================================

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
  if(isEmpty($configObject['postDirectoryPath']) || isEmpty($configObject['postFileNamePrefix'])) {
    responseError('Invalid Config File');
    return NULL;
  }
  
  return $configObject;
}

/** エラーレスポンスを返す */
function responseError($errorMessage) {
  echo json_encode(array('error' => $errorMessage));
}

/** 引数が空値かどうか判定する */
function isEmpty($value) {
  return !isset($value) || trim($value) === '';
}

/** 引数が配列として要素を持っているかどうか確認する */
function isEmptyArray($array) {
  return empty($array) || empty($array[0]);
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

/** 投稿を返す */
function loadPosts($postDirectoryPath, $postFileNamePrefix) {
  $t = getParameter('t');
  
  // パラメータがない場合は現在年月のファイルを返す
  if(isEmpty($t)) {
    $posts = loadCurrentPostsFile($postDirectoryPath, $postFileNamePrefix);
    if(isEmptyArray($posts)) {
      echo json_encode(array('error' => 'No Parameter And Current Posts File Not Found'));
      return;
    }
    
    echo json_encode(array(
      'info' => 'No Parameter, Returns Current Posts',
      't' => getCurrentYearMonth(),
      'posts' => $posts
    ));
    return;
  }
  
  // パラメータは2000年代で YYYY-MM 形式のみ・マッチしなければ現在年月のファイルを返す
  if(!preg_match('/^2[0-9]{3}-[0-1][0-9]$/u', $t)) {
    $posts = loadCurrentPostsFile($postDirectoryPath, $postFileNamePrefix);
    if(isEmptyArray($posts)) {
      echo json_encode(array('error' => 'Invalid Parameter And Current Posts File Not Found'));
      return;
    }
    
    echo json_encode(array(
      'info' => 'Invalid Parameter, Returns Current Posts',
      't' => getCurrentYearMonth(),
      'posts' => $posts
    ));
    return;
  }
  
  // パラメータに基づくファイルを返す・ファイルが見つからなければ現在年月のファイルを返す
  $posts = loadPostsFile($postDirectoryPath, $postFileNamePrefix, $t);
  if(isEmptyArray($posts)) {
    // ファイルが見つからなかったので現在年月のファイルを返す
    $posts = loadCurrentPostsFile($postDirectoryPath, $postFileNamePrefix);
    if(isEmptyArray($posts)) {
      echo json_encode(array('error' => 'That Posts File Not Found (Maybe Invalid Parameter) And Current Posts File Not Found'));
      return;
    }
    
    echo json_encode(array(
      'info' => 'That Posts File Not Found (Maybe Invalid Parameter), Returns Current Posts',
      't' => getCurrentYearMonth(),
      'posts' => $posts
    ));
    return;
  }
  
  echo json_encode(array(
    'info' => 'Valid Parameter, Returns That Posts',
    't' => $t,
    'posts' => $posts
  ));
}

/** 投稿ファイルを読み込む */
function loadPostsFile($postDirectoryPath, $postFileNamePrefix, $postFileName) {
  $postFilePath = $postDirectoryPath . '/' . $postFileNamePrefix . $postFileName . '.json';
  if(!file_exists($postFilePath)) {
    return [];
  }
  
  $postsFile = file_get_contents($postFilePath);  // ファイルを読み込む
  $posts = json_decode($postsFile, true);  // 連想配列に変換する
  
  // 投稿があるかどうか確認する
  if(isEmptyArray($posts)) {
    return [];
  }
  
  return $posts;
}

/** 現在年月の投稿ファイルを読み込む */
function loadCurrentPostsFile($postDirectoryPath, $postFileNamePrefix) {
  return loadPostsFile($postDirectoryPath, $postFileNamePrefix, getCurrentYearMonth());
}

/** 現在年月を返す */
function getCurrentYearMonth() {
  return date('Y-m');
}

?>
