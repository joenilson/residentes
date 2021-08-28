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
require_once 'plugins/residentes/extras/residentes_pdf.php';
require_once 'plugins/residentes/extras/residentesEnviarMail.php';

class ResidentesEstadoCuenta
{
    public $archivo;
    public $empresa;
    public $emailHelper;
    public $cliente_residente;
    public $documento;
    public $info_accion;
    public $user;
    public $rc;
    public function __construct($empresa, $cliente_residente, $user, $archivo, $info_accion)
    {
        $this->archivo = $archivo;
        $this->empresa = $empresa;
        $this->cliente_residente = $cliente_residente;
        $this->user = $user;
        $this->info_accion = $info_accion;
        $this->documento = new residentes_pdf('letter', 'portrait');
        $this->emailHelper = new ResidentesEnviarMail();
    }

    public function crearEstadoCuenta($pendiente, $pagado)
    {
        $this->documento->cliente_residente = $this->cliente_residente;
        $this->documento->pdf->addInfo('Title', 'Pagos Residente ' .
            $this->cliente_residente->codcliente);
        $this->documento->pdf->addInfo('Subject', 'Pagos del Residente ' .
            $this->cliente_residente->codcliente);
        $this->documento->pdf->addInfo('Author', $this->empresa->nombre);
        $this->documento->pdf->ezSetMargins(10, 10, 10, 10);
        $this->crear_documento_cobros($pendiente, $pagado);
    }

    public function crear_documento_cobros($pendiente, $pagado)
    {
//        $this->pendiente = $this->pagosFactura(false);
//        $this->pagado = $this->pagosFactura(true);
        $linea_actual = 0;
        $pagina = 1;
        $lppag = 32; /// líneas por página
        while ($linea_actual < count($pendiente)) {
            /// salto de página
            if ($linea_actual > 0) {
                $this->documento->pdf->ezNewPage();
            }
            $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
            $this->documento->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            $this->documento->generar_pdf_lineas(
                $this->documento,
                $pendiente,
                $linea_actual,
                $lppag,
                'pendiente'
            );
            $this->documento->set_y($this->documento->pdf->y - 16);
        }

        $linea_actual2 = 0;
        while ($linea_actual2 < count($pagado)) {
            if ($linea_actual2 > 0) {
                $this->documento->pdf->ezNewPage();
            } elseif ($linea_actual === 0) {
                $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
                $this->documento->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            }
            $this->documento->generar_pdf_lineas(
                $this->documento,
                $pagado,
                $linea_actual2,
                $lppag,
                'pagado'
            );
            $pagina++;
        }
        $this->documento->set_y(80);
        if ($this->empresa->pie_factura) {
            $this->documento->pdf->addText(20, 40, 8, fs_fix_html('<b>Generado por:</b> ' .
                $this->user->get_agente_fullname()), 0);
            $this->documento->pdf->addText(
                10,
                30,
                8,
                $this->documento->center_text(fs_fix_html($this->empresa->pie_factura), 180)
            );
        }

        if ($this->info_accion === 'enviar') {
            $this->documento->save('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
            $this->emailHelper->accountStatusEmail(
                $this->empresa,
                $this->cliente_residente,
                $this->user,
                $this->archivo
            );
        } else {
            $this->documento->show('documento_cobros_' . \date('dmYhis') . '.pdf');
        }
    }
}