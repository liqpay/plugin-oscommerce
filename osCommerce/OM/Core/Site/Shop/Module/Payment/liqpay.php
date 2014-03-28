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

namespace osCommerce\OM\Core\Site\Shop\Module\Payment;

use osCommerce\OM\Core\HttpRequest;
use osCommerce\OM\Core\Mail;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;
use osCommerce\OM\Core\Site\Shop\Order;
use osCommerce\OM\Core\Site\Shop\Shipping;

/**
 * Payment method liqpay module (shop)
 *
 * @author      Liqpay <support@liqpay.com>
 */
class liqpay extends \osCommerce\OM\Core\Site\Shop\PaymentModuleAbstract
{

    protected $_allowCurrencyCode = array('EUR','UAH','USD','RUB','RUR');


    /**
     * Initialize module
     *
     * @return void
     */
    protected function initialize()
    {
        $OSCOM_PDO = Registry::get('PDO');
        $OSCOM_ShoppingCart = Registry::get('ShoppingCart');

        //$this->_api_version = '3.0-2';

        $this->_title = OSCOM::getDef('payment_liqpay_title');
        $this->_method_title = OSCOM::getDef('payment_liqpay_method_title');
        $this->_status = MODULE_PAYMENT_LIQPAY_STATUS == '1';
        $this->_sort_order = MODULE_PAYMENT_LIQPAY_SORT_ORDER;

        if ($this->_status) {
            if ((int)MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID;
            }

            if ((int)MODULE_PAYMENT_LIQPAY_ZONE > 0) {
                $this->_status = false;

                $sql = '
                    select
                        zone_id
                    from
                        :table_zones_to_geo_zones
                    where
                        geo_zone_id = :geo_zone_id and
                        zone_country_id = :zone_country_id
                    order
                        by zone_id
                ';

                $Qcheck = $OSCOM_PDO->prepare($sql);

                $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_LIQPAY_ZONE);
                $Qcheck->bindInt(':zone_country_id', $OSCOM_ShoppingCart->getBillingAddress('country_id'));
                $Qcheck->execute();

                while ($Qcheck->fetch()) {
                    if ($Qcheck->valueInt('zone_id') < 1) {
                        $this->_status = true;
                        break;
                    }
                    elseif ($Qcheck->valueInt('zone_id') == $OSCOM_ShoppingCart->getBillingAddress('zone_id')) {
                        $this->_status = true;
                        break;
                    }
                }
            }
        }
    }


    /**
     * Pre confirmation check
     *
     * @return boolean
     */
    public function preConfirmationCheck()
    {
        return false;
    }


    /**
     * Get process button
     *
     * @return string
     */
    public function getProcessButton()
    {
        return '
            <script>
            $("form[name=checkout_confirmation]").submit(function(){
                var checkoutConfirmation = $(this);
                $.ajax({
                    url: $(this).attr("action"),
                    success: function(data, textStatus, jqXHR){
                        var formLiqpay = $(data);

                        checkoutConfirmation.after(formLiqpay);
                        formLiqpay.submit();
                    },
                    dataType: "html"
                });
                return false;
            });
            </script>';
    }


    /**
     * Process order
     *
     * @return void
     */
    public function process()
    {
        $this->_order_id = Order::insert(); print_r($this->_order_status);
        Order::process($this->_order_id, $this->_order_status);

        $OSCOM_ShoppingCart = Registry::get('ShoppingCart');
        $amount = (float) $OSCOM_ShoppingCart->getTotal();
        $OSCOM_ShoppingCart->reset(true);

        $action = MODULE_PAYMENT_LIQPAY_ACTION;
        $public_key = MODULE_PAYMENT_LIQPAY_PUBLIC_KEY;
        $private_key = MODULE_PAYMENT_LIQPAY_PRIVATE_KEY;
        $type = 'buy';

        $order = new Order($this->_order_id);

        $currency = $order->info['currency'];
        $order_id = $this->_order_id;
        $description = 'Order #'.$order_id;
        $result_url = OSCOM::getLink(null, 'Account&Orders');
        $server_url = OSCOM::getLink(null, 'Liqpay&Server');;

        $order_id .= '#'.time();

        $signature = base64_encode(sha1(join('',compact(
            'private_key',
            'amount',
            'currency',
            'public_key',
            'order_id',
            'type',
            'description',
            'result_url',
            'server_url'
        )),1));

        $language = 'ru';

        if (!in_array($currency, $this->_allowCurrencyCode)) {
            echo 'Incorrect currency!';
            exit;
        }

        if ($currency == 'RUR') { $currency = 'RUB'; }

        echo '
          <form method="POST" action="'.$action.'" id="liqpay" accept-charset="utf-8">
              <input type="hidden" name="public_key" value="'.$public_key.'" />
              <input type="hidden" name="amount" value="'.$amount.'" />
              <input type="hidden" name="currency" value="'.$currency.'" />
              <input type="hidden" name="description" value="'.$description.'" />
              <input type="hidden" name="order_id" value="'.$order_id.'" />
              <input type="hidden" name="result_url" value="'.$result_url.'" />
              <input type="hidden" name="server_url" value="'.$server_url.'" />
              <input type="hidden" name="type" value="'.$type.'" />
              <input type="hidden" name="signature" value="'.$signature.'" />
              <input type="hidden" name="language" value="'.$language.'" />
          </form>
        ';
        exit;
    }


    /**
     * Show error
     *
     * @return void
     */
    private function showerror($err)
    {
        OSCOM::redirect(OSCOM::getLink(null, 'Cart', 'error_message=' . urlencode(stripslashes($err)), 'SSL'));
    }

}