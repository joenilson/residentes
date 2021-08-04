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
//require_model('cliente.php');
//require_model('residentes_informacion.php');
//require_model('residentes_ayudas.php');
/**
 * Controller para el tab de información adicional de los resodientes
 * Va agregado en Ventas > Clientes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class clientes_informacion extends \fs_controller
{
    /**
     * @var boolean
     */
    public $allow_delete;
    /**
     * @var string
     */
    public $codcliente;
    /**
     * @var string|\FacturaScripts\model\cliente
     */
    public $cliente;
    /**
     * @var array|\FacturaScripts\model\cliente
     */
    public $clientes;
    /**
     * @var object|array
     */
    public $cliente_info;
    /**
     * @var object|array
     */
    public $residentes_informacion;
    /**
     * @var object|array
     */
    public $residentes_ayudas;
    /**
     * @var object|array|boolean
     */
    public $lista_parentesco;
    /**
     * @var object|array|boolean
     */
    public $lista_ocupacion;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Informacion Residente', 'residentes', false, false, false);
    }

    protected function private_core()
    {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->clientes = new cliente();
        $this->residentes_informacion = new residentes_informacion();
        $this->residentes_ayudas = new residentes_ayudas();
        $this->lista_ocupacion = $this->residentes_ayudas->all_tipo('OCUPACION');
        $this->lista_parentesco = $this->residentes_ayudas->all_tipo('PARENTESCO');

        $accion = \filter_input(INPUT_POST, 'accion');
        if ($accion === 'agregar') {
            $codcliente = \filter_input(INPUT_POST, 'codcliente');
            $codigo = $this->clean_text(\filter_input(INPUT_POST, 'codigo'));
            $ocupantes = \filter_input(INPUT_POST, 'ocupantes');
            $ocupantes5anos = \filter_input(INPUT_POST, 'ocupantes5anos');
            $ocupantes12anos = \filter_input(INPUT_POST, 'ocupantes12anos');
            $ocupantes18anos = \filter_input(INPUT_POST, 'ocupantes18anos');
            $ocupantes30anos = \filter_input(INPUT_POST, 'ocupantes30anos');
            $ocupantes50anos = \filter_input(INPUT_POST, 'ocupantes50anos');
            $ocupantes70anos = \filter_input(INPUT_POST, 'ocupantes70anos');
            $ocupantes71anos = \filter_input(INPUT_POST, 'ocupantes71anos');
            $informacion_discapacidad = $this->clean_text(\filter_input(INPUT_POST, 'informacion_discapacidad'));
            $propietario = \filter_input(INPUT_POST, 'propietario');
            $profesion = $this->clean_text(\filter_input(INPUT_POST, 'profesion'));
            $ocupacion = \filter_input(INPUT_POST, 'ocupacion');
            $ca_nombres = $this->clean_text(\filter_input(INPUT_POST, 'ca_nombres'));
            $ca_apellidos = $this->clean_text(\filter_input(INPUT_POST, 'ca_apellidos'));
            $ca_telefono = $this->clean_text(\filter_input(INPUT_POST, 'ca_telefono'));
            $ca_email = strtolower(trim(\filter_input(INPUT_POST, 'ca_email')));
            $ca_propietario = \filter_input(INPUT_POST, 'ca_propietario');
            $ca_parentesco = \filter_input(INPUT_POST, 'ca_parentesco');
            $ca_parentesco_obs = $this->clean_text(\filter_input(INPUT_POST, 'ca_parentesco_obs'));
            $vehiculos = \filter_input(INPUT_POST, 'vehiculos');
            $cliinfo = new residentes_informacion();
            $cliinfo->codcliente=$codcliente;
            $cliinfo->codigo=$codigo;
            $cliinfo->ocupantes=$ocupantes;
            $cliinfo->ocupantes5anos=$ocupantes5anos;
            $cliinfo->ocupantes12anos=$ocupantes12anos;
            $cliinfo->ocupantes18anos=$ocupantes18anos;
            $cliinfo->ocupantes30anos=$ocupantes30anos;
            $cliinfo->ocupantes50anos=$ocupantes50anos;
            $cliinfo->ocupantes70anos=$ocupantes70anos;
            $cliinfo->ocupantes71anos=$ocupantes71anos;
            $cliinfo->informacion_discapacidad= $informacion_discapacidad;
            $cliinfo->propietario= (bool)$propietario;
            $cliinfo->profesion= $profesion;
            $cliinfo->ocupacion= $ocupacion;
            $cliinfo->ca_nombres= $ca_nombres;
            $cliinfo->ca_apellidos= $ca_apellidos;
            $cliinfo->ca_telefono= $ca_telefono;
            $cliinfo->ca_email= $ca_email;
            $cliinfo->ca_propietario= (bool)$ca_propietario;
            $cliinfo->ca_parentesco= $ca_parentesco;
            $cliinfo->ca_parentesco_obs= $ca_parentesco_obs;
            $cliinfo->vehiculos= $vehiculos;
            if ($cliinfo->save()) {
                $this->new_message('¡Información actualizada correctamente!');
            } else {
                $this->new_error_msg('Ocurrió un error al actualizar la información por favor revise los ".
                "datos ingresados e intente nuevamente.');
            }
        }

        $codcliente = \filter_input(INPUT_GET, 'cod');
        if (!empty($codcliente)) {
            $this->codcliente = $codcliente;
            $this->cliente = $this->clientes->get($codcliente);
            $cliente_info = $this->residentes_informacion->get($this->codcliente);
            $this->cliente_info = ($cliente_info) ?: new residentes_informacion();
        }
    }

    public function clean_text($text)
    {
        return strtoupper(htmlentities(strip_tags(trim($text))));
    }

    private function shared_extensions()
    {
        $extensiones = array(
            array(
                'name' => 'informacion_residente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                'text' => '<span class="fa fa-building" aria-hidden="true"></span>&nbsp;Residente Info',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
