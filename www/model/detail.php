<?php

function get_user_history($db, $user_id, $order_id){
    
    $sql = "
      SELECT
        order_id,
        total,
        date
      FROM
        history
      WHERE
        user_id = :user_id
      AND
        order_id = :order_id
    ";
    // SQLインジェクション対策のためSQL文中には変数を使わず、executeの引数に配列で渡す
    $params = array(':user_id' => $user_id, ':order_id' => $order_id);
    // 一致したもの全てを配列で渡す
    return fetch_all_query($db, $sql, $params);
  }


  function get_history_detail($db, $order_id){
    $sql = "
    SELECT
      items.name,
      detail.price,
      detail.amount
    FROM
      items
    JOIN
      detail
    ON
      items.item_id = detail.item_id
    WHERE
      detail.order_id = :order_id
  ";
  // SQLインジェクション対策のためSQL文中には変数を使わず、executeの引数に配列で渡す
  $params = array(':order_id' => $order_id);
  // 一致したもの全てを配列で渡す
  return fetch_all_query($db, $sql, $params);
  }
