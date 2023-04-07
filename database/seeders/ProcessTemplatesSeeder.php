<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;

class ProcessTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //load user admin
        $manifest = file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.json'));
        $svg = file_get_contents(app_path('Templates/Fixtures/process-employee-onboarding-template.svg'));

        ProcessCategory::updateOrCreate(
            ['name'  => 'Default Templates'],
            [
                'name' => 'Default Templates',
                'status' => 'ACTIVE',
                'is_system' => 0,
            ]
        );
        // create process templates seed data
        ProcessTemplates::updateOrCreate(
            ['name' => 'Employee Onboarding 2023'],
            [
                'name' => 'Employee Onboarding 2023',
                'description' => 'New version of the employee onboarding process from veggieDonuts',
                'process_id' => null,
                'user_id' => null,
                'process_category_id' => ProcessCategory::where('name', 'Default Templates')->firstOrFail()->getKey(),
                'manifest' => $manifest,
                'svg' => $svg,
                'is_system' => 0,
                'key' => 'default_templates',
            ]);
    }
}
