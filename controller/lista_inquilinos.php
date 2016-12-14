<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */

require_model('cliente.php');
require_model('inquilino.php');

/**
 * Description of lista_inquilinos
 *
 * @author carlos
 */
class lista_inquilinos extends fs_controller
{
   public $bloque;
   public $cliente;
   public $inquilino;
   public $offset;
   public $resultados;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Inquilinos', 'residentes', FALSE, TRUE);
   }

   protected function private_core()
   {
      $this->bloque = '';
      $this->cliente = new cliente();
      $this->inquilino = new inquilino();

      $this->offset = 0;
      if( isset($_REQUEST['offset']) )
      {
         $this->offset = intval($_REQUEST['offset']);
      }

      if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if( isset($_POST['cliente']) )
      {
         $cliente = $this->cliente->get($_POST['cliente']);
         if($cliente)
         {
            $this->inquilino->codcliente = $cliente->codcliente;
            $this->inquilino->nombre = $cliente->nombre;
            $this->inquilino->bloque = $_POST['bloque'];
            $this->inquilino->piso = $_POST['piso'];
            if( $this->inquilino->save() )
            {
               $this->new_message('Inquilino guardado correctamente.');
               header('Location: '.$this->inquilino->url());
            }
            else
               $this->new_error_msg('Error al guardar el inquilino.');
         }
         else
            $this->new_error_msg('Cliente no encontrado.');
      }
      else if( isset($_GET['delete']) )
      {
         $inq = $this->inquilino->get($_GET['delete']);
         if($inq)
         {
            if( $inq->delete() )
            {
               $this->new_message('Inquilino eliminado correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar el inquilino.');
         }
         else
            $this->new_error_msg('Inquilino no encontrado.');
      }

      if( isset($_GET['cliente']) )
      {
         $this->resultados = $this->inquilino->all_from_cliente($_GET['cliente']);
      }
      else if( isset($_POST['bloque']) )
      {
         if($_POST['bloque'] != '')
         {
            $this->bloque = $_POST['bloque'];
            $this->resultados = $this->inquilino->all_from_bloque($_POST['bloque']);
         }
         else
         {
            $this->resultados = $this->inquilino->all($this->offset);
         }
      }
      else
         $this->resultados = $this->inquilino->all($this->offset);
   }

   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;

      $json = array();
      foreach($this->cliente->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
      }

      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }

   public function bloques()
   {
      $blist = array();

      $data = $this->db->select("SELECT DISTINCT bloque FROM inquilinos ORDER BY bloque ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $blist[] = $d['bloque'];
         }
      }

      return $blist;
   }

   public function anterior_url()
   {
      $url = '';

      if($this->offset > '0')
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      }

      return $url;
   }

   public function siguiente_url()
   {
      $url = '';

      if(count($this->resultados) == FS_ITEM_LIMIT)
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      }

      return $url;
   }
}
