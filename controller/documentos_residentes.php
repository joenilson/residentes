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
require_once 'plugins/facturacion_base/extras/fs_pdf.php';
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_once 'plugins/residentes/extras/residentes_controller.php';
/**
 * Class Controller to manage all the documents to be printed, showed or emailed
 * in the Residentes plugin for FS_2017
 * @author Joe Nilson <joenilson at gmail.com>
 */
class documentos_residentes extends residentes_controller
{
    public $residente;
    public $documento;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Documentos Residentes', 'admin', FALSE, TRUE, FALSE);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->template = false;
        $cod = filter_input(INPUT_POST, 'codcliente');
        $cliente = new cliente();
        $this->residente = $cliente->get($cod);
        $info_accion = filter_input(INPUT_POST, 'info_accion');
        $tipo_documento = filter_input(INPUT_POST, 'tipo_documento');
        if($this->residente AND $info_accion)
        {
            switch($info_accion)
            {
                case 'imprimir':
                    $this->imprimir_documento($tipo_documento);
                    break;
                case 'enviar':
                    $this->enviar_documento($tipo_documento);
                    break;
                default:

                    break;
            }
        }
    }

    public function crear_documento($tipo_documento)
    {
        $this->documento = new fs_pdf();
        switch($tipo_documento)
        {
            case 'informacion_cobros':
                $this->documento->pdf->addInfo('Title', 'Pagos Residente ' . $this->residente->codcliente);
                $this->documento->pdf->addInfo('Subject', 'Pagos del Residente ' . $this->residente->codigo);
                $this->documento->pdf->addInfo('Author', $this->empresa->nombre);
                $this->crear_documento_cobros();
                break;
            default:
                $this->documento = false;
                break;
        }
    }

    public function crear_documento_cobros()
    {
        $archivo = \date('dmYhis').'.pdf';
        if (filter_input(INPUT_POST, 'info_accion') === 'enviar') {
            if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                mkdir('tmp/' . FS_TMP_NAME . 'enviar');
            }

            $this->documento->save('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $this->documento->show('documento_cobros_' . \date('dmYhis') . '.pdf');
        }
    }

    public function enviar_documento($tipo_documento)
    {
        $this->crear_documento($tipo_documento);
    }

    public function imprimir_documento($tipo_documento)
    {
        $this->template = false;
        $this->crear_documento($tipo_documento);
    }

    public function init_variables()
    {

    }
}
