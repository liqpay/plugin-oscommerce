<?php
/**
 * Liqpay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        Liqpay
 * @package         Payment
 * @version         0.0.1
 * @author          Liqpay
 * @copyright       Copyright (c) 2014 Liqpay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * osCommerce       3.0.2
 * LiqPay API       https://www.liqpay.ua/documentation/ru
 *
 */

namespace osCommerce\OM\Core\Site\Shop\Application\Liqpay\Action;

use osCommerce\OM\Core\ApplicationAbstract;
use osCommerce\OM\Core\Registry;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Site\Shop\Order;

/**
 * Action server-server
 *
 * @author      Liqpay <support@liqpay.ua>
 */
class Server
{

    /**
     * Execute
     *
     * @return void
     */
    public static function execute(ApplicationAbstract $application)
    {
        $success =
            isset($_POST['data']) &&
            isset($_POST['signature']);

        if (!$success) { die(); }
        
        $data                = $_POST['data'];
        $parsed_data         = json_decode(base64_decode($data));
        $received_signature  = $_POST['signature'];
        $received_public_key = $parsed_data['public_key'];
        $order_id            = $parsed_data['order_id'];
        $status              = $parsed_data['status'];
        $sender_phone        = $parsed_data['sender_phone'];
        $amount              = $parsed_data['amount'];
        $currency            = $parsed_data['currency'];
        $transaction_id      = $parsed_data['transaction_id'];

        $real_order_id = explode('#', $order_id);
        $real_order_id = $real_order_id[0];

        if ($real_order_id <= 0) { die(); }

        $order = new Order($real_order_id);

        if (!count($order->products)) { die(); }

        $private_key = MODULE_PAYMENT_LIQPAY_PRIVATE_KEY;
        $public_key  = MODULE_PAYMENT_LIQPAY_PUBLIC_KEY;

        $generated_signature = base64_encode(sha1($private_key.$data.$private_key, 1));

        if ($received_signature != $generated_signature || $public_key != $received_public_key) { die(); }

        if ($status == 'success') {
            Order::process($real_order_id, MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID);
        }
    }
}

