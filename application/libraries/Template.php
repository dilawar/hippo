<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Template
{
    public $template_data = array();
        
    public function set($name, $value)
    {
        $this->template_data[$name] = $value;
    }
    
    public function load($view = '', $view_data = array(), $return = false)
    {
        $this->CI =& get_instance();
        $this->set('contents', $this->CI->load->view($view, $view_data, true));
        return $this->CI->load->view('template', $this->template_data, $return);
    }
}

/* End of file Template.php */
/* Location: ./system/application/libraries/Template.php */
