<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2017 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Test\Unit\Block\Adminhtml\Config\Carrier;

use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use TIG\PostNL\Block\Adminhtml\Config\Carrier\Tablerate;
use TIG\PostNL\Model\ResourceModel\Tablerate as TablerateResource;
use TIG\PostNL\Model\ResourceModel\TablerateFactory;
use TIG\PostNL\Services\Import\Csv;
use TIG\PostNL\Test\TestCase;

/**
 * Class TablerateTest
 *
 * @package TIG\PostNL\Test\Unit\Block\Adminhtml\Config\Carrier
 */
class TablerateTest extends TestCase
{
    protected $instanceClass = Tablerate::class;

    public function testAfterSave()
    {
        $tablerateMock = $this->getFakeMock(TablerateResource::class);
        $tablerateMock->setMethods(['getConditionName', 'uploadAndImport']);
        $tablerateMock = $tablerateMock->getMock();

        $tablerateMock->expects($this->once())->method('getConditionName');
        $tablerateMock->expects($this->once())->method('uploadAndImport');

        $tablerateFactoryMock = $this->getFakeMock(TablerateFactory::class)->setMethods(['create'])->getMock();
        $tablerateFactoryMock->expects($this->once())->method('create')->willReturn($tablerateMock);

        $instance = $this->getInstance(['tablerateFactory' => $tablerateFactoryMock]);
        $result = $instance->afterSave();

        $this->assertInstanceOf(Tablerate::class, $result);
    }

    /**
     * @return array
     */
    public function getCsvDataProvider()
    {
        return [
            'no_file_uploaded' => [
                null,
                []
            ],
            'file_uploaded_with_data' => [
                'datafile.csv',
                [
                    'content data',
                    'content data'
                ]
            ],
            'file_uploaded_without_data' => [
                'datafile.csv',
                []
            ],
        ];
    }

    /**
     * @param $file
     * @param $data
     *
     * @dataProvider getCsvDataProvider
     */
    public function testGetCsvData($file, $data)
    {
        $expectedCallCount = (int)(null !== $file);

        $_FILES['groups']['tmp_name']['tig_postnl']['fields']['import']['value'] = $file;

        $websiteMock = $this->getFakeMock(WebsiteInterface::class)->getMock();
        $websiteMock->expects($this->exactly($expectedCallCount))->method('getId')->willReturn('1');

        $storeManagerMock = $this->getFakeMock(StoreManagerInterface::class)->getMock();
        $storeManagerMock->expects($this->exactly($expectedCallCount))->method('getWebsite')->willReturn($websiteMock);

        $csv = $this->getFakeMock(Csv::class)->setMethods(['getData'])->getMock();
        $csv->expects($this->exactly($expectedCallCount))->method('getData')->willReturn($data);

        $instance = $this->getInstance(['storeManager' => $storeManagerMock, 'csv' => $csv]);
        $result = $this->invokeArgs('getCsvData', [1, 'package_value'], $instance);

        $this->assertInternalType('array', $result);
    }
}
