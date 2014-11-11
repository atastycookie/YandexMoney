<?php

	include('config.php');

	$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.$_POST['orderSumBankPaycash'].';'.$configs['shopId'].';'.$_POST['invoiceId'].';'.$_POST['customerNumber'].';'.$configs['ShopPassword']);		
	if (strtolower($hash) != strtolower($_POST['md5'])){
		$code = 1;
	}
	else {
		$code = 0;
	}
		print '<?xml version="1.0" encoding="UTF-8"?>';
		print '<checkOrderResponse performedDatetime="'. $_POST['requestDatetime'] .'" code="'.$code.'"'. ' invoiceId="'. $_POST['invoiceId'] .'" shopId="'. $configs['shopId'] .'"/>';

?>
