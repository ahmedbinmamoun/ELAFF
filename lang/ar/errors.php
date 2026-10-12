<?php

declare(strict_types=1);

/*
 * Messages for use-case failures, keyed by InvalidInput::$reason and by
 * domain exception reason codes.
 */
return [
    // InvalidInput reasons
    'required' => 'هذا الحقل مطلوب.',
    'too_long' => 'يجب ألا يتجاوز :max حرفاً.',
    'too_short' => 'يجب ألا يقل عن :min أحرف.',
    'invalid_url' => 'يجب أن يكون رابطاً صحيحاً يبدأ بـ http أو https.',
    'invalid_slug' => 'الرابط المختصر غير صالح: أحرف صغيرة وأرقام تفصل بينها شرطة واحدة.',
    'slug_taken' => 'الرابط المختصر «:slug» مستخدم بالفعل.',
    'unknown_reference' => 'العنصر المختار غير موجود.',
    'category_cycle' => 'لا يمكن وضع القسم تحت نفسه أو تحت أحد أقسامه الفرعية.',
    'category_closed_to_contributors' => 'هذا القسم غير متاح للكتّاب الضيوف.',
    'series_part_required' => 'حدّد رقم الجزء (1 أو أكثر) لمقال ضمن سلسلة.',
    'too_many_tags' => 'لا يمكن إضافة أكثر من :max وسماً.',
    'user_already_linked' => 'هذا المستخدم مرتبط بكاتب آخر.',
    'invalid_network' => 'اسم الشبكة «:network» غير صالح.',
    'category_has_articles' => 'لا يمكن حذف قسم يحتوي على مقالات. انقل مقالاته أولاً.',
    'category_has_children' => 'لا يمكن حذف قسم له أقسام فرعية.',
    'series_has_articles' => 'لا يمكن حذف سلسلة تحتوي على مقالات.',
    'author_has_articles' => 'لا يمكن حذف كاتب له مقالات منشورة باسمه.',
    'revision_category_missing' => 'قسم هذه النسخة لم يعد موجوداً، لا يمكن استعادتها.',
    'invalid_email' => 'البريد الإلكتروني غير صالح.',
    'email_taken' => 'البريد الإلكتروني مستخدم بالفعل.',
    'invalid_role' => 'الدور المختار غير صالح.',
    'cannot_demote_self' => 'لا يمكنك إزالة صلاحية المدير عن حسابك.',

    'invalid_icon' => 'رمز الأيقونة غير صالح.',
    'too_many_in_review' => 'لديك :max مقالات قيد المراجعة بالفعل. انتظر حتى يُراجع أحدها.',
    'captcha_failed' => 'تعذّر التحقق من أنك لست برنامجاً آلياً. أعد المحاولة.',
    'terms_required' => 'يجب الموافقة على شروط الكتابة.',
    'invalid_display_name' => 'الاسم مطلوب ولا يتجاوز 120 حرفاً.',
    'invalid_bio' => 'النبذة مطلوبة ولا تتجاوز 1000 حرف.',
    'invalid_phone' => 'رقم الهاتف غير صالح.',
    'rejection_reason_required' => 'اكتب سبب الرفض.',
    'no_pending_name_change' => 'لا يوجد طلب تغيير اسم.',
    'illegal_contributor_transition' => 'لا يمكن تنفيذ هذا الإجراء على الحساب في حالته الحالية.',

    // Print issues
    'issue_number_taken' => 'العدد رقم :number موجود بالفعل.',
    'number_not_positive' => 'رقم العدد يجب أن يكون 1 أو أكثر.',
    'invalid_date' => 'التاريخ غير صالح.',
    'not_pdf' => 'الملف المرفوع ليس ملف PDF.',
    'file_too_large' => 'حجم الملف أكبر من :max ميغابايت.',
    'issue_has_articles' => 'لا يمكن حذف عدد مرتبط بـ :count مقالاً. ألغِ ربطها أولاً.',
    'page_not_positive' => 'رقم الصفحة يجب أن يكون 1 أو أكثر.',
    'page_beyond_issue' => 'العدد :number فيه :pages صفحة فقط.',
    'illegal_issue_transition' => 'لا يمكن تنفيذ هذا الإجراء على العدد في حالته الحالية.',

    // Article workflow
    'own_article_approval' => 'لا يمكنك اعتماد مقال كتبته بنفسك.',
    'return_note_required' => 'اكتب ملاحظة للكاتب عند إعادة المقال.',
    'schedule_in_past' => 'يجب أن يكون موعد النشر في المستقبل.',
    'breaking_expiry_in_past' => 'يجب أن يكون موعد انتهاء الخبر العاجل في المستقبل.',
    'published_before_created' => 'لا يمكن أن يسبق تاريخ النشر تاريخ إنشاء المقال.',
    'illegal_transition' => 'لا يمكن نقل المقال من حالة «:from» إلى «:to».',
    'not_publishable' => 'لا يمكن نشر المقال، ينقصه: :missing.',
    'missing' => [
        'body or featured image' => 'نص المقال أو صورة رئيسية',
        'author' => 'كاتب',
    ],

    // Fallbacks
    'not_authorized' => 'ليست لديك صلاحية لتنفيذ هذا الإجراء.',
    'not_found' => 'العنصر المطلوب لم يعد موجوداً.',
    'failed' => 'تعذّر تنفيذ العملية.',
];
