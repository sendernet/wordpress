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
			add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);
        }



        public function senderAddProductImportScript()
        {
            if (get_option('sender_allow_import')) {

                global $product;

                $id = $product->get_id();
                $pName = $product->get_name();
                $pImage = get_the_post_thumbnail_url($id);
                $pDescription = str_replace("\"", '\\"', $product->get_description());
                $pPrice = $product->get_regular_price();
                $pCurrency = get_option('woocommerce_currency');
                $pQty = $product->get_stock_quantity();
                $pRating = $product->get_average_rating();
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
                          "rating": "' . $pRating . '"
                        }
                    </script>';
            }
        }
    }