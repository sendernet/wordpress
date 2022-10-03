<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Sender_WooCommerce
    {
        private $sender;

    public function __construct($sender)
    {
        $this->sender = $sender;

        if (!get_option('sender_wocommerce_sync')) {
            $this->exportCustomers();
            $this->exportProducts();
            $this->exportOrders();
        }

        add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);
    }

        public function senderAddProductImportScript()
        {

                global $product;

                $id = $product->get_id();

                $pImage = get_the_post_thumbnail_url($id);

                if(!$pImage){
                    $gallery = $product->get_gallery_image_ids();
                    if(!empty($gallery)){
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
                          "is_on_sale": "'.$pOnSale.'",
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

            if ($customersCount > $chunkSize){
                $loopTimes = floor($customersCount / $chunkSize);
                for ($x = 0; $x <= $loopTimes; $x++){
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
            }else {
                $customerList = json_decode(json_encode($customer_query->get_results(), true));
                $this->sendCustomersToSender($customerList);
            }

            update_option('sender_wocommerce_sync', true);

            return true;
        }

        public function sendCustomersToSender($customers)
        {
            $list = [get_option('sender_customers_list')];
            $customersExportData = [];
            foreach ($customers as $customerId) {
                $customer = get_user_meta($customerId);
                if (isset($customer['billing_email'])) {
                    $customersExportData[] = [
                        'email' => $customer['billing_email'][0],
                        'firstname' => $customer['first_name'][0] ?: null,
                        'lastname' => $customer['last_name'][0] ?: null,
                        'phone' => $customer['billing_phone'][0] ?: null,
                        'tags' => $list
                    ];
                }
            }

            $this->sender->senderApi->senderExportData(['customers' => $customersExportData]);
        }

        public function exportProducts()
        {
            global $wpdb;
            $products = $wpdb->get_results("SELECT * FROM wp_posts 
                      INNER JOIN wp_wc_product_meta_lookup ON wp_wc_product_meta_lookup.product_id = wp_posts.id
                      WHERE post_type = 'product'");
            $productExportData = [];
            $currency = get_woocommerce_currency();
            foreach ($products as $product){

                $image = null;
                if(get_post_thumbnail_id($product->ID)){
                    $image = wp_get_attachment_image_src(get_post_thumbnail_id($product->ID))[0];
                }

                $productExportData[] = [
                    'title' => $product->post_name,
                    'description' => $product->post_content,
                    'sku' => $product->sku,
                    'quantity' => $product->stock_quantity,
                    'remote_product_id' => $product->product_id,
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
            $orders = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_type = 'shop_order'");
            $ordersExportData = [];

            foreach ($orders as $order) {

                $orderData = [
                    'status' => $order->post_status,
                    'updated_at' => $order->post_modified,
                    'created_at' => $order->post_date,
                    'remote_id' => $order->ID,
                    'name' => $order->post_name,
                    'currency' => get_woocommerce_currency(),
                ];

                $productsData = $wpdb->get_results("SELECT * FROM wp_wc_order_product_lookup
            INNER JOIN wp_wc_product_meta_lookup on wp_wc_product_meta_lookup.product_id = wp_wc_order_product_lookup.product_id
            LEFT JOIN wp_posts on wp_posts.id = wp_wc_order_product_lookup.product_id
            where wp_wc_order_product_lookup.order_id = $order->ID");

                $orderData['products'] = [];
                foreach ($productsData as $key => $product) {
                    $regularPrice = $product->min_price;
                    $salePrice = $product->max_price;

                    if ($regularPrice <= 0) {
                        $regularPrice = 1;
                    }

                    $discount = round(100 - ($salePrice / $regularPrice * 100));
                    $orderData['price'] = $product->product_net_revenue;
                    $orderData['products'][$key] = [
                        'sku' => $product->sku,
                        'name' => $product->post_title,
                        'price' => $product->max_price,
                        'qty' => $product->product_qty,
                        'discount' => (string)$discount,
                        'currency' => get_woocommerce_currency(),
                        'image' => get_the_post_thumbnail_url($product->product_id),
                    ];
                }

                $ordersExportData[] = $orderData;
            }
            $this->sender->senderApi->senderExportData(['orders' => $ordersExportData]);

            return true;
        }
}