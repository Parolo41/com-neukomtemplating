<?php

namespace Neukom\Component\NeukomTemplating\Site\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView {
    protected $form;
    protected $item;
    protected $state;
    protected $params;

    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null) {
        Factory::getApplication()->enqueueMessage('display: ' . $template);

        $this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

        // $this->params = $this->state->params;

		// $this->params->merge($this->item->params);

		parent::display($template);
    }
}

?>