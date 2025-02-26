<?php

namespace Neukom\Component\NeukomTemplating\Administrator\View\Template;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView {
    protected $form;
    protected $item;

    public function display($tpl = null) {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->addToolbar();
        return parent::display($tpl);
    }

    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ($this->item?->id == 0);
        ToolbarHelper::title($isNew ? Text::_('COM_NEUKOMTEMPLATING_MANAGER_NEW') : Text::_('COM_NEUKOMTEMPLATING_MANAGER_EDIT'), 'address foo');
        ToolbarHelper::apply('template.apply');
        ToolbarHelper::cancel('template.cancel', 'JTOOLBAR_CLOSE');
    }
}
