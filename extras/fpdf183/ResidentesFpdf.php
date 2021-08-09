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

//require_once 'plugins/residentes/extras/fpdf183/fpdf.php';
include 'fpdf.php';
include 'NumberToLetterConverter.php';
//include 'exfpdf.php';
//include 'EasyTable.php';

class ResidentesFpdf extends FPDF
{
    public $max_lines;
    public $font;
    public $logo;

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4', $font = 'Verdana')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetFont($font, '', 10);
        $this->setMaxLines($size);
        $this->SetAutoPagebreak(false);
        $this->SetMargins(0, 0, 0);
        $this->font = $font;
    }

    public function setMaxLines($pageSize)
    {
        switch ($pageSize) {
            case 'A4':
                $this->max_lines = 35;
                break;
            case 'letter':
            default:
                $this->max_lines = 30;
                break;
            case 'A5':
                $this->max_lines = 7;
                break;
        }
    }

    public function setPdfLogo()
    {
        if (!file_exists(FS_MYDOCS . 'images') && !mkdir($concurrentDirectory = FS_MYDOCS . 'images', 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        /**
         * Antes se guardaba el logo en el temporal.
         * Mala decisión, lo movemos.
         */
        if (file_exists('tmp/' . FS_TMP_NAME . 'logo.png')) {
            rename('tmp/' . FS_TMP_NAME . 'logo.png', FS_MYDOCS . 'images/logo.png');
        } elseif (file_exists('tmp/' . FS_TMP_NAME . 'logo.jpg')) {
            rename('tmp/' . FS_TMP_NAME . 'logo.jpg', FS_MYDOCS . 'images/logo.jpg');
        }

        $this->logo = false;
        if (file_exists(FS_MYDOCS . 'images/logo.png')) {
            $this->logo = FS_MYDOCS . 'images/logo.png';
        } elseif (file_exists(FS_MYDOCS . 'images/logo.jpg')) {
            $this->logo = FS_MYDOCS . 'images/logo.jpg';
        }
        if ($this->logo) {
            $this->Image($this->logo, 5, 5, 30);
        }
    }

    public function setDocumentHeaderInfo($documentHeader)
    {
        $this->SetFont($this->font, "B", 8);
        $this->SetLineWidth(0.1);
        $localHeight = 8;
        $localTopLine = 8;
        $localSpaceBetweenLine = 5;
        $localLine = 1;
        $this->SetXY(140, $localTopLine);
        $this->Cell(60, 8, $documentHeader['codigo'], 0, 0, 'C');
        $localHeight += 4;
        $this->SetXY(140, $localTopLine+($localSpaceBetweenLine*$localLine));
        $this->Cell(60, 8, $documentHeader['numero2'], 0, 0, 'C');
        $localHeight += 4;
        $localLine++;
        $this->SetXY(140, $localTopLine+($localSpaceBetweenLine*$localLine));
        if (isset($documentHeader['tiponcf'])) {
            $this->Cell(60, 8, $documentHeader['tiponcf'], 0, 0, 'C');
            $localHeight += 4;
            $localLine++;
            $this->SetXY(140, $localTopLine+($localSpaceBetweenLine*$localLine));
            if (substr($documentHeader['numero2'], -10, 2) !== '02') {
                $this->Cell(60, 8, "Valido hasta el: ".$documentHeader['vencimientoncf'], 0, 0, 'C');
                $localHeight += 4;
                $localLine++;
                $this->SetXY(140, $localTopLine*$localSpaceBetweenLine*$localLine);
            }
        }
        $this->Cell(60, 8, "Fecha: " . $documentHeader['fecha'], 0, 0, 'C');
        $localHeight += 4;
        $this->Rect(140, $localTopLine, 60, $localHeight, "D");
    }

    public function setDocumentFooterInfo($documentFooter)
    {
        $x = 10;
        $y = 104;
        $this->SetLineWidth(0.1);
        $this->Rect($x, $y, 95, 6, "D");
        // Subtotal, Impuestos y Total
        $number_to_words = new NumberToLetterConverter();
        $words = $number_to_words->to_word($documentFooter['neto'], 'DOP');
        $subtotal = "Son: " . $words;
        $this->SetFont($this->font, '', 8);
        $this->SetXY($x, $y);
        $this->Cell(95, 8, $subtotal, 0, 0, 'L');
        // el total
//
//        $this->SetXY($x, $y+25);
//        $this->Cell(24, 6, number_format($documentFooter['total'], FS_NF0, FS_NF1, FS_NF2), 0, 0, 'R');

        // trait vertical cadre totaux, 8 de hauteur -> 213 + 8 = 221
        $this->SetFont($this->font, 'B', 8);
        $this->Rect($x, $y+6, 95, 6, "D");
        // Forma de Pago
        $this->SetXY($x, $y+6);
        $this->Cell(38, 5, "Forma de Pago:", 0, 0, 'L');
        $this->Cell(55, 5, utf8_decode($documentFooter['codpago']), 0, 0, 'L');
        // Fecha de Vencimiento
        //$date_ech = date_format($documentFooter['vencimiento'], 'd/m/Y');

        $this->Rect($x, $y+12, 95, 6, "D");
        $this->SetXY($x, $y+12);
        $this->Cell(38, 5, "Fecha de Vencimiento:", 0, 0, 'R');
        $this->Cell(38, 5, $documentFooter['vencimiento'], 0, 0, 'L');

        $this->SetFont($this->font, "BU", 8);
        $this->SetXY($x, $y+30);
        $this->Cell($this->GetStringWidth("Observaciones"), 0, "Observaciones", 0, "L");
        $this->SetFont($this->font, "", 8);
        $this->SetXY($x, $y+35);
        $this->MultiCell(190, 4, utf8_decode($documentFooter['observaciones']), 0, "L");
        //$y += 5;
        $this->setDocumentFooterTotalsBase($x, $y);
        $this->setDocumentFooterTotalsData($documentFooter, $x, $y);
    }

    public function setDocumentFooterCompanyText($companyText = 'Por definir')
    {
        $this->SetLineWidth(0.1);
        $this->Rect(5, 260, 200, 6, "D");
        $this->SetXY(1, 260);
        $this->SetFont($this->font, '', 7);
        $this->Cell($this->GetPageWidth(), 7, $companyText, 0, 0, 'C');
    }

    public function setDocumentFooterTotalsBase($x, $y)
    {
        $x += 130;
        $this->SetLineWidth(0.1);
        $this->Rect($x, $y, 60, (5*5), "D");
//        // Lineas Verticales
//        $this->Line(147, 221, 147, 245);
//        $this->Line(164, 221, 164, 245);
//        $this->Line(181, 221, 181, 245);
//        // Lineas Horizontales
//        $this->Line(130, 227, 205, 227);
//        $this->Line(130, 233, 205, 233);
//        $this->Line(130, 239, 205, 239);
        // La tabla de información de subototal y total
        $this->SetFont($this->font, 'B', 8);
        $this->SetXY($x, $y);
        $this->Cell(30, 6, "Importe", 0, 0, 'R');
        $this->SetXY($x, $y+5);
        $this->Cell(30, 6, "Descuento", 0, 0, 'R');
        $this->SetXY($x, $y+10);
        $this->Cell(30, 6, "Subtotal", 0, 0, 'R');
        $this->SetXY($x, $y+15);
        $this->Cell(30, 6, FS_IVA, 0, 0, 'R');
        $this->SetXY($x, $y+20);
        $this->Cell(30, 6, "Total", 0, 0, 'R');
    }

    public function setDocumentFooterTotalsData($documentFooter, $x, $y)
    {
        $x += 160;
        $this->SetXY($x, $y);
        //var_dump($documentFooter);
        $this->Cell(30, 6, number_format($documentFooter['total_antes_descuento'], FS_NF0, FS_NF1, FS_NF2), 0, 0, 'R');
        $taux = $documentFooter['neto'];

        $totalDescuento = number_format($documentFooter['total_descuento'], FS_NF0, FS_NF1, FS_NF2);
        $this->SetXY($x, $y+5);
        $this->Cell(30, 6, $totalDescuento, 0, 0, 'R');

        $col_ht = $documentFooter['totaliva'];
        $col_tva = $col_ht - ($col_ht * (1-($taux/100)));
        $totalImpuesto = number_format($col_tva, FS_NF0, FS_NF1, FS_NF2);
        $totalNeto = number_format($documentFooter['neto'], FS_NF0, FS_NF1, FS_NF2);
        $this->SetXY($x, $y+10);
        $this->Cell(30, 6, $totalNeto, 0, 0, 'R');
        $this->SetXY($x, $y+15);
        $this->Cell(30, 6, $totalImpuesto, 0, 0, 'R');

        $col_ttc = $documentFooter['total'];
        $totalGeneral = number_format($col_ttc, 2, ',', ' ');
        $this->SetXY($x, $y+20);
        $this->Cell(30, 6, $totalGeneral, 0, 0, 'R');
    }

    public function setPdfCustomerHeader($x, $y, $tipoDocumentoFiscal)
    {
        //var_dump($customerInfo);
        $this->SetFont($this->font, 'B', 8);
        $this->SetXY($x, $y);
        $this->Cell(25, 8, utf8_decode("Residente:"), 0, 0, 'R');
        $this->SetXY($x+100, $y);
        $this->Cell(25, 8, utf8_decode($tipoDocumentoFiscal.":"), 0, 0, 'R');
        $y += 4;
        $this->SetXY($x, $y);
        $this->Cell(25, 8, utf8_decode("Dirección:"), 0, 0, 'R');
        $this->SetXY($x+100, $y);
        $this->Cell(25, 8, utf8_decode("Teléfono:"), 0, 0, 'R');
        $y += 4;
        $this->SetXY($x, $y);
    }

    public function setPdfCustomerInfo($customerInfo)
    {
        //var_dump($customerInfo);
        $x = 30;
        $y = 40;
        $this->Line(10, $y, $this->GetPageWidth() - 10, $y);
        $this->setPdfCustomerHeader(5, $y, $customerInfo['tipoidfiscal']);
        $this->SetFont($this->font, '', 8);
        $this->SetXY($x, $y);
        $this->Cell(100, 8, utf8_decode($customerInfo['nombre']), 0, 0, '');
        $this->Cell(100, 8, utf8_decode($customerInfo['cifnif']), 0, 0, '');
        $y += 4;
        $this->SetXY($x, $y);
        $this->Cell(100, 8, utf8_decode($customerInfo['direccion']), 0, 0, '');
        $this->Cell(100, 8, $customerInfo['telefono1'], 0, 0, '');
        $y += 4;
        $y += 4;
        $this->SetXY($x, $y);
        //$y += 4;
        $this->Line(10, $y, $this->GetPageWidth() - 10, $y);
    }

    public function setPdfSubHeaderCompany($pdf)
    {
        $pdf->SetLineWidth(0.1);
        $pdf->Rect(5, 95, 200, 118, "D");
    }

    public function setDocumentLinesHeader($x, $y)
    {
        $this->SetLineWidth(0.1);
        $this->Rect(10, $y, 190, ($this->max_lines*7), "D");
        $this->Line(10, $y+5, 200, $y+5);
        //Lineas Verticales
        $arrayLineas = [105, 120, 140, 160, 180];
        foreach ($arrayLineas as $lineaX) {
                $this->Line($lineaX, $y, $lineaX, $y+($this->max_lines*7));
        }
        $arrayDatosLineas = ["Descripción", "Cantidad", "Precio", "Descuento", FS_IVA, "Importe"];
        $arrayAnchoLineas = [95, 15, 20, 20, 20, 20];
        //Cabeceras
        $this->SetXY($x, $y);
        $this->SetFont($this->font, 'B', 8);
        $cantidadLineas = count($arrayDatosLineas);
        for ($i = 0; $i < $cantidadLineas; $i++) {
            $this->Cell($arrayAnchoLineas[$i], 5, utf8_decode($arrayDatosLineas[$i]), 0, 0, 'C');
            if (isset($arrayLineas[$i])) {
                $this->SetXY($arrayLineas[$i], $y);
            }
        }
    }

    public function setDocumentLinesInfo($documentLines, $pageNumber, $numberOfPages)
    {
        $x = 10;
        $y = 55;
        $this->setDocumentLinesHeader($x, $y);
        $this->SetFont($this->font, '', 8);
        //We calculate where the FOR function must start
        $i = ($pageNumber * $this->max_lines) - ($this->max_lines);
        $linesSubtotal = 0;
        for ($i; $i < ($pageNumber * $this->max_lines); $i++) {
            if (isset($documentLines[$i])) {
                $this->SetXY($x, $y+5);
                $line = $documentLines[$i];
                $linesSubtotal += $line['pvptotal'];
                $this->Cell(95, 5, utf8_decode($line['descripcion']), 0, 0, 'L');
                // qte
                $this->SetXY(105, $y+6);
                $this->Cell(15, 5, strrev(wordwrap(strrev($line['cantidad']), 3, ' ', true)), 0, 0, 'R');
                // PU
                $numero_formateado = number_format($line['pvpunitario'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(120, $y+6);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                //Descuento
                $numero_formateado = number_format(($line['pvpsindto'] - $line['pvptotal']), FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(140, $y+6);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                // Impuesto
                $numero_formateado = number_format($line['iva'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(160, $y+6);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                // total
                $numero_formateado = number_format($line['pvptotal'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(180, $y+6);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                //$this->Line($x, $y+13, 200, $y+13);
                $y += 6;
            }
        }
        $this->setDocumentLinesSubtotal($linesSubtotal, $pageNumber, $numberOfPages, $x, ($y+($this->max_lines)));
        $this->setDocumentPageNumber($pageNumber, $numberOfPages);
    }

    public function setDocumentLinesSubtotal($linesSubtotal, $pageNumber, $numberOfPages, $x, $y)
    {
        if ($pageNumber !== $numberOfPages) {
            $x += 130;
            $this->SetLineWidth(0.1);
            $this->Rect($x, $y, 60, 6, "D");
            $this->SetFont($this->font, '', 8);
            $this->SetXY($x, $y);
            $this->Cell(30, 6, utf8_decode("Subtotal página: "), 0, 0, 'R');
            $this->SetXY($x+30, $y);
            $this->Cell(30, 6, number_format($linesSubtotal, FS_NF0, FS_NF1, FS_NF2), 0, 0, 'R');
        }
    }

    public function setDocumentPageNumber($pageNumber, $numberOfPages)
    {
        $x = $this->GetPageHeight();
        $y = $this->GetPageWidth()-70;
        //print_r($x);
        $this->SetXY($x, $y);
        $this->SetFont($this->font, 'B', 7);
        $this->Cell(52, 5, utf8_decode("Página " . $pageNumber . " de " . $numberOfPages), 0, 0, 'R');
    }

    public function setCompanyInfo($companyInfo)
    {
        $y1 = 8;
        $x1 = ($this->logo) ? 35 : 1;
        $localLineHeight = 4;
        //var_dump($companyInfo);
        $this->SetXY($x1, $y1);
        $this->SetFont($this->font, 'B', 9);
        $this->Cell($this->GetPageWidth(), 5, utf8_decode($companyInfo['nombre']), 0, 0, 'L');
        $this->SetFont($this->font, '', 7);
        $this->SetXY($x1, $y1 + $localLineHeight);
        $this->Cell($this->GetPageWidth(), 5, utf8_decode($companyInfo['cifnif']), 0, 0, 'L');
        $localLineHeight += 4;
        $this->SetXY($x1, $y1 + $localLineHeight);
        $this->Cell($this->GetPageWidth(), 5, utf8_decode($companyInfo['direccion']), 0, 0, 'L');
        $localLineHeight += 4;
        $this->SetXY($x1, $y1 + $localLineHeight);
        $this->Cell($this->GetPageWidth(), 5, utf8_decode($companyInfo['telefono']), 0, 0, 'L');
        $localLineHeight += 4;
        $this->SetXY($x1, $y1 + $localLineHeight);
        $this->Cell($this->GetPageWidth(), 5, utf8_decode($companyInfo['web']) . ' - ' .
                        utf8_decode($companyInfo['email']), 0, 0, 'L');
    }

    public function createDocument($companyInformation, $documentHeader, $documentLines, $customerInfo)
    {
        $numberOfPages = (int) ceil(count($documentLines)/$this->max_lines);
//        var_dump($this->max_lines);
//        var_dump(count($documentLines));
//        var_dump($numberOfPages);
        for ($pageNumber=0; $pageNumber < $numberOfPages; $pageNumber++) {
            $this->AddPage();
            $this->setPdfLogo();
            $this->setCompanyInfo($companyInformation);
            $this->setPdfCustomerInfo($customerInfo);
            $this->setDocumentHeaderInfo($documentHeader);
            $this->setDocumentLinesInfo($documentLines, $pageNumber+1, $numberOfPages);
            if ($pageNumber === ($numberOfPages-1)) {
                $this->setDocumentFooterInfo($documentHeader);
                $this->setDocumentFooterCompanyText($companyInformation['pie_factura']);
            }
        }
    }
}

//class PDF_MC_Table extends FPDF
//{
//    public $datoscab;
//    public $widths;
//    public $aligns;
//    public $colores;
//    public $extgstates = array();
//    public $angle=0;
//    public $lineaactual = 0;
//    public $piepagina = false;
//    public $logo;
//    //Adición de grupos de páginas
//    //Origen: http://fpdf.de/downloads/addons/57/
//    public $NewPageGroup;   // variable indicating whether a new group was requested
//    public $PageGroups;     // variable containing the number of pages of the groups
//    public $CurrPageGroup;  // variable containing the alias of the current page group
//
//    /**
//     * Ver marca de agua en la factura
//     * @var string 1|0
//     */
//    public $fdf_vermarcaagua;
//    public function Setdatoscab($v)
//    {
//        //Set the array
//        $this->datoscab=$v;
//    }
//
//    public function SetWidths($w)
//    {
//        //Set the array
//        $this->widths=$w;
//    }
//
//    public function SetAligns($a)
//    {
//        //Set the array
//        $this->aligns=$a;
//    }
//
//    public function SetColors($a)
//    {
//        $contador = count($a);
//        for ($i=0; $i<$contador; $i++)
//        {
//            $datos = explode('|', $a[$i]);
//            $this->colores[$i][0] = $datos[0];
//            $this->colores[$i][1] = $datos[1];
//            $this->colores[$i][2] = $datos[2];
//        }
//    }
//
//    public function SetColorRelleno($a)
//    {
//        switch ($a) {
//            case 'rojo':
//                $this->SetFillColor(253, 120, 120);
//                break;
//            case 'verde':
//                $this->SetFillColor(120, 253, 165);
//                break;
//            case 'azul':
//                $this->SetFillColor(120, 158, 253);
//                break;
//            case 'blanco':
//                $this->SetFillColor(255, 255, 255);
//                break;
//            default:
//                if (substr($a, 0, 1)==='#') {
//                    $rgb = $this->htmlColor2Hex($a);
//                    $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
//                } else {
//                    $this->SetFillColor(192);
//                }
//                break;
//        }
//    }
//
//    //Cabecera de pagina
//    public function Header()
//    {
//        // Datos de la empresa
//        $direccion = $this->fde_FS_CIFNIF . ": " . utf8_decode($this->fde_cifnif) . "\n" . $this->fde_direccion;
//
//        if ($this->fde_codpostal && $this->fde_ciudad) {
//            $direccion .= ' - ' .$this->fde_codpostal . ' - ' . $this->fde_ciudad;
//        } else {
//            if ($this->fde_codpostal) {
//                $direccion .= "\n" . $this->fde_codpostal;
//            }
//            if ($this->fde_ciudad) {
//                $direccion .= "\n" . $this->fde_ciudad;
//            }
//        }
//        if ($this->fde_provincia) {
//            $direccion .= ' (' . $this->fde_provincia . ')';
//        }
//        if ($this->fde_telefono) {
//            $direccion .= "\n" . $this->fde_telefono;
//        }
//        if ($this->fde_fax) {
//            $direccion .= " - " . $this->fde_fax;
//        }
//
//        $this->addSociete(utf8_decode($this->fde_nombre), utf8_decode($direccion), utf8_decode($this->fde_email), utf8_decode($this->fde_web));
//
//        //Logotipo
//        $this->logo = false;
//        if ($this->fdf_verlogotipo == '1') {
//            if (!file_exists(FS_MYDOCS.'images')) {
//                @mkdir(FS_MYDOCS.'images', 0777, true);
//            }
//
//            /**
//             * Antes se guardaba el logo en el temporal.
//             * Mala decisión, lo movemos.
//             */
//            if (file_exists('tmp/'.FS_TMP_NAME.'logo.png')) {
//                rename('tmp/'.FS_TMP_NAME.'logo.png', FS_MYDOCS.'images/logo.png');
//            } elseif (file_exists('tmp/'.FS_TMP_NAME.'logo.jpg')) {
//                rename('tmp/'.FS_TMP_NAME.'logo.jpg', FS_MYDOCS.'images/logo.jpg');
//            }
//
//
//            if (file_exists(FS_MYDOCS.'images/logo.png')) {
//                $this->logo = FS_MYDOCS.'images/logo.png';
//            } elseif (file_exists(FS_MYDOCS.'images/logo.jpg')) {
//                $this->logo = FS_MYDOCS.'images/logo.jpg';
//            }
//            if($this->logo !== false){
//                $this->Image($this->logo, $this->fdf_Xlogotipo, $this->fdf_Ylogotipo, 35);
//                $this->Ln(0);
//            }
//        }
//
//        //Marca de agua
//        if ($this->fdf_vermarcaagua == '1' && $this->logo !== false) {
//            // set alpha to semi-transparency
//            $this->SetAlpha(0.05);
//            // draw png image
//            $this->Image($this->logo, $this->fdf_Xmarcaagua, $this->fdf_Ymarcaagua, 160);
//            // restore full opacity
//            $this->SetAlpha(1);
//            $this->Ln(0);
//        }
//
//        if ($this->fdf_verSelloPagado == '1') {
//            $this->sello_pagado = false;
//            if (file_exists(FS_PATH.'plugins/republica_dominicana/extras/images/sello_pagado.png')) {
//                $this->sello_pagado = FS_PATH.'plugins/republica_dominicana/extras/images/sello_pagado.png';
//                $this->Image($this->sello_pagado, $this->fdf_Xsellopagado, $this->fdf_Ysellopagado, 80);
//                $this->Ln(0);
//            }
//        }
//
//        // Tipo de Documento y Numero
//        $this->datos_documento($this->fdf_documento, $this->fdf_tipodocumento, $this->fdf_estado, $this->fdf_tipodocumento_vencimiento);
//
//        // Fecha factura y Codigo Cliente
//        $this->addDate($this->fdf_fecha);
//        $this->addPageNumber($this->GroupPageNo().' de '.$this->PageGroupAlias());
//
//        // Datos del Cliente
//        $this->addClienteInfo();
//        if (!empty($this->fdf_transporte) or !empty($this->fdf_ruta)) {
//            $this->addTransporte(utf8_decode($this->fdf_transporte), utf8_decode($this->fdf_ruta));
//        }
//        $this->SetFont('Arial', '', 7);
//        $this->SetY(-8);
//        $this->SetLineWidth(0.1);
//        $this->SetTextColor(0);
//        $this->Cell(0, 4, utf8_decode($this->fde_piefactura), 0, 0, "C");
//
//        // Cabecera Titulos Columnas
//        $this->SetXY(10, 75);
//        $this->SetFont("Arial", "B", 8);
//        if ($this->fdf_cabecera_tcolor) {
//            $rgb = $this->htmlColor2Hex($this->fdf_cabecera_tcolor);
//            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
//        }
//        $contador_dc = count($this->datoscab);
//        for ($i=0;$i<$contador_dc;$i++) {
//            $this->Cell($this->widths[$i], 5, $this->datoscab[$i], 1, 0, 'C', 1);
//        }
//        $this->SetTextColor(0);
//        // Cuerpo de la Factura
//        $this->Ln();
//        $aquiY = $this->GetY() + 0.6;
//        $this->SetY($aquiY);
//        $aquiX = $this->GetX();
//
//        $this->SetDrawColor(0, 0, 0);
//        $this->SetTextColor(0);
//        //El marco que se dibujara de items
//        $totalItems = 140;
//        for ($i=0;$i<$contador_dc;$i++) {
//            if ($this->fdf_detalle_box == '1') {
//                $this->Rect($aquiX, ($aquiY), $this->widths[$i], $totalItems, 'D');
//            }
//            $aquiX += $this->widths[$i];
//        }
//    }
//
//    public function Firmas()
//    {
//        //Posicion: a 3 cm del final
//        $this->SetY(-40);
//        $this->SetLineWidth(0.1);
//        $this->SetTextColor(0);
//        $this->SetFont('Arial', '', 8);
//        $length = 80;
//        $this->Line(10, $this->h - 15, 80, $this->h - 15);
//        $this->SetXY(10, $this->h - 13);
//        $this->Cell($length, 4, str_pad("Firma Cliente", $length, " ", STR_PAD_BOTH));
//        $this->Line(120, $this->h - 15, 200, $this->h - 15);
//        $this->SetXY(120, $this->h - 13);
//        $this->Cell($length, 4, str_pad("Firma Emisor", $length, " ", STR_PAD_BOTH));
//    }
//    //Pie de pagina
//    public function Footer()
//    {
//        $this->Firmas();
//        //Posicion: a 3 cm del final
//        $this->SetY(-30);
//        $this->SetLineWidth(0.1);
//        $this->SetTextColor(0);
//        $this->SetFont('Arial', '', 8);
//        if ($this->piepagina) {
//            // Si existen Incluimos las Observaciones
//            if ($this->fdf_observaciones != '') {
//                $this->addObservaciones(substr($this->fdf_observaciones, 0, 116));
//            }
//
//            // Total factura
//            $this->addTotal();
//        } else {
//            // Neto por Pagina
//            $this->addNeto();
//        }
//    }
//
//    public function Row($data, $ultimo='1', $cantidad_lineas = 0)
//    {
//        $this->SetFont('Verdana', '', 7);
//
//        // Guardamos la posicion Actual
//        $x=$this->GetX();
//        $y=$this->GetY();
//        // Imprimimos solo los campos numericos
//        $contador_cb = count($this->datoscab);
//        $contador_data = count($data);
//        for ($i=0;$i<$contador_data;$i++) {
//            if ($i != $ultimo) { // La descripcion del articulo la trataremos la ultima. Aqui no.
//                $w=$this->widths[$i];
//                if ($i == ($ultimo-1)) {
//                    $x1 = $x+$w;
//                    $x += $this->widths[$ultimo]+$w;
//                } else {
//                    $x += $w;
//                }
//                // Seleccionar Alineacion
//                $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
//                // Seleccionar color
//                $this->SetTextColor(0);
//                if (isset($this->colores[$i][0])) {
//                    $this->SetTextColor($this->colores[$i][0], $this->colores[$i][1], $this->colores[$i][2]);
//                }
//                // Escribimos el texto
//                $this->MultiCell($w, 5, $data[$i], 0, $a);
//                // Fijamos la posicion a la derecha de la celda
//                $this->SetXY($x, $y);
//            }
//        }
//
//        // En Ultimo lugar escribimos La descripcion del articulo
//        $this->SetXY($x1, $y);
//        $w=$this->widths[$ultimo];
//        $a=isset($this->aligns[$ultimo]) ? $this->aligns[$ultimo] : 'L';
//
//        $this->MultiCell($w, 5, $data[$ultimo], 0, $a);
//
//        // Calcular la altura MAXIMA de la fila e ir a la siguiente línea
//        $nb = 0;
//        $totalLineas = 28;
//        for ($i=0;$i<$contador_data;$i++) {
//            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
//        }
//        if ((($this->lineaactual + $nb) > $totalLineas) and $cantidad_lineas > 1) { // Mas de una Pagina
//            $this->AddPage($this->CurOrientation);
//            $this->lineaactual = 0;
//        } else {
//            if ((($this->lineaactual + $nb) == $totalLineas) and $cantidad_lineas > 1) { // Pagina completa
//                $this->AddPage($this->CurOrientation);
//                $this->lineaactual = 0;
//            } else {
//                $this->lineaactual = $this->lineaactual + $nb; // Una sola Pagina
//            }
//        }
//
//        $h = 5 * $this->lineaactual;
//        $this->Ln($h);
//        $this->SetY(80+$h); // Y=100 en base a la altura de la cabecera
//
//        // Dibujamos una Linea Gris para separar los Articulos
//        $aquiX=$this->GetX()+0.155;
//        $aquiY=$this->GetY();
//        $this->SetDrawColor(200, 200, 200);
//        for ($i=0;$i<$contador_cb;$i++) {
//            $finX = $this->widths[$i]+$aquiX - 0.316;
//            $this->Line($aquiX, $aquiY, $finX, $aquiY);
//            $aquiX = $finX + 0.316;
//        }
//        $this->SetDrawColor(0, 0, 0);
//        $this->SetTextColor(0);
//    }
//
//    public function NbLines($w, $txt)
//    {
//        //Computes the number of lines a MultiCell of width w will take
//        $cw=&$this->CurrentFont['cw'];
//        if ($w==0) {
//            $w=$this->w-$this->rMargin-$this->x;
//        }
//        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
//        $s=str_replace("\r", '', $txt);
//        $nb=strlen($s);
//        if ($nb>0 and $s[$nb-1]=="\n") {
//            $nb--;
//        }
//        $sep=-1;
//        $i=0;
//        $j=0;
//        $l=0;
//        $nl=1;
//        while ($i < $nb) {
//            $c = $s[$i];
//            if ($c == "\n") {
//                $i++;
//                $sep = -1;
//                $j = $i;
//                $l = 0;
//                $nl++;
//                continue;
//            }
//            if ($c == ' ') {
//                $sep = $i;
//            }
//            $l += $cw[$c];
//            if ($l > $wmax) {
//                if ($sep == -1) {
//                    if ($i == $j) {
//                        $i++;
//                    }
//                } else {
//                    $i = $sep + 1;
//                }
//                $sep = -1;
//                $j = $i;
//                $l = 0;
//                $nl++;
//            } else {
//                $i++;
//            }
//        }
//        return $nl;
//    }
//
//    /**
//     * @deprecated since version 101
//     * @param type $x
//     * @param type $y
//     * @param type $w
//     * @param type $h
//     * @param type $r
//     * @param type $style
//     */
//    public function RoundedRect($x, $y, $w, $h, $r, $style = '')
//    {
//        $k = $this->k;
//        $hp = $this->h;
//        if ($style=='F') {
//            $op='f';
//        } elseif ($style=='FD' or $style=='DF') {
//            $op='B';
//        } else {
//            $op='S';
//        }
//        $MyArc = 4/3 * (sqrt(2) - 1);
//        $this->_out(sprintf('%.2f %.2f m', ($x+$r)*$k, ($hp-$y)*$k));
//        $xc = $x+$w-$r ;
//        $yc = $y+$r;
//        $this->_out(sprintf('%.2f %.2f l', $xc*$k, ($hp-$y)*$k));
//
//        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
//        $xc = $x+$w-$r ;
//        $yc = $y+$h-$r;
//        $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k, ($hp-$yc)*$k));
//        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
//        $xc = $x+$r ;
//        $yc = $y+$h-$r;
//        $this->_out(sprintf('%.2f %.2f l', $xc*$k, ($hp-($y+$h))*$k));
//        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
//        $xc = $x+$r ;
//        $yc = $y+$r;
//        $this->_out(sprintf('%.2f %.2f l', ($x)*$k, ($hp-$yc)*$k));
//        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
//        $this->_out($op);
//    }
//
//    public function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
//    {
//        $h = $this->h;
//        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
//            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
//    }
//
//    // Uso:
//    // set alpha to semi-transparency
//    // $pdf->SetAlpha(0.5, 'Lighten');
//    // draw jpeg image
//    // $pdf->Image('imagen.jpg',30,30,40);
//    // restore full opacity
//    // $pdf->SetAlpha(1);
//    //
//    // class AlphaPDF
//
//    // alpha: real value from 0 (transparent) to 1 (opaque)
//    // bm:    blend mode, one of the following:
//    //          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
//    //          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
//    public function SetAlpha($alpha, $bm='Normal')
//    {
//        // set alpha for stroking (CA) and non-stroking (ca) operations
//        $gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
//        $this->SetExtGState($gs);
//    }
//
//    public function AddExtGState($parms)
//    {
//        $n = count($this->extgstates)+1;
//        $this->extgstates[$n]['parms'] = $parms;
//        return $n;
//    }
//
//    public function SetExtGState($gs)
//    {
//        $this->_out(sprintf('/GS%d gs', $gs));
//    }
//
//    public function _enddoc()
//    {
//        if (!empty($this->extgstates) && $this->PDFVersion<'1.4') {
//            $this->PDFVersion='1.4';
//        }
//        parent::_enddoc();
//    }
//
//    public function _putextgstates()
//    {
//        $counter_extgstates = count($this->extgstates);
//        for ($i = 1; $i <= $counter_extgstates; $i++) {
//            $this->_newobj();
//            $this->extgstates[$i]['n'] = $this->n;
//            $this->_out('<</Type /ExtGState');
//            $parms = $this->extgstates[$i]['parms'];
//            $this->_out(sprintf('/ca %.3F', $parms['ca']));
//            $this->_out(sprintf('/CA %.3F', $parms['CA']));
//            $this->_out('/BM '.$parms['BM']);
//            $this->_out('>>');
//            $this->_out('endobj');
//        }
//    }
//
//    public function _putresourcedict()
//    {
//        parent::_putresourcedict();
//        $this->_out('/ExtGState <<');
//        foreach ($this->extgstates as $k=>$extgstate) {
//            $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
//        }
//        $this->_out('>>');
//    }
//
//    public function _putresources()
//    {
//        $this->_putextgstates();
//        parent::_putresources();
//    }
//    // END-class AlphaPDF
//
//    // Girar Texto o Imagen
//    public function RotatedText($x, $y, $txt, $angle)
//    {
//        //Text rotated around its origin
//        $this->Rotate($angle, $x, $y);
//        $this->Text($x, $y, $txt);
//        $this->Rotate(0);
//    }
//
//    public function RotatedImage($file, $x, $y, $w, $h, $angle)
//    {
//        //Image rotated around its upper-left corner
//        $this->Rotate($angle, $x, $y);
//        $this->Image($file, $x, $y, $w, $h);
//        $this->Rotate(0);
//    }
//
//    public function Rotate($angle, $x=-1, $y=-1)
//    {
//        if ($x==-1) {
//            $x=$this->x;
//        }
//        if ($y==-1) {
//            $y=$this->y;
//        }
//        if ($this->angle!=0) {
//            $this->_out('Q');
//        }
//        $this->angle=$angle;
//        if ($angle!=0) {
//            $angle*=M_PI/180;
//            $c=cos($angle);
//            $s=sin($angle);
//            $cx=$x*$this->k;
//            $cy=($this->h-$y)*$this->k;
//            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
//        }
//    }
//
//    public function _endpage()
//    {
//        if ($this->angle!=0) {
//            $this->angle=0;
//            $this->_out('Q');
//        }
//        parent::_endpage();
//    }
//    // END - Girar Texto o Imagen
//
//    // Factura
//    public function sizeOfText($texte, $largeur)
//    {
//        $index    = 0;
//        $nb_lines = 0;
//        $loop     = true;
//        while ($loop) {
//            $pos = strpos($texte, "\n");
//            if (!$pos) {
//                $loop  = false;
//                $ligne = $texte;
//            } else {
//                $ligne  = substr($texte, $index, $pos);
//                $texte = substr($texte, $pos+1);
//            }
//            $length = floor($this->GetStringWidth($ligne));
//            $res = 1 + floor($length / $largeur) ;
//            $nb_lines += $res;
//        }
//        return $nb_lines;
//    }
//
//    public function htmlColor2Hex($hex)
//    {
//        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
//        return array($r, $g, $b);
//    }
//
//    // Empresa
//    public function addSociete($nom, $adresse, $email, $web)
//    {
//        $x1 = ($this->fdf_verlogotipo == '1')?45:10;
//        $r1  = $x1;
//        $r2  = $r1 + 90;
//        $y1 = 8;
//        $this->SetXY($x1, $y1);
//        $this->SetTextColor(0);
//        $length1 = $this->GetStringWidth($nom);
//        $this->SetFont('Arial', 'B', ($length1>($r2 - $r1))?10:9);
//        $this->MultiCell(($r2 - $r1), 4, $nom, '0', 'L');
//        $y1+=($this->getY()-$y1);
//        $this->SetXY($x1, $y1);
//        $this->SetFont("Arial", "", 8);
//        $this->MultiCell(($r2 - $r1), 4, $adresse);
//        $y1+=($this->getY()-$y1);
//        $this->SetXY($x1, $y1);
//        if ($email != '') {
//            $this->SetFont('Arial', '', 8);
//            $this->Write(5, 'Email: ');
//            $this->SetTextColor(0, 0, 255);
//            $this->Write(5, $email, 'mailto:' . $email);
//            $this->SetTextColor(0);
//            $this->SetFont('');
//        }
//
//        if ($web != '') {
//            $this->SetFont('Arial', '', 8);
//            $this->Write(5, ' - Web: ');
//            $this->SetTextColor(0, 0, 255);
//            $this->Write(5, $web, $web);
//            $this->SetTextColor(0);
//            $this->SetFont('');
//        }
//    }
//
//    public function datos_documento($documento, $tipo_documento, $estado, $fecha_vencimiento)
//    {
//        $r1  = $this->w - 80;
//        $r2  = $r1 + 70;
//        $y1  = 6;
//        $y2  = $y1 + 20;
//        if ($this->fdf_cabecera_tcolor) {
//            $rgb = $this->htmlColor2Hex($this->fdf_cabecera_tcolor);
//            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
//        }
//        $codigo  = utf8_decode('Factura: ').$documento->codigo;
//        $ncf  = utf8_decode(ucfirst(FS_NUMERO2)).': '.$documento->ncf;
//        $szfont = 9;
//        $loop   = 0;
//
//        while ($loop == 0) {
//            $this->SetFont("Arial", "B", $szfont);
//            $sz = $this->GetStringWidth($ncf);
//            if (($r1+$sz) > $r2) {
//                $szfont--;
//            } else {
//                $loop++;
//            }
//        }
//
//        $this->SetLineWidth(0.1);
//        $this->Rect($r1, $y1, ($r2 - $r1), $y2, 'DF');
//        $y1++;
//        $this->SetXY($r1+1, $y1);
//        $this->MultiCell($r2-$r1-1, 3, utf8_decode($tipo_documento), 0, "C");
//        $y1++;
//        $y1++;
//        $this->SetXY($r1+1, $y1+1);
//        $this->Cell($r2-$r1 -1, 5, $ncf, 0, 0, "C");
//        $this->SetFont("Arial", "B", 8);
//        $y1++;
//        $y1++;
//        $y1++;
//        $this->SetXY($r1+1, $y1+2);
//        $this->Cell($r2-$r1-1, 5, $codigo, 0, 0, "C");
//        $y1++;
//        $y1++;
//        $y1++;
//        $this->SetXY($r1+1, $y1+4);
//        if (empty($estado)) {
//            if ($fecha_vencimiento) {
//                $this->MultiCell($r2-$r1-1, 3, utf8_decode('Válida hasta: '.$fecha_vencimiento), 0, "C");
//            }
//        } else {
//            $this->MultiCell($r2-$r1-1, 3, 'Estado: '.$estado, 0, "C");
//        }
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        if ($documento->ncf_afecta) {
//            $this->SetXY($r1+1, $y1+3);
//            $this->SetFont("Arial", "B", 8);
//            $this->Cell(30, 5, "Rectifica: ", 0, 0, "R");
//            $this->SetFont("Arial", "", 9);
//            $this->Cell(60, 5, $documento->ncf_afecta, 0, 0, "L");
//        }
//        $this->SetTextColor(0);
//    }
//
//    // Nombre, numero y estado de la factura
//    public function fact_dev($libelle, $num, $estado)
//    {
//        $r1  = $this->w - 80;
//        $r2  = $r1 + 70;
//        $y1  = 6;
//        $y2  = $y1 + 7;
//        $mid = ($r1 + $r2) / 2;
//
//        $texte  = 'NCF: ' . $num;
//        $tipo_comprobante = $libelle;
//        $szfont = 9;
//        $loop   = 0;
//
//        while ($loop == 0) {
//            $this->SetFont("Arial", "B", $szfont);
//            $sz = $this->GetStringWidth($texte);
//            if (($r1+$sz) > $r2) {
//                $szfont --;
//            } else {
//                $loop ++;
//            }
//        }
//
//        $this->SetLineWidth(0.1);
//        $this->Rect($r1, $y1, ($r2 - $r1), $y2, 'DF');
//        $this->SetXY($r1+1, $y1+2);
//        $this->Cell($r2-$r1 -1, 5, $texte, 0, 0, "C");
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY($r1+1, $y1+7);
//        $this->MultiCell($r2-$r1-1, 3, $tipo_comprobante, 0, "C");
//        $this->SetXY($r1+1, $y1+7);
//        $this->MultiCell($r2-$r1-1, 3, $estado, 0, "C");
//    }
//
//    public function addDate($date)
//    {
//        $r1  = $this->w - 80;
//        $r2  = $r1 + 70;
//        $y1  = 27;
//        $y2  = $y1+5;
//        if ($this->fdf_cabecera_tcolor) {
//            $rgb = $this->htmlColor2Hex($this->fdf_cabecera_tcolor);
//            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
//        }
//        $this->SetXY($r1, $y1);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'DF');
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(20, 5, "Fecha: ", 0, 0, "L");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $date, 0, 0, "R");
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(20, 5, "F. Pago: ", 0, 0, "L");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(20, 5, utf8_decode($this->fdf_epago), 0, 0, "R");
//        $this->SetTextColor(0);
//    }
//
//    //Transporte Asociado
//    public function addTransporte($transporte, $codruta)
//    {
//        $r1  = $this->w - 80;
//        $r2  = $r1 + 70;
//        $y1  = 32;
//        $y2  = $y1+5;
//        if ($this->fdf_cabecera_tcolor) {
//            $rgb = $this->htmlColor2Hex($this->fdf_cabecera_tcolor);
//            $this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
//        }
//        $this->SetXY($r1, $y1);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'DF');
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(20, 5, "Ruta: ", 0, 0, "L");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $codruta, 0, 0, "R");
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(25, 5, "Transporte: ", 0, 0, "L");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(15, 5, $transporte, 0, 0, "R");
//        $this->SetTextColor(0);
//    }
//
//
//    /**
//     * //Ya no se utiliza
//     * @deprecated
//     * @param type $ref
//     */
//    public function addClient($ref)
//    {
//        $r1  = $this->w - 50;
//        $r2  = $r1 + 40;
//        $y1  = 30;
//        $y2  = $y1;
//        $mid = $y1 + ($y2 / 3);
//        $this->Rect($r1, $y1, ($r2 - $r1), $y2-6, 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2-$r1)/2 - 5, $y1+1);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(10, 5, 'N'.chr(176).' de CLIENTE', 0, 0, "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 - 5, $y1 + 7);
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $ref, 0, 0, "C");
//    }
//
//    public function addPageNumber($page)
//    {
//        $this->SetXY(($this->w-20)/2, $this->h - 14);
//        $this->SetFont("Arial", "", 7);
//        $this->Cell(10, 5, "- Pagina ".$page." -", 0, 0, "C");
//    }
//
//    public function addClienteInfo()
//    {
//        $r1  = $this->w - 205;
//        $r2  = $this->w - 10;
//        $y1  = 45;
//        //$y2  = $y1;
//        $this->SetXY($r1, $y1);
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Cliente:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->Cell(120, 5, $this->fdf_codcliente. ' - '.utf8_decode($this->fdf_nombrecliente), 0, 0, "L");
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode($this->fdf_FS_CIFNIF.':'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->Cell(25, 5, $this->fdf_cifnif, 0, 0, "C");
//        $this->SetXY($r1, $y1+4);
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Dirección:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->MultiCell(120, 5, utf8_decode($this->fdf_direccion. " - ".$this->fdf_codpostal . " - ".$this->fdf_ciudad . " (".$this->fdf_provincia.")\n"));
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $this->SetXY($r1, $y1+4);
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Teléfonos:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $telefonos = '';
//        if ($this->fdc_telefono1) {
//            $telefonos.= $this->fdc_telefono1;
//        }
//        if ($this->fdc_telefono2) {
//            $telefonos.= $this->fdc_telefono2;
//        }
//        $this->Cell(120, 5, $telefonos, 0, 0, "L");
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Fax:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->Cell(30, 5, $this->fdc_fax, 0, 0, "L");
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $this->SetXY($r1, $y1+4);
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Email:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->Cell(30, 5, $this->fdc_email, 0, 0, "L");
//        $y1++;
//        $y1++;
//        $y1++;
//        $y1++;
//        $this->SetXY($r1, $y1+4);
//        $this->SetFont('Arial', 'B', 8);
//        $this->Cell(25, 5, utf8_decode('Vendedor:'), 0, 0, "R");
//        $this->SetFont('Arial', '', 9);
//        $this->Cell(120, 5, utf8_decode($this->fde_vendedor), 0, 0, "L");
//        if ($this->fdf_cliente_box =='1') {
//            $this->Rect($r1-1, 43, ($r2 - ($r1-1)), 30, 'D');
//        }
//    }
//
//    // Cliente
//    public function addClientAdresse($adresse)
//    {
//        $r1     = $this->w - 205;
//        $y1     = 40;
//        $this->SetXY($r1, $y1);
//        $this->AddFont('Verdana');
//        $this->SetFont('Verdana', '', 8);
//        $this->MultiCell(87, 4, $adresse);
//    }
//
//
//
//    // Ruta asociada si es que se va utilizar distribucion
//    public function addRuta($ruta)
//    {
//        $r1  = 120;
//        $r2  = $r1 + 30;
//        $y1  = 65;
//        $y2  = $y1+10;
//        $mid = $y1 + (($y2-$y1) / 2);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1+1);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(10, 4, "RUTA", 0, 0, "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1 + 5);
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $ruta, 0, 0, "C");
//    }
//
//    // Transporte Asociado
//    public function addDocumentoRectifica($mode)
//    {
//        $r1  = 150;
//        $r2  = $r1 + 50;
//        $y1  = 65;
//        $y2  = $y1+10;
//        $mid = $y1 + (($y2-$y1) / 2);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1+1);
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(10, 4, "AFECTA AL DOCUMENTO", 0, 0, "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1 + 5);
//        $this->SetFont("Arial", "", 9);
//        $this->Cell(10, 5, $mode, 0, 0, "C");
//    }
//
//    // Forma de Pago
//    public function addPago($mode)
//    {
//        $r1  = 150;
//        $r2  = $r1 + 50;
//        $y1  = 80;
//        $y2  = $y1+10;
//        $mid = $y1 + (($y2-$y1) / 2);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1+1);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(10, 4, "FORMA DE PAGO", 0, 0, "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 -5, $y1 + 5);
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $mode, 0, 0, "C");
//    }
//
//    // Divisa
//    public function addDivisa($divisa)
//    {
//        $r1  = 140;
//        $r2  = $r1 + 30;
//        $y1  = 80;
//        $y2  = $y1+10;
//        $mid = $y1 + (($y2-$y1) / 2);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2 - $r1)/2 - 5, $y1+1);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(10, 4, "DIVISA", 0, 0, "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 - 5, $y1 + 5);
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(10, 5, $divisa, 0, 0, "C");
//    }
//
//    // Pais
//    public function addPais($pais)
//    {
//        $r1  = 170;
//        $r2  = $r1 + 30;
//        $y1  = 80;
//        $y2  = $y1+10;
//        $mid = $y1 + (($y2-$y1) / 2);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->Line($r1, $mid, $r2, $mid);
//        $this->SetXY($r1 + ($r2 - $r1)/2 - 5, $y1+1);
//        $this->SetFont("Arial", "B", 9);
//        $this->Cell(10, 4, "PAIS", '', '', "C");
//        $this->SetXY($r1 + ($r2-$r1)/2 - 5, $y1 + 5);
//        $this->SetFont("Arial", "", 9);
//        $this->Cell(10, 5, $pais, '', '', "C");
//    }
//
//    // Incluir Observaciones
//    public function addObservaciones($observa)
//    {
//        $this->SetFont("Arial", "I", 8);
//        $length = $this->GetStringWidth("Observaciones: " . $observa);
//        $this->SetXY(10, $this->h - 57.5);
//        $this->Cell($length, 4, "Observaciones: " . $observa);
//    }
//
//    // Incluir Lineas de Iva
//    public function addLineasIva($datos)
//    {
//        $r1  = 10;
//        $y1  = $this->h - 50;
//        $cantidad_datos = count($datos);
//        if ($datos) {
//            if ($cantidad_datos > 3) {
//                // Comentar o eliminar las siguientes 5 lineas para NO mostrar el error.
//                $this->SetFont("Arial", "B", 8);
//                $this->SetXY($r1, $y1 + 8);
//                $this->Cell(8, 4, "ERROR: Localizadas ".count($datos)." lineas de ".FS_IVA."... ", 0, '', "L");
//                $this->SetXY($r1, $y1 + 12);
//                $this->Cell(8, 4, chr(161).chr(161).chr(161)." Esta plantilla SOLO puede detallar TRES lineas de ".FS_IVA." !!!", 0, '', "L");
//            } else {
//                for ($i=1; $i <= $cantidad_datos; $i++) {
//                    $datos[$i][1]=($datos[$i][1]>0)?$datos[$i][1]*-1:$datos[$i][1];
//                    if ($i == 1) {
//                        $y2  = $y1 + 6;
//                    }
//                    if ($i == 2) {
//                        $y2  = $y1 + 10;
//                    }
//                    if ($i == 3) {
//                        $y2  = $y1 + 14;
//                    }
//                    $this->SetFont("Arial", "B", 8);
//                    $this->SetXY($r1, $y2);
//                    $this->Cell(8, 4, $datos[$i][0], 0, '', "L");
//                    $this->Cell(18, 4, $datos[$i][1], 0, '', "R");
//                    $this->Cell(7, 4, $datos[$i][2], 0, '', "R");
//                    $this->Cell(18, 4, $datos[$i][3], 0, '', "R");
//                    $this->Cell(7, 4, $datos[$i][4], 0, '', "R");
//                    $this->Cell(18, 4, $datos[$i][5], 0, '', "R");
//                    $this->Cell(7, 4, $datos[$i][6], 0, '', "R");
//                    $this->Cell(18, 4, $datos[$i][7], 0, '', "R");
//                    $this->SetFont("Arial", "B", 9);
//                    $this->Cell(24, 4, $datos[$i][8], 0, '', "R");
//                }
//            }
//        }
//    }
//
//    public function addNeto()
//    {
//        $r1  = $this->w - 70;
//        $r2  = $r1 + 60;
//        $y1  = $this->h - 50;
//        $y2  = $y1+20;
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 1.5, 'D');
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY($r1, $y1);
//        $this->Cell(60, 4, $this->fdf_divisa, 1, 0, "C");
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY($r1, $y1+7);
//        $this->Cell(15, 4, "NETO", 0, 0, "C");
//
//        // Total Neto de la pagina
//        $this->SetFont("Arial", "", 8);
//        $this->SetXY($r1+16, $y1+6.5);
//        $this->Cell(43, 4, $this->neto, 0, 0, "C");
//
//        // Suma y Sigue
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY($r1+16, $y1+13);
//        $this->MultiCell(43, 3, '(SUMA y SIGUE)', 0, 'C');
//    }
//
//    public function addTotal()
//    {
//        $this->SetFont("Arial", "B", 9);
//        $rr1  = 10;
//        $rr2  = $rr1 + 125;
//        $yy1  = $this->h - 50;
//        $yy2  = $yy1+20;
//        $this->Rect($rr1, $yy1, ($rr2 - $rr1), ($yy2-$yy1), 'D');
//        $r1  = $this->w - 70;
//        $r2  = $r1 + 60;
//        $y1  = $this->h - 50;
//        $y2  = $y1+22;
//        $this->SetLineWidth(0.15);
//        $this->Rect($r1, $y1, ($r2 - $r1), ($y2-$y1), 'D');
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY($r1, $y1);
//        $this->Cell(60, 4, $this->fdf_divisa, 1, 0, "C");
//        $this->SetXY($r1, $y1+5);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(15, 4, "NETO", 0, 0, "R");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(43, 4, $this->fdf_documento_neto, 0, 0, "R");
//        $this->SetXY($r1, $y1+9);
//        //$this->SetFont( "Arial", "B", 9);
//        //$this->Cell(15,4, "DSCTO", 0, 0, "R");
//        //$this->SetFont( "Arial", "", 8);
//        //$this->Cell(43,4, $this->fdf_documento_descuentos, 0, 0, "R");
//        $this->SetXY($r1, $y1+13);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(15, 4, FS_IVA, 0, 0, "R");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(43, 4, $this->fdf_documento_totaliva, 0, 0, "R");
//        $this->SetXY($r1, $y1+17);
//        $this->SetFont("Arial", "B", 8);
//        $this->Cell(15, 4, "TOTAL", 0, 0, "R");
//        $this->SetFont("Arial", "", 8);
//        $this->Cell(43, 4, $this->fdf_numtotal, 0, 0, "R");
//        $this->SetLineWidth(0.1);
//        // Total factura en texto
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY(12, $y1+4);
//        $texto = $this->numtoletras($this->fdf_textotal);
//        $this->MultiCell(120, 3, "SON: ".$texto, 0, 'L');
//        $this->SetXY(10, $this->h - 33.5);
//        $this->SetFont("Arial", "B", 8);
//        $this->SetXY(10, $this->h - 33.5);
//        if($this->fdf_pagada){
//            $this->Cell(120, 3, 'FACTURA PAGADA EL: '.$this->fdf_fecha_pagada, 0, 0, "R");
//        }else{
//            $this->Cell(120, 3, 'FACTURA VENCE EL: '.$this->fdf_vencimiento, 0, 0, "R");
//        }
//
//    }
//
//    //------    CONVERTIR NUMEROS A LETRAS         ---------------
//    //------    Máxima cifra soportada: 18 dígitos con 2 decimales
//    //------    999,999,999,999,999,999.99
//    // NOVECIENTOS NOVENTA Y NUEVE MIL NOVECIENTOS NOVENTA Y NUEVE con 99/100
//    public function numtoletras($xcifra1)
//    {
//        $xarray = array(0 => "Cero",
//            1 => "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
//            "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
//            "VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
//            100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS", 700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
//        );
//        //
//        $xcifra2 = ($xcifra1<0)?($xcifra1*-1):$xcifra1;
//        $xcifra = trim($xcifra2);
//        $xlength = strlen($xcifra);
//        $xpos_punto = strpos($xcifra, ".");
//        $xaux_int = $xcifra;
//        $xdecimales = "00";
//        if (!($xpos_punto === false)) {
//            if ($xpos_punto == 0) {
//                $xcifra = "0" . $xcifra;
//                $xpos_punto = strpos($xcifra, ".");
//            }
//            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a convertir
//            $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
//        }
//
//        $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
//        $xcadena = "";
//        for ($xz = 0; $xz < 3; $xz++) {
//            $xaux = substr($XAUX, $xz * 6, 6);
//            $xi = 0;
//            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
//            $xexit = true; // bandera para controlar el ciclo del While
//            while ($xexit) {
//                if ($xi == $xlimite) { // si ya ha llegado al límite máximo de enteros
//                    break; // termina el ciclo
//                }
//
//                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
//                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
//                for ($xy = 1; $xy < 4; $xy++) { // ciclo para revisar centenas, decenas y unidades, en ese orden
//                    switch ($xy) {
//                        case 1: // checa las centenas
//                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas
//                            } else {
//                                $key = (int) substr($xaux, 0, 3);
//                                if (true === array_key_exists($key, $xarray)) {  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
//                                    $xseek = $xarray[$key];
//                                    $xsub = $this->subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
//                                    if (substr($xaux, 0, 3) == 100) {
//                                        $xcadena = " " . $xcadena . " CIEN " . $xsub;
//                                    } else {
//                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
//                                    }
//                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
//                                } else { // entra aquí si la centena no es numero redondo (101, 253, 120, 980, etc.)
//                                    $key = (int) substr($xaux, 0, 1) * 100;
//                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
//                                    $xcadena = " " . $xcadena . " " . $xseek;
//                                } // ENDIF ($xseek)
//                            } // ENDIF (substr($xaux, 0, 3) < 100)
//                            break;
//                        case 2: // Chequear las decenas (con la misma lógica que las centenas)
//                            if (substr($xaux, 1, 2) < 10) {
//                            } else {
//                                $key = (int) substr($xaux, 1, 2);
//                                if (true === array_key_exists($key, $xarray)) {
//                                    $xseek = $xarray[$key];
//                                    $xsub = $this->subfijo($xaux);
//                                    if (substr($xaux, 1, 2) == 20) {
//                                        $xcadena = " " . $xcadena . " VEINTE " . $xsub;
//                                    } else {
//                                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
//                                    }
//                                    $xy = 3;
//                                } else {
//                                    $key = (int) substr($xaux, 1, 1) * 10;
//                                    $xseek = $xarray[$key];
//                                    if (20 == substr($xaux, 1, 1) * 10) {
//                                        $xcadena = " " . $xcadena . " " . $xseek;
//                                    } else {
//                                        $xcadena = " " . $xcadena . " " . $xseek . " Y ";
//                                    }
//                                } // ENDIF ($xseek)
//                            } // ENDIF (substr($xaux, 1, 2) < 10)
//                            break;
//                        case 3: // Chequear las unidades
//                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada
//                            } else {
//                                $key = (int) substr($xaux, 2, 1);
//                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
//                                $xsub = $this->subfijo($xaux);
//                                $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
//                            } // ENDIF (substr($xaux, 2, 1) < 1)
//                            break;
//                    } // END SWITCH
//                } // END FOR
//                $xi = $xi + 3;
//            } // ENDDO
//
//            if (substr(trim($xcadena), -5, 5) == "ILLON") { // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
//                $xcadena.= " DE";
//            }
//
//            if (substr(trim($xcadena), -7, 7) == "ILLONES") { // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
//                $xcadena.= " DE";
//            }
//
//            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
//            if (trim($xaux) != "") {
//                switch ($xz) {
//                    case 0:
//                        if (trim(substr($XAUX, $xz * 6, 6)) == "1") {
//                            $xcadena.= "UN BILLON ";
//                        } else {
//                            $xcadena.= " BILLONES ";
//                        }
//                        break;
//                    case 1:
//                        if (trim(substr($XAUX, $xz * 6, 6)) == "1") {
//                            $xcadena.= "UN MILLON ";
//                        } else {
//                            $xcadena.= " MILLONES ";
//                        }
//                        break;
//                    case 2:
//                        if ($xcifra < 1) {
//                            $xcadena = "CERO con $xdecimales/100";
//                        }
//                        if ($xcifra >= 1 && $xcifra < 2) {
//                            $xcadena = "UNO con $xdecimales/100";
//                        }
//                        if ($xcifra >= 2) {
//                            $xcadena.= " con $xdecimales/100";
//                        }
//                        break;
//                } // endswitch ($xz)
//            } // ENDIF (trim($xaux) != "")
//
//            $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
//            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
//            $xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
//            $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
//            $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
//            $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
//            $xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
//        } // ENDFOR ($xz)
//
//        $xcadena = str_replace("UN MIL ", "MIL ", $xcadena); // quito el BUG de UN MIL
//        return trim($xcadena);
//    }
//
//    // END FUNCTION
//
//    public function subfijo($xx)
//    { // esta función genera un subfijo para la cifra
//        $xx = trim($xx);
//        $xstrlen = strlen($xx);
//        if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3) {
//            $xsub = "";
//        }
//        //
//        if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6) {
//            $xsub = "MIL";
//        }
//        //
//        return $xsub;
//    }
//
//    // END FUNCTION
//
//    // create a new page group; call this before calling AddPage()
//    public function StartPageGroup()
//    {
//        $this->NewPageGroup=true;
//    }
//
//    // current page in the group
//    public function GroupPageNo()
//    {
//        return $this->PageGroups[$this->CurrPageGroup];
//    }
//
//    // alias of the current page group -- will be replaced by the total number of pages in this group
//    public function PageGroupAlias()
//    {
//        return $this->CurrPageGroup;
//    }
//
//    public function _beginpage($orientation, $size = null, $rotation = 0)
//    {
//        parent::_beginpage($orientation, $size, $rotation);
//        if ($this->NewPageGroup) {
//            // start a new group
//            $n = 1;
//            if (is_array($this->PageGroups) && sizeof($this->PageGroups) <= 0) {
//                $n = sizeof($this->PageGroups)+1;
//            }
//
//            $alias = "{nb$n}";
//            $this->PageGroups[$alias] = 1;
//            $this->CurrPageGroup = $alias;
//            $this->NewPageGroup=false;
//        } elseif ($this->CurrPageGroup) {
//            $this->PageGroups[$this->CurrPageGroup]++;
//        }
//    }
//
//    public function _putpages()
//    {
//        $nb = $this->page;
//        if (!empty($this->PageGroups)) {
//            // do page number replacement
//            foreach ($this->PageGroups as $k => $v) {
//                for ($n = 1; $n <= $nb; $n++) {
//                    $this->pages[$n]=str_replace($k, $v, $this->pages[$n]);
//                }
//            }
//        }
//        parent::_putpages();
//    }
//}