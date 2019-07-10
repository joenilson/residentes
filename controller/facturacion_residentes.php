<?php

/*
 * Copyright (C) 2019 Joe Nilson <joenilson at gmail.com>
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

require_once 'plugins/residentes/extras/residentes_pdf.php';
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_once 'plugins/residentes/extras/residentes_controller.php';

/**
 * Description of facturacion_residentes
 *
 * @author joenilson
 */


class facturacion_residentes extends residentes_controller 
{

    public $printExtensions;
    public $familia;
    public $familias;
    public $impuesto;
    public $referencia;
    public $forma_pago;
    public $loop_horas;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Facturación Residentes', 'residentes', FALSE, TRUE);
    }
    
    protected function private_core()
    {
        parent::private_core();
    }
    
    public function init()
    {
        parent::init();
        $extensions = new fs_extension();
        $this->printExtensions = $extensions->all_to('ventas_factura');
        $this->forma_pago = new forma_pago();
        $this->familia = new familia();
        $this->familias = $this->familia->get("RESIDENT");
        $this->impuesto = new impuesto();
        $this->loop_horas = [];
        //Creamos un array para el selector de horas para cron
        for ($x = 0; $x < 25; $x++) {
            $this->loop_horas[] = str_pad($x, 2, "0", STR_PAD_LEFT);
        }
    }
    
    private function shared_functions()
    {
        
    }
    
    
}
