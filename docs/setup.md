# Подготовка системы

_Инструкции актуальны для Ubuntu 22.4_

Нужно чтобы было:
* git
* docker + docker-compose
    * [Install Docker Engine on Ubuntu](https://docs.docker.com/engine/install/ubuntu/)
    * [How To Install and Use Docker on Ubuntu 22.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-22-04)
    * [How To Install and Use Docker Compose on Ubuntu 22.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04)
* для удобства работы с sail [добавить алиас](https://laravel.com/docs/11.x/sail#configuring-a-shell-alias)
    * `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'`

# Настройка и запуск

## 1. Клонирование репозитория

```shell
git clone git@github.com:kneetech/jabbakhBot.git
cd jabbakhBot
```

## 2. Публикация конфигов

Для среды dev:

```shell
cp docker-compose.dev.yml docker-compose.yml
cp docker/dev.Dockerfile docker/Dockerfile
cp .env.example .env
cp .docker.env.example .docker.env  
```

Для среды prod:

```shell
cp docker-compose.prod.yml docker-compose.yml
cp docker/prod.Dockerfile docker/Dockerfile
cp .env.example .env
cp .docker.env.example .docker.env  
```

## 3. Настройка конфигов

На что обратить внимание:
* .env
    * `APP_URL` _(ручка, которая будет смотреть на внешний порт контейнера nginx)_
    * `BOT_NAME` _(имя бота)_
    * `BOT_TOKEN` _(токен бота)_
    * `DB_DATABASE`
    * `DB_USERNAME`
    * `DB_PASS`
* .docker.env
    * `DB_DATABASE`
    * `DB_USERNAME`
    * `DB_PASS`
* docker/Dockerfile
    * для среды dev ID юзера sail должен совпадать с локальным `echo $UID`
      (по-умолчанию `1000`)
* docker-compose.yml
    * внешний порт контейнера nginx должен быть свободен
      (по-умолчанию `8531`)
    * для среды dev должен быть свободен также внешний порт контейнера db
      (по-умолчанию `65432`)

## 4. Запуск и остановка приложения

### Запуск

```shell
sail up -d
```

### Первичная инициализация

#### Установка зависимостей composer

```shell
sail composer install
```

#### Генерация уникального ключа приложения

```shell
sail artisan key:generate
```

#### Запуск миграций

```shell
sail artisan migrate
```

#### Проинициализировать БД 

```shell
sail artisan db:seed
```

В результате этих действий живое приложение должно торчать
через внешний порт контейнера (по-умолчанию `8531`)

### Остановка приложения

Для остановки приложения:
```shell
sail down
```

### Пересборка контейнеров

Для [пересборки контейнеров](https://laravel.com/docs/11.x/sail#rebuilding-sail-images)
(сбросить виртуальные машины, в т.ч. БД):

```shell
docker compose down -v
sail build --no-cache
sail up
```

## 5. Запуск и остановка бота

Бот получает сообщения от сервера телеграм через свой метод (хук)
`{APP_URL}/bot/{BOT_TOKEN}/webhook`, который должен быть доступен во
внешней сети интернет.

Параметры `APP_URL` и `BOT_TOKEN` задаются в файле `.env`. Также для запуска
бота необходимо заполнить `BOT_NAME`.

### Запуск бота

Регистрация хука. Телеграм начинает отправлять сообщения серверу бота

```shell
sail artisan bot:start --get-updates
```

При добавлении флага `--get-updates` телеграм передаст все необработанные
события бота за последние 24 часа

### Остановка бота

Снятие хука. Телеграм продолжает обрабатывать и хранить события бота 24 часа

```shell
sail artisan bot:stop
```

_ВНИМАНИЕ: Останавливать бот желательно всегда перед остановкой
контейнера, а также если бот выдаёт ошибки `500`, т.к. сервер телеграм банит
хуки, которые отвечают некорректно._

### Локальный сервер для разработки

Для разработки удобно использовать сервис проброса локального
порта во внешний интернет [TUNA](https://tuna.am/) (бесплатный, но требует
регистрации):

```shell
$ tuna http 8531
INFO[22:33:08] Welcome to Tuna                              
INFO[22:33:09] Account: MK (Free)                           
INFO[22:33:09] Web Interface: http://127.0.0.1:4040         
INFO[22:33:09] Forwarding https://dfmui7-178-46-68-159.ru.tuna.am -> 127.0.0.1:8531 
```

Временный домен (действует 30 минут) достаточно прописать в .env:

```env 
APP_URL=https://dfmui7-178-46-68-159.ru.tuna.am
```


