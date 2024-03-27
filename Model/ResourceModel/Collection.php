<?php 
namespace MiniOrange\SP\Model\ResourceModel;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
	public function _construct(){
		$this->_init("MiniOrange\SP\Model\MiniorangeSamlIDPs","MiniOrange\SP\Model\ResourceModel\MiniOrangeSamlIDPs");
	}
}