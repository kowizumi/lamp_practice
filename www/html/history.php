<?php
require_once '../conf/const.php';
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'user.php';
require_once MODEL_PATH . 'item.php';
require_once MODEL_PATH . 'history.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$db = get_db_connect();
$user = get_login_user($db);

$historys = [];

// adminではない場合
if(is_admin($user) === false){
// ユーザidを渡す
  $historys = get_user_history($db, $user['user_id']);
} else {
// それ以外(admin)の場合は第ニ引数を渡さない
  $historys = get_user_history($db);
}

include_once VIEW_PATH . 'history_view.php';