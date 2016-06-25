<?php
require('connector.php');

class AccHistorial extends Connector {

	public $acc_his_id;
	public $usu_det_id;
	public $acc_his_fecha;

	function _construct(){
		$this->acc_his_id = "";
		$this->usu_det_id = "";
		$this->usu_det_id = "";
	}

	public function getAccHisId(){
		return Connector::ConexionBD();
	}

	public function Seleccion($tableName){
		$connector = Connector::ConexionBD();
		return Connector::SelectAll($tableName,$connector['conexion']);
	}
}

$test = new AccHistorial();
$res = $test->Seleccion("persona");
print_r($res['contenido']);

?>