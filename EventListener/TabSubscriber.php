<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class TabSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomObject[]
     */
    private $customObjects = [];

    /**
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(
        CustomObjectModel $customObjectModel
    )
    {
        $this->customObjectModel = $customObjectModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectTabs', 0],
        ];
    }

    /**
     * @param CustomContentEvent $event
     */
    public function injectTabs(CustomContentEvent $event)
    {
        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs')) {
            $vars    = $event->getVars();
            $objects = $this->getCustomObjects();

            foreach ($objects as $object) {
                $data = [
                    'customObjectId' => $object->getId(),
                    'count'          => 55,
                    'title'          => $object->getNamePlural(),
                ];
    
                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:link.html.php', $data);
            }
        }

        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')) {
            $vars    = $event->getVars();
            $contact = $vars['lead'];
            $objects = $this->getCustomObjects();

            foreach ($objects as $object) {
                $data = [
                    'key'   => "custom-object-{$object->getId()}",
                    'page'  => 1,
                    'search' => '',
                    // 'count' => 55,
                    // 'title' => $object->getNamePlural(),
                ];
    
                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:content.html.php', $data);
            }
        }
    }

    /**
     * @return array
     */
    private function getCustomObjects()
    {
        if (!$this->customObjects) {
            $this->customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        }
        
        return $this->customObjects;
    }
}
