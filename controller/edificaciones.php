<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
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
require_model('cliente.php');
require_model('residentes_edificaciones.php');
require_model('residentes_edificaciones_tipo.php');
require_model('residentes_edificaciones_mapa.php');
/**
 * Description of edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class edificaciones extends fs_controller{
    public $edificaciones;
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $allow_delete;
    public $nombre_edificacion;
    public $residentes_setup;
    public $mapa;
    public $padre_interior;
    public $padre_inmuebles;
    public $lista_interior;
    public $lista_inmuebles;
    public function __construct() {
        parent::__construct(__CLASS__, 'Edificaciones', 'residentes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->shared_extensions();
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $this->edificaciones = new residentes_edificaciones();

        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];

        $accion = filter_input(INPUT_POST, 'accion');
        switch ($accion){
            //Este case solo es temporal, cuando se tenga el plugin estable se eliminará
            case "fixdb":
                $sql1 = "DROP TABLE residentes_mapa_edificaciones;";
                $this->db->exec($sql1);
                $sql2 = "DROP TABLE residentes_edificaciones;";
                $this->db->exec($sql2);
                new residentes_edificaciones();
                new residentes_edificaciones_mapa();
                $this->new_message('¡Tablas eliminadas y creadas nuevamente!');
                break;
            case "agregar":
                $this->tratar_edificaciones();
                break;
            case "agregar_inmueble":
                $this->agregar_inmueble();
                break;
            case "agregar_residente":
                $this->agregar_residente();
                break;
            case "agregar_hijo":
                $id = \filter_input(INPUT_POST, 'id_hijo');
                $objeto = $this->edificaciones_tipo->get($id);
                $this->agregar($objeto);
                break;
            case "tratar_tipo":
                $this->tratar_tipo_edificaciones();
                break;
            case "cambiar_datos":
                $nombre = filter_input(INPUT_POST, 'nombre_edificacion');
                $residentes_config = array(
                    'residentes_nombre_edificacion' => trim($nombre)
                );
                $this->fsvar->array_save($residentes_config);
            default:
                break;
        }

        $tipo = $accion = \filter_input(INPUT_GET, 'type');
        if($tipo=='select-hijos'){
            $this->obtener_hijos();
        }

        $this->fsvar = new fs_var();
        //Aqui almacenamos las variables del plugin
        $this->residentes_setup = $this->fsvar->array_get(
            array(
            'residentes_nombre_edificacion' => 'Inmueble',
            ), FALSE
        );

        $this->nombre_edificacion = $this->residentes_setup['residentes_nombre_edificacion'];

        if(isset($_REQUEST['busqueda'])){
            if($_REQUEST['busqueda']=='arbol_tipo_edificaciones'){
                $this->buscar_tipo_edificaciones();
            }
        }
        $interior = \filter_input(INPUT_GET, 'interior');
        if($interior){
            $this->padre_interior = $this->edificaciones_mapa->get($interior);
            $this->lista_interior = $this->edificaciones_mapa->get_by_field('padre_id', $interior);
        }
        $inmuebles = \filter_input(INPUT_GET, 'inmuebles');
        if($inmuebles){
            $this->padre_inmuebles = $this->edificaciones_mapa->get($inmuebles);
            $this->lista_inmuebles = $this->edificaciones->get_by_field('id_edificacion', $inmuebles);
        }

        if(\filter_input(INPUT_GET, 'buscar_cliente')){
            $this->buscar_cliente();
        }

        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);
    }

    public function url(){
        $interior = \filter_input(INPUT_GET, 'interior');
        $inmuebles = \filter_input(INPUT_GET, 'inmuebles');
        if($interior){
            return 'index.php?page='.__CLASS__.'&interior='.$interior;
        }elseif($inmuebles){
            return 'index.php?page='.__CLASS__.'&inmuebles='.$inmuebles;
        }else{
            return 'index.php?page='.__CLASS__;
        }
    }

    public function parent_url(){
        return 'index.php?page='.__CLASS__;
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $cliente = new cliente();
        $json = array();
        foreach ($cliente->search(\filter_input(INPUT_GET,'buscar_cliente')) as $cli) {
            $json[] = array('value' => $cli->razonsocial, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => \filter_input(INPUT_GET,'buscar_cliente'), 'suggestions' => $json));
    }

    public function buscar_padre($id,&$codigo,&$unir = false){
        $dato = $this->edificaciones_mapa->get($id);
        $codigo[] = ($unir)?'"'.$dato->id_tipo.'":"'.$dato->codigo_edificacion.'"':$dato->codigo_edificacion;
        if($dato->padre_id==0){
            return $codigo;
        }else{
            $this->buscar_padre($dato->padre_id,$codigo,$unir);
        }
    }

    public function crear_codigo($id,$unir = false){
        $codigo = array();
        $this->buscar_padre($id,$codigo, $unir);
        if($unir){
            $lista = array();
            foreach($codigo as $linea){
                array_unshift($lista, $linea);
            }
            $codigo = $lista;
        }else{
            rsort($codigo);
        }
        return $codigo;
    }

    public function agregar_inmueble(){
        $inicio = \filter_input(INPUT_POST, 'inicio');
        $final_p = \filter_input(INPUT_POST, 'final');
        $id = \filter_input(INPUT_POST, 'id');
        $id_edificacion = \filter_input(INPUT_POST, 'id_edificacion');
        $cantidad = \filter_input(INPUT_POST, 'cantidad');
        $incremento = \filter_input(INPUT_POST, 'incremento');
        $codigo_mapa = $this->crear_codigo($id_edificacion);
        $codigo_interno = $this->crear_codigo($id_edificacion,1);
        $codigo = implode("",$codigo_mapa);
        $codigo_interno = "{".implode(",",$codigo_interno)."}";
        $ubicacion = "";
        $codcliente = "";
        $ocupado = FALSE;
        $final=(!empty($final_p))?$final_p:$inicio;
        $inmuebles = 0;
        $error = 0;
        $linea = 0;
        if($inicio == $final){
            $item = (is_int($inicio))?str_pad($inicio,3,"0",STR_PAD_LEFT):$inicio;
            $edif0 = new residentes_edificaciones();
            $edif0->id = $id;
            $edif0->id_edificacion = $id_edificacion;
            $edif0->codigo = $codigo;
            $edif0->codigo_interno = $codigo_interno;
            $edif0->numero = $item;
            $edif0->ubicacion = trim($ubicacion);
            $edif0->codcliente = trim($codcliente);
            $edif0->ocupado = ($ocupado)?"TRUE":"FALSE";
            try {
                $edif0->save();
                $inmuebles++;
            } catch (Exception $exc) {
                $this->new_error_msg($exc->getTraceAsString());
                $error++;
            }
        }else{
            for ($i = $inicio; $i<=($final); $i++){
                if($linea==$cantidad AND $cantidad!=0){
                    $i = ($i-$cantidad)+$incremento;
                    $linea = 0;
                }
                $item = (is_int($i))?str_pad($i,3,"0",STR_PAD_LEFT):$i;
                $edif0 = new residentes_edificaciones();
                $edif0->id = $id;
                $edif0->id_edificacion = $id_edificacion;
                $edif0->codigo = $codigo;
                $edif0->codigo_interno = $codigo_interno;
                $edif0->numero = $item;
                $edif0->ubicacion = trim($ubicacion);
                $edif0->codcliente = trim($codcliente);
                $edif0->ocupado = ($ocupado)?"TRUE":"FALSE";
                try {
                    $edif0->save();
                    $inmuebles++;
                } catch (Exception $exc) {
                    $this->new_error_msg($exc->getTraceAsString());
                    $error++;
                }
                $linea++;
            }
        }
        if($error){
            $this->new_error_msg('No puedieron guardarse la informacion de '.$error.' inmuebles, revise su listado.');
        }if($inmuebles){
            $this->new_message('Se guardaron correctamente '.$inmuebles.' inmuebles.');
        }
    }

    public function agregar_residente(){
        $id_edificacion = \filter_input(INPUT_POST, 'id_edificacion');
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $fecha_ocupacion = \filter_input(INPUT_POST, 'fecha_ocupacion');
        $fecha_disponibilidad = \filter_input(INPUT_POST, 'fecha_disponibilidad');
        $accion = \filter_input(INPUT_POST, 'accion');
        $inmueble = $this->edificaciones->get($id_edificacion);
        if($inmueble AND $accion == 'agregar_residente'){
            $inmueble->ocupado = 'TRUE';
            $inmueble->codcliente = $codcliente;
            $inmueble->fecha_ocupacion = ($fecha_ocupacion)?\date('Y-m-d',strtotime($fecha_ocupacion)):NULL;
            $inmueble->fecha_disponibilidad = ($fecha_disponibilidad)?\date('Y-m-d',strtotime($fecha_disponibilidad)):NULL;
            if($inmueble->save()){
                $this->new_message('Residente agregado exitosamente.');
            }else{
                $this->new_error_msg('No se pudo agregar al residente confirme el nombre del residente y las fechs de ocupación y disponibilidad');
            }
        }elseif($inmueble AND $accion == 'quitar_residente'){
            $inmueble->ocupado = 'FALSE';
            $inmueble->codcliente = '';
            $inmueble->fecha_ocupacion = '';
            $inmueble->fecha_disponibilidad = '';
            if($inmueble->save()){
                $this->new_message('Residente removido exitosamente.');
            }else{
                $this->new_error_msg('No se pudo remover al residente');
            }
        }
    }

    public function tratar_edificaciones(){
        $id = filter_input(INPUT_POST, 'id');
        $precodigo = "";
        $precodigo_interno = array();
        foreach($this->edificaciones_tipo->all() as $i){
            $campo = "campo_".$i->id;
            $linea = filter_input(INPUT_POST, $campo);
            $precodigo .= $linea;
            $precodigo_interno[$i->id]=$linea;
        }
        $codigo_p = filter_input(INPUT_POST, 'codigo');
        $codigo_interno_p = filter_input(INPUT_POST, 'codigo_interno');
        $codigo = ($codigo_p)?$codigo_p:$precodigo;
        $codigo_interno = ($codigo_interno_p)?$codigo_interno_p:\json_encode($precodigo_interno);
        $numero = filter_input(INPUT_POST, 'numero_edificacion');
        $ubicacion = filter_input(INPUT_POST, 'ubicacion');
        $codcliente = filter_input(INPUT_POST, 'codcliente');
        $ocupado = filter_input(INPUT_POST, 'ocupado');
        $delete = filter_input(INPUT_POST, 'delete');
        if($delete){
            $item = $this->edificaciones->get($id);
            if(!$item->ocupado){
                try {
                    $item->delete();
                    $this->new_message('Edificación eliminada con exito.');
                } catch (Exception $exc) {
                    $this->new_error_msg('Ocurrió un error al querer eliminar la Edificación. '.$exc->getTraceAsString());
                }
            } else{
                $this->new_error_msg('¡No se puede eliminar una edificación que está ocupada!');
            }
        }else{
            $edif0 = new FacturaScripts\model\residentes_edificaciones();
            $edif0->id = $id;
            $edif0->codigo = $codigo;
            $edif0->codigo_interno = $codigo_interno;
            $edif0->numero = trim($numero);
            $edif0->ubicacion = trim($ubicacion);
            $edif0->codcliente = trim($codcliente);
            $edif0->ocupado = ($ocupado)?"TRUE":"FALSE";
            try {
                $edif0->save();
                $this->new_message('¡Edificación guardada exitosamente!');
            } catch (Exception $exc) {
                $this->new_error_msg('Ocurrió un error intentando guardar la información. '.$exc->getTraceAsString());
            }
        }
        $this->edificaciones = new residentes_edificaciones();
    }

    public function tratar_tipo_edificaciones(){
        $id = filter_input(INPUT_POST, 'id');
        $descripcion = filter_input(INPUT_POST, 'descripcion');
        $padre = filter_input(INPUT_POST, 'padre');
        $delete = filter_input(INPUT_POST, 'delete');
        if($delete){
            $item = $this->edificaciones_tipo->get($delete);
            if(!$item->hijos()){
                try{
                    $item->delete();
                    $this->new_message('Tipo de edificación eliminada con exito.');
                } catch (ErrorException $e){
                    $this->new_error_msg('Ocurrió un error al querer eliminar el tipo de edificación. '.$e->getTraceAsString());
                }
            }else{
                $this->new_error_msg('No se puede eliminar un Tipo de edificación que es padre de otros tipos de edificación.');
            }
        }else{
            $tipo0 = new residentes_edificaciones_tipo();
            $tipo0->id = $id;
            $tipo0->descripcion = ucfirst(strtolower(trim(htmlspecialchars($descripcion))));
            $tipo0->padre = $padre;
            try {
                $tipo0->save();
                $this->new_message('¡Tipo de edificación guardado exitosamente!');
            } catch (Exception $exc) {
                $this->new_error_msg('Ocurrió un error intentando guardar la información. '.$exc->getTraceAsString());
            }
        }
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
    }

    public function buscar_tipo_edificaciones(){
        $estructura = $this->edificaciones_tipo->jerarquia();
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($estructura);
    }

    public function obtener_hijos(){
        $this->template = FALSE;
        $id_tipo = \filter_input(INPUT_GET, 'id_tipo');
        $hijos = array();
        if($id_tipo){
            $hijos = $this->edificaciones_tipo->get_by_field('padre', $id_tipo);
        }
        header('Content-Type: application/json');
        echo json_encode($hijos);
    }

     /**
     * funcion para guardar los codigos de las edificaciones base Manzana, Zona, Grupo, Edificio, etc
     */
    public function agregar($objeto){
        $inicio = \filter_input(INPUT_POST, 'inicio');
        $final_p = \filter_input(INPUT_POST, 'final');
        $id = \filter_input(INPUT_POST, 'id');
        $codigo_padre = \filter_input(INPUT_POST, 'codigo_padre');
        $padre_id = \filter_input(INPUT_POST, 'padre_id');
        $final=(!empty($final_p))?$final_p:$inicio;
        $inmuebles = 0;
        $error = 0;
        $linea = 0;
        foreach(range($inicio,$final) as $item){
            $item = (is_int($item))?str_pad($item,3,"0",STR_PAD_LEFT):$item;
            $punto = new residentes_edificaciones_mapa();
            $punto->id = $id;
            $punto->id_tipo = $objeto->id;
            $punto->codigo_edificacion = $item;
            $punto->codigo_padre = $codigo_padre;
            $punto->padre_tipo = $objeto->padre;
            $punto->padre_id = $padre_id;
            $punto->numero = '';
            if($punto->save()){
                $inmuebles++;
            }else{
                $error++;
            }
            $linea++;
        }
        if($error){
            $this->new_error_msg('No puedieron guardarse la informacion de '.$error.' inmuebles, revise su listado.');
        }
        $this->new_message('Se guardaron correctamente '.$inmuebles.' inmuebles.');
    }

    private function mayusculas($string){
        return strtoupper(trim(strip_tags(stripslashes($string))));
    }

    private function minusculas($string){
        return strtolower(trim(strip_tags(stripslashes($string))));
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'tipo_edificaciones',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'button',
                'text' => '<span class="fa fa-list-ol"></span>&nbsp;Tipo Edificaciones',
                'params' => '&tipo_edificaciones=TRUE'
            ),
            array(
                'name' => 'configurar_residentes_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/residentes/view/js/2/residentes.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'treeview_edificaciones_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/residentes/view/js/1/bootstrap-treeview.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'treeview_edificaciones_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/residentes/view/css/bootstrap-treeview.min.css"/>',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
            if($fsext0->name == 'tipo_edificaciones'){
                $fsext0->delete();
            }
        }
    }
}
