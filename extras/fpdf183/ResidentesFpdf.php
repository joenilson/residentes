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
    public $page_size;

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4', $font = 'Verdana')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetFont($font, '', 10);
        $this->setMaxLines($size);
        $this->SetAutoPagebreak(false);
        $this->SetMargins(0, 0, 0);
        $this->font = $font;
        $this->page_size = $size;
    }

    public function setMaxLines($pageSize)
    {
        switch ($pageSize) {
            case 'A4':
                $this->max_lines = 20;
                break;
            case 'letter':
            default:
                $this->max_lines = 23;
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
        //$y = 104;
        //$y = $this->GetPageHeight()-63.5;
        $y = ($this->page_size === 'A5') ? $this->GetPageHeight()-44.5 : $this->GetPageHeight()-63.5;
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

        $this->Rect($x, $y+18, 95, 6, "D");
        $this->SetXY($x, $y+18);
        $this->Cell(38, 5, "Condicion Factura:", 0, 0, 'L');
        $this->Cell(38, 5, $documentFooter['condicion'], 0, 0, 'L');


        $this->SetFont($this->font, "BU", 8);
        $this->SetXY($x, $y+30);
        $this->Cell($this->GetStringWidth("Observaciones"), 0, "Observaciones", 0, "L");
        $this->SetFont($this->font, "", 8);
        $this->SetXY($x, $y+35);
        $this->MultiCell(190, 4, utf8_decode($documentFooter['observaciones']), 0, "L");
        //$y += 5;
        //$y = $this->GetPageHeight()-63.5;
        $yy = $y +5;

        $y = ($this->page_size === 'A5') ? $this->GetPageHeight()-44.5 : $this->GetPageHeight()-63.5;
        $this->setDocumentFooterTotalsBase($x, $y);
        $this->setDocumentFooterTotalsData($documentFooter, $x, $y);
    }

    public function setDocumentFooterCompanyText($companyText = 'Por definir')
    {
        $this->SetLineWidth(0.1);
        $this->Rect(10, $this->GetPageHeight()-10, $this->GetPageWidth()-20, 6, "D");
        $this->SetXY(1, $this->GetPageHeight()-10);
        $this->SetFont($this->font, '', 7);
        $this->Cell($this->GetPageWidth(), 6, $companyText, 0, 0, 'C');
    }

    public function setDocumentFooterTotalsBase($x, $y)
    {
        $x += 130;
        $this->SetLineWidth(0.1);
//        $this->Rect($x, $y, 60, (5*5), "D");
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
        $this->setDocumentInternalLines($arrayDatosLineas, $arrayAnchoLineas, $arrayLineas, $y);
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
                $y += 6;
                $this->SetXY($x, $y);
                $line = $documentLines[$i];
                $linesSubtotal += $line['pvptotal'];
                $this->Cell(95, 5, utf8_decode($line['descripcion']), 0, 0, 'L');
                // qte
                $this->SetXY(105, $y);
                $this->Cell(15, 5, strrev(wordwrap(strrev($line['cantidad']), 3, ' ', true)), 0, 0, 'R');
                // PU
                $numero_formateado = number_format($line['pvpunitario'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(120, $y);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                //Descuento
                $numero_formateado = number_format(($line['pvpsindto'] - $line['pvptotal']), FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(140, $y);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                // Impuesto
                $numero_formateado = number_format($line['iva'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(160, $y);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                // total
                $numero_formateado = number_format($line['pvptotal'], FS_NF0, FS_NF1, FS_NF2);
                $this->SetXY(180, $y);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
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

        $x = $this->GetPageWidth()-76;
        $y = 3;
        $this->SetXY($x, $y);
        $this->SetFont($this->font, 'B', 7);
        $this->Cell(60, 5, utf8_decode("Página " . $pageNumber . " de " . $numberOfPages), 0, 0, 'R');
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

    public function addEstadoCuentaPendienteHeader($x, $y)
    {
        $this->SetLineWidth(0.1);
        $this->Rect(10, $y, $this->GetPageWidth()-20, 6, "D");
        $this->SetXY(10, $y);
        $this->SetFont($this->font, 'B', 8);
        $this->Cell($this->GetPageWidth(), 6, "Estado de Cuenta Pendiente de Pago al ".\date('d-m-Y H:i:s'), 0, 0, 'C');
    }

    public function addEstadoCuentaLineasHeader($x, $y)
    {
        $this->SetLineWidth(0.1);
        $this->Rect(10, $y, 190, ($this->max_lines*7), "D");
        $this->Line(10, $y+5, 200, $y+5);
        //Lineas Verticales
        $arrayLineas = [90, 110, 130, 145, 160, 180];
        foreach ($arrayLineas as $lineaX) {
            $this->Line($lineaX, $y, $lineaX, $y+($this->max_lines*7));
        }
        $arrayDatosLineas = array('item' , 'fecha', 'vence', 'importe', 'descto.', 'total', 'atraso');
        $arrayAnchoLineas = [80, 20, 20, 15, 15, 20, 20];
        //Cabeceras
        $this->SetXY($x+10, $y);
        $this->setDocumentInternalLines($arrayDatosLineas, $arrayAnchoLineas, $arrayLineas, $y);
    }

    public function addEstadoCuentaLineasData($documentLines, $pageNumber, $numberOfPages)
    {
        $x = $this->GetX();
        $y = $this->GetY();
        $this->addEstadoCuentaLineasHeader($x, $y);
        $this->SetFont($this->font, '', 7);
        //We calculate where the FOR function must start
        $i = ($pageNumber * $this->max_lines) - ($this->max_lines);
        $linesSubtotal = $this->max_lines;
        //var_dump($i);
        for ($i; $i < ($pageNumber * $this->max_lines); $i++) {
            if (isset($documentLines[$i])) {
                $y += 5;
                $this->SetXY($x+10, $y);
                $item = $documentLines[$i];
                //var_dump($item);
                $this->Cell(80, 5, utf8_decode($item->descripcion), 0, 0, 'L');
                $this->Cell(20, 5, utf8_decode($item->fecha), 0, 0, 'R');
                $this->Cell(20, 5, utf8_decode($item->vencimiento), 0, 0, 'R');
                $numero_formateado = number_format(($item->pvpsindto), FS_NF0, FS_NF1, FS_NF2);
                $this->Cell(15, 5, $numero_formateado, 0, 0, 'R');
                $numero_formateado = number_format(($item->dtopor), FS_NF0, FS_NF1, FS_NF2);
                $this->Cell(15, 5, $numero_formateado, 0, 0, 'R');
                $numero_formateado = number_format($item->pvptotal, FS_NF0, FS_NF1, FS_NF2);
                $this->Cell(20, 5, $numero_formateado, 0, 0, 'R');
                $dias = ($item->dias_atraso !== 0) ? $item->dias_atraso. ' días' : '';
                $this->Cell(20, 5, utf8_decode($dias), 0, 0, 'C');
            }
        }
    }

    public function addEstadoCuentaPendiente($listaDocumentos)
    {
        $x = 10;
        $y = 10;
        if ($listaDocumentos) {
            $numberOfPages = (int) ceil(count($listaDocumentos)/$this->max_lines);
            for ($pageNumber=0; $pageNumber < $numberOfPages; $pageNumber++) {
                $this->AddPage();
                $this->addEstadoCuentaPendienteHeader($x, $y);
                $this->SetY($y+10);
                $this->addEstadoCuentaLineasData($listaDocumentos, $pageNumber+1, $numberOfPages);
            }

        }
    }

    /**
     * @param array $arrayDatosLineas
     * @param array $arrayAnchoLineas
     * @param array $arrayLineas
     * @param $y
     */
    public function setDocumentInternalLines(array $arrayDatosLineas, array $arrayAnchoLineas, array $arrayLineas, $y): void
    {
        $this->SetFont($this->font, 'B', 8);
        $cantidadLineas = count($arrayDatosLineas);
        for ($i = 0; $i < $cantidadLineas; $i++) {
            $this->Cell($arrayAnchoLineas[$i], 5, utf8_decode($arrayDatosLineas[$i]), 0, 0, 'C');
            if (isset($arrayLineas[$i])) {
                $this->SetXY($arrayLineas[$i], $y);
            }
        }
    }
}