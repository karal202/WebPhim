<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/OrderModel.php';

session_start();

// Initialize OrderModel
$orderModel = new OrderModel($conn);

// MoMo configuration
$momo_PartnerCode = "MOMOBKUN20180529";
$momo_AccessKey = "klm05TvNBzhg7h7j";
$momo_SecretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";
$momo_Endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
$momo_RedirectUrl = "http://localhost/WebPhim/app/controller/CheckoutController.php?method=momo";
$momo_IpnUrl = "http://localhost/WebPhim/app/controller/CheckoutController.php?method=momo";

// VNPay configuration
$vnp_TmnCode = "TUOCD0X2";
$vnp_HashSecret = "WVXI7L2MKSY59ROPI2FSM1EV8FKNAWW5";
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://localhost/WebPhim/app/controller/CheckoutController.php?method=vnpay";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
$apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";

// Config input format - Expiration time
$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

// Check if this is a callback
if (isset($_GET['method'])) {
    if ($_GET['method'] === 'momo') {
        handleMomoCallback();
    } elseif ($_GET['method'] === 'vnpay') {
        handleVnpayCallback();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request for initiating payment
    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'momo') {
        initiateMomoPayment();
    } else {
        initiateVnpayPayment();
    }
} else {
    error_log("Invalid request - No method or POST data");
    die(json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']));
}

function initiateMomoPayment() {
    global $conn, $orderModel, $momo_PartnerCode, $momo_AccessKey, $momo_SecretKey, $momo_Endpoint, $momo_RedirectUrl, $momo_IpnUrl;

    // Log incoming POST data
    error_log("initiateMomoPayment - POST data: " . print_r($_POST, true));

    // Validate POST data
    if (!isset($_POST['orderId'], $_POST['amount'])) {
        error_log("initiateMomoPayment - Missing required POST data");
        die(json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']));
    }

    $orderId = intval($_POST['orderId']);
    $amount = floatval($_POST['amount']);
    $ipAddr = $_SERVER['REMOTE_ADDR'];

    // Log validated inputs
    error_log("initiateMomoPayment - orderId: $orderId, amount: $amount");

    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        error_log("initiateMomoPayment - User not logged in");
        header('Location: ../../login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        die();
    }

    // Validate order exists and is pending
    $sql = "SELECT id, user_id, total_amount, status FROM orders WHERE id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log("initiateMomoPayment - Order not found for orderId: $orderId");
        die(json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại']));
    }
    if ($order['status'] !== 'pending') {
        error_log("initiateMomoPayment - Order status is not pending: " . $order['status']);
        die(json_encode(['success' => false, 'message' => 'Đơn hàng không ở trạng thái chờ xử lý']));
    }
    if ($order['total_amount'] != $amount) {
        error_log("initiateMomoPayment - Amount mismatch: POST amount=$amount, DB amount=" . $order['total_amount']);
        die(json_encode(['success' => false, 'message' => 'Số tiền không khớp với đơn hàng']));
    }
    if ($order['user_id'] != $_SESSION['user_id']) {
        error_log("initiateMomoPayment - User ID mismatch: Session user_id=" . $_SESSION['user_id'] . ", Order user_id=" . $order['user_id']);
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền xử lý đơn hàng này']));
    }

    // Generate unique transaction reference
    $momo_TxnRef = 'WebPhim_MOMO_' . $orderId . '_' . time();
    $requestId = time() . "";
    $orderInfo = "Thanh toan don hang: " . $momo_TxnRef;
    $requestType = "payWithATM";
    $extraData = "";

    // Update order with payment_memo
    $sql = "UPDATE orders SET payment_memo = :payment_memo WHERE id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':payment_memo', $momo_TxnRef, PDO::PARAM_STR);
    $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    // Prepare MoMo input data
    $rawHash = "accessKey=" . $momo_AccessKey . "&amount=" . (int)$amount . "&extraData=" . $extraData . "&ipnUrl=" . $momo_IpnUrl . "&orderId=" . $momo_TxnRef . "&orderInfo=" . $orderInfo . "&partnerCode=" . $momo_PartnerCode . "&redirectUrl=" . $momo_RedirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
    $signature = hash_hmac("sha256", $rawHash, $momo_SecretKey);

    $data = [
        'partnerCode' => $momo_PartnerCode,
        'partnerName' => "TVN Films",
        'storeId' => "TVNFilmsStore",
        'requestId' => $requestId,
        'amount' => (int)$amount,
        'orderId' => $momo_TxnRef,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $momo_RedirectUrl,
        'ipnUrl' => $momo_IpnUrl,
        'lang' => 'vi',
        'extraData' => $extraData,
        'requestType' => $requestType,
        'signature' => $signature
    ];

    // Log request data for debugging
    error_log("initiateMomoPayment - MoMo request data: " . print_r($data, true));

    // Send request to MoMo
    $result = execPostRequest($momo_Endpoint, json_encode($data));
    $jsonResult = json_decode($result, true);

    // Log full MoMo response for debugging
    error_log("initiateMomoPayment - MoMo response: " . print_r($jsonResult, true));

    if (!$result || !isset($jsonResult['payUrl']) || empty($jsonResult['payUrl'])) {
        $errorMessage = isset($jsonResult['message']) ? $jsonResult['message'] : 'Không thể khởi tạo thanh toán MoMo';
        $resultCode = isset($jsonResult['resultCode']) ? $jsonResult['resultCode'] : 'N/A';
        error_log("initiateMomoPayment - Failed to get payUrl for orderId: $orderId, Error: $errorMessage, ResultCode: $resultCode");
        die(json_encode(['success' => false, 'message' => $errorMessage]));
    }

    // Log the redirect URL
    error_log("initiateMomoPayment - Redirecting to MoMo: " . $jsonResult['payUrl']);

    header('Location: ' . $jsonResult['payUrl']);
    die();
}

function handleMomoCallback() {
    global $conn, $orderModel, $momo_SecretKey;

    // Log callback data
    error_log("handleMomoCallback - GET data: " . print_r($_GET, true));

    // Verify MoMo response
    $inputData = [
        'partnerCode' => $_GET['partnerCode'] ?? '',
        'orderId' => $_GET['orderId'] ?? '',
        'requestId' => $_GET['requestId'] ?? '',
        'amount' => $_GET['amount'] ?? '',
        'orderInfo' => $_GET['orderInfo'] ?? '',
        'orderType' => $_GET['orderType'] ?? '',
        'transId' => $_GET['transId'] ?? '',
        'resultCode' => $_GET['resultCode'] ?? '',
        'message' => $_GET['message'] ?? '',
        'payType' => $_GET['payType'] ?? '',
        'responseTime' => $_GET['responseTime'] ?? '',
        'extraData' => $_GET['extraData'] ?? ''
    ];
    $receivedSignature = $_GET['signature'] ?? '';

    // Generate signature for verification
    $rawHash = "accessKey=" . $GLOBALS['momo_AccessKey'] . "&amount=" . $inputData['amount'] . "&extraData=" . $inputData['extraData'] . "&message=" . $inputData['message'] . "&orderId=" . $inputData['orderId'] . "&orderInfo=" . $inputData['orderInfo'] . "&orderType=" . $inputData['orderType'] . "&partnerCode=" . $inputData['partnerCode'] . "&payType=" . $inputData['payType'] . "&requestId=" . $inputData['requestId'] . "&responseTime=" . $inputData['responseTime'] . "&resultCode=" . $inputData['resultCode'] . "&transId=" . $inputData['transId'];
    $computedSignature = hash_hmac("sha256", $rawHash, $momo_SecretKey);

    $orderId = isset($_GET['orderId']) ? explode('_', $_GET['orderId'])[2] : 0;
    $momo_ResultCode = $_GET['resultCode'] ?? '';
    $momo_TxnRef = $_GET['orderId'] ?? '';
    $momo_Amount = floatval($_GET['amount'] ?? 0);
    $momo_TransId = $_GET['transId'] ?? '';

    // Validate signature and order
    if ($computedSignature !== $receivedSignature) {
        $paymentStatus = 'Thất bại: Chữ ký không hợp lệ';
        $orderModel->updateOrderStatus($orderId, 'cancelled');
        error_log("handleMomoCallback - Invalid signature for orderId: $orderId");
    } elseif ($momo_ResultCode !== '0') {
        $paymentStatus = 'Thất bại: Mã lỗi ' . $momo_ResultCode;
        $orderModel->updateOrderStatus($orderId, 'cancelled');
        error_log("handleMomoCallback - Payment failed with result code: $momo_ResultCode for orderId: $orderId");
    } else {
        // Verify order exists and matches
        $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, m.title as movie_title, rd.date, rd.time, r.name as room_name, 
                       GROUP_CONCAT(s.seat_number) as seat_numbers, GROUP_CONCAT(srv.name) as service_names
                FROM orders o
                LEFT JOIN room_detail rd ON o.movie_id = rd.movie_id AND o.room_id = rd.room_id AND o.date = rd.date AND o.time = rd.time
                LEFT JOIN movies m ON rd.movie_id = m.id
                LEFT JOIN rooms r ON rd.room_id = r.id
                LEFT JOIN order_seats os ON o.id = os.order_id
                LEFT JOIN seats s ON os.seat_id = s.id
                LEFT JOIN order_services osv ON o.id = osv.order_id
                LEFT JOIN services srv ON osv.service_id = srv.id
                WHERE o.id = :order_id AND o.payment_memo = :payment_memo
                GROUP BY o.id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':payment_memo', $momo_TxnRef, PDO::PARAM_STR);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order && $order['total_amount'] == $momo_Amount && $order['status'] == 'pending') {
            $paymentStatus = 'Thành công';
            $orderModel->updateOrderStatus($orderId, 'completed');
            try {
                saveMomoTransactionDetails($orderId, $momo_TxnRef, $momo_TransId, $momo_Amount, $momo_ResultCode);

                // Kiểm tra xem hóa đơn đã tồn tại chưa
                $sqlCheckInvoice = "SELECT id, payment_status FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
                $stmtCheckInvoice = $conn->prepare($sqlCheckInvoice);
                $stmtCheckInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmtCheckInvoice->execute();
                $existingInvoice = $stmtCheckInvoice->fetch(PDO::FETCH_ASSOC);

                if ($existingInvoice) {
                    // Cập nhật trạng thái hóa đơn nếu đã tồn tại
                    $sqlUpdateInvoice = "UPDATE invoices SET payment_status = :payment_status, transaction_ref = :transaction_ref WHERE order_id = :order_id";
                    $stmtUpdateInvoice = $conn->prepare($sqlUpdateInvoice);
                    $stmtUpdateInvoice->bindValue(':payment_status', $paymentStatus, PDO::PARAM_STR);
                    $stmtUpdateInvoice->bindValue(':transaction_ref', $momo_TxnRef, PDO::PARAM_STR);
                    $stmtUpdateInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                    $stmtUpdateInvoice->execute();
                } else {
                    // Nếu chưa có hóa đơn, tạo mới
                    saveInvoiceDetails($order, $paymentStatus, $momo_TxnRef);
                }
            } catch (Exception $e) {
                error_log("handleMomoCallback - Failed to save transaction or invoice for orderId: $orderId, Error: " . $e->getMessage());
                $paymentStatus = 'Thất bại: Lỗi lưu giao dịch';
                $orderModel->updateOrderStatus($orderId, 'cancelled');
            }
            error_log("handleMomoCallback - Payment successful for orderId: $orderId");
        } else {
            $paymentStatus = 'Thất bại: Đơn hàng không hợp lệ hoặc đã được xử lý';
            $orderModel->updateOrderStatus($orderId, 'cancelled');
            error_log("handleMomoCallback - Invalid order or already processed for orderId: $orderId");
        }
    }

    // Store payment status in session for invoice display
    $_SESSION['payment_status'] = $paymentStatus;
    $_SESSION['momo_TxnRef'] = $momo_TxnRef;

    // Redirect to invoice.html
    error_log("handleMomoCallback - Redirecting to invoice.html?orderId=$orderId");
    header('Location: ../../invoice.html?orderId=' . $orderId);
    die();
}

function saveMomoTransactionDetails($orderId, $momo_TxnRef, $momo_TransId, $momo_Amount, $momo_ResultCode) {
    global $conn;
    try {
        $sql = "
            INSERT INTO transactions (order_id, momo_txn_ref, momo_trans_id, momo_amount, momo_result_code)
            VALUES (:order_id, :momo_txn_ref, :momo_trans_id, :momo_amount, :momo_result_code)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':momo_txn_ref', $momo_TxnRef, PDO::PARAM_STR);
        $stmt->bindValue(':momo_trans_id', $momo_TransId, PDO::PARAM_STR);
        $stmt->bindValue(':momo_amount', $momo_Amount, PDO::PARAM_STR);
        $stmt->bindValue(':momo_result_code', $momo_ResultCode, PDO::PARAM_STR);
        $stmt->execute();
        error_log("saveMomoTransactionDetails - Successfully saved transaction for orderId: $orderId, momo_TxnRef: $momo_TxnRef");
    } catch (Exception $e) {
        error_log("saveMomoTransactionDetails - Error saving transaction for orderId: $orderId, Error: " . $e->getMessage());
        throw $e;
    }
}

function execPostRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("execPostRequest - cURL error: " . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function initiateVnpayPayment() {
    global $conn, $orderModel, $vnp_TmnCode, $vnp_HashSecret, $vnp_Url, $vnp_Returnurl, $expire;

    // Log incoming POST data
    error_log("initiateVnpayPayment - POST data: " . print_r($_POST, true));

    // Validate POST data
    if (!isset($_POST['orderId'], $_POST['amount'])) {
        error_log("initiateVnpayPayment - Missing required POST data");
        die(json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']));
    }

    $orderId = intval($_POST['orderId']);
    $amount = floatval($_POST['amount']);
    $bankCode = isset($_POST['bankCode']) ? $_POST['bankCode'] : '';
    $language = 'vn';
    $ipAddr = $_SERVER['REMOTE_ADDR'];

    // Log validated inputs
    error_log("initiateVnpayPayment - orderId: $orderId, amount: $amount, bankCode: $bankCode");

    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        error_log("initiateVnpayPayment - User not logged in");
        header('Location: ../../login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        die();
    }

    // Validate order exists and is pending
    $sql = "SELECT id, user_id, total_amount, status FROM orders WHERE id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log("initiateVnpayPayment - Order not found for orderId: $orderId");
        die(json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại']));
    }
    if ($order['status'] !== 'pending') {
        error_log("initiateVnpayPayment - Order status is not pending: " . $order['status']);
        die(json_encode(['success' => false, 'message' => 'Đơn hàng không ở trạng thái chờ xử lý']));
    }
    if ($order['total_amount'] != $amount) {
        error_log("initiateVnpayPayment - Amount mismatch: POST amount=$amount, DB amount=" . $order['total_amount']);
        die(json_encode(['success' => false, 'message' => 'Số tiền không khớp với đơn hàng']));
    }
    if ($order['user_id'] != $_SESSION['user_id']) {
        error_log("initiateVnpayPayment - User ID mismatch: Session user_id=" . $_SESSION['user_id'] . ", Order user_id=" . $order['user_id']);
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền xử lý đơn hàng này']));
    }

    // Generate unique transaction reference
    $vnp_TxnRef = 'WebPhim_' . $orderId . '_' . time();

    // Update order with payment_memo
    $sql = "UPDATE orders SET payment_memo = :payment_memo WHERE id = :order_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':payment_memo', $vnp_TxnRef, PDO::PARAM_STR);
    $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    // Prepare VNPay input data
    $inputData = [
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $amount * 100,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $ipAddr,
        "vnp_Locale" => $language,
        "vnp_OrderInfo" => "Thanh toan don hang: " . $vnp_TxnRef,
        "vnp_OrderType" => "billpayment",
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_ExpireDate" => $expire
    ];

    if (!empty($bankCode)) {
        $inputData['vnp_BankCode'] = $bankCode;
    }

    ksort($inputData);
    $query = "";
    $hashdata = "";
    $i = 0;
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

    // Log the redirect URL
    error_log("initiateVnpayPayment - Redirecting to VNPay: $vnp_Url");

    header('Location: ' . $vnp_Url);
    die();
}

function handleVnpayCallback() {
    global $conn, $orderModel, $vnp_HashSecret;

    // Log callback data
    error_log("handleVnpayCallback - GET data: " . print_r($_GET, true));

    // Verify VNPay response
    $vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
    $inputData = [];
    foreach ($_GET as $key => $value) {
        if (substr($key, 0, 4) == "vnp_") {
            $inputData[$key] = $value;
        }
    }
    unset($inputData['vnp_SecureHash']);
    ksort($inputData);
    $hashData = "";
    $i = 0;
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }
    $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

    $orderId = isset($_GET['vnp_TxnRef']) ? explode('_', $_GET['vnp_TxnRef'])[1] : 0;
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
    $vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
    $vnp_Amount = ($_GET['vnp_Amount'] ?? 0) / 100;
    $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';

    // Validate secure hash and order
    if ($secureHash !== $vnp_SecureHash) {
        $paymentStatus = 'Thất bại: Chữ ký không hợp lệ';
        $orderModel->updateOrderStatus($orderId, 'cancelled');
        error_log("handleVnpayCallback - Invalid secure hash for orderId: $orderId");
    } elseif ($vnp_ResponseCode !== '00') {
        $paymentStatus = 'Thất bại: Mã lỗi ' . $vnp_ResponseCode;
        $orderModel->updateOrderStatus($orderId, 'cancelled');
        error_log("handleVnpayCallback - Payment failed with response code: $vnp_ResponseCode for orderId: $orderId");
    } else {
        // Verify order exists and matches
        $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, m.title as movie_title, rd.date, rd.time, r.name as room_name, 
                       GROUP_CONCAT(s.seat_number) as seat_numbers, GROUP_CONCAT(srv.name) as service_names
                FROM orders o
                LEFT JOIN room_detail rd ON o.movie_id = rd.movie_id AND o.room_id = rd.room_id AND o.date = rd.date AND o.time = rd.time
                LEFT JOIN movies m ON rd.movie_id = m.id
                LEFT JOIN rooms r ON rd.room_id = r.id
                LEFT JOIN order_seats os ON o.id = os.order_id
                LEFT JOIN seats s ON os.seat_id = s.id
                LEFT JOIN order_services osv ON o.id = osv.order_id
                LEFT JOIN services srv ON osv.service_id = srv.id
                WHERE o.id = :order_id AND o.payment_memo = :payment_memo
                GROUP BY o.id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':payment_memo', $vnp_TxnRef, PDO::PARAM_STR);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order && $order['total_amount'] == $vnp_Amount && $order['status'] == 'pending') {
            $paymentStatus = 'Thành công';
            $orderModel->updateOrderStatus($orderId, 'completed');
            try {
                saveVnpayTransactionDetails($orderId, $vnp_TxnRef, $vnp_TransactionNo, $vnp_Amount, $vnp_ResponseCode);

                // Kiểm tra xem hóa đơn đã tồn tại chưa
                $sqlCheckInvoice = "SELECT id, payment_status FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
                $stmtCheckInvoice = $conn->prepare($sqlCheckInvoice);
                $stmtCheckInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmtCheckInvoice->execute();
                $existingInvoice = $stmtCheckInvoice->fetch(PDO::FETCH_ASSOC);

                if ($existingInvoice) {
                    // Cập nhật trạng thái hóa đơn nếu đã tồn tại
                    $sqlUpdateInvoice = "UPDATE invoices SET payment_status = :payment_status, transaction_ref = :transaction_ref WHERE order_id = :order_id";
                    $stmtUpdateInvoice = $conn->prepare($sqlUpdateInvoice);
                    $stmtUpdateInvoice->bindValue(':payment_status', $paymentStatus, PDO::PARAM_STR);
                    $stmtUpdateInvoice->bindValue(':transaction_ref', $vnp_TxnRef, PDO::PARAM_STR);
                    $stmtUpdateInvoice->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                    $stmtUpdateInvoice->execute();
                } else {
                    // Nếu chưa có hóa đơn, tạo mới
                    saveInvoiceDetails($order, $paymentStatus, $vnp_TxnRef);
                }
            } catch (Exception $e) {
                error_log("handleVnpayCallback - Failed to save transaction or invoice for orderId: $orderId, Error: " . $e->getMessage());
                $paymentStatus = 'Thất bại: Lỗi lưu giao dịch';
                $orderModel->updateOrderStatus($orderId, 'cancelled');
            }
            error_log("handleVnpayCallback - Payment successful for orderId: $orderId");
        } else {
            $paymentStatus = 'Thất bại: Đơn hàng không hợp lệ hoặc đã được xử lý';
            $orderModel->updateOrderStatus($orderId, 'cancelled');
            error_log("handleVnpayCallback - Invalid order or already processed for orderId: $orderId");
        }
    }

    // Store payment status in session for invoice display
    $_SESSION['payment_status'] = $paymentStatus;
    $_SESSION['vnp_TxnRef'] = $vnp_TxnRef;

    // Redirect to invoice.html
    error_log("handleVnpayCallback - Redirecting to invoice.html?orderId=$orderId");
    header('Location: ../../invoice.html?orderId=' . $orderId);
    die();
}

function saveVnpayTransactionDetails($orderId, $vnp_TxnRef, $vnp_TransactionNo, $vnp_Amount, $vnp_ResponseCode) {
    global $conn;
    try {
        $sql = "
            INSERT INTO transactions (order_id, vnp_txn_ref, vnp_transaction_no, vnp_amount, vnp_response_code)
            VALUES (:order_id, :vnp_txn_ref, :vnp_transaction_no, :vnp_amount, :vnp_response_code)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':vnp_txn_ref', $vnp_TxnRef, PDO::PARAM_STR);
        $stmt->bindValue(':vnp_transaction_no', $vnp_TransactionNo, PDO::PARAM_STR);
        $stmt->bindValue(':vnp_amount', $vnp_Amount, PDO::PARAM_STR);
        $stmt->bindValue(':vnp_response_code', $vnp_ResponseCode, PDO::PARAM_STR);
        $stmt->execute();
        error_log("saveVnpayTransactionDetails - Successfully saved transaction for orderId: $orderId, vnp_TxnRef: $vnp_TxnRef");
    } catch (Exception $e) {
        error_log("saveVnpayTransactionDetails - Error saving transaction for orderId: $orderId, Error: " . $e->getMessage());
        throw $e;
    }
}

function saveInvoiceDetails($order, $paymentStatus, $transactionRef) {
    global $conn;

    try {
        // Kiểm tra xem hóa đơn đã tồn tại chưa
        $sqlCheckInvoice = "SELECT id FROM invoices WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
        $stmtCheckInvoice = $conn->prepare($sqlCheckInvoice);
        $stmtCheckInvoice->bindValue(':order_id', $order['id'], PDO::PARAM_INT);
        $stmtCheckInvoice->execute();
        $existingInvoice = $stmtCheckInvoice->fetch(PDO::FETCH_ASSOC);

        if ($existingInvoice) {
            // Nếu hóa đơn đã tồn tại, cập nhật trạng thái và transaction_ref
            $sqlUpdateInvoice = "UPDATE invoices SET payment_status = :payment_status, transaction_ref = :transaction_ref WHERE order_id = :order_id";
            $stmtUpdateInvoice = $conn->prepare($sqlUpdateInvoice);
            $stmtUpdateInvoice->bindValue(':payment_status', $paymentStatus, PDO::PARAM_STR);
            $stmtUpdateInvoice->bindValue(':transaction_ref', $transactionRef, PDO::PARAM_STR);
            $stmtUpdateInvoice->bindValue(':order_id', $order['id'], PDO::PARAM_INT);
            $stmtUpdateInvoice->execute();
            error_log("saveInvoiceDetails - Updated invoice for orderId: " . $order['id']);
        } else {
            // Nếu chưa có hóa đơn, tạo mới
            $showTime = $order['date'] . ' ' . $order['time'];
            $services = $order['service_names'] ? $order['service_names'] : 'Không có';

            $sql = "
                INSERT INTO invoices (
                    order_id, user_id, movie_title, show_time, room_name, seat_numbers, 
                    services, total_amount, payment_status, transaction_ref
                ) VALUES (
                    :order_id, :user_id, :movie_title, :show_time, :room_name, :seat_numbers, 
                    :services, :total_amount, :payment_status, :transaction_ref
                )
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':order_id', $order['id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $order['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':movie_title', $order['movie_title'], PDO::PARAM_STR);
            $stmt->bindValue(':show_time', $showTime, PDO::PARAM_STR);
            $stmt->bindValue(':room_name', $order['room_name'], PDO::PARAM_STR);
            $stmt->bindValue(':seat_numbers', $order['seat_numbers'], PDO::PARAM_STR);
            $stmt->bindValue(':services', $services, PDO::PARAM_STR);
            $stmt->bindValue(':total_amount', $order['total_amount'], PDO::PARAM_STR);
            $stmt->bindValue(':payment_status', $paymentStatus, PDO::PARAM_STR);
            $stmt->bindValue(':transaction_ref', $transactionRef, PDO::PARAM_STR);
            $stmt->execute();
            error_log("saveInvoiceDetails - Successfully saved invoice for orderId: " . $order['id']);
        }
    } catch (Exception $e) {
        error_log("saveInvoiceDetails - Error saving/updating invoice for orderId: " . $order['id'] . ", Error: " . $e->getMessage());
        throw $e;
    }
}
?>