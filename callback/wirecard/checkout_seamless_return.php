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


chdir('../../');
include('includes/application_top.php');

require_once('includes/external/wirecardcheckoutseamless/Seamless.php');

$plugin = new WirecardCheckoutSeamless();
$redirectUrl = $plugin->back();

if (!strlen($redirectUrl)) {
    $redirectUrl = xtc_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false);
}

$smarty = new Smarty;
$smarty->assign('language', $_SESSION['language']);

require(DIR_WS_INCLUDES . 'header.php');

echo "<h3>" . $plugin->getText('redirection_header') . "</h3>";
echo "<p>" . $plugin->getText('redirection_text') . "<a href='". $redirectUrl ."' target='_parent'>" . $plugin->getText('redirection_here') . "</a></p>";
printf(<<<HTML
<script type="text/javascript">
	function iframeBreakout()
    {
		parent.location.href = %s;
    }
    iframeBreakout();
</script>


HTML
    , json_encode($redirectUrl));

require('includes/application_bottom.php');
