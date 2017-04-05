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
require_model('core/residentes_edificaciones_tipo.php');
require_model('core/residentes_informacion.php');
require_model('core/residentes_vehiculos.php');
require_model('cliente.php');
/**
 * Description of residentes_edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_edificaciones extends \fs_model{
    /**
     * El Id de la de edificacion
     * @var type integer serial
     */
    public $id;
    /**
     * Este es el id de la edificacion en el mapa de Edificaciones
     * @var type integer
     */
    public $id_edificacion;
    /**
     * De cara al usuario se mostrará y buscara este código
     * pero esto se generará de los valores que ingrese de
     * los tipos de edificaciones
     * @var type string
     */
    public $codigo;
    /**
     * Este codigo es en realidad una cadena para saber donde buscar
     * los datos de las edificaciones, si solo manejan el tipo de edificación
     * principal que es Bloque con código 1, entonces el codigo interno
     * será 1:LETRA_O_NUMERO del Bloque, si por el contrario tiene Bloque y Edificio
     * entonces el valor guardado será 1:1_2:1 que indicara que esta en el Bloque 1 Edificio 1
     * siendo el id de Edificio el 2.
     * @var type string
     */
    public $codigo_interno;
    /**
     * Este es el número del Inmueble
     * @var type string
     */
    public $numero;
    /**
     * @todo En un futuro si se necesita colocar la ubicación del edificio, como la calle o avenida interna
     * @var type string
     */
    public $ubicacion;
    /**
     * Si se van a agregar las coordenadas del inmueble pueden colocarse aquí
     * @var type varchar(64)
     */
    public $coordenadas;
    /**
     * Si la edificación esta ocupada entonces se coloca aquí el código del Residente o Cliente
     * @var type string
     */
    public $codcliente;
    /**
     * Con esto sabremos si un edificio está o no ocupado en el listado de edificios
     * @var type boolean
     */
    public $ocupado;
    /**
     *
     * @var type date
     */
    public $fecha_ocupacion;
    /**
     *
     * @var type
     */
    public $fecha_disponibilidad;
    //Datos auxiliares
    public $edificaciones_tipo;
    public $cliente;
    public $cliente_info;
    public $cliente_vehiculo;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_edificaciones','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->id_edificacion = $t['id_edificacion'];
            $this->codigo = $t['codigo'];
            $this->codigo_interno = $t['codigo_interno'];
            $this->numero = $t['numero'];
            $this->ubicacion = $t['ubicacion'];
            $this->coordenadas = $t['coordenadas'];
            $this->codcliente = $t['codcliente'];
            $this->ocupado = $this->str2bool($t['ocupado']);
            $this->fecha_ocupacion = $t['fecha_ocupacion'];
            $this->fecha_disponibilidad = $t['fecha_disponibilidad'];
        }else{
            $this->id = null;
            $this->id_edificacion = null;
            $this->codigo = null;
            $this->codigo_interno = null;
            $this->numero = null;
            $this->ubicacion = null;
            $this->coordenadas = null;
            $this->codcliente = null;
            $this->ocupado = false;
            $this->fecha_ocupacion = NULL;
            $this->fecha_disponibilidad = NULL;
        }
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->cliente = new cliente();
        $this->cliente_info = new residentes_informacion();
        $this->cliente_vehiculo = new residentes_vehiculos();
    }

    public function install(){
        $familias_sql = "INSERT INTO familias (descripcion,codfamilia) VALUES ('Servicios Residentes','RESIDENT');";
        $articulos_sql = "INSERT INTO articulos (referencia, descripcion, codfamilia, nostock, controlstock, secompra, sevende, bloqueado) VALUES ".
                "('RES_CUOTA','Cuota Mensual','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE),".
                "('RES_SEGURIDAD','Pago de Seguridad común','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE),".
                "('RES_ILUMINACION','Pago Iluminación de Parques','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE),".
                "('RES_AGUA','Pago de Agua','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE),".
                "('RES_GAS','Pago de Gas Común','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE),".
                "('RES_SERVICIOS','Pago de Servicios Municipales','RESIDENT',TRUE,TRUE,FALSE, TRUE, FALSE);";
        return "$familias_sql $articulos_sql";
    }

    public function url(){
        if(!is_null($this->id)){
            return FS_PATH.'index.php?page=ver_residente&id='.$this->id;
        }else{
            return FS_PATH.'index.php?page=residentes';
        }
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY codigo_interno,numero";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_edificaciones($d);
                $item->pertenencia = $this->pertenencia($item);
                if($item->codcliente){
                    $item->nombre = $this->cliente->get($item->codcliente)->nombre;
                    $item->info = $this->cliente_info->get($item->codcliente);
                    $item->vehiculos = $this->cliente_vehiculo->get_by_field('codcliente', $item->codcliente);
                }
                $lista[] = $item;
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function all_ocupados(){
        $sql = "SELECT * FROM ".$this->table_name." WHERE ocupado = TRUE ORDER BY codigo_interno,numero";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_edificaciones($d);
                $item->pertenencia = $this->pertenencia($item);
                $item->nombre = $this->cliente->get($item->codcliente)->nombre;
                $item->info = $this->cliente_info->get($item->codcliente);
                $item->vehiculos = $this->cliente_vehiculo->get_by_field('codcliente', $item->codcliente);
                $lista[] = $item;
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
            $item = new residentes_edificaciones($data[0]);
            $item->pertenencia = $this->pertenencia($item);
            if($item->codcliente){
                $item->nombre = $this->cliente->get($item->codcliente)->nombre;
                $item->telefono = $this->cliente->get($item->codcliente)->telefono1;
                $item->email = $this->cliente->get($item->codcliente)->email;
                $item->info = $this->cliente_info->get($item->codcliente);
                $item->vehiculos = $this->cliente_vehiculo->get_by_field('codcliente', $item->codcliente);
            }
            return $item;
        }else{
            return false;
        }
    }

    /**
     * //Si queremos buscar por codigo o codigo_interno o codcliente o numero
     * @param type $field string
     * @param type $value string
     * @return boolean|\FacturaScripts\model\residentes_edificaciones
     */
    public function get_by_field($field,$value){
        $query = (is_string($value))?$this->var2str($value):$this->intval($value);
        $sql = "SELECT * FROM ".$this->table_name." WHERE ".strtoupper(trim($field))." = ".$query." ORDER BY codigo_interno,numero;";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $item = new residentes_edificaciones($d);
                $item->pertenencia = $this->pertenencia($item);
                if($item->codcliente){
                    $item->nombre = $this->cliente->get($item->codcliente)->nombre;
                    $item->info = $this->cliente_info->get($item->codcliente);
                    $item->vehiculos = $this->cliente_vehiculo->get_by_field('codcliente', $item->codcliente);
                }
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
                    "fecha_ocupacion = ".$this->var2str($this->fecha_ocupacion).", ".
                    "fecha_disponibilidad = ".$this->var2str($this->fecha_disponibilidad).", ".
                    "id_edificacion = ".$this->intval($this->id_edificacion).", ".
                    "codigo = ".$this->var2str($this->codigo).", ".
                    "codigo_interno = ".$this->var2str($this->codigo_interno).", ".
                    "numero = ".$this->var2str($this->numero).", ".
                    "ubicacion = ".$this->var2str($this->ubicacion).", ".
                    "codcliente = ".$this->var2str($this->codcliente).", ".
                    "coordenadas = ".$this->var2str($this->coordenadas).", ".
                    "ocupado = ".$this->var2str($this->ocupado)." ".
                    "WHERE id = ".$this->intval($this->id).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (id_edificacion, codigo, codigo_interno, numero, ubicacion, coordenadas,codcliente, ocupado, fecha_ocupacion, fecha_disponibilidad) VALUES (".
                    $this->intval($this->id_edificacion).", ".
                    $this->var2str($this->codigo).", ".
                    $this->var2str($this->codigo_interno).", ".
                    $this->var2str($this->numero).", ".
                    $this->var2str($this->ubicacion).", ".
                    $this->var2str($this->coordenadas).", ".
                    $this->var2str($this->codcliente).", ".
                    $this->var2str($this->ocupado).", ".
                    $this->var2str($this->fecha_ocupacion).", ".
                    $this->var2str($this->fecha_disponibilidad).");";
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
                $l = new residentes_edificaciones($d);
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

    public function cantidad_inmuebles($id_edificacion){
        $sql = "SELECT count(*) as cantidad FROM ".$this->table_name." WHERE id_edificacion = ".$this->intval($id_edificacion).";";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['cantidad'];
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

    public function search($query){
        $sql = "SELECT * FROM ".$this->table_name." WHERE ";
        $OR = "";
        if(is_int($query)){
            $sql.=" id_edificacion LIKE '%".$query."%' ";
            $sql.=" id LIKE '%".$query."%' ";
            $OR = "OR";
        }
        $sql.=" $OR numero LIKE '%".strtoupper($query)."%' ";
        $sql.=" OR codigo LIKE '%".strtoupper($query)."%' ";
        $sql.=" ORDER BY codigo,numero";
        $data = $this->db->select($sql);
        $lista = array();
        if($data){
            foreach($data as $d){
                $item = new residentes_edificaciones($d);
                $item->pertenencia = $this->pertenencia($item);
                $item->nombre = 'Libre';
                $item->info = '';
                $item->vehiculos = '';
                if($item->codcliente){
                    $item->nombre = $this->cliente->get($item->codcliente)->nombre;
                    $item->info = $this->cliente_info->get($item->codcliente);
                    $item->vehiculos = $this->cliente_vehiculo->get_by_field('codcliente', $item->codcliente);
                }
                $lista[] = $item;
            }
        }
        return $lista;
    }

}
