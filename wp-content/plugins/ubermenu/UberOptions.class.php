<?php

class UberOptions extends UberSparkOptions{
	
	
	function __construct( $id, $config = array() , $links = array() ){
		
		parent::__construct( $id, $config , $links );
		
		$this->options_key = self::generateOptionsKey( $this->id );
		
	}
	
	
	public static function generateOptionsKey( $id ){
		return $id;
	}
	
	public function previewButton(){
		global $uberMenu;
		return '<input type="submit" value="Preview" name="ubermenu-preview-button" id="ubermenu-preview-button" class="button reset-button" />'.
				'<div id="ubermenu-style-preview"></div>'.
				'<input type="submit" value="Show/Hide CSS" name="ubermenu-style-viewer-button" id="ubermenu-style-viewer-button" class="button reset-button" />'.
				'<div id="ubermenu-style-viewer"><textarea disabled>'.$uberMenu->getGeneratorCSS().'</textarea></div>';
		
	}
	
}