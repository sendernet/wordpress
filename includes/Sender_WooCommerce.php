<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_WooCommerce
{
    private $sender;
    private $tablePrefix;

    public function __construct($sender, $update = false)
    {
        $this->sender = $sender;
        add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);

        //Update user data from admin panel
        if (is_admin()) {
            if (get_option('sender_subscribe_label') && !empty(get_option('sender_subscribe_to_newsletter_string'))) {
                add_action('edit_user_profile', [$this, 'senderNewsletter']);
            }
            add_action('edit_user_profile_update', [$this, 'senderUpdateCustomerData'], 10, 1);
            add_action('woocommerce_saved_address', [$this, 'senderUpdateCustomerData2'], 10, 1);
            add_action('woocommerce_process_shop_order_meta', [$this, 'senderAddUserAfterManualOrderCreation'], 51);
            add_action('before_delete_post', [$this, 'senderRemoveSubscriber']);
        }

        //Adding after plugins loaded to avoid error on user_query
        add_action('plugins_loaded', [&$this, 'senderExportShopData'], 99);

        if ($update){
            $this->senderExportShopData();
        }
    }

    public function senderNewsletter($user)
    {
        $currentValue = (int)get_user_meta($user->ID, 'sender_newsletter', true);

        ?>
        <table class="form-table">
            <tbody>
            <tr class="show-admin-bar user-admin-bar-front-wrap">
                <th scope="row">Subscribed to newsletter</th>
                <td>
                    <label for="admin_bar_front">
                        <input name="sender_newsletter" type="checkbox" id="sender_newsletter"
                            <?php echo $currentValue === 1 ? 'checked' : '' ?> value="1"></label><br>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    public function senderAddUserAfterManualOrderCreation($orderId)
    {
        $postMeta = get_post_meta($orderId);
        if ($postMeta && isset($postMeta['_billing_email'][0])) {
            $visitorId = $this->sender->senderApi->generateVisitorId();
            if (!$visitorId->id) {
                return;
            }

            $subscriberData = array(
                'email' => $postMeta['_billing_email'][0],
                'firstname' => $postMeta['_billing_first_name'][0] ?: null,
                'lastname' => $postMeta['_billing_last_name'][0] ?: null,
                'visitor_id' => $visitorId->id,
            );

            if (get_option('sender_customers_list')) {
                $subscriberData['list_id'] = get_option('sender_customers_list');
            }

            $this->sender->senderApi->senderTrackNotRegisteredUsers($subscriberData);

            if (isset($_POST['sender_newsletter']) && !empty($_POST['sender_newsletter'])) {
                update_post_meta($orderId, 'sender_newsletter', 1);
                $updateFields['subscriber_status']= 'ACTIVE';
            } else {
                update_post_meta($orderId, 'sender_newsletter', 0);
                $updateFields['subscriber_status']= 'UNSUBSCRIBED';
            }

            if (!empty($postMeta['_billing_phone'][0])){
                if (isset($_POST['_billing_phone'])) {
                    $requestPhone = sanitize_text_field($_POST['_billing_phone']);
                    if ($requestPhone !== $postMeta['_billing_phone'][0]) {
                        $updateFields['phone'] = $requestPhone;
                    }
                }
            }

            if(!empty($updateFields)) {
                $this->sender->senderApi->updateCustomer($updateFields, $subscriberData['email']);
            }

        }
    }

    public function senderUpdateCustomerData($userId)
    {
        if (isset($_POST['sender_newsletter']) && !empty($_POST['sender_newsletter'])) {
            update_user_meta($userId, 'sender_newsletter', 1);
        } else {
            update_user_meta($userId, 'sender_newsletter', 0);
        }

        $oldUserData = get_userdata($userId);

        $oldFirstname = $oldUserData->first_name;
        $oldLastName = $oldUserData->last_name;
        $updatedFirstName = $_POST['first_name'] ?: '';
        $updatedLastName = $_POST['last_name'] ?: '';

        $changedFields = [];
        if ($oldFirstname !== $updatedFirstName) {
            $changedFields['firstname'] = $updatedFirstName;
        }

        if ($oldLastName !== $updatedLastName) {
            $changedFields['lastname'] = $updatedLastName;
        }

        $oldUserMetaData = get_user_meta($userId);
        if (!empty($oldUserMetaData['billing_phone'][0])) {
            $oldPhone = $oldUserMetaData['billing_phone'][0];
            $updatedPhone = $_POST['billing_phone'] ?: '';

            if ($oldPhone !== $updatedPhone){
                $changedFields['phone'] = $updatedPhone;
            }
        }

        $emailSubscription = get_user_meta($userId, 'sender_newsletter', true);
        if((int)$emailSubscription === 1){
            $changedFields['subscriber_status'] = 'ACTIVE';
        }else{
            $changedFields['subscriber_status'] = 'UNSUBSCRIBED';
        }

        if (!empty($changedFields)) {
            $this->sender->senderApi->updateCustomer($changedFields, get_userdata($userId)->user_email);
        }
    }

    public function senderRemoveSubscriber($postId)
    {
        if (get_post_type($postId) === 'shop_order') {
            // Your code to execute when an order is moved to trash
            $billingEmail = get_post_meta($postId, '_billing_email', true);
            if (!empty($billingEmail)){
                $this->sender->senderApi->deleteSubscribers(['subscribers' => [$billingEmail]]);
            }
        }
    }

    public function senderAddProductImportScript()
    {

        global $product;

        $id = $product->get_id();

        $pImage = get_the_post_thumbnail_url($id);

        if (!$pImage) {
            $gallery = $product->get_gallery_image_ids();
            if (!empty($gallery)) {
                $pImage = wp_get_attachment_url($gallery[0]);
            }
        }

        $pName = str_replace("\"", '\\"', $product->get_name());
        $pDescription = str_replace("\"", '\\"', $product->get_description());
        $pPrice = $product->get_regular_price();
        $pCurrency = get_option('woocommerce_currency');
        $pQty = $product->get_stock_quantity() ? $product->get_stock_quantity() : 1;
        $pRating = $product->get_average_rating();
        $pOnSale = $product->is_on_sale();
        $pSalePrice = $pPrice;
        $pDiscount = 0;

        if ($product->is_on_sale() && !empty($product->get_sale_price())) {
            $pSalePrice = $product->get_sale_price();
            $pDiscount = round((string)100 - ($pSalePrice / $pPrice * 100));
        }

        echo '<script type="application/sender+json">
                        {
                          "name": "' . $pName . '",
                          "image": "' . $pImage . '",
                          "description": "' . $pDescription . '",
                          "price": "' . (float)$pPrice . '",
                          "discount": "-' . $pDiscount . '%",
                          "special_price": "' . (float)$pSalePrice . '",
                          "currency": "' . $pCurrency . '",
                          "quantity": "' . $pQty . '",
                          "is_on_sale": "' . $pOnSale . '",
                          "rating": "' . $pRating . '"
                        }
                    </script>';
    }

    public function exportCustomers()
    {
        $customer_query = new WP_User_Query(
            array(
                'fields' => 'id',
                'role' => 'customer',
            )
        );

        $customersCount = $customer_query->get_total();

        $chunkSize = 5000;
        $customersExported = 0;

        if ($customersCount > $chunkSize) {
            $loopTimes = floor($customersCount / $chunkSize);
            for ($x = 0; $x <= $loopTimes; $x++) {
                $customer_query = new WP_User_Query(
                    array(
                        'fields' => 'id',
                        'role' => 'customer',
                        'number' => $chunkSize,
                        'offset' => $customersExported
                    )
                );
                $customerList = json_decode(json_encode($customer_query->get_results(), true));
                $this->sendCustomersToSender($customerList);
                $customersExported += $chunkSize;
            }
        } else {
            $customerList = json_decode(json_encode($customer_query->get_results(), true));
            $this->sendCustomersToSender($customerList);
        }
    }

    public function sendCustomersToSender($customers)
    {
        $list = [get_option('sender_customers_list')];
        $customersExportData = [];
        foreach ($customers as $customerId) {
            $customer = get_user_meta($customerId);
            if (!empty($customer['billing_email'][0])) {
                $data = [
                    'email' => $customer['billing_email'][0],
                    'firstname' => $customer['first_name'][0] ?: null,
                    'lastname' => $customer['last_name'][0] ?: null,
                    'phone' => $customer['billing_phone'][0] ?: null,
                    'tags' => [get_option('sender_customers_list')],
                ];

                if (isset($customer['sender_newsletter']) && $customer['sender_newsletter']) {
                    $data['newsletter'] = true;
                }

                $customersExportData[] = $data;
            }
        }

        $this->sender->senderApi->senderExportData(['customers' => $customersExportData]);
    }

    public function exportProducts()
    {
        global $wpdb;
        $products = $wpdb->get_results('SELECT * FROM ' . $this->tablePrefix . 'posts 
                      INNER JOIN ' . $this->tablePrefix . 'wc_product_meta_lookup ON ' . $this->tablePrefix . 'wc_product_meta_lookup.product_id = ' . $this->tablePrefix . 'posts.id
                      WHERE post_type = "product"');

        $productExportData = [];
        $currency = get_option('woocommerce_currency');
        foreach ($products as $product) {

            $image = null;
            if (get_post_thumbnail_id($product->ID)) {
                $image = wp_get_attachment_image_src(get_post_thumbnail_id($product->ID))[0];
            }

            $productExportData[] = [
                'title' => $product->post_name,
                'description' => $product->post_content,
                'sku' => $product->sku,
                'quantity' => $product->stock_quantity,
                'remote_productId' => $product->product_id,
                'image' => [$image],
                'price' => number_format($product->max_price, 2),
                'status' => $product->post_status,
                'created_at' => $product->post_date,
                'updated_at' => $product->post_modified,
                'currency' => $currency,
            ];
        }

        $this->sender->senderApi->senderExportData(['products' => $productExportData]);

        return true;
    }

    public function exportOrders()
    {
        global $wpdb;
        $orders = $wpdb->get_results('SELECT * FROM ' . $this->tablePrefix . 'posts WHERE post_type = "shop_order"');

        $ordersCount = count($orders);
        $chunkSize = 50;
        $ordersExported = 0;
        $loopTimes = floor($ordersCount / $chunkSize);

        $ordersExportData = [];

        for ($x = 0; $x <= $loopTimes; $x++) {
            $chunkedOrders = $wpdb->get_results('SELECT * FROM ' . $this->tablePrefix . 'posts WHERE post_type = "shop_order" LIMIT ' . $chunkSize . '
             OFFSET ' . $ordersExported);

            foreach ($chunkedOrders as $order) {
                $remoteId = get_post_meta($order->ID, 'sender_remote_id', true);
                if (!$remoteId) {
                    $remoteId = get_post_meta($order->ID, '_order_key', true);
                }

                $orderData = [
                    'status' => $order->post_status,
                    'updated_at' => $order->post_modified,
                    'created_at' => $order->post_date,
                    'remoteId' => $remoteId,
                    'name' => $order->post_name,
                    'currency' => get_option('woocommerce_currency'),
                    'orderId' => $order->ID
                ];

                $productsData = $wpdb->get_results('SELECT * FROM ' . $this->tablePrefix . 'wc_order_product_lookup
            INNER JOIN ' . $this->tablePrefix . 'wc_product_meta_lookup on ' . $this->tablePrefix . 'wc_product_meta_lookup.product_id = ' . $this->tablePrefix . 'wc_order_product_lookup.product_id
            LEFT JOIN ' . $this->tablePrefix . 'posts on ' . $this->tablePrefix . 'posts.id = ' . $this->tablePrefix . 'wc_order_product_lookup.product_id
            where ' . $this->tablePrefix . 'wc_order_product_lookup.order_id = ' . $order->ID);

                $orderData['products'] = [];
                $orderPrice = 0;
                foreach ($productsData as $key => $product) {
                    $regularPrice = $product->min_price;
                    $salePrice = $product->max_price;

                    if ($regularPrice <= 0) {
                        $regularPrice = 1;
                    }

                    $discount = round(100 - ($salePrice / $regularPrice * 100));
                    $orderPrice += $product->max_price * $product->product_qty;
                    $orderData['products'][$key] = [
                        'sku' => $product->sku,
                        'name' => $product->post_title,
                        'price' => $product->max_price,
                        'qty' => $product->product_qty,
                        'discount' => (string)$discount,
                        'currency' => get_option('woocommerce_currency'),
                        'image' => get_the_post_thumbnail_url($product->product_id),
                    ];
                }

                $orderData['price'] = $orderPrice;
                $ordersExportData[] = $orderData;
            }
            $this->sender->senderApi->senderExportData(['orders' => $ordersExportData]);
            $ordersExported += $chunkSize;
        }
    }

    public function getTablePrefix()
    {
        global $wpdb;
        $this->tablePrefix = $wpdb->prefix;
    }

    public function senderExportShopData()
    {
        if (!get_option('sender_wocommerce_sync') && is_admin()) {
            $storeActive = $this->sender->senderApi->senderGetStore();
            if (!$storeActive && !isset($storeActive->xRate)) {
                $this->sender->senderHandleAddStore();
                $storeActive = true;
            }

            if ($storeActive && get_option('sender_store_register')) {
                $this->getTablePrefix();
                $this->exportCustomers();
                $this->exportProducts();
                $this->exportOrders();
                update_option('sender_wocommerce_sync', true);
                update_option('sender_synced_data_date', current_time('Y-m-d H:i:s'));
            }
        }
    }
}