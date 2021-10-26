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
require_once 'plugins/residentes/extras/residentes_controller.php';

/**
 * Description of ver_residente
 *
 * @author carlos
 */
class ver_residente extends residentes_controller
{
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
    public $lista_notas;
    public $total_facturas = 0;
    public $offset = 0;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Residente', 'residentes', false, false);
    }
    
    public function initVars()
    {
        $this->cliente = new cliente();
        $this->facturas = array();
        $this->impuesto = new impuesto();
        $this->forma_pago = new forma_pago();
        $this->residente = false;
        $this->articulos = new articulo();
        $this->familias = new familia();
        $this->familia = $this->familias->get('RESIDENT');
    }

    protected function private_core()
    {
        $this->initVars();

        if (\filter_input(INPUT_GET, 'id')) {
            $res0 = new residentes_edificaciones();
            $this->residente = $res0->get(\filter_input(INPUT_GET, 'id'));
            $this->cliente_data = $this->cliente->get($this->residente->codcliente);
            $this->cliente_data->codcontacto = '';
            $this->total_facturas = $this->residente->totalFacturas();
            $this->offset = \filter_input(INPUT_GET, 'offset') ?? 0;
            if (class_exists('contacto_cliente')) {
                $concli = new contacto_cliente();
                $infoCRM = $concli->all_from_cliente($this->residente->codcliente);
                $this->cliente_data->codcontacto = $infoCRM[0]->codcontacto;
            }
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
        }
        return parent::url();
    }
    
    public function informacionResidente()
    {
        $this->page->title = 'Residente ' . $this->residente->nombre;
        $factura = new factura_cliente();
        $totalFacturas = $this->residente->totalFacturas();
        $cantidadGrupos = ceil($totalFacturas/FS_ITEM_LIMIT);
        $facts = [];
        for($i = 0; $i < $cantidadGrupos; $i++ ) {
            $documentos = $factura->all_from_cliente($this->residente->codcliente, FS_ITEM_LIMIT * $i);
            $facts = array_merge($facts, $documentos);
        }
        $this->facturas = array();
        $articulosCobrados = array();
        foreach ($facts as $fac) {
            if(!$fac->anulada) {
                $fac->referencias = "";
                foreach ($fac->get_lineas() as $linea) {
                    if ($linea->referencia) {
                        $fac->referencias .= $linea->referencia . " ";
                        $this->validarArticulos($articulosCobrados, $fac, $linea);
                    } else {
                        $fac->referencias .= $linea->descripcion . " ";
                    }
                }
                $this->facturas[] = $fac;
            }
        }
        $this->generarArticulosCobrables($articulosCobrados);
    }
    
    public function validarArticulos(&$articulosCobrados, &$fac, &$linea)
    {
        if (!$fac->idfacturarect) {
            $rectificativas = $fac->get_rectificativas();
            $articulosDevueltos = array();
            $this->validarDevoluciones($articulosDevueltos, $rectificativas);
            
            if (!isset($articulosDevueltos[$linea->referencia])) {
                $articulosCobrados[$linea->referencia] = 1;
            }
        }
    }
    
    public function validarDevoluciones(&$articulosDevueltos, $rectificativas)
    {
        foreach ($rectificativas as $rectificativa) {
            $lineas_r = $rectificativa->get_lineas();
            foreach ($lineas_r as $linea_r) {
                $articulosDevueltos[$linea_r->referencia] = 1;
            }
        }
    }
    
    public function generarArticulosCobrables($articulos_cobrados)
    {
        $articulos = $this->familia->get_articulos(0, 1000);
        foreach ($articulos as $art) {
            if (isset($articulos_cobrados[$art->referencia]) === false && !$art->bloqueado) {
                $this->articulos_cobrables[] = $art;
            }
        }
    }

    private function nueva_factura()
    {
        $cliente = $this->cliente->get($this->residente->codcliente);
        if ($cliente) {
            $factura = new factura_cliente();
            
            $this->cabeceraFactura($factura, $cliente);
            
            $this->datosClienteFactura($factura, $cliente);
            
            /// función auxiliar para implementar en los plugins que lo necesiten
            if (!fs_generar_numero2($factura)) {
                $factura->numero2 = '';
            }
            if ($factura->save()) {
                $this->lineasFactura($factura);

                $this->lineasLibresFactura($factura, 'otro');
                $this->lineasLibresFactura($factura, 'otro2');

                $this->totalFactura($factura);
            } else {
                $this->new_error_msg('Imposible guardar la factura.');
            }
        } else {
            $this->new_error_msg('Cliente no encontrado.');
        }
    }
    
    private function cabeceraFactura(&$factura, $cliente)
    {
        $factura->codserie = ($cliente->codserie) ?: $this->empresa->codserie;
        $factura->codagente = $this->user->codagente;
        $factura->codpago = \filter_input(INPUT_POST, 'forma_pago');
        $factura->codalmacen = $this->empresa->codalmacen;
        $factura->set_fecha_hora($this->today(), $this->hour());
        $factura->observaciones = htmlentities(trim(\filter_input(INPUT_POST, 'observaciones')));
        $this->datosContabilidadFactura($factura, $cliente);
        
        $div0 = new divisa();
        $divisa = $div0->get($cliente->coddivisa);
        if ($divisa) {
            $factura->coddivisa = $divisa->coddivisa;
            $factura->tasaconv = $divisa->tasaconv;
        }
    }
    
    private function datosContabilidadFactura(&$factura, $cliente)
    {
        $eje0 = new ejercicio();
        $ejercicio = $eje0->get_by_fecha(date('d-m-Y'));
        if ($ejercicio) {
            $factura->codejercicio = $ejercicio->codejercicio;
        }
        if ($this->empresa->contintegrada) {
            $cliente->get_subcuenta($this->empresa->codejercicio);
        }

        $forma_pago = $this->forma_pago->get($factura->codpago);
        if ($forma_pago->genrecibos === 'Pagados') {
            $factura->pagada = true;
        }
    }
    
    private function datosClienteFactura(&$factura, $cliente)
    {
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
    }
    
    private function lineasFactura(&$factura)
    {
        $art0 = new articulo();
        $lineas = \filter_input(INPUT_POST, 'numlineas');
        for ($x = 0; $x < $lineas; $x++) {
            $referencia = \filter_input(INPUT_POST, 'referencia_' . $x);
            $importe = \filter_input(INPUT_POST, 'importe_' . $x);
            $impuesto = \filter_input(INPUT_POST, 'impuesto_' . $x);
            $art = $art0->get($referencia);
            if ((float)$importe) {
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
    }
    
    private function lineasLibresFactura(&$factura, $linea_nombre)
    {
        if (\filter_input(INPUT_POST, 'desc_'.$linea_nombre) !== '') {
            $art0 = new articulo();
            $linea = new linea_factura_cliente();
            $linea->idfactura = $factura->idfactura;
            $linea->descripcion = \filter_input(INPUT_POST, 'desc_'.$linea_nombre);
            $linea->cantidad = 1;
            $imp = $this->impuesto->get(\filter_input(INPUT_POST, 'impuesto_'.$linea_nombre));
            if ($imp) {
                $linea->codimpuesto = $imp->codimpuesto;
                $linea->iva = $imp->iva;
                $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario =
                    (100 * (float)\filter_input(INPUT_POST, $linea_nombre)) / (100 + $imp->iva);
                
                $articulo = (\filter_input(INPUT_POST, 'ref_'.$linea_nombre))
                    ? $art0->get(\filter_input(INPUT_POST, 'ref_'.$linea_nombre))
                    : '';
                if ($articulo !== '') {
                    $linea->referencia = $articulo->referencia;
                    $articulo->sum_stock($this->empresa->codalmacen, -1);
                }
                if ($linea->save()) {
                    $factura->neto += $linea->pvptotal;
                    $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                }
            }
        }
    }
    
    private function totalFactura(&$factura)
    {
        /// redondeamos
        $factura->neto = round($factura->neto, FS_NF0);
        $factura->totaliva = round($factura->totaliva, FS_NF0);
        $factura->totalirpf = round($factura->totalirpf, FS_NF0);
        $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
        $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

        if (abs((float)(\filter_input(INPUT_POST, 'total_importe')) - $factura->total) > .01) {
            $this->new_error_msg("El total difiere entre la vista y el controlador (" .
                \filter_input(INPUT_POST, 'total_importe') .
                " frente a " . $factura->total . "). Debes informar del error.");
            $factura->delete();
        } elseif ($factura->save()) {
            $this->generar_asiento($factura);
            /// Función de ejecución de tareas post guardado correcto de la factura
            fs_documento_post_save($factura);
            $this->new_message("<a href='" . $factura->url() . "'>Factura</a> guardada correctamente.");
            $this->new_change('Factura Cliente ' . $factura->codigo, $factura->url(), true);
        } else {
            $this->new_error_msg("¡Imposible actualizar la <a href='" . $factura->url() . "'>Factura</a>!");
        }
    }

    /**
     * Genera el asiento para la factura, si procede
     * @param factura_cliente $factura
     */
    private function generar_asiento(&$factura)
    {
        if ($this->empresa->contintegrada) {
            $asiento_factura = new asiento_factura();
            $asiento_factura->generar_asiento_venta($factura);
        } else {
            /// de todas formas forzamos la generación de las líneas de iva
            $factura->get_lineas_iva();
        }
    }

    private function buscar_referencia()
    {
        /// desactivamos la plantilla HTML
        $this->template = false;

        $articulo = new articulo();
        $json = array();
        foreach ($articulo->search($_REQUEST['buscar_referencia']) as $art) {
            $json[] = array(
                'value' => $art->referencia,
                'data' => $art->referencia,
                'pvpi' => $art->pvp_iva(false),
                'codimpuesto' => $art->codimpuesto,
                'descripcion' => $art->descripcion
            );
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_referencia'], 'suggestions' => $json), JSON_THROW_ON_ERROR);
    }

    public function paginas()
    {
        $url = $this->url();

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        /// añadimos todas la página
        while ($num < $this->total_facturas) {
            $paginas[$i] = array(
                'url' => $url . "&offset=" . ($i * FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num == $this->offset)
            );

            if ($num == $this->offset) {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach ($paginas as $j => $value) {
            $enmedio = (int)($i / 2);

            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j>1 && $j<$actual-5 && $j !== $enmedio) || ($j > $actual + 5 && $j < $i - 1 && $j !== $enmedio)) {
                unset($paginas[$j]);
            }
        }

        if (count($paginas) > 1) {
            return $paginas;
        } else {
            return array();
        }
    }
}
