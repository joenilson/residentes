<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'base/fs_controller.php';
require_once 'plugins/residentes/extras/residentes_controller.php';
require_once 'plugins/residentes/extras/residentesEnviarMail.php';
require_once 'plugins/residentes/extras/fpdf183/residentesFpdf.php';

class residentesFacturaDetallada
{
    public $archivo ;
    public $document;
    public $emailHelper;
    public $output;
    public $user;
    public $log;
    private $residentesController;
    public function __construct($orientation = 'L', $um = 'mm', $size = 'A5', $output = 'enviar', $archivo, $user)
    {
        $this->archivo = ($archivo) ?: \date('dmYHis') . ".pdf";
        $this->output = $output;
        $this->user = $user;
        $this->log = new fs_core_log();
        $this->emailHelper = new ResidentesEnviarMail();
        $this->document = new ResidentesFpdf($orientation, $um, $size);
        $this->residentesController = new residentes_controller();
    }

    /**
     * @param object $companyInformation
     * @param object $invoice
     * @param object $customer
     */
    public function crearFactura(&$companyInformation, &$invoice, &$customer)
    {
        $datosFactura = $this->invoiceData($companyInformation, $invoice);
        $datosEmpresa = (array) $companyInformation;
        $customerInfo = (array) $customer;
        $customerInfo['direccion'] = trim($customer->inmueble->codigo_externo()) . " numero " . $customer->inmueble->numero;
        $this->document->createDocument($datosEmpresa, $datosFactura[0], $datosFactura[1], $customerInfo);
        $this->residentesController->cliente_residente = $customer;
        $pendiente = $this->residentesController->pagosFactura(false);
        $this->document->addEstadoCuentaPendiente($pendiente);
        if ($this->output === 'enviar') {
            $this->document->Output(
                'F',
                'tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo,
                true
            );
            $this->emailHelper->invoiceEmail($companyInformation, $invoice, $customer, $this->user, $this->archivo);
        } else {
            $this->document->Output(
                'I',
                'factura_' .$datosFactura[0]['numero2']. '_' . \date('dmYhis') . '.pdf'
            );
        }
    }

    private function invoiceData($empresa, $invoice)
    {
        $datosFacturaDetalle = [];
        $datosFacturaCabecera = (array) $invoice;
        if ($this->residentesController->RD_plugin) {
            $ncf = new ncf_ventas();
            $ncfTipo = $ncf->get($empresa->id, $invoice->numero2);
            $datosFacturaCabecera['tiponcf'] = $ncfTipo[0]->tipo_descripcion;
            $datosFacturaCabecera['vencimientoncf'] = $ncfTipo[0]->fecha_vencimiento;
        }
        $lineas = $invoice->get_lineas();
        $totalAntesDescuento = 0;
        $totalDescuento = 0;
        foreach ($lineas as $linea) {
            $totalAntesDescuento += $linea->pvpsindto;
            $totalDescuento += ($linea->pvpsindto - $linea->pvptotal);
            $datosFacturaDetalle[] = (array) $linea;
        }
        $datosFacturaCabecera['total_antes_descuento'] = $totalAntesDescuento;
        $datosFacturaCabecera['total_descuento'] = $totalDescuento;
        return [$datosFacturaCabecera, $datosFacturaDetalle];
    }
}