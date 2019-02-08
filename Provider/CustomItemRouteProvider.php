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

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\Routing\RouterInterface;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomItemRouteProvider
{
    public const ROUTE_LIST          = 'mautic_custom_item_list';
    public const ROUTE_VIEW          = 'mautic_custom_item_view';
    public const ROUTE_EDIT          = 'mautic_custom_item_edit';
    public const ROUTE_CLONE         = 'mautic_custom_item_clone';
    public const ROUTE_DELETE        = 'mautic_custom_item_delete';
    public const ROUTE_BATCH_DELETE  = 'mautic_custom_item_batch_delete';
    public const ROUTE_NEW           = 'mautic_custom_item_new';
    public const ROUTE_CANCEL        = 'mautic_custom_item_cancel';
    public const ROUTE_SAVE          = 'mautic_custom_item_save';
    public const ROUTE_LOOKUP        = 'mautic_custom_item_lookup';
    public const ROUTE_LINK          = 'mautic_custom_item_link';
    public const ROUTE_IMPORT_ACTION = 'mautic_import_action';
    public const ROUTE_IMPORT_LIST   = 'mautic_import_index';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param int $objectId
     * @param int $page
     */
    public function buildListRoute(int $objectId, int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['objectId' => $objectId, 'page' => $page]);
    }

    /**
     * @param int $objectId
     */
    public function buildNewRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_NEW, ['objectId' => $objectId]);
    }

    /**
     * @param int      $objectId
     * @param int|null $itemId
     */
    public function buildSaveRoute(int $objectId, ?int $itemId = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildViewRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildEditRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildCloneRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildDeleteRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildLookupRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_LOOKUP, ['objectId' => $objectId]);
    }

    /**
     * @param int $itemId
     */
    public function buildBatchDeleteRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_BATCH_DELETE, ['objectId' => $objectId]);
    }

    /**
     * @param int $objectId
     */
    public function buildNewImportRoute(int $objectId): string
    {
        return $this->buildImportRoute($objectId, 'new');
    }

        /**
     * @param int $objectId
     */
    public function buildListImportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_LIST, ['object' => $this->buildImportRouteObject($objectId)]);
    }

    /**
     * @param int $objectId
     * @param string $objectAction
     */
    private function buildImportRoute(int $objectId, string $objectAction): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_ACTION, [
            'object'       => $this->buildImportRouteObject($objectId),
            'objectAction' => $objectAction
        ]);
    }

    private function buildImportRouteObject(int $objectId): string
    {
        return "custom-object:{$objectId}";
    }
}
