<?php

class Psl_Import_Helper_Importproducts extends Mage_Core_Helper_Abstract
{
    
    private $_openFile;
    private $_mappedAttributes = array();
    private $_nonRealAttributes = array('status','visibility','category_ids','tax_class_id','website_ids','attribute_set_id');
    private $_websites;
    private $_attributeSets;

    /**
     * Processing of the file uploaded
     *
     * @param string $path the file uploaded to the server
     * @param string $file the file name
     * @return array Messages of success or error for created products
     */
    public function processFile($path,$file){
                
        setlocale(LC_ALL, 'en_US.UTF-8');
        iconv_set_encoding("internal_encoding", "UTF-8");

        $this->_openFile = fopen($path,"r") or die("Error al abrir el fichero");        

        //Get first row of file which contains the headers
        $columnsMap = array_flip(fgetcsv($this->_openFile));

        $this->mapColumnsAndConfiguration($columnsMap);

        return $this->createProduct();
    }

    /**
     * Map the columns in the file with the configuration
     *
     * @param array $columnsMap  Array with the headers of the file
     * @return array
     */
    public function mapColumnsAndConfiguration($columnsMap){
        $mapConfig = unserialize(Mage::getStoreConfig('psl_import/process/fields_mapping'));
        $mapConfigNa = unserialize(Mage::getStoreConfig('psl_import/process/non_attributes_values'));

        foreach(Mage::app()->getWebsites() as $website){
            $this->_websites[$website->getName()] = $website->getId();
        }

        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
        ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId());      
        foreach($attributeSets as $atr){
            $this->_attributeSets[$atr->getAttributeSetName()] = $atr->getAttributeSetId();
        }

        $mapConfig = array_merge($mapConfig,$mapConfigNa);

        foreach($mapConfig as $key=>$data){
            if(array_key_exists(utf8_decode($data['remote_attribute']), $columnsMap)){
                $this->_mappedAttributes[$columnsMap[$data['remote_attribute']]] = array('label'=>$data['local_attribute'],'use_value'=>$data['use_value']);
            }
        }
        ksort($this->_mappedAttributes);
        
        $this->getAttributesType();

        return;
    }

    /**
     * Create the necessary products for each row of the file
     *
     * @return array Messages of success or error for created products
     */
    public function createProduct(){

        $productData = array();
        $messages = array();
        $saved = 0;
        $row = 2;

        while (($data = fgetcsv($this->_openFile)) !== false ) {
            $product = new Mage_Catalog_Model_Product();
            foreach($this->_mappedAttributes as $key=>$value){
                if(($value['type']=='select' && $this->_mappedAttributes[$key]['use_value']=='label') || (in_array($value['label'],array('category_ids','website_ids','attribute_set_id')) && $this->_mappedAttributes[$key]['use_value']=='label') ){
                    if(isset($this->_mappedAttributes[$key][$data[$key]])){
                        $productData[$value['label']] = $this->_mappedAttributes[$key][$data[$key]];
                    }else{
                        $optionId = $this->getAttributeOption($value['label'],$data[$key],$key);
                        if(is_null($optionId)){
                            $messages['error'][] = "Attribute option '". $data[$key]."' for attribute '".$value['label']."' does not exist in file row ".$row;
                            $row++;
                            continue 2;
                        }else{
                            $productData[$value['label']] = $this->getAttributeOption($value['label'],$data[$key],$key);
                        }
                    }
                }elseif($value['label']=='qty'){
                    $productData['stock_data'] = array(
                              'manage_stock' => 1,
                              'is_in_stock' => 1,
                              'qty' => $data[$key],
                              'use_config_manage_stock' => 0
                    );
                }else{
                    $productData[$value['label']] = in_array($value['label'], array('category_ids','website_ids'))?explode("|",  utf8_decode($data[$key])):$data[$key];
                }
            }
            $productData['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;

            Mage::log("Trying to assign next data for product in row $row",null,'importProducts.log');
            Mage::log(print_r($productData,true),null,'importProducts.log');
            Mage::log("----------------------------------------------------------",null,'importProducts.log');

            foreach($productData as $key=>$data){
                $product->setData($key,$data);
            }
            try{
                $product->save($product);
                $saved++;
            }catch(Exception $e){
                $messages['error'][] = "Error saving product in row ".$row." : ".$e->getMessage();
            }

            $row++;
        }
        $messages['success'] = "Correctly saved $saved product(s)";
        return $messages;
    }

    /**
     * Get the attribute option id for the defined attribute
     *
     * @param string $attribute_code name of the attribute
     * @param string $label name of the attribute option
     * @param int $key key assigned in the _mappedAttributes for that attribute
     * @return int the option ID of the found attribute option
     */
    public function getAttributeOption($attribute_code,$label,$key){
        
        $optionId = null;
        
        if(in_array($attribute_code, $this->_nonRealAttributes)){
            if($attribute_code=='category_ids'){
                $catIds = array();
                foreach(explode("|",$label) as $key=>$value){
                    $cat = Mage::getResourceModel('catalog/category_collection')
                                ->addFieldToFilter('name', $value);

                    $catIds[] = $cat->getFirstItem()->getEntityId();
                }

                $optionId = $catIds;
            }elseif($attribute_code=='website_ids'){
                foreach(explode("|",$label) as $key=>$value){
                    if(isset($this->_websites[$value])){
                        $optionId[] = $this->_websites[$value];
                    }
                }
            }elseif($attribute_code=='attribute_set_id'){
                if(isset($this->_attributeSets[$label])){
                    $optionId = $this->_attributeSets[$label];
                }
            }

        }else{

            $attribute_model = Mage::getSingleton('eav/entity_attribute');
            $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table');
            $attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
            $attribute = $attribute_model->load($attribute_id);

            $options = $attribute_options_model->setAttribute($attribute)->getAllOptions(false);

            foreach($options as $option)
            {
                if ($option['label'] == $label)
                {
                    $optionId = $option['value'];
                    $this->_mappedAttributes[$key][$label] = $optionId;
                    break;
                }
            }
        }

        return $optionId;
    }

    /**
     * Get the attrbute type for each mapped attribute
     *
     */
    public function getAttributesType(){
        $attribute_model = Mage::getSingleton('eav/entity_attribute');
        foreach($this->_mappedAttributes as $key=>$value){
            $attribute_id = $attribute_model->getIdByCode('catalog_product', $value['label']);
            $attribute = $attribute_model->load($attribute_id);
            $attributeType = $attribute->getFrontendInput();
            
            $this->_mappedAttributes[$key]['type'] = $attributeType;
        }
        return;
    }
}
