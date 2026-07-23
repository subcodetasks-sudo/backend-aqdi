<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $statuses = [
            [
                'name' => 'مستلم من الموظف',
                'color' => '#F97316',
                'color_text' => '#FFFFFF',
                'description' => 'الحالة الآن - يراجع فريقنا بيانات طلبك',
                'order' => 7,
            ],
            [
                'name' => 'إرسال مسودة العقد لكم عبر واتساب',
                'color' => '#22C55E',
                'color_text' => '#FFFFFF',
                'description' => 'تصلك المسودة للاطلاع والمراجعة قبل التوثيق',
                'order' => 8,
            ],
            [
                'name' => 'توثيق العقد في إيجار',
                'color' => '#0EA5E9',
                'color_text' => '#FFFFFF',
                'description' => 'يُوثّق العقد ويصبح جاهزاً للتحميل',
                'order' => 9,
            ],
        ];

        if (Schema::hasTable('contract_statuses')) {
            foreach ($statuses as $status) {
                $exists = DB::table('contract_statuses')->where('name', $status['name'])->exists();
                if ($exists) {
                    continue;
                }

                $row = [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'color_text' => $status['color_text'],
                    'description' => $status['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('contract_statuses', 'order')) {
                    $row['order'] = $status['order'];
                }

                DB::table('contract_statuses')->insert($row);
            }
        }

        if (Schema::hasTable('draft_contract_statuses')) {
            foreach ($statuses as $status) {
                $exists = DB::table('draft_contract_statuses')->where('name', $status['name'])->exists();
                if ($exists) {
                    continue;
                }

                DB::table('draft_contract_statuses')->insert([
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'color_text' => $status['color_text'],
                    'description' => $status['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'مستلم من الموظف',
            'إرسال مسودة العقد لكم عبر واتساب',
            'توثيق العقد في إيجار',
        ];

        if (Schema::hasTable('contract_statuses')) {
            DB::table('contract_statuses')->whereIn('name', $names)->delete();
        }

        if (Schema::hasTable('draft_contract_statuses')) {
            DB::table('draft_contract_statuses')->whereIn('name', $names)->delete();
        }
    }
};
