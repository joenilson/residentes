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

    public function init()
    {
        $this->clientes = new cliente();
        $this->edificaciones = new residentes_edificaciones();
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
            //$fecha.
            " ORDER BY f.fecha,f.idfactura;";
        $data = $this->db->select($sql);
        $lista = [];
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
}
