<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

class QueryFilterHelper
{
    use DbalQueryTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var QueryFilterFactory
     */
    private $queryFilterFactory;

    /**
     * @var CustomFieldTypeProvider
     */
    private $fieldTypeProvider;

    /**
     * @var int
     */
    private $itemRelationLevelLimit;

    public function __construct(
        EntityManager $entityManager,
        QueryFilterFactory $queryFilterFactory
    ) {
        $this->entityManager      = $entityManager;
        $this->queryFilterFactory = $queryFilterFactory;
    }

    public function createValueQuery(
        string $alias,
        ContactSegmentFilter $segmentFilter
    ): UnionQueryContainer {
        $unionQueryContainer = $this->queryFilterFactory->createQuery($alias, $segmentFilter);
        $this->addCustomFieldValueExpressionFromSegmentFilter($unionQueryContainer, $alias, $segmentFilter);

        return $unionQueryContainer;
    }

    public function createItemNameQueryBuilder(string $queryBuilderAlias): SegmentQueryBuilder
    {
        $queryBuilder = new SegmentQueryBuilder($this->entityManager->getConnection());

        return $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);
    }

    /**
     * Limit the result to given contact Id, table used is selected by availability
     * CustomFieldValue and CustomItemName are supported.
     *
     * @throws InvalidArgumentException
     */
    public function addContactIdRestriction(SegmentQueryBuilder $queryBuilder, string $queryAlias, int $contactId): void
    {
        if (!$this->hasQueryJoinAlias($queryBuilder, $queryAlias.'_contact')) {
            if (!$this->hasQueryJoinAlias($queryBuilder, $queryAlias.'_value')) {
                throw new InvalidArgumentException('SegmentQueryBuilder contains no usable tables for contact restriction.');
            }
            $tableAlias = $queryAlias.'_contact.contact_id';
        } else {
            $tableAlias = $queryAlias.'_contact.contact_id';
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($tableAlias, ':contact_id_'.$contactId)
        );
        $queryBuilder->setParameter('contact_id_'.$contactId, $contactId);
    }

    public function addCustomFieldValueExpressionFromSegmentFilter(
        UnionQueryContainer $unionQueryContainer,
        string $tableAlias,
        ContactSegmentFilter $filter
    ): void {
        foreach ($unionQueryContainer as $segmentQueryBuilder) {
            $expression = $this->getCustomValueValueExpression($segmentQueryBuilder, $tableAlias, $filter->getOperator());

            $this->addOperatorExpression(
                $segmentQueryBuilder,
                $tableAlias,
                $expression,
                $filter->getOperator(),
                $filter->getParameterValue()
            );
        }
    }

    public function addCustomObjectNameExpression(
        SegmentQueryBuilder $queryBuilder,
        string $tableAlias,
        string $operator,
        ?string $value
    ): void {
        $expression = $this->getCustomObjectNameExpression($queryBuilder, $tableAlias, $operator);
        $this->addOperatorExpression($queryBuilder, $tableAlias, $expression, $operator, $value);
    }

    /**
     * @param CompositeExpression|string            $expression
     * @param array|string|CompositeExpression|null $value
     */
    private function addOperatorExpression(
        SegmentQueryBuilder $segmentQueryBuilder,
        string $tableAlias,
        $expression,
        string $operator,
        $value
    ): void {
        $valueType = null;

        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                break;
            case '!multiselect':
            case 'notIn':
            case 'multiselect':
            case 'in':
                $valueType      = Connection::PARAM_STR_ARRAY;
                $valueParameter = $tableAlias.'_value_value';

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
        }

        switch ($operator) {
            case 'notIn':
                break;
            default:
                $segmentQueryBuilder->andWhere($expression);

                break;
        }

        if (isset($valueParameter)) {
            $segmentQueryBuilder->setParameter($valueParameter, $value, $valueType);
        }
    }

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator.
     *
     * @return CompositeExpression|string
     */
    private function getCustomValueValueExpression(SegmentQueryBuilder $customQuery, string $tableAlias, string $operator)
    {
        switch ($operator) {
            case 'empty':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->eq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                );

                break;
            case 'notEmpty':
                $expression = $customQuery->expr()->andX(
                    $customQuery->expr()->isNotNull($tableAlias.'_value.value'),
                    $customQuery->expr()->neq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                );

                break;
            case 'notIn':
            case '!multiselect':
            case 'in':
            case 'multiselect':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_value.value',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->neq($tableAlias.'_value.value', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_value.value')
                );

                break;
            case 'contains':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->like($tableAlias.'_value.value', "%:{$valueParameter}%");

                break;
            case 'notLike':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->like($tableAlias.'_value.value', ":${valueParameter}")
                );

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_value.value',
                    ":${valueParameter}"
                );
        }

        return $expression;
    }

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator.
     *
     * @return CompositeExpression|string
     */
    private function getCustomObjectNameExpression(SegmentQueryBuilder $customQuery, string $tableAlias, string $operator)
    {
        switch ($operator) {
            case 'empty':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->eq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
                );

                break;
            case 'notEmpty':
                $expression = $customQuery->expr()->andX(
                    $customQuery->expr()->isNotNull($tableAlias.'_item.name'),
                    $customQuery->expr()->neq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
                );

                break;
            case 'notIn':
            case 'in':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias.'_item.name', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_item.name')
                );

                break;
            case 'notLike':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->like($tableAlias.'_item.name', ":${valueParameter}")
                );

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );
        }

        return $expression;
    }

    /**
     * Get all tables currently registered in the queryBuilder and check is alias is present.
     */
    private function hasQueryJoinAlias(SegmentQueryBuilder $queryBuilder, $alias): bool
    {
        $joins    = array_column($queryBuilder->getQueryParts()['join'], 0);
        $tables   = array_column($joins, 'joinAlias');
        $tables[] = $queryBuilder->getQueryParts()['from'][0]['alias'];

        return in_array($alias, $tables, true);
    }

    /**
     * Get basic query builder with contact reference and item join.
     */
    private function getBasicItemQueryBuilder(SegmentQueryBuilder $queryBuilder, string $alias): SegmentQueryBuilder
    {
        $customFieldQueryBuilder = $queryBuilder->createQueryBuilder();

        $customFieldQueryBuilder
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', $alias.'_contact')
            ->leftJoin(
                $alias.'_contact',
                MAUTIC_TABLE_PREFIX.'custom_item',
                $alias.'_item',
                $alias.'_item.id='.$alias.'_contact.custom_item_id'
            );

        return $customFieldQueryBuilder;
    }
}
