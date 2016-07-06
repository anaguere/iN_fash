<?php
class Connector
{
    private $connector;
    private $conn;
    private $resultado;

    public function _construct()
    {
    }

    #------------------------------- CONECTAR A BASE DE DATOS -------------------------------------------

    final public function ConexionBD()
    {
        $connector = json_decode(file_get_contents("../data/connectorData.json"), true);
        $conn      = pg_connect("host=".$connector['host']." port=".$connector['port']." dbname=".$connector['dbname']." user=".$connector['user']." password=".$connector['password']);
        try {
            if (!$conn) {
                throw new Exception("Ha ocurrido un problema al conectarse a la base de datos!");
            }
            $resultado['conexion'] = true;
            $resultado['mensaje']  = "Conexión satisfactoria";
            $resultado['conexion'] = $conn;
            return ($resultado);
        } catch (Exception $e) {
            $resultado['conexion'] = false;
            $resultado['mensaje']  = $e->getMessage();

            return ($resultado);
        }
    }

    #------------------------------- VALIDAR CLAVES FORANEAS -------------------------------------------

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

    #------------------------------- SELECCIONAR REGISTRO -------------------------------------------

    final public function SelectIn($conn, $tableName, $array_insertion)
    {
        $query;
        $sentence_exec;
        $main_table_result;
        $this->tableName       = $tableName;
        $this->conn            = $conn;
        $this->array_insertion = $array_insertion;
        $foreign_keys;
        try {
            if (!$this->array_insertion) {
                $this->query = "SELECT * FROM ".$this->tableName.";";
            } else {
                $this->query = "SELECT * FROM ".$this->tableName." WHERE ".$this->array_insertion[0]."='".$this->array_insertion[1]."';";
            }

            $this->main_table_result = pg_fetch_all(pg_query($this->conn, $this->query));
            if (!$this->main_table_result) {
                throw new Exception("Error al acceder a la tabla".$this->tableName);
            }

            $this->foreign_keys = $this->ForeignKeys($this->tableName, $this->conn);
            if ($this->foreign_keys['conexion']) {
                $this->foreign_keys = $this->foreign_keys['contenido'];
                for ($i = 0; $i < count($this->foreign_keys); $i++) {
                    for ($j = 0; $j < count($this->main_table_result); $j++) {
                        $dependency_inyection = pg_query($this->conn, "SELECT * FROM ".$this->foreign_keys[$i]['foreign_table_name']." WHERE ".$this->foreign_keys[$i]['foreign_table_column_name']."=".$this->main_table_result[$j][$this->foreign_keys[$i]['main_table_column_name']]);

                        $dependency_inyection_result = pg_fetch_all($dependency_inyection);

                        $keys_array = array_keys($dependency_inyection_result[0]);

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
    #------------------------------- CREAR NUEVO REGISTRO -------------------------------------------
    final public function InsertIn($conn, $tableName, $column_name)
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

    #------------------------------- ELIMINAR REGISTRO -------------------------------------------

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

        $this->query_select  = "SELECT * FROM ".$this->table_name." WHERE ".$this->column_name."= '".$this->value."';";
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

    #------------------------------- ACTUALIZAR REGISTRO -------------------------------------------

    final public function UpdateIn($conn, $table_name, $column_name, $id_name)
    {
        $cant_columns;
        $query_update;
        $sentence_exec;
        $resultado;
        $values;
        $this->conn        = $conn;
        $this->table_name  = $table_name;
        $this->column_name = $column_name;
        $this->id_name     = $id_name;

        $cant_columns = count($this->column_name);
        $k            = 0;
        foreach ($this->column_name as $clave => $valor) {
            $k++;
            if ($k < $cant_columns) {
                $this->values = $this->values.$clave."='".$valor."',";
            } else {
                $this->values = $this->values.$clave."='".$valor."'";
            }
        }
        try {
            $this->query_update  = "UPDATE ".$this->table_name."  SET ".$this->values." WHERE ".$id_name[0]."=".$id_name[1];
            $this->sentence_exec = pg_query($this->conn, $this->query_update);
            if (!$this->sentence_exec) {
                throw new Exception("Ha ocurrido un error al tratar de actualizar el registro!");
            }
            $this->resultado['conexion'] = true;
            $this->resultado['mensaje']  = "Actualizado con éxito!";
        } catch (Exception $e) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = $e->getMessage();
        }
        $this->resultado['contenido'] = 0;
        return $this->resultado;
    }

    #------------------------------- CERRAR CONEXION -------------------------------------------

    final public function CloseIn($conn)
    {
        $this->conn = $conn;
        $query_close;
        $resultado;
        try {
            $this->query_close = pg_close($this->conn);
            if (!$this->query_close) {
                throw new Exception("Ha ocurrido un error al procesar el cierre de la conexión!");
            }
            $this->resultado['conexion'] = true;
            $this->resultado['mensaje']  = "Ha finalizado la conexión SQL";
        } catch (Exception $e) {
            $this->resultado['conexion'] = false;
            $this->resultado['mensaje']  = $e->getMessage();
        }
        $this->resultado['contenido'] = 0;
        return $this->resultado;
    }

    final public function DataBaseIn($conn, $schema_name)
    {
        $conn        = $conn;
        $schema_name = $schema_name;
        $resultado;
        $query = "SELECT DISTINCT ON  (ic.column_name) ic.column_name, it.table_name,  itc.constraint_type AS keys, ic.is_nullable AS is_null, ic.data_type AS type, ic.character_maximum_length AS length
			FROM information_schema.tables AS it
			INNER JOIN information_schema.columns AS ic ON ic.table_name = it.table_name
			INNER JOIN information_schema.table_constraints as itc ON itc.table_name =it.table_name
			WHERE it.table_schema = '".$schema_name."' GROUP BY ic.column_name, itc.constraint_type, ic.is_nullable, ic.data_type, ic.character_maximum_length,it.table_name;
";
        try {
            $sentence_exec = pg_query($conn, $query);
            if (!$sentence_exec) {
                throw new Exception("Ha ocurrido un error al realiar consulta SQL!");
            }
            $resultado['conexion']  = true;
            $resultado['mensaje']   = "Conexión realizada con éxito!";
            $resultado['contenido'] = pg_fetch_all($sentence_exec);
        } catch (Exception $e) {
            $resultado['conexion'] = false;
            $resultado['mensaje']  = $e->getMessage();
        }
        return $resultado;
    }
};

