<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 *  * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */

require_model('articulo.php');
require_model('cliente.php');
require_model('factura_cliente.php');
require_model('impuesto.php');
require_model('inquilino.php');
/**
 * Description of ver_inquilino
 *
 * @author carlos
 */
class ver_inquilino extends fs_controller
{
   public $cliente;
   public $facturas;
   public $impuesto;
   public $impuesto_a;
   public $impuesto_g;
   public $impuesto_m;
   public $inquilino;
   public $precio_agua;
   public $precio_gas;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Inquilino', 'ventas', FALSE, FALSE);
   }

   protected function private_core()
   {
      $this->cliente = new cliente();
      $this->facturas = array();
      $this->impuesto = new impuesto();
      $this->impuesto_a = NULL;
      $this->impuesto_g = NULL;
      $this->impuesto_m = NULL;
      $this->inquilino = FALSE;
      $this->precio_agua = 0;
      $this->precio_gas = 0;

      if( isset($_COOKIE['impuesto_a']) )
      {
         $this->impuesto_a = $_COOKIE['impuesto_a'];
      }

      if( isset($_COOKIE['impuesto_g']) )
      {
         $this->impuesto_g = $_COOKIE['impuesto_g'];
      }

      if( isset($_COOKIE['impuesto_m']) )
      {
         $this->impuesto_m = $_COOKIE['impuesto_m'];
      }

      if( isset($_COOKIE['precio_agua']) )
      {
         $this->precio_agua = $_COOKIE['precio_agua'];
      }

      if( isset($_COOKIE['precio_gas']) )
      {
         $this->precio_gas = $_COOKIE['precio_gas'];
      }

      if( isset($_REQUEST['id']) )
      {
         $inq0 = new inquilino();
         $this->inquilino = $inq0->get($_REQUEST['id']);
      }

      if($this->inquilino)
      {
         if( isset($_REQUEST['buscar_referencia']) )
         {
            $this->buscar_referencia();
         }
         else if( isset($_POST['cliente']) )
         {
            /// modificar el inquilino
            $cliente = $this->cliente->get($_POST['cliente']);
            if($cliente)
            {
               $this->inquilino->codcliente = $cliente->codcliente;
               $this->inquilino->nombre = $cliente->nombre;
               $this->inquilino->piso = $_POST['piso'];
               $this->inquilino->bloque = $_POST['bloque'];
               $this->inquilino->fechaalta = $_POST['fechaalta'];
               $this->inquilino->observaciones = $_POST['observaciones'];

               if( $this->inquilino->save() )
               {
                  $this->new_message('Datos guardados correctamente');
               }
               else
                  $this->new_error_msg('Error al guardar los datos.');
            }
            else
               $this->new_error_msg('Cliente no encontrado.');
         }
         else if( isset($_POST['mensualidad']) )
         {
            /// nueva factura
            $this->inquilino->mensualidad = floatval($_POST['mensualidad']);
            $this->inquilino->agua = floatval($_POST['agua']);
            $this->inquilino->fechaagua = date('d-m-Y');
            $this->inquilino->gas = floatval($_POST['gas']);
            $this->inquilino->fechagas = date('d-m-Y');
            if( $this->inquilino->save() )
            {
               $this->impuesto_a = $_POST['impuesto_a'];
               setcookie('impuesto_a', $this->impuesto_a);
               $this->impuesto_g = $_POST['impuesto_g'];
               setcookie('impuesto_g', $this->impuesto_g);
               $this->impuesto_m = $_POST['impuesto_m'];
               setcookie('impuesto_m', $this->impuesto_m);
               $this->precio_agua = $_POST['precio_agua'];
               setcookie('precio_agua', $this->precio_agua);
               $this->precio_gas = $_POST['precio_gas'];
               setcookie('precio_gas', $this->precio_gas);

               $this->nueva_factura();
            }
            else
               $this->new_error_msg('Error al guardar los datos.');
         }

         $this->page->title = 'Inquilino '.$this->inquilino->nombre;

         $factura = new factura_cliente();
         $this->facturas = $factura->all_from_cliente($this->inquilino->codcliente);
      }
      else
         $this->new_error_msg('Inquilino no encontrado.');
   }

   public function url()
   {
      if( isset($_REQUEST['id']) )
      {
         return $this->inquilino->url();
      }
      else
         return parent::url();
   }

   private function nueva_factura()
   {
      $cliente = $this->cliente->get($this->inquilino->codcliente);
      if($cliente)
      {
         $factura = new factura_cliente();
         $factura->codserie = $cliente->codserie;
         $factura->codpago = $cliente->codpago;
         $factura->codalmacen = $this->empresa->codalmacen;

         $eje0 = new ejercicio();
         $ejercicio = $eje0->get_by_fecha( date('d-m-Y') );
         if($ejercicio)
         {
            $factura->codejercicio = $ejercicio->codejercicio;
         }

         $div0 = new divisa();
         $divisa = $div0->get($cliente->coddivisa);
         if($divisa)
         {
            $factura->coddivisa = $divisa->coddivisa;
            $factura->tasaconv = $divisa->tasaconv;
         }

         $factura->codcliente = $cliente->codcliente;
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $factura->codcliente = $cliente->codcliente;
               $factura->cifnif = $cliente->cifnif;
               $factura->nombrecliente = $cliente->razonsocial;
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

         if( $factura->save() )
         {
            /// mensualidad
            if( floatval($_POST['mensualidad']) > 0 )
            {
               $linea = new linea_factura_cliente();
               $linea->idfactura = $factura->idfactura;
               $linea->descripcion = $_POST['desc_mensualidad'];
               $linea->cantidad = 1;
               $imp = $this->impuesto->get($_POST['impuesto_m']);
               if($imp)
               {
                  $linea->codimpuesto = $imp->codimpuesto;
                  $linea->iva = $imp->iva;
                  $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario = (100 * floatval($_POST['mensualidad']))/(100 + $imp->iva);
                  if( $linea->save() )
                  {
                     $factura->neto += $linea->pvptotal;
                     $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                  }
               }
            }

            /// consumo de agua
            if( floatval($_POST['consumo_agua']) > 0 AND floatval($_POST['precio_agua']) > 0 )
            {
               $linea = new linea_factura_cliente();
               $linea->idfactura = $factura->idfactura;
               $linea->descripcion = 'Consumo de agua: '.$_POST['consumo_agua'].' M3';
               $imp = $this->impuesto->get($_POST['impuesto_a']);
               if($imp)
               {
                  $linea->codimpuesto = $imp->codimpuesto;
                  $linea->iva = $imp->iva;
                  $linea->cantidad = floatval($_POST['consumo_agua']);
                  $linea->pvpunitario = (100 * floatval($_POST['precio_agua']))/(100 + $imp->iva);
                  $linea->pvpsindto = $linea->pvptotal = $linea->cantidad * $linea->pvpunitario;
                  if( $linea->save() )
                  {
                     $factura->neto += $linea->pvptotal;
                     $factura->totaliva += $linea->pvptotal * $linea->iva / 100;
                  }
               }
            }

            /// consumo de gas
            if( floatval($_POST['consumo_gas']) > 0 AND floatval($_POST['precio_gas']) > 0 )
            {
               $linea = new linea_factura_cliente();
               $linea->idfactura = $factura->idfactura;
               $linea->descripcion = 'Consumo de gas: '.$_POST['consumo_gas'].' M3';
               $imp = $this->impuesto->get($_POST['impuesto_g']);
               if($imp)
               {
                  $linea->codimpuesto = $imp->codimpuesto;
                  $linea->iva = $imp->iva;
                  $linea->cantidad = floatval($_POST['consumo_gas']);
                  $linea->pvpunitario = (100 * floatval($_POST['precio_gas']))/(100 + $imp->iva);
                  $linea->pvpsindto = $linea->pvptotal = $linea->cantidad * $linea->pvpunitario;
                  if( $linea->save() )
                  {
                     $factura->neto += $linea->pvptotal;
                     $factura->totaliva += $linea->pvptotal * $linea->iva / 100;
                  }
               }
            }

            $art0 = new articulo();

            if($_POST['desc_otro'] != '')
            {
               $linea = new linea_factura_cliente();
               $linea->idfactura = $factura->idfactura;
               $linea->descripcion = $_POST['desc_otro'];
               $linea->cantidad = 1;
               $imp = $this->impuesto->get($_POST['impuesto_otro']);
               if($imp)
               {
                  $linea->codimpuesto = $imp->codimpuesto;
                  $linea->iva = $imp->iva;
                  $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario = (100 * floatval($_POST['otro']))/(100 + $imp->iva);

                  if($_POST['ref_otro'])
                  {
                     $articulo = $art0->get($_POST['ref_otro']);
                     if($art0)
                     {
                        $linea->referencia = $articulo->referencia;
                        $articulo->sum_stock($this->empresa->codalmacen, -1);
                     }
                  }

                  if( $linea->save() )
                  {
                     $factura->neto += $linea->pvptotal;
                     $factura->totaliva += $linea->pvpunitario * $linea->iva / 100;
                  }
               }
            }

            if($_POST['desc_otro2'] != '')
            {
               $linea = new linea_factura_cliente();
               $linea->idfactura = $factura->idfactura;
               $linea->descripcion = $_POST['desc_otro2'];
               $linea->cantidad = 1;
               $imp = $this->impuesto->get($_POST['impuesto_otro2']);
               if($imp)
               {
                  $linea->codimpuesto = $imp->codimpuesto;
                  $linea->iva = $imp->iva;
                  $linea->pvpsindto = $linea->pvptotal = $linea->pvpunitario = (100 * floatval($_POST['otro2']))/(100 + $imp->iva);

                  if($_POST['ref_otro2'])
                  {
                     $articulo = $art0->get($_POST['ref_otro2']);
                     if($art0)
                     {
                        $linea->referencia = $articulo->referencia;
                        $articulo->sum_stock($this->empresa->codalmacen, -1);
                     }
                  }

                  if( $linea->save() )
                  {
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

            if( abs(floatval($_POST['total']) - $factura->total) > .01 )
            {
               $this->new_error_msg("El total difiere entre la vista y el controlador (".$_POST['total'].
                       " frente a ".$factura->total."). Debes informar del error.");
               $factura->delete();
            }
            else if( $factura->save() )
            {
               $this->new_message("<a href='".$factura->url()."'>Factura</a> guardada correctamente.");
               $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
            }
            else
               $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>Factura</a>!");
         }
         else
            $this->new_error_msg('Imposible guardar la factura.');
      }
      else
         $this->new_error_msg('Cliente no encontrado.');
   }

   private function buscar_referencia()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;

      $articulo = new articulo();
      $json = array();
      foreach($articulo->search($_REQUEST['buscar_referencia']) as $art)
      {
         $json[] = array(
             'value' => $art->referencia,
             'data' => $art->referencia,
             'pvpi' => $art->pvp_iva(FALSE),
             'codimpuesto' => $art->codimpuesto,
             'descripcion' => $art->descripcion
         );
      }

      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_referencia'], 'suggestions' => $json) );
   }
}
