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
require_once 'plugins/residentes/extras/residentesFacturaDetallada.php';
require_once 'plugins/residentes/extras/residentesEstadoCuenta.php';
require_once 'plugins/residentes/extras/residentesEnviarMail.php';

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
        if ($facturas !== null) {
            $this->getFacturaProgramadaPendiente($facturas);
        }
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

    public function crearEstadoCuenta()
    {
        $info_accion = $this->filter_request('info_accion');
        $estadoCuentaGenerador = new ResidentesEstadoCuenta(
            $this->empresa,
            $this->cliente_residente,
            $this->user,
            $this->archivo,
            $info_accion
        );
        $pendientes = $this->pagosFactura();
        $pagado = $this->pagosFactura(true);
        $estadoCuentaGenerador->crearEstadoCuenta($pendientes, $pagado);
    }

    public function crearFacturaDetallada()
    {
        $infoAccion = $this->filter_request('info_accion');
        $this->documento = new residentesFacturaDetallada('L', 'mm', 'A5', $infoAccion, $this->archivo, $this->user);
        $this->documento->crearFactura($this->empresa, $this->factura, $this->cliente_residente);
    }

    /**
     * @throws phpmailerException
     */
    public function enviar_documento($tipo_documento)
    {
        $this->crear_documento($tipo_documento);
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
}
