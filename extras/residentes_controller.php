<?php
/*
 * Copyright (C) 2018 Joe Nilson <joenilson at gmail.com>
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
 * Description of residentes_controller
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_controller extends fs_controller
{
    public $cliente_residente;
    public $clientes;
    public $edificaciones;
    public $desde;
    public $hasta;
    public $tesoreria;
    protected function private_core()
    {
        parent::private_core();
        $this->init();
    }

    public function diasAtraso($f1,$f2)
    {
        $date1 = new DateTime($f1);
        $date2 = new DateTime($f2);
        return $date2->diff($date1)->format("%a");
    }

    public function existe_tesoreria()
    {
        $this->tesoreria = false;
        //revisamos si esta el plugin de tesoreria
        $disabled = array();
        if (defined('FS_DISABLED_PLUGINS')) {
            foreach (explode(',', FS_DISABLED_PLUGINS) as $aux) {
                $disabled[] = $aux;
            }
        }
        if (in_array('tesoreria', $GLOBALS['plugins']) and ! in_array('tesoreria', $disabled)) {
            $this->tesoreria = true;
        }
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

    public function init()
    {
        $this->clientes = new cliente();
        $this->edificaciones = new residentes_edificaciones();
        $this->init_templates();
    }

    public function mostrar_direcciones_residente($codcliente)
    {
        $cli = new cliente();
        $cliente = $cli->get($codcliente);
        $data = $cliente->get_direcciones();
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function init_templates()
    {
        $fsvar = new fs_var();
        $residentes_email_plantillas = array();
        $residentes_email_plantillas['mail_informe_residentes'] = "Buenos días, le adjunto su #DOCUMENTO#.\n\n#FIRMA# 4";
        $email_plantillas = $fsvar->array_get($residentes_email_plantillas, FALSE);
        $fsvar->array_save($email_plantillas);
    }

    public function mostrar_informacion_residente(){
        $this->template = 'mostrar_informacion_residente';
        $cod = $this->filter_request('codcliente');
        $this->cliente_residente = $this->clientes->get($cod);
        $this->cliente_residente->residente = $this->edificaciones->get_by_field('codcliente',$cod);
        $this->pagos_pendientes = $this->pagosFactura(false);
        $this->pagos_realizados = $this->pagosFactura(true);
    }

    public function pagosFactura($pagada=false)
    {
        $fecha = '';
        if(isset($this->desde) AND isset($this->hasta)){
            $fecha = " AND f.fecha between ".$this->empresa->var2str(\date('Y-m-d',strtotime($this->desde))). " AND ".
            $this->empresa->var2str(\date('Y-m-d',strtotime($this->hasta)));
        }
        $tipo_pagada = ($pagada)?'TRUE':'FALSE';
        $sql = "SELECT f.idfactura, f.numero2, f.vencimiento, lf.referencia, lf.descripcion, f.fecha, lf.pvpsindto, lf.dtopor, lf.pvptotal".
            " FROM facturascli as f left join lineasfacturascli as lf ON (f.idfactura = lf.idfactura)".
            " WHERE f.anulada = FALSE AND f.pagada = ".$tipo_pagada.
            " AND f.codcliente = ".$this->empresa->var2str($this->cliente_residente->codcliente).
            " ORDER BY f.fecha,f.idfactura;";
        $data = $this->db->select($sql);
        $lista = array();
        $fact = new factura_cliente();
        foreach($data as $l){
            $linea = (object) $l;
            $linea->f_pago = $linea->fecha;
            $linea->dias_atraso = ($pagada)?0:$this->diasAtraso($linea->vencimiento, \date('d-m-Y'));
            if(in_array('tesoreria', $GLOBALS['plugins'])){

            }
            if($pagada){
                $f = $fact->get($linea->idfactura);
                $fp = $f->get_asiento_pago();
                $linea->f_pago = ($fp)?$fp->fecha:$linea->f_pago;
            }
            $lista[] = $linea;
        }
        return $lista;

    }

    /**
     * Función para actualizar la dirección de un cliente al asignarlo a un inmueble
     * @param string $codcliente
     * @param integer $iddireccion
     * @param varchar $nueva_direccion
     * @return integer
     */
    public function actualizar_direccion_residente($codcliente, $iddireccion, $nueva_direccion)
    {
        $cli = new cliente();
        $cliente = $cli->get($codcliente);
        if($iddireccion !== ''){
            foreach($cliente->get_direcciones() as $dir){
                if($dir->id === (int)$iddireccion){
                    $dir->direccion = $nueva_direccion;
                    $dir->save();
                    break;
                }
            }
            return $iddireccion;
        }else{
            $dir = new direccion_cliente();
            $dir->direccion = $nueva_direccion;
            $dir->codcliente = $codcliente;
            $dir->ciudad = $this->empresa->ciudad;
            $dir->apartado = $this->empresa->apartado;
            $dir->provincia = $this->empresa->provincia;
            $dir->codpais = $this->empresa->codpais;
            $dir->codpostal = $this->empresa->codpostal;
            $dir->domenvio = true;
            $dir->domfacturacion = true;
            $dir->descripcion = 'Inmueble '.$nueva_direccion;
            $dir->fecha = \date('d-m-Y');
            $dir->save();
            return $dir->id;
        }
    }
}
