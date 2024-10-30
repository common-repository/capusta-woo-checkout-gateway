=== Capusta.Space payment gateway for Woocommerce ===
Contributors: capusta
Tags: capusta, capusta.space, payment gateway, capusta woocommerce, ecommerce, woo-commerce, woocommerce ===
Requires at least: 5.0.2
Tested up to: 6.0
Requires PHP: >=7.4
Stable tag: 1.2.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Плагин от Capusta.Space позволяет собирать донаты на сайтах Wordpress и принимать онлайн-оплату банковскими картами Visa, Mastercard, Maestro, МИР за заказы в проектах, созданных через Woocommerce.

== Description ==

Capusta.Space – сервис по приёму платежей в сети Интернет, позволяющий принимать онлайн-оплату банковскими картами Visa, Mastercard, МИР

Основные преимущества:

* Быстрая интеграция
* Отсутствие абонентской платы и скрытых комиссий
* Все наиболее востребованные способы оплаты
* Удобный личный кабинет
* Поддержка 24/7

Сайт проекта: https://capusta.space
Поддержка: support@capusta.space

== Installation ==

Этапы установки плагина на сайт:

1. Скачайте репозиторий в папку /wp-content/plugins/wc-capusta
2. Активируйте плагин в настройках WordPress /wp-admin/plugins.php
3. Настройте параметры подключения http(s)://your-domain.ru/wp-admin/admin.php?page=wc-settings&tab=checkout&section=capusta

Настройка магазина на стороне [Capusta.Space](https://capusta.space)
Необходимо через техподдержку сервиса Capusta прописать следующие параметры в вашем проекте:
1. Result Url – http(s)://your-domain.ru/?wc-api=WC_Capusta&action=result
2. Success Url – http(s)://your-domain.ru/?wc-api=WC_Capusta&action=success&payment_id=
3. Fail Url – http(s)://your-domain.ru/?wc-api=WC_Capusta&action=fail&payment_id=


Настройка на стороне сайта:
1. Указать платежные данные: merchantEmail, Token, projectCode

== Screenshots ==
1. assets/img/1.png страница настройки плагина

== Frequently Asked Questions ==
1. В: Установил плагин, включаю его в списке, а он после сохранения остается выключенным.
   О: Проверьте настройки валюты магазина. Сервис Capusta.Space работает  только с Российскими Рублями и Узбекскимии сумами.

2. В: В работе возникают ошибки вида: Fatal error: Uncaught Error: Call to undefined function...
   О: У вас отсутствует какая-то функция в PHP. Нужно доустановить отсутствующие функции на сервер и перезапустить php-fpm, если он у вас используется.

== Changelog ==
= 1.0.1 =
* Добавлена локализация на русский язык

= 1.1.2 =
* Исправление ошибки подписи модуля

= 1.2.0 =
* проверка подписи модуля при отсутствии заголовка

= 1.2.1 =
* обновление vendors

= 1.2.2 =
*  обновление версии

 == Upgrade Notice ==
= 1.0.1 No comments
= 1.1.2 No comments
= 1.2.0 No comments
= 1.2.1 No comments
= 1.2.2 No comments
= 1.2.3 No comments
= 1.2.4 =
*  Проверка работы с WP 6.0 и  Woocommerce 6.5.1
= 1.2.5 =
*  Добавлена работа с UZS
= 1.2.6 =
*  No comments
= 1.2.7 =
* fixed requirements for PHP version