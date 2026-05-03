<?php

namespace Database\Seeders;

use App\Models\ContractStatus;
use Illuminate\Database\Seeder;

class ContractStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'جديد', 'color' => '#3B82F6', 'color_text' => '#FFFFFF', 'description' => 'عقد جديد تم إنشاؤه'],
            ['name' => 'قيد المراجعة', 'color' => '#F59E0B', 'color_text' => '#000000', 'description' => 'العقد قيد المراجعة من قبل الفريق'],
            ['name' => 'مكتمل', 'color' => '#10B981', 'color_text' => '#FFFFFF', 'description' => 'تم إكمال العقد بنجاح'],
            ['name' => 'ملغى', 'color' => '#EF4444', 'color_text' => '#FFFFFF', 'description' => 'تم إلغاء العقد'],
            ['name' => 'معلق', 'color' => '#6B7280', 'color_text' => '#FFFFFF', 'description' => 'العقد معلق حتى استكمال المستندات'],
            ['name' => 'مستلم', 'color' => '#8B5CF6', 'color_text' => '#FFFFFF', 'description' => 'تم استلام العقد من الموظف'],
        ];

        foreach ($statuses as $status) {
            ContractStatus::updateOrCreate(
                ['name' => $status['name']],
                array_merge($status, ['is_active' => true])
            );
        }
    }
}
