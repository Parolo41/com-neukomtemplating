<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Neukom\Component\NeukomTemplating\Administrator\Extension\TemplatesComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void {
		$container->registerServiceProvider(new CategoryFactory('\\Neukom\\Component\\NeukomTemplating'));
        $container->registerServiceProvider(new MVCFactory('\\Neukom\\Component\\NeukomTemplating'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Neukom\\Component\\NeukomTemplating'));
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new TemplatesComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
