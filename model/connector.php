<?php
class Connector
{
    private $connector;
    private $conn;
    private $resultado;

    public function _construct()
    {
    }

    final public function ConexionBD()
    {
        $this->connector = json_decode(file_get_contents("../data/connectorData.json"), true);
        $this->conn      = pg_connect("host=".$this->connector['host']." port=".$this->connector['port']." dbname=".$this->connector['dbname']." user=".$this->connector['user']." password=".$this->connector['password']);
        try {
            if (!$this->conn) {
                throw new Exception("Ha ocurrido un problema al conectarse a la base de datos!");
            }
            $this->resultado['conexion'] = true;
            $this->resultado['mensaje']  = "Conexión satisfactoria";
            $this->resultado['conexion'] = $this->conn;
            return ($this->resultado);
        } catch (Exception $e) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = $e->getMessage();

            return ($this->resultado);
        }
    }

    public function ForeignKeys($tableName, $conn)
    {
        $foreign_keys = array();
        $search;
        $resultado;
        $this->conn      = $conn;
        $this->tableName = $tableName;
        $query;
        $this->query = "SELECT tc.table_name AS main_table_name, tc.constraint_name AS main_table_foreign_key_name,kcu.column_name AS main_table_column_name,ccu.table_name AS foreign_table_name,ccu.column_name AS foreign_table_column_name
					FROM information_schema.table_constraints tc
					LEFT JOIN information_schema.key_column_usage kcu ON tc.constraint_catalog = kcu.constraint_catalog AND tc.constraint_schema = kcu.constraint_schema AND tc.constraint_name = kcu.constraint_name
					LEFT JOIN information_schema.referential_constraints rc ON tc.constraint_catalog = rc.constraint_catalog AND tc.constraint_schema = rc.constraint_schema AND tc.constraint_name = rc.constraint_name
					LEFT JOIN information_schema.constraint_column_usage ccu ON rc.unique_constraint_catalog = ccu.constraint_catalog AND rc.unique_constraint_schema = ccu.constraint_schema AND rc.unique_constraint_name = ccu.constraint_name
					WHERE lower(tc.constraint_type) in ('foreign key') and tc.table_name = '".$this->tableName."'";
        try {
            $this->search = pg_query($this->conn, $this->query);
            if (!$this->search) {
                throw new Exception("Error al ejecutar sentencia!");
            }
            $this->foreign_keys = pg_fetch_all($this->search);
            if ($this->foreign_keys == "") {
                $this->resultado['conexion'] = false;
            } else {
                $this->resultado['conexion'] = true;
            }
            $this->resultado['contenido'] = $this->foreign_keys;
        } catch (Exception $e) {
            $this->resultado['mensaje'] = $e->getMessage();
        }
        return $this->resultado;
    }

    final public function SelectAll($tableName, $conn)
    {
        $query1;
        $main_table_result;
        $this->tableName = $tableName;
        $this->conn      = $conn;
        $foreign_keys;
        try {
            $this->query1 = pg_query($this->conn, "SELECT * FROM ".$this->tableName);
            if (!$this->query1) {
                throw new Exception("Error al acceder a la tabla".$this->tableName);
            }
            $this->main_table_result = pg_fetch_all($this->query1);
            $this->foreign_keys      = $this->ForeignKeys($this->tableName, $this->conn);
            if ($this->foreign_keys['conexion']) {
                $this->foreign_keys = $this->foreign_keys['contenido'];
                for ($i = 0; $i < count($this->foreign_keys); $i++) {
                    for ($j = 0; $j < count($this->main_table_result); $j++) {
                        $dependency_inyection                                                           = pg_query($this->conn, "SELECT * FROM ".$this->foreign_keys[$i]['foreign_table_name']." WHERE ".$this->foreign_keys[$i]['foreign_table_column_name']."=".$this->main_table_result[$j][$this->foreign_keys[$i]['main_table_column_name']]);
                        $dependency_inyection_result                                                    = pg_fetch_all($dependency_inyection);
                        $keys_array                                                                     = array_keys($dependency_inyection_result[0]);
                        $this->main_table_result[$j][$this->foreign_keys[$i]['main_table_column_name']] = $dependency_inyection_result;
                    }
                }
            }
            $this->resultado['conexion']  = true;
            $this->resultado['contenido'] = $this->main_table_result;
            return $this->resultado;
        } catch (Exception $e) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = $e->getMessage();

            return $this->resultado;
        }
    }
    #--------------------------CREAR NUEVO REGISTRO --------------------------------------------------------
    final public function InsertIn($tableName, $conn, $column_name)
    {
        $insert_sentence;
        $sentence_exec;
        $resultado;
        $columns;
        $values;
        $this->tableName   = $tableName;
        $this->conn        = $conn;
        $this->column_name = $column_name;
        $cant_insert       = count($this->column_name);
        $this->columns     = "(";
        $this->values      = "(";
        #Insercion de multiples valores
        $k = 0;
        foreach ($this->column_name as $clave => $valor) {
            $k++;
            if ($k < $cant_insert) {
                $this->columns = $this->columns.$clave.",";
                $this->values  = $this->values."'".$valor."',";
            } else {
                $this->columns = $this->columns.$clave.")";
                $this->values  = $this->values."'".$valor."')";
            }
        }
        $this->sentence = "INSERT INTO ".$this->tableName." ".$this->columns." VALUES ".$this->values."; SELECT lastval();";
        try {
            $this->sentence_exec = pg_query($this->conn, $this->sentence);
            if (!$this->sentence_exec) {
                throw new Exception("Error al ejecutar la sentencia de insersión en la tabla ".$this->tableName);
            }
            $fk_result                            = pg_query($this->conn, "SELECT ref_ccu.column_name FROM  INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS ccu INNER JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE AS ref_ccu ON ref_ccu.CONSTRAINT_NAME = ccu.constraint_name WHERE ccu.table_name ='".$this->tableName."' AND ccu.constraint_type ='PRIMARY KEY'");
            $id_name                              = pg_fetch_all($fk_result);
            $lastval                              = pg_fetch_all($this->sentence_exec);
            $last_reg                             = array();
            $last_reg[$id_name[0]['column_name']] = $lastval[0]['lastval'];
            $this->resultado['conexion']          = true;
            $this->resultado['contenido']         = $last_reg;
        } catch (Exception $e) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = $e->getMessage();
        }
        return $this->resultado;
    }

    final public function DeleteIt($conn, $table_name, $column_name, $value)
    {
        $query_delete;
        $query_select;
        $result_select;
        $sentence_exec;
        $resultado;
        $this->conn        = $conn;
        $this->table_name  = $table_name;
        $this->column_name = $column_name;
        $this->value       = $value;

        $this->query_select  = "SELECT FROM ".$this->table_name." WHERE ".$this->column_name."= '".$this->value."';";
        $this->result_select = pg_fetch_all(pg_query($this->conn, $this->query_select));
        if (!$this->result_select) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = "El registro no existe!";
        } else {
            try {
                $this->query_delete  = "DELETE FROM ".$this->table_name." WHERE ".$this->column_name."= '".$this->value."';";
                $this->sentence_exec = pg_query($this->conn, $this->query_delete);
                if (!$this->sentence_exec) {
                    throw new Exception("Error al eliminar registro ".$this->value);
                }
                $this->resultado['conexion'] = true;
                $this->resultado['mensaje']  = "Registro eliminado con éxito!";
            } catch (Exception $e) {
                $this->resultado['conexion'] = false;
                $this->resultado['mensaje']  = $e->getMessage();
            }
        }
        $this->resultado['contenido'] = 0;
        return $this->resultado;
    }

};

