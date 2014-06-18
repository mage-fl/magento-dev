<?php
require_once("Mobile_Detect.php");

class TM_ProLabels_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_images = array();

    public function isMobileMode()
    {
        $mobileDetect = new Mobile_Detect;

        return $mobileDetect->isMobile() && !$mobileDetect->isTablet();
    }

    public function getLabel(Mage_Catalog_Model_Product $product, $mode = 'product')
    {
        if (!Mage::getStoreConfig("prolabels/general/enabled")){
            return;
        }

        if ($this->isMobileMode() && Mage::getStoreConfig("prolabels/general/mobile")) {
            return false;
        }

        $model       = Mage::getModel('prolabels/label');
        $systemModel = Mage::getModel('prolabels/system');
        $labelsData  = $model->getLabelsData($product->getId(), $mode);

        $html = '';
        foreach ($labelsData as $data) {
            if ($data[$mode . "_position"] == 'content') {
                continue;
            }
            if ($data['rules_id'] == '1' || $data['rules_id'] == '2' || $data['rules_id'] == '3') {
                if (!$data = $this->getSystemLabelsData($data, $mode)) {
                    continue;
                }
            } else {
                if (!$this->checkLabelStore($data['rules_id'])) {
                    continue;
                }
            }

            if (empty($data[$mode . '_image'])) {
                if (!$data['rules_id'] == '2' && $this->_canShowQuantity($product, $mode, $data) != 'out') {
                    continue;
                }
            }

            if ($data['rules_id'] == '1') {
                if (!$this->checkSpecailDate($product)) {
                    continue;
                }
                if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
                    if (!$this->checkSpecailDateForGroupedProduct($product)) {
                        continue;
                    }
                }
                if (!$this->_isOnSale($product, $mode, $data)) {
                    continue;
                }
            }

            if ($data['rules_id'] == '3') {
                if (!$this->checkNewDate($product)) {
                    continue;
                }
            }
            if ($mode == 'categoty') {
                $html .= $this->getCategoryProductUrl($product);
            }
            if ($data['rules_id'] == '2') {
                $out = $this->_canShowQuantity($product, $mode, $data);
                if (!$out) {
                    continue;
                }
                if ($out == 'out') {
                    if ($data[$mode . "_out_stock"] == '1' && !empty($data[$mode . "_out_stock_image"])) {
                        $labelImg = $data[$mode . "_out_stock_image"];
                        $html     .= '<span style="'
                            . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
                    }
                } else {
                    $labelImg = $data[$mode . "_image"];
                    $html .= '<span style="'
                        . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
                }
            } else {
                $labelImg = $data[$mode . "_image"];
                $html .= '<span style="'
                    . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
            }


            if (!$this->_hasLabelPosition($data[$mode . "_position"])) {

                $html .= $this->_getTableMargins(
                    $data[$mode . "_position"],
                    Mage::getBaseDir('media') . '/prolabel/' . $labelImg
                );
            }
            $imgPath = Mage::getBaseDir('media') . '/prolabel/' . $labelImg;
            $onClick = '';
            if ($mode == "category") {
                $separator = "'";
                $onClick = 'onclick="document.location='.$separator . $product->getProductUrl(). $separator .'"';
            }
            $html .= $data[$mode . '_position_style'].'"
                    class = "prolabel '
                    . $data[$mode . '_position'] . '">
                <span class="prolabels-image" ' . $onClick . ' style="background: url(' . Mage::getBaseUrl('media') .
                     'prolabel/' .
                     $labelImg .
                ') no-repeat 0 0;cursor:pointer;'.$this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg).'">' .
                $this->_getProductUrl($product, $imgPath, $mode, $data) .

                '</span>
                </span>';
                if ($mode == 'categoty') {
                    $html .= '</a>';
                }
        }
        return $html;
    }

    public function getMobileLabels($product, $mode)
    {
        if (!Mage::getStoreConfig("prolabels/general/mobile")) {
            return "";
        }
        $model       = Mage::getModel('prolabels/label');
        $systemModel = Mage::getModel('prolabels/system');
        $labelsData  = $model->getLabelsData($product->getId(), $mode);
    
        $html = '<li>';

        foreach ($labelsData as $data) {
            if ($data[$mode . "_position"] == 'content') {
                continue;
            }

            if ($data['rules_id'] == '1' || $data['rules_id'] == '2' || $data['rules_id'] == '3') {
                if (!$data = $this->getSystemLabelsData($data, $mode)) {
                    continue;
                }
            } else {
                if (!$this->checkLabelStore($data['rules_id'])) {
                    continue;
                }
            }

            if (empty($data[$mode . '_image'])) {
                if (!$data['rules_id'] == '2' && $this->_canShowQuantity($product, $mode, $data) != 'out') {
                    continue;
                }
            }

            if ($data['rules_id'] == '1') {
                if (!$this->checkSpecailDate($product)) {
                    continue;
                }
                if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
                    if (!$this->checkSpecailDateForGroupedProduct($product)) {
                        continue;
                    }
                }
                if (!$this->_isOnSale($product, $mode, $data)) {
                    continue;
                }
            }

            if ($data['rules_id'] == '3') {
                if (!$this->checkNewDate($product)) {
                    continue;
                }
            }
            if ($mode == 'categoty') {
                $html .= $this->getCategoryProductUrl($product);
            }
            if ($data['rules_id'] == '2') {
                $out = $this->_canShowQuantity($product, $mode, $data);
                if (!$out) {
                    continue;
                }
                if ($out == 'out') {
                    if ($data[$mode . "_out_stock"] == '1' && !empty($data[$mode . "_out_stock_image"])) {
                        $labelImg = $data[$mode . "_out_stock_image"];
                        $html     .= '<span style="'
                            . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
                    }
                } else {
                    $labelImg = $data[$mode . "_image"];
                    $html .= '<span  style="'
                        . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
                }
            } else {
                $labelImg = $data[$mode . "_image"];
                $html .= '<span style="'
                    . $this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg);
            }


            if (!$this->_hasLabelPosition($data[$mode . "_position"])) {

                $html .= $this->_getTableMargins(
                    $data[$mode . "_position"],
                    Mage::getBaseDir('media') . '/prolabel/' . $labelImg
                );
            }
            $imgPath = Mage::getBaseDir('media') . '/prolabel/' . $labelImg;
            $onClick = '';
            if ($mode == "category") {
                $separator = "'";
                $onClick = 'onclick="return false;"';
            }
            $html .= $data[$mode . '_position_style'].'"
                    class = "prolabel-mobile">
                <span class="prolabels-image-mobile" ' . $onClick . ' style="background: url(' . Mage::getBaseUrl('media') .
                     'prolabel/' .
                     $labelImg .
                ') no-repeat 0 0;cursor:pointer;'.$this->_getTableSize(Mage::getBaseDir('media') . "/prolabel/" . $labelImg).'">' .
                $this->_getProductUrl($product, $imgPath, $mode, $data) .

                '</span>
                </span>';
                if ($mode == 'categoty') {
                    $html .= '</a>';
                }
        }
        $html .= '</li>';
        return $html;
    }

    public function getSystemLabelsData($data, $mode)
    {
        $systemModel = Mage::getModel('prolabels/system');
        $systemData  = $systemModel->getSystemLabelsData($data['rules_id']);

        $data = null;
        foreach ($systemData as $sysLabel) {
            if ($sysLabel[$mode . "_position"] == 'content') {
                continue;
            }
            if (!$this->checkSystemLabelStore($sysLabel['system_id'])) {
                continue;
            }
            $data = $sysLabel;
        }
        if (!$data) {
            return false;
        }
        return $data;
    }

    public function checkLabelStore($labelId)
    {
        $model    = Mage::getModel('prolabels/label');
        $storeIds = $model->lookupStoreIds($labelId);
        $storeId  = Mage::app()->getStore()->getStoreId();
        if (count($storeIds) > 0) {
            if ($storeIds[0] == 0) {
                return true;
            } elseif (in_array($storeId, $storeIds)) {
                return true;
            }
        }

        return false;
    }

    public function checkSystemLabelStore($labelId)
    {
        $model    = Mage::getModel('prolabels/system');
        $storeIds = $model->getStoreIds($labelId);
        $storeId  = Mage::app()->getStore()->getStoreId();
        if (count($storeIds) > 0) {
            if ($storeIds[0] == 0) {
                return true;
            } elseif (in_array($storeId, $storeIds)) {
                return true;
            }
        }

        return false;
    }

    public function _hasLabelPosition($conf)
    {
        if (empty($conf)) {
            return false;
        }
        preg_match_all("/(top|left|rigth|bottom)\s*:/", $conf, $matches);

        return (bool)count($matches[0]);
    }

    public function getGroupedProductPriceAmount($product)
    {
        if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
            $simpleProductIds = $product->getTypeInstance()->getAssociatedProductIds();
            $price = 0;
            $finalPrice = 0;
            $sum = 0;
            $maxResult = 0;
            foreach ($simpleProductIds as $simpleProductId) {
                $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
                if (!$this->checkSpecailDate($simpleProduct)) {
                    continue;
                }

                if ($simpleProduct->getData('special_price')) {
                    $finalPrice = $simpleProduct->getData('special_price');
                    $price = $simpleProduct->getData('price');
                    $sum = ($price - $finalPrice);
                    if ($sum > $maxResult) {
                        $maxResult = $sum;
                    }
                }
            }

            if ($maxResult > 0) {
                return $maxResult;
            }

            return false;
        }
    }

    public function getGroupedProductPricePercent($product, $label, $mode)
    {
        if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
            $simpleProductIds = $product->getTypeInstance()->getAssociatedProductIds();
            $price = 0;
            $finalPrice = 0;
            $maxResult = 0;
            $result = 0;
            foreach ($simpleProductIds as $simpleProductId) {
                $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
                if (!$this->checkSpecailDate($simpleProduct)) {
                    continue;
                }

                if ($simpleProduct->getData('special_price')) {
                    $finalPrice = $simpleProduct->getData('special_price');
                    $price = $simpleProduct->getData('price');
                    $result = (100- ($finalPrice * 100 / $price)) / $label[$mode . '_round'];
                    if ($result > $maxResult) {
                        $maxResult = $result;
                    }
                }

            }

            return $maxResult;
        }
    }

    /**
     * @param Mage_Catalog_Model_Abstract $object
     * @param string $mode [onsale|recommended|stock|new]
     * @return string
     */
    public function _getText(Mage_Catalog_Model_Abstract $object, $mode, $label)
    {
        if ($label['rules_id'] == '2') {
            if ($this->_canShowQuantity($object, $mode, $label) == 'out') {
                $pattern = $label[$mode . '_out_text'];
            } else {
                $pattern = $label[$mode . '_image_text'];
            }

        } else {
            $pattern = $label[$mode . '_image_text'];
        }

        preg_match_all('/#.+?#/', $pattern, $vars);
        $data = array();
//        $this->_isOnSale($object, $mode, $label);
        foreach (current($vars) as $var) {

            if (strpos($var, '#attr:') !== false) {
                $attribute = str_replace('#attr:', '', $var);
                $attribute = str_replace('#', '', $attribute);
                $attribute = $object->getResource()->getAttribute($attribute);
                $data[$var] = $attribute->getFrontend()->getValue($object);

                continue;
            }

            if ($var == '#discount_amount#') {
                if ($object->getData('type_id') === 'bundle') {
                    $price = $object->getPriceModel()->getPrices($object);
                    $fullPrice = ($price[1] * 100) / ($object->getData('special_price'));
                    $data[$var] = $fullPrice - $price[1];
                } elseif ($object->getData('type_id') === 'grouped') {

                    if ($this->getGroupedProductPriceAmount($object)) {
                        $data[$var] = $this->getGroupedProductPriceAmount($object);
                    }
                } else {
                    if ($object->getData('final_price')) {
                        $data[$var] = $object->getData('price') - $object->getData('final_price');
                    } else {
                        $data[$var] = $object->getData('price') - $object->getData('special_price');
                    }

                }

                $data[$var] = $data[$var] / $label[$mode . '_round'];
                $roundMethod = $label[$mode . '_round_method'];
                $data[$var] = $roundMethod($data[$var]);
                $data[$var] = $data[$var] * $label[$mode . '_round'];
                $data[$var] = Mage::helper('core')->currency($data[$var], true);
                $tmp = str_replace('<span class="price">', '', $data[$var]);
                $newTmp = str_replace('</span>', '', $tmp);
                $data[$var] = $newTmp;

                if ($object->getData('type_id') === 'bundle') {
                    $data[$var] = Mage::helper('prolabels')->__('up to ') . $data[$var];
                }
                if ($object->getData('type_id') === 'grouped') {
                    $data[$var] = Mage::helper('prolabels')->__('up to ') . $data[$var];
                }
                continue;
            }
            if ($var == '#special_date#') {
                if ($object->getData('special_to_date')){
                    $currentData = Mage::app()->getLocale()->date();
                    $subtractingDate = Mage::app()->getLocale()->date($object->getData('special_to_date'))->sub($currentData);
                    $toDate = $object->getData('special_to_date');
                    $data[$var] = $this->_subtractingDate($toDate);
                }
                continue;
            }
            if ($var == '#discount_percent#') {

                if ($object->getData('type_id') === 'bundle') {
                    $data[$var] = (100 - $object->getData('special_price')) / $label[$mode . '_round'];

                } elseif ($object->getData('type_id') === 'grouped') {

                    if ($this->getGroupedProductPricePercent($object, $label, $mode)) {
                        $data[$var] = $this->getGroupedProductPricePercent($object, $label, $mode);
                    }

                } else {
                    if ($object->getData('final_price')) {
                        $data[$var] = (100 - $object->getData('final_price') * 100 / $object->getData('price')) / $label[$mode . '_round'];
                    } else {
                        $data[$var] = (100 - $object->getData('special_price') * 100 / $object->getData('price')) / $label[$mode . '_round'];
                    }

                }

                $roundMethod = $label[$mode . '_round_method'];

                $data[$var] = $roundMethod($data[$var]);
                $data[$var] = $data[$var] * $label[$mode . '_round'];
                if ($object->getData('type_id') === 'grouped') {
                    $data[$var] = Mage::helper('prolabels')->__('up to ') . $data[$var];
                }
                continue;
            }
            if ($var == '#stock_item#') {
                $qty = $this->_canShowQuantity($object, $mode, $label);
                if ($qty && $qty != 'out') {
                    $data[$var] = (int)$this->_canShowQuantity($object, $mode, $label);
                } else {
                    $data[$var] = '';
                }
                continue;
            }

            if ($var == '#special_price#') {
                $price = Mage::helper('tax')->getPrice($object, $object->getFinalPrice(), true);
                $data[$var] = $price;
                $data[$var] = $data[$var] / $label[$mode . '_round'];
                $roundMethod = $label[$mode . '_round_method'];
                $data[$var] = $roundMethod($data[$var]);
                $data[$var] = $data[$var] * $label[$mode . '_round'];
                $data[$var] = Mage::helper('core')->currency($data[$var], true);
                continue;
            }

            if ($var == '#price#') {
                $price = Mage::helper('tax')->getPrice($object, $object->getPrice(), true);
                $data[$var] = $price;
                $data[$var] = $data[$var] / $label[$mode . '_round'];
                $roundMethod = $label[$mode . '_round_method'];
                $data[$var] = $roundMethod($data[$var]);
                $data[$var] = $data[$var] * $label[$mode . '_round'];
                $data[$var] = Mage::helper('core')->currency($data[$var], true);
                continue;
            }

            if ($var == '#final_price#') {
                $price = Mage::helper('tax')->getPrice($object, $object->getFinalPrice(), true);
                $data[$var] = $price;
                $data[$var] = $data[$var] / $label[$mode . '_round'];
                $roundMethod = $label[$mode . '_round_method'];
                $data[$var] = $roundMethod($data[$var]);
                $data[$var] = $data[$var] * $label[$mode . '_round'];
                $data[$var] = Mage::helper('core')->currency($data[$var], true);
                continue;
            }

            if ($var == '#product_name#') {
                $data[$var] = $object->getName();
                continue;
            }
            if ($var == '#product_sku#') {
                $data[$var] = $object->getSku();
                continue;
            }

            $data[$var] = $object->getData(substr($var, 1, -1));
        }

        return str_replace(array_keys($data), $data, $pattern);
    }

    protected function _subtractingDate($toDate)
    {
     $blocks = array (
             array('year',  (3600 * 24 * 365)),
             array('month', (3600 * 24 * 30)),
             array('week',  (3600 * 24 * 7)),
             array('day',   (3600 * 24)),
             array('hour',  (3600)),
             array('min',   (60)),
             array('sec',   (1))
         );

         $argtime = strtotime($toDate);
         $nowtime = Mage::app()->getLocale()->date()->getTimestamp();

         $diff    = $argtime - $nowtime;

         $res = array ();

         for ($i = 0; $i < count($blocks); $i++) {
             $title = $blocks[$i][0];
             $calc  = $blocks[$i][1];
             $units = floor($diff / $calc);
             if ($units > 0) {
                 $res[$title] = $units;
             }
         }

         if (isset($res['year']) && $res['year'] > 0) {
             if (isset($res['month']) && $res['month'] > 0) {
                 $format      = "%s %s %s %s";
                 $year_label  = $res['year'] > 1 ? 'years' : 'year';
                 $month_label = $res['month'] > 1 ? 'months' : 'month';
                 return sprintf($format, $res['year'], $year_label, ($res['month']-$res['year']*12), $month_label);
             } else {
                 $format     = "%s %s";
                 $year_label = $res['year'] > 1 ? 'years' : 'year';
                 return sprintf($format, $res['year'], $year_label);
             }
         }

         if (isset($res['month']) && $res['month'] > 0) {
             if (isset($res['week']) && $res['week'] > 0) {
                 $format      = "%s %s %s %s";
                 $month_label = $res['month'] > 1 ? 'months' : 'month';
                 $week_label   = $res['week'] > 1 ? 'weeks' : 'week';
                 return sprintf($format, $res['month'], $month_label, ($res['week']-$res['month']*4), $week_label);
             } else {
                $format      = "%s %s";
                 $month_label = $res['month'] > 1 ? 'months' : 'month';
                 return sprintf($format, $res['month'], $month_label);
             }
         }

         if (isset($res['week']) && $res['week'] > 0) {
             if (isset($res['day']) && $res['day'] > 0) {
                 $format      = "%s %s %s %s";
                 $week_label = $res['month'] > 1 ? 'weeks' : 'week';
                 $day_label   = $res['week'] > 1 ? 'days' : 'day';
                 return sprintf($format, $res['week'], $week_label, ($res['day']-$res['week']*7), $day_label);
             } else {
                $format      = "%s %s";
                 $week_label = $res['week'] > 1 ? 'weeks' : 'week';
                 return sprintf($format, $res['week'], $week_label);
             }
         }

         if (isset($res['day']) && $res['day'] > 0) {
             if (isset($res['hour']) && $res['hour'] > 0) {
                 $format      = "%s %s %s %s";
                 $hour_label = $res['hour'] > 1 ? 'hours' : 'hour';
                 $day_label   = $res['day'] > 1 ? 'days' : 'day';
                 return sprintf($format, $res['day'], $day_label, ($res['hour']-$res['day']*24), $hour_label);
             } else {
                $format      = "%s %s";
                 $day_label = $res['day'] > 1 ? 'days' : 'day';
                 return sprintf($format, $res['day'], $day_label);
             }
         }

         if (isset($res['hour']) && $res['hour'] > 0) {
             if (isset($res['min']) && $res['min'] > 0) {
                 $format      = "%s %s %s %s";
                 $hour_label = $res['hour'] > 1 ? 'hours' : 'hour';
                 $min_label   = $res['min'] > 1 ? 'minuts' : 'minut';
                 return sprintf($format, $res['hour'], $hour_label, ($res['min']-$res['hour']*60), $min_label);
             } else {
                $format      = "%s %s";
                 $hour_label = $res['hour'] > 1 ? 'hours' : 'hour';
                 return sprintf($format, $res['hour'], $hour_label);
             }
         }

         if (isset($res['min']) && $res['min'] > 0) {
             if (isset($res['sec']) && $res['sec'] > 0) {
                 $format      = "%s %s %s %s";
                 $min_label = $res['min'] > 1 ? 'minuts' : 'minut';
                 $sec_label   = $res['min'] > 1 ? 'seconds' : 'second';
                 return sprintf($format, $res['min'], $min_label, ($res['sec']-$res['min']*60), $sec_label);
             } else {
                $format      = "%s %s";
                 $hour_label = $res['hour'] > 1 ? 'hours' : 'hour';
                 return sprintf($format, $res['hour'], $hour_label);
             }
         }

         if (isset ($res['sec']) && $res['sec'] > 0) {
             if ($res['sec'] == 1) {
                 return "One second ago";
             } else {
                 return sprintf("%s seconds", $res['sec']);
             }
         }
    }

    public function getCategoryProductUrl($product)
    {
        $url = $product->getProductUrl();
        $style = "style='width:auto;height:auto;'";

        return "<a href='{$url}' {$style}>$text";
    }

    protected function _getProductUrl(Mage_Catalog_Model_Product $product, $imgPath, $mode, $data)
    {
        $text = $this->_getText($product, $mode, $data);

        if ('' !== $text) {
            $fontstyle = $data[$mode . '_font_style'];
            $text = "<span class='productlabeltext' style='{$fontstyle}'>{$text}</span>";
        } else {
            $text = '&nbsp;';
        }

        //if ($mode == 'product') {
            return $text;
        //}

        // $height = $this->_getImage($imgPath)->getOriginalHeight();
        // $width = $this->_getImage($imgPath)->getOriginalWidth();
    }

    protected function _getImage($path)
    {
        try {
            if (!isset($this->_images[$path])) {
                $this->_images[$path] = new Varien_Image($path);
            }
            return $this->_images[$path];
        } catch (Exception $e) {
            return null;
        }
    }

    protected function _isNew($product, $mode)
    {
        if (!Mage::getStoreConfig("prolabels/{$mode}new/display")) {
            return false;
        }

        $from = Mage::getModel('core/date')->timestamp($product->getData('news_from_date'));
        $to = Mage::getModel('core/date')->timestamp($product->getData('news_to_date'));
        // $from   = strtotime($product->getData('news_from_date'));
        // $to     = strtotime($product->getData('news_to_date'));
        $date = new Zend_Date();
        $today = Mage::getModel('core/date')->timestamp(time());
        // $today = strtotime($date->__toString());

        if ($from && $today < $from) {
            return false;
        }
        if ($to && $today > $to) {
            return false;
        }
        if (!$to && !$from) {
            return false;
        }
        return true;
    }

    public function _isOnSale($product, $mode, $data)
    {
        if (!$this->checkSpecailDate($product)) {
            return false;
        }
        $pattern = $data[$mode . '_image_text'];
        preg_match_all('/#.+?#/', $pattern, $vars);
        foreach ($vars as $var) {
            if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
                if (($var[0] === '#special_price#') || ($var[0] === '#special_date#') || ($var[0] === '#final_price#') || ($var[0] === '#price#')) {
                    return false;
                }
            }
            if ($product->getTypeInstance() instanceof Mage_Bundle_Model_Product_Type) {
                if (($var[0] === '#special_price#') || ($var[0] === '#special_date#') || ($var[0] === '#final_price#') || ($var[0] === '#price#')) {
                    return false;
                }
            }
        }

        if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
            $simpleProductIds = $product->getTypeInstance()->getAssociatedProductIds();
            $price = 0;
            $finalPrice = 0;
            $i = 0;
            foreach ($simpleProductIds as $simpleProductId) {
                $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);

                if ($simpleProduct->getData('special_price')) {
                    $finalPrice = $simpleProduct->getData('special_price');
                    $price = $simpleProduct->getData('price');
                    if ($i == 0) {
                        Mage::unregister('prolabelprice');
                        Mage::unregister('prolabelfinalprice');
                        Mage::register('prolabelprice', $price);
                        Mage::register('prolabelfinalprice', $finalPrice);
                    }

                    if ($i > 0) {
                        if (($price - $finalPrice) > (Mage::registry('prolabelprice') - Mage::registry('prolabelfinalprice'))) {
                            Mage::unregister('prolabelprice');
                            Mage::unregister('prolabelfinalprice');
                            Mage::register('prolabelprice', $price);
                            Mage::register('prolabelfinalprice', $finalPrice);
                        }
                    }
                $i++;
                }
            }
            if (Mage::registry('prolabelfinalprice') && Mage::registry('prolabelprice') ) {
                return true;
            }

            return false;
        }

        if ($product->getTypeInstance() instanceof Mage_Bundle_Model_Product_Type) {
            if ($product->getData('special_price')) {
                return true;
            }
        }else {
            if (Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice())) {
                return true;
            } elseif ($product->getData('final_price')) {
                if ($product->getData('price') > $product->getData('final_price')) {
                    return true;
                }
            } else {
                if ($product->getData('price') > $product->getData('special_price')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function checkSpecailDate($product)
    {
        $today = Mage::getModel('core/date')->timestamp(time());

        if (!$product->getData('special_from_date') && !$product->getData('special_to_date')) {
            return false;
        }

        if ($product->getData('special_from_date') && null === $product->getData('special_to_date')) {
            $from = Mage::getModel('core/date')->timestamp($product->getData('special_from_date'));
            if ($today > $from) {
                return true;
            }
            return false;
        }

        if ($product->getData('special_to_date') && null === $product->getData('special_from_date')) {
            $to =  Mage::getModel('core/date')->timestamp($product->getData('special_to_date'));
            if ($today < $to) {
                return true;
            }
            return false;
        }
        $from = Mage::getModel('core/date')->timestamp($product->getData('special_from_date'));
        $to =  Mage::getModel('core/date')->timestamp($product->getData('special_to_date'));

        if ($from && $today < $from) {
            return false;
        }
        if ($to && $today > $to) {
            return false;
        }
        if (!$to && !$from) {
            return false;
        }
        return true;
    }

    public function checkNewDate($product)
    {
        $today = Mage::getModel('core/date')->timestamp(time());
        
        if (!$product->getData('news_from_date') && !$product->getData('news_to_date')) {
            return false;
        }
        
        if ($product->getData('news_from_date') && null === $product->getData('news_to_date')) {
            $from = Mage::getModel('core/date')->timestamp($product->getData('news_from_date'));
            if ($today > $from) {
                return true;
            }
            return false;
        }

        if ($product->getData('news_to_date') && null === $product->getData('news_from_date')) {
            $to =  Mage::getModel('core/date')->timestamp($product->getData('news_to_date'));
            if ($today < $to) {
                return true;
            }
            return false;
        }
        if (!$product->getData('news_from_date') && !$product->getData('news_to_date')) {
            return false;
        }
        $from = Mage::getModel('core/date')->timestamp($product->getData('news_from_date'));
        $to =  Mage::getModel('core/date')->timestamp($product->getData('news_to_date'));

        if ($from && $today < $from) {
            return false;
        }
        if ($to && $today > $to) {
            return false;
        }
        if (!$to && !$from) {
            return false;
        }

        return true;
    }

    public function _canShowQuantity($product, $mode, $data)
    {
        if (!$product->getData('stock_item')->is_in_stock) {
            return 'out';
        }
        $quantity = 0;
        if ($product->isConfigurable()) {
            $model = new Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable();
            $simpleProductIds = $model->getChildrenIds($product->getId());
            foreach (current($simpleProductIds) as $productId) {
                $simpleProduct = Mage::getModel('catalog/product')->load($productId);
                $productQty = $simpleProduct->getData('stock_item')->qty;
                $quantity = $quantity + (int)$productQty;
            }
        } elseif ($product->getTypeInstance() instanceof Mage_Bundle_Model_Product_Type) {
            if ($mode = 'category') {
                $childIds = $product->getTypeInstance()->getChildrenIds($product->getId());
                $simpleQty = 0;
                $sum = array();
                foreach ($childIds as $childId) {
                    foreach ($childId as $simpleId) {
                        $simpleProduct = Mage::getModel('catalog/product')->load($simpleId);
                        $simpleQty += $simpleProduct->getData('stock_item')->qty;
                    }
                    $sum[] = $simpleQty;
                    $simpleQty = 0;
                }
                $quantity = min($sum);
            } else {
                $groupSum = array();
                foreach ($product->getTypeInstance()->getOptions() as $option) {
                    if (!$option->getData('required')) {
                        continue;
                    }
                    foreach ($option->getSelections() as $simpleProduct) {

                        $sum += $simpleProduct->getData('stock_item')->qty;
                    }
                    $groupSum[] = $sum;
                    $sum = 0;
                }

                $quantity = min($groupSum);
            }
        } else {
            if ($mode == 'category') {
                $quantity = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
            } else {
                $quantity = $product->getData('stock_item')->qty;
            }

        }
        if ($quantity > 0 && $quantity < (int)$data[$mode . '_min_stock']) {
            return $quantity;
        }
        return false;
    }

    protected function _isRecommended($product, $mode)
    {
        if (!Mage::getStoreConfig("prolabels/{$mode}recommended/display")) {
            return false;
        }

        if ((int)$product->getData(Mage::getStoreConfig("prolabels/{$mode}recommended/attributeid")) == 1){
            return true;
        }

        return false;
    }

    protected function _getTableMargins($position, $imagePath)
    {
        if (null === ($image = $this->_getImage($imagePath))) {
            return '';
        }
        switch ($position) {
            case 'top-center':
                $width = - $image->getOriginalWidth() / 2;
                return "margin-left:{$width}px;";
            case 'middle-left':
                $height = - $image->getOriginalHeight() / 2;
                return "margin-top:{$height}px;";
            case 'middle-right':
                $height = - $image->getOriginalHeight() / 2;
                return "margin-top:{$height}px;";
            case 'bottom-center':
                $width = - $image->getOriginalWidth() / 2;
                return "margin-right:{$width}px;";
            case 'middle-center':
                $width = - $image->getOriginalWidth() / 2;
                $height = - $image->getOriginalHeight() / 2;
                return "margin-right:{$width}px; margin-top:{$height}px;";
            default:
                return '';
        }
    }

    protected function _getTableSize($imagePath)
    {

        if (null === ($image = $this->_getImage($imagePath))) {
            return '';
        }
        return "width:{$image->getOriginalWidth()}px; height:{$image->getOriginalHeight()}px;";
    }

    protected function _loadProduct(Mage_Catalog_Model_Product $product)
    {
        $product->load($product->getId());
    }

    public function checkSpecailDateForGroupedProduct($product)
    {
        if ($product->getTypeInstance() instanceof Mage_Catalog_Model_Product_Type_Grouped) {
            $simpleProductIds = $product->getTypeInstance()->getAssociatedProductIds();

            $result = 0;
            foreach ($simpleProductIds as $simpleProductId) {
                $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
                if ($simpleProduct->getData('special_price')) {
                    if ($this->checkSpecailDate($simpleProduct)) {
                        $result++;
                    }
                }
            }
            if ($result > 0) {
                return true;
            }

            return false;
        }
    }
}
