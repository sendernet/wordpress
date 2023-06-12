<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'Sender_Helper.php';

class Sender_Carts
{
    private $sender;
    private $senderSessionCookie;

    const TRACK_CART = 'sender-track-cart';
    const UPDATE_CART = 'sender-update-cart';

    const CONVERTED_CART = '2';

    const FRAGMENTS_FILTERS = [
        'woocommerce_add_to_cart_fragments',
        'woocommerce_update_order_review_fragments'
    ];

    public function __construct($sender)
    {
        $this->sender = $sender;

        $this->senderAddCartsActions()
            ->senderAddCartsFilters();
        if (!isset($_COOKIE['sender_site_visitor'])) {
            return false;
        }

        $this->senderSessionCookie = $_COOKIE['sender_site_visitor'];
    }

    private function senderAddCartsActions()
    {
        //Handle cart changes and convert
        add_action('woocommerce_checkout_order_processed', [&$this, 'prepareConvertCart'], 10, 1);
        add_action('woocommerce_thankyou', [&$this, 'senderConvertCart'], 10, 1);
        add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated'], 50);

        //Adding subscribe to newsletter checkbox
        add_action('woocommerce_review_order_before_submit', [&$this, 'senderAddNewsletterCheck'], 10);
        add_action('woocommerce_edit_account_form', [&$this, 'senderAddNewsletterCheck']);
        add_action('woocommerce_register_form', [&$this, 'senderAddNewsletterCheck']);
        add_action('woocommerce_checkout_update_order_meta', [&$this, 'senderAddNewsletterFromOrder']);

        //Handle sender_newsletter on create/update account
        add_action('woocommerce_created_customer', [&$this, 'senderNewsletterHandle'], 10, 1);
        add_action('woocommerce_save_account_details', [&$this, 'senderNewsletterHandle'], 10, 1);

        //Handle admin order edit subscribe to newsletter
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'senderAddNewsletterCheck']);

        //Capture email when filling checkout details
        add_action('wp_enqueue_scripts', [$this, 'enqueueSenderCheckoutEmailTriggerScript'], 99);
        add_action('wp_ajax_trigger_backend_hook', [$this,'triggerEmailCheckout']);
        add_action('wp_ajax_nopriv_trigger_backend_hook', [$this,'triggerEmailCheckout']);

        return $this;
    }

    public function senderAddNewsletterFromOrder($orderId)
    {
        if (isset($_POST['sender_newsletter']) && !empty($_POST['sender_newsletter'])) {
            update_post_meta($orderId, Sender_Helper::EMAIL_MARKETING_META_KEY, Sender_Helper::generateEmailMarketingConsent(Sender_Helper::SUBSCRIBED));
        } else {
            if (Sender_Helper::shouldChangeChannelStatus($orderId, 'order')) {
                update_post_meta(
                    $orderId,
                    Sender_Helper::EMAIL_MARKETING_META_KEY,
                    Sender_Helper::generateEmailMarketingConsent(Sender_Helper::UNSUBSCRIBED)
                );
                $wcOrder = wc_get_order($orderId);
                if ($wcOrder) {
                    $this->sender->senderApi->updateCustomer(['subscriber_status' => Sender_Helper::UPDATE_STATUS_UNSUBSCRIBED], $wcOrder->get_billing_email());
                }
            } elseif (is_user_logged_in() && Sender_Helper::shouldChangeChannelStatus(get_current_user_id(), 'user')) {
                update_user_meta(
                    get_current_user_id(),
                    Sender_Helper::EMAIL_MARKETING_META_KEY,
                    Sender_Helper::generateEmailMarketingConsent(Sender_Helper::UNSUBSCRIBED)
                );
                $this->sender->senderApi->updateCustomer(['subscriber_status' => Sender_Helper::UPDATE_STATUS_UNSUBSCRIBED], get_userdata(get_current_user_id())->user_email);
            }
        }
    }

    public function senderNewsletterHandle($userId)
    {
        if (!empty($_POST['sender_newsletter'])) {
            update_user_meta($userId, 'email_marketing_consent', Sender_Helper::generateEmailMarketingConsent(Sender_Helper::SUBSCRIBED));
            $this->sender->senderApi->updateCustomer(['subscriber_status' => Sender_Helper::UPDATE_STATUS_ACTIVE], get_userdata($userId)->user_email);
        } else {
            if (Sender_Helper::shouldChangeChannelStatus($userId, 'user')) {
                update_user_meta(
                    $userId,
                    Sender_Helper::EMAIL_MARKETING_META_KEY,
                    Sender_Helper::generateEmailMarketingConsent(Sender_Helper::UNSUBSCRIBED)
                );
                $this->sender->senderApi->updateCustomer(['subscriber_status' => Sender_Helper::UPDATE_STATUS_UNSUBSCRIBED], get_userdata($userId)->user_email);
            }
        }
    }

    private function senderAddCartsFilters()
    {
        add_filter('template_include', [&$this, 'senderRecoverCart'], 99, 1);

        return $this;
    }

    public function prepareConvertCart($orderId)
    {
        $cart = (new Sender_Cart())->findByAttributes(
            [
                'session' => $this->senderSessionCookie,
                'cart_status' => 0
            ],
            'created DESC'
        );

        if ($cart) {
            $cart->cart_status = self::CONVERTED_CART;
            $cart->save();
        }
    }

    public function senderConvertCart($orderId)
    {
        $cart = (new Sender_Cart())->findByAttributes(
            [
                'session' => $this->senderSessionCookie,
                'cart_status' => self::CONVERTED_CART
            ],
            'created DESC'
        );

        if (!$cart){
            return false;
        }

        $list = get_option('sender_customers_list');
        $wcOrder = wc_get_order($orderId);
        $email = $wcOrder->get_billing_email();
        $firstname = $wcOrder->get_billing_first_name();
        $lastname = $wcOrder->get_billing_last_name();
        $phone = $wcOrder->get_billing_phone();

        $cartData = [
            'external_id' => $cart->id,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'resource_key' => $this->senderGetResourceKey(),
            'phone' => $phone,
            'order_id' => (string)$orderId
        ];

        if ($list) {
            $cartData['list_id'] = $list;
        }

        $wpUserId = get_current_user_id();
        if ($wpUserId){
            $cartData['customer_id'] = $wpUserId;
        }

        $metaOrderEmailMarketingConsent = get_post_meta($orderId, Sender_Helper::EMAIL_MARKETING_META_KEY, true);
        if (!empty($metaOrderEmailMarketingConsent)) {
            if (Sender_Helper::handleChannelStatus($metaOrderEmailMarketingConsent)) {
                $cartData['newsletter'] = true;
            }
        }

        update_post_meta($orderId, 'sender_remote_id', $cart->id);
        add_action('wp_head', [&$this, 'addConvertCartScript'], 10, 1);
        do_action('wp_head', json_encode($cartData));
    }

    public function senderPrepareCartData($cart)
    {
        $items = $this->senderGetCart();
        $total = $this->senderGetWoo()->cart->total;
        $user = (new Sender_User())->find($cart->user_id);

        if (!$user){
            return;
        }

        $baseUrl = wc_get_cart_url();
        $lastCharacter = substr($baseUrl, -1);

        if (strcmp($lastCharacter, '/') === 0) {
            $cartUrl = rtrim($baseUrl, '/') . '?hash=' . $cart->id;
        } else {
            $cartUrl = $baseUrl . '&hash=' . $cart->id;
        }

        $data = [
            "visitor_id" => $user->visitor_id,
            "external_id" => $cart->id,
            "url" => $cartUrl,
            "currency" => 'EUR',
            "order_total" => (string)$total,
            "products" => [],
            'resource_key' => $this->senderGetResourceKey(),
            'store_id' => get_option('sender_store_register') ?: '',
        ];

        foreach ($items as $item => $values) {

            $_product = wc_get_product($values['data']->get_id());
            $regularPrice = (int)get_post_meta($values['product_id'], '_regular_price', true);
            $salePrice = (int)get_post_meta($values['product_id'], '_sale_price', true);

            if ($regularPrice <= 0) {
                $regularPrice = 1;
            }

            $discount = round(100 - ($salePrice / $regularPrice * 100));

            $prod = [
                'sku' => $values['data']->get_sku(),
                'name' => (string)$_product->get_title(),
                'price' => (string)$regularPrice,
                'price_display' => (string)$_product->get_price() . get_woocommerce_currency_symbol(),
                'discount' => (string)$discount,
                'qty' => $values['quantity'],
                'image' => get_the_post_thumbnail_url($values['data']->get_id()),
                'product_id' => $values['product_id']
            ];

            $data['products'][] = $prod;
        }

        return $data;
    }

    public function trackUser()
    {
        if (!is_user_logged_in() || !isset($_COOKIE['sender_site_visitor'])) {
            return;
        }

        $wpUser = wp_get_current_user();
        $wpId = $wpUser->ID;

        $visitorId = sanitize_text_field($_COOKIE['sender_site_visitor']);
        $user = (new Sender_User())->findBy('wp_user_id', $wpId);

        if (!$user) {
            $user = (new Sender_User())->findBy('visitor_id', $visitorId);
        }

        if (!$user) {
            $user = new Sender_User();
        }

        $user->visitor_id = $visitorId;
        $user->wp_user_id = $wpId;
        $user->email = $wpUser->user_email;

        if (isset($_POST['sender_newsletter'])){
            $this->senderNewsletterHandle($wpId);
        }

        if ($user->isDirty()) {
            $this->sender->senderApi->senderApiShutdownCallback("senderTrackRegisteredUsers", $wpId);
        }

        $user->save();
    }

    public function senderCartUpdated()
    {
        if (isset($_GET['hash'])){
            return;
        }

        $this->trackUser();
        $items = $this->senderGetCart();

        $cartData = serialize($items);

        if (!$this->senderGetWoo()->session->get_session_cookie()) {
            if (empty($items)){
                return;
            }
            
            //Making the woocommerce cookie active when adding from general view
            WC()->session->set_customer_session_cookie(true);
        }

        if(isset($_COOKIE['sender_recovered_cart'])){
            $cart = (new Sender_Cart())->find($_COOKIE['sender_recovered_cart']);
            $cart->session = $this->senderSessionCookie;
            $cart->save();
        }

        if (!isset($cart)) {
            $cart = (new Sender_Cart())->findByAttributes(
                [
                    'session' => $this->senderSessionCookie,
                    'cart_status' => 0
                ],
                'created DESC'
            );
        }

        if (empty($items) && $cart) {
            #Keep converted carts
            if ($cart->cart_status == self::CONVERTED_CART) {
                return;
            }

            $cart->delete();
            $this->sender->senderApi->senderApiShutdownCallback("senderDeleteCart", $cart->id);

            return;
        }

        //Look for possible cart in a connected user
        if (is_user_logged_in() && !$cart){
            $currentUser = wp_get_current_user();
            $user = (new Sender_User())->findBy('email', $currentUser->user_email);
            #find if current user has any abandoned carts
            if ($user){
                $cart = (new Sender_Cart())->findByAttributes(
                    [
                        'user_id' => $user->id,
                        'cart_status' => 0
                    ],
                    'created DESC'
                );

                if ($cart){
                    $cart->session = $this->senderSessionCookie;
                    $cart->save();
                }
            }
        }

        //If cart converted, start a new cart
        if ($cart && $cart->cart_status == self::CONVERTED_CART){
            $cart = false;
        }

        //Update cart
        if ($cart && !empty($items)) {
            $cart->cart_data = $cartData;
            $cart->save();
            $cartData = $this->senderPrepareCartData($cart);

            if (!$cartData) {
                return;
            }

            if (wp_doing_ajax()) {
                $this->handleCartFragmentsFilters(json_encode($cartData), self::UPDATE_CART);
            } else {
                $this->sender->senderApi->senderApiShutdownCallback("senderUpdateCart", $cartData);
            }

            return;
        }

        if (!empty($items)) {
            if (!$senderUser = $this->senderGetVisitor()){
                return;
            }

            $newCart = new Sender_Cart();
            $newCart->cart_data = $cartData;
            $newCart->user_id = $senderUser->id;
            $newCart->session = $this->senderSessionCookie;
            $newCart->save();

            $cartData = $this->senderPrepareCartData($newCart);
            if (!$cartData){
                return;
            }

            if (wp_doing_ajax()) {
                $this->handleCartFragmentsFilters(json_encode($cartData), self::TRACK_CART);

                //Solution for Cart-Flows email checker
                if ($_POST && isset($_POST['action'])) {
                    if ($_POST['action'] === 'wcf_check_email_exists') {
                        $this->sender->senderApi->senderApiShutdownCallback("senderTrackCart", $cartData);
                    }
                }
            } else {
                add_action('wp_head', [&$this, 'addTrackCartScript']);
                do_action('wp_head', json_encode($cartData));
            }
        }
    }

    public function handleCartFragmentsFilters($cartData, $type)
    {
        switch ($type) {
            case self::TRACK_CART:
                $method = 'trackCart';
                break;
            case self::UPDATE_CART:
                $method = 'updateCart';
                break;
        }

        if (isset($method)) {
            foreach (self::FRAGMENTS_FILTERS as $filterName) {
                add_filter($filterName, function ($fragments) use ($cartData, $type, $method) {
                    ob_start();
                    ?>
                    <script id="<?php echo $type ?>">
                        sender('<?php echo $method; ?>', <?php echo $cartData; ?>)
                    </script>
                    <?php $fragments['script#' . $type] = ob_get_clean();
                    return $fragments;
                });
            }
        }
    }

    public function senderGetVisitor()
    {
        if (empty($this->senderSessionCookie)) {
            return false;
        }

        $user = (new Sender_User())->findBy('visitor_id', $this->senderSessionCookie);

        if (!$user) {
            $user = new Sender_User();
            $user->visitor_id = $this->senderSessionCookie;
            $user->save();
        }
        return $user;
    }

    public function senderGetCart()
    {
        return $this->senderGetWoo()->cart->get_cart();
    }

    public function senderGetWoo()
    {
        global $woocommerce;

        if (function_exists('WC')) {
            return WC();
        }

        return $woocommerce;
    }

    public function senderRecoverCart($template)
    {
        if (!isset($_GET['hash'])) {
            return $template;
        }

        $cartId = sanitize_text_field($_GET['hash']);

        $cart = (new Sender_Cart())->find($cartId);
        if (!$cart || $cart->cart_recovered || $cart->cart_status == self::CONVERTED_CART) {
            return wp_redirect(wc_get_cart_url());
        }

        $cart->cart_recovered = '1';
        $cart->save();

        $cartData = unserialize($cart->cart_data);

        if (empty($cartData)) {
            return $template;
        }

        $wooCart = new WC_Cart();

        foreach ($cartData as $product) {
            $wooCart->add_to_cart(
                (int)$product['product_id'],
                (int)$product['quantity'],
                (int)$product['variation_id'],
                $product['variation']
            );
        }

        setcookie( 'sender_recovered_cart', $cartId, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
        new WC_Cart_Session($wooCart);
        return wp_redirect(wc_get_cart_url());
    }

    public function senderGetResourceKey()
    {
        $key = get_option('sender_resource_key');

        if (!$key) {
            $user = $this->senderGetAccount();
            $key = $user->account->resource_key;
            update_option('sender_resource_key', $key);
        }

        return $key;
    }

    public function addConvertCartScript($cartData)
    {
        ob_start();
        echo "
			<script>
			sender('convertCart', $cartData)
            </script>
		";
    }

    public function senderAddNewsletterCheck($order)
    {
        if (get_option('sender_subscribe_label') && !empty(get_option('sender_subscribe_to_newsletter_string'))) {
            if (is_admin()) {
                $emailMarketingConset = $order->get_meta(Sender_Helper::EMAIL_MARKETING_META_KEY);
                if (!empty($emailMarketingConset)) {
                    $currentValue = Sender_Helper::handleChannelStatus($emailMarketingConset);
                } else {
                    $currentValue = $order->get_meta('sender_newsletter');
                }
            } else {
                $emailMarketingConset = get_user_meta(get_current_user_id(), Sender_Helper::EMAIL_MARKETING_META_KEY, true);
                if (!empty($emailMarketingConset)) {
                    $currentValue = Sender_Helper::handleChannelStatus($emailMarketingConset);
                } else {
                    $currentValue = get_user_meta(get_current_user_id(), 'sender_newsletter', true);
                }
            }

            woocommerce_form_field('sender_newsletter', array(
                'type' => 'checkbox',
                'class' => array('form-row mycheckbox'),
                'label_class' => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
                'input_class' => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
                'label' => get_option('sender_subscribe_to_newsletter_string'),
            ), $currentValue);
        }
    }

    public function addTrackCartScript($cartData)
    {
        ob_start();
        ?>
        <script>
            sender('trackCart', <?php echo $cartData; ?>);
        </script>
        <?php
    }

    public function triggerEmailCheckout()
    {
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $response = $this->sender->senderApi->senderTrackNotRegisteredUsers(['email' => sanitize_text_field($_POST['email']), 'visitor_id' => $this->senderSessionCookie], true);
            if($response) {
                return wp_send_json_success($response);
            }
            return wp_send_json_error('Subscriber not created');
        }
    }

    public function enqueueSenderCheckoutEmailTriggerScript()
    {
        wp_enqueue_script('checkout-email-trigger', plugins_url('assets/js/checkout-email-trigger.js', dirname(__FILE__)));
        wp_localize_script('checkout-email-trigger', 'senderAjax', array('ajaxUrl' => admin_url('admin-ajax.php')));
    }
}