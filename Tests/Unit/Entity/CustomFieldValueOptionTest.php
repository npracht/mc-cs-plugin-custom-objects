<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomFieldValueOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $value1       = 'red';
        $value2       = 'blue';
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $value1);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value1, $optionValue->getValue());

        $optionValue->setValue($value2);

        $this->assertSame($value2, $optionValue->getValue());
    }
}