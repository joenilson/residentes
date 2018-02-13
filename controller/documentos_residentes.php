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
    public $cliente_residente;
    public $documento;
    public $numpaginas;
    public $pagado;
    public $pendiente;
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
        $this->cliente_residente = $cliente->get($cod);
        $info_accion = filter_input(INPUT_POST, 'info_accion');
        $tipo_documento = filter_input(INPUT_POST, 'tipo_documento');
        if($this->cliente_residente AND $info_accion)
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
        $this->documento = new fs_pdf('letter');
        switch($tipo_documento)
        {
            case 'informacion_cobros':
                $this->documento->pdf->addInfo('Title', 'Pagos Residente ' . $this->cliente_residente->codcliente);
                $this->documento->pdf->addInfo('Subject', 'Pagos del Residente ' . $this->cliente_residente->codcliente);
                $this->documento->pdf->addInfo('Author', $this->empresa->nombre);
                $this->documento->pdf->ezSetMargins(10, 10, 10, 10);
                $this->crear_documento_cobros();
                break;
            default:
                $this->documento = false;
                break;
        }
    }

    public function crear_documento_cobros()
    {
        $this->pendiente = $this->pagosFactura(false);
        $this->pagado = $this->pagosFactura(true);
        
        $linea_actual = 0;
        $pagina = 1;
        while($linea_actual < count($this->pendiente)){
            $lppag = 35; /// líneas por página
            /// salto de página
            if ($linea_actual > 0) {
                $this->documento->pdf->ezNewPage();
            }
            $this->documento->generar_pdf_cabecera($this->empresa, $lppag);
            $this->documento->set_y($this->documento->pdf->y-10);
            $this->generar_pdf_lineas($this->documento, $this->pendiente, $linea_actual, $lppag, 'cobros');
            $this->documento->set_y(80);
            $pagina++;
        }
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
    
    public function generar_pdf_lineas(&$pdf_doc, &$items, &$linea_actual, &$lppag, $tipo)
    {
        /// calculamos el número de páginas
        if (!isset($this->numpaginas)) {
            $this->numpaginas = 0;
            $lineas = 0;
            while ($lineas < count($items)) {
                $lppag2 = $lppag;
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

                $lineas += $lppag2;
                $this->numpaginas++;
            }

            if ($this->numpaginas == 0) {
                $this->numpaginas = 1;
            }
        }
        
        /// leemos las líneas para ver si hay que mostrar los tipos de iva, re o irpf
        foreach ($items as $i => $lin) {
            /// restamos líneas al documento en función del tamaño de la descripción
            if ($i >= $linea_actual && $i < $linea_actual + $lppag) {
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
                    $lppag -= $linea_size - 1;
                }
            }
        }
        
        /*
         * Creamos la tabla con las lineas de pendientes
         */
        $pdf_doc->new_table();
        if($tipo === 'cobros'){
            $table_header = array(
                'item' => '<b>Item.</b>',
                'fecha' => '<b>Fecha</b>',
                'vencimiento' => '<b>Vencimiento</b>',
                'importe' => '<b>Monto</b>',
                'descuento' => '<b>Descuento</b>',
                'total' => '<b>Total</b>',
                'atraso' => '<b>Atraso</b>',
            );
            $array_cols = array(
                'item' => array('justification' => 'left'),
                'fecha' => array('justification' => 'center'),
                'vencimiento' => array('justification' => 'center'),
                'importe' => array('justification' => 'right'),
                'descuento' => array('justification' => 'right'),
                'total' => array('justification' => 'right'),
                'atraso' => array('justification' => 'center')
            );
        }elseif($tipo === 'pagos'){
            $table_header = array(
                'item' => '<b>Item.</b>',
                'fecha' => '<b>Fecha</b>',
                'importe' => '<b>Monto</b>',
                'f_pago' => '<b>F. Pago</b>'
            );
            $array_cols = array(
                'item' => array('justification' => 'left'),
                'fecha' => array('justification' => 'center'),
                'importe' => array('justification' => 'right'),
                'f_pago' => array('justification' => 'center')
            );
        }
        $pdf_doc->add_table_header($table_header);
        
        for ($i = $linea_actual; (($linea_actual < ($lppag + $i)) && ( $linea_actual < count($items)));) {
            $descripcion = fs_fix_html($items[$linea_actual]->descripcion);
            if($tipo === 'cobros'){
                $fila = array(
                    'item' => $descripcion,
                    'fecha' => $items[$linea_actual]->fecha,
                    'vencimiento' => $items[$linea_actual]->vencimiento,
                    'importe' => $this->show_precio($items[$linea_actual]->pvpsindto, $this->empresa->coddivisa, TRUE, FS_NF0),
                    'descuento' => $this->show_numero($items[$linea_actual]->dtopor) . " %",
                    'total' => $this->show_precio($items[$linea_actual]->pvptotal, $this->empresa->coddivisa, TRUE, FS_NF0),
                    'atraso' => $items[$linea_actual]->dias_atraso
                );
            }elseif($tipo === 'cobros'){
                $fila = array(
                    'item' => $descripcion,
                    'fecha' => $items[$linea_actual]->fecha,
                    'importe' => $this->show_precio($items[$linea_actual]->pvptotal, $this->empresa->coddivisa, TRUE, FS_NF0),
                    'atraso' => $items[$linea_actual]->f_pago
                );
            }
            $pdf_doc->add_table_row($fila);
            $linea_actual++;
        }
        
        $pdf_doc->save_table(
            array(
                'fontSize' => 8,
                'cols' => $array_cols,
                'width' => 520,
                'shaded' => 1,
                'shadeCol' => array(0.95, 0.95, 0.95),
                'lineCol' => array(0.3, 0.3, 0.3),
            )
        );
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
