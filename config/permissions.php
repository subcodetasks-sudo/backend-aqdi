<?php

return [
    'actions' => [
        'view' => ['ar' => 'عرض القسم', 'en' => 'View'],
        'create' => ['ar' => 'إضافة', 'en' => 'Create'],
        'edit' => ['ar' => 'تعديل', 'en' => 'Edit'],
        'delete' => ['ar' => 'حذف', 'en' => 'Delete'],
        'retrieve' => ['ar' => 'استرجاع', 'en' => 'Retrieve'],
    ],

    'sections' => [
        'analytics' => ['ar' => 'التحليلات', 'en' => 'Analytics'],
        'all_requests' => ['ar' => 'جميع الطلبات', 'en' => 'All Requests'],
        'completed_request' => ['ar' => 'طلب مكتمل', 'en' => 'Completed Request'],
        'incomplete_request' => ['ar' => 'طلب غير مكتمل', 'en' => 'Incomplete Request'],
        'completed_whatsapp_request' => ['ar' => 'طلب واتساب مكتمل', 'en' => 'Completed WhatsApp Request'],
        'incomplete_whatsapp_request' => ['ar' => 'طلب واتساب غير مكتمل', 'en' => 'Incomplete WhatsApp Request'],
        'returned_request' => ['ar' => 'طلب مسترجع', 'en' => 'Returned Request'],
        'request_classification' => ['ar' => 'تصنيف الطلبات', 'en' => 'Request Classification'],
        'roles' => ['ar' => 'الأدوار', 'en' => 'Roles'],
        'employees' => ['ar' => 'الموظفين', 'en' => 'Employees'],
        'employee_salaries' => ['ar' => 'رواتب الموظفين', 'en' => 'Employee Salaries'],
        'settings' => ['ar' => 'الاعدادات', 'en' => 'Settings'],
        'seo_crawl' => ['ar' => 'زحف وفحص الموقع', 'en' => 'Site crawl & audit'],
    ],

    /**
     * Role `name` values that skip the permission matrix (full access).
     *
     * @var list<string>
     */
    'full_access_roles' => [
        'admin',
        'super_admin',
        'superadmin',
        'super-admin',
    ],
];
