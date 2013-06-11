<?php
if (!class_exists('WP_List_Table'))
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_FeesTable extends WP_List_Table {

	public function __construct() {
		parent::__construct(array(
			'singular' => _x('fee', 'fees admin', 'WPBDM'),
			'plural' => _x('fees', 'fees admin', 'WPBDM'),
			'ajax' => false
		));
	}

	public function no_items() {
		echo _x('You do not have any listing fees setup yet.', 'fees admin', 'WPBDM');
	}

    public function get_columns() {
        return array(
        	'label' => _x('Label', 'fees admin', 'WPBDM'),
        	'amount' => _x('Amount', 'fees admin', 'WPBDM'),
        	'duration' => _x('Duration', 'fees admin', 'WPBDM'),
        	'images' => _x('Images', 'fees admin', 'WPBDM'),
        	'categories' => _x('Applied To', 'fees admin', 'WPBDM')
		);
    }

	public function prepare_items() {
		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

		$api = wpbdp_fees_api();
		$this->items = $api->get_fees();
	}

	/* Rows */
	public function column_label($fee) {
		$actions = array();
		$actions['edit'] = sprintf('<a href="%s">%s</a>',
								   esc_url(add_query_arg(array('action' => 'editfee', 'id' => $fee->id))),
								   _x('Edit', 'fees admin', 'WPBDM'));
		$actions['delete'] = sprintf('<a href="%s">%s</a>',
								   esc_url(add_query_arg(array('action' => 'deletefee', 'id' => $fee->id))),
								   _x('Delete', 'fees admin', 'WPBDM'));

		$html = '';
		$html .= sprintf('<strong><a href="%s">%s</a></strong>',
					   	 esc_url(add_query_arg(array('action' => 'editfee', 'id' => $fee->id))),
					     esc_attr($fee->label));
		$html .= $this->row_actions($actions);

		return $html;
	}

	public function column_amount($fee) {
		return $fee->amount;
	}

	public function column_duration($fee) {
		if ($fee->days == 0)
			return _x('Forever', 'fees admin', 'WPBDM');
		return sprintf(_nx('%d day', '%d days', $fee->days, 'fees admin', 'WPBDM'), $fee->days);
	}

	public function column_images($fee) {
		return sprintf(_nx('%d image', '%d images', $fee->images, 'fees admin', 'WPBDM'), $fee->images);
	}

	public function column_categories($fee) {
		if ($fee->categories['all'])
			return _x('All categories', 'fees admin', 'WPBDM');

		$names = array();

		foreach ($fee->categories['categories'] as $category_id) {
			if ($category = get_term($category_id, wpbdp()->get_post_type_category())) {
				$names[] = $category->name;
			}
		}

		return $names ? join($names, ', ') : '--';
	}

}


class WPBDP_FeesAdmin {

	public function __construct() {
		$this->admin = wpbdp()->admin;
		$this->api = wpbdp()->fees;
	}

    public function dispatch() {
    	$action = wpbdp_getv($_REQUEST, 'action');
    	$_SERVER['REQUEST_URI'] = remove_query_arg(array('action', 'id'), $_SERVER['REQUEST_URI']);

    	switch ($action) {
    		case 'addfee':
    		case 'editfee':
    			$this->processFieldForm();
    			break;
    		case 'deletefee':
    			$this->delete_fee();
    			break;
    		default:
    			$this->feesTable();
    			break;
    	}
    }

    public static function admin_menu_cb() {
    	$instance = new WPBDP_FeesAdmin();
    	$instance->dispatch();
    }

    /* field list */
    private function feesTable() {
    	$table = new WPBDP_FeesTable();
    	$table->prepare_items();

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees.tpl.php',
                          array('table' => $table),
                          true);    		    	
    }

	private function processFieldForm() {
		if (isset($_POST['fee'])) {
			if ($this->api->add_or_update_fee($_POST['fee'], $errors)) {
				$this->admin->messages[] = _x('Fee updated.', 'fees admin', 'WPBDM');
				return $this->feesTable();
			} else {
				$errmsg = '';
				foreach ($errors as $err)
					$errmsg .= sprintf('&#149; %s<br />', $err);

				$this->admin->messages[] = array($errmsg, 'error');
			}
		}

		$fee = isset($_GET['id']) ? $this->api->get_fee_by_id($_GET['id']) : null;

		wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees-addoredit.tpl.php',
						  array('fee' => $fee),
						  true);
	}

	private function delete_fee() {
		global $wpdb;

		if (isset($_POST['doit'])) {
			$this->api->delete_fee($_POST['id']);
			$this->admin->messages[] = _x('Fee deleted.', 'fees admin', 'WPBDM');

			return $this->feesTable();
		} else {
			if ($fee = $this->api->get_fee_by_id($_REQUEST['id'])) {
				wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees-confirm-delete.tpl.php',
								  array('fee' => $fee),
								  true);
			}
		}
	}

	}