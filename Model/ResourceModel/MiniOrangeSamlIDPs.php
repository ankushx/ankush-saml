<?php 
namespace MiniOrange\SP\Model\ResourceModel;
class MiniOrangeSamlIDPs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
 public function _construct(){
 $this->_init("miniorange_saml_idps","id");
 }
}