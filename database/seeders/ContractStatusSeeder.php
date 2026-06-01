<?php

namespace Database\Seeders;

use App\Models\ContractStatus;
use Illuminate\Database\Seeder;

class ContractStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'جديد', 'color' => '#3B82F6', 'color_text' => '#FFFFFF', 'description' => 'عقد جديد تم إنشاؤه', 'order' => 1],
            ['name' => 'قيد المراجعة', 'color' => '#F59E0B', 'color_text' => '#000000', 'description' => 'العقد قيد المراجعة من قبل الفريق', 'order' => 2],
            ['name' => 'مكتمل', 'color' => '#10B981', 'color_text' => '#FFFFFF', 'description' => 'تم إكمال العقد بنجاح', 'order' => 3],
            ['name' => 'ملغى', 'color' => '#EF4444', 'color_text' => '#FFFFFF', 'description' => 'تم إلغاء العقد', 'order' => 4],
            ['name' => 'معلق', 'color' => '#6B7280', 'color_text' => '#FFFFFF', 'description' => 'العقد معلق حتى استكمال المستندات', 'order' => 5],
            ['name' => 'مستلم', 'color' => '#8B5CF6', 'color_text' => '#FFFFFF', 'description' => 'تم استلام العقد من الموظف', 'order' => 6],
        ];

        foreach ($statuses as $status) {
            ContractStatus::updateOrCreate(
                ['name' => $status['name']],
                array_merge($status, ['is_active' => true])
            );
        }
    }
}
