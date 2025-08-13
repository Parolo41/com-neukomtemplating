<?php 

namespace Neukom\Component\NeukomTemplating\Site\Service;

use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\Database\DatabaseInterface;

class Router implements RouterInterface
{
    public function __construct($application, $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db) {}

    public function build(&$query) {
        if (!empty($query['act'])) {
            if (in_array($query['act'], array('detail', 'edit', 'contact')) && !empty($query['recordId']) && is_numeric($query['recordId'])) {
                $segments = array($query['act'], $query['recordId']);

                unset($query['act']);
                unset($query['recordId']);

                return $segments;
            } else if (in_array($query['act'], array('new', 'contactsuccess'))) {
                $segments = array($query['act']);

                unset($query['act']);

                return $segments;
            }
        }

        return array();
    }

    public function parse(&$segments) {
        if (count($segments) == 0) {
            return array();
        } else if (count($segments) == 2 && $segments[0] === "detail" && is_numeric($segments[1])) {
            $vars = array('act' => 'detail', 'recordId' => $segments[1]);

            unset($segments[1]);
            unset($segments[0]);

            return $vars;
        } else if (count($segments) == 2 && $segments[0] === "edit" && is_numeric($segments[1])) {
            $vars = array('act' => 'edit', 'recordId' => $segments[1]);

            unset($segments[1]);
            unset($segments[0]);

            return $vars;
        } else if (count($segments) == 2 && $segments[0] === "contact" && is_numeric($segments[1])) {
            $vars = array('act' => 'contact', 'recordId' => $segments[1]);

            unset($segments[1]);
            unset($segments[0]);

            return $vars;
        } else if (count($segments) == 1 && $segments[0] === "new") {
            $vars = array('act' => 'new');

            unset($segments[0]);

            return $vars;
        } else if (count($segments) == 1 && $segments[0] === "contactsuccess") {
            $vars = array('act' => 'contactsuccess');

            unset($segments[0]);

            return $vars;
        }

        return array();
    }

    public function preprocess($query) {
        error_log(var_export($query, true));
        return $query;
    }
}