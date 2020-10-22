<?php 
// 汎用関数ファイル読み込み
require_once MODEL_PATH . 'functions.php';
// データベース関数ファイル読み込み
require_once MODEL_PATH . 'db.php';

// ユーザidからカート商品一覧を取得する関数
function get_user_carts($db, $user_id){
  // SQL文を記述
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = :user_id
  ";
  // SQLインジェクション対策のためSQL文中には変数を使わず、executeの引数に配列で渡す
  $params = array(':user_id' => $user_id);
  // 一致したもの全てを配列で返す
  return fetch_all_query($db, $sql, $params);
}

// カートにある際商品を確認する関数
function get_user_cart($db, $user_id, $item_id){
  // SQL文
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = :user_id
    AND
      items.item_id = :item_id
  ";

  $params = array(':user_id' => $user_id, ':item_id' => $item_id);
  // 結果を返す
  return fetch_query($db, $sql, $params);

}

// カートに追加か在庫追加かを判定する関数
function add_cart($db, $user_id, $item_id ) {
  // get_user_cart関数の戻り値を$cartに代入
  $cart = get_user_cart($db, $user_id, $item_id);
  // 戻り値がなかった場合
  if($cart === false){
    // insert_cartでカートに新規追加する
    return insert_cart($db, $user_id, $item_id);
  }
  // 戻り値があった場合は数量を1増やす
  return update_cart_amount($db, $cart['cart_id'], $cart['amount'] + 1);
}

// カートに新規追加する関数
function insert_cart($db, $user_id, $item_id, $amount = 1){
  // SQL文
  $sql = "
    INSERT INTO
      carts(
        item_id,
        user_id,
        amount
      )
    VALUES(:item_id, :user_id, :amount)
  ";

  $params = array(':item_id' => $item_id, ':user_id' => $user_id, ':amount' => $amount);
  // 
  return execute_query($db, $sql, $params);
}

function update_cart_amount($db, $cart_id){
  $sql = "
    UPDATE
      carts
    SET
      amount = :amount
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";
  $params = array(':amount' => $amount, ':cart_id' => $cart_id);

  return execute_query($db, $sql, $params);
}

function delete_cart($db, $cart_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";
  $params = array(':cart_id' => $cart_id);

  return execute_query($db, $sql, $params);
}

function purchase_carts($db, $carts){
  if(validate_cart_purchase($carts) === false){
    return false;
  }
  foreach($carts as $cart){
    if(update_item_stock(
        $db, 
        $cart['item_id'], 
        $cart['stock'] - $cart['amount']
      ) === false){
      set_error($cart['name'] . 'の購入に失敗しました。');
    }
  }
  
  delete_user_carts($db, $carts[0]['user_id']);
}

function delete_user_carts($db, $user_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      user_id = :user_id
  ";
  $params = array(':user_id' => $user_id);

// SQL文を実行する
  execute_query($db, $sql, $params);
}


function sum_carts($carts){
  $total_price = 0;
  foreach($carts as $cart){
    $total_price += $cart['price'] * $cart['amount'];
  }
  return $total_price;
}

function validate_cart_purchase($carts){
  if(count($carts) === 0){
    set_error('カートに商品が入っていません。');
    return false;
  }
  foreach($carts as $cart){
    if(is_open($cart) === false){
      set_error($cart['name'] . 'は現在購入できません。');
    }
    if($cart['stock'] - $cart['amount'] < 0){
      set_error($cart['name'] . 'は在庫が足りません。購入可能数:' . $cart['stock']);
    }
  }
  if(has_error() === true){
    return false;
  }
  return true;
}

