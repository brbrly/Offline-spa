FROM php:8.2-apache

# تفعيل الإضافات المطلوبة لـ PHP و MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# نسخ ملفات المشروع إلى مجلد Apache
COPY . /var/www/html/

# ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

EXPOSE 80
