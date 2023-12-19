<?php

namespace Database\Seeders;

use App\Helpers\Config;
use App\Models\Incident\IncidentCategory;
use App\Models\Incident\IncidentItem;
use App\Models\TicketConfig;
use Illuminate\Database\Seeder;

class IncidentCategoryIncidentItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Config::INCIDENT_CATEGORY_TO_INCIDENT_ITEM as $value){
            $category = IncidentCategory::findOrFail($value[0]);
            $item = IncidentItem::findOrFail($value[1]);
            $category->items()->attach($item);
        }
    }
}