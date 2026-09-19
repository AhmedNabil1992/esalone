@component('mail::message')
# تهانينا يا {{ $name }}! 🎉

يسعدنا إبلاغك بأن موقعك الإلكتروني الخاص بنظام إدارة الصالات قد تم إنشاؤه وتجهيزه بنجاح وهو الآن نشط وجاهز تماماً للاستخدام.

يمكنك زيارة موقعك الآن عبر الرابط التالي:

@component('mail::button', ['url' => 'https://' . $domain])
زيارة الموقع الإلكتروني
@endcomponent

رابط لوحة تحكم الإدارة:  
[https://{{ $domain }}/admin](https://{{ $domain }}/admin)

رابط العملاء:  
[https://{{ $domain }}/customer](https://{{ $domain }}/customer)


---
مع تحياتنا،  
فريق العمل
@endcomponent
