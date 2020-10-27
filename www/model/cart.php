<?php 
// 汎用関数ファイル読み込み
require_once MODEL_PATH . 'functions.php';
// データベース関数ファイル読み込み
require_once MODEL_PATH . 'db.php';

// ユーザidからカート商品一覧を取得する関数
function get_user_carts($db, $user_id){
  // item_idの同じものを参照する
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
  // 一致したもの全てを配列で渡す
  return fetch_all_query($db, $sql, $params);
}

// カートにある商品を確認する関数
function get_user_cart($db, $user_id, $item_id){
  // user_idとitem_idから重複確認
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
  // SQLインジェクション対策のため配列に変数を入れる
  $params = array(':user_id' => $user_id, ':item_id' => $item_id);
  // fetch_queryに結果を渡す
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
  // カートに新規追加するSQL文
  $sql = "
    INSERT INTO
      carts(
        item_id,
        user_id,
        amount
      )
    VALUES(:item_id, :user_id, :amount)
  ";
  // SQLインジェクション対策のため配列に代入
  $params = array(':item_id' => $item_id, ':user_id' => $user_id, ':amount' => $amount);
  // execute_queryに値を渡す
  return execute_query($db, $sql, $params);
}

// カートに商品が存在した場合数量を1増やす関数
function update_cart_amount($db, $cart_id, $amount){
  // 数量を1増やす
  $sql = "
    UPDATE
      carts
    SET
      amount = :amount
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";
  // SQLインジェクション対策のため配列に代入
  $params = array(':amount' => $amount, ':cart_id' => $cart_id);
  // execute_queryに値を渡す
  return execute_query($db, $sql, $params);
}

// カートの商品を削除する関数
function delete_cart($db, $cart_id){
  // カートテーブルから削除するSQL文
  $sql = "
    DELETE FROM
      carts
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";
  // SQLインジェクション対策のため配列に代入
  $params = array(':cart_id' => $cart_id);
  // execute_queryに値を渡す
  return execute_query($db, $sql, $params);
}

// 商品購入後、カートの数量を減らす指示をする関数
function purchase_carts($db, $carts){
  // カートの商品の数量が0以外か正常か確かめる関数
  if(validate_cart_purchase($carts) === false){
    // 0ならfalseを返す
    return false;
  }
  // $cartsから値を取り出す
  foreach($carts as $cart){
    // 在庫からカートの数量を減らした数をアップデートの関数に渡し、失敗した場合
    if(update_item_stock(
        $db, 
        $cart['item_id'], 
        $cart['stock'] - $cart['amount']
      ) === false){
      // set_error関数にエラーを渡す
      set_error($cart['name'] . 'の購入に失敗しました。');
    }
  }
  // ユーザーのカートの商品を削除する関数に値を渡す
  delete_user_carts($db, $carts[0]['user_id']);
}

// ユーザーのカートの商品を削除する関数 
function delete_user_carts($db, $user_id){
  // カートテーブルのユーザidの一致したものを削除する
  $sql = "
    DELETE FROM
      carts
    WHERE
      user_id = :user_id
  ";
  // SQLインジェクション対策でexecuteに配列で渡す
  $params = array(':user_id' => $user_id);

// SQL文を実行する関数に引数を渡す
  execute_query($db, $sql, $params);
}

// カートの合計金額を算出する関数
function sum_carts($carts){
  // total_priceの初期化
  $total_price = 0;
  // cartsから値を取り出す
  foreach($carts as $cart){
    // 合計に価格と数量を掛けたものを足していく
    $total_price += $cart['price'] * $cart['amount'];
  }
  // 合計金額を返す
  return $total_price;
}

// 商品が正常か確かめる関数
function validate_cart_purchase($carts){
  // カートに商品が入っていない場合
  if(count($carts) === 0){
    // エラーをセットする
    set_error('カートに商品が入っていません。');
    // falseを返す
    return false;
  }
  // cartsから値を取り出す
  foreach($carts as $cart){
    // 商品が非公開の場合
    if(is_open($cart) === false){
      // エラーをセットする
      set_error($cart['name'] . 'は現在購入できません。');
    }
    // カートの数量が在庫より多い場合
    if($cart['stock'] - $cart['amount'] < 0){
      // エラーをセットする
      set_error($cart['name'] . 'は在庫が足りません。購入可能数:' . $cart['stock']);
    }
  }
  // エラーがある場合
  if(has_error() === true){
    // falseを返す
    return false;
  }
  // 商品が正常ならtrueを返す
  return true;
}

