Estamos usando **React com Vite dentro do Laravel** (ou seja, um projeto **monolítico** com frontend e backend juntos), isso simplifica ainda mais a estrutura e o deploy, pois o Vite entrega os assets diretamente e o Laravel serve tudo no mesmo projeto. Nesse caso, siga as orientações abaixo adaptadas à sua arquitetura atual:

---

## 📦 Estrutura do Projeto

Você tem:

```
/var/www/laravel-back
├── app/
├── bootstrap/
├── public/
│   └── build/          <-- saída do Vite
├── resources/
│   └── js/             <-- código React
├── routes/
│   └── web.php         <-- rotas Laravel
├── .env                <-- APP_NAME=LUMEN
```

---

## ✅ Etapas para Deploy do Laravel + React (Vite)

### 1. **Acessar o servidor**

```bash
ssh root@209.97.156.243
cd /var/www
```

### 2. **Clonar ou enviar o projeto**

```bash
git clone https://github.com/seu-usuario/seu-repo.git laravel-back
cd laravel-back
```

---

### 3. **Instalar dependências**

#### 🐘 Backend Laravel:
```bash
composer install
cp .env.example .env
php artisan key:generate
```

#### ⚛️ Frontend React com Vite:
```bash
npm install
npm run build
```

> Isso gerará os arquivos Vite em `public/build/`.

---

### 4. **Ajustar permissões**

```bash
chown -R www-data:www-data /var/www/laravel-back
chmod -R 775 storage bootstrap/cache
```

---

### 5. **NGINX Configuration**

Crie a config do domínio:

```bash
nano /etc/nginx/sites-available/gerador.lumenpublica.com.br
```

Cole esse conteúdo:

```nginx
server {
    listen 80;
    server_name gerador.lumenpublica.com.br;

    root /var/www/laravel-back/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Ative o site:
```bash
ln -s /etc/nginx/sites-available/gerador.lumenpublica.com.br /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

### 6. **Certificado HTTPS com Let's Encrypt**

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d gerador.lumenpublica.com.br
```

---

## ✅ Verificações Finais

- Laravel (`php artisan serve`) **não é necessário** em produção, pois o NGINX com PHP-FPM cuida disso.
- Você **não precisa de banco**, então pode comentar ou limpar a seção `DB_*` no `.env`.
- Certifique-se de que `APP_NAME=LUMEN` no `.env` está como deseja — esse nome aparece em notificações, erros, logs, etc.

---

## ✅ Comando final de build e deploy (resumo rápido)

```bash
cd /var/www/laravel-back
composer install
npm install && npm run build
php artisan key:generate
chown -R www-data:www-data .
```

---

Se quiser, posso te gerar um **script de instalação automática `.sh`** para colocar tudo em produção rapidamente. Deseja isso?


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development/)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
