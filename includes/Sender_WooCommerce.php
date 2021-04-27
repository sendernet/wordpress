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
    }