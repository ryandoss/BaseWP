<?php

if( !class_exists( 'uber_lessc' ) ) require_once( 'lessc.inc.php' );

class StyleGenerator{
	
	private $file;
	private $less;
	private $css;
	
	function __construct( $file ){
		
		if( is_null( $file ) ) return false;
		$this->file = $file;
		$this->less = new uber_lessc( $file );
	}
	
	function generateCSS( $settings ){

		try {
		    $this->css = $this->less->parse( null , $settings );
		} catch (Exception $ex) {
		    echo "lessphp fatal error: ".$ex->getMessage();
		}
		
		return $this->css;
	}
	
	function writeStylesheet( $filename , $prepend ){
		$css = $prepend . $this->css;
		return file_put_contents( $filename , $css );
	}
	
}