<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaLandingPageTest extends TestCase
{
    public function test_landing_page_is_rendered_as_an_inertia_react_page(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Landing'));
    }
}
