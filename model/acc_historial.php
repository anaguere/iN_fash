<?php
require('connector.php');
class AccHistorial extends Connector{
	public $attr_connector;
	public $table_name;
	public $acc_his_fecha;
	public $acc_his_id;
	public $acc_usu_det_id;

	public function __construct($acc_his_fecha,$acc_his_id,$acc_usu_det_id){
		$connector = Connector::ConexionBD();
		$this->attr_connector = $connector['conexion'];
		$this->table_name ="acc_historial";
		$seleccion;
		if($acc_his_fecha != null || $acc_his_id != null || $acc_usu_det_id != null ){
			if(!is_null($acc_his_fecha)){
				$seleccion = array("acc_his_fecha",$acc_his_fecha);
			}
			if(!is_null($acc_his_id)){
				$seleccion = array("acc_his_id",$acc_his_id);
			}
			if(!is_null($acc_usu_det_id)){
				$seleccion = array("acc_usu_det_id",$acc_usu_det_id);
			}
			$this->objeto = Connector::SelectIn($this->attr_connector, $this->table_name, $seleccion);
		}
		else{
			$this->objeto = null;
		}
		}
	public function getAccHisFecha(){
			$this->acc_historial['acc_his_fecha'] = $this->objeto['contenido'][0]['acc_his_fecha'];
			return $this->acc_his_fecha['acc_his_fecha'];
		}
	public function setAccHisFecha($acc_his_fecha){
			$this->acc_historial['acc_his_fecha'] = $acc_his_fecha;
		}
	public function getAccHisId(){
			$this->acc_historial['acc_his_id'] = $this->objeto['contenido'][0]['acc_his_id'];
			return $this->acc_his_id['acc_his_id'];
		}
	public function setAccHisId($acc_his_id){
			$this->acc_historial['acc_his_id'] = $acc_his_id;
		}
	public function getAccUsuDetid(){
			$this->acc_historial['acc_usu_det_id'] = $this->objeto['contenido'][0]['acc_usu_det_id'];
			return $this->acc_usu_det_id['acc_usu_det_id'];
		}
	public function setAccUsuDetid($acc_usu_det_id){
			$this->acc_historial['acc_usu_det_id'] = $acc_usu_det_id;
		}
	public function saveAccHistorial(){
			return Connector::InsertIn($this->attr_connector,$this->table_name,$this->acc_historial);
		}
	public function updateAccHistorial($acc_his_id){
			$acc_his_id = array("acc_his_id",$acc_his_id);
			return Connector::UpdateIn($this->attr_connector,$this->table_name,$this->acc_historial,$acc_his_id);
		}
	public function deleteAccHistorial($acc_his_id){
			return Connector::DeleteIn($this->attr_connector,$this->table,"acc_his_id",$acc_his_id);
		}
}
?>