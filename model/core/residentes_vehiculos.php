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

require_model('cliente.php');
/**
 * Model de la tabla donde se almacenan los distintos vehiculos y la información de los mismos
 * de cada residente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_vehiculos extends \fs_model{
    /**
     * El Id del vehiculo
     * @var integer serial
     */
    public $idvehiculo;
    /**
     * El codigo del Residente o cliente al que pertenece el vehiculo
     * @var string
     */
    public $codcliente;
    /**
     * La marca del Vehiculo
     * @var string
     */
    public $vehiculo_marca;
    /**
     * El modelo del Vehiculo
     * @var string
     */
    public $vehiculo_modelo;
    /**
     * El color del Vehiculo
     * @var string
     */
    public $vehiculo_color;
    /**
     * La placa del Vehiculo
     * @var string
     */
    public $vehiculo_placa;
    /**
     * El tipo de Vehiculo
     * @var string
     */
    public $vehiculo_tipo;
    /**
     * Si posee una tarjeta de acceso asignada al vehiculo
     * este código se guarda aquí
     * @var string
     */
    public $codigo_tarjeta;
    public $cliente;
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
        $this->cliente = new cliente();
    }

    public function install(){
        return "";
    }

    public function info_adicional($i){
        $data = $this->cliente->get($i->codcliente);
        $i->nombre = $data->nombre;
        $i->telefono1 = $data->telefono1;
        $i->telefono2 = $data->telefono2;
        $i->email = $data->email;
        $i->observaciones = $data->observaciones;
        return $i;
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
        $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente)." AND idvehiculo = ".$this->intval($id).";";
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
                    "vehiculo_modelo = ".$this->var2str($this->vehiculo_modelo).", ".
                    "vehiculo_color = ".$this->var2str($this->vehiculo_color).", ".
                    "vehiculo_placa = ".$this->var2str($this->vehiculo_placa).", ".
                    "vehiculo_tipo = ".$this->var2str($this->vehiculo_tipo).", ".
                    "codigo_tarjeta = ".$this->var2str($this->codigo_tarjeta)." ".
                    "WHERE idvehiculo = ".$this->intval($this->idvehiculo)." AND ".
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

    /**
     * Función para realizar buquedas en la mayor cantidad de información de vehiculos del residente
     * @param type $query string/integer
     */
    public function search($busqueda, $offset = 0) {
        $clilist = array();
        $query = mb_strtolower($this->no_html($busqueda), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "(codigo_tarjeta LIKE '%" . $query . "%' OR CAST(idvehiculo as CHAR) = '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "(lower(codigo_tarjeta) LIKE '%" . $buscar . "%' OR lower(vehiculo_color) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_marca) LIKE '%" . $buscar . "%' OR lower(vehiculo_modelo) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_placa) LIKE '%" . $buscar . "%' OR lower(vehiculo_tipo) LIKE '%" . $buscar . "%')";
        }
        $consulta .= " ORDER BY codcliente ASC";

        $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $item = new residentes_vehiculos($d);
                $clilist[] = $item->info_adicional($item);
            }
        }

        return $clilist;
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE idvehiculo = ".$this->intval($this->idvehiculo)." and codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }

}
