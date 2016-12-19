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
require_model('residentes_edificaciones.php');
require_model('residentes_edificaciones_tipo.php');
/**
 * Description of edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class edificaciones extends fs_controller{
    public $edificaciones;
    public $edificaciones_tipo;
    public $allow_delete;
    public $nombre_edificacion;
    public $residentes_setup;
    public function __construct() {
        parent::__construct(__CLASS__, 'Edificaciones', 'residentes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->shared_extensions();

        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones = new residentes_edificaciones();

        $accion = filter_input(INPUT_POST, 'accion');
        switch ($accion){
            case "agregar":
                $this->tratar_edificaciones();
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
    }

    public function tratar_edificaciones(){
        $id = filter_input(INPUT_POST, 'id');
        $precodigo = "";
        $precodigo_interno = "";
        foreach($this->edificaciones_tipo->all() as $i){
            $campo = "campo_".$i->id;
            $linea = filter_input(INPUT_POST, $campo);
            $precodigo .= $linea;
            $precodigo_interno .= "_".$i->id.":".$linea;
        }
        $codigo_p = filter_input(INPUT_POST, 'codigo');
        $codigo_interno_p = filter_input(INPUT_POST, 'codigo_interno');
        $codigo = ($codigo_p)?$codigo_p:$precodigo;
        $codigo_interno = ($codigo_interno_p)?$codigo_interno_p:substr($precodigo_interno, 1);
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
                    $this->new_error_msg('Ocurrió un error al querer eliminar la Edificación. '.$e->getTraceAsString());
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
