<?php
require('connector.php');
class CatProducto extends Connector{
	public $attr_connector;
	public $table_name;
	public $cat_pro_fecha;
	public $cat_pro_id;
	public $cat_pro_nombre;

	public function __construct($cat_pro_fecha,$cat_pro_id,$cat_pro_nombre){
		$connector = Connector::ConexionBD();
		$this->attr_connector = $connector['conexion'];
		$this->table_name ="cat_producto";
		$seleccion;
		if($cat_pro_fecha != null || $cat_pro_id != null || $cat_pro_nombre != null ){
			if(!is_null($cat_pro_fecha)){
				$seleccion = array("cat_pro_fecha",$cat_pro_fecha);
			}
			if(!is_null($cat_pro_id)){
				$seleccion = array("cat_pro_id",$cat_pro_id);
			}
			if(!is_null($cat_pro_nombre)){
				$seleccion = array("cat_pro_nombre",$cat_pro_nombre);
			}
			$this->objeto = Connector::SelectIn($this->attr_connector, $this->table_name, $seleccion);
		}
		else{
			$this->objeto = null;
		}
		}
	public function getCatProFecha(){
			$this->cat_producto['cat_pro_fecha'] = $this->objeto['contenido'][0]['cat_pro_fecha'];
			return $this->cat_pro_fecha['cat_pro_fecha'];
		}
	public function setCatProFecha($cat_pro_fecha){
			$this->cat_producto['cat_pro_fecha'] = $cat_pro_fecha;
		}
	public function getCatProId(){
			$this->cat_producto['cat_pro_id'] = $this->objeto['contenido'][0]['cat_pro_id'];
			return $this->cat_pro_id['cat_pro_id'];
		}
	public function setCatProId($cat_pro_id){
			$this->cat_producto['cat_pro_id'] = $cat_pro_id;
		}
	public function getCatProNombre(){
			$this->cat_producto['cat_pro_nombre'] = $this->objeto['contenido'][0]['cat_pro_nombre'];
			return $this->cat_pro_nombre['cat_pro_nombre'];
		}
	public function setCatProNombre($cat_pro_nombre){
			$this->cat_producto['cat_pro_nombre'] = $cat_pro_nombre;
		}
	public function saveCatProducto(){
			return Connector::InsertIn($this->attr_connector,$this->table_name,$this->cat_producto);
		}
	public function updateCatProducto($cat_pro_id){
			$cat_pro_id = array("cat_pro_id",$cat_pro_id);
			return Connector::UpdateIn($this->attr_connector,$this->table_name,$this->cat_producto,$cat_pro_id);
		}
	public function deleteCatProducto($cat_pro_id){
			return Connector::DeleteIn($this->attr_connector,$this->table,"cat_pro_id",$cat_pro_id);
		}
}
?>