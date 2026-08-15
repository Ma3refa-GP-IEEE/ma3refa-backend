# استخدام نسخة PHP فيها الإضافات الشائعة لـ Laravel
FROM php:8.3-cli

# تثبيت الأدوات والمكتبات المطلوبة لإضافات PHP الخاصة بـ Laravel/MySQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# مجلد العمل جوه الـ container
WORKDIR /app

# نسخ ملفات المشروع كاملة
COPY . .

# تثبيت حزم PHP (بدون حزم التطوير، للإنتاج)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# صلاحيات مجلدات الكاش والـ storage (Laravel محتاج يكتب فيهم)
RUN chmod -R 775 storage bootstrap/cache

# فتح البورت (Render بيحدد البورت الفعلي عن طريق متغير PORT وقت التشغيل)
EXPOSE 8080

# أمر التشغيل: يشغل سيرفر PHP المدمج على البورت اللي Render بيحدده
CMD php artisan config:cache && php artisan serve --host 0.0.0.0 --port ${PORT:-8080}
