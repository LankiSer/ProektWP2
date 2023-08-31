<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе установки.
 * Необязательно использовать веб-интерфейс, можно скопировать файл в "wp-config.php"
 * и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки базы данных
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Параметры базы данных: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'ProektWP' );

/** Имя пользователя базы данных */
define( 'DB_USER', 'root' );

/** Пароль к базе данных */
define( 'DB_PASSWORD', '' );

/** Имя сервера базы данных */
define( 'DB_HOST', 'localhost' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу. Можно сгенерировать их с помощью
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}.
 *
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными.
 * Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'J)K7g1sFglq%/}CZ2}$[J_=/w^cKC{UkMmLbFndpe9%N.qrT{TEfVlhm/0seZL2~' );
define( 'SECURE_AUTH_KEY',  '~1+;A(txC*}.IMPHTsxZO~v.!Y]J#4sJZ6&B?I9pnLNiOI@-j`m2<R85~8X8X&%)' );
define( 'LOGGED_IN_KEY',    'OTwM~v)s|]YuiV:=Ofw%*PZO78Cyl${l4pQI&7tY?$]H4Fl[p9W9|U|J<Y~ bcku' );
define( 'NONCE_KEY',        'EAb:YwwDW~~Z^KcUv-ESZ82rLZ&^*`>qK**7@s9<4L<>4lfmi(dN_8V`H Q$K%IZ' );
define( 'AUTH_SALT',        'LXgf:>.x6]oR#q1s5~<8>}@u[Z8CYRZWBo7d9EKO7y?=slNb_9*tCJp_WnhF7k/A' );
define( 'SECURE_AUTH_SALT', 'N8Y8Y?2 }&+.eobf/oW<4Md/50;qymuJaIO3_yOEZ$d3Zu5-8&fv7`(R~,7FZv1U' );
define( 'LOGGED_IN_SALT',   ';!tCQRM>fwTjn-.T-Hj 6& %n:zb)w2J-*`1TG}3CT]Or$7:rrhpE]7H% ,~<@jq' );
define( 'NONCE_SALT',       'E0ibI1UCG#YqD@*~]Uy:+C:cmz6eD]Ao8w858mF6besPe+J*RPButF/W]5F=v:9/' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Произвольные значения добавляйте между этой строкой и надписью "дальше не редактируем". */



/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
