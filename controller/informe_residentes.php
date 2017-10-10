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
require_model('residentes_edificaciones.php');
require_model('residentes_informacion.php');
require_model('residentes_vehiculos.php');
require_model('residentes_edificaciones_tipo.php');
require_model('residentes_edificaciones_mapa.php');

/**
 * Description of informe_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_residentes extends fs_controller {

    public $bloque;
    public $desde;
    public $hasta;
    public $resultados;
    public $tipo;
    public $residentes;
    public $edificaciones;
    public $total;
    public $total_resultado;
    public $lista;
    public $vehiculos;
    public $codigo_edificacion;
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $inmuebles_libres;
    public $inmuebles_ocupados;
    public $total_vehiculos;
    public $limit;
    public $offset;
    public $order;
    public $search;
    public $sort;
    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'informes', FALSE, TRUE);
    }

    protected function private_core() {
        $this->shared_extensions();
        $this->init_variables();
        $this->init_filters();

        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];

        /// forzamos la comprobación de la tabla residentes
        new residentes_edificaciones();

        $this->tipo = 'informacion';
        if (isset($_GET['tipo'])) {
            $this->tipo = $_GET['tipo'];
        }

        $this->codigo_edificacion = NULL;
        if (\filter_input(INPUT_POST,'codigo_edificacion')) {
            $this->codigo_edificacion = \filter_input(INPUT_POST,'codigo_edificacion');
        }


        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);
        $this->total_vehiculos = count($this->vehiculos->all());

        $this->informacion_edificaciones();

        if($this->filter_request('lista')){
            $this->procesarLista($this->filter_request('lista'));
        }
    }

    public function init_variables()
    {
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $this->edificaciones = new residentes_edificaciones();
        $this->vehiculos = new residentes_vehiculos();
    }

    public function init_filters()
    {
        $this->desde = \Date('01-m-Y');
        if ($this->filter_request('desde')){
            $this->desde = $this->filter_request('desde');
        }

        $this->hasta = \Date('t-m-Y');
        if ($this->filter_request('hasta')){
            $this->hasta = $this->filter_request('hasta');
        }

        $sort = $this->filter_request('sort');
        $order = $this->filter_request('order');
        $this->offset = $this->confirmarValor($this->filter_request('offset'),0);
        $this->limit = $this->confirmarValor($this->filter_request('limit'),FS_ITEM_LIMIT);
        $this->search = $this->confirmarValor($this->filter_request('search'),false);
        $this->sort = ($sort and $sort!='undefined')?$sort:'codigo, numero';
        $this->order = ($order and $order!='undefined')?$order:'ASC';
    }

    public function informacion_edificaciones(){
        $this->resultado = array();
        $this->total_resultado = 0;
        $l = new stdClass();
        $l->descripcion = $this->padre->descripcion;
        $l->cantidad = count($this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id));
        $this->resultado[] = $l;
        $this->total_resultado++;
        //Buscamos la estructura interna
        $this->informacion_interna($this->padre->id);
        //Agregamos los inmuebles
        $edificaciones = $this->edificaciones->all();
        if($edificaciones){
            $l = new stdClass();
            $l->descripcion = 'Inmueble';
            $l->cantidad = count($edificaciones);
            $this->resultado[] = $l;
        }
        //Verificamos los que están ocupados
        $edificaciones_ocupadas = $this->edificaciones->all_ocupados();
        $this->inmuebles_libres = count($edificaciones)-count($edificaciones_ocupadas);
        $this->inmuebles_ocupados = count($edificaciones_ocupadas);
    }

    public function procesarLista($lista)
    {
        $this->template = false;
        switch ($lista) {
            case 'informe_residentes':
                list($resultados, $cantidad) = $this->lista_residentes();
                break;
            case 'informe_inmuebles':
                list($resultados, $cantidad) = $this->lista_inmuebles();
                break;
            case 'informe_cobros':
                list($resultados, $cantidad) = $this->lista_cobros();
                break;
            default:
                break;
        }
        header('Content-Type: application/json');
        $data['rows'] = $resultados;
        $data['total'] = $cantidad;
        echo json_encode($data);
    }

    public function lista_residentes()
    {
        $sql = " select r.codcliente, c.nombre, codigo, numero, fecha_ocupacion ".
            " from residentes_edificaciones as r, clientes as c ".
            " where r.codcliente = c.codcliente ".
            " order by ".$this->sort." ".$this->order;
        $data = $this->db->select_limit($sql, $this->limit, $this->offset);
        $sql_cantidad = "select count(*) as total ".
            " from residentes_edificaciones ".
            " where codcliente != ''";
        $data_cantidad = $this->db->select($sql_cantidad);
        return array($data, $data_cantidad[0]['total']);
    }

    public function lista_inmuebles()
    {
        return array();
    }

    public function lista_cobros()
    {
        return array();
    }

    public function informacion_interna($id){
        $lista_tipo = $this->edificaciones_tipo->get_by_field('padre', $id);
        if($lista_tipo){
            foreach($lista_tipo as $linea){
                $l = new stdClass();
                $l->descripcion = $linea->descripcion;
                $l->cantidad = count($this->edificaciones_mapa->get_by_field('id_tipo', $linea->id));
                $this->resultado[] = $l;
                $this->total_resultado ++;
                $this->informacion_interna($linea->id);
            }
        }else{
            return true;
        }
    }

    /**
     * Función para devolver un valor u otro dependiendo si está presente
     * el primer valor y si la variable existe
     * @param string $variable
     * @param string $valor_si
     * @param string $valor_no
     * @return string
     */
    public function setValor($variable, $valor_si, $valor_no)
    {
        $valor = $valor_no;
        if(!empty($variable) and ($variable == $valor_si)){
            $valor = $valor_si;
        }
        return $valor;
    }

    /**
     * Función para devolver el valor que no esté vacio
     * @param string $valor1
     * @param string $valor2
     * @return string
     */
    public function confirmarValor($valor1, $valor2)
    {
        $valor = $valor2;
        if(!empty($valor1)){
            $valor = $valor1;
        }
        return $valor;
    }

    /**
     * Función para devolver el valor de una variable pasada ya sea por POST o GET
     * @param type string
     * @return type string
     */
    public function filter_request($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre);
        $nombre_get = \filter_input(INPUT_GET, $nombre);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function filter_request_array($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $nombre_get = \filter_input(INPUT_GET, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function url() {
        if (isset($_REQUEST['inmueble'])) {
            return 'index.php?page=informe_residentes&inmueble=' . $_REQUEST['inmueble'];
        } else
            return parent::url();
    }

    private function str2bool($v) {
        return ($v == 't' OR $v == '1');
    }

    public function shared_extensions() {
        $extensiones = array(
            array(
                'name' => '001_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/1/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/1/bootstrap-table-locale-all.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '009_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'view/js/chart.bundle.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_informe_edificaciones_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/residentes/view/css/bootstrap-table.min.css"/>',
                'params' => ''
            ),
        );

        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }

}
