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
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('cliente.php');

require_model('residentes_edificaciones.php');
require_model('residentes_informacion.php');
require_model('residentes_vehiculos.php');
require_model('residentes_edificaciones_tipo.php');
require_model('residentes_edificaciones_mapa.php');

/**
 * Description of lista_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class lista_residentes extends fs_controller {

    public $bloque;
    public $cliente;
    public $residente;
    public $query;
    public $query_r;
    public $query_i;
    public $query_v;
    public $offset;
    public $resultados;
    public $total_resultados;
    public $edificacion_tipo;
    public $edificacion_mapa;
    public $tipo_edificaciones;
    public $residente_informacion;
    public $residente_vehiculo;
    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'residentes', FALSE, TRUE);
    }

    protected function private_core() {

        $this->init_variables();
        $this->filters();

        if (isset($_REQUEST['offset'])) {
            $this->offset = intval($_REQUEST['offset']);
        }

        $accion = filter_input(INPUT_POST, 'accion');
        switch ($accion) {
            case "agregar_residente":
                $this->agregar_residente();
                break;
            default:
                break;
        }

        if (isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        } elseif (isset($_REQUEST['buscar_cliente_avanzado'])) {
            $this->buscar_cliente_avanzado();
        } elseif (isset($_REQUEST['buscar_inmueble'])) {
            $this->buscar_inmueble();
        } elseif (isset($_GET['delete'])) {
            $inq = $this->residente->get($_GET['delete']);
            if ($inq) {
                $inq->ocupado = FALSE;
                $inq->codcliente = '';
                $inq->fecha_disponibilidad = NULL;
                $inq->fecha_ocupacion = NULL;
                if ($inq->save()) {
                    $this->new_message('Inquilino eliminado correctamente.');
                } else
                    $this->new_error_msg('Error al eliminar el inquilino.');
            } else
                $this->new_error_msg('Inquilino no encontrado.');
        }


        $this->buscar();
    }

    public function init_variables()
    {
        $this->bloque = '';
        $this->cliente = new cliente();
        $this->residente = new residentes_edificaciones();
        $this->residente_informacion = new residentes_informacion();
        $this->residente_vehiculo = new residentes_vehiculos();
        $this->edificacion_tipo = new residentes_edificaciones_tipo();
        $this->edificacion_mapa = new residentes_edificaciones_mapa();
        $this->tipo_edificaciones = $this->edificacion_tipo->all();
        $this->offset = 0;
    }

    public function filters()
    {
        $this->query_r = '';
        if (isset($_REQUEST['query_r'])) {
            $this->query_r = $_REQUEST['query_r'];
        }

        $this->query_v = '';
        if (isset($_REQUEST['query_v'])) {
            $this->query_v = $_REQUEST['query_v'];
        }

        $this->query_i = '';
        if (isset($_REQUEST['query_i'])) {
            $this->query_i = $_REQUEST['query_i'];
        }

        $this->orden = 'r.codigo, r.numero ASC';
        if (isset($_REQUEST['orden'])) {
            $this->orden = $_REQUEST['orden'];
        }
    }

    public function buscar(){
        $this->total_resultados = 0;
        if($this->query_r){
            $query = mb_strtolower($this->cliente->no_html($this->query_r), 'UTF8');
            $sql = " FROM residentes_edificaciones as r JOIN clientes as c ON r.codcliente = c.codcliente";
            $sql .= " JOIN residentes_informacion as i ON i.codcliente = r.codcliente ";
            $and = ' WHERE ';
            $this->buscar_residentes($query,$sql,$and);

            $data = $this->db->select("SELECT COUNT(r.codcliente) as total" . $sql . ';');
            if ($data) {
                $this->total_resultados = intval($data[0]['total']);
                $data2 = $this->db->select_limit("SELECT r.*, c.nombre " . $sql . " ORDER BY " . $this->orden, FS_ITEM_LIMIT, $this->offset);
                if ($data2) {
                    foreach ($data2 as $d) {
                        $item = new residentes_edificaciones($d);
                        $item->nombre = $d['nombre'];
                        $item->info = $this->residente_informacion->get($d['codcliente']);
                        $item->vehiculos = $this->residente_vehiculo->get_by_field('codcliente', $item->codcliente);
                        $this->resultados[] = $item;
                    }
                }
            }
        }

        if($this->query_v){
            //Buscamos los vehiculos
            $query = mb_strtolower($this->cliente->no_html($this->query_v), 'UTF8');
            $sql = " FROM residentes_edificaciones as r JOIN clientes as c ON (r.codcliente = c.codcliente)";
            $sql .= " JOIN residentes_vehiculos as i ON i.codcliente = r.codcliente ";
            $and = ' WHERE ';
            $this->buscar_vehiculos($query, $sql, $and);
            $data = $this->db->select("SELECT COUNT(r.codcliente) as total" . $sql . ';');
            if ($data) {
                $this->total_resultados = intval($data[0]['total']);
                $data2 = $this->db->select_limit("SELECT r.*, c.nombre " . $sql . " ORDER BY " . $this->orden, FS_ITEM_LIMIT, $this->offset);
                if ($data2) {
                    foreach ($data2 as $d) {
                        $item = new residentes_edificaciones($d);
                        $item->nombre = $d['nombre'];
                        $item->info = $this->residente_informacion->get($d['codcliente']);
                        $item->vehiculos = $this->residente_vehiculo->get_by_field('codcliente', $item->codcliente);
                        $this->resultados[] = $item;
                    }
                }
            }
        }

        if($this->query_i){
            //Buscamos el inmueble
            $query = mb_strtolower($this->cliente->no_html($this->query_i), 'UTF8');
            $sql = " FROM residentes_edificaciones as r JOIN clientes as c ON (r.codcliente = c.codcliente)";
            $and = ' WHERE ';
            $this->buscar_inmuebles($query, $sql, $and);
            $data = $this->db->select("SELECT COUNT(r.codcliente) as total" . $sql . ';');
            if ($data) {
                $this->total_resultados = intval($data[0]['total']);
                $data2 = $this->db->select_limit("SELECT r.*, c.nombre " . $sql . " ORDER BY " . $this->orden, FS_ITEM_LIMIT, $this->offset);
                if ($data2) {
                    foreach ($data2 as $d) {
                        $item = new residentes_edificaciones($d);
                        $item->nombre = $d['nombre'];
                        $item->info = $this->residente_informacion->get($d['codcliente']);
                        $item->vehiculos = $this->residente_vehiculo->get_by_field('codcliente', $item->codcliente);
                        $this->resultados[] = $item;
                    }
                }
            }
        }

        if(!$this->query_i AND !$this->query_r AND !$this->query_v){
            $sql = " FROM residentes_edificaciones as r JOIN clientes as c ON (r.codcliente = c.codcliente)";
            $data = $this->db->select("SELECT COUNT(r.codcliente) as total" . $sql . ';');
            if ($data) {
                $this->total_resultados = intval($data[0]['total']);
                $data2 = $this->db->select_limit("SELECT r.*, c.nombre " . $sql . " ORDER BY " . $this->orden, FS_ITEM_LIMIT, $this->offset);
                if ($data2) {
                    foreach ($data2 as $d) {
                        $item = new residentes_edificaciones($d);
                        $item->nombre = $d['nombre'];
                        $item->info = $this->residente_informacion->get($d['codcliente']);
                        $item->vehiculos = $this->residente_vehiculo->get_by_field('codcliente', $item->codcliente);
                        $this->resultados[] = $item;
                    }
                }
            }
        }
    }

    public function buscar_residentes(&$query, &$sql, &$and)
    {
        if (is_numeric($query)) {
            $sql .= $and . "(codcliente LIKE '%" . $query . "%'"
                    . " OR cifnif LIKE '%" . $query . "%'"
                    . " OR telefono1 LIKE '" . $query . "%'"
                    . " OR telefono2 LIKE '" . $query . "%'"
                    . " OR ca_telefono LIKE '" . $query . "%'"
                    . " OR observaciones LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= $and . "(lower(nombre) LIKE '%" . $buscar . "%'"
                    . " OR lower(razonsocial) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_apellidos) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_nombres) LIKE '%" . $buscar . "%'"
                    . " OR lower(cifnif) LIKE '%" . $buscar . "%'"
                    . " OR lower(observaciones) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_email) LIKE '%" . $buscar . "%'"
                    . " OR lower(email) LIKE '%" . $buscar . "%')";
        }
    }

    public function buscar_inmuebles(&$query, &$sql, &$and)
    {
        if (is_numeric($query)) {
            $sql .= $and . "(r.codcliente LIKE '%" . $query . "%'"
                    . " OR cifnif LIKE '%" . $query . "%'"
                    . " OR codigo LIKE '%" . $query . "%'"
                    . " OR numero LIKE '%" . $query . "%'"
                    . " OR CONCAT(codigo, numero) LIKE '%" . $query . "%'"
                    . " OR telefono1 LIKE '" . $query . "%'"
                    . " OR telefono2 LIKE '" . $query . "%'"
                    . " OR observaciones LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= $and . "(lower(nombre) LIKE '%" . $buscar . "%'"
                    . " OR lower(codigo) LIKE '%" . $buscar . "%'"
                    . " OR lower(numero) LIKE '%" . $buscar . "%'"
                    . " OR CONCAT(lower(codigo), numero) LIKE '%" . $query . "%'"
                    . " OR lower(razonsocial) LIKE '%" . $buscar . "%'"
                    . " OR lower(cifnif) LIKE '%" . $buscar . "%'"
                    . " OR lower(observaciones) LIKE '%" . $buscar . "%'"
                    . " OR lower(email) LIKE '%" . $buscar . "%')";
        }
    }

    public function buscar_vehiculos(&$query, &$sql, &$and)
    {
        if (is_numeric($query)) {
            $sql .= $and . "(codcliente LIKE '%" . $query . "%'"
                    . " OR vehiculo_placa LIKE '%" . $query . "%'"
                    . " OR CAST(idvehiculo AS CHAR) LIKE '" . $query . "%'"
                    . " OR telefono2 LIKE '" . $query . "%'"
                    . " OR observaciones LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= $and . "(lower(vehiculo_marca) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_modelo) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_color) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_placa) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_tipo) LIKE '%" . $buscar . "%'"
                    . " OR lower(codigo_tarjeta) LIKE '%" . $buscar . "%')";
        }
    }

    public function agregar_residente(){
        $id_edificacion = \filter_input(INPUT_POST, 'id_edificacion');
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $fecha_ocupacion = \filter_input(INPUT_POST, 'fecha_ocupacion');
        $fecha_disponibilidad = \filter_input(INPUT_POST, 'fecha_disponibilidad');
        $accion = \filter_input(INPUT_POST, 'accion');
        $inmueble = $this->residente->get($id_edificacion);
        if($inmueble AND $accion == 'agregar_residente'){
            $inmueble->ocupado = TRUE;
            $inmueble->codcliente = $codcliente;
            $inmueble->fecha_ocupacion = ($fecha_ocupacion)?\date('Y-m-d',strtotime($fecha_ocupacion)):NULL;
            $inmueble->fecha_disponibilidad = ($fecha_disponibilidad)?\date('Y-m-d',strtotime($fecha_disponibilidad)):NULL;
            if($inmueble->save()){
                $this->new_message('Residente agregado exitosamente.');
            }else{
                $this->new_error_msg('No se pudo agregar al residente confirme el nombre del residente y las fechs de ocupación y disponibilidad');
            }
        }elseif($inmueble AND $accion == 'quitar_residente'){
            $inmueble->ocupado = FALSE;
            $inmueble->codcliente = '';
            $inmueble->fecha_ocupacion = '';
            $inmueble->fecha_disponibilidad = '';
            if($inmueble->save()){
                $this->new_message('Residente removido exitosamente.');
            }else{
                $this->new_error_msg('No se pudo remover al residente');
            }
        }
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        foreach ($this->cliente->search($_REQUEST['buscar_cliente']) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
    }

    private function buscar_cliente_avanzado() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;
        $json = array();
        //Buscamos en la lista de clientes
        foreach ($this->cliente->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            $lista = $this->residente->get_by_field('codcliente', $cli->codcliente);
            if($lista){
                foreach($lista as $residente){
                    $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente, 'nombre' => $cli->nombre, 'asignado' => true);
                }
            }else{
                $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente, 'nombre' => $cli->nombre, 'asignado' => false);
            }
        }
        //Buscamos en los datos adicionales del residente
        foreach ($this->residente_informacion->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
            }
        }
        //Buscamos en los datos de vehiculos del residente
        foreach ($this->residente_vehiculo->search($_REQUEST['buscar_cliente_avanzado']) as $cli){
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre.' '.$cli->vehiculo_placa." ".$cli->vehiculo_marca.''.$cli->vehiculo_modelo, 'data' => $cli->codcliente);
            }
        }
        //Buscamos en las residencias
        foreach($this->residente->search($_REQUEST['buscar_cliente_avanzado']) as $cli){
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre." ".$cli->codigo.' '.$cli->numero, 'data' => $cli->id, 'asignado' => true);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente_avanzado'], 'suggestions' => $json));
    }

    private function buscar_inmueble() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        foreach ($this->residente->search($_REQUEST['buscar_inmueble']) as $inmueble) {
            if(!$inmueble->ocupado){
                $json[] = array('value' => $inmueble->codigo.$inmueble->numero, 'data' => $inmueble->id);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_inmueble'], 'suggestions' => $json));
    }

    public function paginas() {
        $url = $this->url() . "&query=" . $this->query
                . "&query_r=" . $this->query_r
                . "&query_v=" . $this->query_v
                . "&query_i=" . $this->query_i
                . "&orden=" . $this->orden;

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        /// añadimos todas la página
        while ($num < $this->total_resultados) {
            $paginas[$i] = array(
                'url' => $url . "&offset=" . ($i * FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num == $this->offset)
            );

            if ($num == $this->offset) {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach ($paginas as $j => $value) {
            $enmedio = intval($i / 2);

            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j > 1 AND $j < $actual - 5 AND $j != $enmedio) OR ( $j > $actual + 5 AND $j < $i - 1 AND $j != $enmedio)) {
                unset($paginas[$j]);
            }
        }

        if (count($paginas) > 1) {
            return $paginas;
        } else {
            return array();
        }
    }

}
