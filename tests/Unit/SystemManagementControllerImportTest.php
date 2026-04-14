<?php

namespace Tests\Unit;

use App\Http\Controllers\SystemManagementController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shuchkin\SimpleXLS;

class SystemManagementControllerImportTest extends TestCase
{
    public function test_resolve_import_structure_supports_legacy_subaybayan_template(): void
    {
        $controller = new SystemManagementController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('resolveImportStructure');
        $method->setAccessible(true);

        $templatePath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . 'legacy-subaybayan-template.xls';

        $xls = SimpleXLS::parse($templatePath);
        $this->assertNotFalse($xls, SimpleXLS::parseError() ?: 'Legacy SubayBayan template should be parseable.');

        $columns = array_merge(
            $reflection->getConstant('SUBAYBAYAN_TEMPLATE_HEADERS'),
            ['created_at', 'updated_at']
        );

        $result = $method->invoke($controller, $xls->rows(), $columns, []);

        $this->assertSame(3, $result['dataStartRow']);
        $this->assertSame(87, count($result['headerMap']));
        $this->assertSame('project_code', $result['headerMap'][1] ?? null);
        $this->assertSame('total_estimated_cost_of_project', $result['headerMap'][31] ?? null);
        $this->assertSame('uploaded_images_w_geotag', $result['headerMap'][44] ?? null);
        $this->assertSame('uploaded_images_without_geotag', $result['headerMap'][45] ?? null);
        $this->assertSame('before_w_geotag', $result['headerMap'][46] ?? null);
        $this->assertSame('project_billboard_w_geotag', $result['headerMap'][48] ?? null);
        $this->assertSame('photo_20_40_w_geotag', $result['headerMap'][50] ?? null);
        $this->assertSame('photo_50_70_without_geotag', $result['headerMap'][53] ?? null);
        $this->assertSame('photo_90_w_geotag', $result['headerMap'][54] ?? null);
        $this->assertSame('completed_without_geotag', $result['headerMap'][57] ?? null);
        $this->assertSame('during_the_operation_w_geotag', $result['headerMap'][58] ?? null);
        $this->assertSame('during_the_operation_without_geotag', $result['headerMap'][59] ?? null);
        $this->assertSame('obligation', $result['headerMap'][62] ?? null);
        $this->assertSame('disbursement', $result['headerMap'][63] ?? null);
        $this->assertSame('liquidations', $result['headerMap'][64] ?? null);
        $this->assertSame('ded_pow_prep_notarized_lce_cert', $result['headerMap'][70] ?? null);
        $this->assertSame('installation_of_community_billboard', $result['headerMap'][77] ?? null);
        $this->assertSame('installation_of_community_billboard_2', $result['headerMap'][78] ?? null);
    }

    public function test_resolve_import_structure_prefers_single_row_headers_when_they_map_best(): void
    {
        $controller = new SystemManagementController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('resolveImportStructure');
        $method->setAccessible(true);

        $rows = [
            ['PROGRAM', 'PROJECT CODE', 'PROJECT TITLE', 'STATUS'],
            ['FALGU', 'PRJ-001', 'Road concreting', 'Ongoing'],
        ];
        $columns = ['program', 'project_code', 'project_title', 'status', 'created_at', 'updated_at'];

        $result = $method->invoke($controller, $rows, $columns, []);

        $this->assertSame(1, $result['dataStartRow']);
        $this->assertSame('program', $result['headerMap'][0] ?? null);
        $this->assertSame('project_code', $result['headerMap'][1] ?? null);
        $this->assertSame('project_title', $result['headerMap'][2] ?? null);
        $this->assertSame('status', $result['headerMap'][3] ?? null);
    }
}
