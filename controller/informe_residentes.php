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
    public $ocupados;
    public $vacios;
    public $codigo_edificacion;
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $inmuebles_libres;
    public $inmuebles_ocupados;
    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'informes', FALSE, TRUE);
    }

    protected function private_core() {
        $this->shared_extensions();
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $this->edificaciones = new residentes_edificaciones();
        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];

        /// forzamos la comprobación de la tabla residentes
        $residente = new residentes_edificaciones();

        $this->tipo = 'informacion';
        if (isset($_GET['tipo'])) {
            $this->tipo = $_GET['tipo'];
        }

        $this->codigo_edificacion = NULL;
        if (\filter_input(INPUT_POST,'codigo_edificacion')) {
            $this->codigo_edificacion = \filter_input(INPUT_POST,'codigo_edificacion');
        }
        $this->desde = Date('01-m-Y');
        if (isset($_POST['desde'])){
            $this->desde = $_POST['desde'];
        }

        $this->hasta = Date('t-m-Y');
        if (isset($_POST['hasta'])){
            $this->hasta = $_POST['hasta'];
        }

        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);
        $this->informacion_edificaciones();
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
                'name' => '009_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'view/js/chart.bundle.min.js" type="text/javascript"></script>',
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
