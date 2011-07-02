<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * XtraUpload
 *
 * A turn-key open source web 2.0 PHP file uploading package requiring PHP v5
 *
 * @package	 XtraUpload
 * @author	  Matthew Glinski
 * @copyright   Copyright (c) 2006, XtraFile.com
 * @license	 http://xtrafile.com/docs/license
 * @link		http://xtrafile.com
 * @since	   Version 2.0
 * @filesource
 */

/**
 * XtraUpload Config Controller
 *
 * @package	 XtraUpload
 * @subpackage  Controllers
 * @category	Controllers
 * @author	  Matthew Glinski
 * @author	  momo-i
 * @link		http://xtrafile.com/docs
 */
class Config extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin_access');
		$this->load->model('users');
		$this->load->helper('string');
	}

	// ------------------------------------------------------------------------

	/**
	 * Config->index()
	 *
	 * Load the site config into xHTML for editing/viewing
	 *
	 * @access  public
	 * @return  none
	 */
	public function index()
	{
		// Get the DB config object
		$data['configs'] = $this->db->get_where('config', array('name !=' => '_db_version', 'group' => 0));

		// Load a message
		$data['flash_message'] = '';
		if($this->session->flashdata('msg'))
		{
			$data['flash_message'] = '<span class="info"><strong>'.$this->session->flashdata('msg').'</strong></span>';
		}

		// Send the files to the user
		$this->load->view($this->startup->skin.'/header', array('header_title' => lang('Manage Site Config')));
		$this->load->view($this->startup->skin.'/admin/config/main',$data);
		$this->load->view($this->startup->skin.'/footer');
	}

	public function plugin($name='')
	{
		// Get the DB config object
		if($name != '')
		{
			$data['configs'] = $this->db->get_where('config', array('name !=' => '_db_version', 'group' => $name ));
		}
		else
		{
			$data['configs'] = $this->db->get_where('config', array('name !=' => '_db_version', '`group` !=' => 0));
		}
		$data['num_rows'] = intval($data['configs']->num_rows());
		$data['name'] = $name;

		// Load a message
		$data['flash_message'] = '';
		if($this->session->flashdata('msg'))
		{
			$data['flash_message'] = '<span class="info"><strong>'.$this->session->flashdata('msg').'</strong></span>';
		}

		// Send the files to the user
		$this->load->view($this->startup->skin.'/header', array('header_title' => sprintf(lang('Manage %s Plugin Config'), ucwords(str_replace('_',' ', $name)))));
		$this->load->view($this->startup->skin.'/admin/config/plugin',$data);
		$this->load->view($this->startup->skin.'/footer');
	}

	// ------------------------------------------------------------------------

	/**
	 * Config->update()
	 *
	 * Process a new config object save request
	 *
	 * @access  public
	 * @return  none
	 */
	public function update()
	{
		// If the user has posted new values
		if($this->input->post('valid'))
		{
			// Update the config
			$this->_update_site_config();

			// Send a success message
			$this->session->set_flashdata('msg', lang('Config Updated!'));
			redirect('admin/config');
		}
		else
		{
			// Redirect back to main page
			 redirect('admin/config');
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Config->_update_site_config()
	 *
	 * Save a new config object and write it to the cache
	 *
	 * @access  public
	 * @return  none
	 */
	private function _update_site_config()
	{
		// Encrypt the cache file name for security
		$config_file_name = md5($this->config->config['encryption_key'].'site_config');
		$configData = array();

		// Iterate over the submited values and update each config entry in the DB
		foreach($_POST as $key => $value)
		{
			if($key != 'valid' && $key != 'Submit' && $key != '_db_version')
			{
				// Save the name and value for caching later
				$configData[$key] = $this->input->post($key);

				// Format the update query
				$data = array(
				   'value' => $this->input->post($key)
				);

				// Submit the config to the DB
				$this->db->where('name', $key);
				$this->db->update('config', $data);
			}
		}

		// Cache the results to the filesystem for quick loading
		file_put_contents(CACHEPATH.$config_file_name, base64_encode(serialize($configData)));

		// Send updates to all servers
		$this->load->library('Remote_server_xml_rpc');
		$this->remote_server_xml_rpc->update_cache();

		return true;
	}
}

/* End of file admin/config.php */
/* Location: ./application/controllers/admin/config.php */