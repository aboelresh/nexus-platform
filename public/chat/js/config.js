const Config = {
  API_BASE: '/api/v1',
  WS_KEY: 'f2z4g861ahrhwjecgakq',
  WS_HOST: 'localhost',
  WS_PORT: 8080,
  AUTH_ENDPOINT: '/api/broadcasting/auth',
  STORAGE_TOKEN: 'nexus_token',
  STORAGE_USER: 'nexus_user',
  STORAGE_THEME: 'nexus_theme',
  STORAGE_LANG: 'nexus_lang',
  MAX_FILE_SIZE: 100 * 1024 * 1024,
  MAX_IMAGE_SIZE: 10 * 1024 * 1024,
  TYPING_DEBOUNCE: 2000,
  MESSAGES_PER_PAGE: 30,
  QUICK_EMOJIS: ['👍','❤️','😂','😮','😢','🔥','👏','✅'],
};

const i18n = {
  ar: {
    login: 'تسجيل الدخول', register: 'إنشاء حساب', logout: 'تسجيل الخروج',
    email: 'البريد الإلكتروني', password: 'كلمة المرور', name: 'الاسم الكامل',
    username: 'اسم المستخدم', send: 'إرسال', cancel: 'إلغاء', save: 'حفظ',
    search: 'بحث...', new_conv: 'محادثة جديدة', new_group: 'مجموعة جديدة',
    loading: 'جاري التحميل...', no_messages: 'لا توجد رسائل بعد',
    type_message: 'اكتب رسالة...', is_typing: 'يكتب...',
    reply: 'رد', edit: 'تعديل', delete: 'حذف', copy: 'نسخ', forward: 'تحويل', pin: 'تثبيت',
    voice_call: 'مكالمة صوتية', video_call: 'مكالمة مرئية',
    upload_image: 'رفع صورة', upload_file: 'رفع ملف', record_voice: 'تسجيل صوتي',
    profile: 'الملف الشخصي', settings: 'الإعدادات', edit_profile: 'تعديل الملف',
    change_password: 'تغيير كلمة المرور', change_email: 'تغيير البريد',
    direct: 'محادثة مباشرة', group: 'مجموعة', online: 'متصل', offline: 'غير متصل',
    members: 'أعضاء', add_member: 'إضافة عضو', leave_group: 'مغادرة المجموعة',
    err_fill: 'يرجى ملء جميع الحقول', err_connect: 'خطأ في الاتصال',
    msg_sent: 'تم الإرسال', msg_deleted: 'تم الحذف', profile_updated: 'تم تحديث الملف الشخصي',
  },
  en: {
    login: 'Login', register: 'Register', logout: 'Logout',
    email: 'Email', password: 'Password', name: 'Full Name',
    username: 'Username', send: 'Send', cancel: 'Cancel', save: 'Save',
    search: 'Search...', new_conv: 'New Chat', new_group: 'New Group',
    loading: 'Loading...', no_messages: 'No messages yet',
    type_message: 'Type a message...', is_typing: 'is typing...',
    reply: 'Reply', edit: 'Edit', delete: 'Delete', copy: 'Copy', forward: 'Forward', pin: 'Pin',
    voice_call: 'Voice Call', video_call: 'Video Call',
    upload_image: 'Upload Image', upload_file: 'Upload File', record_voice: 'Record Voice',
    profile: 'Profile', settings: 'Settings', edit_profile: 'Edit Profile',
    change_password: 'Change Password', change_email: 'Change Email',
    direct: 'Direct Message', group: 'Group', online: 'Online', offline: 'Offline',
    members: 'Members', add_member: 'Add Member', leave_group: 'Leave Group',
    err_fill: 'Please fill all fields', err_connect: 'Connection error',
    msg_sent: 'Sent', msg_deleted: 'Deleted', profile_updated: 'Profile updated',
  }
};

function t(key) {
  const lang = localStorage.getItem(Config.STORAGE_LANG) || 'ar';
  return i18n[lang]?.[key] || i18n['en'][key] || key;
}