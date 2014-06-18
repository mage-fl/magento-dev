<?php

class TM_Attributepages_Block_Product_Option extends Mage_Core_Block_Template
{
    /**
     * Get template path to render
     *
     * @return string
     */
    public function getTemplate()
    {
        $template = parent::getTemplate();
        if (null === $template) {
            $template = $this->_getData('template');
        }
        if (null === $template) {
            $template = 'tm/attributepages/product/option.phtml';
        }
        return $template;
    }

    /**
     * Retrieve product to use for output
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        $product = $this->_getData('product');
        if (null === $product) {
            $product = Mage::registry('current_product');
            $this->setData('product', $product);
        }
        return $product;
    }

    /**
     * Set the attribute_code to display
     *
     * @param  string $code
     * @return TM_Attributepages_Block_Product_Option
     */
    public function setAttributeCode($code)
    {
        $this->setData('attribute_code', $code);
        return $this;
    }

    /**
     * Set the parent page identifier.
     * Useful when the attribute is used on multiple attributepages
     *  For example:
     *      manufacturer/amd
     *      computer-brands/amd
     *
     * @param  string $identifier
     * @return TM_Attributepages_Block_Product_Option
     */
    public function setParentPageIdentifier($identifier)
    {
        $this->setData('page_identifier', $identifier);
        return $this;
    }

    /**
     * Set the product to use
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return TM_Attributepages_Block_Product_Option
     */
    public function setProduct(Mage_Catalog_Model_Product $product)
    {
        $this->setData('product', $product);
        return $this;
    }
}
