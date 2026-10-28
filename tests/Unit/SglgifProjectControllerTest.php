<?php

namespace Tests\Unit;

use App\Modules\Projects\Controllers\SglgifProjectController;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class SglgifProjectControllerTest extends \Tests\TestCase
{
    #[Test]
    public function it_groups_province_modal_projects_in_a_single_pass(): void
    {
        $controller = new SglgifProjectController();
        $method = new \ReflectionMethod(SglgifProjectController::class, 'buildProvinceProjectsModalMap');
        $method->setAccessible(true);

        $rows = collect([
            ['project_code' => 'A', 'province' => 'Abra'],
            ['project_code' => 'B', 'province' => 'Abra'],
            ['project_code' => 'C', 'province' => ''],
            ['project_code' => 'D', 'province' => 'Apayao'],
        ]);
        $labels = new Collection(['Abra', 'Unspecified']);

        $result = $method->invoke($controller, $rows, $labels);

        $this->assertSame(['Abra', 'Unspecified'], $result->keys()->all());
        $this->assertSame(['A', 'B'], $result->get('Abra')->pluck('project_code')->all());
        $this->assertSame(['C'], $result->get('Unspecified')->pluck('project_code')->all());
    }
}
