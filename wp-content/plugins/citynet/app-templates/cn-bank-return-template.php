<?php
/**
 * Template Name: قالب بازگشت بانک پنل سیتی نت
 *
 *
 * @package Citynet
 */

get_header();
?>
<style type="text/css">
.bank-return.stop-refresh-alert {
    background-color: white;
    border-radius: 8px;
}
.loader {
  border: 3px solid #f3f3f3;
  border-radius: 50%;
  border-top: 3px solid #555;
  width: 30px;
  height: 30px;
  -webkit-animation: spin 1.5s linear infinite; /* Safari */
  animation: spin 1.5s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
<?php
function generate_front_log_info_api()
{
    if ( count($_POST) != 0 ) {
        $message = [
            'RefId' => $_POST['RefId'],
            'SaleOrderId' => $_POST['SaleOrderId'],
            'SaleReferenceId' => $_POST['SaleReferenceId'],
            'FinalAmount' => $_POST['FinalAmount'],
        ];
    } else {
        $message = [
            'RefId' => ' ',
            'SaleOrderId' => ' ',
            'SaleReferenceId' => ' ',
            'FinalAmount' => ' ',
        ];
    }
    $data =  [
        'route' => '/panelreturn',
        'event' => 'php',
        'createdAt' => time(),
        'message' => $message
    ];
    $url =  $_SERVER['HTTP_HOST'];
    $request_url = 'https://gateway.citysoft.ir/';
    if (strpos($url, 'demoplus') !== false || strpos($url, 'local') !== false) {
        $request_url = 'https://demoplusapi.citysoft.ir/';
    }
	$response = wp_remote_post(
		$request_url . 'api/v1.0/front/log',
		array(
			'method' => 'POST',
			'body' => $data
		)
	);
    // var_dump($_POST);
	return $response;
}

generate_front_log_info_api();
// var_dump($_POST);
?>
<div id='app'></div>
<!-- mellat -->
<input type="hidden" name="refId" value="<?= $_POST['RefId'] ?>">
<input type="hidden" name="resCode" value="<?= $_POST['ResCode'] ?>">
<input type="hidden" name="saleOrderId" value="<?= $_POST['SaleOrderId'] ?>">
<input type="hidden" name="saleReferenceId" value="<?= $_POST['SaleReferenceId'] ?>">
<input type="hidden" name="cardHolderInfo" value="<?= $_POST['CardHolderInfo'] ?>">
<input type="hidden" name="cardHolderPan" value="<?= $_POST['CardHolderPan'] ?>">
<input type="hidden" name="finalAmount" value="<?= $_POST['FinalAmount'] ?>">


<!-- saman -->
<input type="hidden" name="State" value="<?= $_POST['State'] ?>">
<input type="hidden" name="Status" value="<?= $_POST['Status'] ?>">
<input type="hidden" name="RefNum" value="<?= $_POST['RefNum'] ?>">
<input type="hidden" name="ResNum" value="<?= $_POST['ResNum'] ?>">
<input type="hidden" name="TerminalId" value="<?= $_POST['TerminalId'] ?>">
<input type="hidden" name="TraceNo" value="<?= $_POST['TraceNo'] ?>">
<input type="hidden" name="Amount" value="<?= $_POST['Amount'] ?>">

<!-- parsian -->
<input type="hidden" name="Token" value="<?= $_POST['Token'] ?>">
<input type="hidden" name="RRN" value="<?= $_POST['RRN'] ?>">
<input type="hidden" name="OrderId" value="<?= $_POST['OrderId'] ?>">
<input type="hidden" name="status" value="<?= $_POST['status'] ?>">
<input type="hidden" name="Amount" value="<?= $_POST['Amount'] ?>">

<!-- irankish -->
<input type="hidden" name="token" value="<?= $_POST['token'] ?>">
<input type="hidden" name="acceptorId" value="<?= $_POST['acceptorId'] ?>">
<input type="hidden" name="responseCode" value="<?= $_POST['responseCode'] ?>">
<input type="hidden" name="paymentId" value="<?= $_POST['paymentId'] ?>">
<input type="hidden" name="requestId" value="<?= $_POST['requestId'] ?>">
<input type="hidden" name="retrievalReferenceNumber" value="<?= $_POST['retrievalReferenceNumber'] ?>">
<input type="hidden" name="systemTraceAuditNumber" value="<?= $_POST['systemTraceAuditNumber'] ?>">
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">

<!-- rayan -->
<input type="hidden" name="wasSuccessful" value="<?= $_POST['wasSuccessful'] ?>">
<input type="hidden" name="wasCancel" value="<?= $_POST['wasCancel'] ?>">
<input type="hidden" name="orderId" value="<?= $_POST['orderId'] ?>">
<input type="hidden" name="token" value="<?= $_POST['token'] ?>">

<!-- yekpay--> 
<input type="hidden" name="authority" value="<?= $_POST['authority'] ?>">
<input type="hidden" name="status" value="<?= $_POST['status'] ?>">
<input type="hidden" name="description" value="<?= $_POST['description'] ?>">
<input type="hidden" name="errorCode" value="<?= $_POST['error_code'] ?>">
<input type="hidden" name="contractNumber" value="<?= $_POST['order_id'] ?>">

<!-- knet--> 
<input type="hidden" name="paymentid" value="<?= $_POST['paymentid'] ?>">
<input type="hidden" name="trackid" value="<?= $_POST['trackid'] ?>">
<input type="hidden" name="trandata" value="<?= $_POST['trandata'] ?>">

<!-- ziraat -->
<input type="hidden" name="TransId" value="<?= $_POST['TransId'] ?>">
<input type="hidden" name="oid" value="<?= $_POST['oid'] ?>">
<input type="hidden" name="mdStatus" value="<?= $_POST['mdStatus'] ?>">
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">

<!-- tara -->
<input type="hidden" name="result" value="<?= $_POST['result'] ?>">
<input type="hidden" name="token" value="<?= $_POST['token'] ?>">
<input type="hidden" name="channelRefNumber" value="<?= $_POST['channelRefNumber'] ?>">
<input type="hidden" name="orderId" value="<?= $_POST['orderId'] ?>">

<!-- kipaa -->
<input type="hidden" name="reciept_number" value="<?= $_POST['reciept_number'] ?>">
<input type="hidden" name="payment_token" value="<?= $_POST['payment_token'] ?>">
<input type="hidden" name="state" value="<?= $_POST['state'] ?>">

<!-- asanpardakht -->
<input type="hidden" name="PayGateTranID" value="<?= $_POST['PayGateTranID'] ?>">
<input type="hidden" name="ReturningParams" value="<?= $_POST['ReturningParams'] ?>">
<input type="hidden" name="MerchantShaparakFee" value="<?= $_POST['MerchantShaparakFee'] ?>">


<!-- digipay -->
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">
<input type="hidden" name="trackingCode" value="<?= $_POST['trackingCode'] ?>">
<input type="hidden" name="result" value="<?= $_POST['result'] ?>">
<input type="hidden" name="type" value="<?= $_POST['type'] ?>">
<input type="hidden" name="providerId" value="<?= $_POST['providerId'] ?>">

<!-- meli -->
<input type="hidden" name="token" value="<?= $_POST['token'] ?>">
<input type="hidden" name="OrderId" value="<?= $_POST['OrderId'] ?>">
<input type="hidden" name="ResCode" value="<?= $_POST['ResCode'] ?>">

<!-- sepehr -->
<input type="hidden" name="respcode" value="<?= $_POST['respcode'] ?>">
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">
<input type="hidden" name="tracenumber" value="<?= $_POST['tracenumber'] ?>">
<input type="hidden" name="invoiceid" value="<?= $_POST['invoiceid'] ?>">
<input type="hidden" name="digitalreceipt" value="<?= $_POST['digitalreceipt'] ?>">
<input type="hidden" name="rrn" value="<?= $_POST['rrn'] ?>">

<!-- sibpay -->
<input type="hidden" name="rnn" value="<?= $_POST['rnn'] ?>">
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">
<input type="hidden" name="paymentToken" value="<?= $_POST['paymentToken'] ?>">
<input type="hidden" name="productId" value="<?= $_POST['invoiceid'] ?>">

<!-- saana -->
<input type="hidden" name="status" value="<?= $_POST['status'] ?>">
<input type="hidden" name="amount" value="<?= $_POST['amount'] ?>">
<input type="hidden" name="orderId" value="<?= $_POST['orderId'] ?>">
<input type="hidden" name="satusText" value="<?= $_POST['statusText'] ?>">
<input type="hidden" name="requestCode" value="<?= $_POST['requestCode'] ?>">
<input type="hidden" name="transactionCode" value="<?= $_POST['transactionCode'] ?>">

<?php
 get_footer(); ?>
