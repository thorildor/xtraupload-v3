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
 * XtraUpload Extend Controller
 *
 * @package	 XtraUpload
 * @subpackage  Controllers
 * @category	Controllers
 * @author	  Matthew Glinski
 * @author	  momo-i
 * @link		http://xtrafile.com/docs
 */
class Extend extends CI_Controller {

	private $installed='';
	private $not_installed='';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin_access');
		$this->load->helper('string');
		$this->load->helper('text');
	}

	public function index()
	{
		redirect('admin/extend/view');
	}

	public function view()
	{
		$this->_get_installed_plugins();
		$this->_get_not_installed_plugins();

		$data['installed']=array();
		$data['not_installed']=array();

		foreach($this->installed as $name)
		{
			$data['installed'][$name] = simplexml_load_file(APPPATH."extend/".$name."/main.xml");
		}

		foreach($this->not_installed as $name)
		{
			$data['not_installed'][$name] = simplexml_load_file(APPPATH."extend/".$name."/main.xml");
		}

		$data['flash_message'] = '';
		if($this->session->flashdata('msg'))
		{
			$data['flash_message'] = '<span class="info"><strong>'.$this->session->flashdata('msg').'</strong></span>';
		}

		$this->load->view($this->startup->skin.'/header', array('header_title' => lang('Plugin Manager')));
		$this->load->view($this->startup->skin.'/admin/extend/view', $data);
		$this->load->view($this->startup->skin.'/footer');
	}

	// ------------------------------------------------------------------------

	public function install($name)
	{
		$name = str_replace(array('../', '..'), '', $name);
		$num_rows = $this->db->get_where('extend', array('file_name' => $name))->num_rows();
		if(file_exists(APPPATH."extend/".$name.'/main.php') && file_exists(APPPATH."extend/".$name."/main.xml") && $num_rows == 0)
		{
			$xml = simplexml_load_file(APPPATH."extend/".$name."/main.xml");
			$data = array(
				'data' => serialize($xml),
				'file_name' => $name,
				'date' => time(),
				'active' => '1',
				'uid' => $this->session->userdata('id'),
			);

			$this->db->insert('extend', $data);

			$this->load->extention($name);
			$this->$name->install();

			$this->session->set_flashdata('msg', sprintf(lang('Plugin "%s" Installed'), ucwords(str_replace('_', ' ', $name))));
		}
		$this->_updateCache();
		redirect('admin/extend/view');
	}

	// ------------------------------------------------------------------------

	public function remove($name)
	{	 $name = str_replace(array('../', '..'), '', $name);
		$this->load->extention($name);
		$this->$name->uninstall();

		$this->db->delete('extend', array('file_name' => $name));
		$this->session->set_flashdata('msg', sprintf(lang('Plugin "%s" Uninstalled'), ucwords(str_replace('_', ' ', $name))));
		$this->_updateCache();
		redirect('admin/extend/view');
	}

	// ------------------------------------------------------------------------

	public function turn_on($name)
	{
		$this->db->where('file_name', $name)->update('extend', array('active' => 1));
		$this->session->set_flashdata('msg', sprintf(lang('Plugin "%s" Activated'), ucwords(str_replace('_', ' ', $name))));
		$this->_updateCache();
		redirect('admin/extend/view');
	}

	// ------------------------------------------------------------------------

	public function turn_off($name)
	{
		$this->db->where('file_name', $name)->update('extend', array('active' => 0));
		$this->session->set_flashdata('msg', sprintf(lang('Plugin "%s" Deactivated'), ucwords(str_replace('_', ' ', $name))));
		$this->_updateCache();
		redirect('admin/extend/view');
	}

	// ------------------------------------------------------------------------

	private function _update_cache()
	{
		$extend_file_name = md5($this->config->config['encryption_key'].'extend');

		$data = array();
		$db1 = $this->db->get_where('extend', array('active' => 1));
		foreach($db1->result() as $plugin)
		{
			$data[] = $plugin->file_name;
		}

		if(empty($data))
		{
			@unlink(CACHEPATH . $extend_file_name);
		}
		else
		{
			$final = base64_encode(serialize($data));
			file_put_contents(CACHEPATH . $extend_file_name, $final);
		}

		$this->load->library('remote_server_xml_rpc');
		$this->remote_server_xml_rpc->update_cache();
	}

	// ------------------------------------------------------------------------

	private function _get_installed_plugins()
	{
		if(is_array($this->installed))
		{
			return $this->installed;
		}

		$this->installed = array();
		$db1 = $this->db->get('extend');
		foreach($db1->result() as $plugin)
		{
			$this->installed[] = $plugin->file_name;
		}
		return $this->installed;
	}

	// ------------------------------------------------------------------------

	private function _get_not_installed_plugins()
	{
		if(is_array($this->not_installed))
		{
			return $this->not_installed;
		}

		$this->not_installed = array();
		$dir = APPPATH."extend/";

		// Open a known directory, and proceed to read its contents
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if(is_dir($dir . $file) && $file != '.' && $file != '..' && $file != '.svn' && ! in_array($file, $this->installed))
					{
						$this->not_installed[] = $file;
					}
				}
				closedir($dh);
			}
		}
		return $this->not_installed;
	}
}

/* End of file admin/extend.php */
/* Location: ./application/controllers/admin/extend.php */