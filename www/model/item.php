<?php
// 汎用関数ファイル読み込み
require_once MODEL_PATH . 'functions.php';
// データベース関数ファイル読み込み
require_once MODEL_PATH . 'db.php';

// DB利用
// 指定された商品のデータを取得する関数
function get_item($db, $item_id){
  // SQL文で商品のデータを取得
  $sql = "
    SELECT
      item_id,
      name,
      stock,
      price,
      image,
      status
    FROM
      items
    WHERE
      item_id = :item_id
  ";
  // SQLインジェクション対策のため配列で渡す
  $params = array(':item_id' => $item_id);
  // SQL文を実行
  return fetch_query($db, $sql, $params);
}

// 公開ステータスの商品データを取得
function get_items($db, $is_open = false){
  // 非公開の場合
  $sql = '
    SELECT
      item_id,
      name,
      stock,
      price,
      image,
      status
    FROM
      items
  ';
  // 公開の場合
  if($is_open === true){
    // 上記のSQL文にWHERE文を追加
    $sql .= '
      WHERE status = 1
    ';
  }
  // SQL文の実行
  return fetch_all_query($db, $sql);
}
// 全ての商品のデータを取得
function get_all_items($db){
  // get_items関数で取得して返す
  return get_items($db);
}

// 公開ステータスの商品全てを取得する関数
function get_open_items($db){
  // get_items関数にtrueを渡す
  return get_items($db, true);
}

// 商品情報が正しいか確認しデータベースに登録する関数
function regist_item($db, $name, $price, $stock, $status, $image){
  // 画像ファイルが正しいか確認
  $filename = get_upload_filename($image);
  // 商品の入力情報が正しいか確認し不正があった場合
  if(validate_item($name, $price, $stock, $filename, $status) === false){
    // falseを返す
    return false;
  }
  // データベースに商品情報追加
  return regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename);
}

// 商品をデータベースに登録する処理の関数
function regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename){
  // トランザクション開始
  $db->beginTransaction();
  // データベースに商品情報追加、画像をフォルダに保存
  if(insert_item($db, $name, $price, $stock, $filename, $status)
    && save_image($image, $filename)){
    // 正常に終了したらコミットする
    $db->commit();
    // trueを返す
    return true;
  }
  // 失敗した場合ロールバックする
  $db->rollback();
  // falseを返す
  return false;
}

// データベースに商品情報追加処理をする関数
function insert_item($db, $name, $price, $stock, $filename, $status){
  // status_valueにステータスを代入(0:非公開、1:公開)
  $status_value = PERMITTED_ITEM_STATUSES[$status];
  // itemsテーブルに情報追加
  $sql = "
    INSERT INTO
      items(
        name,
        price,
        stock,
        image,
        status
      )
    VALUES(:name, :price, :stock, :filename, :status_value);
  ";
  // SQLインジェクション対策のため配列で渡す
  $params = array(':name' => $name, ':price' => $price, ':stock' => $stock, ':filename' => $filename, ':status_value' => $status_value);
  // SQL文を実行
  return execute_query($db, $sql, $params);
}

// ステータス変更の処理をする関数
function update_item_status($db, $item_id, $status){
  // ステータスを更新するSQL文
  $sql = "
    UPDATE
      items
    SET
      status = :status
    WHERE
      item_id = :item_id
    LIMIT 1
  ";
  // SQLインジェクション対策のため配列で渡す
  $params = array(':status' => $status, ':item_id' => $item_id);
  // SQL文を実行
  return execute_query($db, $sql, $params);
}

// 商品在庫更新の処理をする関数
function update_item_stock($db, $item_id, $stock){
  // 在庫を更新するSQL文
  $sql = "
    UPDATE
      items
    SET
      stock = :stock
    WHERE
      item_id = :item_id
    LIMIT 1
  ";
  // SQLインジェクション対策のため配列で渡す
  $params = array(':stock' => $stock, ':item_id' => $item_id);
  // SQL文を実行する
  return execute_query($db, $sql, $params);
}

// 商品を削除する関数
function destroy_item($db, $item_id){
  // 商品情報を取得
  $item = get_item($db, $item_id);
  // 商品が不正の場合
  if($item === false){
    // falseを返す
    return false;
  }
  // トランザクション開始
  $db->beginTransaction();
  // 商品と画像を削除
  if(delete_item($db, $item['item_id'])
    && delete_image($item['image'])){
    // 成功したらコミット
    $db->commit();
    // trueを返す
    return true;
  }
  // 失敗した場合ロールバックする
  $db->rollback();
  // falseを返す
  return false;
}

// 商品を削除する処理の関数
function delete_item($db, $item_id){
  // itemsテーブルから削除するSQL文
  $sql = "
    DELETE FROM
      items
    WHERE
      item_id = :item_id
    LIMIT 1
  ";
  // SQLインジェクション対策のため配列で渡す
  $params = array(':item_id' => $item_id);
  // SQL文を実行する
  return execute_query($db, $sql, $params);
}


// 非DB
// 商品が公開か非公開か判定する関数
function is_open($item){
  // 商品ステータスが1 (true、falseで利用)
  return $item['status'] === 1;
}

// 商品の入力情報が正しいか判定する関数
function validate_item($name, $price, $stock, $filename, $status){
  // 正しければ代入
  $is_valid_item_name = is_valid_item_name($name);
  $is_valid_item_price = is_valid_item_price($price);
  $is_valid_item_stock = is_valid_item_stock($stock);
  $is_valid_item_filename = is_valid_item_filename($filename);
  $is_valid_item_status = is_valid_item_status($status);
  // 商品情報を返す
  return $is_valid_item_name
    && $is_valid_item_price
    && $is_valid_item_stock
    && $is_valid_item_filename
    && $is_valid_item_status;
}

// 名前が正しいか判定する関数
function is_valid_item_name($name){
  //
  $is_valid = true;
  // 文字数をチェックし、正しくない場合
  if(is_valid_length($name, ITEM_NAME_LENGTH_MIN, ITEM_NAME_LENGTH_MAX) === false){
    // エラーを出す
    set_error('商品名は'. ITEM_NAME_LENGTH_MIN . '文字以上、' . ITEM_NAME_LENGTH_MAX . '文字以内にしてください。');
    //
    $is_valid = false;
  }
  //
  return $is_valid;
}

// 価格が正しいか判定する関数
function is_valid_item_price($price){
  //
  $is_valid = true;
  // 正しい数値かチェックし、正しくない場合
  if(is_positive_integer($price) === false){
    // エラーを出す
    set_error('価格は0以上の整数で入力してください。');
    //
    $is_valid = false;
  }
  //
  return $is_valid;
}

// 在庫が正しいか判定する関数
function is_valid_item_stock($stock){
  //
  $is_valid = true;
  // 数値が正しいかチェックし、正しくない場合
  if(is_positive_integer($stock) === false){
    // エラーを出す
    set_error('在庫数は0以上の整数で入力してください。');
    //
    $is_valid = false;
  }
  //
  return $is_valid;
}

// ファイルの名前が正しいか判定する関数
function is_valid_item_filename($filename){
  //
  $is_valid = true;
  // 名前がない場合
  if($filename === ''){
    //
    $is_valid = false;
  }

  return $is_valid;
}

// ステータスが正しいか判定する関数
function is_valid_item_status($status){
  //
  $is_valid = true;
  // ステータスが0か1以外の場合
  if(isset(PERMITTED_ITEM_STATUSES[$status]) === false){
    //
    $is_valid = false;
  }

  return $is_valid;
}

function get_ranking($db){
  $sql = "
      SELECT
        items.item_id,
        items.name,
        items.price,
        items.image,
        SUM(amount)
      FROM
        items
      JOIN
        detail
      ON
        items.item_id = detail.item_id
      GROUP BY
        item_id
      ORDER BY
        SUM(amount) DESC
      LIMIT 3
";

return fetch_all_query($db, $sql);
}