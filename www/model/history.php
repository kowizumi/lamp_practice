<?php

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

function insert_detail($db, $carts){

    $order_id = $db->lastInsertId();
    
    foreach ($carts as $value){
    
        if(insert_history_detail($db, $order_id, $value['item_id'], $value['price'], $value['amount']) === false){
            return false;
        }
    }
    return true;
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