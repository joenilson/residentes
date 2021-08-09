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
/**
 * Description of residentes_pdf
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_pdf extends fs_pdf
{
    const LOGO_X = 40;
    const LOGO_Y = 700;

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
}