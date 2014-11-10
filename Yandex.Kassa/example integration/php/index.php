<?php   include('config.php'); ?>

<form method="POST" action="http://money.yandex.ru/eshop.xml">

shopId, scId, ShopPassword should be registered in "config.php" or write to them every time there xD <br>
* - required fields<hr>

<input name="scId" value="<?php echo $configs['scId'] ?>" required=""> scId * - Counterparty's payment form ID. </br>
<input name="shopId" value="<?php echo $configs['shopId'] ?>" required=""> shopId * - Counterparty’s ID.</br>
<input name="customerNumber" required=""> customerNumber * - Payer ID in the Counterparty IS. The ID can be the payer’s contract number, login, etc.</br>
<input name="Sum" type="text" required=""> Sum * - Order total.</br><br>

<input type="radio" name="paymentType" value="PC"><b>Payment purse</b>; paymentType=PC</br>
<input type="radio" name="paymentType" value="AC"><b>Payment with any credit card</b>; paymentType=AC</br>
<input type="radio" name="paymentType" value="MC"><b>Payment from the mobile phone account</b>; paymentType=MC</br>
<input type="radio" name="paymentType" value="GP"><b>Payment via cash and cash terminals</b>; paymentType=GP</br>
<input type="radio" name="paymentType" value="WM"><b>Payment of the purse in system WebMoney</b>; paymentType=WM</br>
<input type="radio" name="paymentType" value="SB"><b>Online Payment through Sberbank</b>; paymentType=SB</br>
<input type="radio" name="paymentType" value="AB"><b>Online Payment through AlphaClick</b>; paymentType=AB</br><br>

<button type="submit">PAY</button>

</form>
