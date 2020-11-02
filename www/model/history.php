<?php
// ユーザidが渡されないと第ニ引数がfalseになる
function get_user_history($db, $user_id = false){
// falseの場合(admin)は全て取得
    $sql = "
      SELECT
        order_id,
        total,
        date
      FROM
        history
        ";
// 第ニ引数があった場合
    if($user_id !== false){
// SQL文にユーザidの一致するものを取得する処理を追加
       $sql .= "WHERE
        user_id = :user_id
        ";
    }
// 降順で並び替える
      $sql .= "ORDER BY 
        date
        DESC
    ";
    // SQLインジェクション対策のためSQL文中には変数を使わず、executeの引数に配列で渡す
    $params = array(':user_id' => $user_id);
    // ユーザidがある場合
    if($user_id !== false){
    //paramsを渡す    
        return fetch_all_query($db, $sql, $params);
    }
    // ユーザidが無い場合(admin)は第三引数無しで渡す
    return fetch_all_query($db, $sql);
  }

function insert_history($db, $user_id, $total_price, $date){
    $sql = "
        INSERT INTO
            history(
                user_id,
                total,
                date
            )
        VALUES(:user_id, :total, :date)
    ";

    $params = array(':user_id' => $user_id, ':total' => $total_price, ':date' => $date);
    
    return execute_query($db, $sql, $params);
}

function insert_history_detail($db, $order_id, $item_id, $price, $amount){
    
    $sql = "
        INSERT INTO
            detail(
                order_id,
                item_id,
                price, 
                amount
            )
        VALUES(:orer_id, :item_id, :price, :amount)
    ";

    $params = array(':orer_id' => $order_id, ':item_id' => $item_id, ':price' => $price, ':amount' => $amount);

    return execute_query($db, $sql, $params);
}

function insert_buy_data($db, $user_id, $total_price, $carts){

    $date = date('Y-m-d H:i:s');

    $db->beginTransaction();

    if (insert_history($db, $user_id, $total_price, $date) 
            && insert_detail($db, $carts)){

            $db->commit();
            return true;
    }
    $db->rollback();
    return false;
}

function insert_detail($db, $carts){

    $order_id = $db->lastInsertId();
    
    foreach ($carts as $value){
    
        if(insert_history_detail($db, $order_id, $value['item_id'], $value['price'], $value['amount']) === false){
            return false;
        }
    }
    return true;
}

