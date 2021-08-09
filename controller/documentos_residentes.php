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
    public $numpaginas;
    public $pagado;
    public $pendiente;
    public $tipo_accion;
    public $idprogramacion;
    public $idfactura;

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
            $cliente = new cliente();
            $this->cliente_residente = $cliente->get($cod);
            $residente_informacion = new residentes_informacion();
            $informacion = $residente_informacion->get($cod);
            $residente_edificacion = new residentes_edificaciones();
            $residente = $residente_edificacion->get_by_field('codcliente', $cod);
            $this->cliente_residente->inmueble = $residente[0];
            $this->cliente_residente->informacion = $informacion;
        }
        $info_accion = $this->filter_request('info_accion');
        $tipo_documento = $this->filter_request('tipo_documento');
        $this->tipo_accion = $tipo_documento;
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

    public function crear_documento($tipo_documento)
    {
        $this->archivo = \date('dmYhis') . '.pdf';
        switch ($tipo_documento) {
            case 'informacion_cobros':
                $this->documento = new residentes_pdf('letter', 'portrait');
                $this->documento->pdf->addInfo('Title', 'Pagos Residente ' .
                                        $this->cliente_residente->codcliente);
                $this->documento->pdf->addInfo('Subject', 'Pagos del Residente ' .
                                        $this->cliente_residente->codcliente);
                $this->documento->pdf->addInfo('Author', $this->empresa->nombre);
                $this->documento->pdf->ezSetMargins(10, 10, 10, 10);
                $this->crear_documento_cobros();
                break;
            case 'factura_residente_detallada':
                $this->idprogramacion = $this->filter_request('idprogramacion');
                $this->crearFacturaDetallada();
                break;
            default:
                $this->documento = false;
                break;
        }
    }

    private function datosFactura()
    {
        $datosFacturaCabecera = [];
        $datosFacturaDetalle = [];
        $this->idfactura = $this->filter_request('idfactura');
        if ($this->idfactura != '') {
            $facturas = new factura_cliente();
            $factura = $facturas->get($this->idfactura);
            $datosFacturaCabecera = (array) $factura;
            if ($this->RD_plugin) {
                $ncf = new ncf_ventas();
                $ncfTipo = $ncf->get($this->empresa->id, $factura->numero2);
                //var_dump($ncfTipo);
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

    public function crearFacturaDetallada()
    {
        $customerInfo = (array) $this->cliente_residente;
        $customerInfo['direccion'] = trim($this->cliente_residente->inmueble->codigo_externo()) . ' numero '
            . $this->cliente_residente->inmueble->numero;
        $datosFactura = $this->datosFactura();
        $datosEmpresa = (array) $this->empresa;
        $this->documento = new ResidentesFpdf('L', 'mm', 'A5');
        //var_dump($datosFactura[1]);
        $this->documento->createDocument($datosEmpresa, $datosFactura[0], $datosFactura[1], $customerInfo);
        $this->documento->Output('I');
    }

    public function crearFacturaDetalladaOLD()
    {
        $this->pendiente = $this->pagosFactura(false);
        $this->pagado = $this->pagosFactura(true);
        $linea_actual = 0;
        $pagina = 1;
        $lppag = 28; /// líneas por página
        while ($linea_actual < count($this->pendiente)) {
            /// salto de página
            if ($linea_actual > 0) {
                $this->documento->pdf->ezNewPage();
            }
            $this->pdfGenerarCabecera($this->documento, $this->empresa, $lppag);
            $this->generar_datos_residente($this->documento, 'factura_detallada', $lppag);
            $this->pdfFacturaResumen($this->documento, $this->empresa, $lppag);
            $this->pdfFacturaDetalle($this->documento, $empresa, $lppag, $lppag);

            $this->generar_pdf_lineas(
                $this->documento,
                $this->pendiente,
                $linea_actual,
                $lppag,
                'pendiente'
            );
            //$this->documento->set_y($this->documento->pdf->y - 16);
            $pagina++;
        }

//        $linea_actual2 = 0;
//        $linea_actual2 = $linea_actual;
//        while ($linea_actual2 < count($this->pagado)) {
//            if ($linea_actual2 > 0) {
//                $this->documento->pdf->ezNewPage();
//                $this->pdfGenerarCabecera($this->documento, $this->empresa, $lppag);
//                $this->pdfFacturaResumen($this->documento, $this->empresa, $lppag);
//                $this->generar_datos_residente($this->documento, 'factura_detallada', $lppag);
//            } elseif ($linea_actual2 === 0) {
//                $this->generar_datos_residente($this->documento, 'factura_detallada', $lppag);
//            }
//            $this->generar_pdf_lineas(
//                $this->documento,
//                $this->pagado,
//                $linea_actual2,
//                $lppag,
//                'pagado'
//            );
//            $pagina++;
//        }

        $this->documento->set_y(80);
        if ($this->empresa->pie_factura) {
            $this->documento->pdf->addText(20, 40, 8, fs_fix_html('<b>Generado por:</b> ' .
                $this->user->get_agente_fullname()), 0);
            $this->documento->pdf->addText(
                10,
                20,
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

    public function crear_documento_cobros()
    {
        $this->pendiente = $this->pagosFactura(false);
        $this->pagado = $this->pagosFactura(true);

        $linea_actual = 0;
        $pagina = 1;
        $lppag = 30; /// líneas por página
        while ($linea_actual < count($this->pendiente)) {
            /// salto de página
            if ($linea_actual > 0) {
                $this->documento->pdf->ezNewPage();
            }
            $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
            $this->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            $this->generar_pdf_lineas(
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
                $this->generar_datos_residente($this->documento, 'informe_cobros', $lppag);
            }
            $this->generar_pdf_lineas(
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

    public function enviar_documento($tipo_documento)
    {
        $this->crear_documento($tipo_documento);
        $tipo_doc = $this->generar_tipo_doc($tipo_documento);
        if (file_exists('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo)) {
            $mail = $this->empresa->new_mail();
            $mail->FromName = $this->user->get_agente_fullname();

            if ($_POST['de'] !== $mail->From) {
                $mail->addReplyTo($_POST['de'], $mail->FromName);
            }

            $mail->addAddress($_POST['email'], $this->cliente_residente->nombre);
            if ($_POST['email_copia']) {
                if (isset($_POST['cco'])) {
                    $mail->addBCC($_POST['email_copia'], $this->cliente_residente->nombre);
                } else {
                    $mail->addCC($_POST['email_copia'], $this->cliente_residente->nombre);
                }
            }

            $mail->Subject = $this->empresa->nombre . ': ' . $tipo_doc;

            if ($this->is_html($_POST['mensaje'])) {
                $mail->AltBody = strip_tags($_POST['mensaje']);
                $mail->msgHTML($_POST['mensaje']);
                $mail->isHTML(true);
            } else {
                $mail->Body = $_POST['mensaje'];
            }

            $mail->addAttachment('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
            if (is_uploaded_file($_FILES['adjunto']['tmp_name'])) {
                $mail->addAttachment($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
            }

            if ($this->empresa->mail_connect($mail) && $mail->send()) {
                $this->new_message('Mensaje enviado correctamente.');
                $this->empresa->save_mail($mail);
            } else {
                $this->new_error_msg("Error al enviar el email: " . $mail->ErrorInfo);
            }

            unlink('tmp/' . FS_TMP_NAME . 'enviar/' . $this->archivo);
        } else {
            $this->new_error_msg('Imposible generar el PDF.');
        }
    }

    public function generar_datos_residente(&$pdf_doc, $tipo_documento, &$lppag, $table_width = 560)
    {
        $width_campo1 = 110;
        $tipo_doc = $this->generar_tipo_doc($tipo_documento);
        $tipo_residente = ($this->cliente_residente->informacion->propietario) ? 'Propietario' : 'Inquilino';
        /*
         * Esta es la tabla con los datos del cliente:
         * Informe Cobros           Fecha:
         * Cliente:        Tipo Residente:
         * Dirección:           Teléfonos:
         */
        $pdf_doc->new_table();
        $pdf_doc->add_table_row(
            array(
                'campo1' => "<b>" . $tipo_doc . "</b>",
                'dato1' => '',
                'campo2' => "<b>Fecha Impresión:</b> " . \date('d-m-Y H:i:s')
            )
        );

        $pdf_doc->add_table_row(
            array(
                'campo1' => "<b>Residente:</b>",
                'dato1' => fs_fix_html($this->cliente_residente->nombre),
                'campo2' => "<b>Tipo Residente:</b> " . $tipo_residente
            )
        );

        $row = array(
            'campo1' => "<b>Inmueble:</b>",
            'dato1' => fs_fix_html($this->cliente_residente->inmueble->codigo_externo() .
                ' - ' . $this->cliente_residente->inmueble->numero),
            'campo2' => ''
        );

        if (!$this->cliente_residente) {
            /// nada
        } elseif ($this->cliente_residente->telefono1) {
            $row['campo2'] = "<b>Teléfonos:</b> " . $this->cliente_residente->telefono1;
            if ($this->cliente_residente->telefono2) {
                $row['campo2'] .= "\n" . $this->cliente_residente->telefono2;
                $lppag -= 2;
            }
        } elseif ($this->cliente_residente->telefono2) {
            $row['campo2'] = "<b>Teléfonos:</b> " . $this->cliente_residente->telefono2;
        }
        $pdf_doc->add_table_row($row);

        $pdf_doc->save_table(
            array(
                'cols' => array(
                    'campo1' => array('width' => $width_campo1, 'justification' => 'right'),
                    'dato1' => array('justification' => 'left'),
                    'campo2' => array('justification' => 'right')
                ),
                'showLines' => 0,
                'width' => $table_width,
                'shaded' => 0,
                'fontSize' => 8
            )
        );
        $pdf_doc->pdf->ezText("\n", 10);
    }

    public function generar_pdf_lineas(&$pdf_doc, &$items, &$linea_actual, &$lppag, $tipo, $table_width = 540)
    {
        /// calculamos el número de páginas
        if (!isset($this->numpaginas)) {
            $this->numpaginas = 0;
            $lineas = 0;
            while ($lineas < count($items)) {
                $lppag2 = $lppag;
                $this->verificar_longitud_linea($items, $lineas, $lppag2);
                $lineas += $lppag2;
                $this->numpaginas++;
            }

            if ($this->numpaginas === 0) {
                $this->numpaginas = 1;
            }
        }

        /// leemos las líneas para ver si hay que mostrar mas información
        $this->verificar_longitud_linea($items, $linea_actual, $lppag);

        /*
         * Creamos la tabla con las lineas de pendientes
         */
        $pdf_doc->new_table();
        [$table_header, $array_cols] = $this->generar_pdf_lineas_tablas($tipo);
        $pdf_doc->add_table_header($table_header);

        for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) && ( $linea_actual < count($items)));) {
            $fila = $this->generar_pdf_lineas_fila($tipo, $items, $linea_actual);
            $pdf_doc->add_table_row($fila);
            $linea_actual++;
        }

        $pdf_doc->save_table(
            array(
                'fontSize' => 8,
                'cols' => $array_cols,
                'width' => $table_width,
                'shaded' => 1,
                'shadeCol' => array(0.95, 0.95, 0.95),
                'lineCol' => array(0.3, 0.3, 0.3),
            )
        );
    }
    
    public function generar_pdf_lineas_tablas($tipo)
    {
        $table_header = array(
            'item' => '<b>Pendiente de Pago</b>', 'fecha' => '<b>Fecha</b>',
            'vencimiento' => '<b>Vencimiento</b>', 'importe' => '<b>Monto</b>',
            'descuento' => '<b>Descuento</b>', 'total' => '<b>Total</b>', 'atraso' => '<b>Atraso</b>',
        );
        $array_cols = array(
            'item' => array('justification' => 'left'), 'fecha' => array('justification' => 'center'),
            'vencimiento' => array('justification' => 'center'), 'importe' => array('justification' => 'right'),
            'descuento' => array('justification' => 'right'),
            'total' => array('justification' => 'right'), 'atraso' => array('justification' => 'center')
        );
        if ($tipo === 'pagado') {
            $table_header = array(
                'item' => '<b>Pagos Realizados</b>', 'fecha' => '<b>Fecha</b>',
                'importe' => '<b>Monto</b>', 'f_pago' => '<b>F. Pago</b>'
            );
            $array_cols = array(
                'item' => array('justification' => 'left'), 'fecha' => array('justification' => 'center'),
                'importe' => array('justification' => 'right'), 'f_pago' => array('justification' => 'center')
            );
        }
        
        return array($table_header,$array_cols);
    }
    
    public function generar_pdf_lineas_fila($tipo, $items, $linea_actual)
    {
        $descripcion = fs_fix_html($items[$linea_actual]->descripcion);
        $fila = array(
            'item' => $descripcion,
            'fecha' => $items[$linea_actual]->fecha,
            'vencimiento' => $items[$linea_actual]->vencimiento,
            'importe' => $this->show_precio($items[$linea_actual]->pvpsindto, $this->empresa->coddivisa, true, FS_NF0),
            'descuento' => $this->show_numero($items[$linea_actual]->dtopor) . " %",
            'total' => $this->show_precio($items[$linea_actual]->pvptotal, $this->empresa->coddivisa, true, FS_NF0),
            'atraso' => $items[$linea_actual]->dias_atraso
        );
        if ($tipo === 'pagado') {
            $fila = array(
            'item' => $descripcion,
            'fecha' => $items[$linea_actual]->fecha,
            'importe' => $this->show_precio($items[$linea_actual]->pvptotal, $this->empresa->coddivisa, true, FS_NF0),
            'f_pago' => $items[$linea_actual]->f_pago
            );
        }
        return $fila;
    }

    public function verificar_longitud_linea($items, &$lineas, &$lppag2)
    {
        foreach ($items as $i => $lin) {
            if ($i >= $lineas && $i < $lineas + $lppag2) {
                $linea_size = 1;
                $len = mb_strlen($lin->descripcion);
                while ($len > 85) {
                    $len -= 85;
                    $linea_size += 0.5;
                }
                $aux = explode("\n", $lin->descripcion);
                if (count($aux) > 1) {
                    $linea_size += 0.5 * ( count($aux) - 1);
                }
                if ($linea_size > 1) {
                    $lppag2 -= $linea_size - 1;
                }
            }
        }
    }

    public function pdfGenerarCabecera(&$pdf_doc, &$empresa, &$lppag)
    {
        /// ¿Añadimos el logo?
        if ($pdf_doc->logo !== false) {
            if (function_exists('imagecreatefromstring')) {
                $lppag -= 4; /// si metemos el logo, caben menos líneas
                $pdf_doc_LOGO_X = 20;
                $pdf_doc_LOGO_Y = 320;
                $tamanyo = $pdf_doc->calcular_tamanyo_logo();
                if (strtolower(substr($pdf_doc->logo, -4)) === '.png') {
                    $pdf_doc->pdf->addPngFromFile($pdf_doc->logo, $pdf_doc_LOGO_X, $pdf_doc_LOGO_Y, $tamanyo[0], $tamanyo[1]);
                } elseif (function_exists('imagepng')) {
                    /**
                     * La librería ezpdf tiene problemas al redimensionar jpegs,
                     * así que hacemos la conversión a png para evitar estos problemas.
                     */
                    if (imagepng(imagecreatefromstring(file_get_contents($pdf_doc->logo)), FS_MYDOCS
                        . 'images/logo.png')) {
                        $pdf_doc->pdf->addPngFromFile(
                            FS_MYDOCS . 'images/logo.png',
                            $pdf_doc_LOGO_X,
                            $pdf_doc_LOGO_Y,
                            $tamanyo[0],
                            $tamanyo[1]
                        );
                    } else {
                        $pdf_doc->pdf->addJpegFromFile($pdf_doc->logo, $pdf_doc_LOGO_X, $pdf_doc_LOGO_Y, $tamanyo[0], $tamanyo[1]);
                    }
                } else {
                    $pdf_doc->pdf->addJpegFromFile($pdf_doc->logo, $pdf_doc_LOGO_X, $pdf_doc_LOGO_Y, $tamanyo[0], $tamanyo[1]);
                }
                $pdf_doc->set_y(400);
                $pdf_doc->pdf->ez['leftMargin'] = 120;
                $pdf_doc->pdf->ezText(
                    "<b>" . fs_fix_html($empresa->nombre) . "</b>",
                    12,
                    array('justification' => 'left')
                );
                $pdf_doc->pdf->ezText(FS_CIFNIF . ": " . $empresa->cifnif, 8, array('justification' => 'left'));

                $direccion = $empresa->direccion . "\n";
                if ($empresa->apartado) {
                    $direccion .= ucfirst(FS_APARTADO) . ': ' . $empresa->apartado . ' - ';
                }

                if ($empresa->codpostal) {
                    $direccion .= 'CP: ' . $empresa->codpostal . ' - ';
                }

                if ($empresa->ciudad) {
                    $direccion .= $empresa->ciudad . ' - ';
                }

                if ($empresa->provincia) {
                    $direccion .= '(' . $empresa->provincia . ')';
                }

                if ($empresa->telefono) {
                    $direccion .= "\nTeléfono: " . $empresa->telefono . ' - '.$pdf_doc_LOGO_X."/".$pdf_doc_LOGO_Y;
                }

                $pdf_doc->pdf->ezText(fs_fix_html($direccion) . "\n", 8, array('justification' => 'left'));
                $pdf_doc->set_y($pdf_doc_LOGO_Y);

                $pdf_doc->pdf->ez['leftMargin'] = 10;
            } else {
                die('ERROR: no se encuentra la función imagecreatefromstring(). '
                    . 'Y por tanto no se puede usar el logotipo en los documentos.');
            }
        } else {
            $pdf_doc->pdf->ezText(
                "<b>" . fs_fix_html($empresa->nombre) . "</b>",
                12,
                array('justification' => 'left')
            );
            $pdf_doc->pdf->ezText(FS_CIFNIF . ": " . $empresa->cifnif, 8, array('justification' => 'left'));

            $direccion = $empresa->direccion;
            if ($empresa->apartado) {
                $direccion .= ' - ' . ucfirst(FS_APARTADO) . ': ' . $empresa->apartado;
            }

            if ($empresa->codpostal) {
                $direccion .= ' - CP: ' . $empresa->codpostal;
            }

            if ($empresa->ciudad) {
                $direccion .= ' - ' . $empresa->ciudad;
            }

            if ($empresa->provincia) {
                $direccion .= ' (' . $empresa->provincia . ')';
            }

            if ($empresa->telefono) {
                $direccion .= ' - Teléfono: ' . $empresa->telefono;
            }

            $pdf_doc->pdf->ezText(fs_fix_html($direccion), 8, array('justification' => 'left'));
        }
    }

    public function pdfFacturaResumen(&$pdf_doc, &$empresa, &$lppag)
    {
        $pdf_doc->set_y(420);
        $pdf_doc->pdf->ez['leftMargin'] = 440;
        $pdf_doc->pdf->ez['rightMargin'] = 10;

        $facturas = new factura_cliente();
        $clientes = new cliente();
        $cliente = $clientes->get($this->filter_request('codcliente'));
        $factura = $facturas->get($this->filter_request('idfactura'));
        $tipo_factura = '';
        if (isset($factura->ncf_tipo)) {
            $tipo_factura = $factura->ncf_tipo;
        }

        $pdf_doc->new_table();
        $pdf_doc->add_table_row(
            array(
                'campos' => "<b>" . ucfirst(FS_FACTURA) . ":</b>\n <b>".FS_NUMERO2.":</b>\n<b>Fecha:</b>\n<b>" . 'F. Pago' . ":</b>",
                'factura' => $factura->codigo . "\n" . $factura->numero2 . "\n" . $factura->fecha . "\n" . $factura->codpago,
            )
        );
        $pdf_doc->save_table(
            array(
                'cols' => array(
                    'campos' => array('justification' => 'right', 'width' => 120),
                    'factura' => array('justification' => 'left')
                ),
                'showLines' => 0,
                'fontSize' => 9,
                'width' => 320
            )
        );

        $pdf_doc->set_y(240);
        $pdf_doc->pdf->ez['leftMargin'] = 10;
    }

    public function pdfFacturaDetalle(&$pdf_doc, &$empresa, &$lppag,  &$linea_actual)
    {
        $facturas = new factura_cliente();
        $clientes = new cliente();
        $cliente = $clientes->get($this->filter_request('codcliente'));
        $factura = $facturas->get($this->filter_request('idfactura'));
        $lineas = $factura->get_lineas();

        $pdf_doc->new_table();
        $table_header = array(
            'descripcion' => '<b>Ref. + Descripción</b>',
            'cantidad' => '<b>Cant.</b>',
            'pvp' => '<b>Precio</b>',
            'dto' => '<b>Dto.</b>',
            'importe' => '<b>Importe</b>'
        );

        $pdf_doc->add_table_header($table_header);

        for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) && ( $linea_actual < count($lineas)));) {
            $descripcion = fs_fix_html($lineas[$linea_actual]->descripcion);
            if (!is_null($lineas[$linea_actual]->referencia) && $this->impresion['print_ref']) {
                $descripcion = '<b>' . $lineas[$linea_actual]->referencia . '</b> ' . $descripcion;
            }

            /// ¿El articulo tiene trazabilidad?
            //$descripcion .= $this->generar_trazabilidad($lineas[$linea_actual]);

            $due_lineas = $this->fbase_calc_desc_due([$lineas[$linea_actual]->dtopor, $lineas[$linea_actual]->dtopor2, $lineas[$linea_actual]->dtopor3, $lineas[$linea_actual]->dtopor4]);

            $fila = array(
                'descripcion' => $descripcion,
                'cantidad' => $this->show_numero($lineas[$linea_actual]->cantidad, 0),
                'pvp' => $this->show_precio($lineas[$linea_actual]->pvpunitario, $factura->coddivisa, true, FS_NF0_ART),
                'dto' => $this->show_numero($due_lineas) . " %",
                'importe' => $this->show_precio($lineas[$linea_actual]->pvptotal, $this->documento->coddivisa)
            );

            if ($lineas[$linea_actual]->dtopor == 0) {
                $fila['dto'] = '';
            }

            if (!$lineas[$linea_actual]->mostrar_cantidad) {
                $fila['cantidad'] = '';
            }

            if (!$lineas[$linea_actual]->mostrar_precio) {
                $fila['pvp'] = '';
                $fila['dto'] = '';
                $fila['importe'] = '';
            }

            $pdf_doc->add_table_row($fila);
            $linea_actual++;
        }

        $pdf_doc->save_table(
            array(
                'fontSize' => 8,
                'cols' => array(
                    'cantidad' => array('justification' => 'right'),
                    'pvp' => array('justification' => 'right'),
                    'dto' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    'importe' => array('justification' => 'right')
                ),
                'width' => 540,
                'shaded' => 1,
                'shadeCol' => array(0.95, 0.95, 0.95),
                'lineCol' => array(0.3, 0.3, 0.3),
            )
        );

        /// ¿Última página?
        if ($linea_actual == count($lineas) && $this->documento->observaciones != '') {
            $pdf_doc->pdf->ezText("\n" . fs_fix_html($this->documento->observaciones), 9);
        }
    }

    public function generar_tipo_doc($tipo_documento)
    {
        return ucfirst(str_replace('_', ' ', $tipo_documento));
    }

    public function imprimir_documento($tipo_documento)
    {
        $this->template = false;
        $this->crear_documento($tipo_documento);
    }

    public function init()
    {
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
}
