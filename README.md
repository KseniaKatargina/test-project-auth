# Auth Gateway Service

Мини-сервис авторизации и управления пользователями, предоставляющий REST API, отчёты и OAuth2 поддержку.

---

## Стек

- PHP 8.2+
- Symfony 6.4
- PostgreSQL 15
- Docker + Docker Compose
- Git
- PHPUnit 9 (тесты)

---

## Запуск проекта через Docker

1. Склонировать репозиторий:

git clone https://github.com/KseniaKatargina/test-project-auth.git
cd auth

2. Создать файл .env.local:

DATABASE_URL=pgsql://auth_user:auth_pass@db:5432/auth_db
APP_ENV=dev
APP_SECRET=<случайная_строка>

И создать файл .env.test:

DATABASE_URL=pgsql://auth_user:auth_pass@db:5432/auth_db_test
APP_ENV=test
APP_SECRET=<случайная_строка>


3. Поднять контейнеры:

 docker-compose -f docker-compose.yml up -d --build  

4. Выполнить миграции и загрузку фикстур:

docker exec -it symfony-app php bin/console doctrine:migrations:migrate
docker exec -it symfony-app php bin/console doctrine:fixtures:load

5. Для тестов:

docker exec -it auth-db psql -U auth_user -c "CREATE DATABASE auth_db_test_test;"
docker exec -it symfony-app php bin/console doctrine:migrations:migrate --env=test
docker exec -it symfony-app php bin/console doctrine:fixtures:load --env=test

## Тестовые учетные данные

| Роль  | Email                                         | Пароль    |
| ----- | --------------------------------------------- | --------- |
| ADMIN | [admin@example.com](mailto:admin@example.com) | adminpass |
| USER  | [user@example.com](mailto:user@example.com)   | userpass  |




## Архитектура и принятые решения

- **Контроллеры** тонкие — только обрабатывают запрос и делегируют логику в сервисы.
- **Сервисы** (`UserService`, `AuthService`, `ReportService`) содержат бизнес-логику.
- **Токены и сессии**:
  - `Token` — хранение `access` и `refresh` токенов.
  - `Session` — актуальные сессии пользователя.
- **Транзакции** применяются в методах, которые меняют несколько сущностей одновременно.
- **Логирование** через PSR-3 `LoggerInterface` на ключевые действия (`login`, `logout`, `register`, блокировка, ошибки).
- **Обработка ошибок** через кастомный `ApiException`, единый JSON формат:
{
  "status": "error",
  "message": "Описание ошибки"
}

## API

Все запросы требуют заголовок:
Authorization: Bearer <access_token>

### **Auth**

| Endpoint                 | Method | Описание                                     |
|--------------------------|--------|---------------------------------------------|
| /api/login               | POST   | Login, возвращает `access` + `refresh` token |
| /api/token/refresh       | POST   | Обновление access token через refresh token |
| /api/me                  | GET    | Личный кабинет, возвращает профиль пользователя |
| /api/me                  | PATCH  | Обновление профиля текущего пользователя   |
| /api/me/sessions         | GET    | Список активных сессий пользователя        |
| /api/me/sessions/{id}    | DELETE | Удаление сессии пользователя               |
| /api/me/api/logout       | POST   | Logout, удаление текущей сессии            |

### **Пользователи (только ADMIN)**

| Endpoint                   | Method | Описание                       |
|----------------------------|--------|-------------------------------|
| /api/users/register        | POST   | Создание пользователя         |
| /api/users                 | GET    | Список всех пользователей     |
| /api/users/{id}            | GET    | Просмотр конкретного пользователя |
| /api/users/{id}            | PATCH  | Обновление пользователя       |
| /api/users/{id}/block      | POST   | Блокировка пользователя       |

### **Отчёты**

| Endpoint                      | Method | Описание                                                      |
|-------------------------------|--------|---------------------------------------------------------------|
| /api/reports/active-users      | GET    | Количество активных пользователей по ролям (view `active_users_by_role`) |
| /api/reports/blocked-users     | GET    | Список заблокированных пользователей (view `blocked_users`) |
| /api/reports/active-sessions   | GET    | Список активных сессий (view `active_sessions`)             |


## Тесты

Используется **PHPUnit**.

### Команда запуска всех тестов:

docker exec -it symfony-app php bin/phpunit

### Покрытие тестами 

- **Auth**:  
  - login (успешный и с неверным паролем)   

- **Личный кабинет** (`/api/me`):  
  - Получение профиля (GET) 

- **Пользователи (через сервис UserService)**:  
  - Получение всех пользователей 


## Пример использования

### Login
curl -X POST http://localhost:8080/api/login \
-H "Content-Type: application/json" \
-d '{"email":"admin@example.com","password":"adminpass"}'  

**Ответ:**
{
  "status": "success",
  "data": {
    "access_token": "...",
    "expires_at": "...",
    "refresh_token": "...",
    "refresh_expires_at": "..."
  }
}
