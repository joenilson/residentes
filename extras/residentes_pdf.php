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
require_once 'plugins/residentes/extras/residentes_controller.php';

/**
 * Description of residentes_pdf
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_pdf extends fs_pdf
{
    const LOGO_X = 40;
    const LOGO_Y = 700;

    public $cliente_residente;
    public $numpaginas;

    public function calcular_tamanyo_logo()
    {
        $tamanyo = $size = getimagesize($this->logo);
        if ($size[0] > 200) {
            $tamanyo[0] = 200;
            $tamanyo[1] = $tamanyo[1] * $tamanyo[0] / $size[0];
            $size[0] = $tamanyo[0];
            $size[1] = $tamanyo[1];
        }

        if ($size[1] > 80) {
            $tamanyo[1] = 80;
            $tamanyo[0] = $tamanyo[0] * $tamanyo[1] / $size[1];
        }

        return $tamanyo;
    }

    public function generar_pdf_cabecera(&$empresa, &$lppag)
    {
        /// ¿Añadimos el logo?
        if ($this->logo !== false) {
            if (function_exists('imagecreatefromstring')) {
                $lppag -= 2; /// si metemos el logo, caben menos líneas

                $tamanyo = $this->calcular_tamanyo_logo();
                if (strtolower(substr($this->logo, -4)) === '.png') {
                    $this->pdf->addPngFromFile($this->logo, self::LOGO_X, self::LOGO_Y, $tamanyo[0], $tamanyo[1]);
                } elseif (function_exists('imagepng')) {
                    /**
                     * La librería ezpdf tiene problemas al redimensionar jpegs,
                     * así que hacemos la conversión a png para evitar estos problemas.
                     */
                    if (imagepng(imagecreatefromstring(file_get_contents($this->logo)), FS_MYDOCS
                        . 'images/logo.png')) {
                        $this->pdf->addPngFromFile(
                            FS_MYDOCS . 'images/logo.png',
                            self::LOGO_X,
                            self::LOGO_Y,
                            $tamanyo[0],
                            $tamanyo[1]
                        );
                    } else {
                        $this->pdf->addJpegFromFile($this->logo, self::LOGO_X, self::LOGO_Y, $tamanyo[0], $tamanyo[1]);
                    }
                } else {
                    $this->pdf->addJpegFromFile($this->logo, self::LOGO_X, self::LOGO_Y, $tamanyo[0], $tamanyo[1]);
                }

                $this->pdf->ez['rightMargin'] = 40;
                $this->pdf->ezText(
                    "<b>" . fs_fix_html($empresa->nombre) . "</b>",
                    12,
                    array('justification' => 'right')
                );
                $this->pdf->ezText(FS_CIFNIF . ": " . $empresa->cifnif, 8, array('justification' => 'right'));

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
                    $direccion .= "\nTeléfono: " . $empresa->telefono;
                }

                $this->pdf->ezText(fs_fix_html($direccion) . "\n", 8, array('justification' => 'right'));
                $this->set_y(self::LOGO_Y + 10);
            } else {
                die('ERROR: no se encuentra la función imagecreatefromstring(). '
                    . 'Y por tanto no se puede usar el logotipo en los documentos.');
            }
        } else {
            $this->pdf->ezText(
                "<b>" . fs_fix_html($empresa->nombre) . "</b>",
                12,
                array('justification' => 'center')
            );
            $this->pdf->ezText(FS_CIFNIF . ": " . $empresa->cifnif, 8, array('justification' => 'center'));

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

            $this->pdf->ezText(fs_fix_html($direccion), 8, array('justification' => 'center'));
        }
    }

    public function generar_datos_residente(&$pdf_doc, $tipo_documento, &$lppag, $table_width = 560)
    {
        $residente_controller = new residentes_controller();
        $width_campo1 = 110;
        $tipo_doc = $residente_controller->generar_tipo_doc($tipo_documento);
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
        $residentes_controller = new residentes_controller();
        $descripcion = fs_fix_html($items[$linea_actual]->descripcion);
        $fila = array(
            'item' => $descripcion,
            'fecha' => $items[$linea_actual]->fecha,
            'vencimiento' => $items[$linea_actual]->vencimiento,
            'importe' => $residentes_controller->show_precio(
                $items[$linea_actual]->pvpsindto,
                $residentes_controller->empresa->coddivisa,
                true,
                FS_NF0
            ),
            'descuento' => $residentes_controller->show_numero($items[$linea_actual]->dtopor) . " %",
            'total' => $residentes_controller->show_precio(
                $items[$linea_actual]->pvptotal,
                $residentes_controller->empresa->coddivisa,
                true,
                FS_NF0
            ),
            'atraso' => $items[$linea_actual]->dias_atraso
        );
        if ($tipo === 'pagado') {
            $fila = array(
                'item' => $descripcion,
                'fecha' => $items[$linea_actual]->fecha,
                'importe' => $residentes_controller->show_precio(
                    $items[$linea_actual]->pvptotal,
                    $residentes_controller->empresa->coddivisa,
                    true,
                    FS_NF0
                ),
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
}