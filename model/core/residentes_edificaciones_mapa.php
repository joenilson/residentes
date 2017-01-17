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
use FacturaScripts\model\residentes_edificaciones_tipo;
/**
 * Description of residentes_edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_edificaciones_mapa extends \fs_model{
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

    public $edificaciones_tipo;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_mapa_edificaciones','plugins/residentes');
        if($t){
            $this->id_tipo = $t['id_tipo'];
            $this->codigo_edificacion = $t['codigo_edificacion'];
            $this->numero = $t['numero'];
            $this->padre_tipo = $t['padre_tipo'];

        }else{
            $this->id_tipo = null;
            $this->codigo_edificacion = null;
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
    public function get($id_tipo,$codigo_edificacion,$padre_tipo){
        $sql = "SELECT * FROM ".$this->table_name.
                " WHERE id_tipo = ".$this->intval($id_tipo)." AND ".
                " codigo_edificacion = ".$this->var2str($codigo_edificacion)." AND ".
                " padre_tipo = ".$this->intval($padre_tipo).";";
        $data = $this->db->select($sql);
        if($data){
            return new residentes_edificaciones_mapa($data[0]);
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
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query.";";
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
                    "codigo_edificacion = ".$this->var2str($this->codigo_edificacion).", ".
                    "numero = ".$this->var2str($this->numero)." ".
                    "padre_tipo = ".$this->intval($this->padre_tipo)." ".
                    "WHERE id_tipo = ".$this->intval($this->id_tipo)." AND";
                    " codigo_edificacion = ".$this->var2str($this->codigo_edificacion)." AND";
                    " padre_tipo = ".$this->intval($this->padre_tipo).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id_tipo, codigo_edificacion, numero, padre_tipo) VALUES (".
                    $this->intval($this->id_tipo).", ".
                    $this->var2str($this->codigo_edificacion).", ".
                    $this->var2str($this->numero).", ".
                    $this->intval($this->padre_tipo).";";
            if($this->db->exec($sql)){
                return true;
            }else{
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name.
                " WHERE id_tipo = ".$this->intval($this->id).";";
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

}
