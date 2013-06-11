<?php

class TipTour{
	
	private $id;
	private $stepCount;
	private $steps;	//Maps step IDs to objects
	private $pages; //Maps page IDs to step IDs
	
	private $current_page_slug;
	
	private $pagePointers;
	
	function TipTour( $id ){
		$this->id = $id;
		
		$this->stepCount = 0;
		$this->steps = array();
		$this->pages = array();
						
		$this->current_page_slug = '_';
		if ( isset($_GET['page']) ) $this->current_page_slug = $_GET['page'];
		
	}
	
	function tourOn(){
		
		if( isset( $_POST['restart-tour-'.$this->id] ) ) {
			$this->resetTour();
			return true;
		}
		
		//check if tour should be loaded
		return get_option( 'tiptour_on_'.$this->id , 1 ) == 1 ? true : false;
	}
	
	function resetTour(){
		unset( $_GET['tour_step'] );	//this makes sure we start the tour at 0, even if we've previously gotten to a higher step without leaving the page
		update_option( 'tiptour_on_'.$this->id , 1 );
	}
	
	function resetTourButton(){
		return '
				<form action="" method="post" class="reset-tour" >
					<input type="submit" value="Restart Tour" name="restart-tour-'.$this->id.'" />
				</form>
				';
	}
	
	function toggleTour( $toggle ){ /* true or false */
		if( !is_bool( $toggle ) ) $toggle = false;
		$toggle = $toggle ? 1 : 0;
		$done = update_option( 'tiptour_on_'.$this->id , $toggle );
	}
	
	function loadTour(){
		
		if( !$this->tourOn() ) return;
		
		//check pages - only load on appropriate pages
		global $pagenow;
		
		if( isset( $this->pages[$pagenow] ) && isset( $this->pages[$pagenow][$this->current_page_slug] ) ){ 
		
			add_action( 'admin_enqueue_scripts', array( &$this , 'loadStyles' ) );
			add_action( 'admin_enqueue_scripts', array( &$this , 'loadScripts' ) );
			//add_action( 'admin_head', array( &$this, 'admin_head' ) );
			add_action( 'admin_print_footer_scripts', array( &$this, 'printPointers' ), 99 );
			//echo '<br/><br/>loaded';
			add_action( 'wp_ajax_end_tiptour_'.$this->id, array( $this, 'endTour' ) , 10 , 0 );	//TODO
			
		}

	}
	
	
	function addStep( $step ){
		
		$this->steps[$this->stepCount] = $step;
		$step->step_index = $this->stepCount;
		
		if( !isset( $this->pages[$step->page] ) ) $this->pages[$step->page] = array();
		if( !isset( $this->pages[$step->page][$step->page_slug] ) ) $this->pages[$step->page][$step->page_slug] = array();
		$this->pages[$step->page][$step->page_slug][] = $this->stepCount;
		
		$this->stepCount++;
		
	}
	
	function endTour(){
		
		check_ajax_referer( $this->nonceAction() , 'security' );
		
		//update option to disable tour
		$this->toggleTour( false );		
		
		die(); // this is required to return a proper result
	}
	
	
	function loadStyles(){
		wp_enqueue_style( 'wp-pointer' );
	}
	
	
	function loadScripts(){		
		
		wp_enqueue_script( 'jquery-ui' ); 
		wp_enqueue_script( 'wp-pointer' ); 
		wp_enqueue_script( 'utils' );
		
	}
	
	function printPointers(){
		global $pagenow;
		$tour_step = isset( $_GET['tour_step'] ) ? $_GET['tour_step'] : 0;
		
		if( isset( $this->pages[$pagenow] ) && isset( $this->pages[$pagenow][$this->current_page_slug] ) ){
				
			//For the first step, only load the pointers for this page if step 2 is also on this page
			if( $tour_step == 0 && 
				( 	$this->steps[1]->page != $pagenow ||
					$this->steps[1]->page_slug != $this->current_page_slug ) ) {
					
					$this->pagePointers = array( 0 );
			}
			else{
				unset( $this->pages[$pagenow][$this->current_page_slug][0] );
				$this->pagePointers = $this->pages[$pagenow][$this->current_page_slug];
			}
			
			?>
			<script type="text/javascript">
			
				jQuery(document).ready( function($){
					<?php $this->buildPointerJS( $tour_step ); ?>
				});
			
				/**
				 * jQuery.ScrollTo - Easy element scrolling using jQuery.
				 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
				 * Dual licensed under MIT and GPL.
				 * Date: 5/25/2009
				 * @author Ariel Flesler
				 * @version 1.4.2
				 *
				 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
				 */
				;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);
			</script>

		<?php
		}
		
	}

	//recursive
	function buildPointerJS( $k ){
		
		$done = false;
		$jump = false;
		$step = $this->steps[$k]; //$this->pagePointers[$k]];
		//$this->steps[$step_index];
		$step_index = $step->step_index;
		
		//Stay on the same page
		$href = '#';
		//if( count( $this->pagePointers ) > $k+1 ){
		//If the next step is also on this page
		if( in_array( $k+1 , $this->pagePointers ) ){
			//Open the next pointer
			$href = '#';
			$jump = false;
		}
		//Move onto the next page
		else{
			//redirect to the next page, if it exists
			if( isset( $this->steps[$step_index+1] ) ){
				$nextPointer = $this->steps[$step_index+1];
				$href = $nextPointer->getURL();
				$jump = true;
			}
			else{
				$done = true;
				$jump = false;
			}
			
		} 
		
		?>
		
		// Start Step <?php echo $k; ?>
		
		var options = {
			
			 content: '<?php echo addslashes( $step->content ); ?>'
			,position: {
				edge: '<?php echo $step->position; ?>',
				offset: '<?php echo $step->offset; ?>'
			}
			,buttons: function( event, t ) {
				var $buttonClose = jQuery('<a class="button-secondary" style="margin-right:10px;" href="#">End Tour</a>');
				$buttonClose.bind( 'click.pointer', function() {
					
					var data = {
						action: 'end_tiptour_<?php echo $this->id; ?>',
						security: '<?php echo wp_create_nonce( $this->nonceAction() ); ?>',
					};
					
					$.post(ajaxurl, data, function(response) {
						//console.log(response);
					});
					
					t.element.pointer('close');
				});
				
				<?php if( !$done ): ?>
				var $buttonNext = $('<a class="button-primary" href="<?php echo $href; ?>"><?php echo $step->nextText; ?></a>');
				$buttonNext.bind( 'click.pointer', function(){
					
					t.element.pointer('close');
					
					<?php
					
					//if we're staying put,open the next dialog
					if( !$jump ){
						//echo 'console.log("build pointer js for '.($k+1).'");';						
						$this->buildPointerJS( $k+1 );
					}
					
					?>
					
				});
				
				<?php endif; ?>
				
				var buttons = $('<div class="tiptour-buttons">');
				<?php if( !$done ){ ?>buttons.append($buttonNext); <?php } ?>
				buttons.append($buttonClose);
				return buttons;
			}
		};
		
		$('<?php echo $step->selector; ?>').pointer( options ).pointer( 'open' );
		$.scrollTo( '<?php echo $step->selector; ?>', 800 , { offset : -100 } );
		
		// End Step <?php echo $k; ?>
		
		<?php
	}
	
	function nonceAction(){
		return 'end-tiptour-'.$this->id;
	}
	
}



class TipTourStep{
	
	public $page;
	public $page_slug;
	
	public $selector;
	public $content;
	public $position;
	public $offset;
	
	public $nextText;
	
	public $step_index;
	
	function TipTourStep( $page , $page_slug , $selector , $title , $text , $position , $offset = '0 0', $nextText = 'Next' ){
		
		$this->page = $page;
		$this->page_slug = $page_slug == '' ? '_' : $page_slug;
		$this->selector = $selector;
		$this->content = "<h3>$title</h3><div>$text</div>";
		$this->position = $position;
		$this->offset = $offset;
		//$this->buttons = $buttons;
		
		$this->nextText = $nextText;
		
	}
	
	function getURL(){
		$path = $this->page;
		if( $this->page_slug != '_' ) $path.= "?page=$this->page_slug&tour_step=".($this->step_index); 
		else $path.= "?tour_step=".($this->step_index);
		return admin_url( $path );
	}
	
	
}
