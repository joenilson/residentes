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
require_once 'plugins/residentes/extras/residentes_pdf.php';
require_once 'plugins/residentes/extras/fpdf183/ResidentesFpdf.php';
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
    public $archivo;
    public $cliente_residente;
    public $documento;
    public $pagado;
    public $pendiente;
    public $tipo_accion;
    public $idprogramacion;
    public $idfactura;
    public $envio_masivo;
    public $factura;
    public $facturas;
    public $contador_facturas;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Documentos Residentes', 'admin', false, false, false);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->init();
        $cod = $this->filter_request('codcliente');
        if ($cod) {
            $this->obtenerInformacionResidente($cod);
        }
        $info_accion = $this->filter_request('info_accion');
        $tipo_documento = $this->filter_request('tipo_documento');
        $this->idprogramacion = $this->filter_request('idprogramacion');
        $this->envio_masivo = !(($this->filter_request('envio_masivo') === 'false'));
        $this->tipo_accion = $tipo_documento;
        $this->verificarCantidadFacturas();
        $this->archivo = $tipo_documento.'_'.\date('dmYhis') . '.pdf';
        if ($this->cliente_residente && $info_accion) {
            switch ($info_accion) {
                case 'imprimir':
                    $this->template = false;
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

    /**
     * @param string $cod
     */
    public function obtenerInformacionResidente($cod)
    {
        $cliente = new cliente();
        $this->cliente_residente = $cliente->get($cod);
        $residenteInformacion = new residentes_informacion();
        $informacion = $residenteInformacion->get($cod);
        $residenteEdificacion = new residentes_edificaciones();
        $residente = $residenteEdificacion->get_by_field('codcliente', $cod);
        $this->cliente_residente->inmueble = $residente[0];
        $this->cliente_residente->informacion = $informacion;
    }

    public function crear_documento($tipo_documento)
    {
        switch ($tipo_documento) {
            case 'informacion_cobros':
                $this->crearEstadoCuenta();
                break;
            case 'factura_residente_detallada':
                //$this->verificarCantidadFacturas();
                $this->crearFacturaDetallada();
                break;
            default:
                $this->documento = false;
                break;
        }
    }

    private function verificarCantidadFacturas()
    {

        $facturas = $this->filter_request('idfactura');
        if ($facturas === '') {
            $this->new_message('No hay facturas para enviar.');
            return;
        }
        $this->getFacturaProgramadaPendiente($facturas);
    }

    /**
     * @param array|string $facturas
     */
    private function getFacturaProgramadaPendiente($facturas)
    {
        $lista_facturas = explode(',', $facturas);
        $this->idfactura = (is_array($lista_facturas)) ? $lista_facturas[0] : $facturas;
        if (is_array($lista_facturas)) {
            unset($lista_facturas[0]);
            $this->contador_facturas = count($lista_facturas);
            $this->facturas = implode(',', $lista_facturas);
        } else {
            $this->contador_facturas = 0;
            $this->facturas = '';
        }


        $facturasCliente = new factura_cliente();
        $f = $facturasCliente->get($this->idfactura);
        $this->factura = $f;
        $this->obtenerInformacionResidente($f->codcliente);
    }

    private function datosFactura()
    {
        $datosFacturaCabecera = [];
        $datosFacturaDetalle = [];
        if ($this->idfactura !== '') {
            $facturas = new factura_cliente();
            $factura = $facturas->get($this->idfactura);
            $datosFacturaCabecera = (array) $factura;
            if ($this->RD_plugin) {
                $ncf = new ncf_ventas();
                $ncfTipo = $ncf->get($this->empresa->id, $factura->numero2);
                $datosFacturaCabecera['tiponcf'] = $ncfTipo[0]->tipo_descripcion;
                $datosFacturaCabecera['vencimientoncf'] = $ncfTipo[0]->fecha_vencimiento;
            }
            $lineas = $factura->get_lineas();
            $totalAntesDescuento = 0;
            $totalDescuento = 0;
            foreach ($lineas as $linea) {
                $totalAntesDescuento += $linea->pvpsindto;
                $totalDescuento += ($linea->pvpsindto - $linea->pvptotal);
                $datosFacturaDetalle[] = (array) $linea;
            }
            $datosFacturaCabecera['total_antes_descuento'] = $totalAntesDescuento;
            $datosFacturaCabecera['total_descuento'] = $totalDescuento;
        }
        return [$datosFacturaCabecera, $datosFacturaDetalle];
    }
    public function crearEstadoCuenta()
    {
        $this->documento = new residentes_pdf('letter', 'portrait');
        $this->documento->cliente_residente = $this->cliente_residente;
        $this->documento->pdf->addInfo('Title', 'Pagos Residente ' .
            $this->cliente_residente->codcliente);
        $this->documento->pdf->addInfo('Subject', 'Pagos del Residente ' .
            $this->cliente_residente->codcliente);
        $this->documento->pdf->addInfo('Author', $this->empresa->nombre);
        $this->documento->pdf->ezSetMargins(10, 10, 10, 10);
        $this->crear_documento_cobros();
    }

    public function crearFacturaDetallada()
    {
        $customerInfo = (array) $this->cliente_residente;
        $customerInfo['direccion'] = trim($this->cliente_residente->inmueble->codigo_externo()) . ' numero '
            . $this->cliente_residente->inmueble->numero;
        $datosFactura = $this->datosFactura();
        $datosEmpresa = (array) $this->empresa;
        $this->documento = new ResidentesFpdf('L', 'mm', 'A5');
//        $this->documento = new ResidentesFpdf('P', 'mm', 'letter');
        $this->documento->createDocument($datosEmpresa, $datosFactura[0], $datosFactura[1], $customerInfo);
        $this->pendiente = $this->pagosFactura(false);
        $this->documento->addEstadoCuentaPendiente($this->pendiente);

        if ($this->filter_request('info_accion') === 'enviar') {
            $this->documento->Output(
                'F',
                'tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo,
                true
            );
        } else {
            $this->documento->Output(
                'I',
                'factura_' .$datosFactura[0]['numero2']. '_' . \date('dmYhis') . '.pdf'
            );
        }
    }

    public function crear_documento_cobros()
    {
        $this->pendiente = $this->pagosFactura(false);
        $this->pagado = $this->pagosFactura(true);

        $linea_actual = 0;
        $pagina = 1;
        $lppag = 32; /// líneas por página
        while ($linea_actual < count($this->pendiente)) {
            /// salto de página
            if ($linea_actual > 0) {
                $this->documento->pdf->ezNewPage();
            }
            $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
            $this->documento->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            $this->documento->generar_pdf_lineas(
                $this->documento,
                $this->pendiente,
                $linea_actual,
                $lppag,
                'pendiente'
            );
            $this->documento->set_y($this->documento->pdf->y - 16);
        }

        $linea_actual2 = 0;
        while ($linea_actual2 < count($this->pagado)) {
            if ($linea_actual2 > 0) {
                $this->documento->pdf->ezNewPage();
            } elseif ($linea_actual === 0) {
                $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
                $this->documento->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            }
            $this->documento->generar_pdf_lineas(
                $this->documento,
                $this->pagado,
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

        if ($this->filter_request('info_accion') == 'enviar') {
            $this->documento->save('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
        } else {
            $this->documento->show('documento_cobros_' . \date('dmYhis') . '.pdf');
        }
    }

    /**
     * @throws phpmailerException
     */
    public function enviar_documento($tipo_documento)
    {
        $this->crear_documento($tipo_documento);
        $tipo_doc = $this->generar_tipo_doc($tipo_documento);
        if (file_exists('tmp/'.FS_TMP_NAME.'enviar/'.$this->archivo)) {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();
            $email = (trim($this->filter_request('email')) !== '')
                ? $this->filter_request('email')
                : $this->cliente_residente->email;
            $this->new_message('Enviando factura a: '.$email);
            $mail->addAddress($email, $this->cliente_residente->nombre);

            $elSubject = ($tipo_documento === 'informacion_cobros')
                ? ': Su Estado de cuenta al '. \date('d-m-Y')
                : ': Su factura ' . $this->factura->codigo . ' ' . $this->factura->numero2;

            $mail->Subject = fs_fix_html($this->empresa->nombre) . $elSubject;
            $mail->AltBody = ($tipo_documento === 'informacion_cobros')
                ? strip_tags($_POST['mensaje'])
                : plantilla_email(
                    'factura',
                    $this->factura->codigo . ' ' . $this->factura->numero2,
                    $this->empresa->email_config['mail_firma']
                );
            if (trim($this->filter_request('email_copia')) !== '') {
                if ($this->filter_request('cco') !== null) {
                    $mail->addBCC(
                        trim($this->filter_request('email_copia')),
                        $this->cliente_residente->nombre
                    );
                } else {
                    $mail->addCC(
                        trim($this->filter_request('email_copia')),
                        $this->cliente_residente->nombre
                    );
                }
            }
            $mail->msgHTML(nl2br($mail->AltBody));
            $mail->isHTML(true);
            $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);

            if (isset($_FILES['adjunto']) && is_uploaded_file($_FILES['adjunto']['tmp_name'])) {
                $mail->aºddAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }
            if ($this->empresa->mail_connect($mail) && $mail->send()) {
                $this->factura->femail = $this->today();
                $this->factura->save();

                $this->empresa->save_mail($mail);
                $done = true;
            } else {
                $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }

            unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
        } else {
            $this->new_error_msg('Imposible generar el PDF.');
        }
    }

    public function imprimir_documento($tipo_documento)
    {
        $this->template = false;
        $this->crear_documento($tipo_documento);
    }

    public function init()
    {
        $this->envio_masivo = false;
        $this->existe_tesoreria();
        $this->cliente_residente = false;
        if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar') &&
            !mkdir($concurrentDirectory = 'tmp/' . FS_TMP_NAME . 'enviar') &&
            !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    public function is_html($txt)
    {
        return $txt !== strip_tags($txt);
    }

    /**
     *
     */
    private function crearFactura(): void
    {
        $this->crearFacturaDetallada();
    }
}
