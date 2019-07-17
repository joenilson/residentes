<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('articulo.php');
require_model('asiento_factura.php');
require_model('familia.php');
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('impuesto.php');
require_model('residentes_edificaciones.php');

/**
 * Description of ver_residente
 *
 * @author carlos
 */
class ver_residente extends fs_controller {

    public $cliente;
    public $cliente_data;
    public $articulos;
    public $articulos_cobrables;
    public $familia;
    public $familias;
    public $facturas;
    public $impuesto;
    public $residente;
    public $forma_pago;
    public function __construct() 
    {
        parent::__construct(__CLASS__, 'Residente', 'residentes', FALSE, FALSE);
    }
    
    public function initVars() 
    {
        $this->cliente = new cliente();
        $this->facturas = array();
        $this->impuesto = new impuesto();
        $this->forma_pago = new forma_pago();
        $this->residente = FALSE;
        $this->articulos = new articulo();
        $this->familias = new familia();
        $this->familia = $this->familias->get('RESIDENT');
    }

    protected function private_core() 
    {
        $this->initVars();

        if (\filter_input(INPUT_GET, 'id')) {
            $inq0 = new residentes_edificaciones();
            $this->residente = $inq0->get(\filter_input(INPUT_GET, 'id'));
            $this->cliente_data = $this->cliente->get($this->residente->codcliente);
        }
        
        if ($this->residente) {
            $accion = \filter_input(INPUT_POST, 'accion');
            if ($accion == 'generar_factura') {
                $this->nueva_factura();
            }
            
            $this->informacionResidente();
            
        } else {
            $this->new_error_msg('Residente no encontrado.');
        }
    }

    public function url() 
    {
        if (\filter_input(INPUT_GET, 'id')) {
            return $this->residente->url();
        } else
            return parent::url();
    }
    
    public function informacionResidente()
    {
        $this->page->title = 'Residente ' . $this->residente->nombre;
        $factura = new factura_cliente();
        $facts = $factura->all_from_cliente($this->residente->codcliente);
        $this->facturas = array();
        $articulos_cobrados = array();
        foreach ($facts as $fac) {
            $fac->referencias = "";
            foreach ($fac->get_lineas() as $linea) {
                if ($linea->referencia) {
                    $fac->referencias .= $linea->referencia . " ";
                    $articulos_cobrados[$linea->referencia] = 1;
                } else {
                    $fac->referencias .= $linea->descripcion . " ";
                }
            }
            $this->facturas[] = $fac;
        }
        $this->generarArticulosCobrables($articulos_cobrados);
    }
    
    public function generarArticulosCobrables($articulos_cobrados)
    {
        foreach($this->familia->get_articulos() as $art) {
            if(!isset($articulos_cobrados[$art->referencia]) AND $art->bloqueado == 0) {
                $this->articulos_cobrables[] = $art;
            }
        }
    }

    private function nueva_factura() 
    {
        $cliente = $this->cliente->get($this->residente->codcliente);
        if ($cliente) {
            $factura = new factura_cliente();
            $factura->codserie = ($cliente->codserie) ? $cliente->codserie : $this->empresa->codserie;
            $factura->codagente = $this->user->codagente;
            $factura->codpago = \filter_input(INPUT_POST, 'forma_pago');
            $factura->codalmacen = $this->empresa->codalmacen;
            $factura->set_fecha_hora($this->today(), $this->hour());
            $eje0 = new ejercicio();
            $ejercicio = $eje0->get_by_fecha(date('d-m-Y'));
            if ($ejercicio) {
                $factura->codejercicio = $ejercicio->codejercicio;
            }
            if ($this->empresa->contintegrada) {
                /// forzamos crear la subcuenta
                $cliente->get_subcuenta($this->empresa->codejercicio);
            }
            
            $forma_pago = $this->forma_pago->get($factura->codpago);
            if ($forma_pago->genrecibos == 'Pagados') {
                $factura->pagada = TRUE;
            }

            $div0 = new divisa();
            $divisa = $div0->get($cliente->coddivisa);
            if ($divisa) {
                $factura->coddivisa = $divisa->coddivisa;
                $factura->tasaconv = $divisa->tasaconv;
            }

            $factura->codcliente = $cliente->codcliente;
            foreach ($cliente->get_direcciones() as $d) {
                if ($d->domfacturacion) {
                    $factura->codcliente = $cliente->codcliente;
                    $factura->cifnif = $cliente->cifnif;
                    $factura->nombrecliente = $cliente->nombre;
                    $factura->apartado = $d->apartado;
                    $factura->ciudad = $d->ciudad;
                    $factura->coddir = $d->id;
                    $factura->codpais = $d->codpais;
                    $factura->codpostal = $d->codpostal;
                    $factura->direccion = $d->direccion;
                    $factura->provincia = $d->provincia;
                    break;
                }
            }
            /// función auxiliar para implementar en los plugins que lo necesiten
            if (!fs_generar_numero2($factura)) {
                $factura->numero2 = '';
            }
            if ($factura->save()) {
                $art0 = new articulo();
                $lineas = \filter_input(INPUT_POST, 'numlineas');
                for ($x = 0; $x < $lineas; $x++) {
                    $referencia = \filter_input(INPUT_POST, 'referencia_' . $x);
                    $importe = \filter_input(INPUT_POST, 'importe_' . $x);
                    $impuesto = \filter_input(INPUT_POST, 'impuesto_' . $x);
                    $art = $art0->get($referencia);
                    if (floatval($importe)) {
                        $linea = new linea_factura_cliente();
                        $linea->idfactura = $factura->idfactura;
                        $linea->referencia = $referencia;
                        $linea->descripcion = ($art) ? $art->descripcion : $referencia . ' Articulo libre';
                        $linea->cantidad = 1;
                        $imp = $this->impuesto->get($impuesto);
                        if ($imp) {
                            $linea->codimpuesto = $imp->codimpuesto;
                            $linea->iva = $imp->iva;
                            $linea->pvpsindto = $importe;
                            $linea->pvpunitario = $importe;
                            $linea->pvptotal = $linea->pvpunitario * $linea->cantidad;
                            if ($linea->save()) {
                                $factura->neto += $linea->pvptotal;
                                $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                            }
                        }
                    }
                }

                if ($_POST['desc_otro'] != '') {
                    $linea = new linea_factura_cliente();
                    $linea->idfactura = $factura->idfactura;
                    $linea->descripcion = $_POST['desc_otro'];
                    $linea->cantidad = 1;
                    $imp = $this->impuesto->get($_POST['impuesto_otro']);
                    if ($imp) {
                        $linea->codimpuesto = $imp->codimpuesto;
                        $linea->iva = $imp->iva;
                        $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario = (100 * floatval($_POST['otro'])) / (100 + $imp->iva);

                        if ($_POST['ref_otro']) {
                            $articulo = $art0->get($_POST['ref_otro']);
                            if ($art0) {
                                $linea->referencia = $articulo->referencia;
                                $articulo->sum_stock($this->empresa->codalmacen, -1);
                            }
                        }

                        if ($linea->save()) {
                            $factura->neto += $linea->pvptotal;
                            $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                        }
                    }
                }

                if ($_POST['desc_otro2'] != '') {
                    $linea = new linea_factura_cliente();
                    $linea->idfactura = $factura->idfactura;
                    $linea->descripcion = $_POST['desc_otro2'];
                    $linea->cantidad = 1;
                    $imp = $this->impuesto->get($_POST['impuesto_otro2']);
                    if ($imp) {
                        $linea->codimpuesto = $imp->codimpuesto;
                        $linea->iva = $imp->iva;
                        $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario = (100 * floatval($_POST['otro2'])) / (100 + $imp->iva);

                        if ($_POST['ref_otro2']) {
                            $articulo = $art0->get($_POST['ref_otro2']);
                            if ($art0) {
                                $linea->referencia = $articulo->referencia;
                                $articulo->sum_stock($this->empresa->codalmacen, -1);
                            }
                        }

                        if ($linea->save()) {
                            $factura->neto += $linea->pvptotal;
                            $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                        }
                    }
                }

                /// redondeamos
                $factura->neto = round($factura->neto, FS_NF0);
                $factura->totaliva = round($factura->totaliva, FS_NF0);
                $factura->totalirpf = round($factura->totalirpf, FS_NF0);
                $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
                $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

                if (abs(floatval($_POST['total_importe']) - $factura->total) > .01) {
                    $this->new_error_msg("El total difiere entre la vista y el controlador (" . $_POST['total_importe'] .
                            " frente a " . $factura->total . "). Debes informar del error.");
                    $factura->delete();
                } else if ($factura->save()) {
                    $this->generar_asiento($factura);
                    /// Función de ejecución de tareas post guardado correcto de la factura
                    fs_documento_post_save($factura);
                    $this->new_message("<a href='" . $factura->url() . "'>Factura</a> guardada correctamente.");
                    $this->new_change('Factura Cliente ' . $factura->codigo, $factura->url(), TRUE);
                } else
                    $this->new_error_msg("¡Imposible actualizar la <a href='" . $factura->url() . "'>Factura</a>!");
            } else
                $this->new_error_msg('Imposible guardar la factura.');
        } else
            $this->new_error_msg('Cliente no encontrado.');
    }

    /**
     * Genera el asiento para la factura, si procede
     * @param factura_cliente $factura
     */
    private function generar_asiento(&$factura) {
        if ($this->empresa->contintegrada) {
            $asiento_factura = new asiento_factura();
            $asiento_factura->generar_asiento_venta($factura);
        } else {
            /// de todas formas forzamos la generación de las líneas de iva
            $factura->get_lineas_iva();
        }
    }

    private function buscar_referencia() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $articulo = new articulo();
        $json = array();
        foreach ($articulo->search($_REQUEST['buscar_referencia']) as $art) {
            $json[] = array(
                'value' => $art->referencia,
                'data' => $art->referencia,
                'pvpi' => $art->pvp_iva(FALSE),
                'codimpuesto' => $art->codimpuesto,
                'descripcion' => $art->descripcion
            );
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_referencia'], 'suggestions' => $json));
    }

}
