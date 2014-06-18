<?php

class TM_FacebookLB_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getCategoryLikeButton($product)
    {
        $result = '';
        $productUrl = $product->getProductUrl();

        if (Mage::getStoreConfig('facebooklb/category_products/enabled')) {
            $send = 'false';
            if (Mage::getStoreConfig('facebooklb/category_products/send')) {
                $send = 'true';
            }
            $showFaces = 'false';
            if (Mage::getStoreConfig('facebooklb/category_products/showfaces')) {
                $showFaces = 'true';
            }
            $layout = Mage::getStoreConfig('facebooklb/category_products/layout');
            $color = Mage::getStoreConfig('facebooklb/category_products/color');
            $font = Mage::getStoreConfig('facebooklb/category_products/font');

            $result .= '
                <div>
                    <fb:like
                        href="' . $productUrl . '"
                        send="' . $send . '"
                        show_faces="' . $showFaces .'"
                        layout="'. $layout .'"
                        colorscheme="'. $color .'"
                        font="'. $font .'"
                    </fb:like>
                </div>
            ';
        }

        return $result;
    }
}
