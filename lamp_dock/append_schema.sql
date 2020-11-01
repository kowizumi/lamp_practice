-- 購入履歴テーブル
-- カラム

-- 注文番号 オートインクリメント 主キー
-- ユーザid
-- 合計金額
-- 購入日時

-- SQL文
CREATE TABLE history (
    order_id INT AUTO_INCREMENT,
    user_id INT,
    total INT,
    date DATETIME,
    primary key(order_id)
);

-- 購入明細テーブル
-- カラム

-- 明細番号 オートインクリメント 主キー
-- 注文番号
-- アイテムID
-- 価格
-- 購入数

-- SQL文
CREATE TABLE detail (
    detail_id INT AUTO_INCREMENT,
    order_id INT,
    item_id INT,
    price INT,
    amount INT,
    primary key(detail_id)
);