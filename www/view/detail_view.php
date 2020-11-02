<?php
  // クリックジャッキング対策
  header('X-FRAME-OPTIONS: DENY');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <?php include VIEW_PATH . 'templates/head.php'; ?>
  <title>カート</title>
  <link rel="stylesheet" href="<?php print(h(STYLESHEET_PATH . 'cart.css')); ?>">
</head>
<body>
  <?php include VIEW_PATH . 'templates/header_logined.php'; ?>
  <h1>購入明細</h1>
  <div class="container">

    <?php include VIEW_PATH . 'templates/messages.php'; ?>
    
    <h2>購入履歴</h2>
    <?php if(count($historys) > 0){ ?>
      <table class="table table-bordered">
        <thead class="thead-light">
          <tr>
            <th>注文番号</th>
            <th>購入日時</th>
            <th>合計金額</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($historys as $history){ ?>
          <tr>
            <td><?php print(h($history['order_id'])); ?></td>
            <td><?php print(h($history['date'])); ?></td>
            <td><?php print(h(number_format($history['total']))); ?>円</td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <h2>購入明細</h2>
      <table class="table table-bordered">
        <thead class="thead-light">
          <tr>
            <th>商品名</th>
            <th>購入価格</th>
            <th>購入数</th>
            <th>小計</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($details as $detail){ ?>
          <!-- 小計はviewで計算する -->
          <tr>
            <td><?php print(h($detail['name'])); ?></td>
            <td><?php print(h(number_format($detail['price']))); ?>円</td>
            <td><?php print(h($detail['amount'])); ?>個</td>
            <td><?php print(h(number_format($detail['price'] * $detail['amount']))); ?>円</td>
          </tr>
          <?php } ?>
        </tbody>
      </table>

    <?php } else { ?>
      <p>購入履歴はありません。</p>
    <?php } ?> 
  </div>

</body>
</html>