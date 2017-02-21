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
 * Description of residentes_edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_edificaciones_mapa extends \fs_model{
    /**
     * ID correlativo de cada linea
     * @var type serial
     */
    public $id;
    /**
     * El Id del tipo de edificacion
     * @var type integer
     */
    public $id_tipo;
    /**
     * Código de la edificación A, 1, C1, como se tenga organizado ese tipo de edificación
     * @var type varchar(6)
     */
    public $codigo_edificacion;
    /**
     * Código de la edificación padre A, 1, C1
     * @var type varchar(6)
     */
    public $codigo_padre;
    /**
     * El número de la edificación, puede ser el número de la casa, del apartamento
     * o de la eficicación a controlar
     * @var type varchar(16)
     */
    public $numero;
    /**
     * @todo padre del ID del tipo de edificación
     * @var type integer
     */
    public $padre_tipo;
    /**
     * el ID de la edificacion padre
     * @var type integer
     */
    public $padre_id;
    public $edificaciones_tipo;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_mapa_edificaciones','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->id_tipo = $t['id_tipo'];
            $this->codigo_edificacion = $t['codigo_edificacion'];
            $this->codigo_padre = $t['codigo_padre'];
            $this->numero = $t['numero'];
            $this->padre_tipo = $t['padre_tipo'];
            $this->padre_id = $t['padre_id'];

        }else{
            $this->id = null;
            $this->id_tipo = null;
            $this->codigo_edificacion = null;
            $this->codigo_padre = null;
            $this->numero = null;
            $this->padre_tipo = null;
        }
        $this->edificaciones_tipo = new \residentes_edificaciones_tipo();
    }

    public function install(){
        return "";
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY id_tipo,codigo_edificacion";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_edificaciones_mapa($d);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     * //Retornamos un punto del mapa en particular
     * @param type $id_tipo integer
     * @param type $codigo_edificacion varchar(6)
     * @param type $padre_tipo integer
     * @return \FacturaScripts\model\residentes_edificaciones_mapa|boolean
     */
    public function get($id){
        $sql = "SELECT * FROM ".$this->table_name.
                " WHERE id = ".$this->intval($id).";";
        $data = $this->db->select($sql);
        if($data){
            $item = new residentes_edificaciones_mapa($data[0]);
            $item->desc_id = $this->edificaciones_tipo->get($item->id_tipo)->descripcion;
            return $item;
        }else{
            return false;
        }
    }

    public function getEstructura(){
        $sql = "SELECT * FROM ".$this->table_name." WHERE codigo_edificacion = ".$this->var2str($this->codigo_edificacion)." AND ".
                " codigo_padre = ".$this->var2str($this->codigo_padre)." AND id_tipo = ".$this->intval($this->id_tipo)." AND ".
                " padre_tipo = ".$this->intval($this->padre_tipo)." AND padre_id = ".$this->intval($this->padre_id).";";
        $data = $this->db->select($sql);
        if($data){
            $item = new residentes_edificaciones_mapa($data[0]);
            $item->desc_id = $this->edificaciones_tipo->get($item->id_tipo)->descripcion;
            return $item;
        }else{
            return false;
        }
    }

    /**
     * //Si queremos buscar por id_tipo o codigo_interno o numero
     * @param type $field string
     * @param type $value string
     * @return boolean|\FacturaScripts\model\residentes_edificaciones_mapa
     */
    public function get_by_field($field,$value){
        $query = (is_string($value))?$this->var2str($value):$this->intval($value);
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query." order by codigo_edificacion ASC;";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_edificaciones_mapa($d);
                $item->desc_id = $this->edificaciones_tipo->get($item->id_tipo)->descripcion;
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function exists() {
        if(!$this->getEstructura()){
            return false;
        }else{
            return $this->getEstructura();
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    " codigo_edificacion = ".$this->var2str($this->codigo_edificacion).", ".
                    " codigo_padre = ".$this->var2str($this->codigo_padre).", ".
                    " id_tipo = ".$this->intval($this->id_tipo).", ".
                    " numero = ".$this->var2str($this->numero).", ".
                    " padre_tipo = ".$this->intval($this->padre_tipo).", ".
                    " padre_id = ".$this->intval($this->padre_id)." ".
                    "WHERE id = ".$this->intval($this->id).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id_tipo, codigo_edificacion, codigo_padre, numero, padre_tipo, padre_id) VALUES (".
                    $this->intval($this->id_tipo).", ".
                    $this->var2str($this->codigo_edificacion).", ".
                    $this->var2str($this->codigo_padre).", ".
                    $this->var2str($this->numero).", ".
                    $this->intval($this->padre_tipo).", ".
                    $this->intval($this->padre_id).");";
            if($this->db->exec($sql)){
                return true;
            }else{
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name.
                " WHERE id = ".$this->intval($this->id).";";
        return $this->db->exec($sql);
    }

    private function pertenencia($d){
        $codigo_interno = $d->codigo_interno;
        $piezas = \json_decode($codigo_interno);
        $lista = array();
        foreach($piezas as $id=>$data){
            $pertenencia = new \stdClass();
            $pertenencia->id = $id;
            $pertenencia->desc_id = $this->edificaciones_tipo->get($id)->descripcion;
            $pertenencia->padre = $this->edificaciones_tipo->get($id)->padre;
            $pertenencia->valor = $data;
            $lista[] = $pertenencia;
        }
        return $lista;
    }

    public function buscar_ubicacion_inmueble($id,$linea){
        $inicio_busqueda = ($linea==0)?"{\"":"{%\"";
        $sql = "SELECT * FROM ".$this->table_name." WHERE codigo_interno LIKE '".$inicio_busqueda.$id."\":%}' ORDER BY codigo;";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $l = new residentes_edificaciones_mapa($d);
                $lista[] = $this->pertenencia($l);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function buscar_cantidad_inmuebles($id,$linea){
        $inicio_busqueda = ($linea==0)?"{\"":"{%\"";
        $sql = "SELECT DISTINCT codigo FROM ".$this->table_name." WHERE codigo_interno LIKE '".$inicio_busqueda.$id."\":%}' ORDER BY codigo;";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $l = $this->get_by_field('codigo', $d['codigo']);
                $lista[] = $l;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function generar_mapa(){
        $mapa = array();
        $linea = 0;
        foreach($this->edificaciones_tipo->all() as $data){
            if($linea==1){
                break;
            }
            $items = $this->buscar_cantidad_inmuebles($data->id,$data->padre);
            foreach($items as $inmueble){
                $mapa[$data->id][] = $inmueble;
            }
            $linea++;
        }
        return $mapa;
    }

    public function tiene_hijos(){
        $sql = "SELECT count(*) as cantidad FROM ".$this->table_name." WHERE padre_id = ".$this->intval($this->id).";";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['cantidad'];
        }else{
            return false;
        }
    }

}
