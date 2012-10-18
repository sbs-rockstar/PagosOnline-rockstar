<?php


/*
Plugin Name: Plataforma de pago PagosOnline - Colombia
Plugin URI: https://github.com/8manos/woocommerce-gateway-pagosonline
Description: Plugin desarrollado para procesar pagos con WooCommerce utilizando la plataforma de pago seguro de PagosOnline.
Version: 1.2
Author: ess_sebastian
*/


/*  ##########################################################################################################################
	Copyright 2012  8manos S.A.S  (email : plugins@8manos.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
	########################################################################################################################## 
	  */

add_action('plugins_loaded', 'inicia_plataforma_pagosonline', 0);

function inicia_plataforma_pagosonline() {
	if ( !class_exists( 'woocommerce_payment_gateway' ) ) return;
	class woocommerce_pagos extends woocommerce_payment_gateway { 
		public function __construct() { 
			global $woocommerce; 
			$this->id			= 'pagosonline';
		   	$this->icon 		= plugins_url(basename(dirname(__FILE__))."/images/pagos.png");
		   	$this->has_fields 	= false;
		   	
			// Si se va a realizar pagos reales activar la siguiente dirección para el action
			
			if ($this->testmode == '0' ) {
			
			$this->purchaseurl = "https://gateway.pagosonline.net/apps/gateway/index.html";
			
			// Si se van a hacer pruebas, activar la siguiente dirección
			}
  else
 { 
			$this->purchaseurl = "https://gateway2.pagosonline.net/apps/gateway/index.html";

}

	    
			$this->init_form_fields();
			$this->init_settings();	

			$this->enabled			= $this->settings['enabled'];
			$this->title 			= $this->settings['title'];
			$this->descripcion  	= $this->settings['Description'];
			$this->usuarioId				= $this->settings['usuarioId'];
			$this->firma		=$this->settings['firma'];
			$this->iva = $this->settings['iva'];
			$this->debugmode		= $this->settings['debugmode'];
			$this->debugmode_email	= $this->settings['debugmode_email'];
			$this->testmode			= $this->settings['testmode'];
				
			add_action( 'init', array(&$this, 'check_pagos_response') );		
			add_action( 'valid-pagosonline-request', array(&$this, 'successful_request') );
			add_action( 'woocommerce_receipt_pagosonline', array(&$this, 'receipt_page') );
			add_action( 'woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options') );	
		}
		//Información de los campos de configuración
    	function init_form_fields() {
    		$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Habilitar/Deshabilitar', 'woothemes' ), 
								'type' => 'checkbox', 
								'label' => __( 'Habilita el pago con PagosOnline', 'woothemes' ), 
								'default' => 'yes'
							), 
				'title' => array(
								'title' => __( 'Titulo', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Este sera el titulo que se mostrara a tus clientes al momento del pago.', 'woothemes' ), 
								'default' => __( 'Pagos', 'woothemes' )
							),
				'Description'=>array(
				             'title' => __( 'Descripcion', 'woothemes' ), 
				             'type' => 'text', 
										 'description' => __( 'Ingresa la descripcion del pago que va a realizar el cliente;', 'woothemes' ), 
										 'default' => 'Pruebas'
										),
				'usuarioId'=>array(
				             'title' => __( 'Numero de cuenta en PagosOnline', 'woothemes' ), 
				             'type' => 'text', 
										 'description' => __( 'Ingresa tu numero de cuenta de PagosOnline; este numero te lo entregaran una vez creada tu cuenta en PagosOnline', 'woothemes' ), 
										 'default' => ''
										),
				'testmode'=>array(
				             'title' => __( 'Estado del pago', 'woothemes' ), 
				             'type' => 'select', 
										 'options' => array('1'=>'Pruebas', '0'=>'Funcionamiento normal'),
								'description' => __( '<br/>Utiliza la opcion <b>Pruebas</b>, cuando estes realizando pagos ficticios para comprobar el funcionamiento del sitio. Una vez listo, utiliza <b>Funcionamiento normal</b> para comenzar a recibir pagos.', 'woothemes' ), 
								'default' => '1'
										),						
																													
				'firma' => array(
								'title' => __( 'Firma', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Ingresa la firma secreta que se te entregara una vez creada tu cuenta de PagosOnline.', 'woothemes' ), 
								'default' => ''
							), 
				'iva' => array(
								'title' => __( 'Iva', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'La tasa de IVA que utilizan tus productos', 'woothemes' ), 
								'default' => ''
							),			
				'debugmode_email' => array(
								'title' => __( 'Email para errores', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Si hay algun problema, a que correo te enviamos la notificacion?', 'woothemes' ), 
								'default' => get_bloginfo('admin_email')
							)
				);
    
		} // Terminamos de editar los campos
		
		
		public function admin_options() {
	
	    	?>
	    	<h3><?php _e('Plataforma de pagos con PagosOnline', 'woothemes'); ?></h3>
	    	
	    	<?php _e('<p>Esta plataforma de pago funciona enviando los datos del cliente a la plataforma de pago seguro de PagosOnline, una vez alli, se genera una respuesta y esta vuelve nuevamente al carrito de compra original alojado en woocommerce.</p>', 'woothemes'); ?>
	    	
	    	
    		<table class="form-table">
    		<?php
    			$this->generate_settings_html();
    		?>
			</table><!--/.form-table-->
    		<?php
    	} 
    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }
    
    /**
	 	* Generamos el boton de pago para integrarlo en el checkout
	 	**/
		public function generate_pagos_form( $order_id ) {
			global $woocommerce;
			$order = &new woocommerce_order( $order_id );
			$oitems = unserialize($order->order_custom_fields['_order_items'][0]);
		    $moneda = "COP";
			
			$order_currency = $order->order_custom_fields[0];
			$shipping_name = explode(' ', $order->shipping_method);
			$llave_encripcion = "$this->firma";
			$refVenta = "$order_id"; 
			$baseDevolucionIva= $oitems[0]['line_total'];
			$iva= $oitems[0]['line_subtotal_tax'];
			$valor= $order->order_total; 
      $firma_cadena = "$llave_encripcion~$this->usuarioId~$refVenta~$valor~$moneda";
			$pagos_args = array_merge(
				array(
					'usuarioId' 					=> $this->usuarioId,
					'prueba'					=> $this->testmode,
					'refVenta'			=> $refVenta,
					'descripcion' => $this->descripcion,
					'iva' => $iva,
					'baseDevolucionIva' => $baseDevolucionIva,
					'emailComprador' => $this->debugmode_email,
					'valor'					=> $valor,
					'moneda' => $moneda,
					'firma' => md5("$firma_cadena"),
					'url_respuesta' => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))),
					'url_confirmacion' => get_site_url()."/confirm-pagos/",
					'nombreComprador' => $order->billing_first_name ." ".$order->billing_last_name,
					'telefonoMovil' => $order->billing_phone,
					
					
				'direccionCobro'		=> $order->billing_address_1,
					'ciudadCobro'					=> $order->billing_city,
					
					
				)
			);
			$item_loop = 0;
			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				$_product = $order->get_product_from_item( $item );
				if ($_product->exists() && $item['qty']) :
					
					if ( $_product->is_virtual() ) :
						$tangible = "N";
					else :
						$tangible = "Y";
					endif;
							
					
					if ($order->prices_include_tax) :
					else :
					endif;
					
					
					
					$item_loop++;
									
				endif;
			endforeach; endif;

			
			if ($order->get_shipping()>0) :
			
				if ($order->prices_include_tax) :
				else :
				endif;
				
				
			endif;
			
			if (!$order->prices_include_tax && $order->get_total_tax()>0) :
			
			endif;
			
			if ($order->get_order_discount()>0) :
			
			endif;
			
			
			if ( $this->debugmode == 'yes' && isset($this->debugmode_email) ) :
								
				foreach ( $pagos_args as $key => $value ) {
					$message .= $key . '=' . $value . "\r\n";
				}
					
				$message = 'Order ID: ' . $order_id . "\r\n" . "\r\n" . $message;
				pagos_debug_email( $this->debugmode_email, 'Pagos Debug. Sent Values Order ID: ' . $order_id, $message );
				
			endif;
			
			$pagos_adr = $this->purchaseurl;
			$pagos_args_array = array();
			foreach ($pagos_args as $key => $value) {
				$pagos_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			return '<form action="'.$pagos_adr.'" method="post" id="Formulario_pago_pagosonline">
					' . implode('', $pagos_args_array) . '
					<input type="submit" class="button-alt" id="submit_Formulario_pago_pagosonline" value="'.__('Paga seguro con Pagosonline', 'woothemes').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancela la orden', 'woothemes').'</a>
					<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block(
								{ 
									message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Gracias por tu orden. Ahora te estamos redireccionando a la plataforma segura de PagosOnline.', 'woothemes').'", 
									overlayCSS: 
									{ 
										background: "#fff", 
										opacity: 1 
									},
									css: { 
                                   		padding:        20, 
                                   		textAlign:      "center", 
                                   		color:          "#555", 
                                   		border:         "3px solid #aaa", 
                                   		backgroundColor:"#fff", 
                                   		cursor:         "wait",
                                   		lineHeight:        "32px"
                               		} 
								});
							jQuery("#submit_Formulario_pago_pagosonline").click();
						});
					</script>
				</form>';
			
		}
		
		function process_payment( $order_id ) {
			
			$order = &new woocommerce_order( $order_id );
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
			);
			
		}
		
		function receipt_page( $order ) {
			echo '<p>'.__('Thank you for your order, please click the button below to pay with pagosonline.', 'woothemes').'</p>';
			
			echo $this->generate_pagos_form( $order );
			
		}
		
		function check_pagos_response() {
			
			$llave = $llave_encripcion;
			$usuario_id=$_REQUEST['usuario_id'];
			$ref_venta=$_REQUEST['ref_venta'];
			$ref_pol = $_REQUEST['ref_pol'];
			$valor=$_REQUEST['valor'];
			$moneda=$_REQUEST['moneda'];
			$estado_pol=$_REQUEST['estado_pol'];
			$firma_cadena= "$llave~$usuario_id~$ref_venta~$valor~$moneda~$estado_pol";$firmacreada = md5($firma_cadena);
			$firma =$_REQUEST['firma'];
			$ref_venta=$_REQUEST['ref_venta'];
			$fecha_procesamiento=$_REQUEST['fecha_procesamiento'];
			$ref_pol=$_REQUEST['ref_pol'];
			$cus=$_REQUEST['cus'];
			$extra1=$_REQUEST['extra1'];
			$banco_pse=$_REQUEST['banco_pse'];
			if($_REQUEST['estado_pol'] == 6 && $_REQUEST['codigo_respuesta_pol'] == 5)
			{$estadoTx = "Transacci&oacute;n fallida";}
			else if($_REQUEST['estado_pol'] == 6 && $_REQUEST['codigo_respuesta_pol'] == 4)
			{$estadoTx = "Transacci&oacute;n rechazada";}
			else if($_REQUEST['estado_pol'] == 12 && $_REQUEST['codigo_respuesta_pol'] == 9994)
			{$estadoTx = "Pendiente, Por favor revisar si el d&eacute;bito fue realizado en el Banco";}
			else if($_REQUEST['estado_pol'] == 4 && $_REQUEST['codigo_respuesta_pol'] == 1)
			{
				$firma = "$llave_encripcion~$usuario_id~$ref_venta~$valor~$moneda~$estado_pol";
				$estadoTx = "Transacci&oacute;n aprobada";
				
				
				}
				
			else
			{$estadoTx=$_REQUEST['mensaje'];}
			
			if ( isset($_REQUEST['ref_pol']) ) :
			
				$firma = $firma_cadena;
				$usuarioId = $usuario_id;
				
				if ( isset($ref_pol) ) :
				
					$RefNr = $ref_pol;
					$order_number = $_REQUEST["order"];
					$total = $valor;
					$pagosMD5 = $_REQUEST["key"];
				
					if ( $this->testmode == 'yes' ):
						$string_to_hash = $usuarioId . $sid . "1" . $valor;		
					else :
						$string_to_hash = $usuarioId . $sid . $_REQUEST["order"] . $valor;		
					endif;
					
					$check_key = strtoupper(md5($string_to_hash));

					$pagos_return_values = array(
						"check_key" 		=> 	$check_key,
						"RefNr" 			=> $RefNr,
						"sale_id" 			=> $ref_venta,
						"total" 			=> $total,
						"twocheckoutMD5" 	=> $pagosMD5
					);
					
			
				elseif ( isset($ref_pol) ) :
			
					$RefNr = $ref_pol;
					$sale_id = $refVenta;
					$invoice_id = $_REQUEST["invoice_id"];
					$pagoMD5 = $_REQUEST["md5_hash"];
					$vendor_id = $ref_pol;
					
					$string_to_hash = $sale_id . $sid . $invoice_id . $secret_word;		
					$check_key = strtoupper(md5($string_to_hash));
					
	
					$pagos_return_values = array(
						"check_key" 		=> $check_key,
						"RefNr" 			=> $RefNr,
						"sale_id" 			=> $sale_id,
						"vendor_id" 		=> $vendor_id,
						"twocheckoutMD5" 	=> $pagoMD5
					);
					
				endif;
				
				if ( $this->debugmode == 'yes' && isset($this->debugmode_email) ) :
				
					$order_id 	  	= $RefNr;
									
					foreach ( $twocheckout_return_values as $key => $value ) {
						$message .= $key . '=' . $value . "\r\n";
					}
						
					$message = 'Order ID: ' . $order_id . "\r\n" . "\r\n" . $message;
					pagos_debug_email( $this->debugmode_email, 'Pagos Debug. Return Values Order ID: ' . $order_id, $message );
				
				endif;
					
				if ( isset($pagos_return_values['check_key']) && $check_key == $twocheckoutMD5 ) :
					do_action("valid-twocheckout-request", $pagos_return_values);
				endif;
				
				
			endif;								
		}
		
	}
}
function add_pagosonline_gateway( $methods ) {
	$methods[] = 'woocommerce_pagos'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_pagosonline_gateway' );