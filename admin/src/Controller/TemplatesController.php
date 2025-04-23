<?php

namespace Neukom\Component\NeukomTemplating\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

class TemplatesController extends FormController {
    public function copy()
	{
		$this->checkToken();

		$pks = $this->input->post->get('cid', [], 'array');
        
		$model  = $this->getModel('Template', 'Administrator');
		$result = null;

		foreach ($pks as $pk)
		{
			$item = $model->getItem($pk);

			if ($item === false)
			{
				continue;
			}

			$data = $item->getProperties();

			unset($data['id']);
			$data['name'] = $data['name'] . ' copy';

			$result = $model->save($data);

			if ($result === false)
			{
				break;
			}
		}

		$redirect = Route::_('index.php?option=com_neukomtemplating' . $this->getRedirectToListAppend(), false);

		if ($result === false)
		{
			$message = Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED');
			$this->setRedirect($redirect, $message, 'error');

			return false;
		}

		$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_BATCH'));
		$this->setRedirect($redirect);

		return true;
	}

    public function delete()
	{
		$this->checkToken();

		$pks = $this->input->post->get('cid', [], 'array');
        
		$model  = $this->getModel('Template', 'Administrator');
		$model->delete($pks);

		$redirect = Route::_('index.php?option=com_neukomtemplating' . $this->getRedirectToListAppend(), false);

		$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_BATCH'));
		$this->setRedirect($redirect);

		return true;
	}
}
