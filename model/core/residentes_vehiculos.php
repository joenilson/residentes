<?php
/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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
 * Model de la tabla donde se almacenan los distintos vehiculos y la información de los mismos
 * de cada residente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_vehiculos extends \fs_model{
    /**
     * El Id del vehiculo
     * @var type integer serial
     */
    public $idvehiculo;
    /**
     * El codigo del Residente o cliente al que pertenece el vehiculo
     * @var type varchar(6)
     */
    public $codcliente;
    /**
     * La marca del Vehiculo
     * @var type varchar(32)
     */
    public $vehiculo_marca;
    /**
     * El modelo del Vehiculo
     * @var type varchar(32)
     */
    public $vehiculo_modelo;
    /**
     * El color del Vehiculo
     * @var type varchar(32)
     */
    public $vehiculo_color;
    /**
     * La placa del Vehiculo
     * @var type varchar(32)
     */
    public $vehiculo_placa;
    /**
     * El tipo de Vehiculo
     * @var type varchar(32)
     */
    public $vehiculo_tipo;
    /**
     * Si posee una tarjeta de acceso asignada al vehiculo
     * este código se guarda aquí
     * @var type varchar(32)
     */
    public $codigo_tarjeta;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_vehiculos','plugins/residentes');
        if($t){
            $this->idvehiculo = $t['idvehiculo'];
            $this->codcliente = $t['codcliente'];
            $this->vehiculo_marca = $t['vehiculo_marca'];
            $this->vehiculo_modelo = $t['vehiculo_modelo'];
            $this->vehiculo_color = $t['vehiculo_color'];
            $this->vehiculo_placa = $t['vehiculo_placa'];
            $this->vehiculo_tipo = $t['vehiculo_tipo'];
            $this->codigo_tarjeta = $t['codigo_tarjeta'];
        }else{
            $this->idvehiculo = null;
            $this->codcliente = null;
            $this->vehiculo_marca = NULL;
            $this->vehiculo_modelo = NULL;
            $this->vehiculo_color = NULL;
            $this->vehiculo_placa = NULL;
            $this->vehiculo_tipo = NULL;
            $this->codigo_tarjeta = null;
        }

    }

    public function install(){
        return "";
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY codcliente,idvehiculo";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_vehiculos($d);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get($codcliente,$id){
        $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente)." AND id = ".$this->intval($id).";";
        $data = $this->db->select($sql);
        if($data){
            return new residentes_vehiculos($data[0]);
        }else{
            return false;
        }
    }

    /**
     * //Si queremos buscar por marca o modelo o codcliente o idvehiculo o tipo o placa
     * @param type $field string
     * @param type $value string
     * @return boolean|\FacturaScripts\model\residentes_vehiculos
     */
    public function get_by_field($field,$value){
        $query = (is_string($value))?$this->var2str($value):$this->intval($value);
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query.";";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_vehiculos($d);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->idvehiculo)){
            return false;
        }else{
            return $this->get($this->codcliente,$this->idvehiculo);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    "vehiculo_marca = ".$this->var2str($this->vehiculo_marca).", ".
                    "vehiculo_modelo = ".$this->var2str($this->vehiculo_modelo)." ".
                    "vehiculo_color = ".$this->var2str($this->vehiculo_color)." ".
                    "vehiculo_placa = ".$this->var2str($this->vehiculo_placa)." ".
                    "vehiculo_tipo = ".$this->var2str($this->vehiculo_tipo)." ".
                    "codigo_tarjeta = ".$this->var2str($this->codigo_tarjeta)." ".
                    "WHERE id = ".$this->intval($this->idvehiculo)." AND ".
                    "codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (codcliente, vehiculo_marca, vehiculo_modelo, vehiculo_color, vehiculo_placa, vehiculo_tipo, codigo_tarjeta) VALUES (".
                    $this->var2str($this->codcliente).", ".
                    $this->var2str($this->vehiculo_marca).", ".
                    $this->var2str($this->vehiculo_modelo).", ".
                    $this->var2str($this->vehiculo_color).", ".
                    $this->var2str($this->vehiculo_placa).", ".
                    $this->var2str($this->vehiculo_tipo).", ".
                    $this->var2str($this->codigo_tarjeta).");";
            if($this->db->exec($sql)){
                return $this->db->lastval();
            }else{
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->idvehiculo)." and codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }

}
