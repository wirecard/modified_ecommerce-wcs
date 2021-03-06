<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 2 (GPLv2) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

require_once(DIR_FS_CATALOG . 'includes/external/wirecardcheckoutseamless/Payment.php');

class wcs_invoice extends WirecardCheckoutSeamlessPayment
{
    protected $_defaultSortOrder = 60;
    protected $_paymenttype = WirecardCEE_Stdlib_PaymentTypeAbstract::INVOICE;
    protected $_logoFilename = 'invoice.png';
    protected $_b2b = false;
	protected $_forceSendAdditionalData = true;

    /**
     * display additional input fields on payment page
     *
     * @return array|bool
     */
    function selection()
    {
        $content = parent::selection();
        if ($content === false) {
            return false;
        }

        if (!xtc_session_is_registered('customer_id')) {
            return false;
        }

        $hasConsent = false;
        if ($this->getConfigParam('PROVIDER') == 'payolution' && $this->getConfigParam('PAYOLUTION_TERMS') == 'True') {
            $hasConsent = true;
            $fieldId = uniqid();
            $content['fields'][] = array(
                'title' => sprintf('<input id="%s" type="checkbox" name="consent" autocomplete="off" class="wcs_invoice consent"/>',
                    $fieldId),
                'field' => sprintf('<label for="%s">%s</label>', $fieldId,
                    $this->_seamless->getText('payolution_consent', $this->getPayolutionLink()))
            );
        }

        if (!$this->_b2b) {

	        $field = '<input type="hidden" name="birthdate" class="wcs_invoice birthday mandatory" data-wcs-fieldname="birthday" id="wcs-birthdate" value="" /><select style="width:30%;" name="days" id="wcs-day" required><option value="0">-</option>';
	        for($i = 1; $i <= 31; $i++) {
	        	$field .= '<option value = "' . $i . '">' . $i . '</option>';
	        }
	        $field .= '</select><select style="width:30%;" name="months" id="wcs-month" required><option value="0">-</option>';
            for($i = 1; $i <= 12; $i++){
            	$field .= '<option value="'. $i .'">'.$i.'</option>';
            }
	        $years = range(date('Y'), date('Y') - 100);
            $field .= '</select><select style="width:30%;" name="years" id="wcs-year" required><option value="0">-</option>';
            foreach ($years as $year) {
            	$field .= '<option value="'.$year.'">'.$year.'</option>';
            }
            $field .='</select>';

            $jsCode = json_encode($this->code);
            $jsMessage = json_encode($this->_seamless->getText('MIN_AGE_MESSAGE'));
            $jsHasConsent = json_encode($hasConsent);
            $jsConsentMessage = json_encode($this->_seamless->getText('CONSENT_MSG'));

            $field .= <<<HTML
        <script type="text/javascript">
        $(function () {
             
            $.fn.wcsValidateInvoice = function (messageBox) {
            var m = $('#wcs-month').val();
            var d = $('#wcs-day').val();
            var dateStr = $('#wcs-year').val() + '-' + m + '-' + d;
            
                var paymentCode = $jsCode;
                this.find('.' + paymentCode + '.birthday').val(dateStr);
                var minAge = 18;
                var msg = '';
                    
                if (!wcsValidateMinAge(dateStr, minAge)) {
                    msg = $jsMessage;
                    messageBox.append('<p class="invoice-installment">' + msg + '</p>');
                } else {
                    msg = '';
                    messageBox.empty();
                }
    
                if ($jsHasConsent)
                {
                    if (!this.find('.' + paymentCode + '.consent').attr('checked')) {
                        msg = $jsConsentMessage;
                        messageBox.append('<p>' + msg + '</p>');
                    }
                }
    
                if (msg.length) {
                    messageBox.css('display', 'block');
                    return false;
                }
    
                return true;
            };
        });
        </script>
HTML;

            $content['fields'][] = array(
                'title' => $this->_seamless->getText('birthday'),
                'field' => $field
            );
        }

        return $content;
    }

    /**
     * save additional info to session
     */
    public function pre_confirmation_check()
    {
        if (isset($_POST['wcs_invoice_birthday'])) {
            $_SESSION['wcs_birthday'] = $_POST['wcs_invoice_birthday'];
        }
    }

    /**
     * @return bool
     */
    function _preCheck()
    {
        if (!parent::_preCheck()) {
            return false;
        }

        if (!$this->invoiceInstallmentPreCheck()) {
            return false;
        }

        return true;
    }

    /**
     * whether sending of basket is forced
     *
     * @return bool
     */
    public function forceSendingBasket()
    {
    	if($this->getConfigParam('PROVIDER') == 'payolution') {
    		return false;
	    }
        return true;
    }

	/**
	 * @return bool
	 */
    public function forceSendingShippingData()
    {
	    if($this->getConfigParam('PROVIDER') == 'payolution') {
		    return false;
	    }
	    return true;
    }

    /**
     * autodeposit is not allowed with this payment
     *
     * @return bool
     */
    public function isAutoDepositAllowed()
    {
        return false;
    }

    /**
     * configuration array
     *
     * @return array
     */
    protected function _configuration()
    {
        $config = parent::_configuration();

        $config['PROVIDER'] = array(
            'configuration_value' => 'payolution',
            'set_function' => "wcs_invoice_cfg_pull_down_provider( "
        );

        $config['PAYOLUTION_TERMS'] = array(
            'configuration_value' => 'True',
            'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), '
        );

        $config['PAYOLUTION_MID'] = array(
            'configuration_value' => ''
        );

        $config['BILLINGSHIPPING_SAME'] = array(
            'configuration_value' => 'True',
            'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), '
        );

        $config['BILLING_COUNTRIES'] = array(
            'configuration_value' => 'AT,DE,CH',
        );

        $config['SHIPPING_COUNTRIES'] = array(
            'configuration_value' => 'AT,DE,CH'
        );

        $config['CURRENCIES'] = array(
            'configuration_value' => 'EUR'
        );

        $config['AMOUNT_MIN'] = array(
            'configuration_value' => '10'
        );

        $config['AMOUNT_MAX'] = array(
            'configuration_value' => '3500'
        );

        return $config;
    }
}

/**
 * invoice option list for module config
 *
 * @param string $provider
 * @param string $key
 *
 * @return string
 */
function wcs_invoice_cfg_pull_down_provider($provider, $key = '')
{
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');

    $providers = array(
        array('id' => 'payolution', 'text' => 'Payolution'),
        array('id' => 'ratepay', 'text' => 'RatePay'),
	    array('id' => 'wirecard', 'text' => 'Wirecard')
    );

    return xtc_draw_pull_down_menu($name, $providers, $provider);
}


