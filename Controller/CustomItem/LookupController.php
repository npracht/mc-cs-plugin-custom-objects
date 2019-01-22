<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;

class LookupController extends JsonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param RequestStack $requestStack
     * @param CustomItemModel $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     */
    public function __construct(
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param integer $objectId
     * 
     * @return JsonResponse
     */
    public function listAction(int $objectId)
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            return new AccessDeniedException($e->getMessage(), $e);
        }

        $request     = $this->requestStack->getCurrentRequest();
        $nameFilter  = InputHelper::clean($request->get('filter'));
        $contactId   = (int) InputHelper::clean($request->get('contactId'));
        $tableConfig = new TableConfig(10, 1, 'CustomItem.name', 'ASC');
        $tableConfig->addFilter(new TableFilterConfig(CustomItem::class, 'customObject', $objectId));
        $tableConfig->addFilterIfNotEmpty(new TableFilterConfig(CustomItem::class, 'name', "%{$nameFilter}%", 'like'));
        $tableConfig->addFilterIfNotEmpty(new TableFilterConfig(CustomItemXrefContact::class, 'contact', $contactId, 'neq'));

        return $this->renderJson(['items' => $this->customItemModel->getLookupData($tableConfig)]);
    }
}