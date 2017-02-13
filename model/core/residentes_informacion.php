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
 * Tabla para guardar la información adicional de los residentes
 * para guardar la información de los vehiculos de los mismos
 * se debe usar el model residentes_vehiculos
 * @author Joe Nilson <joenilson at gmail.com>
 * @version 1
 */
class residentes_informacion extends \fs_model{
    /**
     * Codigo del cliente
     * @var type varchar(6)
     */
    public $codcliente;
    /**
     * Codigo auxiliar del cliente
     * @var type varchar(32)
     */
    public $codigo;
    /**
     * Cantidad de ocupantes del inmueble
     * @var type integer
     */
    public $ocupantes;
    /**
     * Cantidad de ocupantes menores de 12 años
     * @var type integer
     */
    public $ocupantes12anos;
    /**
     * Cantidad de ocupantes menores de 18 años
     * @var type integer
     */
    public $ocupantes18anos;
    /**
     * Cantidad de ocupantes menores de 30 años
     * @var type integer
     */
    public $ocupantes30anos;
    /**
     * Cantidad de ocupantes menores de 50 años
     * @var type integer
     */
    public $ocupantes50anos;
    /**
     * Cantidad de ocupantes menores de 70 años
     * @var type integer
     */
    public $ocupantes70anos;
    /**
     * Cantidad de ocupantes mayores de 70 años
     * @var type integer
     */
    public $ocupantes71anos;
    /**
     * Información sobre alguna discapacidad
     * @var type text
     */
    public $informacion_discapacidad;
    /**
     * Si es propietario valor TRUE si es solo inquilino FALSE
     * @var type boolean
     */
    public $propietario;
    /**
     * Información de la profesión del residente
     * @var type varhcar(180)
     */
    public $profesion;
    /**
     * Información de la ocupación del residente
     * sale del model residentes_ayudas tipo = 'ocupacion'
     * @var type varchar(10)
     */
    public $ocupacion;
    /**
     * Nombres del Contacto Adicional
     * @var type varchar(180)
     */
    public $ca_nombres;
    /**
     * Apellidos del contacto Adicional
     * @var type varchar(180)
     */
    public $ca_apellidos;
    /**
     * Información del teléfono del contacto Adicional
     * @var type varchar(32)
     */
    public $ca_telefono;
    /**
     * Correo electrónico del contacto Adicional
     * @var type varchar(100)
     */
    public $ca_email;
    /**
     * si el contacto adicional es el propietario se pone TRUE, si no se pone FALSE
     * @var type boolean
     */
    public $ca_propietario;
    /**
     * Información de el parentezco del contacto Adicional
     * sale del model residentes_ayudas tipo = 'parentezco'
     * @var type varchar(10)
     */
    public $ca_parentesco;
    /**
     * Si el tipo de parentezco es OTROS entonces se llena la información adicional
     * @var type varchar(180)
     */
    public $ca_parentesco_obs;
    /**
     * Cantidad de Vehiculos a agregar
     * @var type integer
     */
    public $vehiculos;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_informacion','plugins/residentes');
        if($t){
            $this->codcliente=$t['codcliente'];
            $this->codigo=$t['codigo'];
            $this->ocupantes=$t['ocupantes'];
            $this->ocupantes12anos=$t['ocupantes12anos'];
            $this->ocupantes18anos=$t['ocupantes18anos'];
            $this->ocupantes30anos=$t['ocupantes30anos'];
            $this->ocupantes50anos=$t['ocupantes50anos'];
            $this->ocupantes70anos=$t['ocupantes70anos'];
            $this->ocupantes71anos=$t['ocupantes71anos'];
            $this->informacion_discapacidad=$t['informacion_discapacidad'];
            $this->propietario=$this->str2bool($t['propietario']);
            $this->profesion=$t['profesion'];
            $this->ocupacion=$t['ocupacion'];
            $this->ca_nombres=$t['ca_nombres'];
            $this->ca_apellidos=$t['ca_apellidos'];
            $this->ca_telefono=$t['ca_telefono'];
            $this->ca_email=$t['ca_email'];
            $this->ca_propietario=$this->str2bool($t['ca_propietario']);
            $this->ca_parentesco=$t['ca_parentesco'];
            $this->ca_parentesco_obs=$t['ca_parentesco_obs'];
            $this->vehiculos=$t['vehiculos'];
        }else{
            $this->codcliente=NULL;
            $this->codigo=NULL;
            $this->ocupantes=NULL;
            $this->ocupantes12anos=NULL;
            $this->ocupantes18anos=NULL;
            $this->ocupantes30anos=NULL;
            $this->ocupantes50anos=NULL;
            $this->ocupantes70anos=NULL;
            $this->ocupantes71anos=NULL;
            $this->informacion_discapacidad=NULL;
            $this->propietario='FALSE';
            $this->profesion=NULL;
            $this->ocupacion=NULL;
            $this->ca_nombres=NULL;
            $this->ca_apellidos=NULL;
            $this->ca_telefono=NULL;
            $this->ca_email=NULL;
            $this->ca_propietario='FALSE';
            $this->ca_parentesco=NULL;
            $this->ca_parentesco_obs='';
            $this->vehiculos=0;
        }
    }

    public function install(){
        return "";
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY codcliente";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_informacion($d);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    /**
     * //Retornamos un punto del mapa en particular
     * @param type $codcliente integer
     * @param type $codigo_edificacion varchar(6)
     * @param type $padre_tipo integer
     * @return \FacturaScripts\model\residentes_informacion|boolean
     */
    public function get($codcliente){
        $sql = "SELECT * FROM ".$this->table_name.
                " WHERE codcliente = ".$this->var2str($codcliente).";";
        $data = $this->db->select($sql);
        if($data){
            return new residentes_informacion($data[0]);
        }else{
            return false;
        }
    }

    /**
     * //Si queremos buscar por codcliente o codigo_interno o numero
     * @param type $field string
     * @param type $value string
     * @return boolean|\FacturaScripts\model\residentes_informacion
     */
    public function get_by_field($field,$value){
        $query = (is_string($value))?$this->var2str($value):$this->intval($value);
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query.";";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_informacion($d);
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->codcliente)){
            return false;
        }else{
            return $this->get($this->codcliente);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
            "codigo = ".$this->var2str($this->codigo).", ".
            "ocupantes = ".$this->intval($this->ocupantes).", ".
            "ocupantes12anos = ".$this->intval($this->ocupantes12anos).", ".
            "ocupantes18anos = ".$this->intval($this->ocupantes18anos).", ".
            "ocupantes30anos = ".$this->intval($this->ocupantes30anos).", ".
            "ocupantes50anos = ".$this->intval($this->ocupantes50anos).", ".
            "ocupantes70anos = ".$this->intval($this->ocupantes70anos).", ".
            "ocupantes71anos = ".$this->intval($this->ocupantes71anos).", ".
            "informacion_discapacidad = ".$this->var2str($this->informacion_discapacidad).", ".
            "propietario = ".$this->var2str($this->propietario).", ".
            "profesion = ".$this->var2str($this->profesion).", ".
            "ocupacion = ".$this->var2str($this->ocupacion).", ".
            "ca_nombres = ".$this->var2str($this->ca_nombres).", ".
            "ca_apellidos = ".$this->var2str($this->ca_apellidos).", ".
            "ca_telefono = ".$this->var2str($this->ca_telefono).", ".
            "ca_email = ".$this->var2str($this->ca_email).", ".
            "ca_propietario = ".$this->var2str($this->ca_propietario).", ".
            "ca_parentesco = ".$this->var2str($this->ca_parentesco).", ".
            "ca_parentesco_obs = ".$this->var2str($this->ca_parentesco_obs).", ".
            "vehiculos = ".$this->intval($this->vehiculos)." ".
            "WHERE codcliente = ".$this->var2str($this->codcliente).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (codcliente, codigo, ocupantes, ocupantes12anos, ocupantes18anos, ocupantes30anos, ocupantes50anos, ocupantes70anos, ocupantes71anos, informacion_discapacidad, propietario, profesion, ocupacion, ca_nombres, ca_apellidos, ca_telefono, ca_email, ca_propietario, ca_parentesco, ca_parentesco_obs, vehiculos) VALUES (".
            $this->var2str($this->codcliente).", ".
            $this->var2str($this->codigo).", ".
            $this->intval($this->ocupantes).", ".
            $this->intval($this->ocupantes12anos).", ".
            $this->intval($this->ocupantes18anos).", ".
            $this->intval($this->ocupantes30anos).", ".
            $this->intval($this->ocupantes50anos).", ".
            $this->intval($this->ocupantes70anos).", ".
            $this->intval($this->ocupantes71anos).", ".
            $this->var2str($this->informacion_discapacidad).", ".
            $this->var2str($this->propietario).", ".
            $this->var2str($this->profesion).", ".
            $this->var2str($this->ocupacion).", ".
            $this->var2str($this->ca_nombres).", ".
            $this->var2str($this->ca_apellidos).", ".
            $this->var2str($this->ca_telefono).", ".
            $this->var2str($this->ca_email).", ".
            $this->var2str($this->ca_propietario).", ".
            $this->var2str($this->ca_parentesco).", ".
            $this->var2str($this->ca_parentesco_obs).", ".
            $this->intval($this->vehiculos).", ".
            $this->var2str($this->codcliente).");";
            if($this->db->exec($sql)){
                return true;
            }else{
                return false;
            }
        }
    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name.
                " WHERE codcliente = ".$this->var2str($this->codcliente).";";
        return $this->db->exec($sql);
    }

}