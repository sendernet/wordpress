<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_Carts
{
    private $sender;
    private $senderSessionCookie;

    const TRACK_CART = 'sender-track-cart';
    const UPDATE_CART = 'sender-update-cart';

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
        add_action('woocommerce_checkout_order_processed', [&$this, 'prepareConvertCart'], 10, 1);
        add_action('woocommerce_thankyou', [&$this, 'senderConvertCart'], 10, 1);
        add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated'], 50);

        add_action('woocommerce_review_order_before_submit', [&$this, 'senderAddNewsletterCheck'], 10);
        add_action('woocommerce_edit_account_form', [&$this, 'senderAddNewsletterCheck']);
        add_action('woocommerce_register_form', [&$this, 'senderAddNewsletterCheck']);

        add_action('woocommerce_checkout_update_order_meta', [&$this, 'senderAddNewsletterFromOrder']);
        add_action('woocommerce_save_account_details', [&$this, 'senderUpdateNewsletter'], 10, 1);
        add_action('woocommerce_created_customer', [&$this, 'senderUpdateNewsletter'], 10, 1);

        add_action('edit_user_profile_update', [&$this, 'senderUpdateNewsletter']);

        return $this;
    }

    public function senderAddNewsletterFromOrder($orderId)
    {
        if (isset($_POST['sender_newsletter']) && !empty($_POST['sender_newsletter'])) {
            update_post_meta($orderId, 'sender_newsletter', sanitize_text_field($_POST['sender_newsletter']));
        }
    }

    public function senderUpdateNewsletter($userId)
    {
        if (isset($_POST['sender_newsletter']) && !empty($_POST['sender_newsletter'])) {
            update_user_meta($userId, 'sender_newsletter', 1);
        } else {
            update_user_meta($userId, 'sender_newsletter', 0);
        }
    }

    private function senderAddCartsFilters()
    {
        add_filter('template_include', [&$this, 'senderRecoverCart'], 99, 1);

        return $this;
    }

    public function prepareConvertCart($orderId)
    {
        $cart = (new Sender_Cart())->findBy('session', $this->senderSessionCookie);
        $cart->cart_status = '2';
        $cart->save();
    }

    public function senderConvertCart($orderId)
    {
        $cart = (new Sender_Cart())->findBy('session', $this->senderSessionCookie);

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

        //getting from order post
        $metaOrderNewsletter = get_post_meta($orderId, 'sender_newsletter', true);
        if ($wpUserId) {
            if (get_post_meta($orderId, 'sender_newsletter', true) || get_user_meta($wpUserId, 'sender_newsletter', true)) {
                $this->trackUser();
                $cartData['newsletter'] = true;
            }
        }elseif ($metaOrderNewsletter){
            $cartData['newsletter'] = true;
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
            "order_total" => $total,
            "products" => [],
            'resource_key' => $this->senderGetResourceKey()
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
            $this->senderUpdateNewsletter($wpId);
        }

        if ($user->isDirty()) {
            $this->sender->senderApi->senderApiShutdownCallback("senderTrackRegisteredUsers", $wpId);
        }

        $user->save();
    }

    public function senderCartUpdated()
    {
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

        $cart = (new Sender_Cart())->findBy('session', $this->senderSessionCookie);

        if (empty($items) && $cart) {
            if ($cart->cart_status == "2") {
                return;
            }
            $cart->delete();
            $this->sender->senderApi->senderApiShutdownCallback("senderDeleteCart", $cart->id);

            return;
        }

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
            $newCart = new Sender_Cart();
            $newCart->cart_data = $cartData;
            $newCart->user_id = $this->senderGetVisitor()->id;
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

        if (!$cart || $cart->cart_recovered) {
            return $template;
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

        new WC_Cart_Session($wooCart);

        return $template;
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
        echo "
			<script>
			sender('convertCart', $cartData)
            </script>
		";
    }

    /**
     * @return void
     */
    public function senderAddNewsletterCheck()
    {
        if (get_option('sender_subscribe_label') && !empty(get_option('sender_subscribe_to_newsletter_string'))) {
            $currentValue = get_user_meta(get_current_user_id(), 'sender_newsletter', true);
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
        echo "
			<script>
			sender('trackCart', $cartData)
            </script>
		";
    }

}