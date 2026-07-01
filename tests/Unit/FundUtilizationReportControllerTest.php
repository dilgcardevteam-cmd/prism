<?php

namespace Tests\Unit;

use App\Http\Controllers\FundUtilizationReportController;
use PHPUnit\Framework\Attributes\Test;

class FundUtilizationReportControllerTest extends \Tests\TestCase
{
    #[Test]
    public function it_marks_returned_documents_as_lgu_level_for_display(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'resolveFundUtilizationValidatorLevelForDisplay');
        $method->setAccessible(true);

        $level = $method->invoke($controller, [
            'status' => 'returned',
            'uploader_level' => 'lgu',
            'approved_at_dilg_po' => '2026-06-01 09:00:00',
            'approved_at_dilg_ro' => null,
        ]);

        $this->assertSame('lgu', $level);
    }
}
