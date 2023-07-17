<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

use Objectiv\Plugins\Checkout\Action\AccountExistsAction;
use Objectiv\Plugins\Checkout\Action\AddToCartAction;
use Objectiv\Plugins\Checkout\Action\GetVariationForm;
use Objectiv\Plugins\Checkout\Action\CompleteOrderAction;
use Objectiv\Plugins\Checkout\Action\LogInAction;
use Objectiv\Plugins\Checkout\Action\RemoveCouponAction;
use Objectiv\Plugins\Checkout\Action\UpdateCartItemVariation;
use Objectiv\Plugins\Checkout\Action\UpdateCheckoutAction;
use Objectiv\Plugins\Checkout\Action\UpdatePaymentMethodAction;
use Objectiv\Plugins\Checkout\Action\UpdateSideCart;
use Objectiv\Plugins\Checkout\Action\ValidateEmailDomainAction;
use Objectiv\Plugins\Checkout\Action\ValidatePostcodeAction;
use Objectiv\Plugins\Checkout\Admin\Pages\Integrations;
use Objectiv\Plugins\Checkout\Admin\Pages\LocalPickup;
use Objectiv\Plugins\Checkout\Admin\Pages\SideCart;
use Objectiv\Plugins\Checkout\Admin\WelcomeScreenActivationRedirector;
use Objectiv\Plugins\Checkout\API\AbandonedCartRecoveryReportAPI;
use Objectiv\Plugins\Checkout\API\AbandonedCartsAPI;
use Objectiv\Plugins\Checkout\API\AfterCheckoutBumpProductFormAPI;
use Objectiv\Plugins\Checkout\API\GetVariationFormAPI;
use Objectiv\Plugins\Checkout\API\ModalOrderBumpProductFormAPI;
use Objectiv\Plugins\Checkout\API\SettingsAPI;
use Objectiv\Plugins\Checkout\CartImageSizeAdder;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\AfterPayKrokedil;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\AmazonPayV1;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\Braintree;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\BraintreeForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\InpsydePayPalPlus;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\KlarnaCheckout;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\KlarnaPayment;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\KlarnaPayment3;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\NMI;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\PaymentPluginsPayPal;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\PayPalCheckout;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\PayPalForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\PayPalPlusCw;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\PostFinance;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\Square;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\Stripe;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\StripeWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\Vipps;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\WooCommercePayPalPayments;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\WooCommercePensoPay;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\WooSquarePro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ActiveCampaign;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ApplyOnline;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\AstraAddon;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\BeaverThemer;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CartFlows;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CheckoutAddressAutoComplete;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CashierForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CheckoutManager;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Chronopost;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CO2OK;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CoderockzWooDelivery;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CraftyClicks;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CSSHero;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\CURCYWooCommerceMultiCurrency;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\DiviUltimateFooter;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\DiviUltimateHeader;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\DonationForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ElementorPro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\EnhancedEcommerceGoogleAnalytics;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\EUVATNumber;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ExtraCheckoutFieldsBrazil;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\FacebookForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Fattureincloud;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\FreeGiftsforWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\GermanMarket;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\GoogleAnalyticsProV1;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\IconicWooCommerceDeliverySlots;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\IndeedAffiliatePro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\JupiterXCore;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Klaviyo;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MailerLite;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MartfuryAddons;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MixPanel;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MondialRelay;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MyCredPartialPayments;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MyParcel;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\MyShipper;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\NextGenGallery;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\NIFPortugal;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\NLPostcodeChecker;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\OneClickUpsells;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\OrderDeliveryDate;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\OrderDeliveryDateLite;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\OxygenBuilder;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\PixelCaffeine;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\PortugalVaspKios;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\PostNL;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\PostNL4;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\PWGiftCardsPro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SalientWPBakery;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SavedAddressesForWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SendCloud;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ShipMondo;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SkyVergeCheckoutAddons;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\StrollikCore;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SUMOPaymentPlans;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\SUMOSubscriptions;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ThemeHighCheckoutFieldEditorPro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Tickera;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\TranslatePress;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\UltimateRewardsPoints;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\UpsellOrderBumpOffer;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\UpSolutionCore;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WCFieldFactory;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WCPont;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Webshipper;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\Weglot;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceAddressValidation;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceAdvancedMessages;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceCarrierAgents;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceCheckoutFieldEditor;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceCore;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceEUUKVATCompliancePremium;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceExtendedCouponFeaturesPro;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceGermanized;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceGermanMarket;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceGiftCards;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceOrderDelivery;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommercePointsandRewards;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommercePriceBasedOnCountry;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceProductBundles;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceProductRecommendations;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceServices;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceShipmentTracking;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceSmartCoupons;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceSubscriptionGifting;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceSubscriptions;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceTipping;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooFunnelsOrderBumps;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WPCProductBundles;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WPRocket;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\YITHCompositeProducts;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\YITHDeliveryDate;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ThemeHighCheckoutFieldEditor;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\YITHPointsAndRewards;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\RouteApp;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Astra;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Atelier;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Atik;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Avada;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Barberry;
use Objectiv\Plugins\Checkout\Compatibility\Themes\BeaverBuilder;
use Objectiv\Plugins\Checkout\Compatibility\Themes\BeTheme;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Blaszok;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Divi;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Electro;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Flatsome;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Flevr;
use Objectiv\Plugins\Checkout\Compatibility\Themes\FuelThemes;
use Objectiv\Plugins\Checkout\Compatibility\Themes\GeneratePress;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Genesis;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Jupiter;
use Objectiv\Plugins\Checkout\Compatibility\Themes\JupiterX;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Konte;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Listable;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Metro;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Minimog;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Neve;
use Objectiv\Plugins\Checkout\Compatibility\Themes\OceanWP;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Optimizer;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Porto;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Pro;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Savoy;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Shoptimizer;
use Objectiv\Plugins\Checkout\Compatibility\Themes\SpaSalonPro;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Stockie;
use Objectiv\Plugins\Checkout\Compatibility\Themes\The7;
use Objectiv\Plugins\Checkout\Compatibility\Themes\TheBox;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Thrive;
use Objectiv\Plugins\Checkout\Compatibility\Themes\TMOrganik;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Tokoo;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Uncode;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Verso;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Zidane;
use Objectiv\Plugins\Checkout\Compatibility\Themes\Medizin;
use Objectiv\Plugins\Checkout\Admin\DataUpgrader;
use Objectiv\Plugins\Checkout\Admin\Notices\CompatibilityNotice;
use Objectiv\Plugins\Checkout\Admin\Notices\InvalidLicenseKeyNotice;
use Objectiv\Plugins\Checkout\Admin\Notices\TemplateDisabledNotice;
use Objectiv\Plugins\Checkout\Admin\Notices\WelcomeNotice;
use Objectiv\Plugins\Checkout\Admin\Pages\Advanced;
use Objectiv\Plugins\Checkout\Admin\Pages\Appearance;
use Objectiv\Plugins\Checkout\Admin\Pages\WooCommercePages;
use Objectiv\Plugins\Checkout\Admin\Pages\General;
use Objectiv\Plugins\Checkout\Customizer;
use Objectiv\Plugins\Checkout\Features\AbandonedCartRecovery;
use Objectiv\Plugins\Checkout\Features\Pickup;
use Objectiv\Plugins\Checkout\Features\HideOptionalAddressFields;
use Objectiv\Plugins\Checkout\FormFieldAugmenter;
use Objectiv\Plugins\Checkout\Model\Bumps\BumpAbstract;
use Objectiv\Plugins\Checkout\Model\Template;
use Objectiv\Plugins\Checkout\Features\CartEditingAtCheckout;
use Objectiv\Plugins\Checkout\Managers\PlanManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Managers\UpdatesManager;
use Objectiv\Plugins\Checkout\Features\OrderBumps;
use Objectiv\Plugins\Checkout\Stats\StatCollection;
use Objectiv\Plugins\Checkout\Admin\Pages\PageController;
use Objectiv\Plugins\Checkout\Admin\Pages\Support;
use Objectiv\Plugins\Checkout\Admin\ShippingPhoneController;
use Objectiv\Plugins\Checkout\Admin\WooCommerceAdminScreenAugmenter;
use Objectiv\Plugins\Checkout\TrustBadgeImageSizeAdder;
use Objectiv\Plugins\Checkout\Features\TrustBadges;
use Objectiv\Plugins\Checkout\Features\PhpSnippets;
use Objectiv\Plugins\Checkout\Features\GoogleAddressAutocomplete;
use Objectiv\Plugins\Checkout\Features\FetchifyAddressAutocomplete;
use Objectiv\Plugins\Checkout\Features\OrderReviewStep;
use Objectiv\Plugins\Checkout\Features\OnePageCheckout;
use Objectiv\Plugins\Checkout\Features\InternationalPhoneField;
use Objectiv\Plugins\Checkout\AddressFieldsAugmenter;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\OnePageCheckout as WooCommerceOnePageCheckout;
use Objectiv\Plugins\Checkout\Admin\AdminPluginsPageManager;
use Objectiv\Plugins\Checkout\Features\SmartyStreets;
use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;
use Objectiv\Plugins\Checkout\Action\LostPasswordAction;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\AmazonPay;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\AmazonPayLegacy;
use Objectiv\Plugins\Checkout\Admin\Pages\OrderBumps as PagesOrderBumps;
use Objectiv\Plugins\Checkout\Admin\Pages\PickupLocations;
use Objectiv\Plugins\Checkout\PhpErrorOutputSuppressor;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommercePakettikauppa;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooFinvoicer;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\ConvertKitforWooCommerce;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\WooCommercePayments;
use Objectiv\Plugins\Checkout\Compatibility\Gateways\ResursBank;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\EUVATAssistant;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\WooCommerceMemberships;
use Objectiv\Plugins\Checkout\Compatibility\Plugins\BigBlue;
use Objectiv\Plugins\Checkout\DefaultSettingsSetter;
use Objectiv\Plugins\Checkout\Admin\Pages\TrustBadges as TrustBadgesAdminPage;

// Setup our Singletons here
$settings_manager = SettingsManager::instance();
$settings_manager->init();

AddressFieldsAugmenter::instance();

$stats_collection = StatCollection::instance();
$stats_collection->init();

UpdatesManager::instance()->init( SettingsManager::instance()->get_setting( 'enable_beta_version_updates' ) === 'yes' );

// TODO: This should eventually be removed...right? Probably want to grandfather people who have had this active and only disable for new installs
( new PhpErrorOutputSuppressor() )->init();

/**
 * REST API Endpoints
 */

add_filter( 'woocommerce_is_rest_api_request', function( $is_rest_api_request ) {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return $is_rest_api_request;
	}

	// Bail early if this is not our request.
	if ( false === strpos( $_SERVER['REQUEST_URI'], 'checkoutwc' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $is_rest_api_request;
	}

	return false;
} );


new AbandonedCartRecoveryReportAPI();
new AbandonedCartsAPI();
new SettingsAPI();
new ModalOrderBumpProductFormAPI();
new AfterCheckoutBumpProductFormAPI();
new GetVariationFormAPI();

/**
 * Plan Availability
 */
$has_premium_plan    = PlanManager::has_premium_plan();
$required_plans_list = PlanManager::get_english_list_of_required_plans_html();

$acr = new AbandonedCartRecovery(
	$settings_manager->get_setting( 'enable_acr' ) === 'yes',
	$has_premium_plan,
	$required_plans_list,
	$settings_manager
);

$acr->init();

/**
 * Admin Settings Pages
 */
// Handles Parent Menu and General Menu
$appearance_admin_page = new Appearance( $settings_manager );
$general_admin_page    = new General( $appearance_admin_page );
$integrations_page     = new Integrations();

// These priorities start at 70 because General sets up the main menu on $priority - 5
// 65 is our target priority for our admin parent menu
$pages = array(
	$general_admin_page->set_priority( 70 ),
	$appearance_admin_page->set_priority( 72 ),
	( new WooCommercePages() )->set_priority( 75 ),
	( new SideCart() )->set_priority( 80 ),
	( new TrustBadgesAdminPage() )->set_priority( 90 ),
	( new PagesOrderBumps( BumpAbstract::get_post_type(), $required_plans_list, $has_premium_plan ) )->set_priority( 95 ),
	( new LocalPickup() )->set_priority( 102 ),
	( new PickupLocations( Pickup::get_post_type(), true ) )->set_priority( 103 ),
	( new \Objectiv\Plugins\Checkout\Admin\Pages\AbandonedCartRecovery( $acr ) )->set_priority( 104),
	$integrations_page->set_priority( 105 ),
	( new Advanced() )->set_priority( 110 ),
	( new Support() )->set_priority( 120 ),
);

$page_controller = new PageController( ...$pages );
$page_controller->init();

// Note: The active template has to be setup early because admin pages use it to store active theme specific settings
// The fact that a "get" function is causing outside changes in the ether is an indication this should be refactored.
$active_template = cfw_get_active_template();
add_action( 'cfw_do_plugin_activation', array( $active_template, 'run_on_plugin_activation' ) );

/**
 * Premium Features Instantiation
 */
// This should always be first so that it runs before other features
$php_snippets = new PhpSnippets(
	! is_admin(),
	true,
	'',
	$settings_manager,
	$settings_manager->get_field_name( 'php_snippets' )
);
$php_snippets->init();

$order_bumps_feature = new OrderBumps( $settings_manager->get_setting( 'enable_order_bumps' ) === 'yes', $has_premium_plan, $required_plans_list, $settings_manager );
$order_bumps_feature->init();
add_action( 'cfw_angelleye_paypal_ec_is_express_checkout', array( $order_bumps_feature, 'unhook_order_bumps_output' ) );

$order_review_step = new OrderReviewStep(
	$settings_manager->get_setting( 'enable_order_review_step' ) === 'yes',
	true,
	'',
	$settings_manager
);
$order_review_step->init();
add_action( 'cfw_angelleye_paypal_ec_is_express_checkout', array( $order_review_step, 'unhook' ) );

$one_page_checkout = new OnePageCheckout(
	$settings_manager->get_setting( 'enable_one_page_checkout' ) === 'yes',
	true,
	'',
	$settings_manager
);
$one_page_checkout->init();

$address_autocomplete = new GoogleAddressAutocomplete(
	$settings_manager->get_setting( 'enable_address_autocomplete' ) === 'yes',
	true,
	'',
	$settings_manager,
	$integrations_page->get_url()
);
$address_autocomplete->init();

$fetchify_address_autocomplete = new FetchifyAddressAutocomplete(
	$settings_manager->get_setting( 'enable_fetchify_address_autocomplete' ) === 'yes',
	true,
	'',
	$settings_manager
);
$fetchify_address_autocomplete->init();

$trust_badges = new TrustBadges(
	$settings_manager->get_setting( 'enable_trust_badges' ) === 'yes',
	true,
	'',
	$settings_manager,
	$settings_manager->get_field_name( 'trust_badges' )
);
$trust_badges->init();

$smartystreets_address_validation = new SmartyStreets(
	$settings_manager->get_setting( 'enable_smartystreets_integration' ) === 'yes',
	$has_premium_plan,
	$required_plans_list,
	$settings_manager
);
$smartystreets_address_validation->init();

$cart_editing = new CartEditingAtCheckout(
	$settings_manager->get_setting( 'enable_cart_editing' ) === 'yes',
	true,
	'',
	$settings_manager
);
$cart_editing->init();

$international_phone_field = new InternationalPhoneField(
	$settings_manager->get_setting( 'enable_international_phone_field' ) === 'yes' && cfw_is_phone_fields_enabled(),
	true,
	'',
	$settings_manager
);
$international_phone_field->init();

$side_cart_enabled = UpdatesManager::instance()->is_license_valid() && $settings_manager->get_setting( 'enable' ) === 'yes' && $settings_manager->get_setting( 'enable_side_cart' ) === 'yes';

$side_cart = new \Objectiv\Plugins\Checkout\Features\SideCart(
	$side_cart_enabled,
	$has_premium_plan,
	$required_plans_list,
	$settings_manager,
	$order_bumps_feature
);

$side_cart->init();

$pickup = new Pickup(
	$settings_manager->get_setting( 'enable_pickup' ) === 'yes',
	$has_premium_plan,
	$required_plans_list,
	$settings_manager
);
$pickup->init();

$hide_optional_address_fields = new HideOptionalAddressFields(
	'yes' === SettingsManager::instance()->get_setting( 'hide_optional_address_fields_behind_link' ),
	true,
	'',
	$settings_manager
);

$hide_optional_address_fields->init();

add_filter(
	'cfw_get_billing_checkout_fields',
	function ( $fields ) {
		$original_fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_address_1',
			'billing_address_2',
			'billing_company',
			'billing_country',
			'billing_postcode',
			'billing_state',
			'billing_city',
			'billing_phone',
		);

		$enabled_fields = (array) SettingsManager::instance()->get_setting( 'enabled_billing_address_fields' );

		foreach ( $original_fields as $field_key ) {
			if ( ! in_array( $field_key, $enabled_fields, true ) ) {
				unset( $fields[ $field_key ] );
			}
		}
		return $fields;
	},
	100
);

add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		$original_fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_address_1',
			'billing_address_2',
			'billing_company',
			'billing_country',
			'billing_postcode',
			'billing_state',
			'billing_city',
			'billing_phone',
		);

		$enabled_fields = (array) SettingsManager::instance()->get_setting( 'enabled_billing_address_fields' );

		foreach ( $original_fields as $field_key ) {
			if ( ! in_array( $field_key, $enabled_fields, true ) ) {
				if ( isset( $fields['billing'][ $field_key ] ) ) {
					$fields['billing'][ $field_key ]['required'] = false;
				}
			}
		}

		return $fields;
	}
);

/**
 * Setup Compatibility Modules
 */

( new ResursBank() )->init();
( new EUVATAssistant() )->init();
( new WPRocket() )->init();
( new FreeGiftsforWooCommerce() )->init( $side_cart_enabled );
( new YITHCompositeProducts() )->init();
( new WooCommerceMemberships() )->init();
( new YITHPointsAndRewards() )->init();
( new Metro() )->init();

$compatibility_modules = array(
	// Plugins
	WooCommerceCore::instance(),
	MixPanel::instance(),
	SkyVergeCheckoutAddons::instance(),
	Tickera::instance(),
	PixelCaffeine::instance(),
	OneClickUpsells::instance(),
	GoogleAnalyticsProV1::instance(),
	WooCommerceOnePageCheckout::instance(),
	WooCommerceSubscriptions::instance(),
	WooCommerceSubscriptionGifting::instance(),
	WooCommerceGermanized::instance()->setup( $order_review_step->is_active() && ! $one_page_checkout->is_active() ),
	CraftyClicks::instance(),
	CheckoutManager::instance(),
	CheckoutAddressAutoComplete::instance(),
	NLPostcodeChecker::instance(),
	PostNL::instance(),
	PostNL4::instance(),
	ActiveCampaign::instance(),
	UltimateRewardsPoints::instance(),
	WooCommerceSmartCoupons::instance(),
	EUVATNumber::instance(),
	FacebookForWooCommerce::instance(),
	Webshipper::instance(),
	OrderDeliveryDate::instance(),
	OrderDeliveryDateLite::instance(),
	WooFunnelsOrderBumps::instance(),
	MartfuryAddons::instance(),
	WCFieldFactory::instance(),
	MondialRelay::instance(),
	SUMOPaymentPlans::instance(),
	WooCommerceAddressValidation::instance(),
	ElementorPro::instance(),
	SendCloud::instance(),
	CO2OK::instance(),
	DiviUltimateHeader::instance(),
	DiviUltimateFooter::instance(),
	ExtraCheckoutFieldsBrazil::instance(),
	MyCredPartialPayments::instance(),
	GermanMarket::instance(),
	StrollikCore::instance(),
	WooCommerceCheckoutFieldEditor::instance(),
	IndeedAffiliatePro::instance(),
	ShipMondo::instance(),
	Chronopost::instance(),
	JupiterXCore::instance(),
	OxygenBuilder::instance(),
	Fattureincloud::instance(),
	CSSHero::instance(),
	NIFPortugal::instance(),
	WooCommerceOrderDelivery::instance(),
	PortugalVaspKios::instance(),
	WPCProductBundles::instance(),
	YITHDeliveryDate::instance(),
	CartFlows::instance(),
	PWGiftCardsPro::instance(),
	NextGenGallery::instance(),
	Weglot::instance(),
	WooCommerceGiftCards::instance(), // WooCommerce Gift Cards (official)
	BeaverThemer::instance(),
	WooCommerceCarrierAgents::instance(),
	WooCommerceServices::instance(),
	SalientWPBakery::instance(),
	WCPont::instance(),
	MailerLite::instance(),
	ApplyOnline::instance(),
	WooCommerceExtendedCouponFeaturesPro::instance(),
	WooCommerceGermanMarket::instance(),
	IconicWooCommerceDeliverySlots::instance(),
	MyShipper::instance(),
	EnhancedEcommerceGoogleAnalytics::instance(),
	WooCommercePointsandRewards::instance(),
	SavedAddressesForWooCommerce::instance(),
	TranslatePress::instance(),
	SUMOSubscriptions::instance(),
	UpsellOrderBumpOffer::instance(),
	WooCommerceAdvancedMessages::instance(),
	Klaviyo::instance(),
	ThemeHighCheckoutFieldEditor::instance(),
	ThemeHighCheckoutFieldEditorPro::instance(),
	WooCommercePakettikauppa::instance(),
	WooFinvoicer::instance(),
	WooCommerceShipmentTracking::instance(),
	WooCommerceTipping::instance(),
	WooCommerceProductBundles::instance(),
	MyParcel::instance(),
	CoderockzWooDelivery::instance(),
	CURCYWooCommerceMultiCurrency::instance(),
	WooCommerceProductRecommendations::instance()->setup( $side_cart_enabled ),
	CashierForWooCommerce::instance(),
	BigBlue::instance(),
	DonationForWooCommerce::instance(),
	WooCommercePriceBasedOnCountry::instance(),

	// Gateways
	PayPalCheckout::instance(),
	Stripe::instance(),
	PayPalForWooCommerce::instance(),
	Braintree::instance(),
	BraintreeForWooCommerce::instance(),
	AmazonPay::instance(),
	AmazonPayLegacy::instance(),
	AmazonPayV1::instance(),
	KlarnaCheckout::instance(),
	KlarnaPayment::instance(),
	KlarnaPayment3::instance(),
	AfterPayKrokedil::instance(),
	InpsydePayPalPlus::instance(),
	WooSquarePro::instance(),
	PayPalPlusCw::instance(),
	PostFinance::instance(),
	Square::instance(),
	StripeWooCommerce::instance(),
	WooCommercePensoPay::instance(),
	Vipps::instance(),
	ConvertKitforWooCommerce::instance(),
	WooCommercePayments::instance(),
	WooCommercePayPalPayments::instance(),
	UpSolutionCore::instance(),
	WooCommerceEUUKVATCompliancePremium::instance(),
	NMI::instance(),
	PaymentPluginsPayPal::instance(),
	RouteApp::instance(),

	// Themes
	Avada::instance(),
	Porto::instance(),
	GeneratePress::instance(),
	TMOrganik::instance(),
	BeaverBuilder::instance(),
	Astra::instance(),
	Savoy::instance(),
	OceanWP::instance(),
	Atelier::instance(),
	Jupiter::instance(),
	The7::instance(),
	Zidane::instance(),
	Atik::instance(),
	Optimizer::instance(),
	Verso::instance(),
	Listable::instance(),
	Flevr::instance(),
	Divi::instance(),
	Electro::instance(),
	JupiterX::instance(),
	Blaszok::instance(),
	Konte::instance(),
	Genesis::instance(),
	TheBox::instance(),
	Barberry::instance(),
	Stockie::instance(),
	Tokoo::instance(),
	FuelThemes::instance(),
	SpaSalonPro::instance(),
	Shoptimizer::instance(),
	Flatsome::instance(),
	Pro::instance(),
	Uncode::instance(),
	Neve::instance(),
	AstraAddon::instance(),
	Thrive::instance(),
	BeTheme::instance(),
	Minimog::instance(),
	Medizin::instance(),
);

add_filter( 'cfw_blocked_style_handles', 'cfw_remove_theme_styles', 10, 1 );
add_filter( 'cfw_blocked_script_handles', 'cfw_remove_theme_scripts', 10, 1 );

/**
 * WP Admin Notices
 */
( new TemplateDisabledNotice() )->init();
( new InvalidLicenseKeyNotice() )->init();
( new CompatibilityNotice() )->init();
( new WelcomeNotice() )->init();

/**
 * Misc Admin Stuff That Defies Cogent Categorization For The Moment
 */
( new AdminPluginsPageManager( $general_admin_page->get_url() ) )->init();
( new ShippingPhoneController() )->init();
( new WooCommerceAdminScreenAugmenter() )->init();
CartFlows::instance()->admin_init();

add_action( 'admin_init', function() {
	if ( wp_doing_ajax() || defined( 'WC_DOING_AJAX' ) ) {
		return;
	}

	if ( !is_admin() ) {
		return;
	}

	if ( ! get_transient( '_cfw_80_upgrade_welcome_redirect' ) ) {
		return;
	}

	delete_transient( '_cfw_80_upgrade_welcome_redirect' );

	// Redirect to 8.0 upgrade screen
	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'cfw-settings',
				'upgrade' => '80',
			),
			admin_url( 'admin.php' )
		)
	);
} );

add_action( 'init', function() {
	if ( isset( $_GET['force-v8-data-remigrate'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'force-v8-data-remigrate' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings_obj = SettingsManager::instance()->get_settings_obj( true );

		if ( ! empty( $settings_obj ) ) {
			foreach ( $settings_obj as $key => $value ) {
				// Have to use update_setting because of edge case where someone upgrades by activating the plugin
				SettingsManager::instance()->update_setting( $key, $value );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'cfw-settings-advanced',
					'force-v8-data-remigrate-done' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
	}
} );

add_action( 'init', function() {
	if ( isset( $_GET['clear-all-acr-carts'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'clear-all-acr-carts' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		global $wpdb;

		$table_name = $wpdb->prefix . 'cfw_acr_carts';
		$wpdb->query( "DELETE FROM $table_name WHERE id > 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
} );

add_action( 'init', array( new DataUpgrader(), 'init' ) );
add_action( 'init', array( new CartImageSizeAdder(), 'add_cart_image_size' ) );
add_action( 'init', array( new TrustBadgeImageSizeAdder(), 'add_trust_badge_image_size' ) );

/**
 * Customizer Handler
 */
( new Customizer( $appearance_admin_page->get_theme_color_settings() ) )->init();

add_action(
	'after_setup_theme',
	function() {
		// Menu location for template footer
		register_nav_menu( 'cfw-footer-menu', cfw__( 'CheckoutWC: Footer', 'checkout-wc' ) );
	}
);

add_action(
	'plugins_loaded',
	function() use ( $compatibility_modules ) {
		/**
		 * Compatibility Pre-init
		 *
		 * Turns out running this on init causes problems, and plugins_loaded is also too late
		 * Obviously this is something we need to cleanup in the future, but doing this
		 * here eliminates some edge case bugs.
		 */
		/** @var CompatibilityAbstract $module */
		foreach ( $compatibility_modules as $module ) {
			$module->pre_init();
		}
	},
	-1000
);

add_action( 'cfw_do_plugin_activation', array( new DefaultSettingsSetter(), 'init' ) );

register_activation_hook(
	CFW_MAIN_FILE,
	function() {
		// Welcome screen transient
		set_transient( '_cfw_welcome_screen_activation_redirect', true, 30 );

		do_action( 'cfw_do_plugin_activation' );
	}
);

register_deactivation_hook(
	CFW_MAIN_FILE,
	function() {
		do_action( 'cfw_do_plugin_deactivation' );
	}
);

add_filter(
	'cfw_disable_woocommerce_gift_cards_compatibility',
	function() {
		return class_exists( '\\WC_GC_Coupon_Input' );
	}
);

if ( SettingsManager::instance()->get_setting( 'skip_cart_step' ) === 'yes' ) {
	add_filter(
		'woocommerce_add_to_cart_redirect',
		function() {
			return wc_get_checkout_url();
		}
	);
}

add_action( 'admin_init', array( new WelcomeScreenActivationRedirector(), 'welcome_screen_do_activation_redirect' ) );

/**
 * Permissioned Init
 *
 * Nothing south of this check should run if templates aren't enabled / plugin is licensed
 *
 * This has to run on init because we need to be able to use current_user_can()
 * See: https://wordpress.stackexchange.com/questions/198185/what-is-valid-timing-of-using-current-user-can-and-related-functions
 */
add_action(
	'init',
	function() use ( $active_template, $compatibility_modules, $settings_manager, $smartystreets_address_validation, $order_bumps_feature ) {
		if ( ! cfw_is_enabled() ) {
			return;
		}

		/**
		 * Ad Hoc Compatibility
		 */
		// Optimole
		add_filter(
			'optml_dont_replace_url',
			function( $old ) {
				if ( is_cfw_page() ) {
					return true;
				}

				return $old;
			},
			10
		);

		// If language is LTR set HTML tag dir="ltr" using language_attributes filter
		add_filter(
			'language_attributes',
			function( $output ) {
				if ( function_exists( 'is_rtl' ) && ! is_rtl() ) {
					return $output . ' dir="ltr"';
				}

				return $output;
			}
		);

		// Load Translations
		load_plugin_textdomain(
			'checkout-wc',
			false,
			dirname( plugin_basename( CFW_MAIN_FILE ) ) . '/languages'
		);

		// Enqueue Template Assets and Load Template functions.php file
		Template::init_active_template( $active_template );

		// Init Compatibility Modules
		/** @var CompatibilityAbstract $module */
		foreach ( $compatibility_modules as $module ) {
			$module->init();
		}

		/**
		 * Setup Ajax Action Listeners
		 */
		( new AccountExistsAction() )->load();
		( new LogInAction() )->load();
		( new CompleteOrderAction() )->load();
		( new RemoveCouponAction() )->load();
		( new UpdateCheckoutAction() )->load();
		( new UpdatePaymentMethodAction() )->load();
		( new LostPasswordAction() )->load();
		( new UpdateSideCart( $order_bumps_feature ) )->load();
		( new AddToCartAction() )->load();
		( new ValidatePostcodeAction() )->load();
		( new ValidateEmailDomainAction() )->load();
		( new GetVariationForm() )->load();
		( new UpdateCartItemVariation() )->load();
		$smartystreets_address_validation->load_ajax_action();

		if ( SettingsManager::instance()->get_setting( 'registration_style' ) !== 'woocommerce' ) {
			// Override some WooCommerce Options
			add_filter(
				'pre_option_woocommerce_registration_generate_password',
				function() {
					if (
						( is_admin() && empty( $_GET['wc-ajax'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						// processing registration form
						|| isset( $_POST['register'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
						( did_action( 'wp' ) && ! cfw_is_checkout() ) // not on checkout when we can know we should be on checkout
					) {
						return false;
					}
					return 'yes';
				},
				0,
				1
			);

			add_filter(
				'pre_option_woocommerce_registration_generate_username',
				function() {
					if (
						( is_admin() && empty( $_GET['wc-ajax'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						// processing registration form
						|| isset( $_POST['register'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
						( did_action( 'wp' ) && ! cfw_is_checkout() ) // not on checkout when we can know we should be on checkout
					) {
						return false;
					}
					return 'yes';
				},
				0,
				1
			);
		}

		if ( PlanManager::can_access_feature( 'enable_thank_you_page' ) && PlanManager::can_access_feature( 'override_view_order_template' ) ) {
			add_filter(
				'woocommerce_get_view_order_url',
				function( $url, WC_Order $order ) {
					return add_query_arg( 'view', 'true', $order->get_checkout_order_received_url() );
				},
				100,
				2
			);
		}

		/**
		 * User matching
		 */
		if ( PlanManager::can_access_feature( 'user_matching' ) ) {
			// Match new guest orders to accounts
			add_action( 'woocommerce_new_order', 'cfw_maybe_match_new_order_to_user_account', 10, 1 );

			// Match old guest orders to accounts on registration
			add_action( 'cfw_link_orders_for_customer', 'cfw_maybe_link_orders_at_registration', 10, 1 );
			add_action(
				'woocommerce_created_customer',
				function( $user_id ) {
					wp_schedule_single_event( time() + 300, 'cfw_link_orders_for_customer', array( $user_id ) );
				}
			);
		}

		if ( $settings_manager->get_setting( 'enable_order_notes' ) === 'yes' ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_true' );
		}

		add_action(
			'cfw_output_fieldset',
			function( $fieldset ) {
				cfw_output_fieldset( $fieldset ?? array() );
			},
			10,
			1
		);

		/**
		 * Load Frontend Handlers
		 */
		add_action(
			'wp',
			function() {
				if ( is_cfw_page() ) {
					AddressFieldsAugmenter::instance()->init();
				}

				if ( cfw_is_phone_fields_enabled() ) {
					add_action( 'woocommerce_checkout_create_order', array( AddressFieldsAugmenter::instance(), 'update_shipping_phone_on_order_create' ), 10 );
				}
			},
			1
		);

		add_action(
			'wp',
			function() {
				if ( is_cfw_page() ) {
					FormFieldAugmenter::instance()->add_hooks();
				}
			},
			1
		);
		add_action(
			'cfw_checkout_update_order_review',
			function() {
				FormFieldAugmenter::instance()->add_hooks();
			},
			1
		);

		add_action( 'wp', 'cfw_frontend', 1 );
	},
	1
);
