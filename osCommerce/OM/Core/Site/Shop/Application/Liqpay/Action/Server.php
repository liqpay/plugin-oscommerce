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
 * LiqPay API       https://www.liqpay.com/ru/doc
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
 * @author      Liqpay <support@liqpay.com>
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
            isset($_POST['amount']) &&
            isset($_POST['currency']) &&
            isset($_POST['public_key']) &&
            isset($_POST['description']) &&
            isset($_POST['order_id']) &&
            isset($_POST['type']) &&
            isset($_POST['status']) &&
            isset($_POST['transaction_id']) &&
            isset($_POST['sender_phone']);

        if (!$success) { die(); }

        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        $public_key = $_POST['public_key'];
        $description = $_POST['description'];
        $order_id = $_POST['order_id'];
        $type = $_POST['type'];
        $status = $_POST['status'];
        $transaction_id = $_POST['transaction_id'];
        $sender_phone = $_POST['sender_phone'];
        $insig = $_POST['signature'];

        $real_order_id = explode('#', $order_id);
        $real_order_id = $real_order_id[0];

        if ($real_order_id <= 0) { die(); }

        $order = new Order($real_order_id);

        if (!count($order->products)) { die(); }

        $private_key = MODULE_PAYMENT_LIQPAY_PRIVATE_KEY;

        $gensig = base64_encode(sha1(join('',compact(
            'private_key',
            'amount',
            'currency',
            'public_key',
            'order_id',
            'type',
            'description',
            'status',
            'transaction_id',
            'sender_phone'
        )),1));

        if ($insig != $gensig) { die(); }

        if ($status == 'success') {
            Order::process($real_order_id, MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID);
        }
    }
}

