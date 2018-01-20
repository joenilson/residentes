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
require_model('residentes_edificaciones.php');
require_model('residentes_informacion.php');
require_model('residentes_vehiculos.php');
require_model('residentes_edificaciones_tipo.php');
require_model('residentes_edificaciones_mapa.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
/**
 * Description of informe_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_residentes extends fs_controller {

    public $bloque;
    public $desde;
    public $hasta;
    public $resultados;
    public $tipo;
    public $residentes;
    public $edificaciones;
    public $total;
    public $total_resultado;
    public $lista;
    public $vehiculos;
    public $codigo_edificacion;
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $inmuebles_libres;
    public $inmuebles_ocupados;
    public $total_vehiculos;
    public $limit;
    public $offset;
    public $order;
    public $search;
    public $sort;
    public $archivo = 'Residentes';
    public $archivoXLSX;
    public $archivoXLSXPath;
    public $documentosDir;
    public $exportDir;
    public $publicPath;
    public $where_code;
    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'informes', FALSE, TRUE);
    }

    protected function private_core() {
        $this->shared_extensions();
        $this->init_variables();
        $this->init_filters();

        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];

        /// forzamos la comprobación de la tabla residentes
        new residentes_edificaciones();

        $this->tipo = 'informacion';
        if (isset($_GET['tipo'])) {
            $this->tipo = $_GET['tipo'];
        }

        $this->codigo_edificacion = NULL;
        if ($this->filter_request('codigo_edificacion')) {
            $this->codigo_edificacion = $this->filter_request('codigo_edificacion');
        }

        if ($this->codigo_edificacion) {
           $this->where_code =  " AND r.codigo like '".$this->codigo_edificacion."%' ";
        }

        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);

        $this->informacion_edificaciones();

        if($this->filter_request('lista')){
            $this->procesarLista($this->filter_request('lista'));
        }
    }

    public function init_variables()
    {
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $this->edificaciones = new residentes_edificaciones();
        $this->vehiculos = new residentes_vehiculos();
    }

    public function init_filters()
    {
        $this->desde = \Date('01-m-Y');
        if ($this->filter_request('desde')){
            $this->desde = $this->filter_request('desde');
        }

        $this->hasta = \Date('t-m-Y');
        if ($this->filter_request('hasta')){
            $this->hasta = $this->filter_request('hasta');
        }

        $sort = $this->filter_request('sort');
        $order = $this->filter_request('order');
        $this->offset = $this->confirmarValor($this->filter_request('offset'),0);
        $this->limit = $this->confirmarValor($this->filter_request('limit'),FS_ITEM_LIMIT);
        $this->search = $this->confirmarValor($this->filter_request('search'),false);
        $this->sort = ($sort and $sort!='undefined')?$sort:'r.codigo, r.numero';
        $this->order = ($order and $order!='undefined')?$order:'ASC';
    }

    public function informacion_edificaciones(){
        $this->resultado = array();
        list($edificaciones, $inmuebles, $vehiculos, $inmuebles_ocupados) = $this->datosInformacion();
        foreach($edificaciones as $edif){
            $l = new stdClass();
            $l->descripcion = $edif['descripcion'];
            $l->cantidad = $edif['total'];
            $this->resultado[] = $l;
        }
        if($inmuebles){
            $l = new stdClass();
            $l->descripcion = 'Inmueble';
            $l->cantidad = $inmuebles;
            $this->resultado[] = $l;
        }

        //Verificamos los que están ocupados
        $this->inmuebles_libres = $inmuebles-$inmuebles_ocupados;
        $this->inmuebles_ocupados = $inmuebles_ocupados;
        $this->total_vehiculos = $vehiculos;
        $this->carpetasPlugin();
        $this->generarArchivoExcel();
    }

    public function generarArchivoExcel()
    {
        $this->archivoXLSX = $this->exportDir . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".xlsx";
        $this->archivoXLSXPath = $this->publicPath . DIRECTORY_SEPARATOR . $this->archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->archivoXLSX)) {
            unlink($this->archivoXLSX);
        }
        $writer = new XLSXWriter();
        $headerR = array('Código'=>'string','Residente'=>'string',FS_CIFNIF=>'string','Teléfono'=>'string','Email'=>'string','Ubicación'=>'string', 'Inmueble'=>'string','Fecha de Ocupación'=>'date');
        $headerTextR = array('codcliente'=>'Código','nombre'=>'Residente','cifnif'=>FS_CIFNIF,'telefono1'=>'Teléfono','email'=>'Email','codigo'=>'Ubicación','numero'=>'Inmueble','fecha_ocupacion'=>'Fecha Ocupación');
        $dataResidentes = $this->lista_residentes(TRUE);
        $this->crearXLSX($writer, 'Residentes', $headerR, $headerTextR, $dataResidentes[0]);
        $headerI = array('Pertenece'=>'string','Edificación'=>'string','Código'=>'string', 'Residente'=>'string','Ubicación'=>'string','Inmueble Nro'=>'integer','Fecha de Ocupación'=>'date','Ocupado'=>'string');
        $headerTextI = array('padre_desc'=>'Pertenece','edif_desc'=>'Edificacion','codcliente'=>'Código','nombre'=>'Residente','codigo'=>'Ubicación','numero'=>'Inmueble Nro','fecha_ocupacion'=>'Fecha Ocupación','ocupado'=>'Ocupado');
        $dataInmuebles = $this->lista_inmuebles(TRUE);
        $this->crearXLSX($writer, 'Inmuebles', $headerI, $headerTextI, $dataInmuebles[0]);
        $headerC = array('Código'=>'string','Residente'=>'string','Ubicación'=>'string', 'Inmueble'=>'string','Pagado'=>'price','Pendiente'=>'price');
        $headerTextC = array('codcliente'=>'Código','nombre'=>'Residente','codigo'=>'Ubicación','numero'=>'Inmueble','pagado'=>'Pagado','pendiente'=>'Pendiente');
        $dataCobros = $this->lista_cobros(TRUE);
        $this->crearXLSX($writer, 'Cobros', $headerC, $headerTextC, $dataCobros[0]);
        $writer->writeToFile($this->archivoXLSXPath);
        $this->fileXLSX = $this->archivoXLSXPath;
    }

    public function datosInformacion()
    {
        //Cantidad de Edificaciones
        $sql_edificaciones = " select ret.id,ret.descripcion, count(rme.id) as total from residentes_edificaciones_tipo as ret ".
            " join residentes_mapa_edificaciones as rme on (rme.id_tipo = ret.id) ".
            " group by ret.id,ret.descripcion ";
            " order by ret.padre;";
        $data_edificaciones = $this->db->select($sql_edificaciones);
        //cantidad de Inmuebles
        $sql_inmuebles = "select count(*) as total from residentes_edificaciones;";
        $data_inmuebles = $this->db->select($sql_inmuebles);
        //cantidad de Inmuebles Ocupados
        $sql_inmuebles_ocupados = "select count(*) as total from residentes_edificaciones WHERE ocupado = true;";
        $data_inmuebles_ocupados = $this->db->select($sql_inmuebles_ocupados);
        //cantidad de Vehiculos
        $sql_vehiculos = "select count(*) as total from residentes_vehiculos;";
        $data_vehiculos = $this->db->select($sql_vehiculos);

        return array($data_edificaciones, $data_inmuebles[0]['total'],$data_vehiculos[0]['total'],$data_inmuebles_ocupados[0]['total']);
    }

    public function procesarLista($lista)
    {
        $this->template = false;
        switch ($lista) {
            case 'informe_residentes':
                list($resultados, $cantidad) = $this->lista_residentes();
                break;
            case 'informe_inmuebles':
                list($resultados, $cantidad) = $this->lista_inmuebles();
                break;
            case 'informe_cobros':
                list($resultados, $cantidad) = $this->lista_cobros();
                break;
            default:
                break;
        }
        header('Content-Type: application/json');
        $data['rows'] = $resultados;
        $data['total'] = $cantidad;
        echo json_encode($data);
    }

    public function lista_residentes($todo = false)
    {
        $sql = " select r.codcliente, c.nombre, c.cifnif, c.telefono1, c.email, codigo, numero, fecha_ocupacion ".
            " from residentes_edificaciones as r, clientes as c ".
            " where r.codcliente = c.codcliente ".$this->where_code.
            " order by ".$this->sort." ".$this->order;
        if($todo){
            $data = $this->db->select($sql);
        }else{
            $data = $this->db->select_limit($sql, $this->limit, $this->offset);
        }
        $sql_cantidad = "select count(r.id) as total ".
            " from residentes_edificaciones as r ".
            " where r.codcliente != '' ".$this->where_code;
        $data_cantidad = $this->db->select($sql_cantidad);
        return array($data, $data_cantidad[0]['total']);
    }

    public function lista_inmuebles($todo = false)
    {
        $sql = "select r.id, r.codcliente, c.nombre, r.codigo, r.numero, case when r.ocupado then 'Si' else 'NO' end as ocupado, r.fecha_ocupacion, rme.padre_id, ret.descripcion as padre_desc, rme.codigo_padre, ret2.descripcion as edif_desc, codigo_edificacion ".
            " from residentes_edificaciones as r ".
            " join residentes_mapa_edificaciones as rme on (r.id_edificacion = rme.id ".$this->where_code.") ".
            " join residentes_edificaciones_tipo as ret on (rme.padre_tipo = ret.id) ".
            " join residentes_edificaciones_tipo as ret2 on (rme.id_tipo = ret2.id) ".
            " left join clientes as c on (r.codcliente = c.codcliente) ".
            " order by ".$this->sort." ".$this->order;
        if($todo){
            $data = $this->db->select($sql);
        }else{
            $data = $this->db->select_limit($sql, $this->limit, $this->offset);
        }

        $sql_cantidad = "select count(r.id) as total ".
            " from residentes_edificaciones as r";
        if($this->where_code){
            $sql_cantidad.=" WHERE r.codigo!='' ".$this->where_code;
        }
        $data_cantidad = $this->db->select($sql_cantidad);
        return array($data, $data_cantidad[0]['total']);
    }

    public function lista_cobros($todo = false)
    {
        $sql = "select r.codcliente, c.nombre, r.codigo, r.numero, sum(f1.total) as pagado, sum(f2.total) as pendiente ".
            " from residentes_edificaciones as r ".
            " join clientes as c on (r.codcliente = c.codcliente ".$this->where_code.") ".
            " left join facturascli as f1 on (r.codcliente = f1.codcliente and f1.anulada = false and f1.pagada = true) ".
            " left join facturascli as f2 on (r.codcliente = f2.codcliente and f2.anulada = false and f2.pagada = false) ".
            " group by r.codcliente, c.nombre, r.codigo, r.numero ".
            " order by ".$this->sort." ".$this->order;
        if($todo){
            $data = $this->db->select($sql);
        }else{
            $data = $this->db->select_limit($sql, $this->limit, $this->offset);
        }

        $sql_cantidad = "select count(r.id) as total ".
            " from residentes_edificaciones as r".
            " where r.codcliente != '' ".$this->where_code;
        $data_cantidad = $this->db->select($sql_cantidad);
        return array($data, $data_cantidad[0]['total']);
    }

    public function informacion_interna($id){
        $lista_tipo = $this->edificaciones_tipo->get_by_field('padre', $id);
        if($lista_tipo){
            foreach($lista_tipo as $linea){
                $l = new stdClass();
                $l->descripcion = $linea->descripcion;
                $l->cantidad = count($this->edificaciones_mapa->get_by_field('id_tipo', $linea->id));
                $this->resultado[] = $l;
                $this->total_resultado++;
                $this->informacion_interna($linea->id);
            }
        }else{
            return true;
        }
    }

    /**
     * Función para devolver un valor u otro dependiendo si está presente
     * el primer valor y si la variable existe
     * @param string $variable
     * @param string $valor_si
     * @param string $valor_no
     * @return string
     */
    public function setValor($variable, $valor_si, $valor_no)
    {
        $valor = $valor_no;
        if(!empty($variable) and ($variable == $valor_si)){
            $valor = $valor_si;
        }
        return $valor;
    }

    /**
     * Función para devolver el valor que no esté vacio
     * @param string $valor1
     * @param string $valor2
     * @return string
     */
    public function confirmarValor($valor1, $valor2)
    {
        $valor = $valor2;
        if(!empty($valor1)){
            $valor = $valor1;
        }
        return $valor;
    }

    /**
     * Función para devolver el valor de una variable pasada ya sea por POST o GET
     * @param type string
     * @return type string
     */
    public function filter_request($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre);
        $nombre_get = \filter_input(INPUT_GET, $nombre);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function filter_request_array($nombre)
    {
        $nombre_post = \filter_input(INPUT_POST, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $nombre_get = \filter_input(INPUT_GET, $nombre, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }

    public function crearXLSX(&$writer, $hoja_nombre, $header,$headerText, $data)
    {
        $style_header = array('border'=>'left,right,top,bottom','font'=>'Arial','font-size'=>10,'font-style'=>'bold');
        $writer->writeSheetRow($hoja_nombre, $headerText, $style_header);
        $writer->writeSheetHeader($hoja_nombre, $header, true);
        $this->agregarDatosXLSX($writer, $hoja_nombre, $data, $headerText);
    }

    public function agregarDatosXLSX(&$writer, $hoja_nombre, $datos, $indice)
    {
        $total_importe = 0;
        if($datos){
            foreach($datos as $linea){
                $data = $this->prepararDatosXLSX($linea, $indice, $total_importe);
                $writer->writeSheetRow($hoja_nombre, $data);
            }
        }
    }

    public function prepararDatosXLSX($linea, $indice, &$total_importe)
    {
        $item = array();
        foreach($indice as $idx=>$desc){
            $item[] = $linea[$idx];
            if($idx == 'total'){
                $total_importe += $linea['total'];
            }
        }
        return $item;
    }

    public function carpetasPlugin()
    {
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->exportDir = $this->documentosDir . DIRECTORY_SEPARATOR . "informes_residentes";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'informes_residentes';
        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir);
        }
    }

    public function url() {
        if (isset($_REQUEST['inmueble'])) {
            return 'index.php?page=informe_residentes&inmueble=' . $_REQUEST['inmueble'];
        } else
            return parent::url();
    }

    private function str2bool($v) {
        return ($v == 't' OR $v == '1');
    }

    public function shared_extensions() {
        $extensiones = array(
            array(
                'name' => '001_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/1/bootstrap-table.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/1/bootstrap-table-locale-all.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-filter.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-toolbar.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/residentes/view/js/plugins/bootstrap-table-mobile.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '009_informe_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'view/js/chart.bundle.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '001_informe_edificaciones_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/residentes/view/css/bootstrap-table.min.css"/>',
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
