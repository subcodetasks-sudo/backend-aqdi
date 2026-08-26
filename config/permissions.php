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
        'operating_expenses' => ['ar' => 'مصاريف تشغيلية', 'en' => 'Operating Expenses'],
        'finance_expenses' => ['ar' => 'مصاريف (قديم)', 'en' => 'Finance Expenses'],
        'all_requests' => ['ar' => 'جميع الطلبات', 'en' => 'All Requests'],
        'completed_request' => ['ar' => 'طلب مكتمل', 'en' => 'Completed Request'],
        'incomplete_request' => ['ar' => 'طلب غير مكتمل', 'en' => 'Incomplete Request'],
        'completed_whatsapp_request' => ['ar' => 'طلب واتساب مكتمل', 'en' => 'Completed WhatsApp Request'],
        'incomplete_whatsapp_request' => ['ar' => 'طلب واتساب غير مكتمل', 'en' => 'Incomplete WhatsApp Request'],
        'returned_request' => ['ar' => 'طلب مسترجع', 'en' => 'Returned Request'],
        'request_classification' => ['ar' => 'تصنيف الطلبات', 'en' => 'Request Classification'],
        'roles' => ['ar' => 'الأدوار', 'en' => 'Roles'],
        'permissions' => ['ar' => 'الصلاحيات', 'en' => 'Permissions'],
        'employees' => ['ar' => 'الموظفين', 'en' => 'Employees'],
        'employee_salaries' => ['ar' => 'رواتب الموظفين', 'en' => 'Employee Salaries'],
        'employee_kpis' => ['ar' => 'مؤشرات الموظفين', 'en' => 'Employee KPIs'],
        'users' => ['ar' => 'العملاء', 'en' => 'Users / Clients'],
        'notifications' => ['ar' => 'الإشعارات', 'en' => 'Push Notifications'],
        'payments' => ['ar' => 'المدفوعات', 'en' => 'Payments'],
        'contract_payments' => ['ar' => 'تحصيل الموظف', 'en' => 'Employee Contract Payments'],
        'regions' => ['ar' => 'المناطق', 'en' => 'Regions'],
        'cities' => ['ar' => 'المدن', 'en' => 'Cities'],
        'real_estates' => ['ar' => 'العقارات', 'en' => 'Real Estates'],
        'property_reference' => ['ar' => 'مرجع العقارات والوحدات', 'en' => 'Property & Unit Reference'],
        'tenant_roles' => ['ar' => 'صفات المستأجر', 'en' => 'Tenant Roles'],
        'contract_statuses' => ['ar' => 'حالات العقد', 'en' => 'Contract Statuses'],
        'draft_contract_statuses' => ['ar' => 'حالات المسودة', 'en' => 'Draft Contract Statuses'],
        'contract_periods' => ['ar' => 'فترات العقد', 'en' => 'Contract Periods'],
        'contract_whatsapp' => ['ar' => 'طلبات واتساب', 'en' => 'Contract WhatsApp'],
        'instrument_settings' => ['ar' => 'إعدادات نوع الصك', 'en' => 'Instrument Type Settings'],
        'coupons' => ['ar' => 'الكوبونات', 'en' => 'Coupons'],
        'blogs' => ['ar' => 'المدونة', 'en' => 'Blogs'],
        'ads' => ['ar' => 'الإعلانات', 'en' => 'Ads'],
        'faqs' => ['ar' => 'الأسئلة الشائعة', 'en' => 'FAQs'],
        'paperworks' => ['ar' => 'المستندات', 'en' => 'Paperworks'],
        'popup_contracts' => ['ar' => 'نوافذ العقود', 'en' => 'Popup Contracts'],
        'instruction_sections' => ['ar' => 'الأقسام التعليمية', 'en' => 'Instruction Sections'],
        'message_alerts' => ['ar' => 'رسائل توضيحية', 'en' => 'Message Alerts'],
        'app_content' => ['ar' => 'محتوى التطبيق', 'en' => 'App Content'],
        'payment_messages' => ['ar' => 'رسائل الدفع', 'en' => 'Payment Messages'],
        'settings' => ['ar' => 'الاعدادات', 'en' => 'Settings'],
        'sms' => ['ar' => 'الرسائل النصية', 'en' => 'SMS'],
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
