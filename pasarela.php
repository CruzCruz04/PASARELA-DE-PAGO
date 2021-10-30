<?php

/*
Plugin Name: Pasarela
Description: Advance plugin about gateway payment
Version: 1.0.0
Author: Elias
Author URI: https://bestshoes.com
Text Domain: besthoes.com
WC requires at least: 3.0.0
WC tested up to: 5.6.0
*/
/*
 * Con este hook o gancho podremos registrar nuestras clases de PHP
 * como una pasarela de pago para woocommerce...
 */

add_filter( 'woocommerce_payment_gateways', 'pasarela_add_gateway_class' );
function pasarela_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Pasarela_Gateway'; // el nombre de la clase esta aqui
	return $gateways;
}

/*
 * Esta clase nos ayudara a cargar nuestro plugin, primeramente le pasamos le gancho como primer parametro y luego la funcion 
 */
add_action( 'plugins_loaded', 'pasarela_init_gateway_class' );

function pasarela_init_gateway_class() {

	class WC_Pasarela_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Clase publica que sera nuestro constructor sin parametros
 		 */
 		public function __construct() {

	$this->id = 'pasarela'; // ID del plugin de pasarela de pago
	$this->icon = ''; // URL del icono que se mostrará en la página de pago cerca del nombre de la puerta de enlace
	$this->has_fields = true; // en caso de que necesite un formulario de tarjeta de crédito personalizado
	$this->method_title = 'Pasarela de pago';
	$this->method_description = 'Descripcion acerca de la pasarela de pago'; // se mostrará en la página de opciones

	// las pasarelas pueden admitir suscripciones, reembolsos, métodos de pago guardados
	$this->supports = array(
		'products'
	);

	// Método con todos los campos de opciones
	$this->init_form_fields();

	// cargando las configuraciones
	$this->init_settings();
	$this->title = $this->get_option( 'title' );
	$this->description = $this->get_option( 'description' );
	$this->enabled = $this->get_option( 'enabled' );
	$this->testmode = 'yes' === $this->get_option( 'testmode' );
	$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
	$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

	// con este hook podremos guardar todos los cambios
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	// Necesitamos JavaScript personalizado para obtener un token
	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	
 }


		/**
 		 * opciones que tendra el plugin
 		 */
 		public function init_form_fields(){

	$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable Pasarela',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'Credit Card',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Pay with your credit card via our super-cool payment gateway.',
		),
		'testmode' => array(
			'title'       => 'Test mode',
			'label'       => 'Enable Test Mode',
			'type'        => 'checkbox',
			'description' => 'Place the payment gateway in test mode using test API keys.',
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'test_publishable_key' => array(
			'title'       => 'Test Publishable Key',
			'type'        => 'text'
		),
		'test_private_key' => array(
			'title'       => 'Test Private Key',
			'type'        => 'password',
		),
		'publishable_key' => array(
			'title'       => 'Live Publishable Key',
			'type'        => 'text'
		),
		'private_key' => array(
			'title'       => 'Live Private Key',
			'type'        => 'password'
		)
	);
}

		/**
		 * Lo necesitará si desea su formulario de tarjeta de crédito personalizado...
		 * aqui ira el cuestionario de la pasarela, donde tenemos que editar y a;adir algunos textbox
		 */
		public function payment_fields() {
 
	// Vamos a mostrar alguna descripción antes del formulario de pago
	if ( $this->description ) {
		// puede poner instrucciones para el modo de prueba, me refiero a números de tarjeta de prueba, etc..
		if ( $this->testmode ) {
			$this->description .= 'MODO DE PRUEBA ACTIVADO';
			$this->description  = trim( $this->description );
		}
		//mostrar la descripción con <p> etiquetas, etc.
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	// se uso eco () para el formulario, pero puede cerrar las etiquetas PHP e imprimirlo directamente en HTML
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	// Agregue este gancho de acción si desea que su pasarela de pago personalizada lo admita
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	// Recomiendo usar inique IDs, porque otras puertas de enlace ya podrían usar #ccNo, #expdate, #cvc
	echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
		<input id="misha_ccNo" type="text" autocomplete="off">
		</div>
		<div class="form-row form-row-first">
			<label>Expiry Date <span class="required">*</span></label>
			<input id="misha_expdate" type="text" autocomplete="off" placeholder="MM / YY">
		</div>
		<div class="form-row form-row-last">
			<label>Card Code (CVC) <span class="required">*</span></label>
			<input id="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
		</div>
		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
 
}

		/*
		 * CSS y JS personalizados, en la mayoría de los casos requeridos solo cuando decidió ir con un formulario de tarjeta de crédito personalizado
		 */
	 	public function payment_scripts() {

	// necesitamos JavaScript para procesar un token solo en las páginas de carrito / pago
	if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
		return;
	}

	// si nuestra pasarela de pago está deshabilitada, no tenemos que poner en cola JS también
	if ( 'no' === $this->enabled ) {
		return;
	}

	// No hay razón para poner en cola JavaScript si las claves de API no están establecidas
	if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
		return;
	}

	// No trabaje con detalles de tarjetas sin SSL a menos que su sitio web esté en modo de prueba
	if ( ! $this->testmode && ! is_ssl() ) {
		return;
	}

	// Supongamos que es nuestro procesador de pagos JavaScript el que permite obtener un token
	wp_enqueue_script( 'misha_js', 'https://www.mishapayments.com/api/token.js' );

	// y este es nuestro JS personalizado en su directorio de complementos que funciona con token.js
	wp_register_script( 'woocommerce_misha', plugins_url( 'misha.js', FILE ), array( 'jquery', 'misha_js' ) );

	// En la mayoría de los procesadores de pagos, debe utilizar PUBLIC KEY para obtener un token
	wp_localize_script( 'woocommerce_misha', 'misha_params', array(
		'publishableKey' => $this->publishable_key
	) );

	wp_enqueue_script( 'woocommerce_misha' );

}
		/*
 		 * Validación de campos, más información en el paso 5
		 */
		public function validate_fields(){
 
	if( empty( $_POST[ 'billing_first_name' ]) ) {
		wc_add_notice(  'First name is required!', 'error' );
		return false;
	}
	return true;
 
}

		/*
		 * Estamos procesando los pagos aquí.
		 */
		public function process_payment( $order_id ) {
 
	global $woocommerce;
 
	// lo necesitamos para obtener cualquier detalle del pedido
	$order = wc_get_order( $order_id );
 
 
	/*
 	 * Matriz con parámetros para la interacción de API
	 aqui tenemos que definir los campos que le mandaremos a la API para poder hacer la conexion correctamente...
	 */
	$args = array(
 
	);
 
	/* con el hook wp_remote_post() podremos hacer la interaccion con la API, se encuentra comentada porque aun no tenemos un endpoint, debemos de crearlo y pasarle los argumentos que haremos por el metodo post...
	 */
	 $response = wp_remote_post( '{payment processor endpoint}', $args );
 
 //este es el condicional donde podremos ver si el endpoint nos autoriza realizar el pago, ya sea APROBADA O RECHAZADA, debemos de redirigir en estos if a otra pagina con el mensaje que nos mande nuestro endpoint
	 if( !is_wp_error( $response ) ) {
 
		 $body = json_decode( $response['body'], true );
 
		 // Podría ser diferente dependiendo de su procesador de pagos
		 if ( $body['response']['responseCode'] == 'APPROVED' ) {
 
			// recibimos el pago
			$order->payment_complete();
			$order->reduce_order_stock();
 
			// algunas notas para el cliente (reemplace true por false para que sea privado)
			$order->add_order_note( 'Hey, your order is paid! Thank you!', true );
 
			// carrito vacio
			$woocommerce->cart->empty_cart();
 
			// Redirigir a la página de agradecimiento
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
 
		 } else {
			wc_add_notice(  'Please try again.', 'error' );
			return;
		}
 
	} else {
		wc_add_notice(  'Connection error.', 'error' );
		return;
	}
 
}
 	}
}
