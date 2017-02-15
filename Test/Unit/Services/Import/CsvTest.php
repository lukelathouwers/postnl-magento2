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
namespace TIG\PostNL\Unit\Services\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface as DirectoryReadInterface;
use Magento\Framework\Filesystem\File\ReadInterface as FileReadInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\Import;
use TIG\PostNL\Services\Import\Csv;
use TIG\PostNL\Test\TestCase;

/**
 * Class CsvTest
 *
 * @package TIG\PostNL\Unit\Services\Import
 */
class CsvTest extends TestCase
{
    protected $instanceClass = Csv::class;

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'no_records' => [
                ['column_1', 'column_2'],
                []
            ],
            'single_record' => [
                ['column_1', 'column_2'],
                [
                    ['record_1_1', 'record_1_2']
                ]
            ],
            'multiple_records' => [
                ['column_1', 'column_2'],
                [
                    ['record_2_1', 'record_2_2'],
                    ['record_3_1', 'record_3_2'],
                    ['record_4_1', 'record_4_2']
                ]
            ],
        ];
    }

    /**
     * @param $columns
     * @param $records
     *
     * @dataProvider getDataProvider
     */
    public function testGetData($columns, $records)
    {
        $fileReadInterface = $this->getMockBuilder(FileReadInterface::class)->getMock();

        $dirReadInterface = $this->getMockBuilder(DirectoryReadInterface::class)->getMock();
        $dirReadInterface->expects($this->any())->method('openFile')->willReturn($fileReadInterface);

        $filesystemMock = $this->getFakeMock(Filesystem::class)->setMethods(['getDirectoryRead'])->getMock();
        $filesystemMock->expects($this->any())->method('getDirectoryRead')->willReturn($dirReadInterface);

        $importMock = $this->getFakeMock(Import::class)->setMethods(['getData', 'getColumns'])->getMock();
        $importMock->expects($this->any())->method('getColumns')->willReturn($columns);
        $importMock->expects($this->any())->method('getData')->willReturn($records);

        $instance = $this->getInstance(['filesystem' => $filesystemMock, 'import' => $importMock]);
        $result = $instance->getData('somefile.csv', 1, 'package_value');

        $this->assertInternalType('array', $result);

        $this->assertArrayHasKey('columns', $result);
        $this->assertEquals($columns, $result['columns']);

        $this->assertArrayHasKey('records', $result);
        $this->assertEquals($records, $result['records']);
    }

    /**
     * @return array
     */
    public function checkImportErrorsProvider()
    {
        return [
            'no_errors' => [
                false,
                []
            ],
            'single_error' => [
                true,
                ['Error abc']
            ],
            'multiple_errors' => [
                true,
                ['Error def', 'Error ghi']
            ]
        ];
    }

    /**
     * @param $hasErrors
     * @param $errorMessages
     *
     * @throws LocalizedException
     * @throws \Exception
     *
     * @dataProvider checkImportErrorsProvider
     */
    public function testCheckImportErrors($hasErrors, $errorMessages)
    {
        $import = $this->getFakeMock(Import::class)->setMethods(['hasErrors', 'getErrors'])->getMock();
        $import->expects($this->once())->method('hasErrors')->willReturn($hasErrors);
        $import->expects($this->exactly((int)$hasErrors))->method('getErrors')->willReturn($errorMessages);

        $instance = $this->getInstance(['import' => $import]);

        $expectedErrorMessage = 'We couldn\'t import this file because of these errors: ';
        $expectedErrorMessage .= implode(" \n", $errorMessages);

        try {
            $this->invoke('checkImportErrors', $instance);
            $this->assertFalse($hasErrors);
        } catch (LocalizedException $exception) {
            if (!$hasErrors) {
                throw $exception;
            }

            $this->assertTrue($hasErrors);
            $this->assertEquals($expectedErrorMessage, $exception->getMessage());
        }
    }
}
