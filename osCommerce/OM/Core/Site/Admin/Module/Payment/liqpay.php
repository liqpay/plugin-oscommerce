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

namespace osCommerce\OM\Core\Site\Admin\Module\Payment;

use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

/**
 * Payment method liqpay module (admin)
 *
 * @author      Liqpay <support@liqpay.ua>
 */
class liqpay extends \osCommerce\OM\Core\Site\Admin\PaymentModuleAbstract
{

    /**
     * The administrative title of the payment module
     *
     * @var string
     */
    protected $_title;


    /**
     * The administrative description of the payment module
     *
     * @var string
     */
    protected $_description;


    /**
     * The developers name
     *
     * @var string
     */
    protected $_author_name = 'Liqpay';


    /**
     * The developers address
     *
     * @var string
     */
    protected $_author_www = 'http://liqpay.ua';


    /**
     * The status of the module
     *
     * @var boolean
     */
    protected $_status = false;


    /**
     * Initialize module
     *
     * @return void
     */
    protected function initialize()
    {
        $this->_title = OSCOM::getDef('payment_liqpay_title');
        $this->_description = OSCOM::getDef('payment_liqpay_description');
        $this->_status = defined('MODULE_PAYMENT_LIQPAY_STATUS') && MODULE_PAYMENT_LIQPAY_STATUS == '1';
        $this->_sort_order = defined('MODULE_PAYMENT_LIQPAY_SORT_ORDER') ? MODULE_PAYMENT_LIQPAY_SORT_ORDER : 0;
    }


    /**
     * Checks to see if the module has been installed
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return defined('MODULE_PAYMENT_LIQPAY_STATUS');
    }


    /**
     * Installs the module
     *
     * @see \osCommerce\OM\Core\Site\Admin\PaymentModuleAbstract::install()
     */
    public function install()
    {
        parent::install();
        $OSCOM_PDO = Registry::get('PDO');
        $Qcheck = $OSCOM_PDO->prepare("SELECT max(orders_status_id) + 1 as status_id FROM :table_orders_status ");
        $Qcheck->execute();
        $res = $Qcheck->fetch();
        $status_id = (int) $res['status_id'];
        $Qcheck = $OSCOM_PDO->prepare('insert into :table_orders_status VALUES("'.$status_id.'","1","Paid [Liqpay]") ');
        $Qcheck->execute();
        $data = array(
            array(
                'title' => 'Enable Liqpay Module',
                'key' => 'MODULE_PAYMENT_LIQPAY_STATUS',
                'value' => '-1',
                'description' => 'Do you want to accept Liqpay payments?',
                'group_id' => '6',
                'use_function' => 'osc_cfg_use_get_boolean_value',
                'set_function' => 'osc_cfg_set_boolean_value(array(1, -1))',
            ),
            array(
                'title' => OSCOM::getDef('payment_liqpay_public_key'),
                'key' => 'MODULE_PAYMENT_LIQPAY_PUBLIC_KEY',
                'value' => '',
                'description' => OSCOM::getDef('payment_liqpay_public_key_desc'),
                'group_id' => '6',
            ),
            array(
                'title' => OSCOM::getDef('payment_liqpay_private_key'),
                'key' => 'MODULE_PAYMENT_LIQPAY_PRIVATE_KEY',
                'value' => '',
                'description' => OSCOM::getDef('payment_liqpay_private_key_desc'),
                'group_id' => '6',
            ),
            array(
                'title' => OSCOM::getDef('payment_liqpay_action'),
                'key' => 'MODULE_PAYMENT_LIQPAY_ACTION',
                'value' => 'https://www.liqpay.ua/api/checkout',
                'description' => '',
                'group_id' => '6',
            ),
            array(
                'title' => 'Payment Zone',
                'key' => 'MODULE_PAYMENT_LIQPAY_ZONE',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'group_id' => '6',
                'use_function' => 'osc_cfg_use_get_zone_class_title',
                'set_function' => 'osc_cfg_set_zone_classes_pull_down_menu',
            ),
            array(
                'title' => 'Set Order Status',
                'key' => 'MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID',
                'value' => $status_id,
                'description' => 'Set the status of orders made with this payment module to this value',
                'group_id' => '6',
                'use_function' => 'osc_cfg_use_get_order_status_title',
                'set_function' => 'osc_cfg_set_order_statuses_pull_down_menu',
            ),
            array(
                'title' => 'Sort order of display.',
                'key' => 'MODULE_PAYMENT_LIQPAY_SORT_ORDER',
                'value' => '1',
                'description' => 'Sort order of display. Lowest is displayed first.',
                'group_id' => '6',
            ),
        );
        OSCOM::callDB('Admin\InsertConfigurationParameters', $data, 'Site');
    }


    /**
     * Remove the module
     *
     * @access public
     */
    public function remove()
    {
        parent::remove();
        $OSCOM_PDO = Registry::get('PDO');
        $Qcheck = $OSCOM_PDO->prepare("DELETE FROM :table_orders_status WHERE orders_status_name = 'Paid [Liqpay]' ");
        $Qcheck->execute();
    }


    /**
     * Return the configuration parameter keys in an array
     *
     * @return array
     */
    public function getKeys()
    {
        return array(
            'MODULE_PAYMENT_LIQPAY_STATUS',
            'MODULE_PAYMENT_LIQPAY_PUBLIC_KEY',
            'MODULE_PAYMENT_LIQPAY_PRIVATE_KEY',
            'MODULE_PAYMENT_LIQPAY_ACTION',
            'MODULE_PAYMENT_LIQPAY_ZONE',
            'MODULE_PAYMENT_LIQPAY_ORDER_STATUS_ID',
            'MODULE_PAYMENT_LIQPAY_SORT_ORDER',
        );
    }
}
