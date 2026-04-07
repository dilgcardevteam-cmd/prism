<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = TicketCategory::defaultCategories();
        $activeCategoryNames = collect($categories)->pluck('name')->all();

        TicketCategory::query()
            ->whereNotIn('name', $activeCategoryNames)
            ->update(['is_active' => false]);

        foreach ($categories as $category) {
            TicketCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
