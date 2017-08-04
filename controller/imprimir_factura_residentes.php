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
require_model('factura_cliente.php');
require_model('cliente.php');
require_model('almacen.php');
/**
 * Description of imprimir_factura_residentes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class imprimir_factura_residentes extends fs_controller {
    public $imprimir;
    public $id;
    public $factura;
    public $factura_logo_uri;
    public $facturas_pendientes;
    public $cliente;
    public $articulo;
    public $sizeFactura;
    public $total_facturas_pendientes;
    public function __construct() {
        parent::__construct(__CLASS__, 'Factura Residente', 'ventas', TRUE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->shared_extensions();
        $id_p = \filter_input(INPUT_POST, 'id');
        $id_g = \filter_input(INPUT_GET, 'id');
        $this->id = ($id_p)?$id_p:$id_g;
        $this->facturas_pendientes = array();
        $this->total_facturas_pendientes = 0;
        $this->sizeFactura = 100;

        $logo = FALSE;
        if( file_exists(FS_MYDOCS.'images/logo.png') )
        {
           $logo = FS_MYDOCS.'images/logo.png';
        }
        else if( file_exists(FS_MYDOCS.'images/logo.jpg') )
        {
           $logo = FS_MYDOCS.'images/logo.jpg';
        }
        
        if($logo){
            $type = pathinfo($logo, PATHINFO_EXTENSION);
            $data = file_get_contents($logo);
            $this->factura_logo_uri = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        if($this->id){
            $fac = new factura_cliente();
            $this->factura = $fac->get($this->id);
            //Agregamos la cantidad de lineas multiplicadas por 4
            $this->sizeFactura+= count($this->factura->get_lineas())*8;
            //Agregamos la linea de separación del total
            $this->sizeFactura+=4;
            //Agregamos las 3 lineas de Neto, FS_IVA y Total
            $this->sizeFactura+=12;
            //Agregamos un espacio de 4 lineas aprox
            $this->sizeFactura+=20;
            $cli = new cliente();
            $this->cliente = $cli->get($this->factura->codcliente);
            $facturas = $fac->all_from_cliente($this->factura->codcliente);
            if($facturas){
                foreach($facturas as $f)
                {
                    if(!$f->pagada){
                        $this->facturas_pendientes[] = array('factura'=>$f->codigo,'fecha'=>$f->fecha,'monto'=>$f->total);
                        $this->sizeFactura+=4;
                        $this->total_facturas_pendientes += $f->total;
                    }
                }
            }
            $this->sizeFactura+=20;
        }
    }

    public function url(){
        if($this->id){
            return parent::url().'&id='.$this->id;
        }
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'factura_residentes',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'pdf',
                'text' => 'Factura Residentes',
                'params' => ''
            ),
            array(
                'name' => '001_imprimir_factura_residentes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/jspdf.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_imprimir_factura_residentes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/jsPDF/plugins/split_text_to_size.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );

        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }
}
