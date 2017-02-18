<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\model;
/**
 * Description of residentes_edificaciones_tipo
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_edificaciones_tipo extends \fs_model{
    /**
     * El Id del tipo de edificacion
     * @var type integer serial
     */
    public $id;
    /**
     * Esta es la descripción del tipo de edificación
     * @var type varchar(64)
     */
    public $descripcion;
    /**
     * Todo tipo debe tener un padre, el primero que se crea tiene el padre 0
     * @var type integer
     */
    public $padre;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_edificaciones_tipo','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->descripcion = $t['descripcion'];
            $this->padre = $t['padre'];
        }else{
            $this->id = null;
            $this->descripcion = null;
            $this->padre = null;
        }
    }

    public function install(){
        return "insert into residentes_edificaciones_tipo (descripcion) VALUES ('Bloque');";
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY padre";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $lista[] = new residentes_edificaciones_tipo($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get($id){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id).";";
        $data = $this->db->select($sql);
        if($data){
            return new residentes_edificaciones_tipo($data[0]);
        }else{
            return false;
        }
    }

    /**
     * //Si queremos buscar por descripcion o padre
     * @param type $field string
     * @param type $value string or integer
     * @return boolean|\FacturaScripts\model\residentes_edificaciones_tipo
     */
    public function get_by_field($field,$value){
        $query = (is_string($value))?$this->var2str($value):$this->intval($value);
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query.";";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $lista[] =  new residentes_edificaciones_tipo($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->id)){
            return false;
        }else{
            return $this->get($this->id);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    "descripcion = ".$this->var2str($this->descripcion).", ".
                    "padre = ".$this->intval($this->padre)." ".
                    "WHERE id = ".$this->intval($this->id).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (descripcion, padre) VALUES (".
                    $this->var2str($this->descripcion).", ".
                    $this->intval($this->padre).");";
            if($this->db->exec($sql)){
                return $this->db->lastval();
            }else{
                return false;
            }
        }
    }
    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->id).";";
        return $this->db->exec($sql);
    }

    public function jerarquia(){
        $lista = $this->all();
        return ($lista)?$this->estructura($lista):null;
    }

    public function estructura($lista, $raiz = 0){
        $estructura = array();
        foreach($lista as $key=>$item){
            if($item->padre == $raiz){
                unset($lista[$key]);
                $estructura[]=array(
                    'id'=>$item->id,
                    'text'=>$item->descripcion,
                    'padre'=>$item->padre,
                    'nodes'=>$this->estructura($lista,$item->id)
                );
            }
        }
        return empty($estructura)?null:$estructura;
    }
}
