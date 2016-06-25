<?php
class Connector{
	private $connector;
	private $conn;
	private $resultado;

	function _construct(){
	}

	final function ConexionBD(){
		$this->connector = json_decode(file_get_contents("../data/connectorData.json"),true);
		$this->conn = pg_connect("host=".$this->connector['host']." port=".$this->connector['port']." dbname=".$this->connector['dbname']." user=".$this->connector['user']." password=".$this->connector['password']);
		try{
			if(!$this->conn){
				throw new Exception("Ha ocurrido un problema al conectarse a la base de datos!");
			}
			$this->resultado['conexion'] = true;
			$this->resultado['mensaje'] = "Conexión satisfactoria";
			$this->resultado['conexion'] = $this->conn;
			return ($this->resultado);
		}catch (Exception $e){
			$this->resultado['conexion'] = false;
			$this->resultado['mensaje'] = $e->getMessage();

			return ($this->resultado);
		}
	}

	public function ForeignKeys($tableName,$conn){
		$foreign_keys = array();
		$search;
		$resultado;
		$this->conn = $conn;
		$this->tableName = $tableName;
		$query;
		$this->query = "SELECT tc.table_name AS main_table_name, tc.constraint_name AS main_table_foreign_key_name,kcu.column_name AS main_table_column_name,ccu.table_name AS foreign_table_name,ccu.column_name AS foreign_table_column_name
					FROM information_schema.table_constraints tc
					LEFT JOIN information_schema.key_column_usage kcu ON tc.constraint_catalog = kcu.constraint_catalog AND tc.constraint_schema = kcu.constraint_schema AND tc.constraint_name = kcu.constraint_name
					LEFT JOIN information_schema.referential_constraints rc ON tc.constraint_catalog = rc.constraint_catalog AND tc.constraint_schema = rc.constraint_schema AND tc.constraint_name = rc.constraint_name
					LEFT JOIN information_schema.constraint_column_usage ccu ON rc.unique_constraint_catalog = ccu.constraint_catalog AND rc.unique_constraint_schema = ccu.constraint_schema AND rc.unique_constraint_name = ccu.constraint_name
					WHERE lower(tc.constraint_type) in ('foreign key') and tc.table_name = '".$this->tableName."'";
		try{
			$this->search = pg_query($this->conn,$this->query);
			if(!$this->search){
				throw new Exception("Error al ejecutar sentencia!");
			}
			$this->foreign_keys = pg_fetch_all($this->search);
			$this->resultado['conexion'] = true;
			$this->resultado['contenido'] = $this->foreign_keys;
			return $this->resultado;
		}catch(Exception $e){
			$this->resultado['conexion']= false;
			$this->resultado['mensaje']= $e->getMessage();
		}
	}

	final function SelectAll($tableName,$conn){
		$query1;
		$result;
		$this->tableName = $tableName;
		$this->conn = $conn;
		$foreign_keys;
		try{
			$this->query1 = pg_query($this->conn,"SELECT * FROM ".$this->tableName);
			if(!$this->query1){
				throw  new Exception("Error al acceder a la tabla".$this->tableName);
			}
			$this->result = pg_fetch_all($this->query1);
			$this->foreign_keys = $this->ForeignKeys($this->tableName,$this->conn);
			$this->foreign_keys = $this->foreign_keys['contenido'];
			for($i=0;$i< count($this->foreign_keys);$i++){
				for($j =0;$j<count($this->result);$j++){
					$query2 = pg_query($this->conn,"SELECT * FROM ".$this->foreign_keys[$i]['foreign_table_name']." WHERE ".$this->foreign_keys[$i]['foreign_table_column_name']."=".$this->result[$j][$this->foreign_keys[$i]['main_table_column_name']]);
					$result2 = pg_fetch_all($query2);
					$keys_array = array_keys($result2[0]);
					$this->result[$j][$this->foreign_keys[$i]['main_table_column_name']] = $result2;
				}
			}
			$this->resultado['conexion'] = true;
			$this->resultado['contenido'] = $this->result;
			return $this->resultado;
		}catch(Exception $e){
			$this->resultado['conexion']=false;
			$this->resultado['mensaje'] = $e->getMessage();

			return $this->resultado;
		}
	}

};

?>