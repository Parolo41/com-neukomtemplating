<?php

namespace Neukom\Component\NeukomTemplating\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Neukom\Component\NeukomTemplating\Administrator\Service\HTML\AdministratorService;
use Psr\Container\ContainerInterface;

class TemplatesComponent extends MVCComponent implements BootableExtensionInterface, CategoryServiceInterface, RouterServiceInterface {
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    
    public function boot(ContainerInterface $container) {
        $this->getRegistry()->register('templatesadministrator', new AdministratorService);
    }
}
